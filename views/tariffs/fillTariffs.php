<?php

/* @var $this yii\web\View */
use app\assets\FillAsset;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
FillAsset::register($this);


$this->title = 'Необходимо заполнить тарифы';

?>
<div class="row">
    <div class="col-lg-12">
        <h1>Не заполнены необходимые данные о тарифах</h1>
    </div>
    <?php
    $form = ActiveForm::begin(['id' => 'tariffsSetupForm', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => false,'action'=>['/tariffs/index']]);
    /** @var \app\models\TariffsKeeper $lastTariffs */
    if(!empty($lastTariffs->powerMonthsForFilling)){
        echo '<h2>Тарифы на электроэнергию</h2>';
        if(!empty($lastTariffs->lastPowerData)){
	        echo '<h3>Последняя заполненная ставка</h3>';
	        echo "<div class='form-group power-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Лимит потребления</span>
                        <input type='text' class=' form-control power-limit disabled' disabled value='{$lastTariffs->lastPowerData->powerLimit}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Льготная цена</span>
                        <input type='text' class=' form-control  power-cost disabled' disabled value='{$lastTariffs->lastPowerData->powerCost}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена</span>
                        <input type='text' class='form-control power-overcost disabled' disabled value='{$lastTariffs->lastPowerData->powerOvercost}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
        }
        foreach ($lastTariffs->powerMonthsForFilling as $key=>$value){
            echo '<h3>' .  TimeHandler::getFullFromShotMonth($key) . "</h3>";
            echo "<div class='form-group power-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Лимит потребления</span>
                        <input type='text' name='TariffsKeeper[power][$key][limit]' class='required form-control integer power-limit'/>
                        <span class='input-group-addon'>кВт/ч</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Льготная цена</span>
                        <input type='text' name='TariffsKeeper[power][$key][cost]' class='required form-control float power-cost'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена</span>
                        <input type='text' name='TariffsKeeper[power][$key][overcost]' class='required form-control float power-overcost'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                    </div>";
        }
    }
    if(!empty($lastTariffs->membershipQuartersForFilling)){
        echo "<h2>Тарифы на членские взносы</h2>";

	    if(!empty($lastTariffs->lastMembershipData)){
		    echo '<h3>Последняя заполненная ставка</h3>';
		    echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' class=' form-control mem-fixed disabled' disabled value='{$lastTariffs->lastMembershipData->fixed_part}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' class='form-control mem-float disabled' disabled value='{$lastTariffs->lastMembershipData->changed_part}'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                        <button type='button' class='btn btn-success glyphicon glyphicon-download copy-data'></button>
                     </div>
                    </div>";
	    }

        foreach ($lastTariffs->membershipQuartersForFilling as $key=>$value){
            echo "<h3>" .  TimeHandler::getFullFromShortQuarter($key) . "</h3>";
            echo "<div class='form-group membership-group'>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Фиксированная ставка</span>
                        <input type='text' name='TariffsKeeper[membership][$key][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                        </div>
                     </div>
                    <div class='col-lg-4'>
                        <div class='input-group'>
                        <span class='input-group-addon'>Цена с сотки</span>
                        <input type='text' name='TariffsKeeper[membership][$key][float]' class='required form-control float mem-float'/>
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
