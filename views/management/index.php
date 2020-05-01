<?php

use app\assets\ManagementAsset;
use app\models\MailSettings;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $mailSettings MailSettings */

ManagementAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Всякие разные настройки';
?>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Обшие действия</a>
    </li>
    <li><a href="#email_set" data-toggle="tab">Настройки почты</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="global_actions">
        <div class="row">
            <div class="col-lg-12 margened">
                <button class="btn btn-default" id="sendBackupButton"><span class="text-info">Отправить бекап</span>
                </button>
                <button class="btn btn-default activator" id="createReportBtn" data-action="/report/choose-date"><span
                            class="text-info">Сформировать отчёт</span></button>
            </div>
        </div>
    </div>
    <div class="tab-pane margened" id="email_set">
        <?php
        $form = ActiveForm::begin([
            'id' => 'mail-settings-form',
            'options' => ['class' => 'form'],
            'enableAjaxValidation' => false,
            'validateOnSubmit' => false
        ]);

        echo $form->field($mailSettings, 'address', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->textInput()
            ->hint('Введите адрес почты, с коротого будет осуществляться отправка почты');

        echo $form->field($mailSettings, 'user_name', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->textInput()
            ->hint('Введите имя пользователя почты, с коротой будет осуществляться отправка почты');

        echo $form->field($mailSettings, 'user_pass', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->passwordInput()
            ->hint('Введите пароль почты, с коротой будет осуществляться отправка почты');

        echo $form->field($mailSettings, 'snt_name', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->textInput()
            ->hint('Введите название СНТ, которое будет отображаться в заголовке письма');
        echo $form->field($mailSettings, 'is_test', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->checkbox()
            ->hint('Если активно- почта будет отправляться на указанный ниже адрес вместо отправки реальным получателям');
        echo $form->field($mailSettings, 'test_mail', ['template' =>
            '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
            ->textInput()
            ->hint('Введите адрес почты, на который почта будет отправляться в тестовом режиме');
        ?>

        <div class="form-group">
            <div class="col-lg-offset-1 col-lg-11">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php
        ActiveForm::end();
        ?>
    </div>
</div>
