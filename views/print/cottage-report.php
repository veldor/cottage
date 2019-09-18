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

<h3>Отчёт по платежам участка</h3>

<p>
    Участок № <?= $transactionsInfo['cottageInfo']->cottageNumber ?>, Площадь: <?= $transactionsInfo['cottageInfo']->cottageSquare ?>м<sup>2</sup> Владелец: <?= $transactionsInfo['cottageInfo']->cottageOwnerPersonals ?>
</p>
<p>
    Отчет по платежам с <?=TimeHandler::getDateFromTimestamp($start)?> по <?=TimeHandler::getDateFromTimestamp($end)?>
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
        <th rowspan="2" class="text-center vertical-top">Скид.</th>
        <th rowspan="2" class="text-center vertical-top">Деп</th>
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
    </tbody>
</table>
