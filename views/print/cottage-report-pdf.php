<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 22:50
 */

use app\assets\printAsset;
use app\models\Calculator;
use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\PersonalTariff;
use app\models\SingleHandler;
use app\models\Table_cottages;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_target;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\TargetHandler;
use app\models\TimeHandler;
use yii\web\View;

printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this View */
/* @var $cottageInfo Table_cottages */
/** @var string $end */
/** @var $transactionsInfo [] */
/** @var string $start */


$finesDetails = '';
$finesSumm = 0;
// определю конец периода
$lastMonth = TimeHandler::getShortMonthFromTimestamp($end);
$accrued = 0;
$payedAmount = 0;
$powerDetails = '';
// получу все месяцы оплаты до текущего
$months = Table_power_months::find()->where(['<=', 'month', $lastMonth])->andWhere(['cottageNumber' => $transactionsInfo['cottageInfo']->cottageNumber])->all();

// если есть неоплаченные месяцы- для каждого из них предоставлю детализацию
if (!empty($months)) {
    foreach ($months as $month) {
        // проверю, не просрочен ли платёж
        $total = CashHandler::toRubles($month->totalPay);
        if ($total > 0) {
            // поищу оплаты по этому месяцу. Если их нет- заношу месяц в долги
            $payed = Table_payed_power::find()->where(['month' => $month->month, 'cottageId' => $transactionsInfo['cottageInfo']->cottageNumber])->andWhere(['<=', 'paymentDate', $end])->all();
            if (empty($payed)) {
                $powerDetails .= $month->month . ' : ' . $total . "<br/>\n";
                $accrued += $total;
                // определю срок оплаты
                $payUp = TimeHandler::getPayUpMonth($month->month);
                if ($payUp < FinesHandler::START_POINT) {
                    $payUp = FinesHandler::START_POINT;
                }
                // если месяц не оплачен и прошел срок выплат- считаю пени
                if ($payUp < $end) {
                    // посчитаю сумму пени
                    // посчитаю количество дней задолженности
                    $dayDifference = TimeHandler::checkDayDifference($payUp, $end);
                    if ($dayDifference > 0) {
                        $finesPerDay = CashHandler::countPercent($total, FinesHandler::PERCENT);
                        $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                        // теперь попробую найти оплату по данному пени
                        $savedFine = Table_penalties::findOne(['cottage_number' => $cottageInfo->cottageNumber, 'pay_type' => 'power', 'period' => $month->month]);
                        $payedFines = Table_payed_fines::find()->where(['fine_id' => $savedFine->id])->andWhere(['<', 'pay_date', $end])->all();
                        $payedFineAmount = 0;
                        if (!empty($payedFines)) {
                            foreach ($payedFines as $payedFine) {
                                $payedFineAmount += CashHandler::toRubles($payedFine->summ);
                            }
                        }
                        $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                        if ($fineDuty > 0) {
                            $finesDetails .= "Э* " . $month->month . ' : ' . $fineDuty . "<br/>\n";
                            $finesSumm += $fineDuty;
                        }

                    }
                }
            } else {
                $payedAmount = 0;
                // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
                foreach ($payed as $pay) {
                    $payedAmount += CashHandler::toRubles($pay->summ);
                }
                $payedAmount = CashHandler::toRubles($payedAmount);
                if ($payedAmount < $total) {
                    $difference = CashHandler::toRubles($total - $payedAmount);
                    $powerDetails .= $month->month . ' : ' . $difference . "<br/>\n";
                    $accrued += $difference;
                }
            }
        }
    }
}
$powerDebt = $accrued;

// членские
$membershipDetails = '';
$total = 0;
// получу окончательный квартал расчёта
$lastQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($end));
// получу первый квартал расчёта
$firstQuarterValue = Table_payed_membership::find()->where(['cottageId' => $transactionsInfo['cottageInfo']->cottageNumber])->orderBy('quarter')->one();
if (!empty($firstQuarterValue)) {
    $firstQuarter = $firstQuarterValue->quarter;
} else {
    $firstQuarter = $cottageInfo->membershipPayFor;
}
// получу список кварталов
$list = TimeHandler::getQuartersList($firstQuarter, $lastQuarter);
// обработаю список
if (!empty($list)) {
    foreach ($list as $item) {
        // получу начисление за квартал
        if ($cottageInfo->individualTariff) {
            $tariff = PersonalTariff::getMembershipRate($cottageInfo, $item);
            $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $cottageInfo->cottageSquare));
        } else {
            $tariff = Table_tariffs_membership::findOne(['quarter' => $item]);
            $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottageInfo->cottageSquare));
        }
        // найду оплаты за данный период
        $payed = Table_payed_membership::find()->where(['quarter' => $item, 'cottageId' => $transactionsInfo['cottageInfo']->cottageNumber])->andWhere(['<=', 'paymentDate', $end])->all();
        if (empty($payed)) {
            $membershipDetails .= $item . ' : ' . $accrued . "<br/>\n";
            $total += $accrued;

            $payUp = TimeHandler::getPayUpQuarterTimestamp($item);
            if ($payUp < FinesHandler::START_POINT) {
                $payUp = FinesHandler::START_POINT;
            }
            // если месяц не оплачен и прошел срок выплат- считаю пени
            if ($payUp < $end) {
                // посчитаю сумму пени
                // посчитаю количество дней задолженности
                $dayDifference = TimeHandler::checkDayDifference($payUp, $end);
                if ($dayDifference > 0) {
                    $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                    $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                    // теперь попробую найти оплату по данному пени
                    $savedFine = Table_penalties::findOne(['cottage_number' => $cottageInfo->cottageNumber, 'pay_type' => 'membership', 'period' => $item]);
                    if (!empty($savedFine)) {
                        $payedFines = Table_payed_fines::find()->where(['fine_id' => $savedFine->id])->andWhere(['<', 'pay_date', $end])->all();
                    }
                    $payedFineAmount = 0;
                    if (!empty($payedFines)) {
                        foreach ($payedFines as $payedFine) {
                            $payedFineAmount += CashHandler::toRubles($payedFine->summ);
                        }
                    }
                    $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                    if ($fineDuty > 0) {
                        $finesDetails .= "Ч* " . $item . ' : ' . $fineDuty . "<br/>\n";
                        $finesSumm += $fineDuty;
                    }

                }
            }
        } else {
            $payedAmount = 0;
            // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
            foreach ($payed as $pay) {
                $payedAmount += CashHandler::toRubles($pay->summ);
            }
            $payedAmount = CashHandler::toRubles($payedAmount);
            if ($payedAmount < $total) {
                $difference = CashHandler::toRubles($accrued - $payedAmount);
                $membershipDetails .= $item . ' : ' . $difference . "<br/>\n";
                $total += $difference;
            }
        }
    }
    $membershipDuty = CashHandler::toRubles($total);
}
// целевые
$targetDetails = '';
$total = 0;
// получу первый и последний годы расчёта
$lastYear = TimeHandler::getYearFromTimestamp($end);

$firstYear = Table_tariffs_target::find()->orderBy('year')->one()->year;

//  получу список лет
$yearsList = TimeHandler::getYearsList($firstYear, $lastYear);
$existentTargets = TargetHandler::getDebt($cottageInfo);
foreach ($yearsList as $year) {
    // получу данные
    // получу начисление за квартал
    if ($cottageInfo->individualTariff) {
        $tariff = PersonalTariff::getTargetRate($cottageInfo, $year);
        if (!empty($tariff)) {
            $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $cottageInfo->cottageSquare));
        }
    } else {
        $tariff = Table_tariffs_target::findOne(['year' => $year]);
        if (!empty($tariff)) {
            $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $cottageInfo->cottageSquare));
        }
    }
    if (!empty($accrued)) {

        // найду оплаты за данный период
        $payed = Table_payed_target::find()->where(['year' => $year, 'cottageId' => $transactionsInfo['cottageInfo']->cottageNumber])->andWhere(['<=', 'paymentDate', $end])->all();
        if (empty($payed)) {
            // возможно, оплата была до введения системы
            // проверю, если год присутствует в задолженностях участка- значит он не оплачен, если нет- значит, оплачен ранее
            if (!empty($existentTargets[$year])) {
                $targetDetails .= $year . ' : ' . $accrued . "<br/>\n";
                $total += $accrued;

                $payUp = Table_tariffs_target::find()->where(['year' => $year])->one()->payUpTime;
                if ($payUp < FinesHandler::START_POINT) {
                    $payUp = FinesHandler::START_POINT;
                }
                // если месяц не оплачен и прошел срок выплат- считаю пени
                if ($payUp < $end) {
                    // посчитаю сумму пени
                    // посчитаю количество дней задолженности
                    $dayDifference = TimeHandler::checkDayDifference($payUp, $end);
                    if ($dayDifference > 0) {
                        $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                        $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                        // теперь попробую найти оплату по данному пени
                        $savedFine = Table_penalties::findOne(['cottage_number' => $cottageInfo->cottageNumber, 'pay_type' => 'target', 'period' => $year]);
                        $payedFines = Table_payed_fines::find()->where(['fine_id' => $savedFine->id])->andWhere(['<', 'pay_date', $end])->all();
                        $payedFineAmount = 0;
                        if (!empty($payedFines)) {
                            foreach ($payedFines as $payedFine) {
                                $payedFineAmount += CashHandler::toRubles($payedFine->summ);
                            }
                        }
                        $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                        if ($fineDuty > 0) {
                            $finesDetails .= "Ц* " . $item . ' : ' . $fineDuty . "<br/>\n";
                            $finesSumm += $fineDuty;
                        }

                    }
                }
            }
        } else {
            $payedAmount = 0;
            // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
            foreach ($payed as $pay) {
                $payedAmount += CashHandler::toRubles($pay->summ);
            }
            $payedAmount = CashHandler::toRubles($payedAmount);
            if ($payedAmount < $total) {
                $difference = CashHandler::toRubles($accrued - $payedAmount);
                $targetDetails .= $year . ' : ' . $difference . "<br/>\n";
                $total += $difference;
            }
        }
        $targetDuty = CashHandler::toRubles($total);
    }
}

$singleDetails = '';
$accrued = 0;
// получу разовые платежи по участку
$duties = SingleHandler::getDebtReport($cottageInfo);
// если дата задолженности раньше конца периода- считаю в задолженность
if (!empty($duties)) {
    foreach ($duties as $duty) {
        if ($duty->time < $end) {
            // проверю назначение платежа
            $description = urldecode($duty->description);
            $accrued += CashHandler::toRubles(CashHandler::toRubles($duty->amount) - CashHandler::toRubles($duty->partialPayed));
            if (stripos($description, "электроэнергии")) {
                $singleDetails .= "(Э*) " . $accrued;
            }
        }
    }
}
$singleDuty = CashHandler::toRubles($accrued);

// пени
?>

<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Квитанции</title>
    <style type="text/css">
        .small-text {
            font-size: 9px;
        }

        .table-bordered {
            border: 1px solid #ddd;
        }

        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }


        table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
            padding: 8px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }

        .table-bordered > thead > tr > th, .table-bordered > tbody > tr > th, .table-bordered > tfoot > tr > th, .table-bordered > thead > tr > td, .table-bordered > tbody > tr > td, .table-bordered > tfoot > tr > td {
            border: 1px solid #ddd;
        }

        .text-center {
            text-align: center;
            vertical-align: top;
        }

        p {
            line-height: 2;
        }
    </style>
</head>
<body>

<h3>Отчёт по платежам участка</h3>

<p>
    Участок № <?= /** @var $transactionsInfo [] */
    $transactionsInfo['cottageInfo']->cottageNumber ?>, Площадь: <?= $transactionsInfo['cottageInfo']->cottageSquare ?>м<sup>2</sup>
    Владелец: <?= $transactionsInfo['cottageInfo']->cottageOwnerPersonals ?>
</p>
<p>
    Период с <?= /** @var string $start */
    TimeHandler::getDateFromTimestamp($start) ?> по <?= /** @var string $end */
    TimeHandler::getDateFromTimestamp($end) ?>
</p>

<table class="table table-bordered table-condensed little-text small-text">
    <thead>
    <tr>
        <th rowspan="2" class="text-center vertical-top">Дата</th>
        <th rowspan="2" class="text-center vertical-top">№ сч.</th>
        <th colspan="2" class="text-center">Членские</th>
        <th colspan="3" class="text-center">Электричество</th>
        <th colspan="2" class="text-center">Целевые</th>
        <th colspan="2" class="text-center">Разовые</th>
        <th colspan="2" class="text-center">Пени</th>
        <th rowspan="2" class="text-center vertical-top">Депозит</th>
        <th rowspan="2" class="text-center vertical-top">Итого</th>
    </tr>
    <tr>
        <th class="text-center">Покварт.</th>
        <th class="text-center">Итого</th>
        <th class="text-center">Показ.</th>
        <th class="text-center">Всего</th>
        <th class="text-center">Опл.</th>
        <th class="text-center">По годам</th>
        <th class="text-center">Итого</th>
        <th class="text-center">Дет.</th>
        <th class="text-center">Итог</th>
        <th class="text-center">Дет.</th>
        <th class="text-center">Итог</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if (!empty($transactionsInfo['content'])) {
        foreach ($transactionsInfo['content'] as $item) {
            echo $item;
        }
    }
    ?>

    <tr>
        <td colspan="16" class="text-center"><h3>Информация по задолженностям
                на <?= TimeHandler::getDatetimeFromTimestamp($end) ?></h3></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td class="text-center"><?= $membershipDetails ?></td>
        <td class="text-center"><?= $membershipDuty ?></td>
        <td class="text-center"><?= $powerDetails ?></td>
        <td></td>
        <td class="text-center"><?= $powerDebt ?></td>
        <td class="text-center"><?= $targetDetails ?></td>
        <td class="text-center"><?= $targetDuty ?></td>
        <td class="text-center"><?= $singleDetails ?></td>
        <td class="text-center"><?= $singleDuty ?></td>
        <td class="text-center"><?= $finesDetails ?></td>
        <td class="text-center"><?= $finesSumm ?></td>
        <td></td>
        <td class="text-center"><?= CashHandler::toRubles($membershipDuty + $powerDebt + $targetDuty + $singleDuty + $finesSumm) ?></td>
    </tr>

    </tbody>


</table>

<div>
    <?php
    if (!empty($finesSumm)) {
        echo '<p class="small-text">Э* - пени на задолженность по оплате электроэнергии</p>
    <p class="small-text">Ц* - пени на задолженность по оплате целевых взносов</p>
    <p class="small-text">Ч* - пени на задолженность по оплате членских взносов</p>';
    }
    ?>


    <?php
    $counter = 1;
    if (!empty($transactionsInfo['singleDescriptions']))
        foreach ($transactionsInfo['singleDescriptions'] as $item) {
            echo "<p class='small-text'>($counter)* : $item</p>";
            $counter++;
        }
    ?>
</div>

</body>
</html>
