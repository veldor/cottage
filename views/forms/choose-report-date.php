<?php

use app\models\utils\TotalDutyReport;
use kartik\date\DatePicker;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $matrix TotalDutyReport */

$form = ActiveForm::begin(['id' => 'TotalDutyReport', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'validateOnChange' => false, 'validateOnBlur' => false, 'action' => ['/report/choose-date']]);

try {
    echo $form->field($matrix, 'date',['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])->widget(DatePicker::class, [
        'value' => date('d-m-Y'),
        'pluginOptions' => [
            'format' => 'dd-mm-yyyy',
            'autoclose' => true,
            'todayHighlight' => true,
        ]
    ]);
} catch (Exception $e) {
    die('error create datepicker: ' .  $e->getMessage());
}

/*try {
    echo DatePicker::widget([
        'form' => $form,
        'model' => $matrix,
        'name' => 'TotalDutyReport[date]',
        'attribute' => 'date',
        'value' => date('d-m-Y'),
        'pluginOptions' => [
          'format' => 'dd-mm-yyyy',
          'todayHighlight' => true
        ]
        ]);
} catch (Exception $e) {
}*/

echo "<div class='clearfix'></div>";
echo Html::beginTag('div', ['class' => 'form-group text-center margened']);
echo Html::submitButton('<span class="text-success">Сформировать</span>', ['class' => 'btn btn-default']);
echo Html::endTag('div');
ActiveForm::end();
