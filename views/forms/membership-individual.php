<?php

use app\models\database\Accruals_membership;
use app\models\utils\IndividualMembership;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $data Accruals_membership */
/* @var $model IndividualMembership */

$form = ActiveForm::begin(['id' => 'individualMembershipTariff', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => [Url::toRoute(['forms/membership-individual', 'accrualId' => $data->id])]]);


echo $form->field($model, 'fixed_part', ['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number', 'step' => '0.01']);

echo $form->field($model, 'square_part', ['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'number', 'step' => '0.01']);

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();


