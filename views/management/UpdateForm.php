<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;


$form = ActiveForm::begin(['id' => 'updateForm', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/update/validate']]);
/** @var \yii\base\model $matrix */
echo $form->field($matrix, 'version' ,['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-4">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Введите сюда формулировку вопроса, ответ на который будет дан в поле ввода."></button></div>'])
    ->textInput(['autocomplete' => 'off'])
    ->label('Версия обновления.');
echo $form->field($matrix, 'description' ,['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-4">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Введите сюда формулировку вопроса, ответ на который будет дан в поле ввода."></button></div>'])
    ->textarea(['autocomplete' => 'off'])
    ->label('Описание изменений.');
echo Html::submitButton('Создать', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
ActiveForm::end();