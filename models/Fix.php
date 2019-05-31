<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 12:23
 */

namespace app\models;


use yii\base\Model;

class Fix extends Model
{
//
//    public static function refreshPower(): array
//    {
//        // получу список всех участков
//        $cottages = Table_cottages::find()->select(['cottageNumber', 'currentPowerData'])->all();
//        if(!empty($cottages)){
//            foreach ($cottages as $cottage){
//                // найду последнюю запись о электроэнергии, запишу показания в карточку участка
//                $data = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->orderBy('searchTimestamp DESC')->select('newPowerData')->one();
//                $cottage->currentPowerData = $data->newPowerData;
//                $cottage->save();
//            }
//        }
//        return ['status' => 1];
//    }
//
//	/**
//	 *
//	 */
//	public static function checkTargets(){
//    	$cottages = Cottage::getRegistredList();
//	    foreach ($cottages as $cottage) {
//		    $dom = DOMHandler::getDom($cottage->targetPaysDuty);
//		    $xpath = DOMHandler::getXpath($dom);
//		    $t = $xpath->query('//target');
//		    if($t->length > 0){
//		    	$changed = false;
//		    	$summ = 0;
//			    foreach ($t as $item) {
//				    /** @var \DOMElement $item */
//				    $float = $item->getAttribute('float');
//				    $fixed = $item->getAttribute('fixed');
//				    $payed = $item->getAttribute('payed');
//				    $summ += Calculator::countFixedFloat($fixed, $float, $cottage->cottageSquare) - $payed;
//				    if($float === '' || $fixed === ''){
//				    	// получу данные по тарифу
//					    $year = $item->getAttribute('year');
//					    $tariff = TargetHandler::getRowTariff($year);
//					    $item->setAttribute('fixed', $tariff->fixed_part);
//					    $item->setAttribute('float', $tariff->float_part);
//					    $changed = true;
//				    }
//		    	}
//		    	if($changed){
//			    	$text = DOMHandler::saveXML($dom);
//				    /** @var Table_cottages $cottage */
//			    	$cottage->targetPaysDuty = $text;
//			    }
//			    $cottage->targetDebt = CashHandler::rublesRound($summ);
//			    $cottage->save();
//		    }
//    	}
//    	return ['status' => 1];
//    }
//
//	/**
//	 *
//	 */
//	public static function bills(): array
//	{
//		$bills = Table_payment_bills::find()->all();
//		foreach ($bills as $bill){
//			$dom = DOMHandler::getDom($bill->bill_content);
//			$xpath = DOMHandler::getXpath($dom);
//			$powers = $xpath->query('/payment/power/month');
//			if($powers->length > 0){
//				foreach ($powers as $power){
//					/** @var \DOMElement $power */
//					if(!$power->hasAttribute('corrected')){
//						$power->setAttribute('corrected', 0);
//					}
//				}
//			}
//			$membership = $xpath->query('/payment/membership/quarter');
//			if($membership->length > 0){
//				foreach ($membership as $item){
//					/** @var \DOMElement $item */
//					if(!$item->hasAttribute('float-cost')){
//						$float = CashHandler::toRubles($item->getAttribute('float'));
//						$square = $item->getAttribute('square');
//						$item->setAttribute('float-cost', CashHandler::rublesRound($float / 100 * $square));
//					}
//				}
//			}
//			$targets = $xpath->query('/payment/target/pay');
//			if($targets->length > 0){
//				foreach ($targets as $item){
//					/** @var \DOMElement $item */
//					if(!$item->hasAttribute('total-summ')){
//						$float = CashHandler::toRubles($item->getAttribute('float-cost'));
//						$fixed = CashHandler::toRubles($item->getAttribute('fixed-cost'));
//						$square = $item->getAttribute('square');
//						$payedBefore = CashHandler::toRubles($item->getAttribute('payed-before'));
//						$summ = CashHandler::toRubles($item->getAttribute('summ'));
//						$totalSumm = Calculator::countFixedFloatPlus($fixed, $float, $square);
//						$item->removeAttribute('fixed-cost');
//						$item->setAttribute('float-cost', $totalSumm['float']);
//						$item->setAttribute('float', $float);
//						$item->setAttribute('fixed', $fixed);
//						$item->setAttribute('total-summ', $totalSumm['total']);
//						$item->setAttribute('left-pay', $totalSumm['total'] - $payedBefore - $summ);
//					}
//				}
//			}
//			$text = DOMHandler::saveXML($dom);
//			$bill->bill_content = $text;
//			$bill->save();
//		}
//		return ['status' => 1];
//	}
//
//	public static function recalculateAllPower(): array
//	{
//		$months = Table_tariffs_power::find()->all();
//		if(!empty($months)){
//			foreach ($months as $month) {
//				PowerHandler::recalculatePower($month->targetMonth);
//			}
//		}
//		return ['status' => 1];
//	}
//	public static function recalculateAllTargets(): array
//	{
//		$years = Table_tariffs_target::find()->all();
//		if(!empty($years)){
//			foreach ($years as $year) {
//				TargetHandler::recalculateTarget($year->year);
//			}
//		}
//		return ['status' => 1];
//	}
//
//	public static function recalculateAllMemberships(): array
//	{
//		$quarters = Table_tariffs_membership::find()->all();
//		if(!empty($quarters)){
//			foreach ($quarters as $quarter) {
//				MembershipHandler::recalculateMembership($quarter->quarter);
//			}
//		}
//		return ['status' => 1];
//	}
//
//	public static function recountPayments(){
//	    // получу все платежи вообще
//        $payments = Table_payment_bills::find()->all();
//
//        foreach ($payments as $payment){
//            // получу все платежи за электроэнергию в этом чеке
//            $dom = new DOMHandler($payment->bill_content);
//            $powers = $dom->findByName('month');
//            foreach ($powers as $power) {
//                // теперь найду запись о платеже в таблице оплат за электроэнергию
//                $target = Table_payed_power::find()->where(['cottageId' => $payment->cottageNumber, 'month' => $power->getAttribute('date')])->one();
//                if($target){
//                }
//            }
//            $mems = $dom->findByName('membership/quarter');
//            foreach ($mems as $mem) {
//                // теперь найду запись о платеже в таблице оплат за электроэнергию
//                $target = Table_payed_membership::find()->where(['cottageId' => $payment->cottageNumber, 'quarter' => $mem->getAttribute('date')])->one();
//                if($target){
//                    if(CashHandler::toRubles($mem->getAttribute('summ')) != CashHandler::toRubles($target->summ)){
//                        $target->summ = CashHandler::toRubles($mem->getAttribute('summ'));
//                        $target->save();
//
//                    }
//                }
//            }
//            $mems = $dom->findByName('additional_membership/quarter');
//            foreach ($mems as $mem) {
//                // теперь найду запись о платеже в таблице оплат за электроэнергию
//                $target = Table_additional_payed_membership::find()->where(['cottageId' => $payment->cottageNumber, 'quarter' => $mem->getAttribute('date')])->one();
//                if($target){
//                    if(CashHandler::toRubles($mem->getAttribute('summ')) != CashHandler::toRubles($target->summ)){
//                        $target->summ = CashHandler::toRubles($mem->getAttribute('summ'));
//                        $target->save();
//
//                    }
//                }
//            }
//        }
//    }
    public static function fillTransactions()
    {
        $transactions = Table_transactions::find()->all();
        $bills = [];
        foreach ($transactions as $transaction) {
            $bill = Table_payment_bills::find()->where(['id' => $transaction->billId])->one();
            if (!empty($bills[$transaction->billId])) {
                echo "duplicate \n";
                $transaction->usedDeposit = 0;
                $transaction->save();
                $bills[$transaction->billId]->gainedDeposit = 0;
                $bills[$transaction->billId]->partial = true;
                $bills[$transaction->billId]->save();

            } else {
                $transaction->usedDeposit = $bill->depositUsed ?? 0;
                $transaction->gainedDeposit = $bill->toDeposit ?? 0;
                $transaction->save();
                $bills[$transaction->billId] = $transaction;
            }
        }

    }
}