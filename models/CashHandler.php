<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 23.10.2018
 * Time: 18:11
 */

namespace app\models;

use yii\base\InvalidArgumentException;
use yii\base\Model;
date_default_timezone_set('Europe/Moscow');
setlocale(LC_ALL, 'ru_RU.utf8');

class CashHandler extends Model
{
	public const RUB = ' &#8381;';
	public const KW = ' кВт.ч';
    /**
     * @param $summ
     * @param bool $isNegative
     * @throws InvalidArgumentException
     * @return float
     */
    public static function toRubles($summ, $isNegative = false): float
    {
        if($isNegative){
            $re = '/^\s*(-?\d+)[,.]?(\d*)?\s*$/';
        }
        else{
            $re = '/^\s*(\d+)[,.]?(\d*)?\s*$/';
        }
        $match = null;
        if(preg_match($re, $summ, $match)){
            $summ = round((double) "$match[1],$match[2]", 2);
            return $summ;
        }
        throw new InvalidArgumentException($summ . ' - Данное значение не является верным числом');
    }
    public static function calculatePower($newData, $oldData, $limit, $cost, $overcost){
        $newData = (int) $newData;
        $oldData = (int) $oldData;
        $limit = (int) $limit;
        $cost = (float) $cost;
        $overcost = (float) $overcost;
        $difference = $newData - $oldData;
        if($difference == 0)
            return 0;
        if ($limit != 0) {
            if ($limit > $difference) {
                $paymentTotal = round($difference * $cost, 2);
            } else {
                $std = $limit * $cost;
                $over = ($difference - $limit) * $overcost;
                $paymentTotal = round($std + $over, 2);
            }
        } else
            $paymentTotal = round($difference * $overcost, 2);
        return $paymentTotal;
    }

    public static function calculateMembership($square, $fixed, $float)
    {
        $square = (int) $square;
        $fixed = (float) $fixed;
        $float = (float) $float;
        return self::toRubles($fixed + $float / 100 * $square);
    }
    public static function rublesComparison($first, $second){
        return (string) $first === (string) $second;
    }
    public static function rublesMath($expression){
        $rounded = round($expression, 2);
        if($rounded === -0){
            $rounded = 0;
        }
        return $rounded;
    }

    /**
     * @param $value
     * @return float
     */
    public static function rublesRound($value): float
    {
        return round($value, 2);
    }
    public static function rublesMore($first, $second): bool
    {
        return (string)$first > (string)$second;
    }

    public static function dividedSumm($summ, $negative = false): array
    {
        // приведу сумму к стандартному виду
        $summ = self::toRubles($summ, $negative);
        $divided = explode(',', $summ);
        $rubles = $divided[0];
        if(!empty($divided[1])){
            if(strlen($divided[1]) === 1){
                $cents =$divided[1] . '0';
            }
            else{
                $cents = $divided[1];
            }
        }
        else{
            $cents = '00';
        }
        return ['rubles' => $rubles, 'cents' => $cents];
    }
    public static function toSmoothRubles($summ): string
    {
        $divided = self::dividedSumm($summ);
        return $divided['rubles'] . ' руб.' . $divided['cents'] . ' коп.';
    }

    public static function toShortSmoothRubles($summ, $negative = false): string
    {
        $divided = self::dividedSumm($summ, $negative);
        return $divided['rubles'] . ',' . $divided['cents'] . self::RUB;
    }

    public static function countPercent($summ, float $percent)
    {
        return self::toRubles($summ) / 100 * $percent;
    }

    /**
     * @param string $powerCost
     * @return int
     */
    public static function toNewRubles(string $powerCost): int
    {
        $summ = self::dividedSumm($powerCost);
        return (int) ($summ['rubles'] . $summ ['cents']);
    }

    public static function toJsRubles($summ){
        $summ = self::toRubles($summ);
        return str_replace(',', '.', $summ);
    }

    public static function countPartialPercent(float $plannedTotal, float $payedWhole)
    {
        if($plannedTotal > 0){
            return round($payedWhole / $plannedTotal, 4) * 100;
        }
        return 0;
    }

    public static function floatToInt(string $float)
    {
        return round(self::toRubles($float) * 100);
    }

    public static function sumFromInt(int $intValue)
    {
        return round($intValue / 100, 2);
    }
}