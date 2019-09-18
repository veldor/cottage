<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:08
 */

namespace app\models;

use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use DOMElement;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class Report extends Model
{

    /**
     * @param $start
     * @param $end
     * @param $cottageNumber
     * @return array
     */
    public static function cottageReport($start, $end, $cottageNumber): array
    {
        // Получу информацию об участке
        $cottageInfo = Table_cottages::find()->where(['cottageNumber' => $cottageNumber])->select(['cottageNumber', 'cottageSquare', 'cottageOwnerPersonals'])->one();
        $content = [];
        // найду все транзакции данного участка
        $trs = Table_transactions::find()->where(['cottageNumber' => $cottageNumber])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
        if (!empty($trs)) {
//            foreach ($trs as $item) {
//                /** @var Table_fulltransactioninfo $item */
//                // членские взносы
//                $memList = '--';
//                $memSumm = '--';
//                $payedSumm = 0;
//                $partial = $item->partial;
//                $date = TimeHandler::getDateFromTimestamp($item->transactionDate);
//                $type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
//                if(!empty($item->billCast)){
//                    $dom = new DOMHandler($item->billCast);
//                }
//                else{
//                    $dom = new DOMHandler($item->bill_content);
//                }
//                $mem = $dom->query('/payment/membership');
//                if ($mem->length === 1) {
//                    /** @var DOMElement $memItem */
//                    $memItem = $mem->item(0);
//                    if ($partial) {
//                        $payedSumm = $memItem->getAttribute('payed');
//                        if(!empty($payedSumm)){
//                            $payedSumm = CashHandler::toRubles($payedSumm);
//                        }
//                        else{
//                            $payedSumm = 0;
//                        }
//                        if ($payedSumm > 0) {
//                            $memSumm = $payedSumm;
//                        } else {
//                            $memSumm = 0;
//                        }
//                    } else {
//                        $memSumm = CashHandler::toRubles($memItem->getAttribute('cost'));
//                    }
//                    $quarters = $dom->query('/payment/membership/quarter');
//                    if ($memList === '--') {
//                        $memList = '';
//                    }
//                    foreach ($quarters as $quarter) {
//                        /** @var DOMElement $quarter */
//                        $summ = CashHandler::toRubles($quarter->getAttribute('summ'));
//                        if ($partial) {
//                            // если оплата частичная- сверю сумму с полной оплатой раздела.
//                            if ($payedSumm > 0) {
//                                if ($summ > $payedSumm) {
//                                    $payedSumm -= CashHandler::toRubles($summ);
//                                } else {
//                                    $summ = $payedSumm;
//                                    $payedSumm = 0;
//                                }
//                            } else {
//                                $summ = 0;
//                            }
//                        }
//                        $memList .= $quarter->getAttribute('date') . ' - ' . $summ . '<br>';
//                    }
//                }
//                $memAdd = $dom->query('/payment/additional_membership');
//                if ($memAdd->length === 1) {
//                    $payedSumm = 0;
//                    $additionalSumm = 0;
//                    $memItem = $memAdd->item(0);
//                    if ($partial) {
//                        $payedSumm = $memItem->getAttribute('payed');
//                        if ($payedSumm > 0) {
//                            $additionalSumm = $payedSumm;
//                        } else {
//                            $additionalSumm = 0;
//                        }
//                    }
//
//                    if ($memSumm === '--') {
//                        $memSumm = $additionalSumm;
//                    } else {
//                        $memSumm += $additionalSumm;
//                    }
//                    $quarters = $dom->query('/payment/additional_membership/quarter');
//                    if ($memList === '--') {
//                        $memList = '';
//                    }
//                    foreach ($quarters as $quarter) {
//                        /** @var DOMElement $quarter */
//                        $summ = CashHandler::toRubles($quarter->getAttribute('summ'));
//
//                        if ($partial) {
//                            // если оплата частичная- сверю сумму с полной оплатой раздела.
//                            if ($payedSumm > 0) {
//                                if ($summ > $payedSumm) {
//                                    $payedSumm -= $summ;
//                                } else {
//                                    $summ = $payedSumm;
//                                    $payedSumm = 0;
//                                }
//                            } else {
//                                $summ = 0;
//                            }
//                        }
//
//                        $memList .= $quarter->getAttribute('date') . '(д) - ' . $summ . '<br>';
//                    }
//                }
//                // электричество
//                $powCounterValue = '--';
//                $powUsed = '--';
//                $powSumm = '--';
//                $power = $dom->query('/payment/power');
//                if ($power->length === 1) {
//                    /** @var DOMElement $powerItem */
//                    $powerItem = $power->item(0);
//                    if ($partial) {
//                        $payedSumm = $powerItem->getAttribute('payed');
//                        if ($payedSumm > 0) {
//                            $powSumm = CashHandler::toRubles($payedSumm);
//                        } else {
//                            $powSumm = 0;
//                        }
//                    } else {
//                        $powSumm = CashHandler::toRubles($powerItem->getAttribute('cost'));
//                    }
//                    $months = $dom->query('/payment/power/month');
//                    $powCounterValue = '';
//                    $powUsed = '';
//                    foreach ($months as $month) {
//                        /** @var DOMElement $month */
//                        $powCounterValue .= $month->getAttribute('date') . ': ' . $month->getAttribute('new-data') . '<br>';
//                        $powUsed .= $month->getAttribute('difference') . '<br>';
//                    }
//                }
//                $additional = $dom->query('/payment/additional_power');
//                if ($additional->length === 1) {
//                    /** @var DOMElement $powerItem */
//                    $powerItem = $additional->item(0);
//                    $summ = CashHandler::toRubles($powerItem->getAttribute('cost'));
//                    if ($powSumm === '--') {
//                        $powSumm = CashHandler::toRubles($summ);
//                    } else {
//                        $powSumm += $summ;
//                    }
//                    $months = $dom->query('/payment/additional_power/month');
//                    $powCounterValue = '';
//                    $powUsed = '';
//                    foreach ($months as $month) {
//                        /** @var DOMElement $month */
//                        $powCounterValue .= $month->getAttribute('date') . ': ' . $month->getAttribute('new-data') . '<br>';
//                        $powUsed .= $month->getAttribute('difference') . '<br>';
//                    }
//                }
//                // целевые взносы
//                $tarList = '--';
//                $tarSumm = '--';
//                $tar = $dom->query('/payment/target/pay');
//                if ($tar->length > 0) {
//                    // проверю, не оплачена ли часть платежа ранее
//                    $pay = $dom->query('/payment/target')->item(0);
//                    /** @var DOMElement $pay */
//                    $payedBefore = $pay->getAttribute('payed');
//                    if($payedBefore > 0){
//                        $payedBefore = CashHandler::toRubles($payedBefore);
//                    }
//                    /** @var DOMElement $targetItem */
//                    $targetItem = $tar->item(0);
//                    $payedSumm = 0;
//                    $tarList = '';
//                    if ($partial) {
//                        $payedSumm = $targetItem->parentNode->getAttribute('payed');
//                        if ($payedSumm > 0) {
//                            $payedSumm = CashHandler::toRubles($payedSumm);
//                            $tarSumm = $payedSumm;
//                        } else {
//                            $tarSumm = 0;
//                        }
//                    }
//                    else if($payedBefore > 0){
//                        // это завершающий платёж частичной оплаты
//                        $tarSumm = CashHandler::toRubles($targetItem->parentNode->getAttribute('cost')) - $payedBefore;
//                    }
//                    else {
//                        $tarSumm = CashHandler::toRubles($targetItem->parentNode->getAttribute('cost'));
//                    }
//                    foreach ($tar as $value) {
//                        /** @var DOMElement $value */
//                        $summ = CashHandler::toRubles($value->getAttribute('summ'));
//                        if ($partial) {
//                            // если оплата частичная- сверю сумму с полной оплатой раздела.
//                            if ($payedSumm > 0) {
//                                if ($payedSumm >= $summ) {
//                                    $payedSumm -= $summ;
//                                }
//                                else {
//                                    $summ = $payedSumm;
//                                    $payedSumm = 0;
//                                }
//                            } else {
//                                $summ = 0;
//                            }
//                        }
//                        elseif($payedBefore > 0){
//                            if($payedBefore >= $summ){
//                                $payedBefore -= $summ;
//                                continue;
//                            }
//                            else{
//                                $summ -= $payedBefore;
//                                $payedBefore = 0;
//                            }
//                        }
//                        $tarList .= $value->getAttribute('year') . ' - ' . $summ . '<br/>';
//                    }
//                }
//                $additionalTar = $dom->query('/payment/additional_target/pay');
//                if ($additionalTar->length > 0) {
//                    /** @var DOMElement $parent */
//                    $parent = $additionalTar->item(0)->parentNode;
//                    $summ = CashHandler::toRubles($parent->getAttribute('cost'));
//                    if ($tarSumm === '--') {
//                        $tarSumm = $summ;
//                    } else {
//                        $tarSumm += $summ;
//                    }
//                    foreach ($additionalTar as $value) {
//                        /** @var DOMElement $value */
//                        $tarList .= $value->getAttribute('year') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
//                    }
//                }
//                // разовые взносы
//                $singleList = '--';
//                $singleSumm = '--';
//                $singles = $dom->query('/payment/single/pay');
//                if ($singles->length > 0) {
//                    $singleList = '';
//                    $parent = $singles->item(0)->parentNode;
//                    $singleSumm = CashHandler::toRubles($parent->getAttribute('cost'));
//                    foreach ($singles as $value) {
//                        $singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
//                    }
//                }
//                $toDeposit = $item->gainedDeposit ?: 0;
//                $deposit = CashHandler::toRubles($toDeposit - $item->usedDeposit, true);
//
//
//                $content[] = "<tr><td>$date</td><td>$memList</td><td>$memSumm</td><td>$powCounterValue</td><td>$powUsed</td><td>$powSumm</td><td>$tarList</td><td>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>" . CashHandler::toRubles($item->discount) . "</td><td>{$deposit}</td></td><td>" . CashHandler::toRubles($item->transactionSumm) . "</td><td class='text-primary'>$type</td></tr>";
//            }
            $wholePower = 0;
            $wholeTarget = 0;
            $wholeMembership = 0;
            $wholeFines = 0;
            $wholeSumm = 0;
            $fullSumm = 0;
            $wholeDeposit = 0;
            foreach ($trs as $transaction) {
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
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            else{
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>'  . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $memSumm += $membership->summ;
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                        }
                        $memSumm = CashHandler::toRubles($memSumm);
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
                                    echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
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
                        $powSumm = CashHandler::toRubles($powSumm);
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
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            else{
                                $tarList .= '(Доп) ' . $target->year . ': <b>'  . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    }
                    else{
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if(!empty($singles)){
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);
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
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    }
                    else{
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if(!empty($discount)){
                        $discountSumm = CashHandler::toRubles($discount->summ);
                    }
                    else{
                        $discountSumm = '--';
                    }
                    if(!empty($fromDeposit)){
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    }
                    else{
                        $fromDepositSumm = 0;
                    }
                    if(!empty($toDeposit)){
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    }
                    else{
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}</td><td class='cottage-number-cell'>{$transaction->cottageNumber}</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$discountSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . "</td></tr>";
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
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            else{
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>'  . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                            $memSumm += $membership->summ;
                        }
                        $memSumm = CashHandler::toRubles($memSumm);
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
                        $powSumm = CashHandler::toRubles($powSumm);
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
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            else{
                                $tarList .= '(Доп) ' . $target->year . ': <b>'  . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    }
                    else{
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if(!empty($singles)){
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);
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
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    }
                    else{
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if(!empty($discount)){
                        $discountSumm = CashHandler::toRubles($discount->summ);
                    }
                    else{
                        $discountSumm = '--';
                    }
                    if(!empty($fromDeposit)){
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    }
                    else{
                        $fromDepositSumm = 0;
                    }
                    if(!empty($toDeposit)){
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    }
                    else{
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}-a</td><td class='cottage-number-cell'>{$transaction->cottageNumber}-a</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$discountSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . "</td></tr>";
                }
            }
            $content[] = "<tr><td class='date-cell'>Итого</td><td class='bill-id-cell'></td><td class='cottage-number-cell'></td><td class='quarter-cell'></td><td class='mem-summ-cell'>$wholeMembership</td><td class='pow-values'></td><td class='pow-total'></td><td class='pow-summ'>$wholePower</td><td class='target-by-years-cell'></td><td class='target-total'>$wholeTarget</td><td></td><td></td><td></td><td>$wholeFines</td><td></td><td>$wholeDeposit</td><td>$wholeSumm</td></tr>";
        }
        return ['content' => $content, 'cottageInfo' => $cottageInfo];
    }

	/**
	 * @param $cottageNumber
	 * @return string
	 * @throws ErrorException
	 */
    public static function powerDebtReport($cottageNumber): string
    {

            $content = "<table class='table table-hover table-striped'><thead><tr><th>Месяц</th><th>Данные</th><th>Потрачено</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead>
<tbody>";
            $info = PowerHandler::getDebtReport(Cottage::getCottageByLiteral($cottageNumber));
            foreach ($info as $item) {
                $inLimitPay = CashHandler::toShortSmoothRubles($item->powerData->inLimitPay);
                $overLimitPay = CashHandler::toShortSmoothRubles($item->powerData->overLimitPay);
                $totalPay = CashHandler::toShortSmoothRubles($item->powerData->totalPay);

                $date = TimeHandler::getFullFromShotMonth($item->powerData->month);
                $content .= "<tr><td>$date</td><td>{$item->powerData->newPowerData} кВт.ч</td><td>{$item->powerData->difference} кВт.ч</td><td>$inLimitPay</td><td>$overLimitPay</td><td>$totalPay</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
    }
    public static function power_additionalDebtReport($cottageNumber): string
    {

            $content = "<table class='table table-hover table-striped'><thead><tr><th>Месяц</th><th>Данные</th><th>Потрачено</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead>
<tbody>";
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            $info = PowerHandler::getDebtReport($cottageInfo);
        foreach ($info as $item) {
            $inLimitPay = CashHandler::toShortSmoothRubles($item->powerData->inLimitPay);
            $overLimitPay = CashHandler::toShortSmoothRubles($item->powerData->overLimitPay);
            $totalPay = CashHandler::toShortSmoothRubles($item->powerData->totalPay);

            $date = TimeHandler::getFullFromShotMonth($item->powerData->month);
            $content .= "<tr><td>$date</td><td>{$item->powerData->newPowerData} кВт.ч</td><td>{$item->powerData->difference} кВт.ч</td><td>$inLimitPay</td><td>$overLimitPay</td><td>$totalPay</td></tr>";
        }
            $content .= '</tbody></table>';
            return $content;
    }

    /**
     * @param $cottageNumber
     * @return bool|string
     */
    public static function membershipDebtReport($cottageNumber)
    {
        $cottageInfo = Table_cottages::findOne($cottageNumber);
        if (!empty($cottageInfo)) {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Квартал</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
            $info = MembershipHandler::getDebt($cottageInfo);
            foreach ($info as $item) {
                $fixed = CashHandler::toShortSmoothRubles($item->tariffFixed);
                $float = CashHandler::toShortSmoothRubles($item->tariffFloat);
                $floatSumm = CashHandler::toShortSmoothRubles($item->amount - ($item->tariffFixed));
                $totalSumm = CashHandler::toShortSmoothRubles($item->amount);
                $content .= "<tr><td>$item->quarter</td><td>{$cottageInfo->cottageSquare}</td><td>$fixed</td><td>$float</td><td>$fixed</td><td>$floatSumm</td><td>$totalSumm</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
        }
        return false;
    }
    public static function membership_additionalDebtReport($cottageNumber)
    {
        $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
        if (!empty($cottageInfo)) {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Квартал</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
            $info = MembershipHandler::getDebt($cottageInfo);
            foreach ($info as $key => $item) {
                $content .= "<tr><td>$key</td><td>{$cottageInfo->cottageSquare}</td><td>{$item['fixed']}  &#8381;</td><td>{$item['float']}  &#8381;</td><td>{$item['fixed']}  &#8381;</td><td>{$item['float_summ']}  &#8381;</td><td>{$item['total_summ']}  &#8381;</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
        }
        return false;
    }

    /**
     * @param $cottageNumber int|string
     * @return string
     */
    public static function targetDebtReport($cottageNumber): string
    {
        $cottageInfo = Table_cottages::findOne($cottageNumber);
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Год</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th><th>Уже оплачено</th></tr></thead><tbody>";
        if (!empty($cottageInfo)) {
            $years = TargetHandler::getDebt($cottageInfo);
            foreach ($years as $item) {
                $content .= "<tr><td>{$item->year}</td><td>{$cottageInfo->cottageSquare}</td><td>{$item->tariffFixed} &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->amount}&#8381;</td><td>{$item->partialPayed}&#8381;</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
        }
        throw new InvalidArgumentException('Неверный адрес участка');
    }
    public static function target_additionalDebtReport($cottageNumber): string
    {
        $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Год</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
        if (!empty($cottageInfo)) {
            $years = TargetHandler::getDebt($cottageInfo);
            foreach ($years as $key =>$year) {
                $content .= "<tr><td>{$key}</td><td>{$cottageInfo->cottageSquare}</td><td>{$year['fixed']} &#8381;</td><td>{$year['float']}  &#8381;</td><td>{$year['fixed']}  &#8381;</td><td>{$year['summ']['float']}  &#8381;</td><td>{$year['realSumm']}  &#8381;</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
        }
        throw new InvalidArgumentException('Неверный адрес участка');
    }

    /**
     * @param $cottageNumber int|string
     * @return string
     */
    public static function singleDebtReport($cottageNumber): string
    {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Дата</th><th>Цена</th><th>Цель</th></tr></thead><tbody>";
            $duty = SingleHandler::getDebtReport($cottageNumber);

            foreach ($duty as $value) {
                $date = TimeHandler::getDateFromTimestamp($value->time);
                $summ = $value->amount;
                $payed = $value->partialPayed;
                $description = urldecode($value->description);
                $realSumm = CashHandler::rublesMath($summ - $payed);
                $content .= "<tr class='single-item' data-id='{$value->time}'><td>$date</td><td>{$realSumm}  &#8381;</td><td>{$description}</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
    }
    public static function single_additionalDebtReport($cottageNumber): string
    {
            $content = "<table class='table table-hover table-striped'><thead><tr><th>Дата</th><th>Цена</th><th>Цель</th></tr></thead><tbody>";
            $duty = SingleHandler::getDebtReport($cottageNumber, true);
            foreach ($duty as $key => $value) {
                $date = TimeHandler::getDateFromTimestamp($key);
                $summ = $value['summ'];
                $payed = $value['payed'];
                $description = urldecode($value['description']);
                $realSumm = CashHandler::rublesMath($summ - $payed);
                $content .= "<tr class='single-item' data-id='$key'><td>$date</td><td>{$realSumm}  &#8381;</td><td>{$description}</td></tr>";

            }
            $content .= '</tbody></table>';
            return $content;
    }
}