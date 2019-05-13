<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 20:16
 */

use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this \yii\web\View */
$form = ActiveForm::begin(['id' => 'PersonalTariff', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false]);
echo "<input type='text' name='PersonalTariff[cottageNumber]' class='hidden' value='{$cottageNumber}'/>";
if(!empty($requirements['membership'])){
    echo "<h2 class='text-center'>Тарифы на членские взносы</h2>";
    foreach ($requirements['membership'] as $key => $quarter) {
        echo "<h3>" . TimeHandler::getFullFromShortQuarter($key) . "</h3>";
        echo "<div class='form-group membership-group'>
                    <div class='col-lg-5'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='PersonalTariff[membership][$key][fixed]' class='required form-control float mem-fixed ready' value='{$quarter['fixed']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='PersonalTariff[membership][$key][float]' class='required form-control float mem-float ready' value='{$quarter['float']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
    }
}
if(!empty($requirements['target'])){
    echo "<h2 class='text-center'>Тарифы на целевые взносы</h2>";
    foreach ($requirements['target'] as $key => $year) {
        echo "<h3>" . $key . "</h3>";
        echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='PersonalTariff[target][{$key}][fixed]' class='required form-control float mem-fixed ready' value='{$year['fixed']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='PersonalTariff[target][{$key}][float]' class='required form-control float mem-float ready' value='{$year['float']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Оплачено ранее</span>
                        <input type='text' name='PersonalTariff[target][{$key}][payed-before]' class='required form-control float mem-float ready'  value='{$year['payed-before']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    </div>";
    }
}
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
ActiveForm::end();