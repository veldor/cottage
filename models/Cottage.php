<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 23:26
 */

namespace app\models;

use app\models\tables\Table_penalties;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\web\NotFoundHttpException;

class Cottage extends Model
{
    public $globalInfo;
    public $filledPower;
    public $lastPowerFillDate;
    public $powerDataCancellable = false;
    public $powerDataAdditionalCancellable = false;
    public $powerPayDifference = '';
    public $membershipDebts = 0;
    public $targetDebts = 0;
    public $powerDebts = 0;
    public $unpayedBills = 0;
    public $counterChanged = false;
    public $totalDebt = 0;
    public $additionalCottageInfo;
    public $fines;

    /**
     * Cottage constructor.
     * @param $cottageId
     * @throws NotFoundHttpException
     * @throws \ErrorException
     */
    public function __construct($cottageId)
    {
        parent::__construct();
        // заполню общую информацию об участке
        $this->globalInfo = Cottage::getCottageInfo($cottageId);
        if (empty($this->globalInfo->cottageNumber)) {
            throw new NotFoundHttpException('Участка с таким номером не существует');
        }
        // проверю, не менялся ли в прошлом месяце счётчик
        $this->counterChanged = CounterChangeHandler::checkChange($this->globalInfo);
        // проверю созданные и неоплаченные счета
        $this->unpayedBills = Table_payment_bills::findOne(['cottageNumber' => $this->globalInfo->cottageNumber, 'isPayed' => 0]);
        $powerStatus = PowerHandler::getCottageStatus($this->globalInfo);
        $this->filledPower = $powerStatus['filledPower'];
        $this->lastPowerFillDate = $powerStatus['lastPowerFillDate'];
        $this->powerPayDifference = $powerStatus['powerPayDifference'];
        if (!$this->counterChanged && !$this->unpayedBills && $powerStatus['powerPayed'] === 'no' && ($this->lastPowerFillDate === TimeHandler::getPreviousShortMonth() || $this->lastPowerFillDate === TimeHandler::getCurrentShortMonth())) {
            $this->powerDataCancellable = true;
        }
        $this->powerDebts = $powerStatus['powerDebt'];
        // Посчитаю задолженности
        if ($this->globalInfo->individualTariff) {
            $this->membershipDebts = PersonalTariff::countMembershipDebt($this->globalInfo)['summ'];
        } else {
            $this->membershipDebts = MembershipHandler::getCottageStatus($this->globalInfo);
        }
        $this->targetDebts = $this->globalInfo->targetDebt;
        $this->powerDebts = $this->globalInfo->powerDebt;
        $this->totalDebt = CashHandler::toRubles($this->membershipDebts) + CashHandler::toRubles($this->targetDebts) + CashHandler::toRubles($this->powerDebts) + CashHandler::toRubles($this->globalInfo->singleDebt);

        // проверю, не привязан ли дополнительный участок
        if ($this->globalInfo->haveAdditional) {
            $this->additionalCottageInfo = AdditionalCottage::getCottageInfo($cottageId);
            if (!$this->unpayedBills && !empty($this->additionalCottageInfo['powerStatus']['lastPowerFillDate']) && $this->additionalCottageInfo['powerStatus']['lastPowerFillDate'] === TimeHandler::getPreviousShortMonth() && $this->additionalCottageInfo['powerStatus']['powerPayed'] === 'no') {
                $this->powerDataAdditionalCancellable = true;
            }
        }
        $this->fines = Table_penalties::find()->where(['cottage_number' => $cottageId])->all();
    }

    /**
     * @param $cottageInfo Table_cottages
     */
    public static function recalculateTargetDebt($cottageInfo)
    {
        $targetDuty = 0;
        // проверю, есть ли долги
        $targetsInfo = $cottageInfo->targetPaysDuty;
        if (!empty($targetsInfo)) {
            // загружу сведения в DOM
            $targetsDom = new \DOMDocument('1.0', 'UTF-8');
            $targetsDom->loadXML($cottageInfo->targetPaysDuty);
            $targetsXpath = new \DOMXpath($targetsDom);
            $targets = $targetsXpath->query('/targets/target');
            if ($targets->length > 0) {
                /**
                 * @var $target \DOMElement
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
    public static function recalculateSingleDebt($cottageInfo)
    {
        $singleDuty = 0;
        // проверю, есть ли долги
        if (!empty($cottageInfo->singlePaysDuty)) {
            // загружу сведения в DOM
            $targetsDom = new \DOMDocument('1.0', 'UTF-8');
            $targetsDom->loadXML($cottageInfo->singlePaysDuty);
            $targetsXpath = new \DOMXpath($targetsDom);
            $targets = $targetsXpath->query('/singlePayments/singlePayment');
            if ($targets->length > 0) {
                /**
                 * @var $target \DOMElement
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
     * @param $cottageNumber int
     * @param bool $double
     * @return Table_cottages|Table_additional_cottages
     */
    public static function getCottageInfo($cottageNumber, $double = false)
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
     * @return Table_cottages[]
     */
    public static function getRegistred($double = false)
    {
        if ($double) {
            return Table_additional_cottages::find()->where(['hasDifferentOwner' => 1])->orderBy('masterId')->all();
        }
        return Table_cottages::find()->orderBy('cottageNumber')->all();
    }

    /**
     * @return array
     */
    public static function getRegistredList(): array
    {
        $answer = [];
        $data = Table_cottages::find()->orderBy('cottageNumber')->all();
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    $answer[$item->cottageNumber] = $item;
                }
            } else {
                $answer[$data->cottageNumber] = $data;
            }
        }
        return $answer;
    }

    public static function isMain($cottageInfo)
    {
        return $cottageInfo instanceof Table_cottages;
    }

    public static function getLiteralInfo($key)
    {
        $re = '/^(\d+)(-a)?$/';
        $match = null;
        if (preg_match($re, $key, $match)) {
            if (count($match) === 2) {
                return self::getCottageInfo($match[1]);
            }
        }
        return self::getCottageInfo($match[1], true);
    }

    public static function getCottageInfoForMail($own, $cottageNumber)
    {

        // получу сведения о участке
        if ($own === 'main') {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber, true);
        }
        return $cottageInfo;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @return bool
     */
    public static function hasMail($cottage)
    {
        if (!empty($cottage->cottageOwnerEmail) || !empty($cottage->cottageContacterEmail)) {
            return true;
        }
        return false;
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

    public static function getCottageByLiteral($key)
    {
        $additional = false;
        if (strpos($key, '-a')) {
            $additional = true;
        }
        return self::getCottageInfo((int)$key, $additional);
    }

    public static function hasPayUpDuty(Table_cottages $cottage)
    {
        $time = time();
        // получу данные по целевым задолженностям
        $duty = TargetHandler::getDebt($cottage);
        if (!empty($duty)) {
            foreach ($duty as $value) {
                $tariff = Table_tariffs_target::findOne(['year' => $value->year]);
                if ($tariff->payUpTime < $time) {
                    return true;
                }
            }
        }
        // если не оплачен предыдущий месяц электроэнергии
        if ($cottage->powerDebt > 0) {
            $months = Table_power_months::find()->where(['month' => $cottage->membershipPayFor])->all();
            if (!empty($months)) {
                foreach ($months as $month) {
                    if ($month->difference > 0 && $month->month <= TimeHandler::getPreviousMonth()) {
                        return true;
                    }
                }
            }
        }
        // если не оплачен текущий квартал
        if ($cottage->membershipPayFor < TimeHandler::getPrevQuarter(TimeHandler::getCurrentQuarter())) {
            return true;
        } elseif ($cottage->membershipPayFor == TimeHandler::getPrevQuarter(TimeHandler::getCurrentQuarter())) {
            $payUp = TimeHandler::getPayUpQuarterTimestamp(TimeHandler::getCurrentQuarter());
            $dayDifference = TimeHandler::checkDayDifference($payUp);
            if ($dayDifference > 0) {
                return true;
            }
        }
        return false;
    }

    public static function getPreviousCottage()
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/https\:\/\/dev\.com\/show-cottage\/(\d+)/', $link, $matches)) {
            while ($next = --$matches[1]) {
                try{
                    if (!empty(Cottage::getCottageByLiteral($next))) {
                        return 'https://dev.com/show-cottage/' . $next;
                    }
                    if ($next < 1) {
                        break;
                    }
                }
                catch (\Exception $e){

                }
            }
        }
        return 'https://dev.com/show-cottage/180';
    }

    public static function getNextCottage()
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/https\:\/\/dev\.com\/show-cottage\/(\d+)/', $link, $matches)) {
            while ($next = ++$matches[1]) {
                try {
                    if (!empty(Cottage::getCottageByLiteral($next))) {
                        return 'https://dev.com/show-cottage/' . $next;
                    }
                    if ($next > 180) {
                        break;
                    }
                } catch (\Exception $e) {

                }
            }
        }
        return 'https://dev.com/show-cottage/1';
    }

    public static function getFullDebt(Table_cottages $cottage)
    {
        // получу задолженность по электроэнергии
        $powerDebt = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber, 'payed' => 'no'])->andWhere(['>', 'difference' , 0])->all();
        if(!empty($powerDebt)){
            foreach ($powerDebt as $item) {
                // найду оплату по счёту
                $payed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => $item->month])->all();
                $totalPay = CashHandler::toRubles($item->totalPay);
                if(!empty($payed)){
                    foreach ($payed as $payedItem) {
                        $totalPay -= CashHandler::toRubles($payedItem->summ);
                    }
                }
                if($totalPay > 0){
                    echo "Э " . $cottage->cottageNumber . " " . CashHandler::toRubles($totalPay) . '<br/>';
                }
            }
        }
        $membershipDebt = MembershipHandler::getDebt($cottage);
        if(!empty($membershipDebt)){
            foreach ($membershipDebt as $item) {
                if(!empty($item->partialPayed)){
                    echo "Ч " . $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount - $item->partialPayed) . '<br/>';
                }
                else{
                    echo "Ч " . $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount) . '<br/>';
                }
            }
        }
        $targetDebt = TargetHandler::getDebt($cottage);
        if(!empty($targetDebt)){
            foreach ($targetDebt as $item) {
                if(!empty($item->partialPayed)){
                    echo "Ц " .  $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount - $item->partialPayed) . '<br/>';
                }
                else{
                    echo "Ц " .  $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount) . '<br/>';
                }
            }
        }
        $singleDebt = SingleHandler::getDebtReport($cottage);
        if(!empty($singleDebt)){

            foreach ($singleDebt as $item) {
                if(!empty($item->partialPayed)){
                    echo "Р " .  $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount - $item->partialPayed) . '<br/>';
                }
                else{
                    echo "Р " .  $cottage->cottageNumber . " " . CashHandler::toRubles($item->amount) . '<br/>';
                }
            }
        }
    }

}