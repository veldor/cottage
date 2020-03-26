<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 19:16
 */

use app\models\CashHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this \yii\web\View */
/* @var $model \app\models\Pay */


$form = ActiveForm::begin(['id' => 'confirmCash', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/pay-double/cash/check/' . $model->billIdentificator]]);
if($model->fromDeposit > 0){
    if($model->fromDeposit > 0 && $model->discount > 0){
        $summ = $model->totalSumm - CashHandler::rublesMath(($model->fromDeposit + $model->discount));
        echo "<h3>К оплате: <span id='paySumm'>{$summ}</span> &#8381; <small>Полная сумма: {$model->totalSumm} &#8381;, минус оплата с депозита: {$model->fromDeposit} &#8381;, минус скидка: {$model->discount} &#8381;</small></h3>";
    }
    elseif($model->fromDeposit > 0){
        $summ = $model->totalSumm - $model->fromDeposit;

        echo "<h3>К оплате: <span id='paySumm'>{$summ}</span> &#8381; <small>Полная сумма: {$model->totalSumm} &#8381;, минус оплата с депозита: {$model->fromDeposit} &#8381;</small></h3>";
    }
}
elseif($model->discount > 0){
    $summ = $model->totalSumm - $model->discount;
    echo "<h3>К оплате: <span id='paySumm'>{$summ}</span> &#8381; <small>Полная сумма: {$model->totalSumm} &#8381;, минус скидка: {$model->discount} &#8381;</small></h3>";
}
else
    echo "<h2>К оплате: <span id='paySumm'>{$model->totalSumm}</span></h2>";
echo $form->field($model, 'billIdentificator',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'totalSumm',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'change',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'rawSumm' ,['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-3"><div class="input-group"><span id="roundSummGet" class="btn btn-success input-group-addon">Ровно</span>{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off'])
    ->hint('В рублях')
    ->label('Получено наличными ');
echo "<h2>Сдача: <span id='change' data-change='0'>0</span> &#8381;</h2>";
echo $form->field($model, 'changeToDeposit' ,['options' => ['class' => 'col-lg-4 form-group'],'template' =>
    '<button type="button" class="btn btn-info">{input}</button>
									{error}{hint}'])
    ->checkbox(['autocomplete' => 'off', 'class' => 'hidden']);
echo $form->field($model, 'toDeposit' ,['options' => ['class' => 'col-lg-8 form-group'],'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-5"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div><div class="col-lg-2"><button id="allChangeToDepositBtn" type="button" class="btn btn-info" disabled>Всё</button></div>'])
    ->textInput(['autocomplete' => 'off', 'disabled' => true])
    ->hint('В рублях')
    ->label('Начислить на депозит');
echo "<div class='clearfix'></div>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
ActiveForm::end();
