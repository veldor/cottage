<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 8:08
 */

namespace app\models;

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
                $date = TimeHandler::getDateFromTimestamp($item->transactionDate);
                $type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadXML($item->bill_content);
                $xpath = new \DOMXPath($dom);
                // членские взносы
                $memList = '--';
                $memSumm = '--';
                $mem = $xpath->query('/payment/membership');
                if ($mem->length === 1) {
                    $memSumm = CashHandler::toRubles($mem->item(0)->getAttribute('cost'));
                    $quarters = $xpath->query('/payment/membership/quarter');
                    $memList = '';
                    foreach ($quarters as $quarter) {
	                    /** @var \DOMElement $quarter */
	                    $memList .= $quarter->getAttribute('date') . ' - ' . CashHandler::toRubles($quarter->getAttribute('summ')) . '<br>';
                    }
                }
                // электричество
                $powCounterValue = '--';
                $powUsed = '--';
                $powSumm = '--';
                $power = $xpath->query('/payment/power');
                if ($power->length === 1) {
                    $powSumm =  CashHandler::toRubles($power->item(0)->getAttribute('cost')) . '</b>';
                    $months = $xpath->query('/payment/power/month');
                    $powCounterValue = '';
                    $powUsed = '';
                    foreach ($months as $month) {
	                    /** @var \DOMElement $month */
	                    $powCounterValue .= $month->getAttribute('date') . ': '  . $month->getAttribute('new-data') . '<br>';
                        $powUsed .= $month->getAttribute('difference') . '<br>';
                    }
                }
                // целевые взносы
                $tarList = '--';
                $tarSumm = '--';
                $tar = $xpath->query('/payment/target/pay');
                if ($tar->length > 0) {
                    $tarList = '';
                    $tarSumm = CashHandler::toRubles($tar->item(0)->parentNode->getAttribute('cost'));
                    foreach ($tar as $value) {
	                    /** @var \DOMElement $value */
	                    $tarList .= $value->getAttribute('year') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
                    }
                }
                // разовые взносы
                $singleList = '--';
                $singleSumm = '--';
                $singles = $xpath->query('/payment/single/pay');
                if ($singles->length > 0) {
                    $singleList = '';
                    $singleSumm = CashHandler::toRubles($singles->item(0)->parentNode->getAttribute('cost'));
                    foreach ($tar as $value) {
                        $singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
                    }
                }
                $toDeposit = $item->toDeposit ?: 0;
                $deposit = CashHandler::toRubles($toDeposit - $item->depositUsed, true);
                $content[] = "<tr><td>$date</td><td>$memList</td><td>$memSumm</td><td>$powCounterValue</td><td>$powUsed</td><td>$powSumm</td><td>$tarList</td><td>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>{$item->discount}</td><td>{$deposit}</td></td><td>{$item->payedSumm}</td><td class='text-primary'>$type</td></tr>";
            }
        }
        return ['content' => $content, 'cottageInfo' => $cottageInfo];
    }

	/**
	 * @param $cottageNumber
	 * @return string
	 * @throws \yii\base\ErrorException
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