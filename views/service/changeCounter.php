<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 10:57
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this \yii\web\View */
/* @var $matrix \app\models\PowerCounter */

$form = ActiveForm::begin(['id' => 'changeCounterForm', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'action' => ['/service/change-counter/' . $matrix->cottageNumber]]);
/** @var \app\models\AddCottage $matrix */

echo $form->field($matrix, 'cottageNumber', ['template' => "{input}"])->hiddenInput()->label(false);

echo "<fieldset class='color-salad'><legend>Сведения об старом счётчике</legend>";

echo $form->field($matrix, 'oldCounterStartData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7"><div class="input-group">{input}<span class="input-group-addon">кВт.ч</span></div>
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Старые показания счётчика."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'readonly' => true])
    ->label('Старые показания старого счётчика электроэнергии.')
    ->hint("<b class='text-info'>Неизменяемое поле.</b> Только для информации");

echo $form->field($matrix, 'oldCounterEndData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7"><div class="input-group">{input}<span class="input-group-addon">кВт.ч</span></div>
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Последние показания счётчика."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, ' . $matrix->oldCounterStartData])
    ->label('Последние показания старого счётчика электроэнергии.')
    ->hint("<b class='text-info'>Обязательное поле.</b> Должны быть больше или равны последним показаниям этого счётчика");

echo "</fieldset>";

echo "<fieldset class='color-orange'><legend>Сведения о новом счётчике</legend>";

echo $form->field($matrix, 'newCounterData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7"><div class="input-group">{input}<span class="input-group-addon">кВт.ч</span></div>
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Последние показания счётчика."></button></div>'])
    ->textInput(['autocomplete' => 'off', 'placeholder' => 'Например, 0'])
    ->label('Показания нового счётчика электроэнергии.')
    ->hint("<b class='text-info'>Обязательное поле.</b> Введите начальные показания нового счётчика");

echo "</fieldset>";

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);

ActiveForm::end();