<?php


namespace app\models\database;


use app\models\handlers\BillsHandler;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

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

    public static function tableName():string
    {
        return 'mailing_schedule';
    }

    /**
     * @return MailingSchedule[]
     */
    public static function getWaiting(): array
    {
        return self::find()->orderBy('cast(mailingId as unsigned) asc')->all();
    }

    public static function countWaiting()
    {
        return self::find()->count();
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public static function clearSchedule(): array
    {
        $allMessages = self::find()->all();
        if(!empty($allMessages)){
            foreach ($allMessages as $message) {
                $message->delete();
            }
        }
        return ['status' => 1];
    }

    /**
     * @param $identificator
     * @return array
     */
    public static function addBankInvoiceSending($identificator): array
    {
        // получу информацию о счёте
        $billInfo = BillsHandler::getBill($identificator);
        // получу почтовые ящики для данного участка
        $mails = Mail::getCottageMails($billInfo->cottageNumber);
        if(!empty($mails)){
            foreach ($mails as $mail) {
                (new self(['mailId' => $mail->id, 'billId' => $identificator]))->save();
            }
            return ['status' => 1, 'message' => 'Сообщения добавлены в очередь отправки'];
        }
        return ['status' => 2, 'message' => 'Не найдено адресов электронной почты'];
    }
}