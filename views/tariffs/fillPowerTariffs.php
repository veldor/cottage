<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.10.2018
 * Time: 13:26
 */

/* @var $this \yii\web\View */

/* @var $unfilledTariffs array */
/* @var $period string */

use app\assets\FillAsset;
use app\models\CashHandler;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Заполнение тарифов на членские платежи';

/* @var $this \yii\web\View */


FillAsset::register($this);

?>
<h2>Необходимо заполнить тарифы на электроэнергию-</h2>
<div class="row">
    <?php
    $form = ActiveForm::begin(['id' => 'tarrifsSetupForm', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,'action'=>['/fill/power/' . $period]]);
         if(!empty($unfilledTariffs)){
	        echo '<h3>Последняя заполненная ставка</h3>';
	        echo "<div class='form-group power-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Лимит потребления</span>
                        <input type='text' class=' form-control power-limit disabled' disabled value='{$unfilledTariffs->powerLimit}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Льготная цена</span>
                        <input type='text' class=' form-control  power-cost disabled' disabled value='" . CashHandler::toRubles($unfilledTariffs->powerCost) . "'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена</span>
                        <input type='text' class='form-control power-overcost disabled' disabled value='" . CashHandler::toRubles($unfilledTariffs->powerOvercost) . "'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
        }


    echo '<h3>' .  TimeHandler::getFullFromShotMonth($period) . "</h3>";
    echo "<div class='form-group power-group'>
                        <input type='text' name='PowerHandler[month]' value='$period' class='hidden'/>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Лимит потребления</span>
                        <input type='text' name='PowerHandler[powerLimit]' class='required form-control integer power-limit'/>
                        <span class='input-group-addon'>кВт/ч</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Льготная цена</span>
                        <input type='text' name='PowerHandler[powerCost]' class='required form-control float power-cost'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена</span>
                        <input type='text' name='PowerHandler[powerOvercost]' class='required form-control float power-overcost'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                    </div>";

    echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
    ActiveForm::end();
        ?>
<div id="alertsContentDiv"></div>
