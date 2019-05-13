<?php

/** @var \app\models\Payments $matrix */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$form = ActiveForm::begin(['id' => 'powerPayment', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/payment/get-form/last-membership/' . $matrix->cottageNumber]]);
echo $form->field($matrix, 'cottageNumber',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($matrix, 'lastPayedMembership' ,['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-2">{input}
									{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('Квартал/год(4 цифры).')
    ->label('Последний оплаченный квартал.');
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success disabled', 'id' => 'buttonSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true', 'disabled' => true]);

ActiveForm::end();