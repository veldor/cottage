<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 18:17
 */

namespace app\models;

use app\validators\CashValidator;
use yii\base\InvalidArgumentException;
use yii\base\Model;


class PayDouble extends Model {
	public $billIdentificator; // Идентификатор платежа
	public $rawSumm = 0; // фактическое количество наличных
	public $totalSumm; // Общая сумма оплаты
	public $fromDeposit = 0;
	public $toDeposit = 0; // Начисление средств на депозит
	public $realSumm; // Фактическая сумма поступившив в кассу средств
	public $changeToDeposit = 0; // зачислить сдачу на депозин
	public $change = 0;
	public $discount = 0;
	/**
	 * @var $billInfo Table_payment_bills
	 */
	protected $billInfo;

	const SCENARIO_CASH = 'cash';

	public function scenarios(): array
	{
		return [
			self::SCENARIO_CASH => ['billIdentificator', 'totalSumm', 'totalSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'changeToDeposit', 'change'],
		];
	}

	public function attributeLabels(): array
	{
		return [
			'changeToDeposit' => 'Зачислить сдачу на депозит',
			'rawSumm' => 'Сумма наличных',
			'toDeposit' => 'Сумма, зачисляемая на депозит',
		];
	}

	/**
	 * @return array
	 */
	public function rules(): array
	{
		return [
			[['totalSumm', 'rawSumm', 'fromDeposit', 'toDeposit', 'realSumm', 'rawSumm', 'change', 'changeToDeposit'], CashValidator::class],
			[['billIdentificator', 'totalSumm', 'rawSumm', 'change'], 'required', 'on' => self::SCENARIO_CASH],
			[['toDeposit'], 'required', 'when' => function () {
				return $this->changeToDeposit;
			}, 'whenClient' => "function () {return $('input#pay-changetodeposit').prop('checked');}"],
			['changeToDeposit', 'in', 'range' => [1, 0]],
			['toDeposit', 'checkToDeposit'],
			['rawSumm', 'checkRawSumm'],
		];
	}

	public function checkRawSumm($attribute){
        $totalSumm = CashHandler::rublesMath($this->totalSumm - $this->fromDeposit - $this->discount);
        if(CashHandler::rublesMore($totalSumm, $this->$attribute)){
            $this->addError($attribute, "Сумма наличных не может быть меньше суммы к оплате");
        }
    }

	public function checkToDeposit($attribute)
	{
		// сумма, зачисляемая на депозит не должна превышать сумму потенциальной сдачи
        $totalSumm =CashHandler::rublesMath($this->totalSumm - $this->fromDeposit - $this->discount) ;
		$changeSumm = CashHandler::rublesMath($this->rawSumm - $totalSumm);

		if (CashHandler::rublesMore($this->$attribute, $changeSumm)) {
			$this->addError($attribute, 'Сумма слишком велика');
		}
		if (!CashHandler::rublesComparison(($this->$attribute + $this->change), $changeSumm)) {
			$this->addError($attribute, 'Не сходится сумма сдачи и зачисления (' . $changeSumm . ' != ' . ($this->$attribute + $this->change)  . ')');
		}
	}

	public function fillInfo($identificator): bool
	{
		$this->billInfo = ComplexPaymentDouble::getBill($identificator);
		if ($this->billInfo->isPayed === 1) {
			throw new InvalidArgumentException('Счёт уже оплачен!');
		}
		$this->billIdentificator = $identificator;
		$this->totalSumm = $this->billInfo->totalSumm;
		$this->fromDeposit = $this->billInfo->depositUsed;
		$this->discount = $this->billInfo->discount;
		return true;
	}

	/**
	 * @return array
	 */
	public function confirm(): array
	{
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $this->billInfo->paymentTime = time();
            $this->billInfo->toDeposit = $this->toDeposit;
            $info = ComplexPaymentDouble::getBillInfo($this->billInfo);
            $cottageInfo = $info['cottageInfo'];
            // теперь нужно отметить платёж как оплаченный, создать денежную транзакцию, сохранить её, сохранить в данных участка изменения связанные с оплатой
            // ищу информацию по каждому платежу
            // данные о оплате электроэнергии
            if (!empty($info['paymentContent']['additionalPower'])) {
                PowerHandler::registerPayment($cottageInfo, $this->billInfo, $info['paymentContent']['additionalPower'], true);
            }
            if (!empty($info['paymentContent']['additionalMembership'])) {
                MembershipHandler::registerPayment($cottageInfo, $this->billInfo, $info['paymentContent']['additionalMembership'], true);
            }
            if (!empty($info['paymentContent']['additionalTarget'])) {
                TargetHandler::registerPayment($cottageInfo, $this->billInfo, $info['paymentContent']['additionalTarget'], true);
            }
            if (!empty($info['paymentContent']['single'])) {
                SingleHandler::registerPayment($cottageInfo, $this->billInfo, $info['paymentContent']['single'], true);
            }

            if ($this->fromDeposit > 0) {
                DepositHandler::registerDeposit($this->billInfo, $info['cottageInfo'], 'out');
            }
            if ($this->discount > 0) {
                DiscountHandler::registerDiscount($this->billInfo);
            }
            if ($this->changeToDeposit && $this->toDeposit > 0) {
                DepositHandler::registerDeposit($this->billInfo, $info['cottageInfo'], 'in');
            }
            $payedSumm = $this->billInfo->totalSumm - $this->discount - $this->fromDeposit + $this->toDeposit;
            $this->billInfo->isPayed = 1;
            $this->billInfo->payedSumm = $payedSumm;
            $this->billInfo->save();
            $t = new Table_transactions_double();
            $t->cottageNumber = $info['cottageInfo']->masterId;
            $t->billId = $this->billInfo->id;
            $t->transactionDate = $this->billInfo->paymentTime;
            $t->transactionType = 'cash';
            $t->transactionSumm = $payedSumm;
            $t->transactionWay = 'in';
            $t->transactionReason = 'Оплата';
            $t->save();
            // обновлю информацию о балансе садоводства на этот месяц
            Balance::toBalance($t->transactionSumm, 'cash');
            /** @var Table_cottages $cottageInfo */
            $cottageInfo->save();
            $transaction->commit();
            return ['status' => 1];
        }
        catch (\Exception $e){
            $transaction->rollBack();
            throw $e;
        }
	}
	public static function getUnpayedBillId($cottageNumber){
		$info = Table_payment_bills_double::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => false])->select('id')->one();
		return $info;
	}
}