<?php

/* @var $this yii\web\View */
use app\assets\IndexAsset;
use app\models\TimeHandler;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
IndexAsset::register($this);

$this->title = 'Необходимо заполнить данные за предыдущий месяц';

setlocale(LC_ALL, 'RUS');
?>
<div class="row">
    <div class="col-lg-12">
        <h1>Не заполнены необходимые данные о тарифах</h1>
        <p class="text-danger">Тариф на месяц не заполнен. Введите данные для сохранения</p>
    </div>
    <?php
    $form = ActiveForm::begin(['id' => 'tarrifsSetupForm', 'options'=> ['class' => 'form-horizontal bg-default'],'enableAjaxValidation' => true,'action'=>['/tariffs']]);
    /** @var \app\models\TariffsKeeper $lastTarrifs */
    echo "<h3>Тариф на электроэнергию, " . TimeHandler::getPreviousMonth(). "</h3>";
    echo $form->field($lastTarrifs, 'targetMonth',['template' => "{input}"])->hiddenInput()->label(false);
    echo $form->field($lastTarrifs, 'powerLimit' ,['template' =>
        '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">Кв</span></div>
									{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off'])
        ->label('Лимит льготного использования электроэнергии.')
        ->hint("Цифрами в киловаттах, разделитель- точка.");
    echo $form->field($lastTarrifs, 'powerCost' ,['template' =>
        '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off'])
        ->label('Цена киловатта энергии в пределах льготного лимита.')
        ->hint("Цифрами, разделитель- точка.");
    echo $form->field($lastTarrifs, 'powerOvercost' ,['template' =>
        '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
        ->textInput(['autocomplete' => 'off'])
        ->label('Цена киловатта энергии за пределами льготного лимита.')
        ->hint("Цифрами, разделитель- точка.");
    echo  "<h3>Членские взносы, квартал " . TimeHandler::getCurrentQuarter(). "</h3>";
    if(empty($lastTarrifs->membersFixedPay)){
        echo $form->field($lastTarrifs, 'membersFixedPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off'])
            ->label('Фиксированная часть членского взноса.')
            ->hint("За квартал, цифрами, разделитель- точка.");
        echo $form->field($lastTarrifs, 'membersFloatPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off'])
            ->label('Переменная часть членского взноса.')
            ->hint("За квартал, цена за еденицу площади, разделитель- точка.");
    }
    else{
        echo $form->field($lastTarrifs, 'membersFixedPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off', 'readonly' => true])
            ->label('Фиксированная часть членского взноса.')
            ->hint("За квартал, цифрами, разделитель- точка.");
        echo $form->field($lastTarrifs, 'membersFloatPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off', 'readonly' => true])
            ->label('Переменная часть членского взноса.')
            ->hint("За квартал, цена за еденицу площади, разделитель- точка.");

    }
    echo  "<h3>Целевые взносы, год " . TimeHandler::getThisYear(). "</h3>";
    if(empty($lastTarrifs->targetFixedPay)){
        echo $form->field($lastTarrifs, 'targetFixedPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off'])
            ->label('Фиксированная часть целевого взноса.')
            ->hint("За квартал, цифрами, разделитель- точка.");
        echo $form->field($lastTarrifs, 'targetFloatPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off'])
            ->label('Переменная часть членского взноса.')
            ->hint("За квартал, цена за еденицу площади, разделитель- точка.");
        echo $form->field($lastTarrifs, 'targetDescription' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2">{input}{error}{hint}</div>'])
            ->textarea(['autocomplete' => 'off'])
            ->label('Цели взноса.');
    }
    else{
        echo $form->field($lastTarrifs, 'targetFixedPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off', 'readonly' => true])
            ->label('Фиксированная часть целевого взноса.')
            ->hint("За год, цифрами, разделитель- точка.");
        echo $form->field($lastTarrifs, 'targetFloatPay' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2"><div class="input-group">{input}<span class="input-group-addon">&#8381;</span></div>
									{error}{hint}</div>'])
            ->textInput(['autocomplete' => 'off', 'readonly' => true])
            ->label('Переменная часть членского взноса.')
            ->hint("За год, цена за еденицу площади, разделитель- точка.");
        echo $form->field($lastTarrifs, 'targetDescription' ,['template' =>
            '<div class="col-lg-6">{label}</div><div class="col-lg-2">{input}{error}{hint}</div>'])
            ->textarea(['autocomplete' => 'off'])
            ->label('Цели взноса.');

    }
    echo Html::submitButton('Сохранить', ['class' => 'btn btn-success', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement'=> 'top', 'data-html' => 'true',]);
    ActiveForm::end();
    ?>
</div>
<div id="alertsContentDiv"></div>
