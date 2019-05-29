<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

namespace app\models;


use DateTime;
use DOMElement;
use InvalidArgumentException;
use yii\base\Model;

class Search extends Model
{
    public $startDate;
    public $finishDate;
    public $searchType;
    public $searchTypeList = ['routine' => 'Обычный', 'summary' => 'Суммарный', 'report' => 'Отчёт'];

    const SCENARIO_BILLS_SEARCH = 'bills-search';

    public function scenarios():array
    {
        return [
            self::SCENARIO_BILLS_SEARCH => ['startDate', 'finishDate', 'searchType'],
        ];
    }

    public function rules():array
    {
        return [
            [['startDate', 'finishDate', 'searchType'], 'required'],
            [['startDate', 'finishDate'], 'date', 'format' => 'y-M-d'],
	        ['searchType', 'in', 'range' => ['routine', 'summary', 'report']]
        ];
    }

    public function attributeLabels():array
    {
        return [
            'startDate' => 'Начало периода',
            'finishDate' => 'Конец периода',
            'searchType' => 'Тип отчёта',
        ];
    }


    public function doSearch(): array
    {
        $start = new DateTime('0:0:00'. $this->startDate);
        $finish = new DateTime('23:59:50'. $this->finishDate);
        $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];
        switch ($this->searchType){
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
        $totalSumm = 0;
        $content = "<table class='table table-striped'><thead><th>Дата платежа</th><th>№</th><th>Сумма</th><th>Тип</th><th>Вид</th></thead><tbody>";
        $results = Table_transactions::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
        if (!empty($results)) {
            foreach ($results as $result) {
                $totalSumm += $result->transactionSumm;
                $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                if ($result->transactionWay === 'in') {
	                $way = "<b class='text-success'>Поступление</b>";
                }
                else {
	                $way = "<b class='text-danger'>Списание</b>";
                }
                if ($result->transactionType === 'cash') {
	                $type = "<b class='text-success'>Наличные</b>";
                }
                else {
	                $type = "<b class='text-primary'>Безналичный расчёт</b>";
                }
                $summ = CashHandler::toShortSmoothRubles($result->transactionSumm);
                $content .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='{$result->billId}'>{$result->billId}</a></td><td><b class='text-info'>{$summ}</b></td><td>$way</td><td>$type</td></tr>";
            }
        }

        $doubleResults = Table_transactions_double::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->andWhere(['transactionType' => 'cash'])->all();
        if (!empty($doubleResults)) {
            foreach ($doubleResults as $result) {
                $totalSumm += $result->transactionSumm;
                $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                if ($result->transactionWay === 'in') {
                    $way = "<b class='text-success'>Поступление</b>";
                }
                else {
                    $way = "<b class='text-danger'>Списание</b>";
                }
                if ($result->transactionType === 'cash') {
                    $type = "<b class='text-success'>Наличные</b>";
                }
                else {
                    $type = "<b class='text-primary'>Безналичный расчёт</b>";
                }
                $summ = CashHandler::toShortSmoothRubles($result->transactionSumm);
                $content .= "<tr><td>$date</td><td><a href='#' class='bill-info' data-bill-id='{$result->billId}' data-double='yes'>{$result->billId}-a</a></td><td><b class='text-info'>{$summ}</b></td><td>$way</td><td>$type</td></tr>";
            }
        }
        if(empty($result) && empty($doubleResults)){
            $content .= '</tbody></table>';
            $content .= '<h3>Транзакций за данный период не найдено</h3>';
        }
        else{
            $content .= '</tbody></table>';
        }
        return ['status' => 1, 'data' => $content, 'totalSumm' => $totalSumm];
    }

    private function getSummary($interval): array
    {
        $totalPowerSumm = 0;
        $totalMemSumm = 0;
        $totalTargetSumm = 0;
        $totalSingleSumm = 0;
        $content = "<table class='table table-striped'><thead><th>Электроэнергия</th><th>Членские</th><th>Целевые</th><th>Разовые</th><th>С депозита</th><th>На депозит</th><th>Скидка</th></thead><tbody>";
        $powers = Table_payed_power::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($powers)) {
            foreach ($powers as $power) {
                $totalPowerSumm += $power->summ;
            }
        }
        $additionalPowers = Table_additional_payed_power::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($additionalPowers)) {
            foreach ($additionalPowers as $power) {
                $totalPowerSumm += $power->summ;
            }
        }
        $mems = Table_payed_membership::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($mems)) {
            foreach ($mems as $mem) {
                $totalMemSumm += $mem->summ;
            }
        }
        $additionalMems = Table_additional_payed_membership::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($additionalMems)) {
            foreach ($additionalMems as $mem) {
                $totalMemSumm += $mem->summ;
            }
        }
        $targets = Table_payed_target::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($targets)) {
            foreach ($targets as $target) {
                $totalTargetSumm += $target->summ;
            }
        }
        $additionalTargets = Table_additional_payed_target::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($additionalTargets)) {
            foreach ($additionalTargets as $target) {
                $totalTargetSumm += $target->summ;
            }
        }
        $singls = Table_payed_single::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($singls)) {
            foreach ($singls as $single) {
                $totalSingleSumm += $single->summ;
            }
        }
        $additonalSingls = Table_additional_payed_single::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['finish']])->all();
        if (!empty($additonalSingls)) {
            foreach ($additonalSingls as $single) {
                $totalSingleSumm += $single->summ;
            }
        }

        $fromDeposit = 0;
        $toDeposit = 0;
        $discount = 0;

        // рассчитаю движения по депозиту и скидкам за этот период
        $results = Table_fulltransactioninfo::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
        $additionalResults = Table_fulladditionaltransactioninfo::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
        if(!empty($results)){
            /** @var Table_fulltransactioninfo $result */
            foreach ($results as $result) {
                $fromDeposit += $result->depositUsed;
                $toDeposit += $result->toDeposit;
                $discount += $result->discount;
            }
        }if(!empty($additionalResults)){
            /** @var Table_fulladditionaltransactioninfo $result */
            foreach ($additionalResults as $result) {
                $fromDeposit += $result->depositUsed;
                $toDeposit += $result->toDeposit;
                $discount += $result->discount;
            }
        }

        $totalPowerSumm = CashHandler::toRubles($totalPowerSumm);
        $totalMemSumm = CashHandler::toRubles($totalMemSumm);
        $totalTargetSumm = CashHandler::toRubles($totalTargetSumm);
        $totalSingleSumm = CashHandler::toRubles($totalSingleSumm);
        $fromDeposit = CashHandler::toRubles($fromDeposit);
        $toDeposit = CashHandler::toRubles($toDeposit);
        $discount = CashHandler::toRubles($discount);
        $total = CashHandler::toRubles($totalSingleSumm + $totalPowerSumm + $totalTargetSumm + $totalMemSumm - $discount - $fromDeposit + $toDeposit, true);
        $content .= "<tr><td>" . CashHandler::toShortSmoothRubles($totalPowerSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalMemSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalTargetSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($totalSingleSumm) . "</td><td>" . CashHandler::toShortSmoothRubles($fromDeposit) . "</td><td>" . CashHandler::toShortSmoothRubles($toDeposit) . "</td><td>" . CashHandler::toShortSmoothRubles($discount) . "</td></tr></tbody></table>";
        return ['status' => 1, 'data' => $content, 'totalSumm' => $total];
    }

    /**
     * @param $interval
     * @return array
     */
    private function getReport($interval)
	{
	    $fullSumm = 0;
	    $totalSumm = 0;
		$trs = Table_fulltransactioninfo::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
		$content = [];
		if (!empty($trs)) {
			foreach ($trs as $item) {
                /** @var Table_fulltransactioninfo $item */
                $fullSumm += CashHandler::toRubles($item->transactionSumm);
                    // членские взносы
                $memList = '--';
                $memSumm = '--';
                $payedSumm = 0;
                $partial = $item->partialPayed;
				$date = TimeHandler::getDateFromTimestamp($item->transactionDate);
				$type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
				$dom = new DOMHandler($item->bill_content);
				$mem = $dom->query('/payment/membership');
				if ($mem->length === 1) {
                    /** @var DOMElement $memItem */
                    $memItem = $mem->item(0);
                    if($partial){
                        $payedSumm = $memItem->getAttribute('payed');
                        if($payedSumm > 0){
                            $memSumm = $payedSumm;
                        }
                        else{
                            $memSumm = 0;
                        }
                    }
                    else{
                        $memSumm = CashHandler::toRubles($memItem->getAttribute('cost'));
                    }
					$quarters = $dom->query('/payment/membership/quarter');
					if($memList === '--'){
                        $memList = '';
                    }
					foreach ($quarters as $quarter) {
                        /** @var \DOMElement $quarter */
					    $summ = CashHandler::toRubles($quarter->getAttribute('summ'));
					    $totalSumm += $summ;
                        if($partial){
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if($payedSumm > 0){
                                if($summ > $payedSumm){
                                    $payedSumm -= $summ;
                                }
                                else{
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            }
                            else{
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
                    if($partial){
                        $payedSumm = $memItem->getAttribute('payed');
                        if($payedSumm > 0){
                            $additionalSumm = $payedSumm;
                        }
                        else{
                            $additionalSumm = 0;
                        }
                    }

				    if($memSumm === '--')
                    {
                        $memSumm = $additionalSumm;
                    }
				    else{
                        $memSumm += $additionalSumm;
                    }
					$quarters = $dom->query('/payment/additional_membership/quarter');
                    if($memList === '--'){
                        $memList = '';
                    }
					foreach ($quarters as $quarter) {
						/** @var \DOMElement $quarter */
						$summ = CashHandler::toRubles($quarter->getAttribute('summ'));
                        $totalSumm += $summ;

                        if($partial){
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if($payedSumm > 0){
                                if($summ > $payedSumm){
                                    $payedSumm -= $summ;
                                }
                                else{
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            }
                            else{
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
				    $payedSumm = 0;
                    /** @var DOMElement $powerItem */
                    $powerItem = $power->item(0);
                    if($partial){
                        $payedSumm = $powerItem->getAttribute('payed');
                        if($payedSumm > 0){
                            $powSumm = $payedSumm;
                        }
                        else{
                            $powSumm = 0;
                        }
                    }
                    else{
                        $powSumm = CashHandler::toRubles($powerItem->getAttribute('cost'));
                    }

                    $totalSumm += $powSumm;
					$months = $dom->query('/payment/power/month');
					$powCounterValue = '';
					$powUsed = '';
					foreach ($months as $month) {
						/** @var \DOMElement $month */
						$powCounterValue .= $month->getAttribute('date') . ': '  . $month->getAttribute('new-data') . '<br>';
						$powUsed .= $month->getAttribute('difference') . '<br>';
					}
				}
				$additional = $dom->query('/payment/additional_power');
				if ($additional->length === 1) {
					$summ =  CashHandler::toRubles($additional->item(0)->getAttribute('cost'));
                    $totalSumm += $summ;
                    if($powSumm === '--'){
                        $powSumm =  $summ . '</br>';
                    }
                    else{
                        $powSumm += $summ;
                    }
					$months = $dom->query('/payment/additional_power/month');
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
				$tar = $dom->query('/payment/target/pay');
				if ($tar->length > 0) {
                    /** @var DOMElement $targetItem */
                    $targetItem = $tar->item(0);
				    $payedSumm = 0;
					$tarList = '';
                    if($partial){
                        $payedSumm = $targetItem->parentNode->getAttribute('payed');
                        if($payedSumm > 0){
                            $tarSumm = $payedSumm;
                        }
                        else{
                            $tarSumm = 0;
                        }
                    }
                    else{
                        $tarSumm = CashHandler::toRubles($targetItem->parentNode->getAttribute('cost'));
                    }
					$totalSumm += $tarSumm;
					foreach ($tar as $value) {
						/** @var \DOMElement $value */
                        $summ = CashHandler::toRubles($value->getAttribute('summ'));
                        if($partial){
                            // если оплата частичная- сверю сумму с полной оплатой раздела.
                            if($payedSumm > 0){
                                if($summ > $payedSumm){
                                    $payedSumm -= $summ;
                                }
                                else{
                                    $summ = $payedSumm;
                                    $payedSumm = 0;
                                }
                            }
                            else{
                                $summ = 0;
                            }
                        }

						$tarList .= $value->getAttribute('year') . ' - ' . $summ . '<br/>';
					}
				}
				$additionalTar = $dom->query('/payment/additional_target/pay');
				if ($additionalTar->length > 0) {
                    $summ = CashHandler::toRubles($additionalTar->item(0)->parentNode->getAttribute('cost'));
					if($tarSumm === '--'){
					    $tarSumm = $summ;
                    }
                    else{
					    $tarSumm += $summ;
                    }
					$totalSumm += $summ;
					foreach ($additionalTar as $value) {
						/** @var \DOMElement $value */
						$tarList .= $value->getAttribute('year') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
					}
				}
				// разовые взносы
				$singleList = '--';
				$singleSumm = '--';
				$singles = $dom->query('/payment/single/pay');
				if ($singles->length > 0) {
					$singleList = '';
					$singleSumm = CashHandler::toRubles($singles->item(0)->parentNode->getAttribute('cost'));
					$totalSumm += $singleSumm;
					foreach ($singles as $value) {
						$singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('summ')) . '<br/>';
					}
				}
				$toDeposit = $item->toDeposit ?: 0;
				$deposit = CashHandler::toRubles($toDeposit - $item->depositUsed, true);

				$totalSumm +=  ($item->toDeposit - $item->depositUsed + $item->discount);


				$content[] = "<tr><td class='date-cell'>$date</td><td class='bill-id-cell'>{$item->bill_id}</td><td class='cottage-number-cell'>{$item->cottage_number}</td><td class='quarter-cell'>$memList</td><td class='mem-summ-cell'>$memSumm</td><td class='pow-values'>$powCounterValue</td><td class='pow-total'>$powUsed</td><td class='pow-summ'>$powSumm</td><td class='target-by-years-cell'>$tarList</td><td class='target-total'>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>{$item->discount}</td><td>{$deposit}</td></td><td>{$item->transactionSumm}</td><td class='text-primary'>$type</td></tr>";
			}
		}
		$additionalTrs = Table_fulladditionaltransactioninfo::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['finish']])->all();
		if (!empty($additionalTrs)) {
			foreach ($additionalTrs as $item) {
                $fullSumm += CashHandler::toRubles($item->transactionSumm);
				$date = TimeHandler::getDateFromTimestamp($item->transactionDate);
				$type = $item->transactionType === 'cash' ? 'Нал' : 'Безнал';
				$dom = new \DOMDocument('1.0', 'UTF-8');
				$dom->loadXML($item->bill_content);
				$xpath = new \DOMXPath($dom);
				// членские взносы
				$memList = '--';
				$memSumm = '--';
				$additionalMem = $xpath->query('/payment/additional_membership');
				if ($additionalMem->length === 1) {
				    if($memSumm === '--'){
                        $memSumm = CashHandler::toRubles($additionalMem->item(0)->getAttribute('cost'));
                    }
                    else{
                        $memSumm += CashHandler::toRubles($additionalMem->item(0)->getAttribute('cost'));
                    }
					$quarters = $xpath->query('/payment/additional_membership/quarter');
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
					$powSumm = CashHandler::toRubles($power->item(0)->getAttribute('cost')) . '</b>';
					$months = $xpath->query('/payment/power/month');
					$powCounterValue = '';
					$powUsed = '';
					foreach ($months as $month) {
						/** @var \DOMElement $month */
						$powCounterValue .= $month->getAttribute('date') . ': '  . $month->getAttribute('new-data') . '<br>';
						$powUsed .= $month->getAttribute('difference') . '<br>';
					}
				}
				$additonalPower = $xpath->query('/payment/additonal_power');
				if ($additonalPower->length === 1) {
				    if($powSumm === '--'){
                        $powSumm =  CashHandler::toRubles($additonalPower->item(0)->getAttribute('cost'));
                    }
                    else{
                        $powSumm += CashHandler::toRubles($additonalPower->item(0)->getAttribute('cost'));
                    }
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
				$tar = $xpath->query('/payment/additional_target/pay');
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
					foreach ($singles as $value) {
						$singleList .= $value->getAttribute('timestamp') . ' - ' . CashHandler::toRubles($value->getAttribute('payed')) . '<br/>';
					}
				}
				$toDeposit = $item->toDeposit ?: 0;
				$deposit = CashHandler::toRubles($toDeposit - $item->depositUsed, true);
				$content[] = "<tr><td>$date</td><td>{$item->bill_id}-a</td><td>{$item->cottage_number}-a</td><td>$memList</td><td>$memSumm</td><td>$powCounterValue</td><td>$powUsed</td><td>$powSumm</td><td>$tarList</td><td>$tarSumm</td><td>$singleList</td><td>$singleSumm</td><td>{$item->discount}</td><td>{$deposit}</td></td><td>{$item->transactionSumm}</td><td class='text-primary'>$type</td></tr>";
			}
		}
		return ['status' => 1, 'data' => $content, 'totalSumm' => $fullSumm];
	}
}