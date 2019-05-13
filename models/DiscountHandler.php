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
	 */
	public static function registerDiscount($billInfo)
	{
		$discounts = new Table_discounts();
		$discounts->cottageNumber = $billInfo->cottageNumber;
		$discounts->billId = $billInfo->id;
		$discounts->summ = $billInfo->discount;
		$discounts->reason = $billInfo->discountReason;
		$discounts->actionDate = $billInfo->paymentTime;
		$discounts->save();
	}
}