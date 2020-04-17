<?php

use app\models\Table_tariffs_power;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $matrix Table_tariffs_power */

$form = ActiveForm::begin(['id' => 'changePower>', 'options' => ['class' => 'form-horizontal bg-default'], 'action' => ['/change-tariff/power/' . $matrix->targetMonth]]);


echo $form->field($matrix, 'id', ['template' => '{input}'])->hiddenInput()->label(false);

echo $form->field($matrix, 'powerLimit', ['template' =>
    '<div class="col-sm-6">{label}</div><div class="col-sm-6">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number']);
echo $form->field($matrix, 'powerCost', ['template' =>
    '<div class="col-sm-6">{label}</div><div class="col-sm-6">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number', 'step' => '0.01']);
echo $form->field($matrix, 'powerOvercost', ['template' =>
    '<div class="col-sm-6">{label}</div><div class="col-sm-6">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number', 'step' => '0.01']);

echo "<div class='clearfix'></div>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();