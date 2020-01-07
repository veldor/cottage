<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.01.2019
 * Time: 12:43
 */

namespace app\models;


use app\priv\Info;
use function GuzzleHttp\Psr7\str;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

class ErrorsHandler extends Model {

private static $root;
private static $sMail = Info::TECH_MAIL;
private static $sName = 'Повелитель';
	/**
	 * @param $exception \Exception
	 */
	public static function addMyError($exception){
		self::$root = str_replace('\\', '/', Yii::getAlias('@app'));
		$errorInfo = 'Surprise ';
		$errorInfo .= time() . "\r\n";
		$errorInfo .= 'url ' .  Url::to() . "\r\n";
		$errorInfo .=  'message ' . $exception->getMessage() . "\r\n";
		$errorInfo .=  'code ' . $exception->getCode() . "\r\n";
		$errorInfo .=  'in file ' . $exception->getFile() . "\r\n";
		$errorInfo .=  'in sting ' . $exception->getLine() . "\r\n";
		$errorInfo .=  $exception->getTraceAsString() . "\r\n";
		if(!empty($_POST)){
			$errorInfo .= 'post is ';
			$errorInfo .= self::arrayToString($_POST);
		}
		if(!empty($_GET)){
			$errorInfo .= 'get is ';
			$errorInfo .= self::arrayToString($_GET);
		}
		// Помещу данные об ошибке в файл
		if(!is_dir(self::$root . "/errors")){
			if (!mkdir($concurrentDirectory = self::$root . "/errors") && !is_dir($concurrentDirectory)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
			}
		}
		// пишу ошибки в файл только если учётка не админская
        if (!Yii::$app->user->can('manage')) {
            file_put_contents(self::$root . '/errors/' . 'errors.txt', $errorInfo . "\r\n\r\n\r\n", FILE_APPEND);
        }
	}

	public static function arrayToString($arr){
		$answer = '';
		if(!empty($arr)){
			foreach ($arr as $key => $value) {
				if(is_array($value)){
					$val = self::arrayToString($value);
					$answer .= "\r\n\t $key => $val";
				}
				else{
					$answer .= "\r\n\t $key => $value";
				}
			}
		}
		return $answer;
	}

	public static function sendErrors(): int
	{
		self::$root = str_replace('\\', '/', Yii::getAlias('@app'));
		$file = self::$root . '/errors/' . 'errors.txt';
		if(is_file($file) && Cloud::sendErrors(self::$sMail, self::$sName, $file)) {
			unlink($file);
			return 0;
		}
		return 1;
	}
}