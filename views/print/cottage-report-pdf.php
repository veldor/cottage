<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 22:50
 */

use app\assets\printAsset;
use app\models\TimeHandler;
use yii\web\View;

printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this View */
?>

<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Квитанции</title>
    <style type="text/css">
        .small-text {
            font-size: 10px;
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
        }

        p{
            line-height: 2;
        }
    </style>
</head>
<body>

<h3>Отчёт по платежам участка</h3>

<p>
    Участок № <?= /** @var $transactionsInfo [] */
    $transactionsInfo['cottageInfo']->cottageNumber ?>, Площадь: <?= $transactionsInfo['cottageInfo']->cottageSquare ?>м<sup>2</sup> Владелец: <?= $transactionsInfo['cottageInfo']->cottageOwnerPersonals ?>
</p>
<p>
    Отчет по платежам с <?= /** @var string $start */
    TimeHandler::getDateFromTimestamp($start)?> по <?= /** @var string $end */
    TimeHandler::getDateFromTimestamp($end)?>
</p>

<table class="table table-bordered table-condensed little-text small-text">
    <thead>
    <tr>
        <th rowspan="2" class="text-center vertical-top">Дата</th>
        <th rowspan="2" class="text-center vertical-top">№</th>
        <th rowspan="2" class="text-center vertical-top">Уч.</th>
        <th colspan="2" class="text-center">Членские</th>
        <th colspan="3" class="text-center">Электричество</th>
        <th colspan="2" class="text-center">Целевые</th>
        <th colspan="2" class="text-center">Разовые</th>
        <th colspan="2" class="text-center">Пени</th>
        <th rowspan="2" class="text-center vertical-top">Скидка</th>
        <th rowspan="2" class="text-center vertical-top" style="-webkit-transform: rotate(90deg); transform: rotate(90deg);">Депозит</th>
        <th rowspan="2" class="text-center vertical-top" style="-webkit-transform: rotate(90deg); transform: rotate(90deg);">Итого</th>
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
    </tbody>

</table>

<table class="table table-bordered table-condensed little-text small-text">
</table>

</body>
</html>
