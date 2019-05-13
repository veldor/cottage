<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 19:16
 */

use app\models\CashHandler;
use app\models\Pay;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model Pay */

$billInfo = $model->billInfo['billInfo'];

$form = ActiveForm::begin(['id' => 'confirmCash', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'validateOnSubmit' => false, 'action' => ['/pay/confirm/check/' . $model->billIdentificator]]);

// считаю сумму с учётом модификаторов

$fullSumm = CashHandler::toRubles($model->totalSumm);
$fromDeposit = $model->fromDeposit ?? 0;
$discount = $model->discount ?? 0;
$payedBefore = $model->payedBefore ?? 0;

$summToPay = CashHandler::toRubles($fullSumm - $fromDeposit - $discount - $payedBefore);

echo '
    <h2>К оплате: <b id="paySumm" class="text-info" data-full-summ="' . $fullSumm . '" data-summ="' . $summToPay . '" data-deposit="' . $fromDeposit . '" data-discount="' . $discount . '" data-payed-before="' . $payedBefore . '">' . CashHandler::toSmoothRubles($summToPay) . '</b></h2>';

if ($fullSumm != $summToPay) {
    $text = '<p> <b class="text-danger"> ' . CashHandler::toSmoothRubles($fullSumm) . ' (Полная сумма)</b><br/>';
    if($fromDeposit){
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($fromDeposit) . ' (Оплачено с депозита)</b><br/>';
    }
    if($discount){
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($discount) . ' (Скидка)</b><br/>';
    }
    if($payedBefore){
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($payedBefore) . ' (Оплачено ранее)</b><br/>';
    }
    // опишу модификаторы
    echo $text . '</p>';
}

echo $form->field($model, 'billIdentificator', ['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'totalSumm', ['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'change', ['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($model, 'double', ['template' => "{input}"])->hiddenInput()->label(false);

echo $form->field($model, 'payType', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7"><div class="btn-group" data-toggle="buttons">{input}</div>
									{error}{hint}</div>'])
    ->radioList(['cash' => 'Наличные', 'cashless' => 'Безналичный расчёт'], ['item' =>
        function ($index, $label, $name, $checked, $value) {
            $tagName = $index === 0 ? 'Наличные' : 'Безналичный расчёт';
            return "<label class='btn btn-info'><input name='$name' type='radio' value='$value'/>$tagName</label>";
        }]);

echo $form->field($model, 'payWholeness', ['template' =>
    '<div class="hidden"><div class="col-sm-5">{label}</div><div class="col-sm-7"><div class="btn-group" data-toggle="buttons">{input}</div>
									{error}{hint}</div></div> '])
    ->radioList(['full' => 'Полностью', 'partial' => 'Частично'], ['item' =>
        function ($index, $label, $name, $checked, $value) {
            $tagName = $index === 0 ? 'Полностью' : 'Частично';
            return "<label class='btn btn-info'><input name='$name' type='radio' value='$value'/>$tagName</label>";
        }]);

echo $form->field($model, 'rawSumm', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-4"><div class="input-group"><span id="roundSummGet" class="btn btn-success input-group-addon">Ровно</span>{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
    ->hint('В рублях')
    ->label('Получено средств');

// ========================================БЛОК ЧАСТИЧНОЙ ОПЛАТЫ=====================================


echo $form->field($model, 'power', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'additionalPower', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'membership', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'additionalMembership', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'target', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'additionalTarget', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);
echo $form->field($model, 'single', ['template' => "{input}"])->hiddenInput(['class' => 'form-control divided-input'])->label(false);

// подробно распишу все входящие в счёт платежи

if (!empty($model->billInfo['paymentContent']['power'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['power']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['power']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['power']['payed'] . '"><h3 class="text-center">Электроэнергия, всего <b id="fullPowerSumm" class="text-success">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['power']['summ']) . '</b></h3> ' . $payedBeforeBlock . '<ol>';
    foreach ($model->billInfo['paymentContent']['power']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = "<span class='btn btn-info prepayed'> Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = '<span class="btn btn-info prepayed"> Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="power-data" data-month="' . $value['date'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . TimeHandler::getFullFromShotMonth($value['date']) . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
        <div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="power">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="powerDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['additionalPower'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['power']['additionalPower'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['additionalPower']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['additionalPower']['payed'] . '"><h3 class="text-center">Электроэнергия (доп.), всего <b id="fullAdditionalPowerSumm" class="text-success">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['additionalPower']['summ']) . '</b></h3> ' . $payedBeforeBlock . '<ol>';
    foreach ($model->billInfo['paymentContent']['additionalPower']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = "<span class='btn btn-info prepayed'> Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = '<span class="btn btn-info prepayed"> Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="power-data" data-month="' . $value['date'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . TimeHandler::getFullFromShotMonth($value['date']) . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
        <div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="additionalPower">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="additionalPowerDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['membership'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['membership']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['membership']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['membership']['payed'] . '"><h3 class="text-center">Членские, всего <b class="text-success" id="fullMembershipSumm">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['membership']['summ']) . '</b></h3><ol>';

    foreach ($model->billInfo['paymentContent']['membership']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = " <span class='btn btn-info prepayed'>Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = ' <span class="btn btn-info prepayed">Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="membership-data" data-period="' . $value['date'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
<div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="membership">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="membershipDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['additionalMembership'])) {

    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['additionalMembership']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['additionalMembership']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['additionalMembership']['payed'] . '"><h3 class="text-center">Членские (доп.), всего <b class="text-success" id="fullMembershipSumm">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['additionalMembership']['summ']) . '</b></h3><ol>';

    foreach ($model->billInfo['paymentContent']['additionalMembership']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = " <span class='btn btn-info prepayed'>Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = ' <span class="btn btn-info prepayed">Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="additional-membership-data" data-period="' . $value['date'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
<div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="additionalMembership">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="additionalMembershipDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['target'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['target']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['target']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['target']['payed'] . '"><h3 class="text-center">Целевые взносы, всего <b id="fullSingleSumm" class="text-success">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['target']['summ']) . '</b></h3> ' . $payedBeforeBlock . '<ol>';
    foreach ($model->billInfo['paymentContent']['target']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = "<span class='btn btn-info prepayed'> Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = '<span class="btn btn-info prepayed"> Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="target-data" data-time="' . $value['year'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . $value['year'] . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
        <div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="target">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="targetDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['additionalTarget'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['additionalTarget']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['additionalTarget']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['additionalTarget']['payed'] . '"><h3 class="text-center">Целевые взносы(доп.), всего <b id="fullSingleSumm" class="text-success">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['additionalTarget']['summ']) . '</b></h3> ' . $payedBeforeBlock . '<ol>';
    foreach ($model->billInfo['paymentContent']['additionalTarget']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = "<span class='btn btn-info prepayed'> Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = '<span class="btn btn-info prepayed"> Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="target-data" data-time="' . $value['year'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . $value['year'] . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
        <div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="additionalTarget">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="additionalTargetDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}
if (!empty($model->billInfo['paymentContent']['single'])) {
    $payedBefore = CashHandler::toRubles(($model->billInfo['paymentContent']['single']['payed'] ?? 0));
    $payedBeforeBlock = $payedBefore ? '<h4 class="text-center">(Ранее оплачено: <b class="text-info">' . CashHandler::toSmoothRubles($payedBefore) . '</b>)</h4>': '';

    echo '<div class="col-sm-12 payment-details hidden" data-summ="' . $model->billInfo['paymentContent']['single']['summ'] . '" data-payed="' . $model->billInfo['paymentContent']['single']['payed'] . '"><h3 class="text-center">Разовые взносы, всего <b id="fullSingleSumm" class="text-success">' . CashHandler::toSmoothRubles($model->billInfo['paymentContent']['single']['summ']) . '</b></h3> ' . $payedBeforeBlock . '<ol>';
    foreach ($model->billInfo['paymentContent']['single']['values'] as $value) {
        $payedBeforeBlock = '';
        $payedBeforeSumm = 0;
        // проверю, не оплачена ли часть платежа предварительно
        if($payedBefore > 0){
            $summ = CashHandler::toRubles($value['summ']);
            if($payedBefore >= $summ){
                $payedBeforeBlock = "<span class='btn btn-info prepayed'> Полностью оплачено ранее</span>";
                $payedBeforeSumm = $summ;
                $payedBefore -= $summ;
            }
            else{
                $payedBeforeBlock = '<span class="btn btn-info prepayed"> Ранее оплачено ' . CashHandler::toSmoothRubles($payedBefore) . '</span>';
                $payedBeforeSumm = $payedBefore;
                $payedBefore = 0;
            }
        }
        echo '<li class="single-data" data-time="' . $value['timestamp'] . '" data-summ="' . $value['summ'] . '" data-payed-before="' . $payedBeforeSumm . '">' . $value['description'] . ' : <b class="text-info">' . CashHandler::toSmoothRubles($value['summ']) . '</b>' . $payedBeforeBlock . '</li>';
    }
    echo '</ol>
        <div class="input-group col-sm-5"><span class="btn btn-default input-group-addon all-distributed-button" data-category="single">Всё доступное</span><input type="number" step="0.01" class="form-control distributed-summ-input" id="singleDistributed"><span class="input-group-addon">&#8381;</span></div>
</div>';
}

// ===================================== END OF БЛОК ЧАСТИЧНОЙ ОПЛАТЫ================================

echo "<h2>Сдача: <span id='change' data-change='0'>0</span> &#8381;</h2>";
echo $form->field($model, 'changeToDeposit', ['options' => ['class' => 'col-lg-4 form-group'], 'template' =>
    '<button type="button" class="btn btn-info">{input}</button>
									{error}{hint}'])
    ->checkbox(['autocomplete' => 'off', 'class' => 'hidden']);
echo $form->field($model, 'toDeposit', ['options' => ['class' => 'col-lg-8 form-group'], 'template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-5"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div><div class="col-lg-2"><button id="allChangeToDepositBtn" type="button" class="btn btn-info" disabled>Всё</button></div>'])
    ->textInput(['autocomplete' => 'off', 'disabled' => true, 'type' => 'number', 'step' => '0.01'])
    ->hint('В рублях')
    ->label('Начислить на депозит');
echo "<div class='clearfix'></div>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();