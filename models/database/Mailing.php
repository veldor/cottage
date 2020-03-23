<?php


namespace app\models\database;


use app\models\selections\CottageMail;
use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $title
 * @property string $body
 */

class Mailing extends ActiveRecord
{

    public static function tableName()
    {
        return 'mailings';
    }
}