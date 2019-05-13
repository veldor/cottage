<?php

/* @var $this yii\web\View */
use app\assets\IndexAsset;
use nirvana\showloading\ShowLoadingAsset;
use app\widgets\CottagesShowWidget;

/** @var \app\models\Table_cottages $existedCottages */
/* @var $this yii\web\View */
ShowLoadingAsset::register($this);
IndexAsset::register($this);

$this->title = 'Центр управления';
?>
<div class="row">
    <div class="col-lg-12">
        <p>
            <!--<button type="button" id="addCottageBtn" class="btn btn-success">Добавить участок</button>-->
        </p>
    </div>
    <div class="col-lg-12">
        <?=CottagesShowWidget::widget(['cottages' => $existedCottages]);?>
    </div>
</div>
