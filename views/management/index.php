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
        <button class="btn btn-default" id="fixButton"><span class="text-info">Нажать для исправления</span></button>
    </div>
</div>
