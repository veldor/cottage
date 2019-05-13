<?php

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

use app\models\ErrorsHandler;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';
try{
	(new yii\web\Application($config))->run();
}
catch (Exception $e){
	// Обработаю ошибку
	ErrorsHandler::addMyError($e);
	throw $e;
}
