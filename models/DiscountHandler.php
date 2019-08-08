<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 22:02
 */

namespace app\models;


use yii\base\Model;

class DiscountHandler extends Model {
    /**
     * @param $billInfo Table_payment_bills
     * @param null|Table_transactions $transaction
     */
	public static function registerDiscount($billInfo, $transaction = null)
	{
		$discounts = new Table_discounts();
		if(!empty($transaction)){
		    $discounts->transactionId = $transaction->id;
        }
		$discounts->cottageNumber = $billInfo->cottageNumber;
		$discounts->billId = $billInfo->id;
		$discounts->summ = $billInfo->discount;
		$discounts->reason = $billInfo->discountReason;
		$discounts->actionDate = $transaction->bankDate;
		$discounts->save();
	}
}