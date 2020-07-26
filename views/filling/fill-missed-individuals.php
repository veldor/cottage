<?php

use app\assets\AppAsset;
use app\models\Cottage;
use app\models\small_classes\IndividualMissing;
use app\models\TimeHandler;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */

AppAsset::register($this);

/** @var bool $error */
if($error){
    echo "<h1 class='text-center text-danger'>Произошла ошибка сохранения</h1><p>Проверьте, заполнены ли все поля, и везде ли введены верные цифры.</p>";
}

$form = ActiveForm::begin(['id' => 'PersonalTariffFilling', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false]);

?>
<h1 class="text-center">Необходимо заполнить индивидуальные тарифы</h1>

<?php

/** @var IndividualMissing[] $items */
foreach ($items as $item) {
    $cottageNumber = Cottage::getCottageNumber($item->cottageInfo);
    echo "<h2> Участок №$cottageNumber</h2>";
    if(!empty($item->targetMissing)){
        echo "<h3>Целевые взносы</h3>";
        foreach ($item->targetMissing as $duty) {
            echo "<h3>$duty</h3>";
?>
<div class='form-group membership-group'>
                <div class='col-sm-4'>
                    <div class='input-group'>
                        <span class='input-group-addon'>С дачи</span>
                        <input type='number' step='0.01' name='PersonalTariffFilling[target][<?=$cottageNumber?>][<?=$duty?>][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class='input-group'>
                        <span class='input-group-addon'>С сотки</span>
                        <input type='number' step='0.01' name='PersonalTariffFilling[target][<?=$cottageNumber?>][<?=$duty?>][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class='input-group'>
                        <span class='input-group-addon'>Оплачено</span>
                        <input type='number' step='0.01' name='PersonalTariffFilling[target][<?=$cottageNumber?>][<?=$duty?>][payed-before]' class='required form-control float mem-float ready'/>
                        <span class='input-group-addon'>&#8381;</span>
                    </div>
                </div>
            </div>
<?php
        }
    }
    if(!empty($item->membershipMissing)){
        echo "<h3>Членские взносы</h3>";
        foreach ($item->membershipMissing as $key=>$duty) {
            echo "<h3>" . TimeHandler::getFullFromShortQuarter($key) . "</h3>";
?>
            <div class='form-group membership-group'>
                <div class='col-sm-5'>
                    <div class='input-group'>
                        <span class='input-group-addon'>С дачи</span>
                        <input type='number' step='0.01' name='PersonalTariffFilling[membership][<?=$cottageNumber?>][<?=$key?>][fixed]' class='required form-control float mem-fixed'/>
                        <span class='input-group-addon'>&#8381;</span>
                    </div>
                </div>
                <div class='col-sm-4'>
                    <div class='input-group'>
                        <span class='input-group-addon'>С сотки</span>
                        <input type='number' step='0.01' name='PersonalTariffFilling[membership][<?=$cottageNumber?>][<?=$key?>][float]' class='required form-control float mem-float'/>
                        <span class='input-group-addon'>&#8381;</span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
?>
    <button class="btn btn-success" type="submit">Сохранить</button>
<?php
ActiveForm::end();
