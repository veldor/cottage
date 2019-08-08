<?php

use app\models\Table_transactions;
use app\models\Table_transactions_double;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $data Table_transactions|Table_transactions_double */

$form = ActiveForm::begin(['id' => 'changeTransactionDate', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,'validateOnSubmit' => false, 'action'=>['/transaction/change-date/']]);

echo "<div class='row'>";

echo $form->field($data, 'id', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($data, 'double', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($data, 'payDate', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-7">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'date', 'value' => TimeHandler::dateInputDateFromTimestamp($data->payDate)])
    ->label('Дата оплаты');

echo $form->field($data, 'bankDate', ['template' =>
    '<div class="col-lg-5">{label}</div><div class="col-lg-7">{input}{error}{hint}</div>'])
    ->textInput(['type' => 'date', 'value' => TimeHandler::dateInputDateFromTimestamp($data->bankDate)])
    ->label('Дата поступления на счёт');

echo "<div class=' col-sm-12 text-center margened'>";
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';

echo "</div>";

ActiveForm::end();


?>

<script>
    form = $('#changeTransactionDate');
    console.log(form);
    form.on('submit.send', function (e) {
        e.preventDefault();
        sendAjax('post', form.attr('action'), simpleAnswerHandler, form, true);
    });
</script>