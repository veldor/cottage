<?php

/** @var $this ->cottageInfo  \app\models\Table_cottages */

namespace app\models;

use yii\base\Model;

class Payments extends Model
{
    /**
     * @param $type
     * @param $cottageNumber
     * @return Payments_power[]|array|null|\yii\db\ActiveRecord[]
     * @var Table_cottages $info
     * @var integer $info ->id
     */
    public static function paymentsHistory($type, $cottageNumber)
    {
        // найду id участка
        $info = Table_cottages::find()->where(['cottageNumber' => $cottageNumber])->one();
        $data = null;
        if ($type === 'power') {
            $data = Payments_power::find()->where(['cottageNumber' => $info->cottageNumber])->all();
        }
        return $data;
    }

    public static function invoiceInfo($invoiceId)
    {
        $info = Table_payment_bills::findOne(['id' => $invoiceId]);
        $cottageInfo = Table_cottages::findOne(['cottageNumber' => $info->cottageNumber]);
        return ['invoiceInfo' => $info, 'cottageInfo' => $cottageInfo];
    }
}