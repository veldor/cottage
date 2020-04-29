<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 12.12.2018
 * Time: 12:44
 */

namespace app\models;


use app\models\selections\TargetDebt;
use app\models\selections\TargetInfo;
use app\validators\CashValidator;
use DOMElement;
use InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;

class TargetHandler extends Model
{
    public $year;
    public float $fixed;
    public float $float;
    public string $description;

    public const SCENARIO_NEW_TARIFF = 'new_tariff';

    public static function getPayUpTime($year): int
    {
        return Table_tariffs_target::findOne(['year' => $year])->payUpTime;
    }

    public static function changePayTime(int $id, $timestamp): void
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

    /**
     * Получение информации о целевых платежах
     * @param Table_cottages $cottage <p>Участок</p>
     * @return TargetInfo[] <p>Массив данных о целевых взносах</p>
     */
    public static function getTargetInfo(Table_cottages $cottage): array
    {
        /** @var float[] $info */
        $info = [];
        $result = [];
        // получу информацию о долгах на данный момент
        $duties = self::getDebt($cottage);
        if(!empty($duties)){
            foreach ($duties as $duty) {
                $info[$duty->year] = CashHandler::toRubles($duty->amount - $duty->partialPayed);
            }
        }
        // теперь найду все прошедшие оплаты и прибавлю их к выборке
        $pays = Table_payed_target::findAll(['cottageId' => $cottage->cottageNumber]);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                if(!empty($info[$pay->year])){
                    $info[$pay->year] = CashHandler::toRubles($info[$pay->year] + $pay->summ);
                }
                else{
                    $info[$pay->year] = $pay->summ;
                }
            }
        }
        if(!empty($info)){
            foreach ($info as $key => $item) {
                $result [] = new TargetInfo(['year' => $key, 'amount' => $item]);
            }
        }
        return $result;
    }
    /**
     * Получение платежей за данный период
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param string $period
     * @return Table_additional_payed_target[]|Table_payed_target[]
     */
    public static function getPaysForPeriod($cottage, string $period): array
    {
        if(Cottage::isMain($cottage)){
            return Table_payed_target::findAll(['cottageId' => $cottage->cottageNumber, 'year' => $period]);
        }
        return Table_additional_payed_target::findAll(['cottageId' => $cottage->masterId, 'year' => $period]);
    }

    /**
     * получу стоимость периода
     * @param $cottage
     * @param string $year
     * @return float
     */
    public static function getAmount($cottage, string $year): float
    {
        // получу текущую задолженность по периоду
        $data = self::getYearDuty($cottage, $year);
        $pays = self::getPaysForPeriod($cottage, $year);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                $data = CashHandler::toRubles(CashHandler::toRubles($data) + CashHandler::toRubles($pay->summ));
            }
        }
        return 0;
    }

    /**
     * Возвращает текущую задолженность за данный год
     * @param $cottage
     * @param string $year
     * @return float|int
     */
    private static function getYearDuty($cottage, string $year)
    {
        $duties = self::getDebt($cottage);
        foreach ($duties as $duty) {
            if($duty->year === $year){
                return CashHandler::toRubles(CashHandler::toRubles($duty->amount) - CashHandler::toRubles($duty->partialPayed));
            }
        }
        return 0;
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
     * @param $cottageInfo Table_cottages|Table_additional_cottages
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
     * @return TargetDebt[]
     */
    public static function getDebt($cottageInfo): array
    {
        $isMain = Cottage::isMain($cottageInfo);
        if (!$isMain && !$cottageInfo->isTarget) {
            return [];
        }
        $targetDom = new DOMHandler($cottageInfo->targetPaysDuty);
        $answer = [];
        $years = $targetDom->query('/targets/target');
        if ($years !== null) {
            /**
             * @var $year DOMElement
             */
            foreach ($years as $year) {
                $answerItem = new TargetDebt();
                $answerItem->description = $year->getAttribute('description');
                $answerItem->year = $year->getAttribute('year');
                $answerItem->tariffFixed = CashHandler::toRubles($year->getAttribute('fixed'));
                $answerItem->tariffFloat = CashHandler::toRubles($year->getAttribute('float'));
                $answerItem->amount = Calculator::countFixedFloat($answerItem->tariffFixed, $answerItem->tariffFloat, $cottageInfo->cottageSquare);
                $answerItem->partialPayed = CashHandler::toRubles($year->getAttribute('payed'));
                $answer[$answerItem->year] = $answerItem;
            }
        }
        return $answer;
    }

    public static function createPayment($cottageInfo, $target, $additional = false): array
    {
        $answer = '';
        $summ = 0;
        $debt = self::getDebt($cottageInfo);
        foreach ($target as $key => $value) {
            if (!empty($value)) {
                $toPay = CashHandler::toRubles($value['value']);
                $s = Calculator::countFixedFloatPlus($debt[$key]->tariffFixed, $debt[$key]->tariffFloat, $cottageInfo->cottageSquare);
                $leftPay = CashHandler::rublesMath($s['total'] - $debt[$key]->partialPayed);
                    $summ += $toPay;
                    $answer .= "<pay year='$key' summ='{$toPay}' total-summ='{$s['total']}' float-cost='{$s['float']}' fixed='{$debt[$key]->tariffFixed}' float='{$debt[$key]->tariffFloat}' square='{$cottageInfo->cottageSquare}' payed-before='{$debt[$key]->partialPayed}' left-pay='$leftPay'/>";
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

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payments
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, $transaction, $additional = false): void
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
            self::insertPayment($cottageInfo, $billInfo, $payment, $transaction, $additional);
            //self::insertPayment($payment, $cottageInfo, $billInfo, $additional);
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $date
     * @param $summ
     * @param $transaction Table_transactions|Table_transactions_double
     */

    public static function insertSinglePayment($cottageInfo, $bill, $date, $summ, $transaction)
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
        $cottageInfo->targetDebt = CashHandler::toRubles(CashHandler::toRubles($cottageInfo->targetDebt) - CashHandler::toRubles($summ));
        $cottageInfo->targetPaysDuty = $dom->save();
        // зарегистрирую платёж
        if ($main) {
            $write = new Table_payed_target();
            $write->cottageId = $cottageInfo->cottageNumber;
        } else {
            $write = new Table_additional_payed_target();
            $write->cottageId = $cottageInfo->masterId;
        }
        $write->billId = $bill->id;
        $write->year = $date;
        $write->summ = $summ;
        $write->paymentDate = $transaction->bankDate;
        $write->transactionId = $transaction->id;
        $write->save();
        self::recalculateTarget($date);
    }

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payment
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function insertPayment($cottageInfo, $billInfo, $payment, $transaction, $additional = false)
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
            $write->transactionId = $transaction->id;
            $write->summ = $summ;
            $write->paymentDate = $transaction->bankDate;
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

    /**
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $paymentInfo []
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function handlePartialPayment($bill, $paymentInfo, $cottageInfo, $transaction)
    {
        foreach($paymentInfo as $key=>$value){
            if($value > 0){
                self::insertSinglePayment($cottageInfo, $bill, $key, $value, $transaction);
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