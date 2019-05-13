<?php

namespace app\assets;

use yii\web\AssetBundle;

class AuthAsset extends AssetBundle{
	public $basePath = '@webroot';
	public $baseUrl = '@web';
	public $css = [
		'css/auth.css',
	];
	public $js = [
		'js/auth.js',
	];
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
	];
}