<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 08.10.2018
 * Time: 8:33
 */

use app\assets\CottageAsset;

CottageAsset::register($this);

$this->title = 'Не заполнены тарифы!';

/* @var $this \yii\web\View */
?>
<h1 id="tariff-no-filled">Необходимо заполнить тарифы за прошлый месяц</h1>
