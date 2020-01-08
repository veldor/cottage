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
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

ShowLoadingAsset::register($this);
printAsset::register($this);

$this->title = "Отчёт по платежам";

/* @var $this View */
/* @var $cottageInfo Table_cottages */
/** @var string $end */
/** @var $transactionsInfo [] */
/** @var string $start */

$duty = new CottageDutyReport($cottageInfo, $end);
?>

<h3>Отчёт по платежам участка</h3>

<p>
    Участок № <?=
    $transactionsInfo['cottageInfo']->cottageNumber ?>, Площадь: <?= $transactionsInfo['cottageInfo']->cottageSquare ?>м<sup>2</sup>
    Владелец: <?= $transactionsInfo['cottageInfo']->cottageOwnerPersonals ?>
</p>
<p>
    Период с <?=
    TimeHandler::getDateFromTimestamp($start) ?> по <?=
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
        <td><?= $duty->targetDetails ?></td>
        <td><?= $duty->targetAmount ?></td>
        <td><?= $duty->signleDetails ?></td>
        <td><?= $duty->singleAmount ?></td>
        <td><?= $duty->fineDetails ?></td>
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
    if(!empty($transactionsInfo['singleDescriptions']))
        foreach ($transactionsInfo['singleDescriptions'] as $item) {
            echo "<p class='small-text'>($counter)* : $item</p>";
            $counter++;
    }
    ?>
</div>

<div class="row">
    <div class="col-sm-12 text-center">
        <button id="sendReportButton" class="btn btn-default no-print"><span class="text-success">Отправить отчёт владельцу</span>
        </button>
        <a class="btn btn-default no-print" target="_blank" href="/report.pdf"><span
                    class="text-success">Скачать PDF</span></a>
    </div>
</div>
<span class="hidden" id="cottageNumber"><?= $transactionsInfo['cottageInfo']->cottageNumber ?></span>
