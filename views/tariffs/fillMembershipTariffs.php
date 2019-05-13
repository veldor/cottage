<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.10.2018
 * Time: 13:26
 */

/* @var $this \yii\web\View */

/* @var $unfilledTariffs array */
/* @var $period string */

use app\assets\FillAsset;
use app\models\CashHandler;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Заполнение тарифов на членские платежи';

/* @var $this \yii\web\View */


FillAsset::register($this);

?>
<h2>Необходимо заполнить тарифы членских платежей за следующие кварталы-</h2>
<div class="row">
<?php
$form = ActiveForm::begin(['id' => 'tarrifsSetupForm', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,'action'=>['/fill/membership/' . $period]]);
    echo '<h3>Последняя заполненная ставка</h3>';
    echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' class=' form-control mem-fixed disabled' disabled value='" . CashHandler::toRubles($unfilledTariffs['lastTariffData']['fixed']) . "'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' class='form-control mem-float disabled' disabled value='" . CashHandler::toRubles($unfilledTariffs['lastTariffData']['float']) . "'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
    foreach ($unfilledTariffs['quarters'] as $key => $value) {
        $button = $key === array_key_last($unfilledTariffs['quarters']) ? "" : "<button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>";
        echo "<h3>" . TimeHandler::getFullFromShortQuarter($key) . "</h3>";
        echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='MembershipHandler[membership][$key][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='MembershipHandler[membership][$key][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        $button
                     </div>
                    </div>";
    }
    echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
ActiveForm::end();
?>
</div>
<div id="alertsContentDiv"></div>
