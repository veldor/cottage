<?php

use app\models\SingleHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

echo '<div class=row>';
/* @var $matrix SingleHandler */
$form = ActiveForm::begin(['id' => 'targetHandler', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/tariffs/create-target']]);
echo $form->field($matrix, 'year', ['options' => ['class' => 'form-group hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'fixed', ['options' => ['class' => 'form-group col-lg-12'], 'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('В рублях')
    ->label('Цена участка');
echo $form->field($matrix, 'float', ['options' => ['class' => 'form-group  col-lg-12'], 'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('В рублях')
    ->label('Цена сотки');
echo $form->field($matrix, 'description', ['options' => ['class' => 'form-group  col-lg-12'], 'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textarea(['autocomplete' => 'off'])
    ->hint('В свободной форме')
    ->label('Назначение платежа');
echo $form->field($matrix, 'payUpLimit', ['options' => ['class' => 'form-group  col-lg-12'], 'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'date'])
    ->label('Срок оплаты счёта');
echo "<div class=' col-lg-12 text-center margened'>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
ActiveForm::end();
echo '</div>';
