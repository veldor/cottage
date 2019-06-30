<?php

use app\assets\ManagementAsset;
use nirvana\showloading\ShowLoadingAsset;

/* @var $this yii\web\View */
ManagementAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Всякие разные настройки';
?>
<div class="row">
    <div class="col-lg-12 margened">
        <?php if (Yii::$app->user->can('manage')) {
            echo '<button class="btn btn-primary" id="createUpdateButton">Создать обновление</button>';
        }?>
        <button class="btn btn-primary" id="checkUpdateButton">Проверить обновления </button>
    </div>
    <div class="btn-group-vertical">
        <button id="count_penalties" class="btn btn-info">Посчитать все пени</button>
    </div>
</div>
