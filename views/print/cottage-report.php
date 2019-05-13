<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 22:50
 */

use app\models\TimeHandler;

\app\assets\printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this \yii\web\View */
?>

<h3>Отчёт по платежам участка</h3>

<p>
    Участок № <?= $transactionsInfo['cottageInfo']->cottageNumber ?>, Площадь: <?= $transactionsInfo['cottageInfo']->cottageSquare ?>м<sup>2</sup> Владелец: <?= $transactionsInfo['cottageInfo']->cottageOwnerPersonals ?>
</p>
<p>
    Отчет по платежам с <?=TimeHandler::getDateFromTimestamp($start)?> по <?=TimeHandler::getDateFromTimestamp($end)?>
</p>

<table class="table table-bordered table-condensed">
    <thead>
    <tr>
        <th rowspan="2" class="text-center vertical-top">Дата</th>
        <th colspan="2" class="text-center">Членские</th>
        <th colspan="3" class="text-center">Электричество</th>
        <th colspan="2" class="text-center">Целевые</th>
        <th colspan="2" class="text-center">Разовые</th>
        <th rowspan="2" class="text-center vertical-top">Скидка</th>
        <th rowspan="2" class="text-center vertical-top">Депозит</th>
        <th rowspan="2" class="text-center vertical-top">Итого</th>
        <th rowspan="2" class="text-center vertical-top">Вид</th>
    </tr>
    <tr>
        <th class="text-center">Поквартально</th>
        <th class="text-center">Итого</th>
        <th class="text-center">Показания</th>
        <th class="text-center">Всего</th>
        <th class="text-center">Оплачено</th>
        <th class="text-center">По годам</th>
        <th class="text-center">Итого</th>
        <th class="text-center">Отдельно</th>
        <th class="text-center">Итого</th>
    </tr>
    </thead>
    <tbody>
    <?php
    if(!empty($transactionsInfo['content'])){
        foreach ($transactionsInfo['content'] as $item) {
            echo $item;
        }
    }
    ?>
    </tbody>
</table>
