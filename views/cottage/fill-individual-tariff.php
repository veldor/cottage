<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 20.12.2018
 * Time: 0:13
 */

/* @var $this \yii\web\View */

use app\assets\FillAsset;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

FillAsset::register($this);

?>
<h2>Необходимо заполнить тарифы членских платежей за следующие кварталы-</h2>
<div class="row">
	<?php
	/** @var array $info */
	$form = ActiveForm::begin(['id' => 'personalTariff', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,'action'=>['/fill/membership-personal/' . $info['cottageNumber'] . '/' . TimeHandler::getCurrentQuarter()]]);
	if(!empty($info['lastFilled'])){
	echo '<h3>Последняя заполненная ставка</h3>';
	echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' class=' form-control mem-fixed disabled' disabled value='{$info['lastFilled']['fixed']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' class='form-control mem-float disabled' disabled value='{$info['lastFilled']['float']}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";

	}
	if(!empty($info['quarterList'])){
        foreach ($info['quarterList'] as $key => $value) {
            echo '<h3>' . TimeHandler::getFullFromShortQuarter($key) . '</h3>';
            echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='PersonalTariff[membership][$key][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='PersonalTariff[membership][$key][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
        }
    }
	echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
	ActiveForm::end();
	?>
</div>
<div id="alertsContentDiv"></div>