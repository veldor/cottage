<?php

use app\models\CashHandler;
use app\models\tables\Table_penalties;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $matrix Table_penalties */

$form = ActiveForm::begin(['id' => 'lockFine', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => [Url::toRoute(['fines/lock/', 'id' => $matrix->id])]]);


echo $form->field($matrix, 'summ', ['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number', 'step' => '0.01', 'value' => CashHandler::toJsRubles($matrix->summ)])
    ->label("Стоимость");


echo Html::submitButton('Заблокировать', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();


