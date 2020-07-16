<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 22:57
 */

namespace app\models;

use app\models\database\Accruals_membership;
use app\models\database\CottageSquareChanges;
use app\models\interfaces\CottageInterface;
use app\models\selections\FixedFloatTariff;
use app\models\selections\MembershipDebt;
use DOMElement;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;

/**
 * Class MembershipHandler
 * @package app\models
 */
class MembershipHandler extends Model
{

    public $membership;


    const SCENARIO_NEW_RECORD = 'new_record';

    public static function changePayTime(int $id, $timestamp)
    {
        // найду все платежи данного счёта
        $pays = Table_payed_membership::find()->where(['billId' => $id])->all();
        if (!empty($pays)) {
            foreach ($pays as $pay) {
                /** @var Table_payed_membership $pay */
                $pay->paymentDate = $timestamp;
                $pay->save();
            }
        }
    }

    /**
     * Получаю данные о певом учитываемом квартале участка
     * @param Table_cottages $cottage
     * @return string <p>Квартал в формате 2020-1</p>
     */
    public static function getFirstFilledQuarter(CottageInterface $cottage): string
    {
        // попробую найти оплаченные кварталы. Если они есть, то первый квартал будет первым из них,
        // иначе первый квартал- следующий, после последнего оплаченного по данным учаска
        if ($cottage->isMain())
            $firstPayed = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->orderBy('quarter')->limit(1)->all();
        else
            $firstPayed = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->limit(1)->all();
        if (empty($firstPayed)) {
            return TimeHandler::getNextQuarter($cottage->membershipPayFor);
        }
        return $firstPayed[0]->quarter;
    }

    /**
     * Верну раскладку по тарифу
     * @param $cottage
     * @param $quarter
     * @return FixedFloatTariff
     */
    public static function getCottageTariff($cottage, $quarter): FixedFloatTariff
    {
        $fixed = 0;
        $float = 0;
        if ($cottage->individualTariff) {
            $tariff = PersonalTariff::getMembershipRate($cottage, $quarter);
            if (!empty($tariff)) {
                $fixed = $tariff['fixed'];
                $float = $tariff['float'];
            } else {
                $tariff = Table_tariffs_membership::findOne(['quarter' => $quarter]);
                if ($tariff !== null) {
                    $fixed = $tariff->fixed_part;
                    $float = $tariff->changed_part;
                }
            }
        } else {
            $tariff = Table_tariffs_membership::findOne(['quarter' => $quarter]);
            if ($tariff !== null) {
                $fixed = $tariff->fixed_part;
                $float = $tariff->changed_part;
            }
        }
        return new FixedFloatTariff(['fixed' => $fixed, 'float' => $float]);
    }

    /**
     * Получение платежей за данный период
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param string $period
     * @return Table_additional_payed_membership[]|Table_payed_membership[]
     */
    public static function getPaysForPeriod($cottage, string $period): array
    {
        if (Cottage::isMain($cottage)) {
            return Table_payed_membership::findAll(['cottageId' => $cottage->cottageNumber, 'quarter' => $period]);
        }
        return Table_additional_payed_membership::findAll(['cottageId' => $cottage->masterId, 'quarter' => $period]);
    }

    /**
     * Получаю стоимость периода
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param string $quarter
     * @return float
     */
    public static function getAmount($cottage, string $quarter): float
    {
        $data = self::getCottageTariff($cottage, $quarter);
        if ($data !== null) {
            return Calculator::countFixedFloat(
                $data->fixed,
                $data->float,
                $cottage->cottageSquare
            );
        }
        return 0;
    }

    public static function getFirstPayedQuarter(interfaces\CottageInterface $cottage)
    {
        if ($cottage->isMain()) {
            return Table_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->one();
        }
        return Table_additional_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->one();
    }

    public static function getPaysBefore(string $quarter, interfaces\CottageInterface $cottage, int $periodEnd)
    {
        if ($cottage->isMain()) {
            return Table_payed_membership::find()->where(['quarter' => $quarter, 'cottageId' => $cottage->getBaseCottageNumber()])->andWhere(['<=', 'paymentDate', $periodEnd])->all();
        }
        return Table_additional_payed_membership::find()->where(['quarter' => $quarter, 'cottageId' => $cottage->getBaseCottageNumber()])->andWhere(['<=', 'paymentDate', $periodEnd])->all();
    }

    /**
     * @param interfaces\CottageInterface $cottage
     * @param string $month
     * @return float
     * @throws Exception
     */
    public static function getAccrued(interfaces\CottageInterface $cottage, string $month): float
    {
        // так, нужно получить общую стоимость членских взносов за месяц
        $tariff = self::getCottageTariff($cottage, TimeHandler::getQuarterFromTimestamp(TimeHandler::getMonthTimestamp($month)));
        $square = CottageSquareChanges::getQuarterSquare($cottage, TimeHandler::getQuarterFromTimestamp(TimeHandler::getMonthTimestamp($month)));
        return Calculator::countFixedFloat($tariff->fixed, $tariff->float, $square);
    }

    public static function getCottageAccruals(CottageInterface $cottage): array
    {
        return Accruals_membership::findAll(['cottage_number' => $cottage->getCottageNumber()]);
    }


    public function scenarios(): array
    {
        return [
            self::SCENARIO_NEW_RECORD => ['membership'],
        ];
    }

    public static function getCottageStatus($cottageInfo)
    {
        $isMain = Cottage::isMain($cottageInfo);
        // верну общую сумму неоплаченных членских взносов
        $summ = 0;
        // Сделаю выборку тарифов
        $start = TimeHandler::getQuarterTimestamp($cottageInfo->membershipPayFor);
        $now = TimeHandler::getQuarterTimestamp(TimeHandler::getCurrentQuarter());
        if ($start === $now || $start > $now) {
            return 0;
        }
        $tariffs = Table_tariffs_membership::find()
            ->where(['and', "search_timestamp>$start", "search_timestamp<=$now"])
            ->all();
        if (!empty($tariffs)) {
            foreach ($tariffs as $item) {
                $summ += $item->fixed_part;
                $summ += $cottageInfo->cottageSquare * ($item->changed_part / 100);
                // вычту сумму частично оплаченного квартала, если она есть
                if ($isMain) {
                    $payed = Table_payed_membership::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'quarter' => $item->quarter])->all();
                } else {
                    $payed = Table_additional_payed_membership::find()->where(['cottageId' => $cottageInfo->masterId, 'quarter' => $item->quarter])->all();
                }
                if (!empty($payed)) {
                    foreach ($payed as $payedItem) {
                        $summ -= $payedItem->summ;
                    }
                }
            }
            return CashHandler::rublesRound($summ);
        }
        return false;
    }

    /**
     * @param $cottage Table_additional_cottages|Table_cottages
     * @return MembershipDebt[]
     */
    public static function getDebt($cottage): array
    {
        $result = [];
        // проверка, является ли участок основным
        $isMain = Cottage::isMain($cottage);
        if (!$isMain && !$cottage->isMembership) {
            // если дополнительный участок не оплачивает членские взносы
            return $result;
        }
        // получу список кварталов, начиная от первого неоплаченного до текущего
        $list = TimeHandler::getQuarterList($cottage->membershipPayFor);
        if (empty($list)) {
            return $result;
        }
        foreach ($list as $key => $value) {
            /*            // получу раскладку по тарифу
                        $tariff = self::getCottageTariff($cottage, $key);
                        $cost = Calculator::countFixedFloat(
                            $tariff->fixed,
                            $tariff->float,
                            $cottage->cottageSquare
                        );
                        $payedYet = 0;
                        // если квартал частично оплачен- вычту сумму частичной оплаты из цены
                        if ($isMain) {
                            $pays = Table_payed_membership::findAll(['cottageId' => $cottage->cottageNumber, 'quarter' => $key]);
                        } else {
                            $pays = Table_additional_payed_membership::findAll(['cottageId' => $cottage->masterId, 'quarter' => $key]);
                        }
                        if (!empty($pays)) {
                            foreach ($pays as $pay) {
                                $payedYet = CashHandler::toRubles($payedYet + $pay->summ);
                            }
                        }
                        $existentTariff = Table_tariffs_membership::findOne(['quarter' => $key]);*/
            $accrual = Accruals_membership::findOne(['cottage_number' => $cottage->getCottageNumber(), 'quarter' => $key]);
            if ($accrual !== null) {
                $existentTariff = Table_tariffs_membership::findOne(['quarter' => $key]);
                // получу значение начисления по кварталу

                $payedYet = 0;
                // если квартал частично оплачен- вычту сумму частичной оплаты из цены
                if ($isMain) {
                    $pays = Table_payed_membership::findAll(['cottageId' => $cottage->cottageNumber, 'quarter' => $key]);
                } else {
                    $pays = Table_additional_payed_membership::findAll(['cottageId' => $cottage->masterId, 'quarter' => $key]);
                }
                if (!empty($pays)) {
                    foreach ($pays as $pay) {
                        $payedYet = CashHandler::toRubles($payedYet + $pay->summ);
                    }
                }
                $result[] = new MembershipDebt(['partialPayed' => $payedYet, 'amount' => Calculator::countFixedFloat($accrual->fixed_part, $accrual->square_part, $accrual->counted_square), 'quarter' => $key, 'tariffFixed' => $accrual->fixed_part, 'tariffFloat' => $accrual->square_part, 'tariff' => $existentTariff]);
            }
        }
        return $result;
    }

    /**
     * @param $quartersNumber int|string
     * @param $cottageNumber int|string
     * @param $additional boolean
     * @return array
     */
    public static function getFutureQuarters($quartersNumber, $cottageNumber, $additional): array
    {
        if ($additional) {
            $cottage = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottage = Cottage::getCottageInfo($cottageNumber);
        }
        // получу список тарифов на данный период
        try {
            $tariffs = self::getTariffs(['start' => TimeHandler::getCurrentQuarter(), 'finish' => TimeHandler::getQuarterShift($quartersNumber, TimeHandler::getCurrentQuarter())]);
        } catch (InvalidValueException $e) {
            return ['status' => 2, 'lastQuarterForFilling' => TimeHandler::getQuarterShift($quartersNumber)];
        }
        $totalCost = 0;
        $content = '<table class="table">';
        foreach ($tariffs as $key => $value) {
            $accrual = Accruals_membership::findOne(['cottage_number' => $cottage->getCottageNumber(), 'quarter' => $key]);
            if ($accrual !== null) {
                $existentTariff = Table_tariffs_membership::findOne(['quarter' => $key]);
                // получу значение начисления по кварталу

                $payedYet = 0;
                // если квартал частично оплачен- вычту сумму частичной оплаты из цены
                if ($cottage->isMain()) {
                    $pays = Table_payed_membership::findAll(['cottageId' => $cottage->cottageNumber, 'quarter' => $key]);
                } else {
                    $pays = Table_additional_payed_membership::findAll(['cottageId' => $cottage->masterId, 'quarter' => $key]);
                }
                if (!empty($pays)) {
                    foreach ($pays as $pay) {
                        $payedYet = CashHandler::toRubles($payedYet + $pay->summ);
                    }
                }
                $toPay = Calculator::countFixedFloat($accrual->fixed_part, $accrual->square_part, $accrual->counted_square);
                $summToPay = $toPay - $payedYet;
                if ($summToPay > 0) {
                    $content .= "<tr><td><input type='checkbox' class='pay-activator form-control' data-for='ComplexPayment[membership][{$key}][value]' name='ComplexPayment[membership][{$key}][pay]'/></td><td>{$key}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[membership][{$key}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
                }
            /*$summToPay = Calculator::countFixedFloatPlus($value['fixed'], $value['float'], $cottage->cottageSquare);
            if ($additional) {
                $payed = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->masterId, 'quarter' => $key])->all();
            } else {
                $payed = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber, 'quarter' => $key])->all();
            }
            $payedBefore = 0;
            if (!empty($payed)) {
                foreach ($payed as $item) {
                    $payedBefore += $item->summ;
                }
            }
            $summToPay = $summToPay['total'] - $payedBefore;
            if ($summToPay > 0) {
                $content .= "<tr><td><input type='checkbox' class='pay-activator form-control' data-for='ComplexPayment[membership][{$key}][value]' name='ComplexPayment[membership][{$key}][pay]'/></td><td>{$key}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[membership][{$key}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";*/
            }
        }
        $content .= '</table>';
        return ['status' => 1, 'content' => $content, 'totalSumm' => $totalCost];
    }

    /**
     * @param $period string|array[start, finish]
     * @param bool $skipCheckWholeness
     * @return array
     */
    public static function getTariffs($period, $skipCheckWholeness = false): array
    {
        $query = Table_tariffs_membership::find();
        if (is_string($period)) {
            $quarter = TimeHandler::isQuarter($period);
            $query->where(['quarter' => $quarter['full']]);
            $length = 1;
        } elseif (is_array($period)) {
            $start = TimeHandler::isQuarter($period['start']);
            if (!empty($period['finish'])) {
                $finish = TimeHandler::isQuarter($period['finish']);
                if ($period['start'] === $period['finish']) {
                    return [];
                }
                if ($start['full'] < $finish['full']) {
                    $query->where(['>', 'quarter', $start['full']]);
                    $query->andWhere(['<=', 'quarter', $finish['full']]);
                } else {
                    $query->where(['>', 'quarter', $finish['full']]);
                    $query->andWhere(['<=', 'quarter', $start['full']]);
                }
                $length = abs(TimeHandler::checkQuarterDifference($start['full'], $finish['full']));
            } else {
                $query->where(['>', 'quarter', $start['full']]);
            }
        }
        $result = $query->all();
        $answer = [];
        foreach ($result as $item) {
            $answer[$item->quarter] = ['fixed' => $item->fixed_part, 'float' => $item->changed_part];
        }
        if ($skipCheckWholeness) {
            return $answer;
        }
        if (!empty($result)) {
            // проверю целостность тарифов
            if (empty($length)) {
                $start = key($answer);
                end($answer);
                $finish = key($answer);
                $length = abs(TimeHandler::checkQuarterDifference($start, $finish)) + 1;
            }
            if (count($answer) === $length) {
                return $answer;
            }
        }
        throw new InvalidValueException('Не заполнены тарифы!');
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $membershipPeriods
     * @param bool $additional
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function createPayment($cottageInfo, $membershipPeriods, $additional = false): array
    {
        $answer = '';
        $summ = 0;
        foreach ($membershipPeriods as $key => $value) {
            $toPay = CashHandler::toRubles($value['value']);
            $accrual = Accruals_membership::getItem($cottageInfo, $key);
            if($accrual !== null){
                if ($additional) {
                    $payedBefore = Table_additional_payed_membership::find()->where(['quarter' => $key, 'cottageId' => $cottageInfo->masterId])->all();
                } else {
                    $payedBefore = Table_payed_membership::find()->where(['quarter' => $key, 'cottageId' => $cottageInfo->cottageNumber])->all();
                }
                $payedSumm = 0;
                if (!empty($payedBefore)) {
                    foreach ($payedBefore as $item) {
                        $payedSumm += CashHandler::toRubles($item->summ);
                    }
                }
                $cost = Calculator::countFixedFloatPlus($accrual->fixed_part, $accrual->square_part, $accrual->counted_square);
                $totalSumm = CashHandler::toRubles($cost['total'] - $payedSumm);
                if ($toPay > $totalSumm) {
                    throw new ExceptionWithStatus('Сумма оплаты ' . $toPay . ' за членские взносы за ' . $key . ' больше максимальной- ' . $totalSumm);
                }
                $summ += $totalSumm;
                $answer .= "<quarter date='{$key}' summ='{$totalSumm}' square='{$accrual->counted_square}' float-cost='{$cost['float']}' float='{$accrual->square_part}' fixed='{$accrual->fixed_part}' prepayed='$payedSumm'/>";
            }
        }
        if ($additional) {
            $answer = /** @lang xml */
                "<additional_membership cost='{$summ}'>" . $answer . '</additional_membership>';
        } else {
            $answer = /** @lang xml */
                "<membership cost='{$summ}'>" . $answer . '</membership>';
        }
        return ['text' => $answer, 'summ' => CashHandler::rublesRound($summ)];
    }

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payments
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, $transaction, $additional = false)
    {
        // зарегистрирую платежи
        foreach ($payments['values'] as $payment) {
            self::insertPayment($payment, $cottageInfo, $billInfo, $transaction, $additional);
        }
        $cottageInfo->membershipPayFor = end($payments['values'])['date'];
    }

    /**
     * @param $payment
     * @param $cottageInfo
     * @param $billInfo
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function insertPayment($payment, $cottageInfo, $billInfo, $transaction, $additional = false)
    {
        $partialPayed = self::checkPartialPayedQuarter($cottageInfo);
        $summ = CashHandler::toRubles($payment['summ']);
        if ($summ > 0) {
            if (!empty($partialPayed) && $partialPayed['date'] === $payment['date']) {
                $cottageInfo->partialPayedMembership = null;
            }
            if ($additional) {
                $write = new Table_additional_payed_membership();
                $write->cottageId = $cottageInfo->masterId;
            } else {
                $write = new Table_payed_membership();
                $write->cottageId = $cottageInfo->cottageNumber;
            }
            $write->billId = $billInfo->id;
            $write->transactionId = $transaction->id;
            $write->quarter = $payment['date'];
            $write->summ = $summ;
            $write->paymentDate = $transaction->bankDate;
            $write->save();
            self::recalculateMembership($payment['date']);
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $date
     * @param $summ
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ)
    {
        if (Cottage::isMain($cottageInfo)) {
            $write = new Table_payed_membership();
            $write->cottageId = $cottageInfo->cottageNumber;
        } else {
            $write = new Table_additional_payed_membership();
            $write->cottageId = $cottageInfo->masterId;
        }
        $write->billId = $bill->id;
        $write->quarter = $date;
        $write->summ = $summ;
        $write->paymentDate = $transaction->bankDate;
        $write->transactionId = $transaction->id;
        $write->save();
        self::recalculateMembership($date);
    }

    public static function recalculateMembership($period)
    {
        $quarter = TimeHandler::isQuarter($period);
        try {
            $tariff = self::getRowTariff($quarter['full']); // получу тарифные ставки за данный месяц

            $payedNow = 0; // оплачено внутри программы
            $payedCounter = 0; // счётчик оплаченных участков
            $additionalPayedCounter = 0; // счётчик дополнительных оплаченных участков

            // получу оплаченные счета за этот месяц
            $payed = Table_payed_membership::find()->where(['quarter' => $quarter['full']])->all();
            $additionalPayed = Table_additional_payed_membership::find()->where(['quarter' => $quarter['full']])->all();
            // составлю массив данных о фактической оплате (через программу)
            $insidePayed = [];

            if (!empty($payed)) {
                foreach ($payed as $item) {
                    $payedNow += $item->summ;
                    $payedCounter++;
                    $insidePayed[$item->cottageId] = true;
                }
            }
            if (!empty($additionalPayed)) {
                foreach ($additionalPayed as $item) {
                    $payedNow += $item->summ;
                    $additionalPayedCounter++;
                }
            }
            $cottages = Cottage::getRegistred();
            $additionalCottages = AdditionalCottage::getRegistred();

            $cottagesCount = count($cottages);
            $additionalCottagesCount = count($additionalCottages);

            $fullSquare = 0;
            $neededSumm = 0;
            $payedOutsideCounter = 0;
            $payedOutside = 0;

            foreach ($cottages as $cottage) {
                $summ = 0;
                if ($cottage->individualTariff) {
                    // получу тариф за данный месяц
                    $rates = PersonalTariff::getMembershipRate($cottage, $quarter['full']);
                    if (!empty($rates)) {
                        $summ = Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                        $neededSumm += $summ;
                    }
                } else {
                    $summ = Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                    $neededSumm += $summ;
                }
                // дополнительный блок- буду считать оплату в теории- если у участка нет долгов- считаю, что он заплатил раньше по стандартному тарифу
                $fullSquare += $cottage->cottageSquare;
                if ($cottage->membershipPayFor >= $quarter['full'] && empty($insidePayed[$cottage->cottageNumber])) {
                    // если квартал считается оплаченным, но при этом не оплачен в программе - считаю его оплаченным вне программы по стандартному тарифу
                    $payedOutsideCounter++;
                    $payedOutside += $summ;

                }
            }
            foreach ($additionalCottages as $cottage) {
                if ($cottage->isMembership) {
                    if ($cottage->individualTariff) {
                        if ($cottage->isMembership) {
                            $rates = PersonalTariff::getMembershipRate($cottage, $quarter['full']);
                            if (!empty($rates)) {
                                $neededSumm += Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                            }
                        }
                    } else {
                        $neededSumm += Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                    }
                }
                $fullSquare += $cottage->cottageSquare;
            }
            $untrustedPayed = CashHandler::rublesMath($payedNow + $payedOutside);
            $payUntrusted = $payedCounter + $payedOutsideCounter;
            $tariff->fullSumm = $neededSumm;
            $tariff->payedSumm = $payedNow;
            $tariff->paymentInfo = /** @lang xml */
                "<info><cottages_count>$cottagesCount</cottages_count><additional_cottages_count>$additionalCottagesCount</additional_cottages_count><pay>$payedCounter</pay><pay_outside>$payedOutsideCounter</pay_outside><pay_untrusted>$payUntrusted</pay_untrusted><pay_additional>$additionalPayedCounter</pay_additional><full_square>$fullSquare</full_square><payed_outside>$payedOutside</payed_outside><payed_untrusted>$untrustedPayed</payed_untrusted></info>";
            /** @var Table_tariffs_power $tariff */
            $tariff->save();
        } catch (Exception $e) {
            // предполагается, что тут я буду считать данные, если основной тариф не заполнен
        }
    }

    /**
     * @param bool $period
     * @param bool $skipIntegrityErrors
     * @return Table_tariffs_membership
     */
    public static function getRowTariff($period = false, $skipIntegrityErrors = false): Table_tariffs_membership
    {
        if (!empty($period)) {
            $quarter = TimeHandler::isQuarter($period);
            $data = Table_tariffs_membership::findOne(['quarter' => $quarter['full']]);
            if (!empty($data)) {
                return $data;
            }
            throw new InvalidArgumentException('Тарифа на данный квартал не существует!');
        }
        $data = Table_tariffs_membership::find()->orderBy('search_timestamp DESC')->one();
        if (!empty($data)) {
            return $data;
        }
        throw new InvalidValueException('Тарифы  не обнаружены');
    }

    public static function validateFillTariff($from)
    {
        $thisQuarter = TimeHandler::getCurrentQuarter();
        $start = TimeHandler::isQuarter($from)['full'];
        if ($thisQuarter === $start) {
            return ['status' => 2];
        }
        try {
            $tariffs = self::getTariffs(['start' => $start, 'finish' => $thisQuarter]);
            return ['status' => 2, 'tariffs' => $tariffs];
        } catch (Exception $e) {
            return ['status' => 1];
        }
    }

    public static function getUnfilled($period): array
    {
        $quarters = TimeHandler::getQuarterList($period);
        $tariffs = self::getTariffs(['start' => $period, 'finish' => TimeHandler::getCurrentQuarter()], true);
        foreach ($quarters as $key => $value) {
            if (!empty($tariffs[$key])) {
                unset($quarters[$key]);
            }
        }
        if (!empty($tariffs)) {
            end($tariffs);
            $lastTariffData = ['fixed' => end($tariffs)['fixed'], 'float' => end($tariffs)['float']];
        } else {
            $data = Table_tariffs_membership::find()->orderBy('search_timestamp DESC')->one();
            if (!empty($data)) {
                $lastTariffData = ['fixed' => $data->fixed_part, 'float' => $data->changed_part];
            } else {
                $lastTariffData = ['fixed' => 0, 'float' => 0];
            }
        }
        return ['quarters' => $quarters, 'lastTariffData' => $lastTariffData];
    }

    public function save(): bool
    {
        foreach ($this->membership as $key => $value) {
            self::createTariff($key, $value['fixed'], $value['float']);
        }
        return true;
    }


    private static function createTariff($quarter, $fixed, $float): void
    {
        $quarter = TimeHandler::isQuarter($quarter);
        if (Table_tariffs_membership::find()->where(['quarter' => $quarter['full']])->count()) {
            throw new InvalidArgumentException('Тариф на ' . $quarter['full'] . 'уже заполнен!');
        }
        $tariff = new Table_tariffs_membership();
        $tariff->quarter = $quarter['full'];
        $tariff->fixed_part = $fixed;
        $tariff->changed_part = $float;
        $tariff->search_timestamp = TimeHandler::getQuarterTimestamp($quarter['full']);
        $tariff->fullSumm = 0;
        $tariff->payedSumm = 0;
        $tariff->paymentInfo = '<info><cottages_count>0</cottages_count><additional_cottages_count>0</additional_cottages_count><pay>0</pay><pay_additional>0</pay_additional><full_square>0</full_square></info>';
        $tariff->save();
        // добавлю начисления в таблицу
        Accruals_membership::addQuarter($quarter['full'], $tariff->fixed_part, $tariff->changed_part);
    }

    /**
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $paymentSumm double
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function handlePartialPayment($bill, $paymentSumm, $cottageInfo, $transaction)
    {
        // проверка, оплачивается основной или дополнительный участок
        $main = Cottage::isMain($cottageInfo);
        $payedQuarters = null;
        $partialPayedQuarter = null;
        $dom = new DOMHandler($bill->bill_content);
        // получу данные о полном счёте за членские взносы
        if ($main) {
            $membershipQuarters = $dom->query('//membership/quarter');
        } else {
            $membershipQuarters = $dom->query('//additional_membership/quarter');
        }
        // если ранее производилась оплата по данному счёту- посчитаю сумму оплаты
        if ($main) {
            $payedBefore = Table_payed_membership::find()->where(['billId' => $bill->id])->all();
        } else {
            $payedBefore = Table_additional_payed_membership::find()->where(['billId' => $bill->id])->all();
        }
        $payedSumm = 0;
        if (!empty($payedBefore)) {
            foreach ($payedBefore as $item) {
                $payedSumm += CashHandler::toRubles($item->summ);
            }
        }
        /** @var DOMElement $quarter */
        foreach ($membershipQuarters as $quarter) {
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($quarter, 'summ');
            // отсекаю кварталы, полностью оплаченные в прошлый раз
            if ($summ <= $payedSumm) {
                $payedSumm -= $summ;
                continue;
            }
            $summWithPrepay = $summ - $payedSumm;
            if (CashHandler::toRubles($summWithPrepay) <= CashHandler::toRubles($paymentSumm)) {
                // денег хватает на полую оплату периода. Добавляю его в список полностью оплаченных и вычитаю из общей суммы стоимость месяца
                $payedQuarters[] = ['date' => $quarter->getAttribute('date'), 'summ' => $summWithPrepay];
                $paymentSumm -= $summWithPrepay;
                $payedSumm = 0;
            } elseif ($paymentSumm > 0) {
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                $partialPayedQuarter = ['date' => $quarter->getAttribute('date'), 'summ' => $paymentSumm];
                break;
            }
        }
        // если есть полностью оплаченные кварталы

        if (!empty($payedQuarters)) {
            // зарегистрирую каждый квартал как оплаченный
            foreach ($payedQuarters as $payedQuarter) {
                $date = $payedQuarter['date'];
                $summ = $payedQuarter['summ'];
                self::insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
                $cottageInfo->partialPayedMembership = null;
            }
        }
        if (!empty($partialPayedQuarter)) {
            $date = $partialPayedQuarter['date'];
            $summ = $partialPayedQuarter['summ'];
            // переменная для хранения финального значения суммы оплаты за квартал
            $summForSave = $summ;
            // проверю существование частично оплаченного периода у данного участка
            $savedPartial = self::checkPartialPayedQuarter($cottageInfo);
            if ($savedPartial) {
                $prevPayment = CashHandler::toRubles($savedPartial['summ']);
                // получу полную стоимость данного месяца
                /** @var DOMElement $monthInfo */
                $monthInfo = $dom->query('//quarter[@date="' . $date . '"]')->item(0);
                $fullPaySumm = CashHandler::toRubles($monthInfo->getAttribute('summ'));
                if ($prevPayment + $summ === $fullPaySumm) {
                    // отмечу месяц как полностью оплаченный
                    self::insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ);
                    $cottageInfo->membershipPayFor = $date;
                    $cottageInfo->partialPayedMembership = null;
                    return;
                } else {
                    $summForSave += $prevPayment;
                }
            }
            // отмечу квартал как оплаченный частично
            $cottageInfo->partialPayedMembership = "<partial date='$date' summ='$summForSave'/>";
            // зарегистрирую платёж в таблице оплаты членских взносов
            if ($main) {
                $table = new Table_payed_membership();
                $table->cottageId = $cottageInfo->cottageNumber;
            } else {
                $table = new Table_additional_payed_membership();
                $table->cottageId = $cottageInfo->masterId;
            }
            $table->billId = $bill->id;
            $table->quarter = $date;
            $table->summ = $summ;
            $table->paymentDate = $transaction->bankDate;
            $table->transactionId = $transaction->id;
            $table->save();
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
        $main = Cottage::isMain($cottageInfo);
        $payedMonths = null;
        $partialPayedMonth = null;
        // добавлю оплаченную сумму в xml
        if ($main) {
            $membershipContainer = $billDom->query('//membership')->item(0);
        } else {
            $membershipContainer = $billDom->query('//additional_membership')->item(0);
        }
        // проверю, не оплачивалась ли часть платежа ранее
        /** @var DOMElement $membershipContainer */
        $payedBefore = CashHandler::toRubles(0 . $membershipContainer->getAttribute('payed'));
        // получу данные о полном счёте за электричество
        if ($main) {
            $membershipQuarters = $billDom->query('//membership/quarter');
        } else {
            $membershipQuarters = $billDom->query('//additional_membership/quarter');
        }

        /** @var DOMElement $quarter */
        foreach ($membershipQuarters as $quarter) {
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($quarter, 'summ');
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if ($prepayed > 0) {
                // часть квартала оплачена заранее
                $date = $quarter->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
                // отмечу квартал последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
            } else {
                // отмечу месяц как оплаченный полностью
                $date = $quarter->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
            }
        }
        $cottageInfo->partialPayedMembership = null;
    }

    public static function checkPartialPayedQuarter($cottageInfo)
    {
        if ($cottageInfo->partialPayedMembership) {
            $dom = new DOMHandler($cottageInfo->partialPayedMembership);
            $root = $dom->query('/partial');
            return DOMHandler::getElemAttributes($root->item(0));
        }
        return null;
    }

    /**
     * @param $billInfo Table_payment_bills|Table_payment_bills_double
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return bool
     */
    public static function noTimeForPay($billInfo, $cottageInfo)
    {
        $paysDom = new DOMHandler($billInfo->bill_content);
        $membershipPayments = $paysDom->query("//membership/quarter");
        if ($membershipPayments->length > 0) {
            /** @var DOMElement $firstPayed */
            $firstPayed = $membershipPayments->item(0);
            $firstPayedQuarter = $firstPayed->getAttribute("date");
            $firstQuarterToPay = TimeHandler::getNextQuarter($cottageInfo->membershipPayFor);
            if ($firstPayedQuarter > $firstQuarterToPay) {
                return true;
            }
        }
        return false;
    }
}