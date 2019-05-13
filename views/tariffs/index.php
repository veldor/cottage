<?php

/* @var $this yii\web\View */

use app\assets\TariffsAsset;
use app\widgets\MembershipStatisticWidget;
use app\widgets\PowerStatisticWidget;
use app\widgets\TargetStatisticWidget;
use nirvana\showloading\ShowLoadingAsset;

/** @var app\models\TariffsKeeper $lastTariffs */

/* @var $this yii\web\View */
ShowLoadingAsset::register($this);
TariffsAsset::register($this);


$this->title = 'Центр управления';
echo '<h2>Электроэнергия</h2>';
echo PowerStatisticWidget::widget(['monthInfo' => $lastTariffs->power]);
echo '<h2>Членские взносы</h2>';
echo MembershipStatisticWidget::widget(['quarterInfo' => $lastTariffs->membership]);
echo '<h2>Целевые взносы</h2>';
if(!empty($lastTariffs->target)){
	echo TargetStatisticWidget::widget(['yearInfo' => $lastTariffs->target]);
}
else{
	echo "<button id='createTargetPayment' class='btn btn-success'>Создать целевой платёж</button>";
}
