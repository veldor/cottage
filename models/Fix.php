<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 12:23
 */

namespace app\models;


use app\models\database\Mail;
use app\models\tables\Table_bill_fines;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use yii\base\Model;

class Fix extends Model
{
    public static function fixSmallDeposits(){
        $wrongTransactionsCount = 0;
        $wholeFixedDifference = 0;
        // получу все транзакции
        $allTransactions = Table_transactions::find()->all();
        $transactonsCount = count($allTransactions);
        foreach ($allTransactions as $singleTransaction){
            $payedInTransaction = 0;
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_payed_power::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_additional_payed_power::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_payed_membership::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_additional_payed_membership::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    if($powerPay->cottageId != 127){
                        $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                    }
                }
            }

            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_payed_target::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_additional_payed_target::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_payed_single::find()->where(['transactionId' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            // тут посчитаю сумму всего, что входит в транзакцию и сравню с общей суммой транзакции
            $powerPayedInTransaction = Table_payed_fines::find()->where(['transaction_id' => $singleTransaction->id])->all();
            if(!empty($powerPayedInTransaction)){
                foreach ($powerPayedInTransaction as $powerPay){
                    $payedInTransaction = CashHandler::toRubles($payedInTransaction) + CashHandler::toRubles($powerPay->summ);
                }
            }
            if(CashHandler::toRubles($singleTransaction->transactionSumm) !== CashHandler::toRubles($payedInTransaction)){
                if(Deposit_io::find()->where(['transactionId' => $singleTransaction->id, 'destination' => 'in'])->count() == 0){
                    $payedInThisFromDeposit = Deposit_io::find()->where(['transactionId' => $singleTransaction->id, 'destination' => 'out'])->one();
                    if($payedInThisFromDeposit !== null){
                        $payedInTransaction = CashHandler::toRubles($payedInTransaction) - CashHandler::toRubles($payedInThisFromDeposit->summ);
                    }
                    if(CashHandler::toRubles($singleTransaction->transactionSumm) !== CashHandler::toRubles($payedInTransaction)){
                        $difference = CashHandler::toRubles(CashHandler::toRubles($singleTransaction->transactionSumm) - CashHandler::toRubles($payedInTransaction), true);
                        if($difference > 0){
                            echo "{$singleTransaction->id} : $difference <br/>";
                            $cottageInfo = Cottage::getCottageByLiteral($singleTransaction->cottageNumber);
                            $wholeFixedDifference += $difference;
                            // добавлю сумму к депозиту- это я косячнул :)
                            $newDepositIo = new Deposit_io();
                            $newDepositIo->destination = 'in';
                            $newDepositIo->transactionId = $singleTransaction->id;
                            $newDepositIo->summ = $difference;
                            $newDepositIo->billId = $singleTransaction->billId;
                            $newDepositIo->actionDate = time();
                            $newDepositIo->summBefore = $cottageInfo->deposit;
                            $cottageInfo->deposit = CashHandler::toRubles($cottageInfo->deposit + $difference);
                            $newDepositIo->summAfter = $cottageInfo->deposit;
                            $newDepositIo->cottageNumber = $cottageInfo->cottageNumber;
                            $newDepositIo->save();
                            $cottageInfo->save();
                            $singleTransaction->gainedDeposit = $difference;
                            $singleTransaction->save();
                            $wrongTransactionsCount++;
                        }
                    }
                }
            }
        }
        echo "wrong transactions count is $wrongTransactionsCount of $transactonsCount, whole fixed " . CashHandler::toRubles($wholeFixedDifference);
    }


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

    public static function test()
    {
        // получу платежи с задолженностями за 2018
        $results = Table_cottages::find()->where(['LIKE', 'targetPaysDuty', 'year="2018"'])->all();
        // для каждого получу последний платёж
        foreach ($results as $result) {
            $bill = Table_payment_bills::find()->where(['cottageNumber' => $result->cottageNumber])->orderBy('creationTime DESC')->one();
            if (!empty($bill)) {
                if (!$bill->isMessageSend && !$bill->isInvoicePrinted) {
                    echo "$result->cottageNumber <br/>";
                }
            } else {
                echo "$result->cottageNumber <br/>";
            }
        }
    }

    public static function fix()
    {
        // зарегистрирую все почтовые ящики
        $cottages = Cottage::getRegister();
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                if (!empty($cottage->cottageOwnerEmail) && Mail::findOne(['cottage' => $cottage->cottageNumber, 'email' => $cottage->cottageOwnerEmail, 'cottage_is_double' => false]) === null) {
                    (new Mail(['scenario' => Mail::SCENARIO_CREATE, 'cottage' => $cottage->cottageNumber, 'fio' => $cottage->cottageOwnerPersonals, 'email' => $cottage->cottageOwnerEmail, 'cottage_is_double' => false]))->save();
                    $cottage->is_mail = true;
                    $cottage->save();
                }
                if (!empty($cottage->cottageContacterEmail) && Mail::findOne(['cottage' => $cottage->cottageNumber, 'email' => $cottage->cottageContacterEmail, 'cottage_is_double' => false]) === null) {
                    (new Mail(['scenario' => Mail::SCENARIO_CREATE,'cottage' => $cottage->cottageNumber, 'fio' => $cottage->cottageContacterPersonals, 'email' => $cottage->cottageContacterEmail, 'cottage_is_double' => false]))->save();
                    $cottage->is_mail = true;
                    $cottage->save();
                }
            }
        }
        $cottages = AdditionalCottage::getRegistred();
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                if (!empty($cottage->cottageOwnerEmail) && Mail::findOne(['cottage' => $cottage->cottageNumber, 'email' => $cottage->cottageOwnerEmail, 'cottage_is_double' => true]) === null) {
                    (new Mail(['cottage' => $cottage->cottageNumber, 'fio' => $cottage->cottageOwnerPersonals, 'email' => $cottage->cottageOwnerEmail, 'cottage_is_double' => true]))->save();
                    $cottage->is_mail = true;
                    $cottage->save();
                }
                if (!empty($cottage->cottageContacterEmail) && Mail::findOne(['cottage' => $cottage->cottageNumber, 'email' => $cottage->cottageContacterEmail, 'cottage_is_double' => true]) === null) {
                    (new Mail(['cottage' => $cottage->cottageNumber, 'fio' => $cottage->cottageContacterPersonals, 'email' => $cottage->cottageContacterEmail, 'cottage_is_double' => true]))->save();
                    $cottage->is_mail = true;
                    $cottage->save();
                }
            }
        }
    }

    /*public static function fix()
    {
        $partial = [];
        // найду все транзакции
        $transactions = Table_transactions::find()->all();
        foreach ($transactions as $transaction) {
            // если не заполнена дата транзакции- проставляю её
            if ($transaction->bankDate === 0) {
                $transaction->bankDate = $transaction->transactionDate;
                $transaction->payDate = $transaction->transactionDate;
                $transaction->save();
            }
            // найду счёт, к которому привязана транзакция
            $bill = Table_payment_bills::findOne($transaction->billId);
            if(empty($bill->payedSumm)){
                $bill->payedSumm = 0;
            }
            // если сумма транзакции полностью покрывает счёт- он считается полностью оплаченным
            $billSumm = CashHandler::toRubles(CashHandler::toRubles($bill->totalSumm) - CashHandler::toRubles($bill->depositUsed) - CashHandler::toRubles($bill->discount));
            if (CashHandler::toRubles($transaction->transactionSumm) >= CashHandler::toRubles($billSumm)) {
                // если есть скидка- привязываю к номеру транзакции
                if ($bill->discount > 0) {
                    $discount = Table_discounts::findOne(['billId' => $bill->id]);
                    if (!empty($discount)) {
                        if (empty($discount->transactionId)) {
                            $discount->transactionId = $transaction->id;
                            $discount->save();
                        }
                    } else {
                        $discount = new Table_discounts();
                        $discount->billId = $bill->id;
                        $discount->transactionId = $transaction->id;
                        $discount->summ = $bill->discount;
                        $discount->reason = $bill->discountReason;
                        $discount->actionDate = $transaction->bankDate;
                        $discount->save();
                    }
                }
                if ($bill->depositUsed > 0) {
                    $transaction->usedDeposit = $bill->depositUsed;
                    $transaction->save();
                    $dep = Table_deposit_io::findOne(['billId' => $bill->id, 'destination' => 'out']);
                    if (!empty($dep)) {
                        if (empty($dep->transactionId)) {
                            $dep->transactionId = $transaction->id;
                            $dep->save();
                        }
                    }
                }
                if ($bill->toDeposit > 0) {
                    $transaction->gainedDeposit = $bill->toDeposit;
                    $transaction->save();
                    $dep = Table_deposit_io::findOne(['billId' => $bill->id, 'destination' => 'in']);
                    if (!empty($dep)) {
                        if (empty($dep->transactionId)) {
                            $dep->transactionId = $transaction->id;
                            $dep->save();
                        }
                    }
                }
                $transaction->transactionReason = 'полная оплата по счёту ' . $bill->id;
                $transaction->save();
                // найду все оплаты по данному счёту, выставлю в них номер данной транзакции
                $power = Table_payed_power::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_target::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_membership::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_single::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                // проверю, не оплачивались ли в транзакции счета по дополнительному участку
                $power = Table_additional_payed_power::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0 && $item->cottageId === $bill->cottageNumber) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_additional_payed_target::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0 && $item->cottageId === $bill->cottageNumber) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_additional_payed_membership::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0 && $item->cottageId === $bill->cottageNumber) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
            } else {
                $transaction->transactionReason = 'полная оплата по счёту ' . $bill->id;
                $transaction->save();
                // Добавлю транзакцию в список транзакций по счетам
                $partial[$bill->id][] = $transaction->id;
            }
        }
        // теперь обработаю частичные оплаты счётов
        foreach ($partial as $key => $value) {
            $bill = Table_payment_bills::findOne($key);
            if (count($value) > 1) {
                // так, тут хитрее. Скидка и потраченный депозит прицепляются к первой транзакции
                $transaction = Table_transactions::findOne($value[0]);
                $transaction->partial = 1;
                if ($bill->discount > 0) {
                    $discount = Table_discounts::findOne(['billId' => $bill->id]);
                    if (!empty($discount)) {
                        if (empty($discount->transactionId)) {
                            $discount->transactionId = $transaction->id;
                            $discount->save();
                        }
                    } else {
                        $discount = new Table_discounts();
                        $discount->billId = $bill->id;
                        $discount->cottageNumber = $bill->cottageNumber;
                        $discount->transactionId = $transaction->id;
                        $discount->summ = $bill->discount;
                        $discount->reason = $bill->discountReason;
                        $discount->actionDate = $transaction->bankDate;
                        $discount->save();
                    }
                }
                if ($bill->depositUsed > 0) {
                    $transaction->usedDeposit = $bill->depositUsed;
                    $dep = Table_deposit_io::findOne(['billId' => $bill->id, 'destination' => 'out']);
                    if (!empty($dep)) {
                        if (empty($dep->transactionId)) {
                            $dep->transactionId = $transaction->id;
                            $dep->save();
                        }
                    }
                }
                $transaction->save();
                // полученный депозит привязывается к последней транзакции
                if($bill->toDeposit > 0){
                    $transaction = Table_transactions::findOne($value[count($value) - 1]);
                    $transaction->gainedDeposit = $bill->toDeposit;
                    $transaction->save();
                    $dep = Table_deposit_io::findOne(['billId' => $bill->id, 'destination' => 'in']);
                    if (!empty($dep)) {
                        if (empty($dep->transactionId)) {
                            $dep->transactionId = $transaction->id;
                            $dep->save();
                        }
                    }
                }
                // теперь привяжу все оплаченные сущности к транзакциям
                foreach ($value as $transactionNumber) {
                    $transaction = Table_transactions::findOne($transactionNumber);
                    // если время проведения транзакции совпадает со временем оплаты- привязываю платёж
                    // найду все оплаты по данному счёту, выставлю в них номер данной транзакции
                    $power = Table_payed_power::find()->where(['billId' => $bill->id])->all();
                    if (!empty($power)) {
                        foreach ($power as $item) {
                            if ($item->transactionId === 0 && $item->paymentDate === $transaction->transactionDate) {
                                $item->transactionId = $transaction->id;
                                $item->save();
                            }
                        }
                    }
                    $power = Table_payed_target::find()->where(['billId' => $bill->id])->all();
                    if (!empty($power)) {
                        foreach ($power as $item) {
                            if ($item->transactionId === 0 && $item->paymentDate === $transaction->transactionDate) {
                                $item->transactionId = $transaction->id;
                                $item->save();
                            }
                        }
                    }
                    $power = Table_payed_membership::find()->where(['billId' => $bill->id])->all();
                    if (!empty($power)) {
                        foreach ($power as $item) {
                            if ($item->transactionId === 0 && $item->paymentDate === $transaction->transactionDate) {
                                $item->transactionId = $transaction->id;
                                $item->save();
                            }
                        }
                    }
                    $power = Table_payed_single::find()->where(['billId' => $bill->id])->all();
                    if (!empty($power)) {
                        foreach ($power as $item) {
                            if ($item->transactionId === 0 && $item->paymentDate === $transaction->transactionDate) {
                                $item->transactionId = $transaction->id;
                                $item->save();
                            }
                        }
                    }
                }
            } else {
                // снова найду информацию о счёте
                $bill = Table_payment_bills::findOne($key);
                // найду транзакцию
                $transaction = Table_transactions::findOne($value[0]);
                $transaction->partial = 1;
                // все оплаты, потраченный депозит и скидка привязываются к данной транзакции
                // если есть скидка- привязываю к номеру транзакции
                if ($bill->discount > 0) {
                    $discount = Table_discounts::findOne(['billId' => $bill->id]);
                    if (!empty($discount)) {
                        if (empty($discount->transactionId)) {
                            $discount->transactionId = $transaction->id;
                            $discount->save();
                        }
                    } else {
                        $discount = new Table_discounts();
                        $discount->billId = $bill->id;
                        $discount->transactionId = $transaction->id;
                        $discount->summ = $bill->discount;
                        $discount->reason = $bill->discountReason;
                        $discount->actionDate = $transaction->bankDate;
                        $discount->save();
                    }
                }
                if ($bill->depositUsed > 0) {
                    $transaction->usedDeposit = $bill->depositUsed;
                    $dep = Table_deposit_io::findOne(['billId' => $bill->id, 'destination' => 'out']);
                    if (!empty($dep)) {
                        if (empty($dep->transactionId)) {
                            $dep->transactionId = $transaction->id;
                            $dep->save();
                        }
                    }
                }
                if ($bill->toDeposit > 0) {
                    die('частичная оплата с депозитом');
                }
                $transaction->save();
                // все оплаченные сущности по счёту считаются оплаченными этой транзакцией
                // найду все оплаты по данному счёту, выставлю в них номер данной транзакции
                $power = Table_payed_power::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_target::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_membership::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
                $power = Table_payed_single::find()->where(['billId' => $bill->id])->all();
                if (!empty($power)) {
                    foreach ($power as $item) {
                        if ($item->transactionId === 0) {
                            $item->transactionId = $transaction->id;
                            $item->save();
                        }
                    }
                }
            }
        }
        $bills = Table_payment_bills::find()->all();
        foreach ($bills as $bill) {
            if(empty($bill->payedSumm)){
                $bill->payedSumm = 0;
                $bill->save();
            }
        }
        $bills = Table_payment_bills_double::find()->all();
        foreach ($bills as $bill) {
            if(empty($bill->payedSumm)){
                $bill->payedSumm = 0;
                $bill->save();
            }
        }
    }*/
}