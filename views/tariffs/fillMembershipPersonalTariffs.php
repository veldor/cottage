<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.10.2018
 * Time: 13:26
 */

/* @var $this \yii\web\View */

/* @var $unfilledTariffs array */
/* @var $cottageNumber int */
/* @var $period string */

use app\assets\FillAsset;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = "Заполнение тарифов на членские платежи";

/* @var $this \yii\web\View */


FillAsset::register($this);

?>
<h2>Необходимо заполнить тарифы членских платежей за следующие кварталы-</h2>
<div class="row">
    <?php
    $form = ActiveForm::begin(['id' => 'PersonalTariff', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,]);
    echo "<input type='text' name='PersonalTariff[cottageNumber]' class='hidden' value='{$cottageNumber}'/>";
    if (!empty($unfilledTariffs)) {
        foreach ($unfilledTariffs as $key => $value) {
            echo "<h3>" . TimeHandler::getFullFromShortQuarter($key) . "</h3>";
            echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='PersonalTariff[membership][$key][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='PersonalTariff[membership][$key][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
        }
        echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
    }
    ActiveForm::end();
    ?>
</div>
<div id="alertsContentDiv"></div>
