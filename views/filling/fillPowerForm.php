<?php

use app\models\PowerHandler;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $model PowerHandler */
$form = ActiveForm::begin(['id' => 'powerHandler', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false]);
echo $form->field($model, 'cottageNumber', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'month', ['template' => '{input}'])->hiddenInput()->label(false);

// информация
echo "<div class='alert alert-info text-center'>Заполнение данных по участку №{$model->cottageNumber}</div>";
echo "<div class='alert alert-info text-center'>" . TimeHandler::getFullFromShotMonth($model->month) . "</div>";

echo $form->field($model, 'newPowerData', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3"><div class="input-group"><span class="input-group-addon">' . $model->currentCondition->currentPowerData . ' >></span> {input}</div> {error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number'])
    ->hint('В кВт.ч')
    ->label('Израсходовано');

echo $form->field($model, 'doChangeCounter', ['template' =>
    '<div class="col-lg-5"></div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->checkbox(['labelOptions' => ['class' => 'btn btn-info']]);

echo $form->field($model, 'counterChangeType',['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group hidden']])
    ->radioList(['simple' => 'С начала периода', 'difficult' => 'До начала периода'], ['item' => function ($index, $label, $name, $checked, $value){
       return "<label class='btn btn-info'>$label<input type='radio' name='$name' value='$value'/></label>";
    }])
    ->label('Вариант замены');

echo $form->field($model, 'newCounterStartData', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group hidden']])
    ->textInput(['autocomplete' => 'off', 'type' => 'number'])
    ->hint('В кВт.ч')
    ->label('Стартовые показания нового счётчика');
echo $form->field($model, 'newCounterFinishData', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>', 'options' => ['class' => 'form-group hidden']])
    ->textInput(['autocomplete' => 'off', 'type' => 'number'])
    ->hint('В кВт.ч')
    ->label('Конечные показания нового счётчика');


echo "<div class='text-center margened'>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
ActiveForm::end();