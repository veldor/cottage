<?php


namespace app\models;


use app\models\tables\Table_bill_fines;
use app\models\tables\Table_payed_fines;
use app\models\utils\DbTransaction;
use Exception;
use yii\base\Model;

class ComparisonHandler extends Model
{

    const SCENARIO_MANUAL_COMPARISON = 'manual comparison';
    public $billId;
    public $transactionId;
    public $sendConfirmation = 0;

    const SCENARIO_COMPARISON = 'comparison';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_COMPARISON => ['billId', 'transactionId', 'sendConfirmation'],
            self::SCENARIO_MANUAL_COMPARISON => ['billId', 'transactionId', 'sendConfirmation'],
        ];
    }

    public function compare()
    {
        // todo обраружить и реализовать обработку платежей с допучастков
        // проверю, не является ли участок дополнительным
        $isDouble = Pay::isDoubleBill($this->billId);

        // найду платёж
        $billInfo = ComplexPayment::getBill($this->billId, $isDouble);
        $transactionInfo = TransactionsHandler::getTransaction($this->transactionId);
        if (empty($billInfo) || empty($transactionInfo)) {
            throw new ExceptionWithStatus('Не найден элемент транзакции', 2);
        }
        // получу необходимую для оплаты сумму
        $requiredAmount = CashHandler::toRubles(CashHandler::toRubles($billInfo->totalSumm) - CashHandler::toRubles($billInfo->depositUsed) - CashHandler::toRubles($billInfo->discount) - CashHandler::toRubles($billInfo->payedSumm));
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber, $isDouble);
        $additionalCottageInfo = null;
        if (!$isDouble && $cottageInfo->haveAdditional) {
            $additionalCottageInfo = Cottage::getCottageByLiteral($cottageInfo->cottageNumber . '-a');
            if ($additionalCottageInfo->hasDifferentOwner) {
                $additionalCottageInfo = null;
            }
        }
        // обработаю транзакцию
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        $difference = $transactionSumm - $requiredAmount;
        if ($difference < 0) {
            throw new ExceptionWithStatus('Частичная оплата счёта тут не обрабатывается!');
        }
        $difference = CashHandler::toRubles($difference);
        $transaction = new DbTransaction();

        try {
            if ($isDouble) {
                // создам транзакцию
                $t = new Table_transactions_double();
                $t->cottageNumber = $billInfo->cottageNumber;
                $t->billId = $billInfo->id;
                $t->transactionDate = $billInfo->paymentTime;
                $t->transactionType = 'no-cash';
                $t->transactionSumm = $transactionSumm;
                if ($difference > 0) {
                    $t->gainedDeposit = $difference;
                } else {
                    $t->gainedDeposit = 0;
                }
                $t->usedDeposit = $billInfo->depositUsed;
                $t->transactionWay = 'in';
                // заполню даты
                $paymentTime = TimeHandler::getCustomTimestamp($transactionInfo->real_pay_date, $transactionInfo->pay_time);
                $bankTime = TimeHandler::getCustomTimestamp($transactionInfo->pay_date);
                $t->transactionDate = time();
                $t->bankDate = $bankTime;
                $t->payDate = $paymentTime;
                $t->partial = 0;
                if ($billInfo->payedSumm > 0) {
                    $t->transactionReason = 'Завершающая оплата по счёту ' . $billInfo->id;
                } else {
                    $t->transactionReason = 'Полная оплата по счёту ' . $billInfo->id;
                }
                $t->save();
                $billInfo->paymentTime = TimeHandler::getTimestampFromBank($transactionInfo->pay_date, $transactionInfo->pay_time);
                $billInfo->isPayed = true;
                $billInfo->payedSumm = $transactionSumm;

                // обработаю отдельные категории
                // разберу категории
                $dom = new DOMHandler($billInfo->bill_content);
                // электричество
                $power = $dom->query('//additional_power/month');
                if ($power->length > 0) {
                    $totalAmount = 0;
                    foreach ($power as $item) {
                        $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                    }
                    // вычту оплаченное
                    $payedPower = Table_additional_payed_power::find()->where(['billId' => $billInfo->id])->all();
                    if (!empty($payedPower)) {
                        foreach ($payedPower as $item) {
                            $totalAmount -= $item->summ;
                        }
                    }
                    PowerHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
                }
                // членские
                $membership = $dom->query('//additional_membership/quarter');
                if ($membership->length > 0) {
                    $totalAmount = 0;
                    foreach ($membership as $item) {
                        $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                    }
                    // вычту оплаченное
                    $payedMembership = Table_additional_payed_membership::find()->where(['billId' => $billInfo->id])->all();
                    if (!empty($payedMembership)) {
                        foreach ($payedMembership as $item) {
                            $totalAmount -= $item->summ;
                        }
                    }
                    MembershipHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
                }
                // целевые
                $target = $dom->query('//additional_target/pay');
                if ($target->length > 0) {
                    $totalAmount = [];
                    foreach ($target as $item) {
                        $year = $item->getAttribute('year');
                        $amount = DOMHandler::getFloatAttribute($item, 'summ');
                        // вычту оплаченное
                        $payedTarget = Table_payed_target::find()->where(['billId' => $billInfo->id, 'year' => $year])->all();
                        if (!empty($payedTarget)) {
                            foreach ($payedTarget as $targetItem) {
                                $amount -= $targetItem->summ;
                            }
                        }
                        $totalAmount[$year] = $amount;
                    }
                    TargetHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
                }
                // разовые
                $single = $dom->query('//additional_single/pay');
                if ($single->length > 0) {
                    $totalAmount = [];
                    foreach ($single as $item) {
                        $time = $item->getAttribute('timestamp');
                        $amount = DOMHandler::getFloatAttribute($item, 'summ');
                        // вычту оплаченное
                        $payedSingle = Table_payed_single::find()->where(['billId' => $billInfo->id, 'time' => $time])->all();
                        if (!empty($payedSingle)) {
                            foreach ($payedSingle as $singleItem) {
                                $amount -= $singleItem->summ;
                            }
                        }
                        $totalAmount[$time] = $amount;
                    }
                    SingleHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
                }
                $fines = Table_bill_fines::find()->where(['bill_id' => $billInfo])->all();
                if (!empty($fines)) {
                    $totalAmount = 0;
                    foreach ($fines as $item) {
                        $totalAmount += $item->start_summ;
                    }
                    // вычту оплаченное
                    $payedFines = Table_payed_fines::find()->where(['transaction_id' => $t->id])->all();
                    if (!empty($payedFines)) {
                        foreach ($payedFines as $item) {
                            $totalAmount -= $item->summ;
                        }
                    }
                    FinesHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
                }

                if ($billInfo->depositUsed > 0) {
                    DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out', $t);
                }
                if ($billInfo->discount > 0) {
                    DiscountHandler::registerDiscount($billInfo, $t);
                }
                if ($difference > 0) {
                    $billInfo->toDeposit = CashHandler::toRubles($difference);
                    DepositHandler::registerDeposit($billInfo, $cottageInfo, 'in', $t);
                }

                $transactionInfo->bounded_bill_id = $billInfo->id;
                $transactionInfo->save();
                $billInfo->save();
                $cottageInfo->save();
                $transaction->commitTransaction();
                if ((bool)$this->sendConfirmation) {
                    Cloud::sendMessage($cottageInfo, 'Получен платёж', 'Получен платёж на сумму ' . CashHandler::toSmoothRubles($t->transactionSumm) . '. Благодарим за оплату.');
                }
                return ['status' => 1];
            }

// создам транзакцию
            $t = new Table_transactions();
            $t->cottageNumber = $billInfo->cottageNumber;
            $t->billId = $billInfo->id;
            $t->transactionDate = $billInfo->paymentTime;
            $t->transactionType = 'no-cash';
            $t->transactionSumm = $transactionSumm;
            if ($difference > 0) {
                $t->gainedDeposit = $difference;
            } else {
                $t->gainedDeposit = 0;
            }
            $t->usedDeposit = $billInfo->depositUsed;
            $t->transactionWay = 'in';
            // заполню даты
            $paymentTime = TimeHandler::getCustomTimestamp($transactionInfo->real_pay_date, $transactionInfo->pay_time);
            $bankTime = TimeHandler::getCustomTimestamp($transactionInfo->pay_date);
            $t->transactionDate = time();
            $t->bankDate = $bankTime;
            $t->payDate = $paymentTime;
            $t->partial = 0;
            if ($billInfo->payedSumm > 0) {
                $t->transactionReason = 'Завершающая оплата по счёту ' . $billInfo->id;
            } else {
                $t->transactionReason = 'Полная оплата по счёту ' . $billInfo->id;
            }
            $t->save();

            $billInfo->paymentTime = TimeHandler::getTimestampFromBank($transactionInfo->pay_date, $transactionInfo->pay_time);
            $billInfo->isPayed = true;
            $billInfo->payedSumm = $transactionSumm;

            // обработаю отдельные категории

            $additionalCottageInfo = null;
            if (!empty($cottageInfo->haveAdditional)) {
                $additionalCottageInfo = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
            }

            // разберу категории
            $dom = new DOMHandler($billInfo->bill_content);

            // электричество
            $power = $dom->query('//power/month');
            if ($power->length > 0) {
                $totalAmount = 0;
                foreach ($power as $item) {
                    $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                }
                // вычту оплаченное
                $payedPower = Table_payed_power::find()->where(['billId' => $billInfo->id])->all();
                if (!empty($payedPower)) {
                    foreach ($payedPower as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                PowerHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
            }
            // электричество
            $power = $dom->query('//additional_power/month');
            if ($power->length > 0) {
                $totalAmount = 0;
                foreach ($power as $item) {
                    $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                }
                // вычту оплаченное
                $payedPower = Table_additional_payed_power::find()->where(['billId' => $billInfo->id])->all();
                if (!empty($payedPower)) {
                    foreach ($payedPower as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                PowerHandler::handlePartialPayment($billInfo, $totalAmount, $additionalCottageInfo, $t);
            }
            // членские
            $membership = $dom->query('//membership/quarter');
            if ($membership->length > 0) {
                $totalAmount = 0;
                foreach ($membership as $item) {
                    $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                }
                // вычту оплаченное
                $payedMembership = Table_payed_membership::find()->where(['billId' => $billInfo->id])->all();
                if (!empty($payedMembership)) {
                    foreach ($payedMembership as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                MembershipHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
            }
            // членские
            $membership = $dom->query('//additional_membership/quarter');
            if ($membership->length > 0) {
                $totalAmount = 0;
                foreach ($membership as $item) {
                    $totalAmount += DOMHandler::getFloatAttribute($item, 'summ');
                }
                // вычту оплаченное
                $payedMembership = Table_additional_payed_membership::find()->where(['billId' => $billInfo->id])->all();
                if (!empty($payedMembership)) {
                    foreach ($payedMembership as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                MembershipHandler::handlePartialPayment($billInfo, $totalAmount, $additionalCottageInfo, $t);
            }
            // целевые
            $target = $dom->query('//target/pay');
            if ($target->length > 0) {
                $totalAmount = [];
                foreach ($target as $item) {
                    $year = $item->getAttribute('year');
                    $amount = DOMHandler::getFloatAttribute($item, 'summ');
                    // вычту оплаченное
                    $payedTarget = Table_payed_target::find()->where(['billId' => $billInfo->id, 'year' => $year])->all();
                    if (!empty($payedTarget)) {
                        foreach ($payedTarget as $targetItem) {
                            $amount -= $targetItem->summ;
                        }
                    }
                    $totalAmount[$year] = $amount;
                }
                TargetHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
            }
            // разовые
            $single = $dom->query('//single/pay');
            if ($single->length > 0) {
                $totalAmount = [];
                foreach ($single as $item) {
                    $time = $item->getAttribute('timestamp');
                    $amount = DOMHandler::getFloatAttribute($item, 'summ');
                    // вычту оплаченное
                    $payedSingle = Table_payed_single::find()->where(['billId' => $billInfo->id, 'time' => $time])->all();
                    if (!empty($payedSingle)) {
                        foreach ($payedSingle as $singleItem) {
                            $amount -= $singleItem->summ;
                        }
                    }
                    $totalAmount[$time] = $amount;
                }
                SingleHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
            }
            $fines = Table_bill_fines::find()->where(['bill_id' => $billInfo])->all();
            if (!empty($fines)) {
                $totalAmount = 0;
                foreach ($fines as $item) {
                    $totalAmount += $item->start_summ;
                }
                // вычту оплаченное
                $payedFines = Table_payed_fines::find()->where(['transaction_id' => $t->id])->all();
                if (!empty($payedFines)) {
                    foreach ($payedFines as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                FinesHandler::handlePartialPayment($billInfo, $totalAmount, $cottageInfo, $t);
            }

            if ($billInfo->depositUsed > 0) {
                DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out', $t);
            }
            if ($billInfo->discount > 0) {
                DiscountHandler::registerDiscount($billInfo, $t);
            }
            if ($difference > 0) {
                $billInfo->toDeposit = CashHandler::toRubles($difference);
                DepositHandler::registerDeposit($billInfo, $cottageInfo, 'in', $t);
            }

            $transactionInfo->bounded_bill_id = $billInfo->id;
            $transactionInfo->save();
            $billInfo->save();
            if (!empty($additionalCottageInfo)) {
                $additionalCottageInfo->save();
            }
            $cottageInfo->save();
            if (!!$this->sendConfirmation) {
                Cloud::sendMessage($cottageInfo, 'Получен платёж', "Получен платёж на сумму " . CashHandler::toSmoothRubles($t->transactionSumm) . ". Благодарим за оплату.");
            }
            $transaction->commitTransaction();
            return ['status' => 1];

        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    public function manualCompare()
    {
        $billInfo = ComplexPayment::getBill($this->billId);
        $transactionInfo = TransactionsHandler::getTransaction($this->transactionId);
        if (empty($billInfo) || empty($transactionInfo)) {
            throw new ExceptionWithStatus('Не найден элемент транзакции', 2);
        }
        $transactionInfo->bounded_bill_id = $this->billId;
        $transactionInfo->save();
        return ['status' => 1, 'message' => "Транзакции успешно связаны"];
    }

}