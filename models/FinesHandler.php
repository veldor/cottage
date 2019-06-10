<?php


namespace app\models;


use yii\base\Model;

class FinesHandler extends Model
{

    private const PERCENT = 0.5;

    public static function countFines($cottageNumber)
    {
        $text = '<table class="table table-striped table-hover"><thead><tr>
                                                                            <th>Вид</th>
                                                                            <th>Крайняя дата оплаты</th>
                                                                            <th>Сумма в день</th>
                                                                            <th>Количество дней</th>
                                                                            <th>Общая сумма</th>
                                                                        </tr></thead><tbody>';
        // получу данные о задолженностях по электроэнергии
        $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
        $powerDuties = PowerHandler::getDebtReport($cottageInfo);
        if (!empty($powerDuties)) {
            foreach ($powerDuties as $powerDuty) {
                if ($powerDuty['difference'] > 0) {
                    // получу дату оплаты долга
                    $payUp = TimeHandler::getPayUpMonth($powerDuty['month']);
                    // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                    $dayDifference = TimeHandler::checkDayDifference($payUp);
                    if ($dayDifference != null) {
                        // посчитаю пени в день
                        $fines = CashHandler::countPercent($powerDuty['totalPay'], self::PERCENT);
                        $fullFine = $fines * $dayDifference;
                        $text .= '<tr><td>Электроэнергия ' . $powerDuty['month'] . '</td><td>' . TimeHandler::getDateFromTimestamp($payUp) . '</td><td>' . CashHandler::toSmoothRubles($fines) . '</td><td>' . $dayDifference . '</td>' . '</td><td>' . CashHandler::toSmoothRubles($fullFine) . '</td>';
                    }
                }
            }
        }

        $membershipDuties = MembershipHandler::getDebt($cottageInfo);
        if (!empty($membershipDuties)) {
            foreach ($membershipDuties as $key=>$membershipDuty) {
                // получу дату оплаты долга
                $payUp = TimeHandler::getPayUpQuarterTimestamp($key);
                // посчитаю количество дней, прошедших с момента крайнего дня оплаты до этого дня
                $dayDifference = TimeHandler::checkDayDifference($payUp);
                if ($dayDifference != null) {
                    // посчитаю пени в день
                    $fines = CashHandler::countPercent($membershipDuty['total_summ'], self::PERCENT);
                    $fullFine = $fines * $dayDifference;
                    $text .= '<tr><td>Членские ' . $key . '</td><td>' . TimeHandler::getDateFromTimestamp($payUp) . '</td><td>' . CashHandler::toSmoothRubles($fines) . '</td><td>' . $dayDifference . '</td>' . '</td><td>' . CashHandler::toSmoothRubles($fullFine) . '</td>';
                }
            }
        }

        $text .= '</tbody></table>';
        return ['status' => 1, 'text' => $text];
    }
}