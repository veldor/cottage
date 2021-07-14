<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 22:57
 */

namespace app\models;

use app\models\database\Accruals_membership;
use app\models\database\CottagesFastInfo;
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

    public array $membership;


    public const SCENARIO_NEW_RECORD = 'new_record';

    public static function changePayTime(int $id, $timestamp): void
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

    public static function fillLastPayedQuarter(CottageInterface $cottage): void
    {
        $cottage->membershipPayFor = self::getLastPayedQuarter($cottage);
        $cottage->save();
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
        if ($cottage->isMain()) {
            $firstPayed = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->orderBy('quarter')->limit(1)->all();
        } else {
            $firstPayed = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->limit(1)->all();
        }
        if (empty($firstPayed)) {
            return TimeHandler::getNextQuarter($cottage->membershipPayFor);
        }
        return $firstPayed[0]->quarter;
    }

    /**
     * Верну раскладку по тарифу
     * @param $quarter
     * @return FixedFloatTariff
     */
    public static function getCottageTariff($quarter): FixedFloatTariff
    {
        $fixed = 0;
        $float = 0;
        $tariff = Table_tariffs_membership::findOne(['quarter' => $quarter]);
        if ($tariff !== null) {
            $fixed = $tariff->fixed_part;
            $float = $tariff->changed_part;
        }
        return new FixedFloatTariff(['fixed' => $fixed, 'float' => $float]);
    }

    /**
     * Получение платежей за данный период
     * @param $cottage CottageInterface
     * @param string $period
     * @return Table_additional_payed_membership[]|Table_payed_membership[]
     */
    public static function getPaysForPeriod(CottageInterface $cottage, string $period): array
    {
        if (Cottage::isMain($cottage)) {
            return Table_payed_membership::findAll(['cottageId' => $cottage->getBaseCottageNumber(), 'quarter' => $period]);
        }
        return Table_additional_payed_membership::findAll(['cottageId' => $cottage->getBaseCottageNumber(), 'quarter' => $period]);
    }

    /**
     * Получаю стоимость периода
     * @param $cottage CottageInterface
     * @param string $quarter
     * @return float
     * @throws ExceptionWithStatus
     */
    public static function getAmount(CottageInterface $cottage, string $quarter): float
    {
        $accrual = Accruals_membership::findOne(['cottage_number' => $cottage->getCottageNumber(), 'quarter' => $quarter]);
        if ($accrual !== null) {
            return Calculator::countFixedFloat(
                $accrual->fixed_part,
                $accrual->square_part,
                $accrual->counted_square
            );
        }
        throw new ExceptionWithStatus('Не найдены начисления по участку');
    }

    public static function getFirstPayedQuarter(interfaces\CottageInterface $cottage)
    {
        if ($cottage->isMain()) {
            return Table_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->one();
        }
        return Table_additional_payed_membership::find()->where(['cottageId' => $cottage->getBaseCottageNumber()])->orderBy('quarter')->one();
    }

    public static function getPaysBefore(string $quarter, interfaces\CottageInterface $cottage, int $periodEnd): array
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
        $tariff = self::getCottageTariff(TimeHandler::getQuarterFromTimestamp(TimeHandler::getMonthTimestamp($month)));
        $square = CottageSquareChanges::getQuarterSquare($cottage, TimeHandler::getQuarterFromTimestamp(TimeHandler::getMonthTimestamp($month)));
        return Calculator::countFixedFloat($tariff->fixed, $tariff->float, $square);
    }

    /**
     * @param CottageInterface $cottage
     * @return Accruals_membership[]
     */
    public static function getCottageAccruals(CottageInterface $cottage): array
    {
        return Accruals_membership::find()->where(['cottage_number' => $cottage->getCottageNumber()])->orderBy('quarter')->all();
    }

    public static function getYearPayments(string $year): array
    {
        $accrual = [];
        $pays = [];
        $quarterCounter = 1;
        while ($quarterCounter < 5) {
            $quarter = "$year-$quarterCounter";
            $accruals = Accruals_membership::findAll(['quarter' => $quarter]);
            $totalAccrued = 0;
            if (!empty($accruals)) {
                foreach ($accruals as $accrualItem) {
                    $totalAccrued += CashHandler::toRubles(
                        Calculator::countFixedFloat(
                            $accrualItem->fixed_part,
                            $accrualItem->square_part,
                            $accrualItem->counted_square
                        )
                    );
                }
            }
            if ($quarterCounter === 1) {
                $accrual[] = ["$year-01", CashHandler::toRubles($totalAccrued)];
                $accrual[] = ["$year-02", 0];
                $accrual[] = ["$year-03", 0];
            } else {
                $month = $quarterCounter * 3 - 2;
                if ($month < 10) {
                    $accrual[] = ["$year-0$month", CashHandler::toRubles($totalAccrued)];
                    $additionalCounter = 0;
                    while ($additionalCounter < 3) {
                        $accrual[] = ["$year-" . ($month + $additionalCounter), 0];
                        ++$additionalCounter;
                    }
                } else {
                    $accrual[] = ["$year-$month", CashHandler::toRubles($totalAccrued)];
                    $additionalCounter = 0;
                    while ($additionalCounter < 3) {
                        $accrual[] = ["$year-" . ($month + $additionalCounter), 0];
                        ++$additionalCounter;
                    }
                }
            }
            $payed = Table_payed_membership::findAll(['quarter' => $quarter]);
            $addPayed = Table_additional_payed_membership::findAll(['quarter' => $quarter]);
            $result = array_merge($payed, $addPayed);
            $totalPayed = 0;
            if (!empty($result)) {
                foreach ($result as $item) {
                    $totalPayed += CashHandler::toRubles($item->summ);
                }
            }
            if ($quarterCounter === 1) {
                $pays[] = ["$year-01", CashHandler::toRubles($totalPayed)];
                $pays[] = ["$year-02", 0];
                $pays[] = ["$year-03", 0];
            } else {
                $month = $quarterCounter * 3 - 2;
                if ($month < 10) {
                    $pays[] = ["$year-0$month", CashHandler::toRubles($totalPayed)];
                    $additionalCounter = 0;
                    while ($additionalCounter < 3) {
                        $pays[] = ["$year-" . ($month + $additionalCounter), 0];
                        ++$additionalCounter;
                    }
                } else {
                    $pays[] = ["$year-$month", CashHandler::toRubles($totalPayed)];
                    $additionalCounter = 0;
                    while ($additionalCounter < 3) {
                        $pays[] = ["$year-" . ($month + $additionalCounter), 0];
                        ++$additionalCounter;
                    }
                }
            }
            $quarterCounter++;
        }
        return [['name' => 'Начислено к оплате, руб.', 'data' => $accrual], ['name' => 'Оплачено, руб.', 'data' => $pays]];

    }

    /**
     * Получение последнего квартала, за который была произведена оплата взносов
     * @param $cottageInfo CottageInterface
     */
    public static function getLastPayedQuarter(CottageInterface $cottageInfo)
    {
        // получу все начисления, считая с конца
        $accruals = Accruals_membership::find()->where(['cottage_number' => $cottageInfo->getCottageNumber()])->orderBy('quarter')->all();
        if (!empty($accruals)) {
            $lastPayedQuarter = null;
            $lastQuarter = null;
            while (true) {
                /** @var Accruals_membership $quarter */
                $quarter = array_pop($accruals);
                if ($quarter !== null) {
                    $lastQuarter = $quarter->quarter;
                    $accrued = Calculator::countFixedFloat($quarter->fixed_part, $quarter->square_part, $quarter->counted_square);
                    if ($accrued > 0) {
                        // посчитаю оплаты
                        $pays = self::getPaysForPeriod($cottageInfo, $quarter->quarter);
                        if (empty($pays)) {
                            continue;
                        }
                        $payed = 0;
                        foreach ($pays as $pay) {
                            $payed += $pay->summ;
                        }
                        if (CashHandler::toRubles($payed) === CashHandler::toRubles($accrued)) {
                            if ($lastPayedQuarter === null) {
                                $lastPayedQuarter = $quarter->quarter;
                            }
                            return $lastPayedQuarter;
                        }
                    } else if ($lastPayedQuarter === null) {
                        $lastPayedQuarter = $quarter->quarter;
                    }
                } else {
                    return TimeHandler::getPrevQuarter($lastQuarter);
                }
            }
        }
        return TimeHandler::getCurrentQuarter();
    }

    public static function getPeriodPaysAmount(string $cottage_number, string $quarter): float
    {
        $pays = self::getPaysForPeriod(Cottage::getCottageByLiteral($cottage_number), $quarter);
        $payed = 0;
        if (!empty($pays)) {
            foreach ($pays as $pay) {
                $payed += $pay->summ;
            }
        }
        return CashHandler::toRubles($payed);
    }

    /**
     * Возвращает сумму долга по участку
     * @param $cottageInfo CottageInterface
     * @return float
     */
    public static function getDebtAmount(CottageInterface $cottageInfo)
    {
        $cottageDebt = self::getDebt($cottageInfo);
        $debt = 0;
        if (!empty($cottageDebt)) {
            foreach ($cottageDebt as $item) {
                $debt += $item->amount - $item->partialPayed;
            }
        }
        return $debt;
    }


    public function scenarios(): array
    {
        return [
            self::SCENARIO_NEW_RECORD => ['membership'],
        ];
    }

    /**
     * @param $cottage CottageInterface
     * @return MembershipDebt[]
     */
    public static function getDebt(CottageInterface $cottage): array
    {
        $result = [];
        // проверка, является ли участок основным
        $isMain = Cottage::isMain($cottage);
        if (!$isMain && !$cottage->isMembership) {
            // если дополнительный участок не оплачивает членские взносы
            return $result;
        }
        // получу список кварталов, начиная от первого неоплаченного до текущего
        $list = TimeHandler::getQuarterList(self::getLastPayedQuarter($cottage));
        if (empty($list)) {
            return $result;
        }
        foreach ($list as $key => $value) {
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
                if ($payedYet < $accrual->getAccrual()) {
                    $result[] = new MembershipDebt(['partialPayed' => $payedYet, 'amount' => Calculator::countFixedFloat($accrual->fixed_part, $accrual->square_part, $accrual->counted_square), 'quarter' => $key, 'tariffFixed' => $accrual->fixed_part, 'tariffFloat' => $accrual->square_part, 'tariff' => $existentTariff]);
                }
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
    public static function getFutureQuarters($quartersNumber, $cottageNumber, bool $additional): array
    {
        if ($additional) {
            $cottage = AdditionalCottage::getCottage($cottageNumber);
            $type = 'additionalMembership';
        } else {
            $cottage = Cottage::getCottageInfo($cottageNumber);
            $type = 'membership';
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
                    $content .= "<tr><td><input type='checkbox' class='pay-activator form-control' data-for='ComplexPayment[$type][$key][value]' name='ComplexPayment[$type][$key][pay]'/></td><td>{$key}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[$type][$key][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
                }
            } else {
                // Добавлю новое начисление
                (new Accruals_membership(['quarter' => $key, 'cottage_number' => $cottage->getCottageNumber(), 'fixed_part' => $value['fixed'], 'square_part' => $value['float'], 'counted_square' => $cottage->cottageSquare]))->save();
                $summToPay = Calculator::countFixedFloat($value['fixed'], $value['float'], $cottage->cottageSquare);
                $content .= "<tr><td><input type='checkbox' class='pay-activator form-control' data-for='ComplexPayment[$type][$key][value]' name='ComplexPayment[$type][$key][pay]'/></td><td>{$key}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[$type][$key][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
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
    public static function getTariffs($period, bool $skipCheckWholeness = false): array
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
    public static function createPayment($cottageInfo, $membershipPeriods, bool $additional = false): array
    {
        $answer = '';
        $summ = 0;
        foreach ($membershipPeriods as $key => $value) {
            $toPay = CashHandler::toRubles($value['value']);
            $accrual = Accruals_membership::getItem($cottageInfo, $key);
            if ($accrual !== null) {
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
                $answer .= "<quarter date='$key' summ='$totalSumm' square='$accrual->counted_square' float-cost='{$cost['float']}' float='$accrual->square_part' fixed='$accrual->fixed_part' prepayed='$payedSumm'/>";
            }
        }
        if ($additional) {
            $answer = /** @lang xml */
                "<additional_membership cost='$summ'>" . $answer . '</additional_membership>';
        } else {
            $answer = /** @lang xml */
                "<membership cost='$summ'>" . $answer . '</membership>';
        }
        return ['text' => $answer, 'summ' => CashHandler::rublesRound($summ)];
    }

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payments
     * @param $transaction Table_transactions
     * @param bool $additional
     * @throws Exception
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, Table_transactions $transaction, bool $additional = false): void
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
     * @throws Exception
     */
    public static function insertPayment($payment, $cottageInfo, $billInfo, Table_transactions $transaction, bool $additional = false): void
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
            CottagesFastInfo::recalculateMembershipDebt($cottageInfo);
            CottagesFastInfo::checkExpired($cottageInfo);
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $date
     * @param $summ
     * @param $transaction Table_transactions|Table_transactions_double
     * @throws Exception
     */
    public static function insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ): void
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
        CottagesFastInfo::recalculateMembershipDebt($cottageInfo);
        CottagesFastInfo::checkExpired($cottageInfo);
    }

    public static function recalculateMembership($period): void
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
            $cottages = Cottage::getRegister();
            $additionalCottages = AdditionalCottage::getRegistred();

            $cottagesCount = count($cottages);
            $additionalCottagesCount = count($additionalCottages);

            $fullSquare = 0;
            $neededSumm = 0;
            $payedOutsideCounter = 0;
            $payedOutside = 0;

            foreach ($cottages as $cottage) {
                $summ = Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                $neededSumm += $summ;
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
                    $neededSumm += Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
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
     * @param string|null $period
     * @return Table_tariffs_membership
     */
    public static function getRowTariff(?string $period = null): Table_tariffs_membership
    {
        if (!empty($period)) {
            $quarter = TimeHandler::isQuarter($period);
            $data = Table_tariffs_membership::findOne(['quarter' => $quarter['full']]);
            if ($data !== null) {
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

    public static function validateFillTariff($from): array
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

    /**
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        foreach ($this->membership as $key => $value) {
            self::createTariff($key, $value['fixed'], $value['float']);
        }
        return true;
    }


    /**
     * @param $quarter
     * @param $fixed
     * @param $float
     * @throws Exception
     */
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
     * @param $cottageInfo CottageInterface
     * @return null
     * @throws ExceptionWithStatus
     */
    public static function checkPartialPayedQuarter(CottageInterface $cottageInfo): ?array
    {
        $accruals = self::getCottageAccruals($cottageInfo);
        $partial = [];
        if (!empty($accruals)) {
            foreach ($accruals as $accrual) {
                $payments = self::getPaysForPeriod($cottageInfo, $accrual->quarter);
                $amount = self::getAmount($cottageInfo, $accrual->quarter);
                $fullAmount = $amount;
                if ($payments !== null) {
                    foreach ($payments as $payment) {
                        try {
                            $amount = CashHandler::toRubles($amount - $payment->summ);
                        } catch (Exception $e) {
                        }
                    }
                }
                if ($amount > 0 && $amount !== $fullAmount) {
                    $partial[$accrual->quarter] = $amount;
                }
            }
        }
        return $partial ?? null;
    }

    /**
     * @param $billInfo Table_payment_bills|Table_payment_bills_double
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return bool
     */
    public static function noTimeForPay($billInfo, $cottageInfo): bool
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