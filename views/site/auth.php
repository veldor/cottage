<?php
use app\assets\AppAsset;

AppAsset::register($this);
/** @var \yii\base\model $authModel */
echo "<a href='" . $authModel->link . "'>Авторизация на Яндексе</a>";
