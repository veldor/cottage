<?php

use app\assets\AppAsset;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use yii\web\View;

/* @var $this View */

AppAsset::register($this);


$options = new QROptions([
    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
]);

$data = "hello, world!";

(new \app\models\utils\QRImageGenerator())->generateQr($data);
