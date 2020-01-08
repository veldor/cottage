<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:08
 */

namespace app\models;

use app\models\selections\MembershipDebt;
use app\models\selections\TargetDebt;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
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
        $singleDescriptions = [];
        $singleCounters = 1;
        // найду все транзакции данного участка
        $trs = Table_transactions::find()->where(['cottageNumber' => $cottageNumber])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
        if (!empty($trs)) {
            $wholePower = 0;
            $wholeTarget = 0;
            $wholeMembership = 0;
            $wholeSingle = 0;
            $wholeFines = 0;
            $wholeSumm = 0;
            $fullSumm = 0;
            $wholeDeposit = 0;
            foreach ($trs as $transaction) {
                $wholeSumm += CashHandler::toRubles($transaction->transactionSumm);
                $fullSumm += CashHandler::toRubles($transaction->transactionSumm);
                if ($transaction instanceof Table_transactions) {
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
                    if (!empty($memberships)) {
                        $memSumm = 0;
                        $memList = '';
                        foreach ($memberships as $membership) {
                            if ($membership instanceof Table_payed_membership) {
                                $memList .= $membership->quarter . ': <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            } else {
                                $memList .= '(Доп) ' . $membership->quarter . ':  <b>' . CashHandler::toRubles($membership->summ) . '</b><br/>';
                            }
                            $memSumm += $membership->summ;
                            $wholeMembership += CashHandler::toRubles($membership->summ);
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
                                if (empty($powData)) {
                                    echo 'p' . $transaction->id . ' ' . ' ' . $transaction->cottageNumber . ' ' . $power->month;
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
                        }
                        $powSumm = CashHandler::toRubles($powSumm);
                        $wholePower += CashHandler::toRubles($power->summ);
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
                            $finesList .= $fineInfo->period . ': <b>' . CashHandler::toRubles($fine->summ) . '</b><br/>';
                            $finesSumm += $fine->summ;
                            $wholeFines += CashHandler::toRubles($fine->summ);
                        }
                        $finesSumm = CashHandler::toRubles($finesSumm);
                    } else {
                        $finesSumm = '--';
                        $finesList = '--';
                    }
                    if (!empty($discount)) {
                        $discountSumm = CashHandler::toRubles($discount->summ);
                    } else {
                        $discountSumm = '--';
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
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}</td><td class='quarter-cell'>" .substr($memList, 0, strlen($memList) - 6) . "</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>" . substr($powCounterValue, 0, strlen($powCounterValue) - 6) . "</td><td class='pow-total'>" . substr($powUsed, 0, strlen($powUsed) - 6) . "</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>" . substr($tarList, 0, strlen($tarList) - 6) ."</td><td class='target-total'>$tarSumm</td><td>" . substr($singleList, 0, strlen($singleList) - 6) . "</td><td>$singleSumm</td><td>" . substr($finesList, 0, strlen($finesList) - 6) . "</td><td>$finesSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . "</td></tr>";
                } else {
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
                                if (empty($powData)) {
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
                            var_dump($name);
                            die;
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
                    if (!empty($discount)) {
                        $discountSumm = CashHandler::toRubles($discount->summ);
                    } else {
                        $discountSumm = '--';
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
                    $content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$transaction->billId}-a</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>$finesList</td><td>$finesSumm</td><td>$totalDeposit</td><td>" . CashHandler::toRubles($transaction->transactionSumm) . "</td></tr>";
                }
            }
            $content[] = "<tr><td class='date-cell'>Итого</td><td class='bill-id-cell'></td><td class='quarter-cell'></td><td class='mem-summ-cell'>$wholeMembership</td><td class='pow-values'></td><td class='pow-total'></td><td class='pow-summ'>$wholePower</td><td class='target-by-years-cell'></td><td class='target-total'>$wholeTarget</td><td></td><td>$wholeSingle</td><td></td><td>$wholeFines</td><td>$wholeDeposit</td><td>$wholeSumm</td></tr>";
        }
        return ['content' => $content, 'cottageInfo' => $cottageInfo, 'singleDescriptions' => $singleDescriptions];
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
            /** @var MembershipDebt[] $info */
            $info = MembershipHandler::getDebt($cottageInfo);
            foreach ($info as $key => $item) {
                $content .= "<tr><td>{$item->quarter}</td><td>{$cottageInfo->cottageSquare}</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->tariffFixed}  &#8381;</td><td>{$item->tariffFloat}  &#8381;</td><td>{$item->amount}  &#8381;</td></tr>";
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

    /**
     * @param $cottageNumber
     * @return string
     */
    public static function target_additionalDebtReport($cottageNumber): string
    {
        $cottageInfo = Table_additional_cottages::findOne($cottageNumber);
        $content = "<table class='table table-hover table-striped'><thead><tr><th>Год</th><th>Площадь</th><th>С участка</th><th>С сотки</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead><tbody>";
        if (!empty($cottageInfo)) {
            /** @var TargetDebt[] $years */
            $years = TargetHandler::getDebt($cottageInfo);
            foreach ($years as $key => $year) {
                $content .= "<tr><td>{$key}</td><td>{$cottageInfo->cottageSquare}</td><td>{$year->tariffFixed} &#8381;</td><td>{$year->tariffFloat}  &#8381;</td><td>{$year->tariffFixed}  &#8381;</td><td>{$year->tariffFloat}  &#8381;</td><td>{$year->amount}  &#8381;</td></tr>";

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