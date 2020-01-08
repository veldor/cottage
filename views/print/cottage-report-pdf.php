<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 22:50
 */

use app\assets\printAsset;
use app\models\CashHandler;
use app\models\Table_cottages;
use app\models\TimeHandler;
use app\models\utils\CottageDutyReport;
use yii\web\View;

printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this View */
/* @var $cottageInfo Table_cottages */
/** @var string $end */
/** @var $transactionsInfo [] */
/** @var string $start */


$duty = new CottageDutyReport($cottageInfo, $end);
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
            vertical-align: top;
            border-top: 1px solid #ddd;
            line-height: 2;
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
        <td><?= $duty->membershipDetails ?></td>
        <td>
            <?= $duty->membershipAmount ?>
        </td>
        <td><?= $duty->powerDetails ?></td>
        <td></td>
        <td><?= $duty->powerAmount ?></td>
        <td class="text-center vertical-top"><?= substr($duty->targetDetails, 0, strlen($duty->targetDetails) - 6) ?></td>
        <td><?= $duty->targetAmount ?></td>
        <td><?= $duty->signleDetails ?></td>
        <td><?= $duty->singleAmount ?></td>
        <td><?= substr($duty->fineDetails, 0, strlen($duty->fineDetails) - 6) ?></td>
        <td><?= $duty->fineAmount ?></td>
        <td></td>
        <td><?= CashHandler::toRubles($duty->membershipAmount + $duty->powerAmount + $duty->targetAmount + $duty->singleAmount + $duty->fineAmount) ?></td>
    </tr>

    </tbody>


</table>

<div>
    <?php
    if (!empty($duty->fineAmount)) {
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
