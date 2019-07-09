<?php

use app\models\TimeHandler;
use yii\web\View;

/* @var $this View */

$billInfo = \app\models\Table_payment_bills::findOne(542);
echo \app\models\Filling::getPaymentDetails($billInfo);


