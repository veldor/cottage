<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.12.2018
 * Time: 15:36
 */

namespace app\models;


use yii\base\Model;

class Calculator extends Model
{
    public static function countFixedFloat($fixed, $float, $square){
        return CashHandler::rublesMath($fixed + ($float / 100 * $square));
    }
    public static function countFixedFloatPlus($fixed, $float, $square){
        $float = CashHandler::rublesMath($float / 100 * $square);
        return ['float' => $float, 'total' => CashHandler::rublesMath($fixed + $float)];
    }
}