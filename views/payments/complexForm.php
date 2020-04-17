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

$cottageName = $matrix->double ? 'Подучасток №2' : ($matrix->cottageInfo->haveAdditional && !$matrix->additionalCottageInfo->hasDifferentOwner ? 'Основной участок' : '');


$totalDutySumm = 0;
$form = ActiveForm::begin(['id' => 'complexPayment', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/payment/validate/complex/' . $matrix->cottageNumber]]);

echo $form->field($matrix, 'cottageNumber', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'countedSumm', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'double', ['options' => ['class' => 'hidden'], 'template' => '{input}'])->hiddenInput()->label(false);
// выведу список всех долгов за электроэнергию, если они есть. Если их нет- так и напишу

$powerDuty = $matrix->unpayed->powerDuty;
echo '<h2 class="text-center ">Электроэнергия:</h2>';
echo "<div class='row color-orange' id='powerCollector'>";
echo "<div class='text-center'> <h3>$cottageName</h3>";
if (!empty($powerDuty)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Месяц</th><th>Сумма</th><th>К оплате</th><th>Без лимита</th></tr>";
    foreach ($powerDuty as $item) {
        $summToPay = $item->powerData->totalPay - $item->partialPayed;
        if ($summToPay > 0) {
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[power][{$item->powerData->month}][value]' name='ComplexPayment[power][{$item->powerData->month}][pay]'/></td><td>{$item->powerData->month}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[power][{$item->powerData->month}][value]' data-value='" . CashHandler::toJsRubles($summToPay) . "' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td><td><input type='checkbox' class='power-no-limit' data-value='" . $item->withoutLimitAmount . "' data-for='ComplexPayment[power][{$item->powerData->month}][value]' name='ComplexPayment[power][{$item->powerData->month}][no_limit]'/></td></tr>";
        }
    }
    echo '</table>';

} else {
    echo '<p class="text-success">Долгов за электроэнергию не найдено</p>';
}
echo '</div>';
echo '</div>';


$membershipDuty = $matrix->unpayed->membershipDuty;
echo '<h2 class="text-center ">Членские возносы:</h2>';
echo "<div class='row color-orange' id='membershipCollector'>";
echo "<div class='text-center'> <h3>$cottageName</h3>";
if (!empty($membershipDuty)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Квартал</th><th>Сумма</th><th>К оплате</th></tr>";
    foreach ($membershipDuty as $item) {
        $summToPay = CashHandler::toRubles(CashHandler::toRubles($item->amount) - CashHandler::toRubles($item->partialPayed));
        if ($summToPay > 0) {
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[membership][{$item->quarter}][value]' name='ComplexPayment[membership][{$item->quarter}][pay]'/></td><td>{$item->quarter}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[membership][{$item->quarter}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
        }
    }
    echo '</table>';

} else {
    echo '<p class="text-success">Долгов за членские взносы не найдено</p>';
}
if($matrix->double){
    echo "<div class='input-group'><span class='input-group-addon'>Предоплата кварталов </span><input class='form-control' id='addAddtionalFutureQuarters'/></div>";
    echo "<div id='additionalFutureDiv'></div>";
}
else{
    echo "<div class='input-group'><span class='input-group-addon'>Предоплата кварталов </span><input class='form-control' id='addFutureQuarters'/></div>";
    echo "<div id='forFutureQuarters'></div>";
}
echo '</div>';
echo '</div>';

$targetDuty = $matrix->unpayed->targetDuty;
echo '<h2 class="text-center ">Целевые возносы:</h2>';
echo "<div class='row color-orange' id='targetCollector'>";
echo "<div class='text-center'> <h3>$cottageName</h3>";
if (!empty($targetDuty)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>год</th><th>Сумма</th><th>К оплате</th></tr>";
    foreach ($targetDuty as $item) {
        $summToPay = $item->amount - $item->partialPayed;
        if ($summToPay > 0) {
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[target][{$item->year}][value]' name='ComplexPayment[target][{$item->year}][pay]'/></td><td>{$item->year}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[target][{$item->year}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
        }
    }
    echo '</table>';

} else {
    echo '<p class="text-success">Долгов за целевые взносы не найдено</p>';
}
echo '</div></div>';

$singleDuty = $matrix->unpayed->singleDuty;
echo '<h2 class="text-center ">Разовые возносы:</h2>';
echo "<div class='row color-orange' id='singleCollector'>";
echo "<div class='text-center'> <h3>$cottageName</h3>";
if (!empty($singleDuty)) {
    echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Цель</th><th>Сумма</th><th>К оплате</th></tr>";
    foreach ($singleDuty as $item) {
        $summToPay = $item->amount - $item->partialPayed;
        if ($summToPay > 0) {
            echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[single][{$item->time}][value]' name='ComplexPayment[single][{$item->time}][pay]'/></td><td>{$item->description}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[single][{$item->time}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
        }
    }
    echo '</table>';

} else {
    echo '<p class="text-success">Долгов за целевые взносы не найдено</p>';
}
echo '</div></div>';


if (!empty($matrix->cottageInfo->haveAdditional)) {
// ================================================= ADDITIONAL

// выведу список всех долгов за электроэнергию, если они есть. Если их нет- так и напишу

    $additionalPowerDuty = $matrix->unpayed->additionalPowerDuty;
    echo '<h2 class="text-center ">Электроэнергия:</h2>';
    echo "<div class='row color-orange' id='powerCollector'>";
    echo "<div class='text-center'> <h3>Доп. участок.</h3>";
    if (!empty($additionalPowerDuty)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Месяц</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($additionalPowerDuty as $item) {
            $summToPay = $item->powerData->totalPay - $item->partialPayed;
            if ($summToPay > 0) {
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[additionalPower][{$item->powerData->month}][value]' name='ComplexPayment[additionalPower][{$item->powerData->month}][pay]'/></td><td>{$item->powerData->month}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[additionalPower][{$item->powerData->month}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
            }
        }
        echo '</table>';

    } else {
        echo '<p class="text-success">Долгов за электроэнергию не найдено</p>';
    }
    echo '</div>';
    echo '</div>';


    $additionalMembershipDuty = $matrix->unpayed->additionalMembershipDuty;
    echo '<h2 class="text-center ">Членские возносы:</h2>';
    echo "<div class='row color-orange' id='membershipCollector'>";
    echo "<div class='text-center'> <h3>Доп. участок</h3>";
    if (!empty($additionalMembershipDuty)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>Квартал</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($additionalMembershipDuty as $item) {
            $summToPay = $item->amount - $item->partialPayed;
            if ($summToPay > 0) {
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[additionalMembership][{$item->quarter}][value]' name='ComplexPayment[additionalMembership][{$item->quarter}][pay]'/></td><td>{$item->quarter}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[additionalMembership][{$item->quarter}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
            }
        }
        echo '</table>';

    } else {
        echo '<p class="text-success">Долгов за членские взносы не найдено</p>';
    }
    echo "<div class='input-group'><span class='input-group-addon'>Предоплата кварталов </span><input class='form-control' id='addAddtionalFutureQuarters'/></div>";
    echo "<div id='additionalFutureDiv'></div>";
    echo '</div>';
    echo '</div>';

    $additionalTargetDuty = $matrix->unpayed->additionalTargetDuty;
    echo '<h2 class="text-center ">Целевые возносы:</h2>';
    echo "<div class='row color-orange' id='targetCollector'>";
    echo "<div class='text-center'> <h3>Доп. участок</h3>";
    if (!empty($additionalTargetDuty)) {
        echo "<table class='table table-condensed table-striped'><tr><th>Платить</th><th>год</th><th>Сумма</th><th>К оплате</th></tr>";
        foreach ($additionalTargetDuty as $item) {
            $summToPay = $item->amount - $item->partialPayed;
            if ($summToPay > 0) {
                echo "<tr><td><input type='checkbox' class='pay-activator' data-for='ComplexPayment[additionalTarget][{$item->year}][value]' name='ComplexPayment[additionalTarget][{$item->year}][pay]'/></td><td>{$item->year}</td><td><b class='text-danger'>" . CashHandler::toSmoothRubles($summToPay) . "</b></td><td><input type='number' class='form-control bill-pay' step='0.01'  name='ComplexPayment[additionalTarget][{$item->year}][value]' value='" . CashHandler::toJsRubles($summToPay) . "' disabled/></td></tr>";
            }
        }
        echo '</table>';

    } else {
        echo '<p class="text-success">Долгов за целевые взносы не найдено</p>';
    }
    echo '</div></div>';


}
if ($matrix->double) {
    $fines = FinesHandler::getFines($matrix->cottageNumber . '-a');
    if ($fines !== null) {
        $hasFine = false;
        $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
        foreach ($fines as $fine) {
            if ($fine->is_enabled && !$fine->is_full_payed) {
                $hasFine = true;
                $dayDifference = TimeHandler::checkDayDifference($fine->payUpLimit);
                $daySumm = $fine->summ / (int)$dayDifference;
                $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                $fineText .= "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . '</td></tr>';
                $totalDutySumm += $summ;
            }
        }
        $fineText .= '</tbody></table></div>';
        if ($hasFine) {
            echo $fineText;
        }
    }
} else {
    $fines = FinesHandler::getFines($matrix->cottageNumber);
    if ($fines !== null) {
        $hasFine = false;
        $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
        foreach ($fines as $fine) {
            if ($fine->is_enabled && !$fine->is_full_payed) {
                $dayDifference = FinesHandler::getFineDaysLeft($fine);
                $hasFine = true;
                if($dayDifference > 0){
                    $daySumm = $fine->summ / $dayDifference;
                }
                $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                $fineText .= "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . '</td></tr>';
                $totalDutySumm += $summ;
            }
        }
        $fineText .= '</tbody></table></div>';
        if ($hasFine) { 
            echo $fineText;
        }
    }
    if (!empty($matrix->additionalCottageInfo) && !$matrix->additionalCottageInfo->hasDifferentOwner) {
        $fines = FinesHandler::getFines($matrix->cottageNumber . '-a');
        if ($fines !== null) {
            $hasFine = false;
            $fineText = "<div class='col-sm-12'><h2 class='text-center'>Пени</h2><table class='table'><thead><tr><th>Оплачивать</th><th>Тип</th><th>Период</th><th>Сумма</th><th>Дней</th><th>В день</th></tr></thead><tbody>";
            foreach ($fines as $fine) {
                if ($fine->is_enabled && !$fine->is_full_payed) {
                    $hasFine = true;
                    $dayDifference = FinesHandler::getFineDaysLeft($fine);
                    $daySumm = $fine->summ / (int)$dayDifference;
                    $summ = CashHandler::rublesMath(CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ));
                    $fineText .= "<tr><td><input type='checkbox' data-summ='$summ' name='ComplexPayment[fines][{$fine->id}]' class='form-control fines-item'/></td><td>" . FinesHandler::$types[$fine->pay_type] . "</td><td>{$fine->period}</td><td>" . CashHandler::toSmoothRubles($summ) . "</td><td>$dayDifference</td><td>" . CashHandler::toSmoothRubles($daySumm) . '</td></tr>';
                    $totalDutySumm += $summ;
                }
            }
            $fineText .= '</tbody></table></div>';
            if ($hasFine) {
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

if ($matrix->cottageInfo->deposit > 0) {
    if ($matrix->cottageInfo->deposit > $totalDutySumm) {
        $usedDeposit = $totalDutySumm;
    } else {
        $usedDeposit = $matrix->cottageInfo->deposit;
    }
} else {
    $usedDeposit = 0;
}

echo $form->field($matrix, 'fromDeposit', ['template' =>
    '<div class="col-lg-4 col-md-4 col-xs-12"><button id="useDepositBtn" type="button" class="btn btn-danger">Не использовать средства с депозита</button></div><div class="col-md-4 col-xs-6"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>{error}{hint}</div><div class="col-md-4 col-xs-6">На депозите <b class="text-success"> <span id="deposit" data-available="' . CashHandler::toRubles($matrix->cottageInfo->deposit) . '">' . CashHandler::toRubles($matrix->cottageInfo->deposit) . '</span> &#8381;</b></div>'])
    ->textInput(['placeholder' => 'Например, 23', 'type' => 'number', 'data-available' => CashHandler::toRubles($matrix->cottageInfo->deposit)])
    ->hint("<b class='text-info'>Списать средства с депозита участка</b>");

ActiveForm::end();

?>

<script>
    function handleForm() {

        let billToolbar = $('<div id="createBillToolbar"><button id="sendBillForm" class="btn btn-success">Создать счёт</button> <span class="text-default">Стоимость: <b id="billCostViewer">0</b></span> <span class="text-default">С депозита: <b id="billUsedDepositViewer">0 руб.</b></span> <span class="text-default">Скидка: <b id="billDiscountViewer">0</b></span> <span class="text-default">Итог: <b id="billFinalCostViewer"></b></span></div>');

        let billModal = $('div.modal');

        let payInputs = billModal.find('input.bill-pay');
        payInputs.on('input.count', function () {
            recalculateBillCost();
        });

        // Отключение лимита оплаты электроэнергии
        let noLimitActivators = $('input.power-no-limit');
        noLimitActivators.on('click.change', function () {
            let target = billModal.find('input[name="' + $(this).attr('data-for') + '"]');
            if($(this).prop('checked')){
                target.val(toRubles($(this).attr('data-value')));
            }
            else{
                target.val(toRubles(target.attr('data-value')));
            }
            recalculateBillCost();
        });

        let useDiscount = false;
        const useDiscountBtn = billModal.find('button#useDiscountBtn');
        const discountInput = billModal.find('input#complexpayment-discount, input#complexpaymentdouble-discount');
        discountInput.on('input.recalculate', function () {
            recalculateBillCost();
        });
        const discountReason = billModal.find('textarea#discountReason');

        useDiscountBtn.on('click.switch', function () {
            if (useDiscount) {
                $(this).text("Использовать скидку.").addClass('btn-success').removeClass('btn-danger');
                discountInput.prop('disabled', true).val('').removeClass('failed');
                discountReason.prop('disabled', true).val('');
                useDiscount = false;
            } else {
                // скидка не используется.
                $(this).text("Не использовать скидку.").removeClass('btn-success').addClass('btn-danger');
                discountInput.prop('disabled', false);
                discountReason.prop('disabled', false);
                discountInput.focus();
                useDiscount = true;
            }
            recalculateBillCost();
        });

        let useDeposit = true;
        const depositInput = billModal.find('input#complexpayment-fromdeposit, input#complexpaymentdouble-fromdeposit');
        depositInput.on('input.count', function () {
            recalculateBillCost();
        });
        const useDepositBtn = billModal.find('button#useDepositBtn');
        useDepositBtn.on('click.switch', function () {
            if (useDeposit) {
                $(this).text("Использовать средства с депозита.").addClass('btn-success').removeClass('btn-danger');
                depositInput.prop('disabled', true).val('').removeClass('failed');
            } else {
                $(this).text("Не использовать средства с депозита.").removeClass('btn-success').addClass('btn-danger');
                depositInput.prop('disabled', false);
            }
            useDeposit = !useDeposit;
            recalculateBillCost();
        });

        // найду активаторы платежей
        let payActivators = billModal.find('input.pay-activator');
        let billCostViewer = billToolbar.find('#billCostViewer');
        let billFinalCostViewer = billToolbar.find('#billFinalCostViewer');
        let discountViewer = billToolbar.find('#billDiscountViewer');
        let depositViewer = billToolbar.find('#billUsedDepositViewer');

        payActivators.on('click.switch', function () {
            switchPay($(this));
        });

        let fineActivators = billModal.find('input.fines-item');
        fineActivators.on('click.switch', function () {
            recalculateBillCost();
        });

        function switchPay(pay) {
            let payInput = billModal.find('input[name="' + pay.attr('data-for') + '"]');
            if (pay.prop('checked')) {
                // активирую платёж
                payInput.prop('disabled', false);
            } else {
                // закрываю платёж
                payInput.prop('disabled', true);
            }
            recalculateBillCost();
        }

        function recalculateBillCost() {
            let summ = 0;
            let total = 0;
            let discountSumm = 0;
            let depositSumm = 0;
            let inputs = billModal.find('input.bill-pay').not(':disabled');
            inputs.each(function () {
                summ += toRubles($(this).val());
            });

            let fineInputs = billModal.find('input.fines-item').filter(':checked');
            fineInputs.each(function () {
                summ += toRubles($(this).attr('data-summ'));
            });

            total += summ;

            // работаю со скидкой
            if (!discountInput.prop('disabled')) {
                let used = toRubles(discountInput.val());
                if (!used) {
                    used = 0;
                }
                if (used > total) {
                    discountInput.val(total);
                    discountSumm = total;
                    makeInformer('warning', 'Сумма скидки скорректирована', 'Сумма скидки уменьшена до стоимости платежа');
                } else {
                    discountSumm = used;
                }
                discountViewer.text(toRubles(discountSumm) + ' руб.');
            }
            total -= discountSumm;

            // работаю с депозитом
            if (depositInput.length === 1 && !depositInput.prop('disabled')) {
                let max = toRubles(depositInput.attr('data-available'));
                let used = toRubles(depositInput.val());
                if(!used){
                    if(total > max){
                        depositInput.val(max);
                        depositSumm = max;
                        depositViewer.text(max + ' руб.');
                    }
                    else{
                        depositInput.val(total);
                        depositSumm = total;
                        depositViewer.text(total + ' руб.');
                    }
                }
                else{
                    if (used > max) {
                        makeInformer('warning', 'Проверьте сумму оплаты с депозита', 'Сумма больше остатка на депозите. Доступно ' + max + ' руб.');
                    } else if (used > total) {
                        depositInput.val(total);
                        depositSumm = total;
                        makeInformer('warning', 'Сумма оплаты с депозита скорректирована', 'Сумма оплаты с депозита уменьшена до стоимости платежа');
                    } else {
                        depositInput.val(used);
                        depositSumm = used;
                        depositViewer.text(used + ' руб.');
                    }
                }
            } else {
                depositViewer.text("0 руб.");
            }
            total -= depositSumm;

            billCostViewer.text(toRubles(summ) + ' руб.');
            billFinalCostViewer.text(toRubles(total) + ' руб.');
        }

        $('body').append(billToolbar);
        billModal.on('hidden.bs.modal.bill', function () {
            billToolbar.remove();
            $('script#billScript').remove();
        });
        let futureDiv = billModal.find('div#forFutureQuarters');
        let addQuartersInput = billModal.find('input#addFutureQuarters');
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
                        futureDiv.html(answer['content']);
                        // найду активаторы платежей
                        let payActivators = futureDiv.find('input.pay-activator');

                        payActivators.on('click.switch', function () {
                            switchPay($(this));
                        });
                    }
                }
            }
        });
        let addQuartersAdditionalInput = billModal.find('input#addAddtionalFutureQuarters');
        let additionalFutureDiv = billModal.find('div#additionalFutureDiv');
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
                            addQuartersAdditionalInput.trigger('input');
                        }
                    } else if (answer.status === 1) {
                        additionalFutureDiv.html(answer['content']);
                        // найду активаторы платежей
                        let payActivators = additionalFutureDiv.find('input.pay-activator');
                        payActivators.on('click.switch', function () {
                            switchPay($(this));
                        });
                    }
                }
            }
        });
        // Отправлю форму при нажатии на кнопку отправки
        billToolbar.find('#sendBillForm').on('click.send', function () {
            sendAjax('post', '/payment/complex/save', handleBillCreate, billModal.find('form').eq(0), true);
        });
        function handleBillCreate(answer) {
                            if (answer.status === 1) {
                                billModal.modal('hide');
                                billModal.on('hidden.bs.modal', function () {
                                    makeInformer('success', 'Счёт создан', 'Теперь нужно выбрать дальнейшее действие');
                                    editBill(answer['billId'], answer['double']);
                                });
                            } else if (answer.status === 2) {
                                makeInformer('danger', "Ошибка во время оплаты", answer['errors']);
                            } else {
                                makeInformer('danger', "Ошибка во время оплаты", 'Произошла неизвестная ошибка, попробуйте ещё раз');
                            }
                        }
    }
    handleForm();
</script>
