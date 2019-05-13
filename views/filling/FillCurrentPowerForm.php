<?php

use app\models\SingleHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model SingleHandler */
$form = ActiveForm::begin(['id' => 'powerHandler', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false]);
echo '<div class=row>';
echo $form->field($model, 'cottageNumber', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'month', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'newPowerData', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3"><div class="input-group"><span class="input-group-addon">' . $status['lastData'] . ' >></span> {input}</div> {error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('В кВт.ч')
    ->label('Израсходовано');
echo "<div class='text-center margened'>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
echo '</div>';
ActiveForm::end();