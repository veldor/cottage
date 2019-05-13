<?php

use app\models\SingleHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

echo '<div class=row>';
/* @var $matrix SingleHandler */
$form = ActiveForm::begin(['id' => 'singleHandler', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action'=>['/payment/validate/single']]);
echo $form->field($matrix, 'cottageNumber', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'double', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'summ', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number'])
    ->hint('В рублях')
    ->label('Сумма оплаты');
echo $form->field($matrix, 'description', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}{error}{hint}</div>'])
    ->textarea(['autocomplete' => 'off'])
    ->hint('В свободной форме')
    ->label('Назначение платежа');
echo "<div class='text-center margened'>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
ActiveForm::end();
echo '</div>';
