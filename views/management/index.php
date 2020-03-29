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
        <button class="btn btn-default" id="sendBackupButton"><span class="text-info">Отправить бекап</span></button>
        <button class="btn btn-default activator" id="createReportBtn" data-action="/report/choose-date"><span class="text-info">Сформировать отчёт</span></button>
        <button class="btn btn-default activator" data-action="/service/recount-fines"><span class="text-info">Пересчитать пени</span></button>
    </div>
</div>
