<?php

use yii\widgets\ActiveForm;

/* @var $matrix \app\models\Payments */
$form = ActiveForm::begin(['id' => 'paySingle', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/pay/single/' . $matrix->cottageNumber]]);
echo $form->field($matrix, 'paymentSumm' ,['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}</div>
									{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('В рублях')
    ->label('Сумма оплаты');
echo $form->field($matrix, 'singlePaymentDescription' ,['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3">{input}</div>
									{error}{hint}</div>'])
    ->textarea(['autocomplete' => 'off'])
    ->hint('В свободной форме')
    ->label('Назначение платежа');
ActiveForm::end();
