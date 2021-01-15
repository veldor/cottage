<?php

use app\assets\AppAsset;
use yii\web\View;

/* @var $this View */

AppAsset::register($this);

$cottageInfo = \app\models\Cottage::getCottageByLiteral("93-a");
$lastPayedQuarter = \app\models\MembershipHandler::getLastPayedQuarter($cottageInfo);
echo $lastPayedQuarter;
