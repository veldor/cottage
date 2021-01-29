<?php

use app\assets\AdditionalCottageAsset;
use app\models\Table_cottages;
use app\models\utils\NewFinesHandler;
use app\widgets\ShowPenaltiesWidget;
use yii\web\View;


/* @var $this View */
/* @var $cottage Table_cottages */
/* @var $finesHandler NewFinesHandler */

AdditionalCottageAsset::register($this);

?>

<div class="text-center">
    <div class="btn-group">
        <a href="<?= '/show-cottage/' . ($cottage->cottageNumber)?>"
                class="btn btn-info"><span class="glyphicon glyphicon-level-up"></span></a>
        <a href="<?= $cottage->cottageNumber > 1 ? '/additional-actions/' . ($cottage->cottageNumber - 1) : '#' ?>"
                class="btn btn-success"><span class="glyphicon glyphicon-backward"></span></a>
        <a href="<?= $cottage->cottageNumber < 180 ? '/additional-actions/' . ($cottage->cottageNumber + 1) : '#' ?>"
                class="btn btn-success"><span class="glyphicon glyphicon-forward"></span></a></div>
</div>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#fines_tab" data-toggle="tab" class="active">Пени</a></li>
    <li><a href="#misc_tab" data-toggle="tab">Всякое</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="fines_tab">
        <table class="table table-striped table-condensed table-hover">
            <tr>
                <th>Тип</th>
                <th>Период</th>
                <th>Статус</th>
                <th>Срок оплаты</th>
                <th>Дата оплаты</th>
                <th>Просрочено</th>
                <th>Долг</th>
                <th>В день</th>
                <th>Начислено</th>
                <th>Оплачено</th>
                <th>Оплачено полностью</th>
            </tr>
            <?= /** @noinspection PhpUnhandledExceptionInspection */(new ShowPenaltiesWidget(['penalties' => $finesHandler->getPowerFines()]))->run() ?>
        </table>
    </div>
    <div class="tab-pane" id="misc_tab">

    </div>
</div>
