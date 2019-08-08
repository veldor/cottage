<?php

namespace app\models;


use app\models\tables\Table_bill_fines;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use yii\base\Model;

class FinesHandler extends Model
{

    public static $types = ['membership' => 'членские взносы', 'target' => 'целевые взносы', 'power' => 'электроэнергия'];

    private const PERCENT = 0.5;
    private const START_POINT = 1561939201;

    public static function getFines($cottageNumber)
    {
        return Table_penalties::find()->where(['cottage_number' => $cottageNumber])->all();
    }

    public static function check($cottageNumber)
    {
        $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
        $powerDuties = PowerHandler::getDebtReport($cottageInfo);
        if (!empty($powerDuties)) {
            foreach ($powerDuties as $powerDuty) {
                if ($powerDuty['difference'] > 0) {
                    // получу дату оплаты долга
                    $payUp = TimeHandler::getPayUpMonth($powerDuty['month']);
                    if ($payUp < self::START_POINT) {
                        $payUp = self::START_POINT;
                    }
                    // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                    $dayDifference = TimeHandler::checkDayDifference($payUp);
                    if ($dayDifference > 0) {
                        $totalPay = CashHandler::rublesMath(CashHandler::toRubles($powerDuty['totalPay']) - CashHandler::toRubles($powerDuty['prepayed']));
                        $fines = CashHandler::countPercent($totalPay, self::PERCENT);
                        $totalFines = $fines * $dayDifference;
                        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $powerDuty['month'], 'pay_type' => 'power'])->one();
                        if (empty($existentFine)) {
                            $existentFine = new Table_penalties();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'power';
                            $existentFine->period = $powerDuty['month'];
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        $existentFine->summ = $totalFines;
                        $existentFine->save();
                    }
                }
            }
        }

        $membershipDuties = MembershipHandler::getDebt($cottageInfo);
        if (!empty($membershipDuties)) {
            foreach ($membershipDuties as $key => $membershipDuty) {
                // получу дату оплаты долга
                $payUp = TimeHandler::getPayUpQuarterTimestamp($key);
                if ($payUp < self::START_POINT) {
                    $payUp = self::START_POINT;
                }
                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if ($dayDifference > 0) {
                    $fines = CashHandler::countPercent($membershipDuty['total_summ'], self::PERCENT);
                    $totalFines = $fines * $dayDifference;
                    $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $key, 'pay_type' => 'membership'])->one();
                    if (empty($existentFine)) {
                        $existentFine = new Table_penalties();
                        $existentFine->cottage_number = $cottageNumber;
                        $existentFine->pay_type = 'membership';
                        $existentFine->period = $key;
                        $existentFine->payUpLimit = $payUp;
                        $existentFine->payed_summ = 0;
                        $existentFine->is_full_payed = 0;
                        $existentFine->is_partial_payed = 0;
                        $existentFine->is_enabled = 1;
                    }
                    $existentFine->summ = $totalFines;
                    $existentFine->save();
                }
            }
        }
        $targetDuties = TargetHandler::getDebt($cottageInfo);
        if (!empty($targetDuties)) {
            foreach ($targetDuties as $key => $targetDuty) {
                // получу дату оплаты долга
                $payUp = Table_tariffs_target::find()->where(['year' => $key])->one()->payUpTime;
                if ($payUp < self::START_POINT) {
                    $payUp = self::START_POINT;
                }
                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if ($dayDifference > 0) {
                    $fines = CashHandler::countPercent($targetDuty['realSumm'], self::PERCENT);
                    $totalFines = $fines * $dayDifference;
                    $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $key, 'pay_type' => 'target'])->one();
                    if (empty($existentFine)) {
                        $existentFine = new Table_penalties();
                        $existentFine->cottage_number = $cottageNumber;
                        $existentFine->pay_type = 'target';
                        $existentFine->period = $key;
                        $existentFine->payUpLimit = $payUp;
                        $existentFine->payed_summ = 0;
                        $existentFine->is_full_payed = 0;
                        $existentFine->is_partial_payed = 0;
                        $existentFine->is_enabled = 1;
                    }
                    $existentFine->summ = $totalFines;
                    $existentFine->save();
                }
            }
        }
        if($cottageInfo->haveAdditional){
            $cottageNumber = $cottageNumber . '-a';
            // расчитаю пени для дополнительного участка
            $additionalCottageInfo = Cottage::getCottageByLiteral($cottageNumber);
            $powerDuties = PowerHandler::getDebtReport($additionalCottageInfo);
            if(!empty($powerDuties)){
                foreach ($powerDuties as $powerDuty) {
                    if ($powerDuty['difference'] > 0) {
                        // получу дату оплаты долга
                        $payUp = TimeHandler::getPayUpMonth($powerDuty['month']);
                        if ($payUp < self::START_POINT) {
                            $payUp = self::START_POINT;
                        }
                        // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                        $dayDifference = TimeHandler::checkDayDifference($payUp);
                        if ($dayDifference > 0) {
                            $totalPay = CashHandler::rublesMath(CashHandler::toRubles($powerDuty['totalPay']) - CashHandler::toRubles($powerDuty['prepayed']));
                            $fines = CashHandler::countPercent($totalPay, self::PERCENT);
                            $totalFines = $fines * $dayDifference;
                            $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $powerDuty['month'], 'pay_type' => 'power'])->one();
                            if (empty($existentFine)) {
                                $existentFine = new Table_penalties();
                                $existentFine->cottage_number = $cottageNumber;
                                $existentFine->pay_type = 'power';
                                $existentFine->period = $powerDuty['month'];
                                $existentFine->payUpLimit = $payUp;
                                $existentFine->payed_summ = 0;
                                $existentFine->is_full_payed = 0;
                                $existentFine->is_partial_payed = 0;
                                $existentFine->is_enabled = 1;
                            }
                            $existentFine->summ = $totalFines;
                            $existentFine->save();
                        }
                    }
                }
            }
            $membershipDuties = MembershipHandler::getDebt($additionalCottageInfo);
            if (!empty($membershipDuties)) {
                foreach ($membershipDuties as $key => $membershipDuty) {
                    // получу дату оплаты долга
                    $payUp = TimeHandler::getPayUpQuarterTimestamp($key);
                    if ($payUp < self::START_POINT) {
                        $payUp = self::START_POINT;
                    }
                    // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                    $dayDifference = TimeHandler::checkDayDifference($payUp);
                    if ($dayDifference > 0) {
                        $fines = CashHandler::countPercent($membershipDuty['total_summ'], self::PERCENT);
                        $totalFines = $fines * $dayDifference;
                        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $key, 'pay_type' => 'membership'])->one();
                        if (empty($existentFine)) {
                            $existentFine = new Table_penalties();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'membership';
                            $existentFine->period = $key;
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        $existentFine->summ = $totalFines;
                        $existentFine->save();
                    }
                }
            }
            $targetDuties = TargetHandler::getDebt($additionalCottageInfo);
            if (!empty($targetDuties)) {
                foreach ($targetDuties as $key => $targetDuty) {
                    // получу дату оплаты долга
                    $payUp = Table_tariffs_target::find()->where(['year' => $key])->one()->payUpTime;
                    if ($payUp < self::START_POINT) {
                        $payUp = self::START_POINT;
                    }
                    // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                    $dayDifference = TimeHandler::checkDayDifference($payUp);
                    if ($dayDifference > 0) {
                        $fines = CashHandler::countPercent($targetDuty['realSumm'], self::PERCENT);
                        $totalFines = $fines * $dayDifference;
                        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $key, 'pay_type' => 'target'])->one();
                        if (empty($existentFine)) {
                            $existentFine = new Table_penalties();
                            $existentFine->cottage_number = $cottageNumber;
                            $existentFine->pay_type = 'target';
                            $existentFine->period = $key;
                            $existentFine->payUpLimit = $payUp;
                            $existentFine->payed_summ = 0;
                            $existentFine->is_full_payed = 0;
                            $existentFine->is_partial_payed = 0;
                            $existentFine->is_enabled = 1;
                        }
                        $existentFine->summ = $totalFines;
                        $existentFine->save();
                    }
                }
            }
        }
    }

    public static function disableFine($finesId)
    {
        $fine = Table_penalties::findOne($finesId);
        if (empty($fine)) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 0;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').removeClass('hidden');$('#fines_{$fine->id}_disable').addClass('hidden');$('#finesSumm').html('" . CashHandler::toSmoothRubles($summ) . "');</script>";
        return ['status' => 1, 'message' => 'Пени за период не расчитываются' . $js];
    }

    public static function enableFine($finesId)
    {
        $fine = Table_penalties::findOne($finesId);
        if (empty($fine)) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 1;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').addClass('hidden');$('#fines_{$fine->id}_disable').removeClass('hidden');$('#finesSumm').html('" . CashHandler::toSmoothRubles($summ) . "');</script>";
        return ['status' => 1, 'message' => 'Пени за период расчитываются' . $js];
    }

    public static function getFinesSumm($cottageId)
    {
        $summ = 0;
        $fines = Table_penalties::find()->where(['cottage_number' => $cottageId])->all();
        if (!empty($fines)) {
            foreach ($fines as $fine) {
                if ($fine->is_enabled)
                    $summ += CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ);
            }
        }
        return CashHandler::toRubles($summ);
    }

    /**
     * @param Table_view_fines_info[] $fines
     * @param $transaction Table_transactions
     * @throws ExceptionWithStatus
     */
    public static function makePayed(array $fines, $transaction)
    {
        foreach ($fines as $fine) {
            self::payFine($fine->fines_id, $fine->start_summ, $transaction->id);
        }
    }

    /**
     * @param int $fines_id
     * @param double $summ
     * @param int $transactionId
     * @throws ExceptionWithStatus
     */
    private static function payFine(int $fines_id, $summ, int $transactionId)
    {
        $fine = Table_penalties::findOne($fines_id);
        $payLeft = $fine->summ - $fine->payed_summ;
        if($summ > $payLeft){
            throw new ExceptionWithStatus("Попытка заплатить за пени больше необходимого");
        }
        elseif($summ == $payLeft){
            $fine->is_full_payed = 1;
            $fine->is_partial_payed = 0;
        }
        else{
            $fine->is_full_payed = 0;
            $fine->is_partial_payed = 1;
        }
        $fine->payed_summ += $summ;
        $fine->save();
        $payed = new Table_payed_fines();
        $payed->fine_id = $fine->id;
        $payed->transaction_id = $transactionId;
        $payed->summ = $summ;
        $payed->pay_date = time();
        $payed->save();
    }
    /**
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $paymentSumm double
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function handlePartialPayment($bill, $paymentSumm, $cottageInfo, $transaction)
    {
        // найду пени
        $fines = Table_view_fines_info::find()->where(['bill_id' => $bill->id])->all();
        foreach ($fines as $fine) {
            // определю, какая сумма нужна для погашения платежа
            $summToPay = CashHandler::toRubles(CashHandler::toRubles($fine->start_summ) - $fine->payed_summ);
            if($summToPay > 0){
                // найду главную запись пени
                $fineInfo = Table_penalties::findOne($fine->fines_id);
                // если введённой суммы хватает для погашения- регистрирую оплату, если меньше
                if($paymentSumm > $summToPay){
                    $paymentSumm -= $summToPay;
                    // оплачиваю полностью
                    $payedFine = new Table_payed_fines();
                    $payedFine->fine_id = $fine->fines_id;
                    $payedFine->transaction_id = $transaction->id;
                    $payedFine->pay_date = $transaction->bankDate;
                    $payedFine->summ = $summToPay;
                    $payedFine->save();
                    $fineInfo->payed_summ += $summToPay;
                    if(CashHandler::toRubles($fineInfo->summ) == CashHandler::toRubles($fineInfo->payed_summ)){
                        $fineInfo->is_partial_payed = 0;
                        $fineInfo->is_full_payed = 1;
                    }
                    else{
                        $fineInfo->is_partial_payed = 1;
                    }
                    $fineInfo->save();
                }
                else{
                    // оплачиваю полностью
                    $payedFine = new Table_payed_fines();
                    $payedFine->fine_id = $fine->fines_id;
                    $payedFine->transaction_id = $transaction->id;
                    $payedFine->pay_date = $transaction->bankDate;
                    $payedFine->summ = $paymentSumm;
                    $payedFine->save();

                    $fineInfo->payed_summ += $paymentSumm;
                    if(CashHandler::toRubles($fineInfo->summ) == CashHandler::toRubles($fineInfo->payed_summ)){
                        $fineInfo->is_partial_payed = 0;
                        $fineInfo->is_full_payed = 1;
                    }
                    else{
                        $fineInfo->is_partial_payed = 1;
                    }
                    $fineInfo->save();
                    break;
                }
            }
        }
    }
}