<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 12.12.2018
 * Time: 12:44
 */

namespace app\models;


use app\validators\CashValidator;
use DOMElement;
use InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;
use yii\db\ActiveRecord;

class TargetHandler extends Model
{
    public $year;
    public $fixed;
    public $float;
    public $description;

    const SCENARIO_NEW_TARIFF = 'new_tariff';

    public static function getPayUpTime($year)
    {
        $result = Table_tariffs_target::findOne(['year' => $year]);
        return $result->payUpTime;
    }

    public static function changePayTime(int $id, $timestamp)
    {
        // найду все платежи данного счёта
        $pays = Table_payed_target::find()->where(['billId' => $id])->all();
        if(!empty($pays)){
            foreach ($pays as $pay) {
                /** @var Table_payed_power $pay */
                $pay->paymentDate = $timestamp;
                $pay->save();
            }
        }
    }


    public function scenarios(): array
    {
        return [
            self::SCENARIO_NEW_TARIFF => ['year', 'fixed', 'float', 'description'],
        ];
    }

    public function rules(): array
    {
        return [
            [['year', 'fixed', 'float', 'description'], 'required', 'on' => self::SCENARIO_NEW_TARIFF],
            ['description', 'string', 'max' => 500],
            ['year', 'integer', 'min' => 1980, 'max' => 3000],
            [['fixed', 'float'], CashValidator::class],
        ];
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        // если уже существует целевой тариф на этот год- ошибка
        try {
            self::getRowTariff(TimeHandler::getThisYear());
            throw new \yii\base\InvalidArgumentException('Тариф на этот год уже заполнен!');
        } catch (InvalidValueException $e) {
            $this->year = TimeHandler::getThisYear();
        }
    }

    public static function getCurrentRates()
    {
        $tariffs = Table_tariffs_target::find()->all();
        if (!empty($tariffs)) {
            $data = [];
            foreach ($tariffs as $tariff) {
                $data[$tariff->year] = ['fixed' => $tariff->fixed_part, 'float' => $tariff->float_part, 'description' => $tariff->description];
            }
            return $data;
        }
        return null;
    }

    /**
     * @param $cottageInfo Table_cottages
     * @return array|bool|null
     */
    public static function getRegistrationRates($cottageInfo = null)
    {
        $data = self::getCurrentRates();
        if ($cottageInfo !== null) {
            $duties = self::getDutyInfo($cottageInfo);
            foreach ($data as $key => $value) {
                if (!empty($duties[$key])) {
                    $data[$key]['payed'] = $duties[$key]['payed'];
                    $data[$key]['square'] = $duties[$key]['square'];
                    $data[$key]['totalSumm'] = Calculator::countFixedFloatPlus($value['fixed'], $value['float'], $duties[$key]['square'])['total'] - $duties[$key]['payed'];
                } else {
                    $data[$key]['square'] = $cottageInfo->cottageSquare;
                    $data[$key]['payed'] = 0;
                    $data[$key]['totalSumm'] = Calculator::countFixedFloatPlus($value['fixed'], $value['float'], $cottageInfo->cottageSquare)['total'];
                }
            }
            return $data;
        }
        foreach ($data as $key => $item) {
            $data[$key]['square'] = 0;
            $data[$key]['payed'] = 0;
            $data[$key]['totalSumm'] = $item['fixed'];
        }
        return $data;
    }

    /**
     * @param $cottageInfo ActiveRecord
     * @return array
     */
    public static function getDutyInfo($cottageInfo): array
    {
        /**
         * @var $year DOMElement
         */
        $duties = [];
        // получу данные о текущем состоянии оплаты целевых платежей
        $targetDom = new DOMHandler($cottageInfo->targetPaysDuty);
        $years = $targetDom->query('/tariffs/target/year');
        foreach ($years as $year) {
            $targetYear = $year->getAttribute('year');
            $fixedTariff = CashHandler::toRubles($year->getAttribute('fixed'));
            $floatTariff = CashHandler::toRubles($year->getAttribute('float'));
            $payed = CashHandler::toRubles($year->getAttribute('payed'));
            $square = $year->getAttribute('square');
            $summ = CashHandler::toRubles($year->getAttribute('summ'));
            $description = $year->getAttribute('description');
            $duties[$targetYear] = ['fixedTariff' => $fixedTariff, 'floatTariff' => $floatTariff, 'payed' => $payed, 'square' => $square, 'summ' => $summ, 'description' => $description];
        }
        return $duties;
    }

    /**
     * @param $targetArray array
     * @param $cottageInfo Table_cottages
     * @return array
     */
    public static function calculateDuty($targetArray, $cottageInfo): array
    {
        $totalDuty = 0;
        $dutyDetails = '<targets>';
        // проконтролирую соответствие переданной информации тарифным ставкам
        $rates = self::getCurrentRates();
        if (count($targetArray) !== count($rates)) {
            throw new InvalidArgumentException('Заполнены не все данные!');
        }
        foreach ($targetArray as $key => $value) {
            if ($value['payed-of'] !== 'full') {
                $payed = 0;
                $fixedTariff = $rates[$key]['fixed'];
                $totalSumm = $fixedTariff;
                $floatTariff = $rates[$key]['float'];
                if ($floatTariff > 0) {
                    $totalSumm = Calculator::countFixedFloat($fixedTariff, $floatTariff, $cottageInfo->cottageSquare);
                }
                if ($value['payed-of'] === 'partial') {
                    $payed = CashHandler::toRubles($value['payed-summ']);
                }
                $totalDuty += CashHandler::rublesMath($totalSumm - $payed);
                $dutyDetails .= "<target payed='{$payed}' year='{$key}' float='{$floatTariff}' fixed='{$fixedTariff}' square='{$cottageInfo->cottageSquare}' summ='{$totalSumm}' description='{$rates[$key]['description']}'/>";
            }
        }
        $dutyDetails .= '</targets>';
        return ['totalDuty' => $totalDuty, 'dutyDetails' => $dutyDetails];
    }

    /**
     * @param $cottageInfo Table_additional_cottages|Table_cottages
     * @param $additional boolean
     * @return array
     */
    public static function getDebt($cottageInfo, $additional = false): array
    {
        if ($additional && !$cottageInfo->isTarget) {
            return [];
        }
        $targetDom = new DOMHandler($cottageInfo->targetPaysDuty);
        $duty = [];
        $years = $targetDom->query('/targets/target');
        if ($years !== null) {
            /**
             * @var $year DOMElement
             */
            foreach ($years as $year) {
                $date = $year->getAttribute('year');
                $description = $year->getAttribute('description');
                $fixed = CashHandler::toRubles($year->getAttribute('fixed'));
                $float = CashHandler::toRubles($year->getAttribute('float'));
                $payed = CashHandler::toRubles($year->getAttribute('payed'));
                $summ = Calculator::countFixedFloatPlus($fixed, $float, $cottageInfo->cottageSquare);
                $realSumm = CashHandler::rublesMath($summ['total'] - $payed);
                $duty[$date] = ['fixed' => $fixed, 'float' => $float, 'payed' => $payed, 'summ' => $summ, 'realSumm' => $realSumm, 'description' => $description];
            }
        }
        return $duty;
    }

    public static function createPayment($cottageInfo, $target, $additional = false): array
    {
        $answer = '';
        $summ = 0;
        $debt = self::getDebt($cottageInfo, $additional);
        foreach ($target as $key => $value) {
            if (!empty($value)) {
                $pay = CashHandler::toRubles($value);
                if (!empty($debt[$key])) {
                    if ($pay > $debt[$key]['realSumm']) {
                        throw new InvalidArgumentException('Сумма платежа превышает сумму задолженности');
                    }
                    $s = Calculator::countFixedFloatPlus($debt[$key]['fixed'], $debt[$key]['float'], $cottageInfo->cottageSquare);
                    $leftPay = CashHandler::rublesMath($s['total'] - $debt[$key]['payed']);
                    $summ += $pay;
                    $answer .= "<pay year='$key' summ='{$pay}' total-summ='{$s['total']}' float-cost='{$s['float']}' fixed='{$debt[$key]['fixed']}' float='{$debt[$key]['float']}' square='{$cottageInfo->cottageSquare}' payed-before='{$debt[$key]['payed']}' left-pay='$leftPay'/>";

                } else {
                    throw new InvalidArgumentException('Год не найден в списке задолженностей');
                }
            }
        }

        if ($summ > 0) {
            $summ = CashHandler::rublesRound($summ);
            if ($additional) {
                $answer = /** @lang xml */
                    "<additional_target cost='{$summ}'>" . $answer . '</additional_target>';
            } else {
                $answer = /** @lang xml */
                    "<target cost='{$summ}'>" . $answer . '</target>';
            }
        } else {
            $answer = '';
        }
        return ['text' => $answer, 'summ' => $summ];
    }

    public static function registerPayment($cottageInfo, $billInfo, $payments, $additional = false)
    {
        $dom = DOMHandler::getDom($cottageInfo->targetPaysDuty);
        $xpath = DOMHandler::getXpath($dom);
        // зарегистрирую платежи
        foreach ($payments['values'] as $payment) {
            $summ = CashHandler::toRubles($payment['summ']);
            /** @var DOMElement $pay */
            $pay = $xpath->query("/targets/target[@year='{$payment['year']}']")->item(0);
            $attrs = DOMHandler::getElemAttributes($pay);
            $payed = CashHandler::toRubles($attrs['payed']);
            $fullSumm = CashHandler::toRubles($attrs['summ']);
            if ($fullSumm === $summ + $payed) {
                // год оплачен полностью, удаляю его из списка долгов
                $pay->parentNode->removeChild($pay);
            } else {
                $pay->setAttribute('payed', $summ + $payed);
            }
            $cottageInfo->targetDebt -= $summ;
            $cottageInfo->targetPaysDuty = DOMHandler::saveXML($dom);
            self::insertPayment($cottageInfo, $billInfo, $payment, $additional);
            //self::insertPayment($payment, $cottageInfo, $billInfo, $additional);
        }
    }

    public static function insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime)
    {
        $main = Cottage::isMain($cottageInfo);
        // получу информацию о задолженностях
        $dom = new DOMHandler($cottageInfo->targetPaysDuty);
        // найду информацию о платеже
        /** @var DOMElement $pay */
        $pay = $dom->query("//targets/target[@year='{$date}']")->item(0);
        $attrs = DOMHandler::getElemAttributes($pay);
        $payed = CashHandler::toRubles($attrs['payed']);
        $fullSumm = CashHandler::toRubles($attrs['summ']);
        if ($fullSumm === $summ + $payed) {
            // год оплачен полностью, удаляю его из списка долгов
            $pay->parentNode->removeChild($pay);
        } else {
            $pay->setAttribute('payed', $summ + $payed);
        }
        $cottageInfo->targetDebt -= $summ;
        $cottageInfo->targetPaysDuty = $dom->save();
        // зарегистрирую платёж
        if ($main) {
            $write = new Table_payed_target();
            $write->cottageId = $cottageInfo->cottageNumber;
        } else {
            $write = new Table_additional_payed_target();
            $write->cottageId = $cottageInfo->masterId;
        }
        $write->billId = $billId;
        $write->year = $date;
        $write->summ = $summ;
        $write->paymentDate = $paymentTime;
        $write->save();
        self::recalculateTarget($date);
    }

    public static function insertPayment($cottageInfo, $billInfo, $payment, $additional = false)
    {
        $summ = CashHandler::toRubles($payment['summ']);
        if ($summ > 0) {
            if ($additional) {
                $write = new Table_additional_payed_target();
                $write->cottageId = $cottageInfo->masterId;
            } else {
                $write = new Table_payed_target();
                $write->cottageId = $cottageInfo->cottageNumber;
            }
            $write->billId = $billInfo->id;
            $write->year = $payment['year'];
            $write->summ = $summ;
            $write->paymentDate = $billInfo->paymentTime;
            $write->save();
            self::recalculateTarget($payment['year']);
        }
    }

    public static function recalculateTarget($period)
    {
        $year = TimeHandler::isYear($period);
        $tariff = self::getRowTariff($year);

        $payedNow = 0; // Оплачено внутри программы
        // составлю массив данных о фактической оплате (через программу)
        $insidePayed = [];

        // получу оплаченные счета за этот год
        $payed = Table_payed_target::find()->where(['year' => $year])->all();
        $additionalPayed = Table_additional_payed_target::find()->where(['year' => $year])->all();
        if (!empty($payed)) {
            foreach ($payed as $item) {
                $payedNow += $item->summ;
                if (empty($insidePayed[$item->cottageId])) {
                    $insidePayed[$item->cottageId] = $item->summ;
                } else {
                    $insidePayed[$item->cottageId] += $item->summ;
                }
            }
        }
        if (!empty($additionalPayed)) {
            foreach ($additionalPayed as $item) {
                $payedNow += $item->summ;
            }
        }
        $cottages = Cottage::getRegistred();
        $additionalCottages = AdditionalCottage::getRegistred();

        $cottagesCount = count($cottages);
        $additionalCottagesCount = count($additionalCottages);

        $neededSumm = 0;
        $fullPayed = 0;
        $additionalFullPayed = 0;
        $partialPayed = 0;
        $additionalPartialPayed = 0;
        $payedOutside = 0;

        foreach ($cottages as $cottage) {
            if (!$cottage->individualTariff) {
                $summ = Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $cottage->cottageSquare);
                $neededSumm += $summ;
            } else {
                $rates = PersonalTariff::getTargetRate($cottage, $year);
                $summ = Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                $neededSumm += $summ;
            }
            $dom = DOMHandler::getDom($cottage->targetPaysDuty);
            $xpath = DOMHandler::getXpath($dom);
            /** @var DOMElement $yearDuty */
            $yearDuty = $xpath->query("/targets/target[@year='$year']")->item(0);
            if (empty($yearDuty)) {
                $fullPayed++;
            } else {
                $partial = $yearDuty->getAttribute('payed');
                if (!empty($partial) && CashHandler::rublesRound($partial) > 0) {
                    $partialPayed++;
                }
            }
            if (empty($insidePayed[$cottage->cottageNumber])) {
                if (empty($yearDuty)) {
                    // если год считается оплаченным, но при этом не оплачен в программе - считаю его оплаченным вне программы по стандартному тарифу
                    $payedOutside += $summ;
                } elseif (!empty($partial) && $partial > 0) {// если год частично оплачен но не в программе
                    $payedOutside += CashHandler::rublesRound($partial);// Добавляю к оплаченному вне программы сумму оплаты
                }
            } else if (empty($yearDuty)) {
                // если год считается оплаченным - получатеся, что остаток выплачен вне программы
                $payedOutside += $summ - $insidePayed[$cottage->cottageNumber];
            }
        }
        foreach ($additionalCottages as $cottage) {
            if ($cottage->isTarget) {
                if (!$cottage->individualTariff) {
                    $neededSumm += Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $cottage->cottageSquare);
                } else {
                    $rates = PersonalTariff::getTargetRate($cottage, $year);
                    $neededSumm += Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                }
                $dom = new DOMHandler($cottage->targetPaysDuty);
                $yearDuty = $dom->query("/targets/target[@year='$year']")->item(0);
                if (empty($yearDuty)) {
                    $additionalFullPayed++;
                } else {
                    $partial = $yearDuty->getAttribute('payed');
                    if (!empty($partial) && CashHandler::rublesRound($partial) > 0) {
                        $additionalPartialPayed++;
                    }
                }
            }
        }
        $untrustedPayment = CashHandler::rublesMath($payedNow + $payedOutside);
        $tariff->fullSumm = $neededSumm;
        $tariff->payedSumm = $payedNow;
        $tariff->paymentInfo = /** @lang xml */
            "<info><cottages_count>$cottagesCount</cottages_count><additional_cottages_count>$additionalCottagesCount</additional_cottages_count><full_payed>$fullPayed</full_payed><additional_full_payed>$additionalFullPayed</additional_full_payed><partial_payed>$partialPayed</partial_payed><additional_partial_payed>$additionalPartialPayed</additional_partial_payed><payed_outside>$payedOutside</payed_outside><payed_untrusted>$untrustedPayment</payed_untrusted></info>";
        /** @var Table_tariffs_power $tariff */
        $tariff->save();
    }

    /**
     * @param $period
     * @return Table_tariffs_target
     */
    public static function getRowTariff($period): Table_tariffs_target
    {
        $year = TimeHandler::isYear($period);
        $data = Table_tariffs_target::findOne(['year' => $year]);
        if (!empty($data)) {
            return $data;
        }
        throw new InvalidValueException('Тарифа на данный квартал не существует!');
    }

    public function createTariff(): bool
    {
        if ($this->validate()) {
            $newTariff = new Table_tariffs_target();
            $newTariff->year = $this->year;
            $newTariff->fixed_part = $this->fixed;
            $newTariff->float_part = $this->float;
            $newTariff->description = $this->description;
            $newTariff->fullSumm = 0;
            $newTariff->payedSumm = 0;
            $newTariff->paymentInfo = '';
            $newTariff->save();
            // теперь добавлю долг всем участкам без индивидуальных тарифов и всем дополнительным участкам без индивидуальных тарифов
            $cottages = Cottage::getRegistred();
            foreach ($cottages as $cottage) {
                if (!$cottage->individualTariff) {
                    $dom = DOMHandler::getDom($cottage->targetPaysDuty);
                    $target = $dom->createElement('target');
                    $summ = Calculator::countFixedFloat($this->fixed, $this->float, $cottage->cottageSquare);
                    $readyTarget = DOMHandler::setElemAttributes($target, ['year' => $this->year, 'payed' => 0, 'float' => $this->float, 'fixed' => $this->fixed, 'square' => $cottage->cottageSquare, 'summ' => $summ, 'description' => $this->description]);
                    $dom->documentElement->appendChild($readyTarget);
                    $cottage->targetPaysDuty = DOMHandler::saveXML($dom);
                    $cottage->targetDebt += $summ;
                    /** @var Table_cottages $cottage */
                    $cottage->save();

                }
            }
            $additionalCottages = AdditionalCottage::getRegistred();
            foreach ($additionalCottages as $cottage) {
                if ($cottage->isTarget && !$cottage->individualTariff) {
                    $dom = DOMHandler::getDom($cottage->targetPaysDuty);
                    $target = $dom->createElement('target');
                    $summ = Calculator::countFixedFloat($this->fixed, $this->float, $cottage->cottageSquare);
                    $readyTarget = DOMHandler::setElemAttributes($target, ['year' => $this->year, 'payed' => 0, 'float' => $this->float, 'fixed' => $this->fixed, 'square' => $cottage->cottageSquare, 'summ' => $summ, 'description' => $this->description]);
                    $dom->documentElement->appendChild($readyTarget);
                    $cottage->targetPaysDuty = DOMHandler::saveXML($dom);
                    $cottage->targetDebt += $summ;
                    /** @var Table_cottages $cottage */
                    $cottage->save();
                }
            }
            self::recalculateTarget($this->year);
            return true;
        }
        throw new \yii\base\InvalidArgumentException('Ошибка в введённых данных!');
    }

    public static function handlePartialPayment(DOMHandler $billDom, float $paymentSumm, $cottageInfo, $billId, int $paymentTime)
    {
        $main = Cottage::isMain($cottageInfo);
        /** @var DOMElement $PayContainer */

        if($main){
            $PayContainer = $billDom->query('//target')->item(0);
        }
        else{
            $PayContainer = $billDom->query('//additional_target')->item(0);
        }

        // проверка на предыдущую неполную оплату категории
        $payedBefore = CashHandler::toRubles(0 . $PayContainer->getAttribute('payed'));
        // записываю сумму прошлой и текущей оплаты в xml
        $PayContainer->setAttribute('payed', $paymentSumm + $payedBefore);
        // получу данные о полном счёте за членские взносы
        if($main){
            $pays = $billDom->query('//target/pay');
        }
        else{
            $pays = $billDom->query('//additional_target/pay');
        }
        /** @var DOMElement $pay */
        foreach ($pays as $pay) {
            // переменная для хранения суммы, предоплаченной за платёж в прошлый раз
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($pay, 'summ');
            $date = $pay->getAttribute('year');
            // отсекаю платежи, полностью оплаченные в прошлый раз
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                // это сумма, которая была предоплачена по кварталу в прошлый раз
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if ($summ - $prepayed <= $paymentSumm) {
                // денег хватает на полную оплату платежа. Плачу за него
                // сумма платежа учитывается с вычетом ранее оплаченного
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
                // корректирую сумму текущего платежа с учётом предыдущего
                $paymentSumm -= $summ - $prepayed;
            } elseif ($paymentSumm > 0) {
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                self::insertSinglePayment($cottageInfo, $billId, $date, $paymentSumm, $paymentTime);
                break;
            }
        }
    }

    /**
     * @param $billDom DOMHandler
     * @param $cottageInfo
     * @param $billId
     * @param $paymentTime
     */
    public static function finishPartialPayment($billDom, $cottageInfo, $billId, $paymentTime)
    {
        // добавлю оплаченную сумму в xml
        /** @var DOMElement $targetContainer */
        $main = Cottage::isMain($cottageInfo);
        if($main){
            $targetContainer = $billDom->query('//target')->item(0);
        }
        else{
            $targetContainer = $billDom->query('//additional_target')->item(0);
        }
        // проверю, не оплачивалась ли часть платежа ранее
        $payedBefore = CashHandler::toRubles(0 . $targetContainer->getAttribute('payed'));
        // получу данные о полном счёте за электричество
        if($main){
            $pays = $billDom->query('//target/pay');
        }
        else{
            $pays = $billDom->query('//additional_target/pay');
        }
        /** @var DOMElement $pay */
        foreach ($pays as $pay) {
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($pay, 'summ');
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            $date = $pay->getAttribute('year');
            self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
        }
    }
}