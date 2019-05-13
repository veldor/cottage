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
$form = ActiveForm::begin(['id' => 'PersonalTariff', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false]);
echo "<input type='text' name='PersonalTariff[cottageNumber]' class='hidden' value='{$requirements['cottageNumber']}'/>";
if (!empty($requirements['additional'])) {
    echo "<input type='text' name='PersonalTariff[additional]' class='hidden' value='{$requirements['additional']}'/>";
}
if (!empty($requirements['quarters'])) {
    echo "<h2 class='text-center'>Тарифы на членские взносы</h2>";
    foreach ($requirements['quarters'] as $key => $quarter) {
        $button = $key === array_key_last($requirements['quarters']) ? "" : "<button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>";
        echo '<h3>' . TimeHandler::getFullFromShortQuarter($key) . '</h3>';
        echo <<<EOT
<div class='form-group membership-group'>
    <div class='col-sm-5'>
        <div class='input-group'>
        <span class='input-group-addon'>С дачи</span>
        <input type='number' step='0.01' name='PersonalTariff[membership][$key][fixed]' class='required form-control float mem-fixed'/>
        <span class='input-group-addon'>&#8381;</span>
     </div>
     </div>
        <div class='col-sm-4'>
        <div class='input-group'>
        <span class='input-group-addon'>С сотки</span>
        <input type='number' step='0.01' name='PersonalTariff[membership][$key][float]' class='required form-control float mem-float'/>
        <span class='input-group-addon'>&#8381;</span>
        </div>
    </div>
        $button
</div>
EOT;
    }
} else {
    echo '<h3>Заполнение тарифов на членские взносы не требуется</h3>';
}
if (!empty($requirements['years'])) {
    echo "<h2 class='text-center'>Тарифы на целевые взносы</h2>";
    foreach ($requirements['years'] as $key => $year) {
        $button = $key === array_key_last($requirements['years']) ? "" : "<button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>";
        echo '<h3>' . $year->year . '</h3>';
        echo "<div class='form-group membership-group'>
                    <div class='col-sm-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>С дачи</span>
                        <input type='number' step='0.01' name='PersonalTariff[target][$year->year][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-sm-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>С сотки</span>
                        <input type='number' step='0.01' name='PersonalTariff[target][$year->year][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-sm-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Оплачено</span>
                        <input type='number' step='0.01' name='PersonalTariff[target][$year->year][payed-before]' class='required form-control float mem-float ready' value='0'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                        $button
                    </div>";
    }
} else {
    echo '<h3>Заполнение тарифов на целевые взносы не требуется</h3>';
}
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();