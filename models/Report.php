<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:08
 */

namespace app\models;

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
        $trs = Table_fulltransactioninfo::find()->where(['cottage_number' => $cottageNumber])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
        if (!empty($trs)) {
            foreach ($trs as $item) {
                /** @var Table_fulltransactioninfo $item */
                // членские взносы
                $memList = '--';
                $memSumm = '--';
                $payedSumm = 0;
                $partial = $item->partial;
                $date = TimeHandler::getDateFromTimestamp($item->transactionDate);
                $type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
                if(!empty($item->billCast)){
                    $dom = new DOMHandler($item->billCast);
                }
                else{
                    $dom = new DOMHandler($item->bill_content);
                }
                $mem = $dom->query('/payment/membership');
                if ($mem->length === 1) {
                    /** @var DOMElement $memItem */
                    $memItem = $mem->item(0);
                    if ($partial) {
                        $payedSumm = $memItem->getAttribute('payed');
                        if(!empty($payedSumm)){
                            $payedSumm = CashHandler::toRubles($payedSumm);
                        }
                        else{
                            $payedSumm = 0;
                        }
                        if ($payedSumm > 0) {
                            $memSumm = $payedSumm;
                        } else {
                            $memSumm = 0;
                        }
                    } else {
                        $memSumm = CashHandler::toRubles($memItem->getAttribute('cost'));
                    }
                    $quarters = $dom->query('/payment/membership/quarter');
                    if ($memList === '--') {
                        $memList = '';
                    }
                    foreach ($quarters as $quarter) {
                        /** @var DOMElement $quarter */
                        $summ = CashHandler::toRubles($quarter->getAttribute('summ'));
                        if ($partial) {
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if ($payedSumm > 0) {
                                if ($summ > $payedSumm) {
                                    $payedSumm -= CashHandler::toRubles($summ);
                                } else {
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            } else {
                                $summ = 0;
                            }
                        }
                        $memList .= $quarter->getAttribute('date') . ' - ' . $summ . '<br>';
                    }
                }
                $memAdd = $dom->query('/payment/additional_membership');
                if ($memAdd->length === 1) {
                    $payedSumm = 0;
                    $additionalSumm = 0;
                    $memItem = $memAdd->item(0);
                    if ($partial) {
                        $payedSumm = $memItem->getAttribute('payed');
                        if ($payedSumm > 0) {
                            $additionalSumm = $payedSumm;
                        } else {
                            $additionalSumm = 0;
                        }
                    }

                    if ($memSumm === '--') {
                        $memSumm = $additionalSumm;
                    } else {
                        $memSumm += $additionalSumm;
                    }
                    $quarters = $dom->query('/payment/additional_membership/quarter');
                    if ($memList === '--') {
                        $memList = '';
                    }
                    foreach ($quarters as $quarter) {
                        /** @var DOMElement $quarter */
                        $summ = CashHandler::toRubles($quarter->getAttribute('summ'));

                        if ($partial) {
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if ($payedSumm > 0) {
                                if ($summ > $payedSumm) {
                                    $payedSumm -= $summ;
                                } else {
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            } else {
                                $summ = 0;
                            }
                        }

                        $memList .= $quarter->getAttribute('date') . '(д) - ' . $summ . '<br>';
                    }
                }
                // электричество
                $powCounterValue = '--';
                $powUsed = '--';
                $powSumm = '--';
                $power = $dom->query('/payment/power');
                if ($power->length === 1) {
                    /** @var DOMElement $powerItem */
                    $powerItem = $power->item(0);
                    if ($partial) {
                        $payedSumm = $powerItem->getAttribute('payed');
                        if ($payedSumm > 0) {
                            $powSumm = CashHandler::toRubles($payedSumm);
                        } else {
                            $powSumm = 0;
                        }
                    } else {
                        $powSumm = CashHandler::toRubles($powerItem->getAttribute('cost'));
                    }
                    $months = $dom->query('/payment/power/month');
                    $powCounterValue = '';
                    $powUsed = '';
                    foreach ($months as $month) {
                        /** @var DOMElement $month */
                        $powCounterValue .= $month->getAttribute('date') . ': ' . $month->getAttribute('new-data') . '<br>';
                        $powUsed .= $month->getAttribute('difference') . '<br>';
                    }
                }
                $additional = $dom->query('/payment/additional_power');
                if ($additional->length === 1) {
                    /** @var DOMElement $powerItem */
                    $powerItem = $additional->item(0);
                    $summ = CashHandler::toRubles($powerItem->getAttribute('cost'));
                    if ($powSumm === '--') {
                        $powSumm = CashHandler::toRubles($summ);
                    } else {
                        $powSumm += $summ;
                    }
                    $months = $dom->query('/payment/additional_power/month');
                    $powCounterValue = '';
                    $powUsed = '';
                    foreach ($months as $month) {
                        /** @var DOMElement $month */
                        $powCounterValue .= $month->getAttribute('date') . ': ' . $month->getAttribute('new-data') . '<br>';
                        $powUsed .= $month->getAttribute('difference') . '<br>';
                    }
                }
                // целевые взносы
                $tarList = '--';
                $tarSumm = '--';
                $tar = $dom->query('/payment/target/pay');
                if ($tar->length > 0) {
                    // проверю, не оплачена ли часть платежа ранее
                    $pay = $dom->query('/payment/target')->item(0);
                    /** @var DOMElement $pay */
                    $payedBefore = $pay->getAttribute('payed');
                    if($payedBefore > 0){
                        $payedBefore = CashHandler::toRubles($payedBefore);
                    }
                    /** @var DOMElement $targetItem */
                    $targetItem = $tar->item(0);
                    $payedSumm = 0;
                    $tarList = '';
                    if ($partial) {
                        $payedSumm = $targetItem->parentNode->getAttribute('payed');
                        if ($payedSumm > 0) {
                            $payedSumm = CashHandler::toRubles($payedSumm);
                            $tarSumm = $payedSumm;
                        } else {
                            $tarSumm = 0;
                        }
                    }
                    else if($payedBefore > 0){
                        // это завершающий платёж частичной оплаты
                        $tarSumm = CashHandler::toRubles($targetItem->parentNode->getAttribute('cost')) - $payedBefore;
                    }
                    else {
                        $tarSumm = CashHandler::toRubles($targetItem->parentNode->getAttribute('cost'));
                    }
                    foreach ($tar as $value) {
                        /** @var DOMElement $value */
                        $summ = CashHandler::toRubles($value->getAttribute('summ'));
                        if ($partial) {
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if ($payedSumm > 0) {
                                if ($payedSumm >= $summ) {
                                    $payedSumm -= $summ;
                                }
                                else {
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            } else {
                                $summ = 0;
                            }
                        }
                        elseif($payedBefore > 0){
                            if($payedBefore >= $summ){
                                $payedBefore -= $summ;
                                continue;
                            }
                            else{
                                $summ -= $payedBefore;
                                $payedBefore = 0;
                            }
                        }
                        $tarList .= $value->getAttribute('year') . ' - ' . $summ . '<br/>';
                    }
                }
                $additionalTar = $dom->query('/payment/additional_target/pay');
                if ($additionalTar->length > 0) {
                    /** @var DOMElement $parent */
                    $parent = $additionalTar->item(0)->parentNode;
                    $summ = CashHandler::toRubles($parent->getAttribute('cost'));
                    if ($tarSumm === '--') {
                        $tarSumm = $summ;
                    } else {
                        $tarSumm += $summ;
                    }
                    foreach ($additionalTar as $value) {
                        /** @var DOMElement $value */
                        $tarList .= $value->getAttribute('year') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
                    }
                }
                // разовые взносы
                $singleList = '--';
                $singleSumm = '--';
                $singles = $dom->query('/payment/single/pay');
                if ($singles->length > 0) {
                    $singleList = '';
                    $parent = $singles->item(0)->parentNode;
                    $singleSumm = CashHandler::toRubles($parent->getAttribute('cost'));
                    foreach ($singles as $value) {
                        $singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
                    }
                }
                $toDeposit = $item->gainedDeposit ?: 0;
                $deposit = CashHandler::toRubles($toDeposit - $item->usedDeposit, true);


                $content[] = "<tr><td>$date</td><td>$memList</td><td>$memSumm</td><td>$powCounterValue</td><td>$powUsed</td><td>$powSumm</td><td>$tarList</td><td>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>{$item->discount}</td><td>{$deposit}</td></td><td>{$item->transactionSumm}</td><td class='text-primary'>$type</td></tr>";
            }
//            foreach ($trs as $item) {
//                $date = TimeHandler::getDateFromTimestamp($item->transactionDate);
//                $type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
//                if(!empty($item->billCast)){
//                    $dom = new DOMHandler($item->billCast);
//                }
//                else{
//                    $dom = new DOMHandler($item->bill_content);
//                }
//                // членские взносы
//                $memList = '--';
//                $memSumm = '--';
//                $mem = $dom->query('/payment/membership');
//                if ($mem->length === 1) {
//                    $memSumm = CashHandler::toRubles($mem->item(0)->getAttribute('cost'));
//                    $quarters = $dom->query('/payment/membership/quarter');
//                    $memList = '';
//                    foreach ($quarters as $quarter) {
//	                    /** @var \DOMElement $quarter */
//	                    $memList .= $quarter->getAttribute('date') . ' - ' . CashHandler::toRubles($quarter->getAttribute('summ')) . '<br>';
//                    }
//                }
//                // электричество
//                $powCounterValue = '--';
//                $powUsed = '--';
//                $powSumm = '--';
//                $power = $dom->query('/payment/power');
//                if ($power->length === 1) {
//                    $powSumm =  CashHandler::toRubles($power->item(0)->getAttribute('cost')) . '</b>';
//                    $months = $dom->query('/payment/power/month');
//                    $powCounterValue = '';
//                    $powUsed = '';
//                    foreach ($months as $month) {
//	                    /** @var \DOMElement $month */
//	                    $powCounterValue .= $month->getAttribute('date') . ': '  . $month->getAttribute('new-data') . '<br>';
//                        $powUsed .= $month->getAttribute('difference') . '<br>';
//                    }
//                }
//                // целевые взносы
//                $tarList = '--';
//                $tarSumm = '--';
//                $tar = $dom->query('/payment/target/pay');
//                if ($tar->length > 0) {
//                    $tarList = '';
//                    $tarSumm = CashHandler::toRubles($tar->item(0)->parentNode->getAttribute('cost'));
//                    foreach ($tar as $value) {
//	                    /** @var \DOMElement $value */
//	                    $tarList .= $value->getAttribute('year') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
//                    }
//                }
//                // разовые взносы
//                $singleList = '--';
//                $singleSumm = '--';
//                $singles = $dom->query('/payment/single/pay');
//                if ($singles->length > 0) {
//                    $singleList = '';
//                    $singleSumm = CashHandler::toRubles($singles->item(0)->parentNode->getAttribute('cost'));
//                    foreach ($tar as $value) {
//                        $singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
//                    }
//                }
//                $toDeposit = $item->toDeposit ?: 0;
//                $deposit = CashHandler::toRubles($toDeposit - $item->depositUsed, true);
//                $content[] = "<tr><td>$date</td><td>$memList</td><td>$memSumm</td><td>$powCounterValue</td><td>$powUsed</td><td>$powSumm</td><td>$tarList</td><td>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>{$item->discount}</td><td>{$deposit}</td></td><td>{$item->payedSumm}</td><td class='text-primary'>$type</td></tr>";
//            }
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
            $info = PowerHandler::getDebtReport($cottageNumber);
            foreach ($info as $item) {


                $inLimitPay = CashHandler::toShortSmoothRubles($item['inLimitPay']);
                $overLimitPay = CashHandler::toShortSmoothRubles($item['overLimitPay']);
                $totalPay = CashHandler::toShortSmoothRubles($item['totalPay']);

                $date = TimeHandler::getFullFromShotMonth($item['month']);
                $content .= "<tr><td>$date</td><td>{$item['newPowerData']} кВт.ч</td><td>{$item['difference']} кВт.ч</td><td>$inLimitPay</td><td>$overLimitPay</td><td>$totalPay</td></tr>";
            }
            $content .= '</tbody></table>';
            return $content;
    }
    public static function power_additionalDebtReport($cottageNumber): string
    {

            $content = "<table class='table table-hover table-striped'><thead><tr><th>Месяц</th><th>Данные</th><th>Потрачено</th><th>Цена 1</th><th>Цена 2</th><th>Всего</th></tr></thead>
<tbody>";
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            $info = PowerHandler::getDebtReport($cottageInfo, true);
            foreach ($info as $item) {
	            $date = TimeHandler::getFullFromShotMonth($item['month']);
	            $content .= "<tr><td>$date</td><td>{$item['newPowerData']} кВт.ч</td><td>{$item['difference']} кВт.ч</td><td>{$item['inLimitPay']} &#8381;</td><td>{$item['overLimitPay']} &#8381;</td><td>{$item['totalPay']} &#8381;</td></tr>";
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
            foreach ($info as $key => $item) {
                $fixed = CashHandler::toShortSmoothRubles($item['fixed']);
                $float = CashHandler::toShortSmoothRubles($item['float']);
                $floatSumm = CashHandler::toShortSmoothRubles($item['float_summ']);
                $totalSumm = CashHandler::toShortSmoothRubles($item['total_summ']);
                $content .= "<tr><td>$key</td><td>{$cottageInfo->cottageSquare}</td><td>$fixed</td><td>$float</td><td>$fixed</td><td>$floatSumm</td><td>$totalSumm</td></tr>";
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