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
 * @property int $mailingId [int(10) unsigned]
 * @property int $mailId [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 */

class MailingSchedule extends ActiveRecord
{

    public static function tableName()
    {
        return 'mailing_schedule';
    }

    /**
     * @return MailingSchedule[]
     */
    public static function getWaiting()
    {
        return self::find()->orderBy('cast(mailingId as unsigned) asc')->all();
    }

    public static function countWaiting()
    {
        return self::find()->count();
    }
}