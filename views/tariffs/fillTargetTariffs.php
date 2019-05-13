<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.10.2018
 * Time: 13:26
 */

/* @var $this \yii\web\View */
/* @var $unfilledTariffs null */

use app\assets\FillAsset;
use app\widgets\FillWidget;

$this->title = "Заполнение тарифов на членские платежи";

/* @var $this \yii\web\View */


FillAsset::register($this);
?>
<h2>Необходимо заполнить тарифы членских платежей за следующие кварталы-</h2>

<div class="row">
    <form id="targetPaymentsForm">
        <?=FillWidget::widget(['periods' => $unfilledTariffs]);?>
    </form>
</div>
<div id="alertsContentDiv"></div>
