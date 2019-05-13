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
<!--    <div class="col-lg-12 margened">
        <div class="btn-group-vertical">
            <button class="btn btn-info" id="refreshPowerData">Обновить последние данные по электроэнергии</button>
            <button class="btn btn-info" id="fixBillInfo">Исправить данные платежей</button>
            <button class="btn btn-info" id="fixTargetInfo">Исправить данные целевого платежа</button>
        </div>
        <div class="btn-group-vertical">
            <button class="btn btn-success" id="recalculatePowerTariffs">Пересчитать тарифы по электроэнергии</button>
            <button class="btn btn-success" id="recalculateMembershipTariffs">Пересчитать тарифы по членским взносам</button>
            <button class="btn btn-success" id="recalculateTargetTariffs">Пересчитать тарифы по целевым взносам</button>
        </div>
        <div class="btn-group-vertical">
            <button class="btn btn-danger" id="recountPayments">Найти копейки</button>
        </div>
    </div>-->
</div>
