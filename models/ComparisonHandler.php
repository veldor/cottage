<?php


namespace app\models;


use app\models\database\MailingSchedule;
use app\models\tables\Table_bill_fines;
use app\models\tables\Table_payed_fines;
use app\models\utils\BillContent;
use app\models\utils\DbTransaction;
use Exception;
use Yii;
use yii\base\Model;

class ComparisonHandler extends Model
{

    public const SCENARIO_MANUAL_COMPARISON = 'manual comparison';
    public string $billId;
    public int $transactionId;
    public string $sendConfirmation = 'false';

    public const SCENARIO_COMPARISON = 'comparison';

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function insertToDeposit(): array
    {
        $bankId = Yii::$app->request->post('operationId');
        $cottageNumber = Yii::$app->request->post('cottageNumber');
        $bankTransaction = Table_bank_invoices::findOne($bankId);
        if ($bankTransaction !== null) {
            $cottage = Cottage::getCottageByLiteral($cottageNumber);
            if ($cottage !== null) {
                $transaction = new DbTransaction();
                // закрою банковскую транзакцию
                $bankTransaction->bounded_bill_id = 0;
                $bankTransaction->save();
                // создам платёжную транзакцию
                $payTransaction = new Table_transactions([
                    'cottageNumber' => $cottage->getCottageNumber(),
                    'bankDate' => time(),
                    'payDate' => time(),
                    'transactionDate' => time(),
                    'transactionSumm' => CashHandler::toRubles($bankTransaction->payment_summ),
                    'gainedDeposit' => CashHandler::toRubles($bankTransaction->payment_summ),
                    'transactionType' => 'cash',
                    'transactionWay' => 'in',
                    'usedDeposit' => 0
                ]);
                $payTransaction->save();
                // зачислю на депозит
                $depositIo = new Table_deposit_io([
                    'cottageNumber' => $cottage->getCottageNumber(),
                    'summBefore' => $cottage->deposit,
                    'summ' => CashHandler::toRubles($bankTransaction->payment_summ),
                    'summAfter' => CashHandler::toRubles($cottage->deposit + CashHandler::toRubles($bankTransaction->payment_summ)),
                    'transactionId' => $payTransaction->id,
                    'actionDate' => time()
                ]);
                $depositIo->save();
                $cottage->deposit += CashHandler::toRubles($bankTransaction->payment_summ);
                $cottage->save();
                $transaction->commitTransaction();
                return ['status' => 1, 'message' => 'Средства успешно зачислены на депозит!'];
            }
            return ['status' => 2, 'message' => 'Неверный номер участка!'];
        }
        return ['status' => 2, 'message' => 'Транзакция не найдена!'];
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_COMPARISON => ['billId', 'transactionId', 'sendConfirmation'],
            self::SCENARIO_MANUAL_COMPARISON => ['billId', 'transactionId', 'sendConfirmation'],
        ];
    }

    /**
     * @return array|null
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function compare(): ?array
    {
        // проверю, не является ли участок дополнительным
        $isDouble = Pay::isDoubleBill($this->billId);
        // найду платёж
        $billInfo = ComplexPayment::getBill($this->billId, $isDouble);
        $transactionInfo = TransactionsHandler::getTransaction($this->transactionId);
        if ($billInfo === null || $transactionInfo === null) {
            throw new ExceptionWithStatus('Не найден элемент транзакции', 2);
        }
        // разберу платёж
        $billContentInfo = new BillContent($billInfo);
        // получу необходимую для оплаты сумму
        $requiredAmount = $billContentInfo->getRequiredSum();
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber, $isDouble);
        $additionalCottageInfo = null;
        if (!$isDouble && $cottageInfo->haveAdditional) {
            $additionalCottageInfo = Cottage::getCottageByLiteral($cottageInfo->cottageNumber . '-a');
            if ($additionalCottageInfo->hasDifferentOwner) {
                $additionalCottageInfo = null;
            }
        }
        else{
            $additionalCottageInfo = $cottageInfo;
        }
        // обработаю транзакцию
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        $difference = CashHandler::toRubles($transactionSumm) - CashHandler::toRubles($requiredAmount);
        if ($difference < 0) {
            throw new ExceptionWithStatus('Частичная оплата счёта тут не обрабатывается!');
        }
        $difference = CashHandler::toRubles($difference);
        $transaction = new DbTransaction();

        try {
            if ($isDouble) {
                // создам транзакцию
                $t = new Table_transactions_double();
            } else {
// создам транзакцию
                $t = new Table_transactions();
            }
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
            // электричество
            if (!empty($billContentInfo->powerEntities)) {
                foreach ($billContentInfo->powerEntities as $powerEntity) {
                    // проверю, оплачивалась ли раньше часть суммы.
                    $leftToPay = CashHandler::sumFromInt($powerEntity->sum) - $powerEntity->getPayedOutside();
                    if ($leftToPay > 0) {
                        // зарегистрирую оплату
                        PowerHandler::insertSinglePayment(
                            ($powerEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                            $billInfo,
                            $t,
                            $powerEntity->date,
                            $leftToPay
                        );
                    }
                }
            }

            // членские
            if (!empty($billContentInfo->membershipEntities)) {
                foreach ($billContentInfo->membershipEntities as $membershipEntity) {
                    // проверю, оплачивалась ли раньше часть суммы.
                    $leftToPay = CashHandler::sumFromInt($membershipEntity->sum) - $membershipEntity->getPayedOutside() - $membershipEntity->getPayedInside();
                    if ($leftToPay > 0) {
                        // зарегистрирую оплату
                        MembershipHandler::insertSinglePayment(
                            ($membershipEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                            $billInfo,
                            $t,
                            $membershipEntity->date,
                            $leftToPay
                        );
                    }
                }
            }


            // целевые
            if (!empty($billContentInfo->targetEntities)) {
                foreach ($billContentInfo->targetEntities as $targetEntity) {
                    // проверю, оплачивалась ли раньше часть суммы.
                    $shift = $targetEntity->totalSum - $targetEntity->sum;
                    $leftToPay = CashHandler::sumFromInt($targetEntity->sum) - $targetEntity->getPayedOutside() - $targetEntity->getPayedInside() + CashHandler::sumFromInt($shift);
                    if ($leftToPay > 0) {
                        // зарегистрирую оплату
                        TargetHandler::insertSinglePayment(
                            (!$isDouble && $targetEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                            $billInfo,
                            $targetEntity->date,
                            $leftToPay,
                            $t
                        );
                    }
                }
            }
            // разовые
            if (!empty($billContentInfo->singleEntities)) {
                foreach ($billContentInfo->singleEntities as $singleEntity) {
                    // проверю, оплачивалась ли раньше часть суммы.
                    $leftToPay = CashHandler::sumFromInt($singleEntity->sum) - $singleEntity->getPayedOutside() - $singleEntity->getPayedInside();
                    if ($leftToPay > 0) {
                        // зарегистрирую оплату
                        SingleHandler::insertSinglePayment(
                            ($singleEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                            $billInfo,
                            $singleEntity->date,
                            $leftToPay,
                            $t
                        );
                    }
                }
            }

            $fines = Table_bill_fines::find()->where(['bill_id' => $billInfo->id])->all();
            if (!empty($fines)) {
                $totalAmount = 0;
                /** @var Table_bill_fines $item */
                foreach ($fines as $item) {
                    $totalAmount += $item->start_summ;
                }
                // вычту оплаченное
                $payedFines = Table_payed_fines::find()->where(['transaction_id' => $t->id])->all();
                if (!empty($payedFines)) {
                    /** @var Table_payed_fines $item */
                    foreach ($payedFines as $item) {
                        $totalAmount -= $item->summ;
                    }
                }
                FinesHandler::handlePartialPayment($billInfo, $totalAmount, $t);
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
            if ($additionalCottageInfo !== null) {
                $additionalCottageInfo->save();
            }
            $cottageInfo->save();
            $transaction->commitTransaction();
            if ($this->sendConfirmation === 'true') {
                return MailingSchedule::addSingleMailing($cottageInfo, 'Получен платёж', 'Получен платёж на сумму ' . CashHandler::toSmoothRubles($t->transactionSumm) . '. Благодарим за оплату.');
            }
            return ['status' => 1];

        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public function manualCompare(): array
    {
        $billInfo = ComplexPayment::getBill($this->billId);
        $transactionInfo = TransactionsHandler::getTransaction($this->transactionId);
        if ($billInfo === null || $transactionInfo === null) {
            throw new ExceptionWithStatus('Не найден элемент транзакции', 2);
        }
        $transactionInfo->bounded_bill_id = $this->billId;
        $transactionInfo->save();
        return ['status' => 1, 'message' => 'Транзакции успешно связаны'];
    }

}