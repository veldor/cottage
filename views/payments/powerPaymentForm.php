<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 03.10.2018
 * Time: 23:02
 */

use app\models\TimeHandler;
use yii\widgets\ActiveForm;

/* @var $matrix \app\models\Payments */
$totalSumm = 0;
$form = ActiveForm::begin(['id' => 'payForPower', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/pay/power/' . $matrix->cottageNumber]]);
echo $form->field($matrix, 'cottageNumber',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($matrix, 'powerPaymentPeriods',['template' => "{input}"])->hiddenInput()->label(false);
foreach ($matrix->unpayed as $key => $month) {
        // обработаю каждый месяц
    // Подробно описываю месяцы, за которые будет проводиться оплата
    $consumptipon = $month['newData'] - $month['oldData'];
    $inLimit = 0;
    $overLimit = 0;
    if ($month['powerLimit'] > 0) {
        if ($consumptipon >= $month['powerLimit']) {
            $inLimit = $month['powerLimit'];
            $overLimit = $consumptipon - $inLimit;
        } else {
            $inLimit = $consumptipon;
        }
    } else {
        $overLimit = $consumptipon;
    }
    $inLimitSumm = $inLimit * $month['powerCost'];
    $overLimitSumm = $overLimit * $month['powerOvercost'];
    $totalMonthSumm = $inLimitSumm + $overLimitSumm;
    $totalSumm += $totalMonthSumm;
        echo "<p class='powerMonth' data-summ='{$totalMonthSumm}'><b class='text-success'> " . TimeHandler::getFullFromShotMonth($key) . ":</b> ";
        echo "<span>Всего израсходовано: <b class='text-info'>{$consumptipon}</b> кВт.ч</span><span> Льготно: <b class='text-info'>{$inLimit}</b> кВт.ч * <b class='text-info'>{$month['powerCost']}</b> &#8381; = <b class='text-danger'>{$inLimitSumm}</b> &#8381;</span><span> Сверх лимита: </span><b class='text-info'>{$overLimit}</b> кВт.ч * <b class='text-info'>{$month['powerOvercost']}</b> &#8381; = <b class='text-danger'>{$overLimitSumm}</b> &#8381;</span><span><br/> Сумма за месяц: <span><b class='text-danger'>{$totalMonthSumm} &#8381;</b></span></span>";
        echo "</p>";
    }
if (count($matrix->unpayed) > 1) {
    echo $form->field($matrix, 'partialPayment', ['template' =>
        '<div class="col-xs-5">{label}</div><div class="col-xs-2">{input}
									{error}{hint}</div><div class="col-lg-4"><p>Всего к оплате: <b class="text-success"><span class="filling" id="FullPowerPaymentSumm" data-summ="' . $totalSumm . '">--</span> &#8381;</b></p></div>'
    ])->radioList(['partial' => 'Часть', 'full' => ' Полностью'],
        [
            'item' => function ($index,$label, $name, $checked, $value) {
                $return = '<label class="btn btn-sm btn-success btn-block">';
                $return .= '<input type="radio" name="' . $name . '" value="' . $value . '"';
                if ($checked) $return .= " checked";
                $return .= '><span>' . $label . '</span>';
                $return .= '</label>';

                return $return;
            },

        ])->label('Вид оплаты.');
}
else {
    echo $form->field($matrix, 'partialPayment', ['template' =>
        '<div class="col-xs-5">{label}</div><div class="col-xs-2">{input}
									{error}{hint}</div><div class="col-lg-4"><p>Всего к оплате: <b class="text-success"><span class="filling" id="FullPowerPaymentSumm" data-summ="' . $totalSumm . '">--</span> &#8381;</b></p></div>'
    ])->radioList(['full' => ' Полностью'],
        [
            'item' => function ($index, $label, $name, $checked, $value) {
                $return = '<label class="btn btn-sm btn-success btn-block">';
                $return .= '<input type="radio" name="' . $name . '" value="' . $value . '"';
                if ($checked) $return .= " checked";
                $return .= '><span>' . $label . '</span>';
                $return .= '</label>';

                return $return;
            },

        ])->label('Вид оплаты.');
}

ActiveForm::end();
