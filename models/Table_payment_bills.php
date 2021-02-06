<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.09.2018
 * Time: 19:18
 */

namespace app\models;

use app\models\tables\Table_bill_fines;
use app\models\tables\Table_payed_fines;
use app\models\utils\BillContent;
use app\models\utils\DbTransaction;
use Exception;
use yii\db\ActiveRecord;

/**
 * Class Table_payment_bills
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $cottageNumber [int(5) unsigned]
 * @property string $bill_content
 * @property bool $isPayed [tinyint(1)]
 * @property int $creationTime [int(20) unsigned]
 * @property int $paymentTime [int(20) unsigned]
 * @property string $depositUsed [float unsigned]
 * @property string $totalSumm [float unsigned]
 * @property string $payedSumm [float unsigned]
 * @property string $discount [float unsigned]
 * @property string $discountReason
 * @property string $toDeposit [float unsigned]
 * @property bool $isPartialPayed [tinyint(4)]
 * @property bool $isMessageSend [tinyint(1)]  Уведомление отправлено
 * @property bool $isInvoicePrinted [tinyint(1)]  Квитанция распечатана
 * @property string $payer_personals [varchar(255)]  Имя плательщика
 */
class Table_payment_bills extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'payment_bills';
    }

    /**
     * @param array $billId идентификатор платежа
     * @param bool $isDouble флаг платежа дополнительного участка
     * @return Table_payment_bills|null Экземпляр счёта при наличии
     */
    public static function getBill(int $billId, bool $isDouble): ?Table_payment_bills
    {
        if ($isDouble) {
            return Table_payment_bills_double::findOne($billId);
        }
        return self::findOne($billId);
    }

    private static function countLeftToPay(Table_payment_bills $billInfo): float
    {
        return ComplexPayment::getBillInfo($billInfo)['summToPay'];
    }

    /**
     * @param Table_cottages $cottageInfo
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public function acceptFullPayFromDeposit(Table_cottages $cottageInfo): array
    {

        // проверю, не является ли участок дополнительным
        $isDouble = !$cottageInfo->isMain();
        // найду платёж
        $billInfo = ComplexPayment::getBill($this->id, $isDouble);
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
            $t->transactionSumm = $requiredAmount;
            $t->usedDeposit = $requiredAmount;
            $t->transactionWay = 'in';
            $t->transactionDate = time();
            $t->gainedDeposit = 0;
            $t->payDate = time();
            $t->bankDate = time();
            $t->partial = 0;
            $t->transactionReason = 'Полная оплата с депозита по счёту ' . $billInfo->id;
            $t->save();

            $billInfo->depositUsed = $requiredAmount;

            $billInfo->paymentTime = time();
            $billInfo->isPayed = true;
            $billInfo->payedSumm = $requiredAmount;

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
                            ($targetEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
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
                DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out', $t, true);
            }
            if ($billInfo->discount > 0) {
                DiscountHandler::registerDiscount($billInfo, $t);
            }
            $billInfo->save();
            if ($additionalCottageInfo !== null) {
                $additionalCottageInfo->save();
            }
            $cottageInfo->save();
            $transaction->commitTransaction();
            return ['status' => 1];

        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }
}