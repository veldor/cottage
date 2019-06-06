<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 18:17
 */

namespace app\models;

use app\validators\CashValidator;
use Exception;
use Yii;
use yii\base\Model;


class Pay extends Model {
	public $billIdentificator; // Идентификатор платежа
	public $rawSumm = 0; // фактическое количество наличных
	public $totalSumm; // Общая сумма оплаты
	public $fromDeposit = 0;
	public $toDeposit = 0; // Начисление средств на депозит
	public $realSumm; // Фактическая сумма поступившив в кассу средств
	public $changeToDeposit = 0; // зачислить сдачу на депозин
	public $change = 0;
	public $discount = 0;
	public $payType;// тип оплаты: наличный\безналичный
	public $payWholeness;// целостность оплаты: полная\частичная
    public $double;

    public $power = 0;
    public $additionalPower = 0;
    public $membership = 0;
    public $additionalMembership = 0;
    public $target = 0;
    public $additionalTarget = 0;
    public $single = 0;

    public $payedBefore;
	/**
	 * @var $billInfo Table_payment_bills
	 */
	public $billInfo;

	const SCENARIO_PAY = 'pay';

    /**
     * @param $payId
     * @param boolean $double
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function closeBill($payId, $double)
    {
        // найду платёж
        if($double){
            $bill = Table_payment_bills_double::findOne($payId);
        }
        else{
            $bill = Table_payment_bills::findOne($payId);
        }
        if(!empty($bill)){
            // проверю, счёт должен быть частично оплачен
            if($bill->isPartialPayed){
                $bill->isPayed = 1;
                $bill->save();
                return ['status' => 1, 'message' => 'Счёт успешно закрыт.'];
            }
            else{
                throw new ExceptionWithStatus('Счёт должен быть частично оплачен', 2);
            }
        }
        throw new ExceptionWithStatus('Счёт не найден', 3);
    }

    public function scenarios(): array
	{
		return [
			self::SCENARIO_PAY => ['billIdentificator', 'totalSumm', 'totalSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'changeToDeposit', 'change', 'payType', 'payWholeness', 'double', 'target', 'additionalTarget', 'membership', 'additionalMembership', 'power', 'additionalPower', 'single'],
		];
	}

	public function attributeLabels(): array
	{
		return [
			'changeToDeposit' => 'Зачислить сдачу на депозит',
			'rawSumm' => 'Сумма наличных',
			'toDeposit' => 'Сумма, зачисляемая на депозит',
			'payType' => 'Вариант оплаты',
			'payWholeness' => 'Целостность оплаты',
		];
	}

	/**
	 * @return array
	 */
	public function rules(): array
	{
		return [
			[['totalSumm', 'rawSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'change', 'changeToDeposit', 'target', 'additionalTarget', 'membership', 'additionalMembership', 'power', 'additionalPower', 'single'], CashValidator::class],
			[['billIdentificator', 'totalSumm', 'rawSumm', 'change', 'payType', 'payWholeness'], 'required', 'on' => self::SCENARIO_PAY],
			[['toDeposit'], 'required', 'when' => function () {
				return $this->changeToDeposit;
			}, 'whenClient' => "function () {return $('input#pay-changetodeposit').prop('checked');}"],
			['changeToDeposit', 'in', 'range' => [1, 0]],
			['payType', 'in', 'range' => ['cash', 'cashless']],
			['payWholeness', 'in', 'range' => ['full', 'partial']],
			['toDeposit', 'checkToDeposit'],
			['rawSumm', 'checkRawSumm'],
		];
	}

	public function checkRawSumm($attribute){
	    if($this->payWholeness === 'full'){
            $totalSumm = CashHandler::rublesMath($this->totalSumm - $this->fromDeposit - $this->discount - $this->payedBefore + $this->toDeposit);
            if(CashHandler::rublesMore($totalSumm, $this->$attribute)){
                $this->addError($attribute, "Сумма наличных не может быть меньше суммы к оплате");
            }
        }
    }

	public function checkToDeposit($attribute)
	{
	    if($this->payWholeness === 'full'){
            // сумма, зачисляемая на депозит не должна превышать сумму потенциальной сдачи
            $totalSumm =CashHandler::rublesMath($this->totalSumm - $this->fromDeposit - $this->discount) ;
            $changeSumm = CashHandler::rublesMath($this->rawSumm - $totalSumm + $this->payedBefore);

            if (CashHandler::rublesMore($this->$attribute, $changeSumm)) {
                $this->addError($attribute, 'Сумма слишком велика');
            }
            if (!CashHandler::rublesComparison(($this->$attribute + $this->change), $changeSumm)) {
                $this->addError($attribute, 'Не сходится сумма сдачи и зачисления (' . $changeSumm . ' != ' . ($this->$attribute + $this->change)  . ')');
            }
        }
	}

	public function fillInfo($identificator, $double = false): bool
	{
	    if(!$this->double){
            $this->double = $double;
        }
	    else{
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
		return true;
	}

	public function confirm()
	{

        $cottageInfo = $this->billInfo['cottageInfo'];
        $additionalCottageInfo = null;
        if($this->double){
            $additionalCottageInfo = $this->billInfo['cottageInfo'];
        }
        elseif(!empty($cottageInfo->haveAdditional)) {
            $additionalCottageInfo = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
        }


        if($this->payWholeness=== 'full' && $this->billInfo['billInfo']->isPartialPayed){
            // завершающая оплата
            $this->payWholeness = 'partial-finish';
        }
	    if($this->payWholeness === 'partial'){
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
	        try{
	            $paymentTime = time();
	            $billInfo = $this->billInfo['billInfo'];
	            $cottageInfo = $this->billInfo['cottageInfo'];
                // проверю, сумма внесённых средств должна соответствовать раскладке по категориям
                if($this->rawSumm != ($this->power + $this->additionalPower + $this->membership + $this->additionalMembership + $this->target + $this->additionalTarget + $this->single)){
                    $this->addError('rawSumm', 'Распределены не все средства');
                }
                // буду работать с xml счёта
                $dom = new DOMHandler($this->billInfo['billInfo']->bill_content);
                // дальше, отмечу оплаченными периоды, на которые хватает средств
                if($this->power > 0){
                    PowerHandler::handlePartialPayment($dom, $this->power, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->additionalPower > 0){
                    PowerHandler::handlePartialPayment($dom, $this->additionalPower, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->membership > 0){
                    MembershipHandler::handlePartialPayment($dom, $this->membership, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->additionalMembership > 0){
                    MembershipHandler::handlePartialPayment($dom, $this->additionalMembership, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->target > 0){
                    TargetHandler::handlePartialPayment($dom, $this->target, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->additionalTarget > 0){
                    TargetHandler::handlePartialPayment($dom, $this->additionalTarget, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if($this->single){
                    SingleHandler::handlePartialPayment($dom, $this->single, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                $previousPayed = $billInfo->isPartialPayed;
                $billInfo->isPartialPayed = 1;
                $billInfo->payedSumm += $this->rawSumm;
                // сохраню изменения в xml платежа
                $billInfo->bill_content = $dom->save();
                if(!empty($additionalCottageInfo)){
                    $additionalCottageInfo->save();
                }
                // регистрирую транзакцию
                // проверю, не является ли участок дополнительным с вторым владельцем
                if($this->double){
                    $billTransaction = new Table_transactions_double();
                    $billTransaction->cottageNumber = $this->billInfo['cottageInfo']->masterId;
                }
                else{
                    $billTransaction = new Table_transactions();
                    $billTransaction->cottageNumber = $this->billInfo['cottageInfo']->cottageNumber;
                }
                $billTransaction->billId = $billInfo->id;
                $billTransaction->transactionDate = $paymentTime;
                // если используются средства с депозита и это первый платёж по данному счёту- списываю средства
                $fromDeposit = $this->fromDeposit;
                if($fromDeposit > 0 && $previousPayed === 0){
                    DepositHandler::registerDeposit($billInfo, $this->billInfo['cottageInfo'], 'out');
                    $billTransaction->usedDeposit = $fromDeposit;
                }
                else{
                    $billTransaction->usedDeposit = 0;
                }
                $billTransaction->gainedDeposit = 0;
                $billTransaction->partial = 1;
                $billTransaction->billCast = $billInfo->bill_content;
                $billTransaction->transactionType = $this->payType === 'cash' ? 'cash' : 'no-cash';
                $billTransaction->transactionSumm = $this->rawSumm;
                $billTransaction->transactionWay = 'in';
                $billTransaction->transactionReason = 'Частичная оплата счёта';
                $billTransaction->save();
                $billInfo->save();
                $cottageInfo->save();
                $transaction->commit();
                return ['status' => 1, 'message' => 'Частичная оплата успешна'];
            }
            catch (Exception $e){
	            $transaction->rollBack();
	            throw $e;
            }
        }
	    elseif($this->payWholeness === 'partial-finish'){
	        // завершение оплаты счёта
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            try{
                // отмечу все категории счёта полностью оплаченными
                $paymentTime = time();
                $billInfo = $this->billInfo['billInfo'];
                $billInfo->paymentTime = $paymentTime;
                $billInfo->toDeposit = $this->toDeposit;
                $dom = new DOMHandler($this->billInfo['billInfo']->bill_content);
                // данные о оплате электроэнергии
                if (!empty($this->billInfo['paymentContent']['power'])) {
                    PowerHandler::finishPartialPayment($dom, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['additionalPower'])) {
                    PowerHandler::finishPartialPayment($dom, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['membership'])) {
                    MembershipHandler::finishPartialPayment($dom, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['additionalMembership'])) {
                    MembershipHandler::finishPartialPayment($dom, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['target'])) {
                    TargetHandler::finishPartialPayment($dom, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['additionalTarget'])) {
                    TargetHandler::finishPartialPayment($dom, $additionalCottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                if (!empty($this->billInfo['paymentContent']['single'])) {
                    SingleHandler::finishPartialPayment($dom, $cottageInfo, $this->billInfo['billInfo']->id, $paymentTime);
                }
                $billInfo->isPartialPayed = 0;
                $billInfo->isPayed = 1;
                $billInfo->payedSumm += $this->rawSumm;
                // сохраню изменения в xml платежа
                $billInfo->bill_content = $dom->save();

                // зачислю остаток платежа на депозит
                DepositHandler::registerDeposit($billInfo, $this->billInfo['cottageInfo'], 'in');
                $billInfo->save();
                $cottageInfo->save();
                if(!empty($additionalCottageInfo)){
                    $additionalCottageInfo->save();
                }

                if($this->double){
                    $t = new Table_transactions_double();
                    $t->cottageNumber = $this->billInfo['cottageInfo']->masterId;
                }
                else{
                    $t = new Table_transactions();
                    $t->cottageNumber = $this->billInfo['cottageInfo']->cottageNumber;
                }

                $t->billId = $billInfo->id;
                $t->transactionDate = $billInfo->paymentTime;
                if($this->payType === 'cash'){
                    $t->transactionType = 'cash';
                }
                else{
                    $t->transactionType = 'no-cash';
                }
                $t->gainedDeposit = $this->toDeposit;
                $t->usedDeposit = 0;
                $t->partial = 0;
                $t->transactionSumm = $this->rawSumm;
                $t->transactionWay = 'in';
                $t->transactionReason = 'Оплата';
                $t->save();
                $transaction->commit();
                return ['status' => 1, 'message' => 'Платёж полностью оплачен'];
            }
            catch (Exception $e){
                $transaction->rollBack();
                throw $e;
            }
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $billInfo = $this->billInfo['billInfo'];
            $billInfo->paymentTime = time();
            $billInfo->toDeposit = $this->toDeposit;
            // теперь нужно отметить платёж как оплаченный, создать денежную транзакцию, сохранить её, сохранить в данных участка изменения связанные с оплатой
            // ищу информацию по каждому платежу
            // данные о оплате электроэнергии
            if (!empty($this->billInfo['paymentContent']['power'])) {
                PowerHandler::registerPayment($cottageInfo, $billInfo, $this->billInfo['paymentContent']['power']);
            }
            if (!empty($this->billInfo['paymentContent']['additionalPower'])) {
                PowerHandler::registerPayment($additionalCottageInfo, $billInfo, $this->billInfo['paymentContent']['additionalPower'], true);
            }
            if (!empty($this->billInfo['paymentContent']['membership'])) {
                MembershipHandler::registerPayment($cottageInfo, $billInfo, $this->billInfo['paymentContent']['membership']);
            }
            if (!empty($this->billInfo['paymentContent']['additionalMembership'])) {
                MembershipHandler::registerPayment($additionalCottageInfo, $billInfo, $this->billInfo['paymentContent']['additionalMembership'], true);
            }
            if (!empty($this->billInfo['paymentContent']['target'])) {
                TargetHandler::registerPayment($cottageInfo, $billInfo, $this->billInfo['paymentContent']['target']);
            }
            if (!empty($this->billInfo['paymentContent']['additionalTarget'])) {
                TargetHandler::registerPayment($additionalCottageInfo, $billInfo, $this->billInfo['paymentContent']['additionalTarget'], true);
            }
            if (!empty($this->billInfo['paymentContent']['single'])) {
                SingleHandler::registerPayment($cottageInfo, $billInfo, $this->billInfo['paymentContent']['single'], $this->double);
            }

            if ($this->fromDeposit > 0) {
                DepositHandler::registerDeposit($billInfo, $this->billInfo['cottageInfo'], 'out');
            }
            if ($this->discount > 0) {
                DiscountHandler::registerDiscount($billInfo);
            }
            if ($this->changeToDeposit && $this->toDeposit > 0) {
                DepositHandler::registerDeposit($billInfo, $this->billInfo['cottageInfo'], 'in');
            }
            $payedSumm = $billInfo->totalSumm - $this->discount - $this->fromDeposit + $this->toDeposit;
            $billInfo->isPayed = 1;
            $billInfo->payedSumm = $payedSumm;
            $billInfo->save();
            if($this->double){
                $t = new Table_transactions_double();
                $t->cottageNumber = $this->billInfo['cottageInfo']->masterId;
            }
            else{
                $t = new Table_transactions();
                $t->cottageNumber = $this->billInfo['cottageInfo']->cottageNumber;
            }

            $t->billId = $billInfo->id;
            $t->transactionDate = $billInfo->paymentTime;
            if($this->payType === 'cash'){
                $t->transactionType = 'cash';
            }
            else{
                $t->transactionType = 'no-cash';
            }
            $t->transactionSumm = $payedSumm;
            $t->gainedDeposit = $this->toDeposit;
            $t->usedDeposit = $this->fromDeposit;
            $t->transactionWay = 'in';
            $t->partial = 0;
            $t->transactionReason = 'Оплата';
            $t->save();
            // обновлю информацию о балансе садоводства на этот месяц
            Balance::toBalance($t->transactionSumm, 'cash');
            /** @var Table_cottages $cottageInfo */
            $cottageInfo->save();
            if ($additionalCottageInfo !== null) {
                $additionalCottageInfo->save();
            }
            $transaction->commit();
            return ['status' => 1];
        }
        catch (Exception $e){
            $transaction->rollBack();
            throw $e;
        }
	}
	public static function getUnpayedBillId($cottageNumber){
		$info = Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => false])->select('id')->one();
		return $info;
	}

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @return Table_payment_bills|Table_payment_bills_double
     */
    public static function getUnpayedBill($cottage){
        if(Cottage::isMain($cottage)){
            return Table_payment_bills::find()->where(['cottageNumber' => $cottage->cottageNumber, 'isPayed' => false])->select('creationTime')->one();
        }
        else{
            return Table_payment_bills_double::find()->where(['cottageNumber' => $cottage->masterId, 'isPayed' => false])->select('creationTime')->one();
        }
	}

}