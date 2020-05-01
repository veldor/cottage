<?php


namespace app\models\database;


use app\models\ExceptionWithStatus;
use app\models\handlers\BillsHandler;
use app\models\MailSettings;
use Dompdf\Exception;
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
        $cottageInfo = \app\models\Cottage::getCottageByLiteral($billInfo->cottageNumber);
        // получу почтовые ящики для данного участка
        $mails = Mail::getCottageMails($billInfo->cottageNumber);
        if(!empty($mails)){
            $inQueue = [];
            foreach ($mails as $mail) {
                $schedule = new self(['mailId' => $mail->id, 'billId' => $identificator]);
                $schedule->save();
                $result = ['schedule' => $schedule, 'mail' => $mail];
                $inQueue[] = $result;
            }
            if(!empty($inQueue)){
                /** @var MailingSchedule $item */
                foreach ($inQueue as $item) {
                    // попытаюсь отправить все сообщения
                    try {
                        $mail = \app\models\Mailing::compileBillMail(
                            $item['schedule'],
                            $cottageInfo,
                            MailSettings::getInstance(),
                            $item['mail']
                        );
                        $mail->send();
                        $mail->sendToReserve();
                        $item['schedule']->delete();
                    } catch (\Exception $e) {
                        return ['status' => 1, 'message' => 'Отправка не удалась, сообщения добавлены в очередь отправки. Отправьте их вручную.'];
                    }
                }
            }
            return ['status' => 1, 'message' => 'Сообщения отправлены'];
        }
        return ['status' => 2, 'message' => 'Не найдено адресов электронной почты'];
    }
}