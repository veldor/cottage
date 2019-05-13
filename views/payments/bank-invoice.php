<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:42
 */

use app\assets\BankInvoiceAsset;
use app\models\CashHandler;
use app\models\TimeHandler;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $info */

/** @var \app\models\BankDetails $bankInfo */

$payInfo = $info['billInfo']['billInfo'];
$paymentContent = $info['billInfo']['paymentContent'];
$bankInfo = $info['bankInfo'];
$smoothSumm = CashHandler::toSmoothRubles($payInfo->totalSumm);

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
            $values .= '<b>' . TimeHandler::getFullFromShotMonth($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']) . ', ';
        }
    }
    if(!empty($paymentContent['additionalPower'])){
        $summ += $paymentContent['additionalPower']['summ'];
        foreach ($paymentContent['additionalPower']['values'] as $value) {
            $values .= '<b>' . TimeHandler::getFullFromShotMonth($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']). ', ';
        }
    }
    $powerText = "Электроэнергия: всего " . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '<br/>';
}
if(!empty($paymentContent['membership']) || !empty($paymentContent['additionalMembership'])){

    $summ = 0;
    $values = '';
    if(!empty($paymentContent['membership'])){
        $summ += $paymentContent['membership']['summ'];
        foreach ($paymentContent['membership']['values'] as $value) {
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']). ', ';
        }
    }
    if(!empty($paymentContent['additionalMembership'])){
        $summ += $paymentContent['additionalMembership']['summ'];
        foreach ($paymentContent['additionalMembership']['values'] as $value) {
            $values .= '<b>' . TimeHandler::getFullFromShortQuarter($value['date']) . ' : </b>' . CashHandler::toSmoothRubles($value['summ']). ', ';
        }
    }
    $memText = 'Членские взносы: всего ' . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '<br/>';
}
if(!empty($paymentContent['target']) || !empty($paymentContent['additionalTarget'])){
    $summ = 0;
    $values = '';
    if(!empty($paymentContent['target'])){
        $summ += $paymentContent['target']['summ'];
        foreach ($paymentContent['target']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : </b>' . CashHandler::toSmoothRubles($value['summ']). ', ';
        }
    }
    if(!empty($paymentContent['additionalTarget'])){
        $summ += $paymentContent['additionalTarget']['summ'];
        foreach ($paymentContent['additionalTarget']['values'] as $value) {
            $values .= '<b>' . $value['year'] . ' год : ' . CashHandler::toSmoothRubles($value['summ']). ', ';
        }
    }
    $tarText = "Целевые взносы: всего " . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '<br/>';
}
if(!empty($paymentContent['single'])){
    $summ = 0;
    $values = '';
    $summ += $paymentContent['single']['summ'];
    foreach ($paymentContent['single']['values'] as $value) {
        $values .= '<b>' . $value['description'] . ' : </b>' . CashHandler::toSmoothRubles($value['summ']). ', ';
    }
    $singleText = "Разовые взносы: всего " . CashHandler::toSmoothRubles($summ) . ' , в том числе ' . substr($values, 0, strlen($values) - 2) . '<br/>';
}

$text = "
<div class='description margened'><span>ПАО СБЕРБАНК</span><span class='pull-right''>Форма №ПД-4</span></div>

<div class='text-center bottom-bordered'><b>{$bankInfo->name}</b></div>
<div class='text-center description margened'><span>(Наименование получателя платежа)</span></div>
<div class='bottom-bordered'><span><b>ИНН</b> {$bankInfo->payerInn} <b>КПП</b> {$bankInfo->kpp}</span><span class='pull-right'>{$bankInfo->personalAcc}</span></div>
<div class='description margened'><span>(инн получателя платежа)</span><span class='pull-right'>(номер счёта получателя платежа)</span></div>
<div class='bottom-bordered text-center'><span><b>БИК</b> {$bankInfo->bik} ({$bankInfo->bankName})</span></div>
<div class='text-center description margened'><span>(Наименование банка получателя платежа)</span></div>
<div class='bottom-bordered text-underline'><b>Участок </b>№{$bankInfo->cottageNumber};<b> ФИО:</b> {$bankInfo->lastName}; <b>Назначение:</b> {$bankInfo->purpose};</b></div>
<div class='description margened text-center'><span>(назначение платежа)</span></div>
<div class='text-center bottom-bordered'><b>Сумма: {$smoothSumm}</b></div>
<div class='description margened text-center'><span>(сумма платежа)</span></div>

<div class='description margened'><span>С условиями приёма указанной в платёжном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен. </span><span class='pull-right'>Подпись плательщика <span class='sign-span bottom-bordered'></span></span></div>
";

BankInvoiceAsset::register($this);

?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div id="invoiceWrapper">

    <img class="logo-img" src="/graphics/logo.png" alt="logo">

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
                <img class="qr-img" src="<?=$qr?>"/>
            </td>
            <td class="rightSide">
                <?=$text?>
            </td>
        </tr>
    </table>

    <div>
        <h4>Детализация платежа по счёту №<?=$payInfo->id . ($info['double'] ? '-a' : '')?></h4>
        <?=$powerText?>
        <?=$memText?>
        <?=$tarText?>
        <?=$singleText?>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

