<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

namespace app\models;


use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use DateTime;
use InvalidArgumentException;
use yii\base\Model;

class Search extends Model
{
    public $startDate;
    public $finishDate;
    public $searchType;
    public $searchTypeList = ['routine' => 'Обычный', 'summary' => 'Суммарный', 'report' => 'Отчёт'];

    const SCENARIO_BILLS_SEARCH = 'bills-search';

    /**
     * @param Table_transactions[]|Table_transactions_double[] $results
     * @return array
     */
    private static function handleTransactions(array $results)
    {
        $totalSumm = 0;
        $text = '';
        if (!empty($results)) {
            foreach ($results as $result) {
                $totalSumm += CashHandler::toRubles($result->transactionSumm);
                $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                $summ = CashHandler::toShortSmoothRubles($result->transactionSumm);
                if ($result instanceof Table_transactions) {
                    $text .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='{$result->billId}'>{$result->billId}</a></td><td><a href='/show-cottage/{$result->cottageNumber}'>{$result->cottageNumber}</a></td></td><td><b class='text-info'>{$summ}</b></td><td><button type='button' data-transaction-id='{$result->id}' class='btn btn-danger change-date'>Изменить</button></td></tr>";
                } else {
                    $text .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='{$result->billId}-a'>{$result->billId}-a</a></td><td><a href='/show-cottage/{$result->cottageNumber}'>{$result->cottageNumber}-a</a></td><td><b class='text-info'>{$summ}</b></td><td><button type='button' data-transaction-id='{$result->id}' class='btn btn-danger change-date'>Изменить</button></td></tr>";
                }
            }
        }
        return ['text' => $text, 'summ' => $totalSumm];

    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_BILLS_SEARCH => ['startDate', 'finishDate', 'searchType'],
        ];
    }

    public function rules(): array
    {
        return [
            [['startDate', 'finishDate', 'searchType'], 'required'],
            [['startDate', 'finishDate'], 'date', 'format' => 'y-M-d'],
            ['searchType', 'in', 'range' => ['routine', 'summary', 'report']]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'startDate' => 'Начало периода',
            'finishDate' => 'Конец периода',
            'searchType' => 'Тип отчёта',
        ];
    }


    public function doSearch(): array
    {
        $start = new DateTime('0:0:00' . $this->startDate);
        $finish = new DateTime('23:59:50' . $this->finishDate);
        $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];
        switch ($this->searchType) {
            case 'summary' :
                return $this->getSummary($interval);
            case 'report':
                return $this->getReport($interval);
            case 'routine':
                return $this->getTransactions($interval);
        }
        throw new InvalidArgumentException("Неверный тип отчёта");
    }

    private function getTransactions($interval): array
    {
        $results = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        if (!empty($results || !empty($doubleResults))) {
            $totalSumm = 0;
            $content = "<table class='table table-striped'><thead><th>Дата платежа</th><th>№</th><th>Участок</th><th>Сумма</th><th>Дата</th></thead><tbody>";
            $data = self::handleTransactions($results);
            $content .= $data['text'];
            $totalSumm += $data['summ'];
            $data = self::handleTransactions($doubleResults);
            $content .= $data['text'];
            $totalSumm += $data['summ'];
            $content .= "</tbody></table>";
            return ['status' => 1, 'data' => $content, 'totalSumm' => $totalSumm];
        } else {
            return ['status' => 1, 'data' => "<h2>Транзакций за период не было</h2>", 'totalSumm' => 0];
        }
    }

    private function getSummary($interval): array
    {
        $totalPowerSumm = 0;
        $totalMemSumm = 0;
        $totalTargetSumm = 0;
        $totalSingleSumm = 0;
        $totalFinesSumm = 0;
        $discountsSumm = 0;
        $toDepositSumm = 0;
        $fromDepositSumm = 0;

        // найду транзакции за день
        $results = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $results = array_merge($results, $doubleResults);
        if(!empty($results)){
            foreach ($results as $result) {
                // найду все сущности, привязанные к транзакции
                if($result instanceof Table_transactions){
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $result->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $result->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $result->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $result->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $result->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $result->id])->all();
                    $discounts = Table_discounts::find()->where(['transactionId' => $result->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'destination' => 'in'])->all();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id, 'destination' => 'out'])->all();
                }
                else{
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $result->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $result->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $result->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $result->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $result->id . '-a'])->all();
                    $discounts = Table_discounts::find()->where(['transactionId' => $result->id . '-a'])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id . '-a', 'destination' => 'in'])->all();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $result->id . '-a', 'destination' => 'out'])->all();
                }
                if(!empty($powers)){
                    foreach ($powers as $p){
                        $totalPowerSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($memberships)){
                    foreach ($memberships as $p){
                        $totalMemSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($targets)){
                    foreach ($targets as $p){
                        $totalTargetSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($singles)){
                    foreach ($singles as $p){
                        $totalSingleSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($fines)){
                    foreach ($fines as $p){
                        $totalFinesSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($discounts)){
                    foreach ($discounts as $p){
                        $discountsSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($toDeposit)){
                    foreach ($toDeposit as $p){
                        $toDepositSumm += CashHandler::toRubles($p->summ);
                    }
                }
                if(!empty($fromDeposit)){
                    foreach ($fromDeposit as $p){
                        $fromDepositSumm += CashHandler::toRubles($p->summ);
                    }
                }
            }
            $content = "<table class='table table-striped'><thead><th>Электроэнергия</th><th>Членские</th><th>Целевые</th><th>Разовые</th><th>Пени</th><th>С депозита</th><th>На депозит</th><th>Скидка</th></thead><tbody>";

            $content .= "<tr><td>" . CashHandler::toShortSmoothRubles($totalPowerSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalMemSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalTargetSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalSingleSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalFinesSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($fromDepositSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($toDepositSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($discountsSumm) . "</td></tr></tbody></table>";
            $total = CashHandler::toRubles($totalSingleSumm + $totalPowerSumm + $totalTargetSumm + $totalFinesSumm + $totalMemSumm - $discountsSumm - $fromDepositSumm + $toDepositSumm, true);
            return ['status' => 1, 'data' => $content, 'totalSumm' => $total];
        }
        return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0];
    }

    /**
     * @param $interval
     * @return array
     */
    private function getReport($interval)
    {
        $wholePower = 0;
        $wholeTarget = 0;
        $wholeMembership = 0;
        $wholeSingle = 0;
        $wholeFines = 0;
        $wholeDiscount = 0;
        $wholeDeposit = 0;
        $wholeSumm = 0;
        $fullSumm = 0;
        // найду транзакции за день по банковскому отчёту
        $transactions = Table_transactions::find()->where(['>=', 'bankDate', $interval['start']])->andWhere(['<=', 'bankDate', $interval['finish']])->all();
        $trs = Table_transactions_double::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
        $transactions = array_merge($transactions, $trs);

        if(!empty($transactions)){
            $content = [];
            foreach ($transactions as $transaction) {
                $wholeSumm += CashHandler::toRubles($transaction->transactionSumm);
                $fullSumm += CashHandler::toRubles($transaction->transactionSumm);
                if($transaction instanceof Table_transactions){
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);

                    // получу оплаченные сущности
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $discount = Table_discounts::find()->where(['transactionId' => $transaction->id])->one();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out'])->one();
                    if(!empty($memberships)){
                        $memSumm = 0;
                        $memList = '';
                        foreach ($memberships as $membership) {
                            if($membership instanceof Table_payed_membership){
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toShortSmoothRubles($membership->summ) . '</b><br/>';
                            }
                            else{
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>'  . CashHandler::toShortSmoothRubles($membership->summ) . '</b><br/>';
                            }
                            $memSumm += $membership->summ;
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                        }
                        $memSumm = CashHandler::toShortSmoothRubles($memSumm);
                    }
                    else{
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if(!empty($powers)){
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        foreach ($powers as $power) {
                            if($power instanceof Table_payed_power){
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if(empty($powData)){
                                    echo $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                            else{
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .='(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                        }
                        $powSumm = CashHandler::toShortSmoothRubles($powSumm);
                        $wholePower += CashHandler::toRubles($power->summ);
                    }
                    else{
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if(!empty($targets)){
                        $tarSumm = 0;
                        $tarList = '';
                        foreach ($targets as $target) {
                            if($target instanceof Table_payed_target){
                                $tarList .= $target->year . ': <b>' . CashHandler::toShortSmoothRubles($target->summ) . '</b><br/>';
                            }
                            else{
                                $tarList .= '(Доп) ' . $target->year . ': <b>'  . CashHandler::toShortSmoothRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toShortSmoothRubles($tarSumm);
                    }
                    else{
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if(!empty($singles)){
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toShortSmoothRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                        }
                        $singleSumm = CashHandler::toShortSmoothRubles($singleSumm);
                    }
                    else{
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if(!empty($fines)){
                        $finesSumm = 0;
                        $finesList = '';
                        foreach ($fines as $fine) {
                            // найду информацию о пени
                            $fineInfo = Table_penalties::findOne($fine->fine_id);
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toShortSmoothRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toShortSmoothRubles($finesSumm);
                    }
                    else{
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if(!empty($discount)){
                        $discountSumm = CashHandler::toShortSmoothRubles($discount->summ);
                    }
                    else{
                        $discountSumm = '--';
                    }
                    if(!empty($fromDeposit)){
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                    }
                    else{
                        $fromDepositSumm = 0;
                    }
                    if(!empty($toDeposit)){
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                    }
                    else{
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}</td><td class='cottage-number-cell'>{$transaction->cottageNumber}</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$discountSumm</td><td>$totalDeposit</td><td>" . CashHandler::toShortSmoothRubles($transaction->transactionSumm) . "</td></tr>";
                }
                else{
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $discount = Table_discounts::find()->where(['transactionId' => $transaction->id])->one();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out'])->one();
                    if(!empty($memberships)){
                        $memSumm = 0;
                        $memList = '';
                        foreach ($memberships as $membership) {
                            if($membership instanceof Table_payed_membership){
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toShortSmoothRubles($membership->summ) . '</b><br/>';
                            }
                            else{
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>'  . CashHandler::toShortSmoothRubles($membership->summ) . '</b><br/>';
                            }
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                            $memSumm += $membership->summ;
                        }
                        $memSumm = CashHandler::toShortSmoothRubles($memSumm);
                    }
                    else{
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if(!empty($powers)){
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        foreach ($powers as $power) {
                            if($power instanceof Table_payed_power){
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if(empty($powData)){
                                    echo $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                            else{
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .='(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
                        $powSumm = CashHandler::toShortSmoothRubles($powSumm);
                    }
                    else{
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if(!empty($targets)){
                        $tarSumm = 0;
                        $tarList = '';
                        foreach ($targets as $target) {
                            if($target instanceof Table_payed_target){
                                $tarList .= $target->year . ': <b>' . CashHandler::toShortSmoothRubles($target->summ) . '</b><br/>';
                            }
                            else{
                                $tarList .= '(Доп) ' . $target->year . ': <b>'  . CashHandler::toShortSmoothRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toShortSmoothRubles($tarSumm);
                    }
                    else{
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if(!empty($singles)){
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toShortSmoothRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                        }
                        $singleSumm = CashHandler::toShortSmoothRubles($singleSumm);
                    }
                    else{
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if(!empty($fines)){
                        $finesSumm = 0;
                        $finesList = '';
                        foreach ($fines as $fine) {
                            // найду информацию о пени
                            $fineInfo = Table_penalties::findOne($fine->fine_id);
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toShortSmoothRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toShortSmoothRubles($finesSumm);
                    }
                    else{
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if(!empty($discount)){
                        $discountSumm = CashHandler::toShortSmoothRubles($discount->summ);
                    }
                    else{
                        $discountSumm = '--';
                    }
                    if(!empty($fromDeposit)){
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                    }
                    else{
                        $fromDepositSumm = 0;
                    }
                    if(!empty($toDeposit)){
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                    }
                    else{
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}-a</td><td class='cottage-number-cell'>{$transaction->cottageNumber}-a</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$discountSumm</td><td>$totalDeposit</td><td>" . CashHandler::toShortSmoothRubles($transaction->transactionSumm) . "</td></tr>";
                }
            }
            $content[] = "<tr><td class='date-cell'>Итого</td><td class='bill-id-cell'></td><td class='cottage-number-cell'></td><td class='quarter-cell'></td><td class='mem-summ-cell'>$wholeMembership</td><td class='pow-values'></td><td class='pow-total'></td><td class='pow-summ'>$wholePower</td><td class='target-by-years-cell'></td><td class='target-total'>$wholeTarget</td><td></td><td></td><td></td><td>$wholeFines</td><td></td><td></td><td>$wholeSumm</td></tr>";
            return ['status' => 1, 'data' => $content, 'totalSumm' => $fullSumm];
        }
        else{
            return ['status' => 1, 'data' => "<h2 class='text-center'>Платежей за данный период не было</h2>", 'totalSumm' => 0];
        }
    }
}