<?php


namespace app\models;


use app\models\tables\Table_penalties;
use Exception;
use yii\base\Model;

class PenaltiesHandler extends Model
{
    private const PERCENT = 0.5;

    /**
     * @return array
     * @throws Exception
     */
    public static function countPenalties(): array
    {
        // найду все участки
        $cottages = Cottage::getRegister();
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

    /**
     * @param $id
     * @throws ExceptionWithStatus
     */
    public static function unlockFine($id): void
    {
        $fine = Table_penalties::findOne($id);
        if($fine !== null){
            $fine->locked = 0;
            $fine->save();
            FinesHandler::checkCottage(Cottage::getCottageByLiteral($fine->cottage_number));
        }
    }

    /**
     * @param $id
     * @return array|null
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function deleteFine($id): ?array
    {
        $existentFine = Table_penalties::findOne($id);
        if($existentFine !== null){
            //$js ='<script>$("tr[data-fine-id="{$existentFine->id}"]").remove();</script>';
            $js = "<script>$('tr[data-fine-id=\"{$existentFine->id}\"]').remove()</script>";
            $existentFine->delete();
            return ['status' => 1, 'message' => 'Пени удалены' . $js];
        }
        return ['status' => 2, 'message' => 'Данные не найдены'];
    }
}