<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.10.2018
 * Time: 10:32
 */

use app\models\TimeHandler;
use yii\widgets\ActiveForm;

/* @var $matrix \app\models\ComplexPayment */

$cottageName = '';

$colWidth = 'col-sm-12';

$totalDutySumm = 0;
$form = ActiveForm::begin(['id' => 'complexPayment', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'action' => ['/payment/validate/complex/' . $matrix->cottageNumber]]);

echo $form->field($matrix, 'cottageNumber', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'noLimitPower', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'membershipPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'powerPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'countedSumm', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

// выведу список всех долгов за электроэнергию, если они есть. Если их нет- так и напишу

$powerDuty = $matrix->unpayed['powerDuty'];

echo '<h2 class="text-center">Электроэнергия:</h2>';
echo "<div class='row color-orange' id='powerCollector'>";
echo "<div class='$colWidth text-center'> <h3>$cottageName</h3>";
if ($matrix->cottageInfo->powerDebt > 0) {
    foreach ($powerDuty as $value) {
        $date = TimeHandler::getFullFromShotMonth($value['month']);
        $cost = $value['totalPay'];
        $totalDutySumm += $cost;
        if ($value['difference'] > 0) {
            $description = "Потрачено электроэнергии: <b class='text-info'>{$value['difference']}</b> КВт/ч<br/>Льготный лимит: <b class='text-info'>{$value['powerLimit']}</b> КВт/ч<br/>Льготная цена киловатта- <b class='text-info'>{$value['powerCost']}</b> &#8381;<br/>Цена киловатта- <b class='text-info'>{$value['powerOvercost']}</b> &#8381;<p>Цена в лимите: <b class='text-info'>{$value['inLimitPay']}</b>  &#8381;<br/>Цена вне лимита: <b class='text-info'>{$value['overLimitPay']}</b> &#8381;</p>";
            echo "<div class='col-lg-12 power-container hoverable main' data-summ='{$cost}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td class='text-right'><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"{$description}\">{$cost} &#8381;</b> <button type='button' class='btn btn-info no-limit popovered glyphicon glyphicon-star main' data-toggle='popover' title='Редактирование' data-placement='right' data-content='Без льготного лимита' data-difference='{$value['difference']}' data-overcost='{$value['powerOvercost']}' data-month='{$value['month']}'></button></td></tr></tbody></table></div>";
        }
 /*       else {
            $description = 'Электроэнергия не расходовалась';
            echo "<div class='col-lg-12 power-container hoverable main' data-summ='{$cost}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td class='text-right'><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"{$description}\">{$cost} &#8381;</b></td></tr></tbody></table></div>";
        }*/
    }
    echo "<div class='text-center col-lg-12'><button class='btn btn-success pay-all main' type='button'>Всё</button><button class='btn btn-danger pay-nothing main' disabled>Ничего</button></div>";
}
else {
    echo '<p>Долгов за электроэнергию не найдено</p>';
}
echo '</div>';
echo '</div>';
echo '<div class="clearfix"></div>';
echo '<h2 class="text-center">Членские взносы:</h2>';
echo "<div class='row color-pinky' id='membershipCollector'>";
$membershipDuty = $matrix->unpayed['membershipDuty'];
echo "<div class='$colWidth text-center'> <h3>$cottageName</h3>";
if(!empty($membershipDuty)){
    foreach($membershipDuty as $key=>$value){
        $date = TimeHandler::getFullFromShortQuarter($key);
        $description = "<p>Площадь расчёта- <b class='text-info'>{$matrix->unpayed['square']}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>{$value['fixed']}</b> &#8381;</p><p>Оплата за сотку- <b class='text-info'>{$value['float']}</b> &#8381;</p><p>Начислено за сотки- <b class='text-info'>{$value['float_summ']}</b> &#8381;</p>";
        echo "<div class='col-lg-12 text-center membership-container hoverable main' data-summ='{$value['total_summ']}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"$description\">{$value['total_summ']} &#8381;</b></td></tr></tbody></table></div>";
        $totalDutySumm += $value['total_summ'];
    }
    echo "<div class='col-lg-12'><button class='btn btn-success pay-all main'>Всё</button><button class='btn btn-danger pay-nothing main' disabled>Ничего</button><div class='limited-input'><div class='input-group '><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addFutureQuarters'/></div></div></div>";
    echo "<div class='row' id='forFutureQuarters' data-additional-summ='0'></div>";
}
else{
    echo "<div class='col-lg-12'><p>Долгов за членские взносы не найдено</p><button class='btn btn-danger pay-nothing main' disabled>Ничего</button><div class='limited-input'><div class='input-group '><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addFutureQuarters'/></div></div></div>";

    echo "<div class='row' id='forFutureQuarters' data-additional-summ='0'></div>";
}
echo '</div>';
echo '</div>';
echo '<div class="clearfix"></div>';

echo '<h2 class="text-center">Целевые взносы:</h2>';
echo "<div class='row color-yellow' id='targetCollector'>";
$targetDuty = $matrix->unpayed['targetDuty'];
echo "<div class='$colWidth target-container text-center'>";
if(!empty($targetDuty)){
    echo "<h3>$cottageName</h3>";
    foreach($targetDuty as $key=>$value){
        $totalDutySumm += $value['realSumm'];
        $description = urldecode($value['description']);
        $summ_description = "<p>Площадь расчёта- <b class='text-info'>{$matrix->unpayed['square']}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>{$value['fixed']}</b> &#8381;</p><p>Оплата за сотку- <b class='text-info'>{$value['float']}</b> &#8381;</p><p>Оплачено ранее- <b class='text-info'>{$value['payed']}</b> &#8381;</p><p>Начислено за сотки- <b class='text-info'>{$value['summ']['float']}</b> &#8381;</p>";
        echo "<div class='col-lg-12 text-center target-container main' data-summ='{$value['realSumm']}'><h3><span class='popovered' data-toggle='popover' title='Назначение платежа' data-placement='top' data-content='{$description}'>{$key} год </span>:<b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$summ_description\">{$value['realSumm']} &#8381;</b><div class='right-input input-group'><input type='text' id='target_{$key}' name='ComplexPaymentDouble[target][{$key}]' class='form-control target-pay' data-max-summ='{$value['realSumm']}'/><span class='input-group-btn'><button class='btn btn-info btn-pay-all' type='button' data-for='target_{$key}'>Всё</button></span></div></h3></div>";
    }

    echo "<div class='text-center col-lg-12'><button class='btn btn-success pay-all target'>Всё</button><button class='btn btn-danger pay-nothing target' disabled>Ничего</button></div>";
}
echo '</div>';
echo '</div>';
echo '<div class="clearfix"></div>';
$singleDuty = $matrix->unpayed['singleDuty'];
echo '<h2 class="text-center">Разовые взносы:</h2>';
if(!empty($singleDuty)){
    echo "<div class='row color-salad' id='simpleCollector'>";
    foreach($singleDuty as $key=>$value){
        $date = TimeHandler::getDatetimeFromTimestamp($key);
        $cost = (float) $value['summ'] - (float)$value['payed'];
        $totalDutySumm += $cost;
        $description = urldecode($value['description']);
        $summ_description = "<p>Оплачено ранее- <b class='text-info'>{$value['payed']}</b> &#8381;</p>";
        echo "<div class='col-lg-12 text-center simple-container' data-summ='{$cost}'><h3><span class='popovered' data-toggle='popover' title='Назначение платежа' data-placement='top' data-content='{$description}'>{$date}</span> : <b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$summ_description\">{$cost} &#8381;</b><div class='right-input input-group'><input type='text' id='single_{$key}' name='ComplexPaymentDouble[single][{$key}]' class='form-control single-pay' data-max-summ='{$cost}'/><span class='input-group-btn'><button class='btn btn-info btn-pay-all' type='button' data-for='single_{$key}'>Всё</button></span></div></h3></div>";
    }

    echo "<div class='text-center col-lg-12'><button class='btn btn-success pay-all'>Всё</button><button class='btn btn-danger pay-nothing' disabled>Ничего</button></div>";
    echo '</div>';
}
else {
    echo '<p class="text-center">Долгов за разовые взносы не найдено</p>';
}

echo "<span class='hidden' id='paySumm'>$totalDutySumm</span>";
echo "<div class='margened'></div>";

echo "<h2 class='text-center'>Скидка</h2>";

echo $form->field($matrix, 'discount', ['template' =>
    '<div class="col-lg-4 col-sm-4"><button id="useDiscountBtn" type="button" class="btn btn-success">Использовать скидку</button></div><div class="col-lg-4 col-sm-4"><div class= "input-group">{input}<span class="input-group-addon">&#8381;</span></div>{error}{hint}</div><div class="col-lg-4 col-sm-4"><textarea id="discountReason" class="form-control   " rows="1" placeholder="Причина скидки" disabled name="ComplexPaymentDouble[discountReason]"></textarea></div>'])
    ->textInput(['placeholder' => 'Например, 23', 'disabled' => true, 'type' => 'number'])
    ->label('Применить скидку')
    ->hint("<b class='text-info'>Необязательное поле.</b>");

echo "<h2 class='text-center'>Депозит</h2>";

echo $form->field($matrix, 'fromDeposit', ['template' =>
    '<div class="col-lg-4 col-sm-4"><button id="useDepositBtn" type="button" class="btn btn-success">Использовать средства с депозита</button></div><div class="col-lg-4 col-sm-4"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>{error}{hint}</div><div class="col-lg-4 col-sm-4">На депозите <b class="text-success"> <span id="deposit">' . $matrix->cottageInfo->deposit . '</span> &#8381;</b></div>'])
    ->textInput(['placeholder' => 'Например, 23', 'disabled' => true, 'type' => 'number'])
    ->hint("<b class='text-info'>Списать средства с депозита участка</b>");

ActiveForm::end();

/*echo "<div class='visible-xs visible-sm visible-md'><p>Долг: <b class='text-danger totalDebtSumm'>0</b>&#8381;</p><p>К оплате: <b class='text-danger totalPaymentSumm'>0</b>&#8381;</p><p class='hidden'>Скидка: <b class='text-danger discountSumm'>0</b>&#8381;</p><p class='hidden'>С депозита: <b class='text-danger fromDepositSumm'>0</b>&#8381;</p><p class='hidden'>Итого: <b class='text-danger recalculatedSumm'>0</b>&#8381;</p><p>Останется: <b class='text-danger leftPaymentSumm'>0</b>&#8381;</p><p><button class='btn btn-success sendFormBtn'>Создать</button></p></div>";*/
