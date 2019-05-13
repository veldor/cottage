<?php

use app\models\small_classes\SerialCottageInfo;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.04.2019
 * Time: 19:21
 */

/* @var $this View */

/** @var array $cottages */

$form = ActiveForm::begin(['id' => 'billsAutofill', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false]);
?>

<table class="table table-hover">
    <thead>
    <tr>
        <th>№ участка</th>
        <th>Задолженность</th>
        <th>Электричество</th>
        <th>Счета</th>
        <th>Сформировать</th>
    </tr>
    </thead>

    <?php
    /** @var SerialCottageInfo $cottage */
    foreach ($cottages as $cottage) {
        ?>
        <tr>
            <td><?=$cottage->isDouble ? $cottage->cottageNumber . '-a' : $cottage->cottageNumber?></td>
            <td><?=$cottage->haveDebt ? '<b class="text-danger">Имеется задолженность</b>' : '<b class="text-success">Задолженность отсутствует</b>'?></td>
            <td><?=$cottage->isUnfilledPower ? '<b class="text-danger">Не заполнено</b>' : '<b class="text-success">Заполнено</b>'?></td>
            <td><?=$cottage->unpayedBill ? '<b class="text-danger">Неоплаченный счёт</b><br/>' . TimeHandler::getDateFromTimestamp($cottage->unpayedBill->creationTime) : '<b class="text-success">Всё оплачено</b>'?></td>
            <td><label class="btn btn-success"><input type="checkbox" name="SerialInvoices[autofill][<?=$cottage->isDouble ? $cottage->cottageNumber . '-a' : $cottage->cottageNumber?>]" class="accept-fill" <?=$cottage->haveDebt ? 'checked': 'disabled'?>>Заполнять</label></td>
        </tr>
        <?php
    }
    ?>
</table>
<?php
echo Html::submitButton('Сформировать счета', ['class' => 'btn btn-success', 'id' => 'autofillSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();
