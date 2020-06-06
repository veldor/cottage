<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 22:14
 */

namespace app\models;


use app\models\interfaces\CottageInterface;
use app\models\utils\DbTransaction;
use app\validators\CashValidator;
use app\validators\CheckCottageNoRegistred;
use yii\base\Model;

class DepositHandler extends Model {

    public $summ;
    public $cottageNumber;
    public $additional;
    public CottageInterface $currentCondition;

    public const SCENARIO_DIRECT_ADD = 'direct_add';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_DIRECT_ADD => ['cottageNumber', 'summ', 'additional'],
        ];
    }


    public function rules(): array
    {
        return [
            [['cottageNumber', 'summ'], 'required', 'on' => self::SCENARIO_DIRECT_ADD],
            ['cottageNumber', CheckCottageNoRegistred::class],
            ['additional', 'boolean'],
            ['summ', CashValidator::class],
        ];
    }

    /**
     * @param $billInfo Table_payment_bills
     * @param $cottageInfo Table_cottages
     * @param $way 'in'|'out'
     * @param null|Table_transactions $transaction
     */
	public static function registerDeposit($billInfo, $cottageInfo, $way, $transaction = null): void
    {
	    if($transaction !== null){
            $depositIo = new Table_deposit_io();
            $depositIo->cottageNumber = $billInfo->cottageNumber;
            $depositIo->transactionId = $transaction->id;
            $depositIo->billId = $billInfo->id;
            $depositIo->destination = $way;
            $depositIo->summBefore = CashHandler::toRubles($cottageInfo->deposit) + CashHandler::toRubles($billInfo->depositUsed);
            if($way === 'out'){
                $depositIo->summ = $billInfo->depositUsed;
                $depositIo->summAfter = $cottageInfo->deposit;
            }
            else{
                $depositIo->summ = $billInfo->toDeposit;
                $cottageInfo->deposit = CashHandler::rublesMath(CashHandler::toRubles($cottageInfo->deposit) + CashHandler::toRubles($billInfo->toDeposit));
                $depositIo->summAfter = $cottageInfo->deposit;
            }
            $depositIo->actionDate = $transaction->bankDate;
            $depositIo->save();
            $cottageInfo->save();
        }
	}

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public function save(): array
    {
        $transaction = new DbTransaction();

        if($this->currentCondition->isMain()){
            $tr = new Table_transactions(['cottageNumber' => $this->currentCondition->getBaseCottageNumber(), 'transactionDate' => time(), 'transactionType' => 'no-cash', 'transactionSumm' => $this->summ, 'transactionWay' => 'in', 'transactionReason' => 'Пополнение депозита', 'usedDeposit' => 0, 'gainedDeposit' => $this->summ, 'partial' => 0, 'payDate' => time(), 'bankDate' => time()]);
            $tr->save();
        }
        else{
            $tr = new Table_transactions_double(['cottageNumber' => $this->currentCondition->getBaseCottageNumber(), 'transactionDate' => time(), 'transactionType' => 'no-cash', 'transactionSumm' => $this->summ, 'transactionWay' => 'in', 'transactionReason' => 'Пополнение депозита', 'usedDeposit' => 0, 'gainedDeposit' => $this->summ, 'partial' => 0, 'payDate' => time(), 'bankDate' => time()]);
            $tr->save();
        }
        if($this->additional && !$this->currentCondition->hasDifferentOwner){
            throw new ExceptionWithStatus('У участка не найден дополнительный владелец', 2);
        }
        $depositIo = new Table_deposit_io();
        $depositIo->cottageNumber = $this->cottageNumber;
        $depositIo->destination = 'in';
        $depositIo->transactionId = $tr->id;
        $depositIo->summBefore = $this->currentCondition->deposit;
        $depositIo->summ = $this->summ;
        $this->currentCondition->deposit = CashHandler::rublesMath($this->currentCondition->deposit + $this->summ);
        $depositIo->summAfter = $this->currentCondition->deposit;
        $depositIo->actionDate = time();
        $depositIo->save();
        $this->currentCondition->save();
        // добавлю транзакцию
        $transaction->commitTransaction();
        return['status' => 1, 'message' => 'Депозит пополнен'];
    }
}