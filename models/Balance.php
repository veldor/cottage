<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.11.2018
 * Time: 12:59
 */

namespace app\models;

use yii\base\Model;


class Balance extends Model
{
    public $currentBalance;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        self::getMonth();
        $this->currentBalance = Balance_table::find()->select('cash_summ')->where(['month' => TimeHandler::getCurrentShortMonth()])->one()->cash_summ;
    }

    public static function toBalance($summ, $type)
    {
        $month = self::getMonth();
        if ($type === 'cash') {
            $month->cash_summ = CashHandler::rublesMath($month->cash_summ + $summ);
            $month->save();
            return $month->cash_summ;
        }
        return false;
    }

    public static function getMonth()
    {
        // получу данный месяц из таблицы
        $month = Balance_table::findOne(['month' => TimeHandler::getCurrentShortMonth()]);
        if (empty($month)) {
            // найду предыдущий месяц, его значение запишу в финальное, и сделаю начальным для текущего.
            $oldMonth = Balance_table::find()->orderBy('month DESC')->one();
            if($oldMonth !== null){
                $oldMonth->finishCashBalance = $oldMonth->cash_summ;
                $oldMonth->finishCashlessBalance = $oldMonth->cashless_summ;
                $oldMonth->save();
                $month = new Balance_table();
                $month->month = TimeHandler::getCurrentShortMonth();
                $month->startCashBalance = $month->cash_summ = $oldMonth->finishCashBalance;
                $month->startCashlessBalance = $month->cashless_summ = $oldMonth->finishCashlessBalance;
                $month->save();
            }
        }
        return $month;
    }

    public static function getSumm($interval, $direction)
    {
        $summ = 0;
        $results = Table_transactions::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['end']])->andWhere(['transactionWay' => $direction])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['end']])->andWhere(['transactionType' => 'cash', 'transactionWay' => $direction])->all();
        if (!empty($results)) {
            foreach ($results as $result) {
                $summ += CashHandler::rublesRound($result->transactionSumm);
            }
        }
        if (!empty($doubleResults)) {
            foreach ($doubleResults as $result) {
                $summ += CashHandler::rublesRound($result->transactionSumm);
            }
        }
        return ['status' => 1, 'summ' => CashHandler::rublesRound($summ)];
    }

    public static function getTransactions($interval)
    {
        $content = "<table class='table table-striped'><thead><th>Дата платежа</th><th>№</th><th>Сумма</th><th>Тип</th><th>Вид</th></thead><tbody>";
        $results = Table_transactions::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['end']])->all();
        $doubleResults = Table_transactions_double::find()->where(['>=', 'transactionDate', $interval['start']])->andWhere(['<=', 'transactionDate', $interval['end']])->andWhere(['transactionType' => 'cash'])->all();
        if (!empty($results) || !empty($doubleResults)) {
            foreach ($results as $result) {
                $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                if ($result->transactionWay === 'in')
                    $way = "<b class='text-success'>Поступление</b>";
                else
                    $way = "<b class='text-danger'>Списание</b>";
                if ($result->transactionType === 'cash')
                    $type = "<b class='text-success'>Наличные</b>";
                else
                    $type = "<b class='text-primary'>Безналичный расчёт</b>";
                $content .= "<tr><td>$date</td><td><a href='#' data-bill-id='{$result->billId}'>{$result->billId}</a></td><td><b class='text-info'>{$result->transactionSumm} &#8381;</b></td><td>$way</td><td>$type</td></tr>";
            }
            foreach ($doubleResults as $result) {
                $date = TimeHandler::getDateFromTimestamp($result->transactionDate);
                if ($result->transactionWay === 'in')
                    $way = "<b class='text-success'>Поступление</b>";
                else
                    $way = "<b class='text-danger'>Списание</b>";
                if ($result->transactionType === 'cash')
                    $type = "<b class='text-success'>Наличные</b>";
                else
                    $type = "<b class='text-primary'>Безналичный расчёт</b>";
                $content .= "<tr><td>$date</td><td><a href='#' data-bill-id='{$result->billId}'>{$result->billId}</a></td><td><b class='text-info'>{$result->transactionSumm} &#8381;</b></td><td>$way</td><td>$type</td></tr>";
            }
            $content .= "</tbody></table>";
        } else {
            $content .= "</tbody></table>";
            $content .= "<h3>Транзакций за данный период не найдено</h3>";
        }
        return ['status' => 1, 'data' => $content];
    }

    public static function getDayIn()
    {
        return self::getSumm(TimeHandler::getDayStartAndFinish(TimeHandler::getCurrentDay()), 'in');
    }

    public static function getMonthIn()
    {
        return self::getSumm(TimeHandler::getMonthStartAndFinish(TimeHandler::getCurrentShortMonth()), 'in');
    }

    public static function getYearIn()
    {
        return self::getSumm(TimeHandler::getYearStartAndFinish(TimeHandler::getThisYear()), 'in');
    }

    public static function getDayOut()
    {
        return self::getSumm(TimeHandler::getDayStartAndFinish(TimeHandler::getCurrentDay()), 'out');
    }

    public static function getMonthOut()
    {
        return self::getSumm(TimeHandler::getMonthStartAndFinish(TimeHandler::getCurrentShortMonth()), 'out');
    }

    public static function getYearOut()
    {
        return self::getSumm(TimeHandler::getYearStartAndFinish(TimeHandler::getThisYear()), 'out');
    }

    public static function getDayTransactions()
    {
        $answer = self::getTransactions(TimeHandler::getDayStartAndFinish(TimeHandler::getCurrentDay()));
        $answer['date'] = TimeHandler::getCurrentMonth() . " года.";
        return $answer;
    }

    public static function getMonthTransactions()
    {
        $answer = self::getTransactions(TimeHandler::getMonthStartAndFinish(TimeHandler::getCurrentShortMonth()));
        $answer['date'] = TimeHandler::getFullFromShotMonth(TimeHandler::getCurrentShortMonth()) . " года.";
        return $answer;
    }

    public static function getYearTransactions()
    {
        $answer = self::getTransactions(TimeHandler::getYearStartAndFinish(TimeHandler::getThisYear()));
        $answer['date'] = TimeHandler::getThisYear() . " год.";
        return $answer;
    }

    public static function getDaySummary()
    {
        $answer = self::getSummary(TimeHandler::getDayStartAndFinish(TimeHandler::getCurrentDay()));
        $answer['date'] = TimeHandler::getCurrentMonth() . " года.";
        return $answer;
    }
    public static function getMonthSummary()
    {
        $answer = self::getSummary(TimeHandler::getMonthStartAndFinish(TimeHandler::getCurrentShortMonth()));
        $answer['date'] = TimeHandler::getFullFromShotMonth(TimeHandler::getCurrentShortMonth()) . " года.";
        return $answer;
    }
    public static function getYearSummary()
    {
        $answer = self::getSummary(TimeHandler::getYearStartAndFinish(TimeHandler::getThisYear()));
        $answer['date'] = TimeHandler::getThisYear() . " год.";
        return $answer;
    }

    public static function getSummary($interval)
    {
        $totalPowerSumm = 0;
        $totalMemSumm = 0;
        $totalTargetSumm = 0;
        $totalSingleSumm = 0;
        $content = "<table class='table table-striped'><thead><th>Электроэнергия</th><th>Членские</th><th>Целевые</th><th>Разовые</th></thead><tbody>";
        $powers = Table_payed_power::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($powers)) {
            foreach ($powers as $power) {
                $totalPowerSumm += $power->summ;
            }
        }
        $powersAdditional = Table_additional_payed_power::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($powersAdditional)) {
            foreach ($powersAdditional as $power) {
                $totalPowerSumm += $power->summ;
            }
        }
        $mems = Table_payed_membership::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($mems)) {
            foreach ($mems as $mem) {
                $totalMemSumm += $mem->summ;
            }
        }
        $memsAdditional = Table_additional_payed_membership::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($memsAdditional)) {
            foreach ($memsAdditional as $mem) {
                $totalMemSumm += $mem->summ;
            }
        }
        $targets = Table_payed_target::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($targets)) {
            foreach ($targets as $target) {
                $totalTargetSumm += $target->summ;
            }
        }
        $targetsAdditional = Table_additional_payed_target::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($targetsAdditional)) {
            foreach ($targetsAdditional as $target) {
                $totalTargetSumm += $target->summ;
            }
        }
        $singls = Table_payed_single::find()->where(['>=', 'paymentDate', $interval['start']])->andWhere(['<=', 'paymentDate', $interval['end']])->all();
        if (!empty($singls)) {
            foreach ($singls as $single) {
                $totalSingleSumm += $single->summ;
            }
        }
        $content .= "<tr><td>$totalPowerSumm</td><td>$totalMemSumm</td><td>$totalTargetSumm</td><td>$totalSingleSumm</td></tr></tbody></table>";
        return ['status' => 1, 'data' => $content];
    }
}