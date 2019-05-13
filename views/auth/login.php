<?php

use app\assets\AuthAsset;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Необходима аутентификация';

AuthAsset::register($this);

?>
<div class="site-login">
	<div class="text-center">
		<h1><?= Html::encode($this->title) ?></h1>
		
		<p>Сайт находится в тестовом режиме, доступ ограничен!</p>

		<p>Заполните поля для входа:</p>
	</div>

    <div class="row">
		<div class="col-lg-4"></div>
        <div class="col-lg-4">
            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                <?= $form->field($auth, 'name')->textInput(['autofocus' => true])->hint('Введите логин.')->label('Имя пользователя') ?>

                <?= $form->field($auth, 'password')->passwordInput()->hint('Введите пароль.')->label('Пароль') ?>

                <?= $form->field($auth, 'rememberMe')->checkbox()->hint('Если активно, вам не придётся вводить данные заново при каждом визите.')->label('Запомнить.') ?>

                <div class="form-group">
                    <?= Html::submitButton('Вход', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
	</div>