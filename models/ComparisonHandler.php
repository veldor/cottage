<?php


namespace app\models;


use Exception;
use Yii;
use yii\base\Model;

class ComparisonHandler extends Model
{

    public $billId;
    public $transactionId;

    const SCENARIO_COMPARISON = 'comparison';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_COMPARISON => ['billId', 'transactionId'],
        ];
    }

    public function compare()
    {
        // найду платёж
        $billInfo = ComplexPayment::getBill($this->billId);
        $fullBillInfo = ComplexPayment::getBillInfo($billInfo);
        $transactionInfo = TransactionsHandler::getTransaction($this->transactionId);
        if (empty($billInfo) || empty($transactionInfo)) {
            throw new ExceptionWithStatus('Не найден элемент транзакции', 2);
        }
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber);
        // обработаю транзакцию
        $billSumm = CashHandler::toRubles($billInfo->totalSumm);
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        $difference = $transactionSumm - $billSumm;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $billInfo->paymentTime = TimeHandler::getTimestampFromBank($transactionInfo->pay_date, $transactionInfo->pay_time);
            $billInfo->isPayed = true;
            $billInfo->payedSumm = $transactionSumm;

            // обработаю отдельные категории

            $additionalCottageInfo = null;
            if(!empty($cottageInfo->haveAdditional)) {
                $additionalCottageInfo = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
            }

            if (!empty($fullBillInfo['paymentContent']['power'])) {
                PowerHandler::registerPayment($cottageInfo, $billInfo,$fullBillInfo['paymentContent']['power']);
            }
            if (!empty($fullBillInfo['paymentContent']['additionalPower'])) {
                PowerHandler::registerPayment($additionalCottageInfo, $billInfo, $fullBillInfo['paymentContent']['additionalPower'], true);
            }
            if (!empty($fullBillInfo['paymentContent']['membership'])) {
                MembershipHandler::registerPayment($cottageInfo, $billInfo, $fullBillInfo['paymentContent']['membership']);
            }
            if (!empty($fullBillInfo['paymentContent']['additionalMembership'])) {
                MembershipHandler::registerPayment($additionalCottageInfo, $billInfo, $fullBillInfo['paymentContent']['additionalMembership'], true);
            }
            if (!empty($fullBillInfo['paymentContent']['target'])) {
                TargetHandler::registerPayment($cottageInfo, $billInfo, $fullBillInfo['paymentContent']['target']);
            }
            if (!empty($fullBillInfo['paymentContent']['additionalTarget'])) {
                TargetHandler::registerPayment($additionalCottageInfo, $billInfo, $fullBillInfo['paymentContent']['additionalTarget'], true);
            }
            if (!empty($fullBillInfo['paymentContent']['single'])) {
                SingleHandler::registerPayment($cottageInfo, $billInfo, $fullBillInfo['paymentContent']['single']);
            }

            if ($billInfo->depositUsed > 0) {
                DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out');
            }
            if ($billInfo->discount > 0) {
                DiscountHandler::registerDiscount($billInfo);
            }
            if ($difference > 0) {
                // зачислю сдачу на депозит участка
                DepositHandler::registerDeposit($billInfo, $cottageInfo, 'in');
            }
            // создам транзакцию
            $t = new Table_transactions();
            $t->cottageNumber = $billInfo->cottageNumber;
            $t->billId = $billInfo->id;
            $t->transactionDate = $billInfo->paymentTime;
            $t->transactionType = 'no-cash';
            $t->transactionSumm = $transactionSumm;
            $t->gainedDeposit = $difference;
            $t->usedDeposit = $billInfo->depositUsed;
            $t->transactionWay = 'in';
            $t->partial = 0;
            $t->transactionReason = 'Оплата';
            $t->save();

            $transactionInfo->bounded_bill_id = $billInfo->id;
            $transactionInfo->save();
            $billInfo->save();
            $cottageInfo->save();
            $transaction->commit();
            return ['status' => 1];
        }
        catch (Exception $e){
            $transaction->rollBack();
            throw $e;
        }
    }

}