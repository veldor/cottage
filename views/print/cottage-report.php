<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 22:50
 */

use app\assets\printAsset;
use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\MembershipHandler;
use app\models\Table_cottages;
use app\models\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;
ShowLoadingAsset::register($this);
printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this View */
/* @var $cottageInfo Table_cottages */
?>

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
        <th rowspan="2" class="text-center vertical-top"  style="-webkit-transform: rotate(90deg); transform: rotate(90deg);">Скидка</th>
        <th rowspan="2" class="text-center vertical-top"  style="-webkit-transform: rotate(90deg); transform: rotate(90deg);">Депозит</th>
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
        <td colspan="17" class="text-center"><h3>Информация по задолженностям на <?=TimeHandler::getDatetimeFromTimestamp(time())?></h3></td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td>
            <?php
            $total = 0;
            $debt = MembershipHandler::getDebt($cottageInfo);
            if(!empty($debt)){
                foreach ($debt as $item) {
                    //echo $item->quarter . " : " . CashHandler::toRubles($item->amount);
                    $total += $item->amount;
                }
            }
            ?>
        </td>
        <td>
            <?=CashHandler::toRubles($total) ?>
        </td>
        <td></td>
        <td><?=CashHandler::toRubles($cottageInfo->powerDebt)?></td>
        <td></td>
        <td></td>
        <td><?= CashHandler::toRubles($cottageInfo->targetDebt)?></td>
        <td></td>
        <td><?= CashHandler::toRubles($cottageInfo->singleDebt)?></td>
        <td></td>
        <td><?= FinesHandler::getFinesSumm($cottageInfo->cottageNumber);?></td>
        <td></td>
        <td></td>
        <td><?= CashHandler::toRubles($cottageInfo->targetDebt + $cottageInfo->singleDebt + $cottageInfo->powerDebt + $total + FinesHandler::getFinesSumm($cottageInfo->cottageNumber))?></td>
    </tr>

    </tbody>

</table>
<div class="row">
    <div class="col-sm-12 text-center">
        <button id="sendReportButton" class="btn btn-default no-print"><span class="text-success">Отправить отчёт владельцу</span></button>
        <a class="btn btn-default no-print" target="_blank" href="/report.pdf"><span class="text-success">Скачать PDF</span></a>
    </div>
</div>
<span class="hidden" id="cottageNumber"><?=$transactionsInfo['cottageInfo']->cottageNumber?></span>
