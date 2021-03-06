<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.09.2018
 * Time: 12:13
 */

namespace app\assets;

use yii\web\AssetBundle;

class FillingAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/globals.js',
        'js/filling.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}