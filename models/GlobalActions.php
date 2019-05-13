<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.04.2019
 * Time: 15:21
 */

namespace app\models;


use yii\base\Model;

class GlobalActions extends Model
{

    public static function showAllBills()
    {
        $bills = Table_payment_bills::find()->where(['isPayed' => 0])->orderBy('cottageNumber')->all();
        $doubleBills = Table_payment_bills_double::find()->where(['isPayed' => 0])->orderBy('cottageNumber')->all();
        return ['bills' => $bills, 'doubleBills' => $doubleBills];
    }
}