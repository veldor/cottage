<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 23:26
 */

namespace app\models;

use app\models\database\Mail;
use app\models\interfaces\CottageInterface;
use app\models\tables\Table_penalties;
use DOMDocument;
use DOMElement;
use DOMXpath;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\web\NotFoundHttpException;

class Cottage extends Model
{
    public $globalInfo;
    public $filledPower;
    public $lastPowerFillDate;
    public ?string $powerDataCancellable = null;
    public bool $powerDataAdditionalCancellable = false;
    public $powerPayDifference = '';
    public $membershipDebts = 0;
    public $targetDebts = 0;
    public $powerDebts = 0;
    public $unpayedBills = 0;
    public $counterChanged = false;
    public $totalDebt = 0;
    public array $additionalCottageInfo;
    public array $fines;

    /**
     * Cottage constructor.
     * @param $cottageId
     * @throws NotFoundHttpException
     */
    public function __construct($cottageId)
    {
        parent::__construct();
        // заполню общую информацию об участке
        $this->globalInfo = self::getCottageInfo($cottageId);
        if ($this->globalInfo->cottageNumber === null) {
            throw new NotFoundHttpException('Участка с таким номером не существует');
        }
        if ($this->globalInfo->cottageNumber !== 0) {
            // проверю, не менялся ли в прошлом месяце счётчик
            $this->counterChanged = CounterChangeHandler::checkChange($this->globalInfo);
            // проверю созданные и неоплаченные счета
            $this->unpayedBills = Table_payment_bills::findOne(['cottageNumber' => $this->globalInfo->cottageNumber, 'isPayed' => 0]);
            $powerStatus = PowerHandler::getCottageStatus($this->globalInfo);
            $this->filledPower = $powerStatus['filledPower'];
            $this->lastPowerFillDate = $powerStatus['lastPowerFillDate'];
            $this->powerPayDifference = $powerStatus['powerPayDifference'];
            // определю, можно ли удалить данные по потреблению электроэнергии. Можно, если ещё не поступала оплата
            $this->powerDataCancellable = PowerHandler::isDataCancellable($this->globalInfo);
            $this->powerDebts = PowerHandler::getDebtAmount($this->globalInfo);
            if ($this->powerDebts !== $this->globalInfo->powerDebt) {
                $this->globalInfo->powerDebt = $this->powerDebts;
                $this->globalInfo->save();
            }
            // Посчитаю задолженности
            $duty = MembershipHandler::getDebt($this->globalInfo);
            $this->membershipDebts = 0;
            if (!empty($duty)) {
                foreach ($duty as $item) {
                    $this->membershipDebts += CashHandler::toRubles($item->amount - $item->partialPayed);
                }
            }
            $this->targetDebts = TargetHandler::getDebtAmount($this->globalInfo);
            $this->totalDebt = CashHandler::toRubles($this->membershipDebts) + CashHandler::toRubles($this->targetDebts) + CashHandler::toRubles($this->powerDebts) + CashHandler::toRubles($this->globalInfo->singleDebt);

            // проверю, не привязан ли дополнительный участок
            if ($this->globalInfo->haveAdditional) {
                $this->additionalCottageInfo = AdditionalCottage::getCottageInfo($cottageId);
                if (!$this->unpayedBills && !empty($this->additionalCottageInfo['powerStatus']['lastPowerFillDate']) && $this->additionalCottageInfo['powerStatus']['lastPowerFillDate'] === TimeHandler::getPreviousShortMonth() && $this->additionalCottageInfo['powerStatus']['powerPayed'] === 'no') {
                    $this->powerDataAdditionalCancellable = true;
                }
            }
            $this->fines = Table_penalties::find()->where(['cottage_number' => $cottageId])->all();
        } else {
            $this->lastPowerFillDate = TimeHandler::getCurrentShortMonth();
        }
    }

    /**
     * @param $cottageInfo Table_cottages
     */
    public static function recalculateTargetDebt(Table_cottages $cottageInfo): void
    {
        $targetDuty = 0;
        // проверю, есть ли долги
        $targetsInfo = $cottageInfo->targetPaysDuty;
        if (!empty($targetsInfo)) {
            // загружу сведения в DOM
            $targetsDom = new DOMDocument('1.0', 'UTF-8');
            $targetsDom->loadXML($cottageInfo->targetPaysDuty);
            $targetsXpath = new DOMXpath($targetsDom);
            $targets = $targetsXpath->query('/targets/target');
            if ($targets->length > 0) {
                /**
                 * @var $target DOMElement
                 */
                foreach ($targets as $target) {
                    $summ = CashHandler::toRubles($target->getAttribute('summ'));
                    $payed = CashHandler::toRubles($target->getAttribute('payed'));
                    $targetDuty += $summ - $payed;
                }
            }
        }
        $cottageInfo->targetDebt = $targetDuty;
        $cottageInfo->save();
    }

    /**
     * @param $cottageInfo Table_cottages
     */
    public static function recalculateSingleDebt(Table_cottages $cottageInfo): void
    {
        $singleDuty = 0;
        // проверю, есть ли долги
        if (!empty($cottageInfo->singlePaysDuty)) {
            // загружу сведения в DOM
            $targetsDom = new DOMDocument('1.0', 'UTF-8');
            $targetsDom->loadXML($cottageInfo->singlePaysDuty);
            $targetsXpath = new DOMXpath($targetsDom);
            $targets = $targetsXpath->query('/singlePayments/singlePayment');
            if ($targets->length > 0) {
                /**
                 * @var $target DOMElement
                 */
                foreach ($targets as $target) {
                    $summ = CashHandler::toRubles($target->getAttribute('summ'));
                    $payed = CashHandler::toRubles($target->getAttribute('payed'));
                    $singleDuty += $summ - $payed;
                }
            }
        }
        $cottageInfo->singleDebt = $singleDuty;
        $cottageInfo->save();
    }

    /**
     * @param $cottageNumber string
     * @param bool $double
     * @return Table_cottages|Table_additional_cottages
     */
    public static function getCottageInfo(string $cottageNumber, $double = false)
    {
        if (is_int((int)$cottageNumber)) {
            if ($double) {
                $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
            } else {
                $cottageInfo = Table_cottages::findOne($cottageNumber);
            }
            if ($cottageInfo) {
                return $cottageInfo;
            }
        }
        throw new InvalidArgumentException('Неверный номер участка');
    }

    /**
     * @param bool $double
     * @return CottageInterface[]
     */
    public static function getRegister($double = false): array
    {
        if ($double) {
            //todo убедиться, что тут корректно обрабатывается выборка
            return Table_additional_cottages::find()->orderBy('masterId')->all();
        }
        return Table_cottages::find()->orderBy('cottageNumber')->all();
    }

    /**
     * <b>Получение списка зарегистрированных участков в виде массива, ключами которого являются адреса участков</b>
     * @return CottageInterface[] <b>Основные участки массивом</b>
     */
    public static function getRegisteredList(): array
    {
        $answer = [];
        $data = Table_cottages::find()->orderBy('cottageNumber')->all();
        if (!empty($data)) {
            /** @var CottageInterface $item */
            foreach ($data as $item) {
                $answer[$item->getCottageNumber()] = $item;
            }
        }
        return $answer;
    }

    /**
     * Проверка, является ли участок основным
     * @param $cottageInfo
     * @return bool
     */
    public static function isMain($cottageInfo): bool
    {
        return !$cottageInfo instanceof Table_additional_cottages;
    }

    public static function getLiteralInfo($key)
    {
        $re = '/^(\d+)(-a)?$/';
        $match = null;
        if (preg_match($re, $key, $match) && count($match) === 2) {
            return self::getCottageInfo($match[1]);
        }
        return self::getCottageInfo($match[1], true);
    }

    /**
     * @param $cottage CottageInterface
     * @return bool
     */
    public static function hasMail(CottageInterface $cottage): bool
    {
        return Mail::find()->where(['cottage' => $cottage->getCottageNumber()])->count();
    }

    /**
     * @param $cottage Table_additional_cottages|Table_cottages
     * @return int|mixed|string
     */
    public static function getCottageNumber($cottage)
    {
        if (self::isMain($cottage)) {
            return $cottage->cottageNumber;
        }
        return $cottage->masterId . "-a";
    }

    /**
     * @param $key
     * @return Table_additional_cottages|Table_cottages
     */
    public static function getCottageByLiteral($key)
    {
        $additional = false;
        if (strpos($key, '-a')) {
            $additional = true;
        }
        return self::getCottageInfo((int)$key, $additional);
    }

    /**
     * @param CottageInterface $cottage
     * @return bool
     * @throws Exception
     */
    public static function hasPayUpDuty(CottageInterface $cottage): bool
    {
        $time = time();
        // получу данные по целевым задолженностям
        $duty = TargetHandler::getDebt($cottage);
        if (!empty($duty)) {
            foreach ($duty as $value) {
                $tariff = Table_tariffs_target::findOne(['year' => $value->year]);
                if ($tariff !== null && $tariff->payUpTime < $time) {
                    return true;
                }
            }
        }
        // получу данные о первом неоплаченном месяце
        $firstUnpaidMonth = Table_power_months::getFirstUnpaid($cottage);
        if ($firstUnpaidMonth !== null && TimeHandler::getPayUpMonth($firstUnpaidMonth->month) < $time) {
            return true;
        }

        // если не оплачен текущий квартал
        if (MembershipHandler::getLastPayedQuarter($cottage) < TimeHandler::getPrevQuarter(TimeHandler::getCurrentQuarter())) {
            return true;
        }

        if (MembershipHandler::getLastPayedQuarter($cottage) === TimeHandler::getPrevQuarter(TimeHandler::getCurrentQuarter())) {
            $payUp = TimeHandler::getPayUpQuarterTimestamp(TimeHandler::getCurrentQuarter());
            $dayDifference = TimeHandler::checkDayDifference($payUp);
            if ($dayDifference > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public static function getPreviousCottage(): string
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/https:\/\/dev\.com\/show-cottage\/(\d+)/', $link, $matches)) {
            while ($next = --$matches[1]) {
                try {
                    if (self::getCottageByLiteral($next) !== null) {
                        return 'https://dev.com/show-cottage/' . $next;
                    }
                    if ($next < 1) {
                        break;
                    }
                } catch (Exception $e) {

                }
            }
        }
        return 'https://dev.com/show-cottage/180';
    }

    /**
     * @return string
     */
    public static function getNextCottage(): string
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/https:\/\/dev\.com\/show-cottage\/(\d+)/', $link, $matches)) {
            while ($next = ++$matches[1]) {
                try {
                    if (self::getCottageByLiteral($next) !== null) {
                        return 'https://dev.com/show-cottage/' . $next;
                    }
                    if ($next > 180) {
                        break;
                    }
                } catch (Exception $e) {

                }
            }
        }
        return 'https://dev.com/show-cottage/1';
    }

    /**
     * Верну участок
     * @param $cottageNumber <p>Номер участка</p>
     * @param bool $additional <p>Метка дополнительного участка</p>
     * @return Table_additional_cottages|Table_cottages|null <p>Верну экземпляр участка</p>
     */
    public static function getCottage($cottageNumber, bool $additional)
    {
        if ($additional) {
            return Table_additional_cottages::findOne(['masterId' => $cottageNumber]);
        }
        return Table_cottages::findOne(['cottageNumber' => $cottageNumber]);
    }
}