<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 19:16
 */

use app\models\CashHandler;
use app\models\Pay;
use app\models\SingleHandler;
use app\models\Table_additional_payed_membership;
use app\models\Table_additional_payed_power;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_payment_bills;
use app\models\Table_payment_bills_double;
use app\models\tables\Table_view_fines_info;
use app\models\TargetHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $model Pay */

/** @var Table_payment_bills_double|Table_payment_bills $billInfo */
$billInfo = $model->billInfo['billInfo'];


$form = ActiveForm::begin(['id' => 'confirmCash', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'validateOnSubmit' => false, 'action' => ['/pay/confirm/check/' . $model->billIdentificator]]);

// считаю сумму с учётом модификаторов

$fullSumm = CashHandler::toRubles($model->totalSumm);
$fromDeposit = $model->fromDeposit ?? 0;
$discount = $model->discount ?? 0;
$payedBefore = $model->payedBefore ?? 0;

// если платёж не оплачивался ранее, добавлю скидку и оплату с депозита как модификатор суммы для частичного платежа
if (!$payedBefore) {
    echo "<span class='hidden' id='partialSummModification' data-summ='" . CashHandler::toRubles($fromDeposit + $discount) . "'></span>";
} else {
    echo "<span class='hidden' id='partialSummModification' data-summ='0'></span>";
}


$summToPay = CashHandler::toRubles($fullSumm - $fromDeposit - $discount - $payedBefore);

echo '
    <h2>К оплате: <b id="paySumm" class="text-info" data-full-summ="' . $fullSumm . '" data-summ="' . $summToPay . '" data-deposit="' . $fromDeposit . '" data-discount="' . $discount . '" data-payed-before="' . $payedBefore . '">' . CashHandler::toSmoothRubles($summToPay) . '</b></h2>';

if ($fullSumm !== $summToPay) {
    $text = '<p> <b class="text-danger"> ' . CashHandler::toSmoothRubles($fullSumm) . ' (Полная сумма)</b><br/>';
    if ($fromDeposit) {
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($fromDeposit) . ' (Оплачено с депозита)</b><br/>';
    }
    if ($discount) {
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($discount) . ' (Скидка)</b><br/>';
    }
    if ($payedBefore) {
        $text .= '<b class="text-success">-' . CashHandler::toSmoothRubles($payedBefore) . ' (Оплачено ранее)</b><br/>';
    }
    // опишу модификаторы
    echo $text . '</p>';
}

echo $form->field($model, 'billIdentificator', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'totalSumm', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'change', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'double', ['template' => '{input}'])->hiddenInput()->label(false);

if(empty($model->bankTransaction)){
    echo $form->field($model, 'rawSumm', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4"><div class="input-group"><span id="roundSummGet" class="btn btn-success input-group-addon">Ровно</span>{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'step' => '0.01'])
        ->hint('В рублях')
        ->label('Получено средств');
}
else{
    echo $form->field($model, 'bankTransactionId', ['template' => '{input}'])->hiddenInput(['value' => $model->bankTransaction->bank_operation_id])->label(false);
    echo $form->field($model, 'rawSumm', ['template' =>
        '<div class="col-sm-5">{label}</div><div class="col-sm-4"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off', 'type' => 'number', 'readonly' => true, 'value' => str_replace(',', '.', CashHandler::toRubles($model->bankTransaction->payment_summ))])
        ->hint('В рублях')
        ->label('Получено средств');
}

// ========================================БЛОК ЧАСТИЧНОЙ ОПЛАТЫ=====================================
// подробно распишу все входящие в счёт платежи

if (!empty($model->billInfo['paymentContent']['power'])) {
    // найду оплаты электроэнергии по данному счёту
    $fullPowerSumm = CashHandler::toRubles($model->billInfo['paymentContent']['power']['summ']);
    $payedBefore = Table_payed_power::find()->where(['billId' => $billInfo->id])->all();
    $previousPayedPower = 0;
    if (!empty($payedBefore)) {
        /** @var Table_payed_power $item */
        foreach ($payedBefore as $item) {
            $previousPayedPower += $item->summ;
        }
        $powerSummToPay = CashHandler::toRubles($fullPowerSumm) - CashHandler::toRubles($previousPayedPower);
        $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($previousPayedPower) . ',осталось оплатить ' . CashHandler::toSmoothRubles($powerSummToPay);
    } else {
        $powerSummToPay = $fullPowerSumm;
        $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($fullPowerSumm);
    }
    if ($powerSummToPay > 0) {
        echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Электроэнергия</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input id='dividedPower' data-max-summ='{$powerSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[power]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
if (!empty($model->billInfo['paymentContent']['additionalPower'])) {
// найду оплаты электроэнергии по данному счёту
    $fullPowerSumm = CashHandler::toRubles($model->billInfo['paymentContent']['additionalPower']['summ']);
    $payedBefore = Table_additional_payed_power::find()->where(['billId' => $billInfo->id])->all();
    $previousPayedPower = 0;
    if (!empty($payedBefore)) {
        foreach ($payedBefore as $item) {
            $previousPayedPower += CashHandler::toRubles($item->summ);
        }
        $powerSummToPay = $fullPowerSumm - $previousPayedPower;
        $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($previousPayedPower) . ',осталось оплатить ' . CashHandler::toSmoothRubles($powerSummToPay);
    } else {
        $powerSummToPay = $fullPowerSumm;
        $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($fullPowerSumm);
    }
    if ($powerSummToPay > 0) {
        echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Электроэнергия(доп.)</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input id='dividedPower' data-max-summ='{$powerSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[additionalPower]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
if (!empty($model->billInfo['paymentContent']['membership'])) {

    $popover = '';

    foreach ($model->billInfo['paymentContent']['membership']['values'] as $item) {
        $popover .= 'Квартал: <b class="text-info">' . $item['date'] . '</b>, стоимость: <b class="text-danger"> ' . $item['summ'] . '</b>, оплачено ранее: <b class="text-success">' . $item['prepayed'] . '</b><br/>';
    }

    // найду оплаты электроэнергии по данному счёту
    $fullMembershipSumm = CashHandler::toRubles($model->billInfo['paymentContent']['membership']['summ']);
    $payedBefore = Table_payed_membership::find()->where(['billId' => $billInfo->id])->all();
    $previousPayedMembership = 0;
    if (!empty($payedBefore)) {
        foreach ($payedBefore as $item) {
            $previousPayedMembership += CashHandler::toRubles($item->summ);
        }
        $previousPayedMembership = CashHandler::toRubles($previousPayedMembership);
        $membershipSummToPay = CashHandler::toRubles($fullMembershipSumm - $previousPayedMembership);
        $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($previousPayedMembership) . ',осталось оплатить ' . CashHandler::toSmoothRubles($membershipSummToPay);
    } else {
        $membershipSummToPay = $fullMembershipSumm;
        $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($fullMembershipSumm);
    }
    if ($membershipSummToPay > 0) {
        echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Членские </label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input id='dividedMembership' data-max-summ='{$membershipSummToPay}' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[membership]'  data-container='body' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
if (!empty($model->billInfo['paymentContent']['additionalMembership'])) {
    $popover = '';
    foreach ($model->billInfo['paymentContent']['additionalMembership']['values'] as $item) {
        $popover .= 'Квартал: <b class="text-info">' . $item['date'] . '</b>, стоимость: <b class="text-danger"> ' . $item['summ'] . '</b>, оплачено ранее: <b class="text-success">' . $item['prepayed'] . '</b><br/>';
    }

    // найду оплаты электроэнергии по данному счёту
    $fullMembershipSumm = CashHandler::toRubles($model->billInfo['paymentContent']['additionalMembership']['summ']);
    $payedBefore = Table_additional_payed_membership::find()->where(['billId' => $billInfo->id])->all();
    $previousPayedMembership = 0;
    if (!empty($payedBefore)) {
        foreach ($payedBefore as $item) {
            $previousPayedMembership += CashHandler::toRubles($item->summ);
        }
        $previousPayedMembership = CashHandler::toRubles($previousPayedMembership);
        $membershipSummToPay = CashHandler::toRubles($fullMembershipSumm - $previousPayedMembership);
        $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($previousPayedMembership) . ',осталось оплатить ' . CashHandler::toSmoothRubles($membershipSummToPay);
    } else {
        $membershipSummToPay = $fullMembershipSumm;
        $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($fullMembershipSumm);
    }
    if ($membershipSummToPay > 0) {
        echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Членские (доп.) </label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input id='dividedMembership' data-max-summ='{$membershipSummToPay}' class='form-control distributed-summ-input popovered' type='number' step='0.01' name='Pay[additionalMembership]' data-container='body' data-toggle='popover' data-placement='auto' data-content='$popover' data-html='true' data-trigger='hover'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
if (!empty($model->billInfo['paymentContent']['target'])) {


    // получу информацию о задолженностях
    $yearInfo = TargetHandler::getDebt($model->cottageInfo);
    $payed = 0;
    // для каждого года оплаты создам отдельное поле ввода
    foreach ($model->billInfo['paymentContent']['target']['values'] as $item) {
        $summToPay = CashHandler::toRubles($item['summ']);
        // получу актуальную информацию о годе
        $payedBefore = Table_payed_target::find()->where(['year' => $item['year'], 'billId' => $billInfo->id])->all();
        if(!empty($payedBefore)){
            /** @var Table_payed_target $payedItem */
            foreach ($payedBefore as $payedItem) {
                $summToPay -= CashHandler::toRubles($payedItem->summ);
                $payed += CashHandler::toRubles($payedItem->summ);
            }
        }
            if (!empty($payed)) {
                $targetSummToPay = $summToPay;
                $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($payed) . ',осталось оплатить ' . CashHandler::toSmoothRubles($summToPay);
            } else {
                $targetSummToPay = $summToPay;
                $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($targetSummToPay);
            }
            echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Целевые {$item['year']}</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='{$targetSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[target][{$item['year']}]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
if (!empty($model->billInfo['paymentContent']['additionalTarget'])) {
    $payed = 0;
    // получу информацию о задолженностях
    if($model->double){
        $yearInfo = TargetHandler::getDebt($model->cottageInfo);
    }
    else{
        $yearInfo = TargetHandler::getDebt($model->additionalCottageInfo);
    }
    // для каждого года оплаты создам отдельное поле ввода
    foreach ($model->billInfo['paymentContent']['additionalTarget']['values'] as $item) {
       $summToPay = CashHandler::toRubles($item['summ']);
        // получу актуальную информацию о годе
        $payedBefore = Table_additional_payed_target::find()->where(['year' => $item['year'], 'billId' => $billInfo->id])->all();
        if(!empty($payedBefore)){
            foreach ($payedBefore as $payedItem) {
                $summToPay -= CashHandler::toRubles($payedItem->summ);
                $payed += CashHandler::toRubles($payedItem->summ);
            }
        }
            if (!empty($payed)) {
                $targetSummToPay = $summToPay;
                $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($payed) . ',осталось оплатить ' . CashHandler::toSmoothRubles($summToPay);
            } else {
                $targetSummToPay = $summToPay;
                $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($targetSummToPay);
            }
            echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Целевые {$item['year']} (доп.)</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='{$targetSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[additionalTarget][{$item['year']}]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";

    }
}
if (!empty($model->billInfo['paymentContent']['single'])) {
    // получу информацию о задолженностях
    $singleInfo = SingleHandler::getDebtReport($model->cottageInfo);
    // для каждого года оплаты создам отдельное поле ввода
    foreach ($model->billInfo['paymentContent']['single']['values'] as $item) {
        // получу актуальную информацию о платеже
        foreach ($singleInfo as $singleDebtItem) {
            if($singleDebtItem->time === $item['timestamp']){
                $targetDebt = $singleDebtItem;
            }
        }
            $payed = CashHandler::toRubles($targetDebt->partialPayed);
            $realSumm = CashHandler::toRubles($targetDebt->amount);
            if (!empty($payed)) {
                $singleSummToPay = $realSumm - $payed;
                $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($payed) . ',осталось оплатить ' . CashHandler::toSmoothRubles($singleSummToPay);
            } else {
                $singleSummToPay = $realSumm;
                $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($singleSummToPay);
            }
            echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Разовый: " . urldecode($item['description']) . "</label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input data-max-summ='{$singleSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[single][{$item['timestamp']}]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
$fines = Table_view_fines_info::find()->where(['bill_id' => $model->billIdentificator])->all();
if (!empty($fines)) {
    $totalSumm = 0;
    $payedSumm = 0;
    // посчитаю общую сумму пени
    /** @var Table_view_fines_info $fine */
    foreach ($fines as $fine) {
        $totalSumm += CashHandler::toRubles($fine->start_summ);
        $payedSumm += CashHandler::toRubles($fine->payed_summ);
    }
    if (!empty($payedSumm)) {
        $finesSummToPay = $totalSumm - $payedSumm;
        $hint = 'Оплачено ранее ' . CashHandler::toShortSmoothRubles($payedSumm) . ',осталось оплатить ' . CashHandler::toSmoothRubles($finesSummToPay);
    } else {
        $finesSummToPay = $totalSumm;
        $hint = 'Осталось оплатить ' . CashHandler::toSmoothRubles($finesSummToPay);
    }
    if ($finesSummToPay > 0) {
        echo "<div class='form-group margened payment-details hidden'><div class='col-sm-5'><label class='control-label'>Пени </label></div><div class='col-sm-4'><div class='input-group'><span class='btn btn-success input-group-addon all-distributed-button'>Максимум</span><input id='dividedMembership' data-max-summ='{$finesSummToPay}' class='form-control distributed-summ-input' type='number' step='0.01' name='Pay[fines]'><span class='input-group-addon'>₽</span></div><div class='hint-block'>$hint</div></div></div>";
    }
}
// ===================================== END OF БЛОК ЧАСТИЧНОЙ ОПЛАТЫ================================

echo $form->field($model, 'toDeposit', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-4">{input}{error}{hint}</div>'])
    ->textInput(['autocomplete' => 'off', 'readonly' => true, 'type' => 'number', 'step' => '0.01', 'value' => 0])
    ->hint('В рублях')
    ->label('Будет зачислено на депозит');
echo "<div class='clearfix'></div>";
if(empty($model->bankTransaction)){
    echo '<div class="form-group margened"><div class="col-sm-5"><label class="control-label" for="payCustomDate">Дата платежа</label></div><div class="col-sm-4"><input type="date" class="form-control " id="payCustomDate" name="Pay[customDate]"></div></div>';
    echo "<div class='margened'></div>";
    echo '<div class="form-group margened"><div class="col-sm-5"><label class="control-label" for="payCustomDate">Дата поступления на счёт</label></div><div class="col-sm-4"><input type="date" class="form-control" id="getCustomDate" name="Pay[getCustomDate]"></div></div>';
}

echo "<div class='margened'></div>";
echo $form->field($model, 'sendConfirmation', ['template' =>
    '<div class="col-sm-5">{label}</div><div class="col-sm-7">{input}{error}{hint}</div>'])
    ->checkbox(['class' => 'form-control'])
    ->label('Отправить подтвержение на e-mail');
echo "<div class='clearfix'></div>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();

?>

<script>
    function handleForm() {
        let newModal = $('.modal');
        let undistributedSumm = 0;
        let frm = newModal.find('form');
        // сумма-модификатор при частичной оплате
        let partialModification = toRubles(newModal.find('span#partialSummModification').attr('data-summ'));
        // поле ввода суммы платежа
        let rawSumm = newModal.find('input#pay-rawsumm');
        // сумма, необходимая для оплаты
        let summToPay = toRubles(newModal.find('#paySumm').attr('data-summ'));
        // кнопка зачисления суммы, необходимой для полного погашения
        let roundSummGetBtn = newModal.find('span#roundSummGet');
        // поле суммы, уходящей на депозит
        let toDepositInput = newModal.find('input#pay-todeposit');
        // элементы частичной оплаты
        let paymentDetailsParts = newModal.find('div.payment-details');

        let undistributedSummContainer;

        // обработаю ввод распределённой суммы в поле ввода
        let distributeInputs = $('.distributed-summ-input');

        // при клике на кнопку- вставляю в поле ввода суммы сумму, равную необходимой для полного погашения платежа
        roundSummGetBtn.on('click.all', function () {
            rawSumm.val(summToPay);
            rawSumm.trigger('change');
        });

        // при изменении суммы общей оплаты- расчитаю результат
        rawSumm.on('change.calculate', function () {
            let value = toRubles($(this).val());
            // если введённая сумма больше необходимого- зачислю сдачу на депозит. Если меньше- посчитаю частичную
            // оплату. Если равна- счёт полностью оплачен
            if (value > summToPay) {
                makeInformer('info', 'информация', "Сумма больше необходимой");
                // перечислю остаток на депозит
                toDepositInput.val(toRubles(value - summToPay));
                paymentDetailsParts.addClass('hidden');
                // помещу в поля ввода детализации максимальные значения
                distributeInputs.each(function () {
                    $(this).val(toRubles($(this).attr('data-max-summ')));
                });
                $('.field-pay-todeposit').removeClass('hidden');
            } else if (value === summToPay) {
                makeInformer('info', 'информация', "Сумма равна необходимой");
                toDepositInput.val(0);
                paymentDetailsParts.addClass('hidden');
                $('.field-pay-todeposit').addClass('hidden');
                // помещу в поля ввода детализации максимальные значения
                distributeInputs.each(function () {
                    $(this).val(toRubles($(this).attr('data-max-summ')));
                });
            } else {
                makeInformer('info', 'информация', "Сумма меньше необходимой");
                toDepositInput.val(0);
                // запишу значение в переменную суммы
                undistributedSumm = toRubles(value + partialModification);
                // покажу параметры частичной оплаты
                distributeInputs.val(0).attr('data-previous-value', 0);
                enablePartial();
            }
        });
        if(rawSumm.val()){
            rawSumm.trigger('change');
        }

        function enablePartial() {
            // скрою ячейку зачисления на депозит, тут она не понадобится
            $('.field-pay-todeposit').addClass('hidden');
            paymentDetailsParts.removeClass('hidden');
            let undistributedWindow = $('<div class="left-float-window">Не распределено<br/><b id="undistributedSumm"></b>&#8381;<br/><div class="btn-group-vertical"><button type="button" class="btn btn-warning" id="resetDistributeActivator">Сбросить</button><button type="button" class="btn btn-success" id="savePayActivator">Сохранить</button></div></div>');
            $('body').append(undistributedWindow);
            newModal.on('hidden.bs.modal', function () {
                undistributedWindow.remove();
            });
            undistributedSummContainer = undistributedWindow.find('#undistributedSumm');
            undistributedSummContainer.html(undistributedSumm);
        }

        // обработаю кнопки раскидывания денежных средств
        let distributeActivators = $('.all-distributed-button');
        distributeActivators.on('click.fill', function () {
            // найду поле ввода, в которое будет распределяться сумма
            let targetInput = $(this).parent().find('input.distributed-summ-input');
            // если в поле уже есть значение- перед распределением средств плюсую его к нераспределённой части
            let previousVal = targetInput.val();
            if (previousVal) {
                undistributedSumm += toRubles(previousVal);
            }
            let summ = toRubles(targetInput.attr('data-max-summ'));
            // если значение суммы больше оставшихся нераспределённых средств- она проставляется как значение поля
            // и вычитается из общей суммы. Если меньше- общая сумма приравнивается к нулю и выставляется в значение поля
            if (summ >= undistributedSumm) {
                targetInput.val(toRubles(undistributedSumm));
                targetInput.attr('data-previous-value', undistributedSumm);
                undistributedSumm = 0;
            } else {
                targetInput.val(toRubles(summ));
                undistributedSumm -= summ;
                targetInput.attr('data-previous-value', summ);
            }
            undistributedSummContainer.html(toRubles(undistributedSumm));
        });
        distributeInputs.on('change.pay', function () {
            // если есть предыдущее значение- выведу его в консоль
            let previousValue = $(this).attr('data-previous-value');
            if (previousValue) {
                // Добавлю предыдущее значение к нераспределённой сумме
                undistributedSumm += toRubles(previousValue);
            }
            let currentValue = toRubles($(this).val());
            if (!currentValue) {
                currentValue = 0;
                $(this).val(0);
            }
            let maxSumm = toRubles($(this).attr('data-max-summ'));
            // если введённое значение больше максимально доступного- сброшу значение поля в ноль и выведу предупреждение
            if (currentValue > maxSumm) {
                $(this).val(0);
                makeInformer('danger', 'Ошибка распределения', 'Введенное значение больше чем сумма оплаты');
            }
            if (currentValue > undistributedSumm) {
                $(this).val(0);
                makeInformer('danger', 'Ошибка распределения', 'Введенное значение больше нераспределённого остатка');
            }
            $(this).attr('data-previous-value', toRubles($(this).val()));
            undistributedSumm -= toRubles($(this).val());
            undistributedSummContainer.html(toRubles(undistributedSumm));

        });

        // отправлю форму
        frm.on('submit', function (e) {
            e.preventDefault();
            sendAjax('post', "/pay/confirm", simpleAnswerHandler, frm, true);
        });
    }

    handleForm();
</script>
