<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.10.2018
 * Time: 10:32
 */

use app\models\CashHandler;
use app\models\ComplexPayment;
use app\models\FinesHandler;
use app\models\TimeHandler;
use yii\widgets\ActiveForm;

/* @var $matrix ComplexPayment */

//$cottageName = $matrix->double ? 'Подучасток №2' : ($matrix->cottageInfo->haveAdditional && ! $matrix->additionalCottageInfo->hasDifferentOwner) ? 'Основной участок' : '';

$cottageName = $matrix->double ? 'Подучасток №2' : ($matrix->cottageInfo->haveAdditional && ! $matrix->additionalCottageInfo->hasDifferentOwner ? 'Основной участок' : '');

$colWidth = $matrix->double ? 'col-sm-12' : ($matrix->cottageInfo->haveAdditional && ! $matrix->additionalCottageInfo->hasDifferentOwner ? 'col-sm-6 col-xs-12' : 'col-sm-12');

$totalDutySumm = 0;
$form = ActiveForm::begin(['id' => 'complexPayment', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit'  => false, 'action' => ['/payment/validate/complex/' . $matrix->cottageNumber]]);

echo $form->field($matrix, 'cottageNumber', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'noLimitPower', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'noLimitAdditionalPower', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'membershipPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'additionalMembershipPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'powerPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'additionalPowerPeriods', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'countedSumm', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'double', ['options' => ['class' => 'hidden'],'template' => '{input}'])->hiddenInput()->label(false);

// выведу список всех долгов за электроэнергию, если они есть. Если их нет- так и напишу

$powerDuty = $matrix->unpayed['powerDuty'];

echo '<h2 class="text-center ">Электроэнергия:</h2>';
echo "<div class='row color-orange' id='powerCollector'>";
echo "<div class='$colWidth text-center'> <h3>$cottageName</h3>";
if ($matrix->cottageInfo->powerDebt > 0) {
    foreach ($powerDuty as $value) {
        $date = TimeHandler::getFullFromShotMonth($value['month']);
        $cost = CashHandler::toRubles($value['totalPay']);
        if(!empty($value['prepayed'])){
            $cost -= CashHandler::toRubles($value['prepayed']);
            $prepayedText = "<br>Оплачено ранее: <b class='text-info'>" . CashHandler::toSmoothRubles($value['prepayed']) . '</b>';
        }
        else{
            $prepayedText = '';
        }
        $totalDutySumm += $cost;
        if ($value['difference'] > 0) {
            $description = "Потрачено электроэнергии: <b class='text-info'>{$value['difference']}</b> КВт/ч<br/>Льготный лимит: <b class='text-info'>{$value['powerLimit']}</b> КВт/ч<br/>Льготная цена киловатта- <b class='text-info'>" . CashHandler::toSmoothRubles($value['powerCost']) . "</b> &#8381;<br/>Цена киловатта- <b class='text-info'>" . CashHandler::toSmoothRubles($value['powerOvercost']) . "</b><p>Цена в лимите: <b class='text-info'>" . CashHandler::toSmoothRubles($value['inLimitPay']) . "</b><br/>Цена вне лимита: <b class='text-info'>" . CashHandler::toSmoothRubles($value['overLimitPay']) . "</b>$prepayedText</p>";
            echo "<div class='col-xs-12 power-container hoverable main' data-summ='{$cost}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td class='text-right'><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"{$description}\">" . CashHandler::toSmoothRubles($cost) . "</b> <button type='button' class='btn btn-info no-limit popovered glyphicon glyphicon-star main' data-toggle='popover' title='Редактирование' data-placement='right' data-content='Без льготного лимита' data-difference='{$value['difference']}' data-overcost='{$value['powerOvercost']}' data-month='{$value['month']}'></button></td></tr></tbody></table></div>";
        }
    }
    echo "<div class='text-center col-xs-12 margened'><button class='btn btn-success pay-all main' type='button'>Всё</button><button class='btn btn-danger pay-nothing main' disabled>Ничего</button></div>";
}
else {
    echo '<p class="text-success">Долгов за электроэнергию не найдено</p>';
}
echo '</div>';
if (!empty($matrix->unpayed['additionalPowerDuty'])) {
    echo "<div class='col-sm-6 col-xs-12'> <h3>Дополнительный участок</h3>";

    foreach ($matrix->unpayed['additionalPowerDuty'] as $value) {

        $date = TimeHandler::getFullFromShotMonth($value['month']);
        $cost = CashHandler::toRubles($value['totalPay']);
        if(!empty($value['prepayed'])){
            $cost -= CashHandler::toRubles($value['prepayed']);
            $prepayedText = "<br>Оплачено ранее: <b class='text-info'>" . CashHandler::toSmoothRubles($value['prepayed']) . '</b>';
        }
        else{
            $prepayedText = '';
        }
        $totalDutySumm += $cost;
        if ($value['difference'] > 0) {
            $description = "Потрачено электроэнергии: <b class='text-info'>{$value['difference']}</b> КВт/ч<br/>Льготный лимит: <b class='text-info'>{$value['powerLimit']}</b> КВт/ч<br/>Льготная цена киловатта- <b class='text-info'>" . CashHandler::toSmoothRubles($value['powerCost']) . "</b><br/>Цена киловатта- <b class='text-info'>" . CashHandler::toSmoothRubles($value['powerOvercost']) . "</b><p>Цена в лимите: <b class='text-info'>" . CashHandler::toSmoothRubles($value['inLimitPay']) . "</b><br/>Цена вне лимита: <b class='text-info'>" . CashHandler::toSmoothRubles($value['overLimitPay']) . "</b>$prepayedText</p>";
            echo "<div class='col-xs-12 power-container hoverable additional' data-summ='{$cost}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td class='text-right'><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"{$description}\">" . CashHandler::toSmoothRubles($cost) . "</b> <button type='button' class='btn btn-info no-limit popovered glyphicon glyphicon-star additional' data-toggle='popover' title='Редактирование' data-placement='right' data-content='Без льготного лимита' data-difference='{$value['difference']}' data-overcost='{$value['powerOvercost']}' data-month='{$value['month']}'></button></td></tr></tbody></table></div>";
        } else {
            $description = 'Электроэнергия не расходовалась';
            echo "<div class='col-xs-12 power-container hoverable additional' data-summ='{$cost}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td class='text-right'><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"{$description}\">{$cost} &#8381;</b></td></tr></tbody></table></div>";
        }
    }
    echo "<div class='text-center col-xs-12 margened'><button class='btn btn-success pay-all additional' type='button'>Всё</button><button class='btn btn-danger pay-nothing additional' disabled>Ничего</button></div>";
    echo '</div>';
}
else if(empty($matrix->cottageInfo->hasDifferentOwner)) {
    echo '<h3>Дополнительный участок</h3><p class="text-success">Долгов за электроэнергию не найдено</p>';
}
echo '</div>';
echo '<div class="clearfix"></div>';
echo '<h2 class="text-center">Членские взносы:</h2>';
echo "<div class='row color-pinky' id='membershipCollector'>";
$membershipDuty = $matrix->unpayed['membershipDuty'];
echo "<div class='$colWidth text-center'> <h3>$cottageName</h3>";
if(!empty($membershipDuty)){
    foreach($membershipDuty as $key=>$value){

        $date = TimeHandler::getFullFromShortQuarter($key);

        $description = "<p>Площадь расчёта- <b class='text-info'>{$matrix->unpayed['square']}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>" . CashHandler::toSmoothRubles($value['fixed']) . "</b></p><p>Оплата за сотку- <b class='text-info'>" . CashHandler::toSmoothRubles($value['float']) . "</b></p><p>Начислено за сотки- <b class='text-info'>" . CashHandler::toSmoothRubles($value['float_summ']) . "</b></p><p>Оплачено ранее- <b class='text-info'>" . CashHandler::toSmoothRubles($value['prepayed']) . "</b></p>";

        echo "<div class='col-lg-12 text-center membership-container hoverable main' data-summ='{$value['total_summ']}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"$description\">" . CashHandler::toSmoothRubles($value['total_summ']) . "</b></td></tr></tbody></table></div>";
        $totalDutySumm += $value['total_summ'];
    }
    echo "<div class='col-xs-12 margened'><button class='btn btn-success pay-all main'>Всё</button><button class='btn btn-danger pay-nothing main' disabled>Ничего</button><div class='clearfix'></div> <div class='col-xs-12 col-sm-6 col-sm-offset-3 col-md-6 col-md-offset-3 margened'><div class='input-group'><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addFutureQuarters'/></div></div></div>";
    echo "<div class='row' id='forFutureQuarters' data-additional-summ='0'></div>";
}
else{
    echo "<div class='col-xs-12'><p>Долгов за членские взносы не найдено</p><button class='btn btn-danger pay-nothing main' disabled>Ничего</button><div class='input-group'><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addFutureQuarters'/></div></div><div class='row' id='forFutureQuarters' data-additional-summ='0'></div>";
}
echo '</div>';
if (!empty($matrix->unpayed['additionalMembershipDuty'])){
    echo "<div class='col-sm-6 col-xs-12  text-center'> <h3>Дополнительный участок</h3>";

    foreach($matrix->unpayed['additionalMembershipDuty'] as $key=>$value){
        $date = TimeHandler::getFullFromShortQuarter($key);
        $description = "<p>Площадь расчёта- <b class='text-info'>{$matrix->unpayed['square']}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>" . CashHandler::toSmoothRubles($value['fixed']) . "</b></p><p>Оплата за сотку- <b class='text-info'>" . CashHandler::toSmoothRubles($value['float']) . "</b></p><p>Начислено за сотки- <b class='text-info'>" . CashHandler::toSmoothRubles($value['float_summ']) . "</b></p><p>Оплачено ранее- <b class='text-info'>" . CashHandler::toSmoothRubles($value['prepayed']) . "</b></p>";

        echo "<div class='col-xs-12 text-center membership-container hoverable additional' data-summ='{$value['total_summ']}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"$description\">{$value['total_summ']} &#8381;</b></td></tr></tbody></table></div>";
        $totalDutySumm += $value['total_summ'];
    }
    echo "<div class='col-xs-12 margened'><button class='btn btn-success pay-all additional'>Всё</button><button class='btn btn-danger pay-nothing additional ' disabled>Ничего</button><div class='clearfix'></div> <div class='col-xs-12 col-sm-6 col-sm-offset-3 col-md-6 col-md-offset-3 margened'><div class='input-group'><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addFutureQuartersAdditional'/></div></div></div>";
    echo "<div class='row' id='forAdditionalFutureQuarters' data-additional-summ='0'></div>";
    echo '</div>';
}
elseif(isset($matrix->unpayed['additionalMembershipDuty'])){
    echo "<div class='col-xs-6 text-center'><h3>Дополнительный участок</h3><p>Долгов за членские взносы не найдено</p><button class='btn btn-danger pay-nothing additonal' disabled>Ничего</button><div class='input-group'><span class='input-group-addon'>Дополнительно </span><input class='form-control' id='addAddtionalFutureQuarters'/></div></div><div class='row' id='forAdditionalFutureQuarters' data-additional-summ='0'></div>";
}
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
        echo "<div class='col-lg-12 text-center target-container main' data-summ='{$value['realSumm']}'><h3><span class='popovered' data-toggle='popover' title='Назначение платежа' data-placement='top' data-content='{$description}'>{$key} год </span>:<b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$summ_description\">{$value['realSumm']} &#8381;</b><div class='right-input input-group'><input type='number' id='target_{$key}' name='ComplexPayment[target][{$key}]' class='form-control target-pay' data-max-summ='{$value['realSumm']}'/><span class='input-group-btn'><button class='btn btn-info btn-pay-all' type='button' data-for='target_{$key}'>Всё</button></span></div></h3></div>";
    }

    echo "<div class='text-center col-xs-12 margened'><button class='btn btn-success pay-all target'>Всё</button><button class='btn btn-danger pay-nothing target' disabled>Ничего</button></div>";
}
echo '</div>';
if(!empty($matrix->unpayed['additionalTargetDuty'])){
    echo "<div class='col-sm-6 col-xs-12 target-container'> <h3>Дополнительный участок</h3>";
    foreach($matrix->unpayed['additionalTargetDuty'] as $key=>$value){
        $totalDutySumm += $value['realSumm'];
        $description = urldecode($value['description']);
        $summ_description = "<p>Площадь расчёта- <b class='text-info'>{$matrix->unpayed['square']}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>{$value['fixed']}</b> &#8381;</p><p>Оплата за сотку- <b class='text-info'>{$value['float']}</b> &#8381;</p><p>Оплачено ранее- <b class='text-info'>{$value['payed']}</b> &#8381;</p><p>Начислено за сотки- <b class='text-info'>{$value['summ']['float']}</b> &#8381;</p>";
        echo "<div class='col-lg-12 text-center target-container additional' data-summ='{$value['realSumm']}'><h3><span class='popovered' data-toggle='popover' title='Назначение платежа' data-placement='top' data-content='{$description}'>{$key} год </span>:<b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$summ_description\">{$value['realSumm']} &#8381;</b><div class='right-input input-group'><input type='number' id='additional_target_{$key}' name='ComplexPayment[additionalTarget][{$key}]' class='form-control target-pay' data-max-summ='{$value['realSumm']}'/><span class='input-group-btn'><button class='btn btn-info btn-pay-all' type='button' data-for='additional_target_{$key}'>Всё</button></span></div></h3></div>";
    }

    echo "<div class='text-center col-lg-12 margened'><button class='btn btn-success pay-all'>Всё</button><button class='btn btn-danger pay-nothing' disabled>Ничего</button></div>";
    echo '</div>';
}
if(empty($matrix->unpayed['targetDuty']) && empty($matrix->unpayed['additionalTargetDuty'])){
    echo '<div class="col-xs-12 text-center text-success"><p>Долгов за целевые взносы не найдено</p></div>';
}
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
        echo "<div class='col-lg-12 text-center simple-container' data-summ='{$cost}'><h3><span class='popovered' data-toggle='popover' title='Назначение платежа' data-placement='top' data-content='{$description}'>{$date}</span> : <b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$summ_description\">{$cost} &#8381;</b><div class='right-input input-group'><input type='number' id='single_{$key}' name='ComplexPayment[single][{$key}]' class='form-control single-pay' data-max-summ='{$cost}'/><span class='input-group-btn'><button class='btn btn-info btn-pay-all' type='button' data-for='single_{$key}'>Всё</button></span></div></h3></div>";
    }

    echo "<div class='text-center col-lg-12 margened'><button class='btn btn-success pay-all'>Всё</button><button class='btn btn-danger pay-nothing' disabled>Ничего</button></div>";
    echo '</div>';
}
else {
    echo '<p class="text-center text-success">Долгов за разовые взносы не найдено</p>';
}

// ======================================================   ПЕНИ   ====================================================

if($matrix->double){
    $fines = FinesHandler::getFines($matrix->cottageNumber . '-a');
    if($fines != null){
        $hasFine = false;
        $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
        foreach ($fines as $fine) {
            if($fine->is_enabled && !$fine->is_full_payed){
                $hasFine = true;
                $dayDifference = TimeHandler::checkDayDifference($fine->payUpLimit);
                $daySumm = $fine->summ / (int) $dayDifference;
                $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                $fineText .=  "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . "</td></tr>";
                $totalDutySumm += $summ;
            }
        }
        $fineText .= "</tbody></table></div>";
        if($hasFine){
            echo $fineText;
        }
    }
}
else{

    $fines = FinesHandler::getFines($matrix->cottageNumber);
    if($fines != null){
        $hasFine = false;
        $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
        foreach ($fines as $fine) {
            if($fine->is_enabled && !$fine->is_full_payed){
                $hasFine = true;
                $dayDifference = TimeHandler::checkDayDifference($fine->payUpLimit);
                $daySumm = $fine->summ / (int) $dayDifference;
                $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                $fineText .=  "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . "</td></tr>";
                $totalDutySumm += $summ;
            }
        }
        $fineText .= "</tbody></table></div>";
        if($hasFine){
            echo $fineText;
        }
    }
    if(!empty($matrix->additionalCottageInfo) && !$matrix->additionalCottageInfo->hasDifferentOwner){
        $fines = FinesHandler::getFines($matrix->cottageNumber . '-a');
        if($fines != null){
            $hasFine = false;
            $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
            foreach ($fines as $fine) {
                if($fine->is_enabled && !$fine->is_full_payed){
                    $hasFine = true;
                    $dayDifference = TimeHandler::checkDayDifference($fine->payUpLimit);
                    $daySumm = $fine->summ / (int) $dayDifference;
                    $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                    $fineText .=  "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . "</td></tr>";
                    $totalDutySumm += $summ;
                }
            }
            $fineText .= "</tbody></table></div>";
            if($hasFine){
                echo $fineText;
            }
        }
    }
}

echo "<span class='hidden' id='paySumm'>$totalDutySumm</span>";
echo "<div class='margened'></div>";

echo "<h2 class='text-center'>Скидка</h2>";

echo $form->field($matrix, 'discount', ['template' =>
    '<div class="col-lg-4 col-md-4 col-xs-12"><button id="useDiscountBtn" type="button" class="btn btn-success">Использовать скидку</button></div><div class="col-md-4 col-xs-6"><div class= "input-group">{input}<span class="input-group-addon">&#8381;</span></div>{error}{hint}</div><div class="col-md-4 col-xs-6"><textarea id="discountReason" class="form-control   " rows="1" placeholder="Причина скидки" disabled name="ComplexPayment[discountReason]"></textarea></div>'])
    ->textInput(['placeholder' => 'Например, 23', 'disabled' => true, 'type' => 'number'])
    ->label('Применить скидку')
    ->hint("<b class='text-info'>Необязательное поле.</b>");

echo "<h2 class='text-center'>Депозит</h2>";

if($matrix->cottageInfo->deposit > 0){
 if($matrix->cottageInfo->deposit > $totalDutySumm){
     $usedDeposit = $totalDutySumm;
 }
 else{
     $usedDeposit = $matrix->cottageInfo->deposit;
 }
}
else{
    $usedDeposit = 0;
}

echo $form->field($matrix, 'fromDeposit', ['template' =>
    '<div class="col-lg-4 col-md-4 col-xs-12"><button id="useDepositBtn" type="button" class="btn btn-danger">Не использовать средства с депозита</button></div><div class="col-md-4 col-xs-6"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>{error}{hint}</div><div class="col-md-4 col-xs-6">На депозите <b class="text-success"> <span id="deposit" data-available="' . CashHandler::toRubles($matrix->cottageInfo->deposit) . '">' . CashHandler::toRubles($matrix->cottageInfo->deposit) . '</span> &#8381;</b></div>'])
    ->textInput(['placeholder' => 'Например, 23', 'type' => 'number'])
    ->hint("<b class='text-info'>Списать средства с депозита участка</b>");

ActiveForm::end();

?>

<script>
    function handleForm() {
        let modal = $('.modal');
        let frm = modal.find('form');
        modal.find('.popovered').popover({'trigger': 'hover', 'html': true});
        $('body').append("<div class='flyingSumm'><p>Долг: <b class='text-danger totalDebtSumm'>0</b>&#8381;</p><p>К оплате: <b class='text-danger totalPaymentSumm'>0</b>&#8381;</p><p class='hidden'>Скидка: <b class='text-danger discountSumm'>0</b>&#8381;</p><p class='hidden'>С депозита: <b class='text-danger fromDepositSumm'>0</b>&#8381;</p><p class='hidden'>Итого: <b class='text-danger recalculatedSumm'>0</b>&#8381;</p><p>Останется: <b class='text-danger leftPaymentSumm'>0</b>&#8381;</p><p><button class='btn btn-success sendFormBtn'>Создать</button></p></div>");
        const memberPeriods = $('input#complexpayment-membershipperiods, input#complexpaymentdouble-membershipperiods');
        const memberAdditionalPeriods = $('input#complexpayment-additionalmembershipperiods, input#complexpaymentdouble-additionalmembershipperiods');
        const powerPeriods = $('input#complexpayment-powerperiods, input#complexpaymentdouble-powerperiods');
        const additionalPowerPeriods = $('input#complexpayment-additionalpowerperiods, input#complexpaymentdouble-additionalpowerperiods');
        const countedSumm = $('input#complexpayment-countedsumm, input#complexpaymentdouble-countedsumm');
        modal.on('hidden.bs.modal', function () {
            $('div.flyingSumm').remove();
        });
        let sendBtn = $('button.sendFormBtn');
        let totalDebt = $('b.totalDebtSumm');
        let totalPayment = $('b.totalPaymentSumm');
        let leftSumm = $('b.leftPaymentSumm');
        let globalSumm = toRubles($('span#paySumm').text());
        let discountSummInput = $('input#complexpayment-discount, input#complexpaymentdouble-discount');
        let depositSummInput = $('input#complexpayment-fromdeposit, input#complexpaymentdouble-fromdeposit');
        let discountSumm = $('b.discountSumm');
        let depositSumm = $('b.fromDepositSumm');
        let availableDeposit = $('#deposit').attr('data-available');
        let finesSumm = 0;

        let finesActivators = $('.fines-item');
        finesActivators.on('click.count', function () {
            let summ = toRubles($(this).attr('data-summ'));
            if($(this).prop('checked')){
                finesSumm += summ;
            }
            else{
                finesSumm -= summ;
            }
            recalculateSumm();
        });

        let recalculatedSumm = $('b.recalculatedSumm');
        const useDiscountBtn = modal.find('button#useDiscountBtn');
        const discountInput = modal.find('input#complexpayment-discount, input#complexpaymentdouble-discount');
        const discountReason = modal.find('textarea#discountReason');

        const useDepositBtn = modal.find('button#useDepositBtn');
        const depositInput = modal.find('input#complexpayment-fromdeposit, input#complexpaymentdouble-fromdeposit');
        const depositWholeSumm = toRubles(modal.find('span#deposit').text());
        let useDiscount = false;
        let additionalSumm = 0;
        totalDebt.text(globalSumm + additionalSumm);
        leftSumm.text(globalSumm + additionalSumm);
        let powerSumm = 0;
        let memSumm = 0;
        let targetSumm = 0;
        let simpleSumm = 0;

        let noPowerLimitBtn = modal.find('button.no-limit');
        let noLimitInput = modal.find('input#complexpayment-nolimitpower, input#complexpaymentdouble-nolimitpower');
        let noLimitAdditionalInput = modal.find('input#complexpayment-nolimitadditionalpower, input#complexpaymentdouble-nolimitadditionalpower');
        noPowerLimitBtn.on('click.removeLimit', function (e) {
            let oldSumm = toRubles($(this).parents('div.power-container').eq(0).attr('data-summ'));
            e.stopPropagation();
            if ($(this).hasClass('main')) {
                noLimitInput.val(noLimitInput.val() + $(this).attr('data-month') + ' ');
            } else {

                noLimitAdditionalInput.val(noLimitAdditionalInput.val() + $(this).attr('data-month') + ' ');
            }
            $(this).popover('destroy');
            disableElement($(this));
            // пересчитаю электроэнергию по максимальному тарифу
            let diff = parseInt($(this).attr('data-difference'));
            let overCost = toRubles($(this).attr('data-overcost'));
            let fullCost = toRubles(diff * overCost);
            $(this).parents('div.power-container').eq(0).attr('data-summ', fullCost).find('b').html(fullCost + ' &#8381;').attr('data-content', 'Принудительная оплата без учёта льготного лимита<br>Потрачено электроэнергии- <b class=\'text-info\'> ' + diff + ' </b><br/>Цена киловатта- <b class=\'text-info\'>' + overCost + '</b> &#8381; ');
            globalSumm = toRubles(globalSumm + fullCost - oldSumm);
            totalDebt.text(globalSumm);
            if (powerSumm > 0)
                powerSumm += fullCost - oldSumm;
            recalculateSumm();
        });
        sendBtn.on('click.send', function () {
            // если указана сумма платежа- отправляю форму на сохранение
            if (depositInput.hasClass('failed') || discountInput.hasClass('failed')) {
                makeInformer('danger', 'Ошибка', 'Что-то не так с данными о скидке или депозите! Проверьте правильность ввода');
                return false;
            }
            if (countedSumm.val() > 0) {
                sendAjax('post', '/payment/complex/save', callback, frm[0], true);

                function callback(answer) {
                    if (answer.status === 1) {
                        modal.modal('hide');
                        modal.on('hidden.bs.modal', function () {
                            makeInformer('success', 'Счёт создан', 'Теперь нужно выбрать дальнейшее действие');
                            editBill(answer['billId'], answer['double']);
                        });
                    } else if (answer.status === 2) {
                        makeInformer('danger', "Ошибка во время оплаты", answer['errors']);
                    } else {
                        makeInformer('danger', "Ошибка во время оплаты", 'Произошла неизвестная ошибка, попробуйте ещё раз');
                    }
                }
            } else {
                makeInformer('danger', 'Сохранение', 'Выберите что-то для сохранения платежа');
            }
        });

        let useDeposit = true;

        function recalculateSumm() {
            let summ = powerSumm + memSumm + targetSumm + simpleSumm + finesSumm;
            totalPayment.text(toRubles(summ));
            if(useDeposit){
                if(availableDeposit > 0){
                    if(availableDeposit > summ){
                        depositSummInput.val(toRubles(summ));
                    }
                    else{
                        depositSummInput.val(toRubles(availableDeposit));
                    }
                }
            }
            let discount = isSumm(discountSummInput.val());
            let deposit = isSumm(depositSummInput.val());
            leftSumm.text(toRubles(globalSumm - summ + additionalSumm));
            countedSumm.val(toRubles(summ));
            if (discount && deposit) {
                if (discount + deposit <= summ) {
                    depositSumm.parents('p').eq(0).removeClass('hidden');
                    discountSumm.parents('p').eq(0).removeClass('hidden');
                    recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                    discountSumm.text(discount);
                    depositSumm.text(deposit);
                    recalculatedSumm.text(toRubles((summ - discount) - deposit));
                } else {
                    // сообщение об ошибке и сбрасываю поля ввода
                    makeInformer('danger', 'Ошибка', 'Сумма скидки и депозита не может быть больше суммы платежа! Придётся заполнить их снова!');
                    depositSumm.parents('p').eq(0).addClass('hidden');
                    discountSumm.parents('p').eq(0).addClass('hidden');
                    recalculatedSumm.parents('p').eq(0).addClass('hidden');
                    discountSumm.text(0);
                    depositSumm.text(0);
                    recalculatedSumm.text(0);
                    useDepositBtn.trigger('click');
                    useDiscountBtn.trigger('click');
                }
            } else if (discount) {
                if (discount <= summ) {
                    discountSumm.parents('p').eq(0).removeClass('hidden');
                    recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                    discountSumm.text(discount);
                    recalculatedSumm.text(toRubles(summ - discount));
                } else {
                    discountSumm.parents('p').eq(0).addClass('hidden');
                    recalculatedSumm.parents('p').eq(0).addClass('hidden');
                    discountSumm.text(0);
                    recalculatedSumm.text(0);
                }
            } else if (deposit) {
                if (deposit <= summ) {
                    depositSumm.parents('p').eq(0).removeClass('hidden');
                    recalculatedSumm.parents('p').eq(0).removeClass('hidden');
                    depositSumm.text(deposit);
                    recalculatedSumm.text(toRubles(summ - deposit));
                } else {
                    depositSumm.parents('p').eq(0).addClass('hidden');
                    recalculatedSumm.parents('p').eq(0).addClass('hidden');
                    depositSumm.text(0);
                    recalculatedSumm.text(0);
                }
            } else {
                discountSumm.parents('p').eq(0).addClass('hidden');
                depositSumm.parents('p').eq(0).addClass('hidden');
                discountSumm.text(0);
                depositSumm.text(0);
                recalculatedSumm.parents('p').eq(0).addClass('hidden');
            }
        }
        recalculateSumm();

        // ОБРАБОТКА СКИДКИ ==================================================================================
        handleCashInput(discountInput);
        discountInput.on('input.checkDiscout, blur.checkDiscount', function () {
            let summ = isSumm($(this).val());
            if (summ) {
                if (summ > toRubles(totalPayment.eq(0).text()) || summ + isSumm(depositSumm.eq(0).text()) > isSumm(totalPayment.eq(0).text())) {
                    makeInputWrong($(this));
                    makeInformer('danger', 'Ошибка', 'Сумма скидки не может превышать сумму платежа!');
                } else {
                    recalculateSumm();
                }
            }

        });

        useDiscountBtn.on('click.switch', function () {
            if (totalPayment.text() && toRubles(totalPayment.text()) > 0) {
                if (useDiscount) {
                    $(this).text("Использовать скидку.").addClass('btn-success').removeClass('btn-danger');
                    discountInput.prop('disabled', true).val('').removeClass('failed');
                    discountReason.prop('disabled', true).val('');
                    recalculateSumm();
                    useDiscount = false;
                } else {
                    // скидка не используется.
                    $(this).text("Не использовать скидку.").removeClass('btn-success').addClass('btn-danger');
                    discountInput.prop('disabled', false);
                    discountReason.prop('disabled', false);
                    discountInput.focus();
                    useDiscount = true;
                }
            } else {
                makeInformer('danger', 'Рано!', 'Сначала выберите что-то для оплаты');
            }
        });
        // ОБРАБОТКА ОПЛАТЫ С ДЕПОЗИТА ==================================================================================
        if (depositSumm === 0) {
            disableElement(useDepositBtn, "на депозите нет средств");
        }
        handleCashInput(depositInput);
        depositInput.on('input.checkDeposit, blur.checkDiscount', function () {
            let summ = isSumm($(this).val());
            if (summ) {
                if (summ > toRubles(totalPayment.eq(0).text() - toRubles(discountSumm)) || summ + isSumm(discountSumm.eq(0).text()) > isSumm(totalPayment.eq(0).text())) {
                    makeInputWrong($(this));
                    makeInformer('danger', 'Ошибка', 'Использование депозита не может превышать сумму платежа!');
                } else if (summ > depositWholeSumm) {
                    makeInputWrong($(this));
                    makeInformer('danger', 'Ошибка', 'На депозите нет таких денег!');
                } else {
                    recalculateSumm();
                }
            }

        });

        useDepositBtn.on('click.switch', function () {
            if(useDeposit){
                $(this).text("Использовать средства с депозита.").addClass('btn-success').removeClass('btn-danger');
                depositInput.prop('disabled', true).val('').removeClass('failed');
            }
            else{
                $(this).text("Не использовать средства с депозита.").removeClass('btn-success').addClass('btn-danger');
                depositInput.prop('disabled', false);
            }
            useDeposit = !useDeposit;
            recalculateSumm();
/*            if (totalPayment.text() && toRubles(totalPayment.text()) > 0) {
                if (!useDeposit) {
                    $(this).text("Использовать депозит.").addClass('btn-success').removeClass('btn-danger');
                    depositInput.prop('disabled', true).val(0).removeClass('failed');
                    recalculateSumm();
                    useDeposit = false;
                } else {
                    // скидка не используется.
                    $(this).text("Не использовать депозит.").removeClass('btn-success').addClass('btn-danger');
                    depositInput.prop('disabled', false);
                    depositInput.focus();
                    useDeposit = true;
                    // добавляю в строку поиска максимальное возможное значение
                    let summ = countedSumm.val();
                    if (summ < depositWholeSumm) {
                        depositInput.val(summ);
                        depositInput.trigger('blur');
                    } else {
                        depositInput.val(depositWholeSumm);
                        depositInput.trigger('blur');
                    }
                    recalculateSumm();
                }
            } else {
                makeInformer('danger', 'Рано!', 'Сначала выберите что-то для оплаты');
            }*/
        });
        // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЭЛЕКТРОЭНЕРГИЮ ==================================================================================
        let powerPayAllBtn = modal.find('div#powerCollector button.pay-all');
        let powerPayNothingBtn = modal.find('div#powerCollector button.pay-nothing');
        let powerParts = modal.find('div.power-container.main');
        let additionalPowerParts = modal.find('div.power-container.additional');

        // частичная оплата по клику
        function payPowerToClick(parts, input, additional) {
            parts.hover(function () {
                $(this).prevAll('div').addClass('choosed');
                // рассчитаю сумму оплаты
            }, function () {
                $(this).prevAll('div').removeClass('choosed');
            });
            parts.on('click.summ', function () {
                // помечаю этот элемент и все ранее выбранные, как готовые для оплаты
                $(this).prevAll('div').addClass('selected');
                $(this).addClass('selected');
                parts.removeClass('hoverable choosed');
                let summ = 0;
                let counter = 1;
                $(this).prevAll().each(function () {
                    let s = $(this).attr('data-summ');
                    if (s) {
                        ++counter;
                        summ += toRubles(s);
                    }
                });
                input.val(counter);
                summ += toRubles($(this).attr('data-summ'));
                parts.unbind('mouseenter mouseleave');
                parts.off('click.summ');
                powerSumm += summ;
                recalculateSumm();
                if (additional) {
                    enableElement(powerPayNothingBtn.filter('.additional'));
                    disableElement(powerPayAllBtn.filter('.additional'));
                } else {
                    enableElement(powerPayNothingBtn.filter('.main'));
                    disableElement(powerPayAllBtn.filter('.main'));
                }

            });
        }

        payPowerToClick(powerParts, powerPeriods);
        payPowerToClick(additionalPowerParts, additionalPowerPeriods, true);
        powerPayAllBtn.on('click.all', function () {
            // отмечу все платежи за электроэнергию как оплачиваемые
            disableElement($(this));
            enableElement($(this).parent().find('button.pay-nothing'));
            if ($(this).hasClass('main')) {
                powerParts.addClass('selected').removeClass('hoverable choosed');
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                powerParts.each(function () {
                    powerSumm += toRubles($(this).attr('data-summ'));
                });
                powerParts.unbind('mouseenter mouseleave');
                powerParts.off('click.summ');
                recalculateSumm();
                powerPeriods.val(powerParts.length);
            } else {
                additionalPowerParts.addClass('selected').removeClass('hoverable choosed');
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                additionalPowerParts.each(function () {
                    powerSumm += toRubles($(this).attr('data-summ'));
                });
                additionalPowerParts.unbind('mouseenter mouseleave');
                additionalPowerParts.off('click.summ');
                recalculateSumm();
                additionalPowerPeriods.val(additionalPowerParts.length);
            }
        });
        powerPayNothingBtn.on('click.nothing', function () {
            disableElement($(this));
            enableElement($(this).parent().find('button.pay-all'));
            if ($(this).hasClass('main')) {
                // отмечу все платежи за электроэнергию как оплачиваемые
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                let selected = powerParts.filter('.selected');
                selected.each(function () {
                    powerSumm -= toRubles($(this).attr('data-summ'));
                });
                powerParts.removeClass('choosed selected').addClass('hoverable');
                payPowerToClick(powerParts, powerPeriods);
                recalculateSumm();
                powerPeriods.val(0);
            } else {
                // отмечу все платежи за электроэнергию как оплачиваемые
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                let selected = additionalPowerParts.filter('.selected');
                selected.each(function () {
                    powerSumm -= toRubles($(this).attr('data-summ'));
                });
                additionalPowerParts.removeClass('choosed selected').addClass('hoverable');
                payPowerToClick(additionalPowerParts, additionalPowerPeriods);
                recalculateSumm();
                powerPeriods.val(0);
            }
        });
        // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЧЛЕНСКИЕ ВЗНОСЫ ==================================================================================
        let addQuartersInput = modal.find('input#addFutureQuarters');
        let addQuartersAdditionalInput = modal.find('input#addAddtionalFutureQuarters');
        let memPayAllBtn = modal.find('div#membershipCollector button.pay-all');
        let memPayNothingBtn = modal.find('div#membershipCollector button.pay-nothing');
        let memParts = modal.find('div.membership-container.main');
        let memAdditionalParts = modal.find('div.membership-container.additional');
        let futureDiv = modal.find('div#forFutureQuarters');
        let additionalFutureDiv = modal.find('div#forAdditionalFutureQuarters');

        // частичная оплата по клику
        function payMembershipToClick(parts, input, additional) {
            parts.hover(function () {
                $(this).prevAll('div').addClass('choosed');
                // рассчитаю сумму оплаты
            }, function () {
                $(this).prevAll('div').removeClass('choosed');
            });
            parts.on('click.summ', function () {
                // помечаю этот элемент и все ранее выбранные, как готовые для оплаты
                $(this).prevAll('div').addClass('selected');
                $(this).addClass('selected');
                parts.removeClass('hoverable choosed');
                let summ = 0;
                let counter = 1;
                $(this).prevAll().each(function () {
                    let s = $(this).attr('data-summ');
                    if (s) {
                        ++counter;
                        summ += toRubles(s);
                    }
                });
                input.val(counter);
                summ += toRubles($(this).attr('data-summ'));
                parts.unbind('mouseenter mouseleave');
                parts.off('click.summ');
                memSumm += summ;
                recalculateSumm();
                if (additional) {
                    enableElement(memPayNothingBtn.filter('.additional'));
                    disableElement(memPayAllBtn.filter('.additional'));
                } else {
                    enableElement(memPayNothingBtn.filter('.main'));
                    disableElement(memPayAllBtn.filter('.main'));
                }
            });
        }

        payMembershipToClick(memParts, memberPeriods);
        payMembershipToClick(memAdditionalParts, memberAdditionalPeriods, true);

        memPayAllBtn.on('click.all', function () {
            disableElement($(this));
            enableElement($(this).parent().find('button.pay-nothing'));
            if ($(this).hasClass('main')) {
                // сброшу количество дополнительных кварталов
                addQuartersInput.val('');
                futureDiv.text('');
                additionalSumm -= futureDiv.attr('data-additional-summ');
                futureDiv.attr('data-additional-summ', 0);
                // отмечу все платежи  как оплачиваемые
                memParts.addClass('selected').removeClass('hoverable choosed');
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                memParts.each(function () {
                    memSumm += toRubles($(this).attr('data-summ'));
                });
                memParts.unbind('mouseenter mouseleave');
                memParts.off('click.summ');
                memberPeriods.val(memParts.length);
            } else {
                // сброшу количество дополнительных кварталов
                addQuartersAdditionalInput.val('');
                futureDiv.text('');
                additionalSumm -= additionalFutureDiv.attr('data-additional-summ');
                additionalFutureDiv.attr('data-additional-summ', 0);
                // отмечу все платежи  как оплачиваемые
                memAdditionalParts.addClass('selected').removeClass('hoverable choosed');
                // считаю общую сумму платежей за электричество и выношу её в общее значение
                memAdditionalParts.each(function () {
                    memSumm += toRubles($(this).attr('data-summ'));
                });
                memAdditionalParts.unbind('mouseenter mouseleave');
                memAdditionalParts.off('click.summ');
                memberAdditionalPeriods.val(memAdditionalParts.length);
            }
            recalculateSumm();
        });
        memPayNothingBtn.on('click.nothing', function () {
            disableElement($(this));
            enableElement($(this).parent().find('button.pay-all'));

            if ($(this).hasClass('main')) {
                // сброшу количество дополнительных кварталов
                addQuartersInput.val('');
                futureDiv.text('');
                memSumm -= futureDiv.attr('data-additional-summ');
                additionalSumm -= futureDiv.attr('data-additional-summ');
                futureDiv.attr('data-additional-summ', 0);
                let selected = memParts.filter('.selected');
                selected.each(function () {
                    memSumm -= toRubles($(this).attr('data-summ'));
                });
                memParts.removeClass('choosed selected').addClass('hoverable');
                payMembershipToClick(memParts, memberPeriods);
                recalculateSumm();
                memberPeriods.val(0);
            } else {
                // сброшу количество дополнительных кварталов
                addQuartersAdditionalInput.val('');
                additionalFutureDiv.text('');
                additionalSumm -= additionalFutureDiv.attr('data-additional-summ');
                memSumm -= additionalFutureDiv.attr('data-additional-summ');
                additionalFutureDiv.attr('data-additional-summ', 0);
                let selected = memAdditionalParts.filter('.selected');
                selected.each(function () {
                    memSumm -= toRubles($(this).attr('data-summ'));
                });
                memAdditionalParts.removeClass('choosed selected').addClass('hoverable');
                payMembershipToClick(memAdditionalParts, memberAdditionalPeriods, true);
                recalculateSumm();
                memberAdditionalPeriods.val(0);
            }
        });
        // оплата дополнительных кварталов
        addQuartersInput.on('input.add', function () {
            if ($(this).val() > 0) {
                sendAjax('get', '/get/future-quarters/' + $(this).val() + "/" + cottageNumber, callback);

                function callback(answer) {
                    if (answer.status === 2) {
                        // если не заполнены тарифы- открою окно для заполнения
                        if (tariffsFillWindow)
                            tariffsFillWindow.close();
                        makeNewWindow('/fill/membership/' + answer['lastQuarterForFilling'], tariffsFillWindow, fillCallback);

                        function fillCallback() {
                            addQuartersAdditionalInput.trigger('input');
                        }
                    } else if (answer.status === 3) {
                        if (tariffsFillWindow)
                            tariffsFillWindow.close();
                        // если не заполнены тарифы- открою окно для заполнения
                        makeNewWindow('/fill/membership-personal/' + cottageNumber + '/' + answer['lastQuarterForFilling'], tariffsFillWindow, callback);

                        function callback() {
                            addQuartersInput.trigger('input');
                        }
                    } else if (answer.status === 1) {
                        enableElement(memPayAllBtn.filter('.main'));
                        enableElement(memPayNothingBtn.filter('.main'));
                        futureDiv.html(answer['content']);
                        let selected = memParts.filter('.selected');
                        selected.each(function () {
                            memSumm -= toRubles($(this).attr('data-summ'));
                        });
                        let futureQuarters = futureDiv.find('div.membership-container');
                        futureDiv.find('.popovered').popover({'trigger': 'hover', 'html': true});
                        memParts.addClass('selected').removeClass('hoverable choosed');
                        futureQuarters.addClass('selected').removeClass('hoverable choosed');
                        memParts.each(function () {
                            memSumm += toRubles($(this).attr('data-summ'));
                        });
                        // добавлю к общей сумме платежа то, что прилетело
                        memSumm += answer['totalSumm'];
                        futureDiv.attr('data-additional-summ', answer['totalSumm']);
                        memParts.unbind('mouseenter mouseleave');
                        memParts.off('click.summ');
                        additionalSumm += answer['totalSumm'];
                        memberPeriods.val(memParts.length + futureQuarters.length);
                        recalculateSumm();
                    }
                }
            }
        });
        // оплата дополнительных кварталов
        addQuartersAdditionalInput.on('input.add', function () {
            if ($(this).val() > 0) {
                sendAjax('get', '/get/future-quarters/additional/' + $(this).val() + "/" + cottageNumber, callback);

                function callback(answer) {
                    if (answer.status === 2) {
                        // если не заполнены тарифы- открою окно для заполнения
                        if (tariffsFillWindow)
                            tariffsFillWindow.close();
                        makeNewWindow('/fill/membership/' + answer['lastQuarterForFilling'], tariffsFillWindow, fillCallback);

                        function fillCallback() {
                            console.log('callback');
                            addQuartersAdditionalInput.trigger('input');
                        }
                    } else if (answer.status === 3) {
                        if (tariffsFillWindow)
                            tariffsFillWindow.close();
                        // если не заполнены тарифы- открою окно для заполнения
                        makeNewWindow('/fill/membership-personal-additional/' + cottageNumber + '/' + answer['lastQuarterForFilling'], tariffsFillWindow, callback);

                        function callback() {
                            console.log('callback personal');
                            addQuartersAdditionalInput.trigger('input');
                        }
                    } else if (answer.status === 1) {
                        enableElement(memPayAllBtn.filter('.additional'));
                        enableElement(memPayNothingBtn.filter('.additional'));
                        additionalFutureDiv.html(answer['content']);
                        let selected = memAdditionalParts.filter('.selected');
                        selected.each(function () {
                            memSumm -= toRubles($(this).attr('data-summ'));
                        });
                        let futureQuarters = additionalFutureDiv.find('div.membership-container');
                        additionalFutureDiv.find('.popovered').popover({'trigger': 'hover', 'html': true});
                        memAdditionalParts.addClass('selected').removeClass('hoverable choosed');
                        futureQuarters.addClass('selected').removeClass('hoverable choosed');
                        memAdditionalParts.each(function () {
                            memSumm += toRubles($(this).attr('data-summ'));
                        });
                        // добавлю к общей сумме платежа то, что прилетело
                        memSumm += answer['totalSumm'];
                        additionalFutureDiv.attr('data-additional-summ', answer['totalSumm']);
                        memAdditionalParts.unbind('mouseenter mouseleave');
                        memAdditionalParts.off('click.summ');
                        additionalSumm += answer['totalSumm'];
                        memberAdditionalPeriods.val(memAdditionalParts.length + futureQuarters.length);
                        recalculateSumm();
                    }
                }
            }
        });
        // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА ЦЕЛЕВЫЕ ВЗНОСЫ ==================================================================================
        // при нажатии на кнопку полной оплаты целевого или разового взноса- заполню поле ввода максимальной суммой
        let fullFillBtns = modal.find('button.btn-pay-all');
        fullFillBtns.on('click.fill', function () {
            let input = modal.find('input#' + $(this).attr('data-for'));
            input.val(input.attr('data-max-summ'));
            input.trigger('change');
        });
        // пересчитываю данные при введении суммы оплаты целевого платежа
        let targetInputs = modal.find('input.target-pay');
        targetInputs.on('change.fill', function () {
            if ($(this).val()) {
                // если введено верное значение
                let limit = toRubles($(this).attr('data-max-summ'));
                let val = toRubles($(this).val());
                if (/^\d+[,.]?\d{0,2}$/.test($(this).val()) && val <= limit) {
                    targetSumm = 0;
                    // получаю сумму всех полей ввода целевых платежей
                    targetInputs.each(function () {
                        if ($(this).val()) {
                            targetSumm += toRubles($(this).val());
                        }
                    });
                    recalculateSumm();
                } else {
                    $(this).focus();
                    makeInformer('danger', "Ошибка", "Неверное значение!");
                }
            }
        });
        let targetPayAllBtn = modal.find('div#targetCollector button.pay-all');
        let targetPayNothingBtn = modal.find('div#targetCollector button.pay-nothing');

        targetPayAllBtn.on('click.all', function () {
            enableElement($(this).parent().find('button.pay-nothing'));
            disableElement($(this));
            $(this).parents('div.target-container').eq(0).find('input.target-pay').each(function () {
                let summ = toRubles($(this).attr('data-max-summ'));
                $(this).val(summ);
                targetSumm += summ;
            });
            recalculateSumm();
        });
        targetPayNothingBtn.on('click.nothing', function () {
            enableElement($(this).parent().find('button.pay-all'));
            disableElement($(this));
            $(this).parents('div.target-container').eq(0).find('input.target-pay').each(function () {
                $(this).val(0);
                $(this).trigger('change');
            });
            recalculateSumm();
        });
        // ОБРАБОТКА ПЛАТЕЖЕЙ ЗА РАЗОВЫЕ ВЗНОСЫ ==================================================================================
        // пересчитываю данные при введении суммы оплаты целевого платежа
        let singleInputs = modal.find('input.single-pay');
        singleInputs.on('change.fill', function () {
            if ($(this).val()) {
                // если введено верное значение
                let limit = toRubles($(this).attr('data-max-summ'));
                let val = toRubles($(this).val());
                if (/^\d+[,.]?\d{0,2}$/.test($(this).val()) && val <= limit) {
                    simpleSumm = 0;
                    // получаю сумму всех полей ввода целевых платежей
                    singleInputs.each(function () {
                        if ($(this).val()) {
                            simpleSumm += toRubles($(this).val());
                        }
                    });
                    recalculateSumm();
                } else {
                    $(this).focus();
                }
            }
        });

        let simplePayAllBtn = modal.find('div#simpleCollector button.pay-all');
        let simplePayNothingBtn = modal.find('div#simpleCollector button.pay-nothing');

        simplePayAllBtn.on('click.all', function () {
            enableElement(simplePayNothingBtn);
            disableElement(simplePayAllBtn);
            simpleSumm = 0;
            singleInputs.each(function () {
                let summ = toRubles($(this).attr('data-max-summ'));
                $(this).val(summ);
                simpleSumm += summ;
            });
            recalculateSumm();
        });
        simplePayNothingBtn.on('click.nothing', function () {
            enableElement(simplePayAllBtn);
            disableElement(simplePayNothingBtn);
            simpleSumm = 0;
            singleInputs.each(function () {
                $(this).val('');
            });
            recalculateSumm();
        });
    }
    handleForm();
</script>
