<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 03.10.2018
 * Time: 23:02
 */

use app\models\TimeHandler;
use yii\widgets\ActiveForm;
$totalSumm = 0;
/* @var $matrix \app\models\Payments */
$form = ActiveForm::begin(['id' => 'payForTarget', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/pay/target/' . $matrix->cottageNumber]]);
echo $form->field($matrix, 'cottageNumber',['template' => "{input}"])->hiddenInput()->label(false);
echo $form->field($matrix, 'targetPaymentPeriods',['template' => "{input}"])->hiddenInput()->label(false);
foreach ($matrix->unpayed as $key => $item){
    $forMeter = $item['float'] / 100;
    $floatSumm = $matrix->cottageInfo->cottageSquare * $forMeter;
    $yearSumm = $floatSumm + $item['fixed'];
    $totalSumm += $yearSumm;
    echo "<p class='targetYear'  data-summ='{$yearSumm}><b class='text-success'> ";
    echo $key . " год: </b>";
    echo "<span>Фиксированная часть - <b class='text-success'> {$item['fixed']} &#8381;</b></span><span> Переменная часть - <b class='text-info'>{$matrix->cottageInfo->cottageSquare} m<sup>2</sup></b> * <b class='text-info'>{$forMeter} &#8381; за m<sup>2</sup></b> = <b class='text-danger'>{$floatSumm} &#8381;</b><br/> Сумма за год: <b class='text-danger'> {$yearSumm} &#8381;</b></span>";
    echo "</p>";
}
if (count($matrix->unpayed) > 1){
    echo $form->field($matrix, 'partialPayment' ,['template' =>
        '<div class="col-xs-5">{label}</div><div class="col-xs-2">{input}
									{error}{hint}</div><div class="col-lg-4"><p>Всего к оплате: <b class="text-success"><span class="filling" id="FullTargetPaymentSumm" data-summ="' . $totalSumm . '">--</span> &#8381;</b></p></div>'
    ])->radioList(	['partial' => ' Частично', 'full' => ' Полностью'],
        [
            'item' => function($index, $label, $name, $checked, $value) {
                $return = '<label class="btn btn-sm btn-success btn-block">';
                $return .= '<input type="radio" name="' . $name . '" value="' . $value . '"';
                if($checked) $return .= " checked";
                $return .= '><span>' . $label . '</span>';
                $return .= '</label>';

                return $return;
            },

        ])->label('Вид оплаты.');
}
else{
    echo $form->field($matrix, 'partialPayment' ,['template' =>
        '<div class="col-xs-5">{label}</div><div class="col-xs-2">{input}
									{error}{hint}</div><div class="col-lg-4"><p>Всего к оплате: <b class="text-success"><span class="filling" id="FullTargetPaymentSumm" data-summ="' . $totalSumm . '">--</span> &#8381;</b></p></div>'
    ])->radioList(	['full' => ' Полностью'],
        [
            'item' => function($index, $label, $name, $checked, $value) {
                $return = '<label class="btn btn-sm btn-success btn-block">';
                $return .= '<input type="radio" name="' . $name . '" value="' . $value . '"';
                if($checked) $return .= " checked";
                $return .= '><span>' . $label . '</span>';
                $return .= '</label>';

                return $return;
            },

        ])->label('Вид оплаты.');
}
ActiveForm::end();

