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
        $singleDescriptions = [];
        $singleCounters = 1;
        // найду все транзакции данного участка за выбранный период
        $trs = Table_transactions::find()->where(['cottageNumber' => $cottageNumber])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
        if (!empty($trs)) {
            // отчёты
            $wholePower = 0;
            $wholeTarget = 0;
            $wholeMembership = 0;
            $wholeSingle = 0;
            $wholeFines = 0;
            $wholeSumm = 0;
            $wholeDeposit = 0;
            foreach ($trs as $transaction) {
                // вычислю полную сумму заплаченного
                $wholeSumm += CashHandler::toRubles($transaction->transactionSumm);
                if ($transaction instanceof Table_transactions) {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = array_merge(Table_payed_power::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all());
                    $memberships = array_merge(Table_payed_membership::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all());
                    $targets = array_merge(Table_payed_target::find()->where(['transactionId' => $transaction->id])->all(), Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all());
                    $singles = Table_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out'])->one();
                    if (!empty($memberships)) {
                        // если были оплаты по членским платежам
                        // полная сумма оплат
                        $memSumm = 0;
                        // список оплаченных месяцев
                        $memList = '';
                        foreach ($memberships as $membership) {
                            // если оплачен счёт по основному участку
                            if ($membership instanceof Table_payed_membership) {
                                // занесу в список сумму оплаты по участку
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                // занесу в список сумму оплаты по дополнительному участку
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            // добавлю сумму оплаты к общей стоимости оплаты за членские взносы в платеже
                            $memSumm += $membership->summ;
                            // добавлю сумму оплаты к общей стоимости оплаты за членские взносы в целом
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                        }
                        // отформатирую сумму оплаты
                        $memSumm = CashHandler::toRubles($memSumm);
                    } else {
                        // иначе отмечу, что ничего не оплачено
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if (!empty($powers)) {
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        foreach ($powers as $power) {
                            if ($power instanceof Table_payed_power) {
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if ($powData === null) {
                                    // если не найден период - выдам ошибку
                                    echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                            }
                            $powSumm += $power->summ;
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
                        $powSumm = CashHandler::toRubles($powSumm);
                    } else {
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if (!empty($targets)) {
                        $tarSumm = 0;
                        $tarList = '';
                        foreach ($targets as $target) {
                            if ($target instanceof Table_payed_target) {
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            } else {
                                $tarList .= '(Доп) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    } else {
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if (!empty($singles)) {
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleSumm += $single->summ;
                            $wholeSingle += $singleSumm;
                            // получу назначение платежа
                            $billInfo = Table_payment_bills::findOne(['id' => $transaction->billId]);
                            if($billInfo === null){
                                echo "счёт {$transaction->billId} - не найден";
                                die;
                            }
                            $xml = new DOMHandler($billInfo->bill_content);
                            $name = $xml->query("//pay[@timestamp='" . $single->time . "']");
                            $attrs = DOMHandler::getElemAttributes($name->item(0));
                            $description = urldecode($attrs['description']);
                            $singleList .= "($singleCounters)* " . CashHandler::toRubles($single->summ) . '<br/>';
                            $singleCounters++;
                            $singleDescriptions[] = $description;

                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);

                    } else {
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if (!empty($fines)) {
                        $finesSumm = 0;
                        $finesList = '';
                        foreach ($fines as $fine) {
                            // найду информацию о пени
                            $fineInfo = Table_penalties::findOne($fine->fine_id);
                            if($fineInfo === null){
                                echo " пени {$fine->fine_id} - не найдены";
                                die;
                            }
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    } else {
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if (!empty($fromDeposit)) {
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    } else {
                        $fromDepositSumm = 0;
                    }
                    if (!empty($toDeposit)) {
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    } else {
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);

                    $content[] = "
                        <tr>
                            <td class='date-cell'>$date</td>
                            <td class='bill-id-cell'>{$transaction->billId}</td>
                            <td class='quarter-cell'>$memList</td>
                            <td class='mem-summ-cell'>$memSumm</td>
                            <td class='pow-values'>" . $powCounterValue . "</td>
                            <td class='pow-total'>" . $powUsed . "</td>
                            <td class='pow-summ'>$powSumm</td>
                            <td class='target-by-years-cell'>$tarList</td>
                            <td class='target-total'>$tarSumm</td>
                            <td>$singleList</td>
                            <td>$singleSumm</td>
                            <td>$finesList</td>
                            <td>$finesSumm</td>
                            <td>$totalDeposit</td>
                            <td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td>
                        </tr>';
                }
                else {
                    $date = TimeHandler::getDateFromTimestamp($transaction->bankDate);
                    // получу оплаченные сущности
                    $powers = Table_additional_payed_power::find()->where(['transactionId' => $transaction->id])->all();
                    $memberships = Table_additional_payed_membership::find()->where(['transactionId' => $transaction->id])->all();
                    $targets = Table_additional_payed_target::find()->where(['transactionId' => $transaction->id])->all();
                    $singles = Table_additional_payed_single::find()->where(['transactionId' => $transaction->id])->all();
                    $fines = Table_payed_fines::find()->where(['transaction_id' => $transaction->id])->all();
                    $toDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'in'])->one();
                    $fromDeposit = Table_deposit_io::find()->where(['transactionId' => $transaction->id, 'destination' => 'out'])->one();
                    if (!empty($memberships)) {
                        $memSumm = 0;
                        $memList = '';
                        foreach ($memberships as $membership) {
                            if ($membership instanceof Table_payed_membership) {
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $wholeMembership += CashHandler::toRubles($membership->summ);
                            $memSumm += $membership->summ;
                        }
                        $memSumm = CashHandler::toRubles($memSumm);
                    } else {
                        $memList = '--';
                        $memSumm = '--';
                    }
                    if (!empty($powers)) {
                        $powCounterValue = '';
                        $powUsed = '';
                        $powSumm = 0;
                        foreach ($powers as $power) {
                            if ($power instanceof Table_payed_power) {
                                // найду данные о показаниях
                                $powData = Table_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                if ($powData === null) {
                                    echo $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
                                    die;
                                }
                                $powCounterValue .= $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            } else {
                                // найду данные о показаниях
                                $powData = Table_additional_power_months::findOne(['cottageNumber' => $transaction->cottageNumber, 'month' => $power->month]);
                                $powCounterValue .= '(Доп) ' . $power->month . ': ' . $powData->newPowerData . '<br/>';
                                $powUsed .= $powData->difference . '<br/>';
                                $powSumm += $power->summ;
                            }
                            $wholePower += CashHandler::toRubles($power->summ);
                        }
                        $powSumm = CashHandler::toRubles($powSumm);
                    } else {
                        $powCounterValue = '--';
                        $powUsed = '--';
                        $powSumm = '--';
                    }
                    if (!empty($targets)) {
                        $tarSumm = 0;
                        $tarList = '';
                        foreach ($targets as $target) {
                            if ($target instanceof Table_payed_target) {
                                $tarList .= $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            } else {
                                $tarList .= '(Доп) ' . $target->year . ': <b>' . CashHandler::toRubles($target->summ) . '</b><br/>';
                            }
                            $tarSumm += $target->summ;
                            $wholeTarget += CashHandler::toRubles($target->summ);
                        }
                        $tarSumm = CashHandler::toRubles($tarSumm);
                    } else {
                        $tarList = '--';
                        $tarSumm = '--';
                    }
                    if (!empty($singles)) {
                        $singleSumm = 0;
                        $singleList = '';
                        foreach ($singles as $single) {
                            $singleList .= CashHandler::toRubles($single->summ) . '<br/>';
                            $singleSumm += $single->summ;
                            $wholeSingle += $singleSumm;
                            // получу назначение платежа
                            $billInfo = Table_payment_bills::findOne(['id' => $transaction->billId]);
                            $xml = new DOMHandler($billInfo->bill_content);
                            $name = $xml->query("/pay[@timestamp='" . $single->time . "']");
                            $attrs = DOMHandler::getElemAttributes($name->item(0));
                            $description = urldecode($attrs['description']);
                            $singleList .= "($singleCounters)* " . CashHandler::toRubles($single->summ) . '<br/>';
                            $singleCounters++;
                            $singleDescriptions[] = $description;
                        }
                        $singleSumm = CashHandler::toRubles($singleSumm);
                    } else {
                        $singleSumm = '--';
                        $singleList = '--';
                    }
                    if (!empty($fines)) {
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
                    } else {
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if (!empty($fromDeposit)) {
                        $fromDepositSumm = CashHandler::toRubles($fromDeposit->summ);
                        $wholeDeposit -= CashHandler::toRubles($fromDeposit->summ, true);
                    } else {
                        $fromDepositSumm = 0;
                    }
                    if (!empty($toDeposit)) {
                        $toDepositSumm = CashHandler::toRubles($toDeposit->summ);
                        $wholeDeposit += CashHandler::toRubles($toDeposit->summ, true);
                    } else {
                        $toDepositSumm = 0;
                    }
                    $totalDeposit = CashHandler::toRubles($toDepositSumm - $fromDepositSumm, true);
                    $content[] = "
                                    <tr>
                                        <td class='date-cell'>$date</td>
                                        <td class='bill-id-cell'>{$transaction->billId}-a</td>
                                        <td class='quarter-cell'>$memList</td>
                                        <td class='mem-summ-cell'>$memSumm</td>
                                        <td class='pow-values'>$powCounterValue</td>
                                        <td class='pow-total'>$powUsed</td>
                                        <td class='pow-summ'>$powSumm</td>
                                        <td class='target-by-years-cell'>$tarList</td>
                                        <td class='target-total'>$tarSumm</td>
                                        <td>$singleList</td>
                                        <td>$singleSumm</td>
                                        <td>$finesList</td>
                                        <td>$finesSumm</td>
                                        <td>$totalDeposit</td>
                                        <td>" . CashHandler::toRubles($transaction->transactionSumm) . '</td>
                                    </tr>';
                }
            }
            $content[] = "
                            <tr>
                                <td class='date-cell'>Итого</td>
                                <td class='bill-id-cell'></td>
                                <td class='quarter-cell'></td>
                                <td class='mem-summ-cell'>$wholeMembership</td>
                                <td class='pow-values'></td>
                                <td class='pow-total'></td>
                                <td class='pow-summ'>$wholePower</td>
                                <td class='target-by-years-cell'></td>
                                <td class='target-total'>$wholeTarget</td>
                                <td></td>
                                <td>$wholeSingle</td>
                                <td></td>
                                <td>$wholeFines</td>
                                <td>$wholeDeposit</td>
                                <td>$wholeSumm</td>
                            </tr>";
        }
        return ['content' => $content, 'cottageInfo' => $cottageInfo, 'singleDescriptions' => $singleDescriptions];
    }
}