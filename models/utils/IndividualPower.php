<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\database\PersonalPower;
use app\models\Table_power_months;
use yii\base\Model;

class IndividualPower extends Model
{
    public string $cost = '';
    public string $overcost = '';
    public string $limit = '';
    public string $fixedAmount = '';
    public string $selection = '';

    public function attributeLabels(): array
    {
        return [
            'cost' => 'Льготная стоимость КВт',
            'overcost' => 'Стоимость КВт',
            'limit' => 'Льготный лимит',
            'fixedAmount' => 'Фиксированная стоимость',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // name, email, subject и body атрибуты обязательны
            [['cost', 'overcost', 'limit', 'fixedAmount', 'selection'], 'required'],
        ];
    }

    /**
     * @param Table_power_months $month
     */
    public function submit(Table_power_months $month): void
    {
        if ($this->selection === '1') {
            $amount = CashHandler::toRubles($this->fixedAmount);
            // сохраняю фиксированную сумму
            (new PersonalPower(['month' => $month->month, 'cottage_number' => $month->cottageNumber, 'fixed_amount' => $amount]))->save();
            $month->totalPay = $amount;
            $month->inLimitPay = 0;
            $month->overLimitPay = $amount;
            $month->save();
        }
        else if($this->selection === '0'){
            $limit = (int) $this->limit;
            (new PersonalPower(['month' => $month->month, 'cottage_number' => $month->cottageNumber, 'limit' => $limit, 'cost' => CashHandler::toRubles($this->cost), 'over_cost' => CashHandler::toRubles($this->overcost)]))->save();
            if($limit === 0){
                $month->inLimitPay = 0;
                $month->inLimitSumm = 0;
                $month->overLimitPay = $month->difference * CashHandler::toRubles($this->overcost);
                $month->overLimitSumm = $month->difference;
                $month->totalPay = $month->overLimitPay;
            }
            elseif($month->difference < $limit){
                $month->overLimitPay = 0;
                $month->overLimitSumm = 0;
                $month->inLimitPay = $month->difference * CashHandler::toRubles($this->cost);
                $month->inLimitSumm = $month->difference;
                $month->totalPay = $month->inLimitPay;
            }
            else{
                $month->inLimitSumm = $limit;
                $month->inLimitPay = $limit * CashHandler::toRubles($this->cost);
                $month->overLimitSumm = $month->difference - $limit;
                $month->overLimitPay = $month->overLimitSumm * CashHandler::toRubles($this->overcost);
                $month->totalPay = $month->inLimitPay + $month->overLimitPay;
            }
            $month->save();
        }
    }
}