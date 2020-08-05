<?php


namespace app\models;


use app\models\tables\Table_penalties;
use yii\base\Model;

class PenaltiesHandler extends Model
{
    private const PERCENT = 0.5;

    public static function countPenalties()
    {
        // найду все участки
        $cottages = Cottage::getRegistred();
        foreach ($cottages as $cottage) {
            $powerDuties = PowerHandler::getDebtReport($cottage);
            if (!empty($powerDuties)) {
                foreach ($powerDuties as $powerDuty) {
                    if ($powerDuty['difference'] > 0) {
                        // получу дату оплаты долга
                        $payUp = TimeHandler::getPayUpMonth($powerDuty['month']);
                        // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                        $dayDifference = TimeHandler::checkDayDifference($payUp);
                        if ($dayDifference != null) {
                            $fines = CashHandler::countPercent($powerDuty['totalPay'], self::PERCENT);
                            $fullFine = $fines * $dayDifference * 100;
                            // если период уже есть- изменю его, если нет- создам новый
                            $existent = Table_penalties::find()->where(['cottage_number' => $cottage->cottageNumber, 'period' => $powerDuty['month'], 'pay_type' => 'power'])->one();
                            if($existent){
                                $existent->summ = $fullFine;
                                $existent->save();
                            }
                            else{
                                // посчитаю пени в день
                                $penalty = new Table_penalties();
                                $penalty->cottage_number = $cottage->cottageNumber;
                                $penalty->summ = $fullFine;
                                $penalty->payed_summ = 0;
                                $penalty->payUpLimit = $payUp;
                                $penalty->pay_type = 'power';
                                $penalty->period = $powerDuty['month'];
                                $penalty->is_full_payed = 0;
                                $penalty->is_partial_payed = 0;
                                $penalty->save();
                            }

                        }
                    }
                }
            }
        }
        return ['status' => 1, 'message' => 'Все пени посчитаны'];
    }

    public static function unlockFine($id)
    {
        $fine = Table_penalties::findOne($id);
        $fine->locked = 0;
        $fine->save();
        FinesHandler::checkCottage(Cottage::getCottageByLiteral($fine->cottage_number));
    }
}