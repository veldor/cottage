<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.09.2018
 * Time: 16:06
 */

/* @var $this \yii\web\View */
/* @var $model \app\models\Filling */
use app\assets\FillingAsset;
use nirvana\showloading\ShowLoadingAsset;
use app\widgets\AllCottagesWidget;

/* @var $this yii\web\View */
FillingAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Массовое заполнение форм';
/** @var array $info */
?>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active"><a href="#power" data-toggle="tab">Электроэнергия</a></li>
    <li><a href="#bills" data-toggle="tab">Счета</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div class="tab-pane active" id="power">
        <div class="row show-grid small-text">
            <?=AllCottagesWidget::widget(['info' => $info]);?>
        </div>
    </div>
    <div class="tab-pane" id="bills">
        <div class="btn-group-vertical margened">
            <button id="showAllBillsActivator" class="btn btn-success">Показать все неоплаченные счета</button>
            <button id="makeAllBillsActivator" class="btn btn-warning">Сформировать счета</button>
        </div>
        <div id="billsWrapper"></div>
    </div>
</div>


