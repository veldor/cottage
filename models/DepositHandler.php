<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 22:14
 */

namespace app\models;


use app\validators\CashValidator;
use app\validators\CheckCottageNoRegistred;
use yii\base\Model;

class DepositHandler extends Model {

    public $summ;
    public $cottageNumber;
    public $additional;
    public $currentCondition;

    const SCENARIO_DIRECT_ADD = 'direct_add';

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

	public static function registerDeposit($billInfo, $cottageInfo, $way, $transaction = null){
	    if(!empty($transaction)){
            $depositIo = new Table_deposit_io();
            $depositIo->cottageNumber = $billInfo->cottageNumber;
            $depositIo->transactionId = $transaction->id;
            $depositIo->billId = $billInfo->id;
            $depositIo->destination = $way;
            $depositIo->summBefore = $cottageInfo->deposit;
            if($way === 'out'){
                $depositIo->summ = $billInfo->depositUsed;
                $cottageInfo->deposit = CashHandler::rublesMath($cottageInfo->deposit - $billInfo->depositUsed);
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

    public function save()
    {
        if($this->additional && !$this->currentCondition->hasDifferentOwner){
            throw new ExceptionWithStatus('У участка не найден дополнительный владелец', 2);
        }
        $depositIo = new Table_deposit_io();
        $depositIo->cottageNumber = $this->cottageNumber;
        $depositIo->destination = 'in';
        $depositIo->summBefore = $this->currentCondition->deposit;
        $depositIo->summ = $this->summ;
        $this->currentCondition->deposit = CashHandler::rublesMath($this->currentCondition->deposit + $this->summ);
        $depositIo->summAfter = $this->currentCondition->deposit;
        $depositIo->actionDate = time();
        $depositIo->save();
        $this->currentCondition->save();
        return['status' => 1, 'message' => 'Депозит пополнен'];
    }
}