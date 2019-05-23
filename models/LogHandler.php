<?php


namespace app\models;


use Yii;

class LogHandler
{
    const MAIL_ERRORS_LOG = "mail_errors.log";

 public static function writeToLog($logName, $text){
     $root = str_replace('\\', '/', Yii::getAlias('@app')) . '/logs/';
     file_put_contents($root . $logName, $text . "\n", FILE_APPEND);
 }
}