<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 23.10.2018
 * Time: 18:11
 */

namespace app\models;

use yii\base\Model;


class TariffsHandler extends Model
{
    public static function calculatePower($cottageNumber, $start, $quantity, $noLimit)
    {
        $noLimArr = [];
        if(!empty($noLimit)){
            $noLimArr = array_flip(explode(' ', trim($noLimit)));
        }
        $bills = Table_power_months::find()->where(['cottageNumber' => $cottageNumber])->andWhere(['>', 'searchTimestamp', $start])->andWhere(['>', 'difference', 0])->limit($quantity)->orderBy('searchTimestamp')->all();
        $tariffs = Table_tariffs_power::find()->where(['>', 'searchTimestamp', $start])->orderBy('searchTimestamp')->all();
        $tariffsData = [];
        foreach ($tariffs as $tariff) {
            $tariffsData[$tariff->targetMonth] = ['limit' => $tariff->powerLimit, 'cost' => $tariff->powerCost, 'overcost' => $tariff->powerOvercost];
        }
        $answer = [];
        $totalSumm = 0;
        foreach ($bills as $bill) {
            if($bill->difference > 0){
                if(isset($noLimArr[$bill->month])){
                    $summ = $bill->difference * $tariffsData[$bill->month]['overcost'];
                    $totalSumm += $summ;
                    $answer[$bill->month] = ['summ' => $summ, 'oldData' => $bill->oldPowerData, 'newData' => $bill->newPowerData, 'difference' => $bill->difference, 'limit' => 0 , 'cost' => $tariffsData[$bill->month]['cost'], 'overcost' => $tariffsData[$bill->month]['overcost'], 'inLimit' => 0, 'overLimit' => $bill->difference, 'inLimitCost' => 0, 'overLimitCost' => $summ, 'corrected' => 1];
                }
                else{
                    $totalSumm += $bill->totalPay;
                    $answer[$bill->month] = ['summ' => $bill->totalPay, 'oldData' => $bill->oldPowerData, 'newData' => $bill->newPowerData, 'difference' => $bill->difference, 'limit' => $tariffsData[$bill->month]['limit'], 'cost' => $tariffsData[$bill->month]['cost'], 'overcost' => $tariffsData[$bill->month]['overcost'], 'inLimit' => $bill->inLimitSumm, 'overLimit' => $bill->overLimitSumm, 'inLimitCost' => $bill->inLimitPay, 'overLimitCost' => $bill->overLimitPay, 'corrected' => 0];
                }

            }
        }
        // верну список
        return ['totalSumm' => $totalSumm, 'details' => $answer];
    }

    public static function calculateMembership($cottageNumber, $start, $quantity, $square)
    {

        $tariffs = Table_tariffs_membership::find()->where(['>', 'search_timestamp', $start])->limit($quantity)->orderBy('search_timestamp')->all();
        $answer = [];
        $totalSumm = 0;
        foreach ($tariffs as $tariff) {
            $quarter = $tariff->quarter;
            $fixed = $tariff->fixed_part;
            $float = $tariff->changed_part;
            $summ = $fixed + ($float / 100 * $square);
            $totalSumm += $summ;
            $answer[$quarter] = ['summ' => $summ, 'fixed' => $fixed, 'float' => $float, 'square' => $square];
        }
        // верну список
        return ['totalSumm' => CashHandler::rublesRound($totalSumm), 'details' => $answer];
    }
}