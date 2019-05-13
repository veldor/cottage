<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 07.12.2018
 * Time: 23:15
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
/* @var $this \yii\web\View */
/* @var $requirements \app\models\Table_tariffs_target[]|array|\yii\db\ActiveRecord[] */
$form = ActiveForm::begin(['id' => 'PersonalTariff', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false]);
echo "<input type='text' name='PersonalTariff[cottageNumber]' class='hidden' value='{$cottageNumber}'/>";
foreach ($requirements as $key => $value) {
    echo "<div class='form-group'>
<div class='col-lg-4'><label class='control-label'>" . $key . " год: Долг <b class='text-danger summ' data-fixed='{$value['fixed']}' data-float='{$value['float']}'>" . $value['summ'] . "</b>  &#8381;</label></div>
<div class='col-lg-5'>
<div class='btn-group' data-toggle='buttons'>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='PersonalTariff[target][" . $key . "][payed-of]' value='full' data-year='{$key}'> Оплачен
        </label>
  <label class='btn btn-primary'>
          <input type='radio' class='target-radio' name='PersonalTariff[target][" . $key . "][payed-of]' value='no-payed' data-year='{$key}'> Не оплачен
        </label>
  <label class=\"btn btn-primary\">
          <input type='radio' class='target-radio' name='PersonalTariff[target][" . $key . "][payed-of]' value='partial' data-year='{$key}'> Частично
        </label>
</div>
        <div class='help-block'></div>
</div>
<div class='col-lg-3 text-input-parent'><div class='input-group'><input type='text' class='form-control target-input' id='personaltariff-target_{$key}' name='PersonalTariff[target][" . $key . "][payed-summ]' value='0' autocomplete='off' aria-invalid='false' aria-required='false' disabled><span class='input-group-addon'> &#8381;</span></div><div class='help-block'></div></div>
</div>";
}
echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
ActiveForm::end();