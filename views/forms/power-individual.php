<?php

use app\models\CashHandler;
use app\models\Table_power_months;
use app\models\Table_tariffs_power;
use app\models\utils\IndividualPower;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;


/* @var $this View */
/* @var $powerData Table_power_months|null */
/* @var $model IndividualPower */

if ($powerData !== null) {
    $tariff = Table_tariffs_power::findOne(['targetMonth' => $powerData->month]);
    $form = ActiveForm::begin(['id' => 'individualPowerTariff', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => [Url::toRoute(['forms/power-individual', 'monthId' => $powerData->id])]]);
    echo $form->field($model, 'selection', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->radio(['label' => 'Изменить тариф', 'value' => 0, 'class' => 'switcher']);


    echo $form->field($model, 'cost', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->textInput(['type' => 'number', 'step' => '0.01', 'value' => CashHandler::toJsRubles($tariff->powerCost)]);

    echo $form->field($model, 'overcost', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->textInput(['type' => 'number', 'step' => '0.01', 'value' => CashHandler::toJsRubles($tariff->powerOvercost)]);

    echo $form->field($model, 'limit', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->textInput(['type' => 'number', 'step' => '1', 'value' => CashHandler::toJsRubles($tariff->powerLimit)]);

    echo $form->field($model, 'selection', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->radio(['label' => 'Фиксированная стоимость', 'value' => 1, 'class' => 'switcher']);

    echo $form->field($model, 'fixedAmount', ['template' =>
        '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
        ->textInput(['type' => 'number', 'step' => '0.01', 'value' => CashHandler::toJsRubles($powerData->totalPay)]);

    echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
    ActiveForm::end();
}
?>
<div>
    <p>Потрачено электроэнергии: <span class="text-success" id="totalSpendView"><?= $powerData->difference ?></span>
        КВт.</p>
    <p>Общая стоимость: <span class="text-success"
                              id="totalAmountView"><?= CashHandler::toSmoothRubles($powerData->totalPay) ?></span></p>
</div>
<script>
    $(function () {
            let totalSpend = $('span#totalSpendView').text() * 1;
            let totalAmountContainer = $('span#totalAmountView');
            let costInput = $('form#individualPowerTariff input#individualpower-cost').prop('readonly', 'readonly');
            let overCostInput = $('form#individualPowerTariff input#individualpower-overcost').prop('readonly', 'readonly');
            let limitInput = $('form#individualPowerTariff input#individualpower-limit').prop('readonly', 'readonly');
            let totalPayInput = $('form#individualPowerTariff input#individualpower-fixedamount').prop('readonly', 'readonly');

            costInput.off('input.change');
            costInput.on('input.change', function () {
                countAmount();
            });
            overCostInput.off('input.change');
            overCostInput.on('input.change', function () {
                countAmount();
            });
            limitInput.off('input.change');
            limitInput.on('input.change', function () {
                countAmount();
            });

            totalPayInput.off('input.changeTotalAmount');
            totalPayInput.on('input.changeTotalAmount', function () {
                let val = $(this).val();
                let converted = checkRubles(val);
                if (converted) {
                    totalAmountContainer.text(converted);
                }
            });
            let switchers = $('input.switcher');
            switchers.on('click.switch', function () {
                if ($(this).attr('value') === '1') {
                    totalPayInput.prop('readonly', false);
                    costInput.prop('readonly', 'readonly');
                    overCostInput.prop('readonly', 'readonly');
                    limitInput.prop('readonly', 'readonly');
                } else {
                    costInput.prop('readonly', false);
                    overCostInput.prop('readonly', false);
                    limitInput.prop('readonly', false);
                    totalPayInput.prop('readonly', 'readonly');
                }
            });

            function countAmount() {
                let limit = parseInt(limitInput.val());
                if (limit === 0) {
                    let overcost = toRubles(overCostInput.val());
                    let converted = checkRubles(totalSpend * overcost);
                    if (converted) {
                        totalAmountContainer.text(converted);
                    }
                }
                else if(limit > totalSpend){
                    let cost = toRubles(costInput.val());
                    let converted = checkRubles(totalSpend * cost);
                    if (converted) {
                        totalAmountContainer.text(converted);
                    }
                }
                else{
                    let cost = toRubles(costInput.val());
                    let overcost = toRubles(overCostInput.val());
                    let inLimit = limit * cost;
                    let overLimit = (totalSpend - limit) * overcost;
                    let converted = checkRubles(inLimit + overLimit);
                    if (converted) {
                        totalAmountContainer.text(converted);
                    }
                }

            }
        }
    );
</script>
