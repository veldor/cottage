<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 21:55
 */

use app\models\AdditionalCottage;
use app\widgets\TargetsFillWidget;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $matrix AdditionalCottage */

$form = ActiveForm::begin(['id' => 'AdditionalCottage', 'options' => ['class' => 'form-horizontal bg-default'], 'enableAjaxValidation' => true, 'action' => ['/create/additional-cottage/' . $matrix->cottageNumber]]);
echo $form->field($matrix, 'cottageNumber', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($matrix, 'cottageSquare', ['template' =>
    '<div class="form-group col-lg-4">{label}</div><div class="col-lg-3"><div class="input-group">{input}<span class="input-group-addon">М<sup>2</sup></span></div> 
									{error}{hint}</div>'])
    ->textInput(['placeholder' => 'Например, 5000'])
    ->label('Площадь участка, в квадратных метрах.')
    ->hint("<b class='text-success'>Обязательное поле.</b>Целое число, в метрах.");

echo $form->field($matrix, 'differentOwner', ['template' => '<div class="col-lg-offset-4" data-toggle="buttons">{input}</div>{error}{hint}'])->checkbox([
    'label' => 'Отдельный владелец',
    'labelOptions' => ['class' => 'btn btn-info with-signal'],
], true);

/*=========================================================БЛОК ДОПОЛНИТЕЛЬНОГО ВЛАДЕЛЬЦА=============================*/

echo "<div class='color-orange'>";
echo $form->field($matrix, 'cottageOwnerPersonals', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Русскими буквами, данные владельца. Будут использованы, например, в автоматическом обращении в рассылках."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, Иванов Иван Иванович'])
    ->label('Фамилия имя и отчество владельца участка.')
    ->hint("<b class='text-success'>Обязательное поле.</b> Буквы, пробелы и тире.");

echo $form->field($matrix, 'cottageOwnerPhone', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="В перспективе- для звонков владельцу."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'autocomplete' => 'off', 'placeholder' => 'Например, 9201234567'])
    ->label(" Номер телефона владельца участка.")
    ->hint("\"<b class='text-info'>Необязательное поле.</b> В произвольном формате");
echo $form->field($matrix, 'cottageOwnerEmail', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'autocomplete' => 'off', 'placeholder' => 'Например, vasya@yandex.ru'])
    ->label('Адрес электронной почты владельца участка.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo "</div>";
echo "<div class='color-pinky'>";
echo $form->field($matrix, 'ownerAddressIndex', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, 000000'])
    ->label('Почтовый индекс.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressTown', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, Нижний Новгород'])
    ->label('Город проживания.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressStreet', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, улица Минина'])
    ->label('Название улицы.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressBuild', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, 23'])
    ->label('Номер дома.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");
echo $form->field($matrix, 'ownerAddressFlat', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-7">{input}
									{error}{hint}</div><div class="col-lg-1"><button type="button" tabindex="-1" class="btn btn-default glyphicon glyphicon-question-sign popover-btn"  data-container="body" data-toggle="popover" data-placement="top" data-content="Тут вроде бы тоже объяснять нечего."></button></div>'])
    ->textInput(['class' => 'form-control owner-dependent', 'disabled' => true, 'placeholder' => 'Например, 23'])
    ->label('Номер квартиры.')
    ->hint("<b class='text-info'>Необязательное поле.</b>");

/*============================================ЗАКОНЧИЛСЯ БЛОК ДОПОЛНИТЕЛЬНОГО ВЛАДЕЛЬЦА==================================*/


echo "</div><div class='color-sea'>";
echo $form->field($matrix, 'isPower', ['template' => '<div class="col-lg-offset-4" data-toggle="buttons">{input}</div>{error}{hint}'])->checkbox([
    'label' => 'Установлен счётчик электроэнергии',
    'labelOptions' => ['class' => 'btn btn-info with-signal'],
], true);
echo $form->field($matrix, 'currentPowerData', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-3"><div class="input-group">{input}<span class="input-group-addon">кВт.ч</span></div>
									{error}{hint}</div>'])
    ->textInput(['class' => 'form-control power-dependent', 'autocomplete' => 'off', 'placeholder' => 'Например, 9999', 'disabled' => true])
    ->label('Текущие показания счётчика электроэнергии, в киловаттах.')
    ->hint("<b class='text-success'>Обязательное поле.</b>Заполнять цифрами.");
echo $form->field($matrix, 'lastPayedMonth', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-3">{input}
									{error}{hint}</div>'])
    ->textInput(['class' => 'form-control power-dependent', 'placeholder' => 'Например, 2000-11', 'disabled' => true])
    ->label('Электроэнергия оплачена в:')
    ->hint("<b class='text-info'>Необязательное поле.</b> Последний оплаченный месяц. Год-месяц, цифрами. Год полностью, 4 цифры.");
echo $form->field($matrix, 'isMembership', ['template' => '<div class="col-lg-offset-4" data-toggle="buttons">{input}</div>{error}{hint}'])->checkbox([
    'label' => 'Оплачивать членские взносы',
    'labelOptions' => ['class' => 'btn btn-info with-signal'],
], true);
echo $form->field($matrix, 'membershipPayFor', ['template' =>
    '<div class="col-lg-4">{label}</div><div class="col-lg-3">{input}
									{error}{hint}</div>'])
    ->textInput(['class' => 'form-control membership-dependent', 'placeholder' => 'Например, 2000-4', 'disabled' => true])
    ->label('Членские взносы оплачены по:')
    ->hint("<b class='text-success'>Обязательное поле.</b> Последний оплаченный квартал. Год-квартал, цифрами. Год полностью, 4 цифры.");
echo $form->field($matrix, 'isTarget', ['template' => '<div class="col-lg-offset-4" data-toggle="buttons">{input}</div>{error}{hint}'])->checkbox([
    'label' => 'Оплачивать целевые платежи',
    'labelOptions' => ['class' => 'btn btn-info with-signal'],
], true);
try {
    echo TargetsFillWidget::widget(['tariffRates' => $matrix->targetInfo, 'disabled' => true, 'formName' => 'AdditionalCottage']);
} catch (Exception $e) {
}
echo $form->field($matrix, 'targetFilled', ['template' => "{input}"])->hiddenInput()->label(false);
echo "<div class='col-lg-12 text-center margened'>";
echo Html::submitButton('Создать', ['class' => 'btn btn-success btn-lg margened', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '</div>';
ActiveForm::end();