<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:42
 */

use app\models\BankDetails;
use app\models\CashHandler;
use app\models\TimeHandler;
use yii\web\View;

/* @var $this View */
/* @var $info */

/** @var BankDetails $bankInfo */

$payInfo = $info['billInfo']['billInfo'];
$paymentContent = $info['billInfo']['paymentContent'];
$bankInfo = $info['bankInfo'];
$fromDeposit = CashHandler::toRubles($payInfo->depositUsed);
$discount = CashHandler::toRubles($payInfo->discount);
$realSumm = CashHandler::rublesMath(CashHandler::toRubles($payInfo->totalSumm) - $fromDeposit - $discount);
$smoothSumm = CashHandler::toSmoothRubles($realSumm);
$depositText = '';
if(!empty($fromDeposit)){
    $depositText = '<br/>Оплачено с депозита: ' . CashHandler::toSmoothRubles($fromDeposit);
}
$discountText = '';
if(!empty($discount)){
    $discountText = '<br/>Скидка: ' . CashHandler::toSmoothRubles($discount);
}

$powerText = '';
$memText = '';
$tarText = '';
$singleText = '';

$qr = $bankInfo->drawQR();

if(!empty($paymentContent['power']) || !empty($paymentContent['additionalPower'])){
    $summ = 0;
    $values = '';
    if(!empty($paymentContent['power'])){
        $summ += $paymentContent['power']['summ'];
        foreach ($paymentContent['power']['values'] as $value) {
            $values .= TimeHandler::getFullFromShotMonth($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']) . '<br/>';
        }
    }
    if(!empty($paymentContent['additionalPower'])){
        $summ += $paymentContent['additionalPower']['summ'];
        foreach ($paymentContent['additionalPower']['values'] as $value) {
            $values .= TimeHandler::getFullFromShotMonth($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $powerText = "Электроэнергия:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['membership']) || !empty($paymentContent['additionalMembership'])){

    $summ = 0;
    $values = '';
    if(!empty($paymentContent['membership'])){
        $summ += $paymentContent['membership']['summ'];
        foreach ($paymentContent['membership']['values'] as $value) {
            $values .= TimeHandler::getFullFromShortQuarter($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    if(!empty($paymentContent['additionalMembership'])){
        $summ += $paymentContent['additionalMembership']['summ'];
        foreach ($paymentContent['additionalMembership']['values'] as $value) {
            $values .= TimeHandler::getFullFromShortQuarter($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $memText = "Членские взносы:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['target']) || !empty($paymentContent['additionalTarget'])){
    $summ = 0;
    $values = '';
    if(!empty($paymentContent['target'])){
        $summ += $paymentContent['target']['summ'];
        foreach ($paymentContent['target']['values'] as $value) {
            $values .= $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    if(!empty($paymentContent['additionalTarget'])){
        $summ += $paymentContent['additionalTarget']['summ'];
        foreach ($paymentContent['additionalTarget']['values'] as $value) {
            $values .= $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $tarText = "Целевые взносы:<br/>всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['single'])){
    $summ = 0;
    $values = '';
    $summ += $paymentContent['single']['summ'];
    foreach ($paymentContent['single']['values'] as $value) {
        $values .= $value['description'] . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
    }
    $singleText = "Разовые взносы:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}

if(!empty($paymentContent['power']) || !empty($paymentContent['additionalPower'])){
    $summ = 0;
    $values = '';
    if(!empty($paymentContent['power'])){
        $summ += $paymentContent['power']['summ'];
        foreach ($paymentContent['power']['values'] as $value) {
            $values .= TimeHandler::getFullFromShotMonth($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']) . '<br/>';
        }
    }
    if(!empty($paymentContent['additionalPower'])){
        $summ += $paymentContent['additionalPower']['summ'];
        foreach ($paymentContent['additionalPower']['values'] as $value) {
            $values .= TimeHandler::getFullFromShotMonth($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $powerText = "Электроэнергия:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['membership']) || !empty($paymentContent['additionalMembership'])){

    $summ = 0;
    $values = '';
    if(!empty($paymentContent['membership'])){
        $summ += $paymentContent['membership']['summ'];
        foreach ($paymentContent['membership']['values'] as $value) {
            $values .= TimeHandler::getFullFromShortQuarter($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    if(!empty($paymentContent['additionalMembership'])){
        $summ += $paymentContent['additionalMembership']['summ'];
        foreach ($paymentContent['additionalMembership']['values'] as $value) {
            $values .= TimeHandler::getFullFromShortQuarter($value['date']) . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $memText = "Членские взносы:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['target']) || !empty($paymentContent['additionalTarget'])){
    $summ = 0;
    $values = '';
    if(!empty($paymentContent['target'])){
        $summ += $paymentContent['target']['summ'];
        foreach ($paymentContent['target']['values'] as $value) {
            $values .= $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    if(!empty($paymentContent['additionalTarget'])){
        $summ += $paymentContent['additionalTarget']['summ'];
        foreach ($paymentContent['additionalTarget']['values'] as $value) {
            $values .= $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
        }
    }
    $tarText = "Целевые взносы:<br/>всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}
if(!empty($paymentContent['single'])){
    $summ = 0;
    $values = '';
    $summ += $paymentContent['single']['summ'];
    foreach ($paymentContent['single']['values'] as $value) {
        $values .= $value['description'] . ' : ' . CashHandler::toSmoothRubles($value['summ']). '<br/>';
    }
    $singleText = "Разовые взносы:<br/> всего " . CashHandler::toSmoothRubles($summ) . '<br/>' . $values;
}

$text = "
<div class='description margened'><span>ПАО СБЕРБАНК</span><span class='pull-right''>Форма №ПД-4</span></div>

<div class='text-center bottom-bordered'><b>{$bankInfo->name}</b></div>
<div class='text-center description margened'><span>(Наименование получателя платежа)</span></div>
<div class='bottom-bordered'><span><b>ИНН</b> {$bankInfo->payerInn} <b>КПП</b> {$bankInfo->kpp}</span><span class='pull-right'>{$bankInfo->personalAcc}</span></div>
<div class='description margened'><span>(инн получателя платежа)</span><span class='pull-right'>(номер счёта получателя платежа)</span></div>
<div class='bottom-bordered text-center'><span><b>БИК</b> {$bankInfo->bik} ({$bankInfo->bankName})</span></div>
<div class='text-center description margened'><span>(Наименование банка получателя платежа)</span></div>
<div class='bottom-bordered text-underline'><b>Участок </b>№{$bankInfo->cottageNumber} ;<b> ФИО:</b> {$bankInfo->lastName}; <b>Назначение:</b> {$bankInfo->purpose};</b></div>
<div class='description margened text-center'><span>(назначение платежа)</span></div>
<div class='text-center bottom-bordered'><b>Сумма: {$smoothSumm}</b></div>
<div class='description margened text-center'><span>(сумма платежа)</span></div>

<div class='description margened'><span>С условиями приёма указанной в платёжном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен. </span><br/><br/><span class='pull-right'>Подпись плательщика <span class='sign-span bottom-bordered'></span></span></div>
";
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Квитанции</title>
    <style type="text/css">
        div#invoiceWrapper{
            width: 180mm;
            margin: auto;
            font-size: 10px;
        }
        .margened {
            margin-bottom: 10px;
            margin-top: 5px;
        }

        .col-xs-12 {
            width: 100%;
        }
        td.leftSide{
            text-align: center;
            width: 65mm;
            border-right: 1px solid black;
        }
        img.qr-img{
            width: 80%;
        }
        .bottom-bordered{
            border-bottom: 1px solid black;
        }
        .description{
            font-size: 8px;
        }

        .text-underline{
        }
        .margened{
            margin-bottom: 10px;
        }
        .sign-span{
            width: 20mm;
            display: inline-block;
        }

        table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
            padding: 8px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }

        .pull-right {
            float: right !important;
        }
        .text-center {
            text-align: center;
        }
        img.logo-img{
            width: 50%;
            margin-left: 25%;
        }
    </style>
</head>
<body>
<div id="invoiceWrapper">
    <img class="logo-img" src="<?php echo $_SERVER["DOCUMENT_ROOT"].'/graphics/logo.png';?>" alt="logo">
    <table class="table">
        <tr>
            <td class="leftSide">
                <h3>Извещение</h3>
            </td>
            <td class="rightSide">
                <?=$text?>
            </td>
        </tr>
        <tr>
            <td class="leftSide">
                <h3>Квитанция</h3>
                <img class="qr-img" src="<?=$qr?>" alt=""/>
            </td>
            <td class="rightSide">
                <?=$text?>
            </td>
        </tr>
    </table>
    <div class="row">
        <div class="col-xs-12 text-center">
            <h2>Детализация платежа по счёту №<?=$payInfo->id . ($info['double'] ? '-a' : '')?></h2>
        </div>
        <div class="col-xs-12">
            <?=$powerText?>
        </div>
        <div class="col-xs-12">
            <?=$memText?>
        </div>
        <div class="col-xs-12">
            <?=$tarText?>
        </div>
        <div class="col-xs-12">
            <?=$singleText?>
        </div>
        <?=$depositText?>
        <?=$discountText?>
    </div>
</div>
</body>
</html>

