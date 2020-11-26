<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 18:17
 */

namespace app\models;

use app\models\database\MailingSchedule;
use app\models\utils\BillContent;
use app\models\utils\DbTransaction;
use app\validators\CashValidator;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;


class Pay extends Model
{
    public string $billIdentificator; // Идентификатор платежа
    public float $rawSumm = 0; // фактическое количество наличных
    public float $totalSumm; // Общая сумма оплаты
    public float $fromDeposit = 0;
    public float $toDeposit = 0; // Начисление средств на депозит
    public float $realSumm = 0; // Фактическая сумма поступившив в кассу средств
    public int $changeToDeposit = 0; // зачислить сдачу на депозит
    public float $discount = 0;
    public bool $double = false;
    public int $customDate;
    public float $change = 0;
    public int $getCustomDate;
    public bool $sendConfirmation = true;

    public float $power = 0;
    public float $additionalPower = 0;
    public float $membership = 0;
    public float $additionalMembership = 0;
    public array $target;
    public array $additionalTarget;
    public array $single;
    public float $fines = 0;

    public float $payedBefore;
    public $billInfo;

    public const SCENARIO_PAY = 'pay';
    /**
     * @var Table_additional_cottages|Table_cottages
     */
    public $cottageInfo;
    /**
     * @var Table_additional_cottages
     */
    public Table_additional_cottages $additionalCottageInfo;
    /**
     * @var Table_bank_invoices
     */
    public Table_bank_invoices $bankTransaction;

    public string $bankTransactionId;

    /**
     * @param $payId
     * @param boolean $double
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function closeBill(string $payId, bool $double): array
    {
        // найду платёж
        if ($double) {
            $bill = Table_payment_bills_double::findOne($payId);
        } else {
            $bill = Table_payment_bills::findOne($payId);
        }
        if ($bill !== null) {
            // проверю, счёт должен быть частично оплачен
            if ($bill->isPartialPayed) {
                $bill->isPayed = 1;
                $bill->save();
                return ['status' => 1, 'message' => 'Счёт успешно закрыт.'];
            }
            throw new ExceptionWithStatus('Счёт должен быть частично оплачен', 2);
        }
        throw new ExceptionWithStatus('Счёт не найден', 3);
    }

    /**
     * Заново открываю счёт
     * @param $billId
     * @param $double
     * @return array
     */
    public static function reopenBill($billId, $double): array
    {
        $billInfo = ComplexPayment::getBill($billId, $double);
        if ($billInfo !== null) {
            // если используется сумма с депозита и счёт не оплачивался- спишу её
            // заморожу средства на депозите
            if ($billInfo->depositUsed > 0 && $billInfo->payedSumm === 0) {
                $cottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber . ($double ? '-a' : ''));
                $cottageInfo->deposit = CashHandler::toRubles(CashHandler::toRubles($cottageInfo->deposit) - $billInfo->depositUsed);
                $cottageInfo->save();
            }
            // если счёт открыт- пишу, что он открыт
            if ($billInfo->isPayed === 0) {
                return ['status' => 2, 'message' => 'Счёт ещё открыт!'];
            }
            $billInfo->isPayed = 0;
            $billInfo->save();
            return ['status' => 1, 'message' => 'Счёт успешно открыт заново!'];
        }
        return ['status' => 3, 'message' => 'Счёт не найден!'];
    }

    /**
     * @param $billId
     * @return bool
     */
    public static function isDoubleBill($billId): bool
    {
        // заменю букву А
        return (bool)substr_count(self::toLatin($billId), 'A');
    }

    public static function toLatin($number)
    {
        $input = ["А"];
        $replace = ["A"];
        return str_replace($input, $replace, mb_strtoupper($number));
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_PAY => ['billIdentificator', 'totalSumm', 'totalSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'changeToDeposit', 'change', 'payType', 'double', 'target', 'additionalTarget', 'membership', 'additionalMembership', 'power', 'additionalPower', 'single', 'customDate', 'getCustomDate', 'sendConfirmation', 'fines', 'bankTransactionId'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'changeToDeposit' => 'Зачислить сдачу на депозит',
            'rawSumm' => 'Сумма наличных',
            'toDeposit' => 'Сумма, зачисляемая на депозит',
            'payType' => 'Вариант оплаты',
            'sendConfirmation' => 'Отправить'
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['totalSumm', 'rawSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'change', 'changeToDeposit', 'membership', 'additionalMembership', 'power', 'additionalPower'], CashValidator::class],
            [['billIdentificator', 'totalSumm', 'rawSumm'], 'required', 'on' => self::SCENARIO_PAY],
            [['toDeposit'], 'required', 'when' => function () {
                return $this->changeToDeposit;
            }, 'whenClient' => "function () {return $('input#pay-changetodeposit').prop('checked');}"],
            ['changeToDeposit', 'in', 'range' => [1, 0]],
        ];
    }

    /**
     * @param $identificator
     * @param null $bankTransaction
     * @param false $double
     * @return bool
     * @throws ExceptionWithStatus
     */
    public function fillInfo($identificator, $bankTransaction = null, $double = false): bool
    {
        if (!$this->double) {
            $this->double = $double;
        } else {
            $double = true;
        }
        $this->billInfo = ComplexPayment::getBillInfo($identificator, $double);
        $billInfo = $this->billInfo['billInfo'];
        if ($billInfo->isPayed === 1) {
            throw new ExceptionWithStatus('Счёт уже оплачен!', 3);
        }
        $this->billIdentificator = $identificator;
        $this->totalSumm = $billInfo->totalSumm;
        $this->fromDeposit = $billInfo->depositUsed;
        $this->discount = $billInfo->discount;
        $this->payedBefore = $billInfo->payedSumm;
        if ($double) {
            $this->cottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber . '-a');
        } else {
            $this->cottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber);
            if ($this->cottageInfo->haveAdditional) {
                $this->additionalCottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber . '-a');
            }
        }
        if (!empty($bankTransaction)) {
            // найду транзакцию
            $this->bankTransaction = Table_bank_invoices::findOne($bankTransaction);
            if (empty($this->bankTransaction)) {
                throw new ExceptionWithStatus('Транзакция #' . $bankTransaction . ' не найдена.');
            }
        }
        return true;
    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function confirm(): ?array
    {
        $transaction = new DbTransaction();
        try {
            // обработаю дату платежа
            if (!empty($this->customDate)) {
                $paymentTime = TimeHandler::getCustomTimestamp($this->customDate);
            } else {
                $paymentTime = time();
            }
            // обработаю дату поступления средств ра счёт
            if (!empty($this->getCustomDate)) {
                $getTime = TimeHandler::getCustomTimestamp($this->getCustomDate);
            } else {
                $getTime = time();
            }
            $additionalCottageInfo = null;
            // найду информацию о счёте
            if ($this->double) {
                $billInfo = Table_payment_bills_double::findOne($this->billIdentificator);
                if($billInfo !== null){
                    $cottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber . '-a');
                }
            } else {
                $billInfo = Table_payment_bills::findOne($this->billIdentificator);
                $cottageInfo = Cottage::getCottageByLiteral($billInfo->cottageNumber);
                if ($cottageInfo->haveAdditional) {
                    $additionalCottageInfo = Cottage::getCottageByLiteral($cottageInfo->cottageNumber . '-a');
                }
            }

            $billContentInfo = new BillContent($billInfo);

            // проверю тип оплаты
            $payType = null;
            // Расчитаю необходимую для оплаты сумму
            if ($billInfo->isPartialPayed) {
                $neededSumm = CashHandler::toRubles($billInfo->totalSumm - $billInfo->discount - $billInfo->depositUsed - $billInfo->payedSumm);
                if ($this->rawSumm >= $neededSumm) {
                    $payType = 'partial-finish';
                } else {
                    $payType = 'partial';
                }
            } else {
                $neededSumm = CashHandler::toRubles(CashHandler::toRubles($billInfo->totalSumm) - CashHandler::toRubles($billInfo->discount) - CashHandler::toRubles($billInfo->depositUsed));
                if ($this->rawSumm >= $neededSumm) {
                    $payType = 'full';
                } else {
                    $payType = 'partial';
                }
            }

            $billInfo->payedSumm += CashHandler::toRubles($this->rawSumm);

            // создам шаблон денежной транзакции, чтобы использовать его номер в отчётах
            if ($this->double) {
                $billTransaction = new Table_transactions_double();
            } else {
                $billTransaction = new Table_transactions();
            }
            if ($this->double) {
                $billTransaction->cottageNumber = $cottageInfo->masterId;
            } else {
                $billTransaction->cottageNumber = $cottageInfo->cottageNumber;
            }
            $billTransaction->payDate = $paymentTime;
            $billTransaction->bankDate = $getTime;
            $billTransaction->billId = $this->billIdentificator;
            $billTransaction->transactionDate = time();
            $billTransaction->transactionType = 'no-cash';
            $billTransaction->transactionSumm = CashHandler::toRubles($this->rawSumm);
            $billTransaction->transactionWay = 'in';
            $billTransaction->usedDeposit = 0;
            $billTransaction->gainedDeposit = 0;
            $billTransaction->save();
            if ($payType === 'partial') {
                $billTransaction->gainedDeposit = 0;
                $billTransaction->partial = 1;
                if (!$billInfo->isPartialPayed) {
                    $billTransaction->usedDeposit = $billInfo->depositUsed;
                }

                // проверю, сумма внесённых средств должна соответствовать раскладке по категориям
                // если это первый платёж, учитываю скидку и использованный депозит
                $gainedSumm = CashHandler::toRubles($this->rawSumm);
                if (!$billInfo->isPartialPayed) {
                    $gainedSumm += CashHandler::toRubles($billInfo->depositUsed);
                    $gainedSumm += CashHandler::toRubles($billInfo->discount);
                    $gainedSumm = CashHandler::toRubles($gainedSumm);

                    $fromDeposit = CashHandler::toRubles($billInfo->depositUsed);
                    if ($fromDeposit > 0) {
                        DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out', $billTransaction);
                        $billTransaction->usedDeposit = $fromDeposit;
                    } else {
                        $billTransaction->usedDeposit = 0;
                    }
                    if ($billInfo->discount > 0) {
                        DiscountHandler::registerDiscount($billInfo, $billTransaction);
                    }
                }
                $neededSumm = 0;
                $neededSumm += CashHandler::toRubles($this->power);
                $neededSumm += CashHandler::toRubles($this->additionalPower);
                $neededSumm += CashHandler::toRubles($this->membership);
                $neededSumm += CashHandler::toRubles($this->additionalMembership);
                $neededSumm += CashHandler::toRubles($this->fines);
                if (!empty($this->target)) {
                    foreach ($this->target as $item) {
                        if (!empty($item)) {
                            $neededSumm += CashHandler::toRubles($item);
                        }
                    }
                }
                if (!empty($this->additionalTarget)) {
                    foreach ($this->additionalTarget as $item) {
                        if (!empty($item)) {
                            $neededSumm += CashHandler::toRubles($item);
                        }
                    }
                }
                if (!empty($this->single)) {
                    foreach ($this->single as $item) {
                        if (!empty($item)) {
                            $neededSumm += CashHandler::toRubles($item);
                        }
                    }
                }
                if ($gainedSumm !== $neededSumm) {
                    throw new ExceptionWithStatus('Не сходится сумма');
                }

                // отмечу счёт частично оплаченным
                $billInfo->isPartialPayed = 1;
            } elseif ($payType === 'full') {
                $billTransaction->partial = 0;
                $billTransaction->usedDeposit = $billInfo->depositUsed;
                $billTransaction->gainedDeposit = $this->toDeposit;

                // отмечу счёт полностью оплаченным
                $billInfo->isPayed = 1;
                $billInfo->isPartialPayed = 0;
                $billInfo->paymentTime = $paymentTime;
            } elseif ($payType === 'partial-finish') {
                $billTransaction->partial = 1;
                $billTransaction->usedDeposit = 0;
                $billTransaction->gainedDeposit = $this->toDeposit;
                if ($this->toDeposit > 0) {
                    $billInfo->toDeposit = CashHandler::toRubles($this->toDeposit);
                    DepositHandler::registerDeposit($billInfo, $cottageInfo, 'in', $billTransaction);
                }
                // отмечу счёт полностью оплаченным
                $billInfo->isPayed = 1;
                $billInfo->isPartialPayed = 0;
                $billInfo->paymentTime = $paymentTime;
            }
            $billTransaction->save();
            if ($payType === 'full') {
                // проверю сумму начисления на депозит
                $neededDeposit = CashHandler::toRubles(CashHandler::toRubles($this->rawSumm) - CashHandler::toRubles($neededSumm));
                if ($neededDeposit !== $this->toDeposit) {
                    throw new ExceptionWithStatus('Не сходится сумма начисления на депозит');
                }

                // обработаю депозит и скидки
                if ($billInfo->depositUsed > 0) {
                    DepositHandler::registerDeposit($billInfo, $cottageInfo, 'out', $billTransaction);
                }
                if ($this->toDeposit > 0) {
                    $billInfo->toDeposit = CashHandler::toRubles($this->toDeposit);
                    DepositHandler::registerDeposit($billInfo, $cottageInfo, 'in', $billTransaction);
                }
                if ($billInfo->discount > 0) {
                    DiscountHandler::registerDiscount($billInfo, $billTransaction);
                }
            }
            if ($this->power > 0) {
                // есть бюджет на оплату электроэнергии, распределю его по периодам
                $sum = $this->power;
                if(!empty($billContentInfo->powerEntities)){
                    foreach ($billContentInfo->powerEntities as $powerEntity) {
                        $leftToPay = $powerEntity->getLeftToPay();
                        // оплачу счёт с учётом того, что ранее он уже мог быть оплачен
                        if(!$powerEntity->isAdditional && $leftToPay > 0 && $sum > 0){
                            if($sum < $leftToPay){
                                $leftToPay = $sum;
                                $sum = 0;
                            }
                            else{
                                $sum -= $leftToPay;
                            }
                            // зарегистрирую платёж
                            PowerHandler::insertSinglePayment(
                                ($powerEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                                $billInfo,
                                $billTransaction,
                                $powerEntity->date,
                                $leftToPay
                            );
                        }
                    }
                    if($sum > 0){
                        // проверю, должен остаться 0, если нет- вызову ошибку
                        throw new InvalidArgumentException("Не сходится сумма платежа за электроэнергию");
                    }
                }
            }
            if ($this->additionalPower > 0) {
                $sum = $this->additionalPower;
                if(!empty($billContentInfo->powerEntities)){
                    foreach ($billContentInfo->powerEntities as $powerEntity) {
                        $leftToPay = $powerEntity->getLeftToPay();
                        // оплачу счёт с учётом того, что ранее он уже мог быть оплачен
                        if($powerEntity->isAdditional && $leftToPay > 0 && $sum > 0){
                            if($sum < $leftToPay){
                                $leftToPay = $sum;
                                $sum = 0;
                            }
                            else{
                                $sum -= $leftToPay;
                            }
                            // зарегистрирую платёж
                            PowerHandler::insertSinglePayment(
                                ($powerEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                                $billInfo,
                                $billTransaction,
                                $powerEntity->date,
                                $leftToPay
                            );
                        }
                    }
                    // проверю, должен остаться 0, если нет- вызову ошибку
                    if($sum > 0){
                        // проверю, должен остаться 0, если нет- вызову ошибку
                        throw new InvalidArgumentException("Не сходится сумма платежа за электроэнергию");
                    }
                }
            }
            if ($this->membership > 0) {
                // есть бюджет на оплату электроэнергии, распределю его по периодам
                $sum = $this->membership;
                if(!empty($billContentInfo->membershipEntities)){
                    foreach ($billContentInfo->membershipEntities as $membershipEntity) {
                        $leftToPay = $membershipEntity->getLeftToPay();
                        // оплачу счёт с учётом того, что ранее он уже мог быть оплачен
                        if(!$membershipEntity->isAdditional && $leftToPay > 0 && $sum > 0){
                            if($sum < $leftToPay){
                                $leftToPay = $sum;
                                $sum = 0;
                            }
                            else{
                                $sum -= $leftToPay;
                            }
                            // зарегистрирую платёж
                            MembershipHandler::insertSinglePayment(
                                ($membershipEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                                $billInfo,
                                $billTransaction,
                                $membershipEntity->date,
                                $leftToPay
                            );
                        }
                    }
                    if($sum > 0){
                        // проверю, должен остаться 0, если нет- вызову ошибку
                        throw new InvalidArgumentException("Не сходится сумма платежа за членские");
                    }
                }
            }
            if ($this->additionalMembership > 0) {
                // есть бюджет на оплату электроэнергии, распределю его по периодам
                $sum = $this->additionalMembership;
                if(!empty($billContentInfo->membershipEntities)){
                    foreach ($billContentInfo->membershipEntities as $membershipEntity) {
                        $leftToPay = $membershipEntity->getLeftToPay();
                        // оплачу счёт с учётом того, что ранее он уже мог быть оплачен
                        if($membershipEntity->isAdditional && $leftToPay > 0 && $sum > 0){
                            if($sum < $leftToPay){
                                $leftToPay = $sum;
                                $sum = 0;
                            }
                            else{
                                $sum -= $leftToPay;
                            }
                            // зарегистрирую платёж
                            MembershipHandler::insertSinglePayment(
                                ($membershipEntity->isAdditional ? $additionalCottageInfo : $cottageInfo),
                                $billInfo,
                                $billTransaction,
                                $membershipEntity->date,
                                $leftToPay
                            );
                        }
                    }
                    // проверю, должен остаться 0, если нет- вызову ошибку
                    throw new InvalidArgumentException("Не сходится сумма платежа за членские");
                }

            }
            if (!empty($this->target)) {
                TargetHandler::handlePartialPayment($billInfo, $this->target, $cottageInfo, $billTransaction);
            }
            if (!empty($this->additionalTarget)) {
                if ($this->double) {
                    TargetHandler::handlePartialPayment($billInfo, $this->additionalTarget, $cottageInfo, $billTransaction);
                } else {
                    TargetHandler::handlePartialPayment($billInfo, $this->additionalTarget, $additionalCottageInfo, $billTransaction);
                }
            }
            if (!empty($this->single)) {
                SingleHandler::handlePartialPayment($billInfo, $this->single, $cottageInfo, $billTransaction);
            }
            if ($this->fines > 0) {
                FinesHandler::handlePartialPayment($billInfo, $this->fines, $billTransaction);
            }
            // регистрирую транзакцию
            $billTransaction->transactionSumm = CashHandler::toRubles($this->rawSumm);
            // если используются средства с депозита и это первый платёж по данному счёту- списываю средства
            $billTransaction->transactionReason = 'Частичная оплата по счёту № ' . $billInfo->id;
            $billTransaction->save();
            if ($billInfo->payedSumm === $billInfo->totalSumm) {
                $billInfo->isPartialPayed = 0;
                $billInfo->isPayed = 1;
            }
            $billInfo->save();
            $cottageInfo->save();
            if (!empty($this->bankTransactionId)) {
                $bankTransaction = Table_bank_invoices::findOne($this->bankTransactionId);
                if($bankTransaction !== null){
                    $billTransaction->bankDate = TimeHandler::getTimestampFromBank($bankTransaction->pay_date, $bankTransaction->pay_time);
                    if (!empty($bankTransaction->real_pay_date)) {
                        $billTransaction->payDate = TimeHandler::getTimestampFromBank($bankTransaction->real_pay_date, $bankTransaction->pay_time);
                    } else {
                        $billTransaction->payDate = $billTransaction->bankDate;
                    }
                    $bankTransaction->bounded_bill_id = $billInfo->id;
                    $bankTransaction->save();
                }
            }
            if ($additionalCottageInfo !== null) {
                $additionalCottageInfo->save();
            }
            if ($this->sendConfirmation) {
                MailingSchedule::addSingleMailing($cottageInfo, 'Получен платёж', 'Получен платёж на сумму ' . CashHandler::toSmoothRubles($billTransaction->transactionSumm) . '. Благодарим за оплату.');
            }
            $transaction->commitTransaction();
            return ['status' => 1, 'message' => 'Счёт успешно оплачен'];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }

    }

    public static function getUnpayedBillId($cottageNumber)
    {
        return Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => false])->select('id')->one();
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @return Table_payment_bills|Table_payment_bills_double
     */
    public static function getUnpayedBill($cottage)
    {
        if (Cottage::isMain($cottage)) {
            return Table_payment_bills::find()->where(['cottageNumber' => $cottage->cottageNumber, 'isPayed' => false])->select('creationTime')->one();
        }

        return Table_payment_bills_double::find()->where(['cottageNumber' => $cottage->masterId, 'isPayed' => false])->select('creationTime')->one();
    }

}