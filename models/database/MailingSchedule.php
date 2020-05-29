<?php


namespace app\models\database;


use app\models\GrammarHandler;
use app\models\handlers\BillsHandler;
use app\models\MailSettings;
use app\models\PDFHandler;
use app\models\Report;
use app\models\Table_cottages;
use app\models\utils\Email;
use Exception;
use Throwable;
use Yii;
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
 * @property int $singleMailId [int(10) unsigned]
 * @property int $reportId [int(10) unsigned]
 */
class MailingSchedule extends ActiveRecord
{

    public static function tableName(): string
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
        if (!empty($allMessages)) {
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
        if (!empty($mails)) {
            $inQueue = [];
            foreach ($mails as $mail) {
                $schedule = new self(['mailId' => $mail->id, 'billId' => $identificator]);
                $schedule->save();
                $result = ['schedule' => $schedule, 'mail' => $mail];
                $inQueue[] = $result;
            }
            if (!empty($inQueue)) {
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
                    } catch (Exception $e) {
                        return ['status' => 1, 'message' => 'Отправка не удалась, сообщения добавлены в очередь отправки. Отправьте их вручную.'];
                    }
                }
            }
            return ['status' => 1, 'message' => 'Сообщения отправлены'];
        }
        return ['status' => 2, 'message' => 'Не найдено адресов электронной почты'];
    }


    /**
     * @param Table_cottages $cottageInfo
     * @param string $subject
     * @param string $body
     * @return array
     */
    public static function addSingleMailing(Table_cottages $cottageInfo, string $subject, string $body): array
    {
        // получу адреса почты
        $mails = Mail::getCottageMails($cottageInfo->cottageNumber);
        if (!empty($mails)) {
            $newMail = new SingleMail(['subject' => $subject, 'body' => $body]);
            $newMail->save();
            $inQueue = [];
            // создам письмо
            foreach ($mails as $mail) {
                $schedule = new self(['mailId' => $mail->id, 'singleMailId' => $newMail->id]);
                $schedule->save();
                $result = ['schedule' => $schedule, 'mail' => $mail];
                $inQueue[] = $result;
            }
            if (!empty($inQueue)) {
                /** @var MailingSchedule $item */
                foreach ($inQueue as $item) {
                    // попытаюсь отправить все сообщения
                    try {
                        $body = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => GrammarHandler::insertLexemes(urldecode($body), $item['mail'], $cottageInfo)]);
                        $email = new Email();
                        $email->setBody($body);
                        $email->setSubject($subject);
                        $email->setFrom(MailSettings::getInstance()->address);
                        $email->setAddress(MailSettings::getInstance()->is_test ? MailSettings::getInstance()->test_mail : $item['mail']->email);
                        $email->setReceiverName($item['mail']->fio);
                        $email->send();
                        $email->sendToReserve();
                        $item['schedule']->delete();
                    } catch (Exception $e) {
                        return ['status' => 1, 'message' => 'Отправка не удалась, сообщения добавлены в очередь отправки. Отправьте их вручную.'];
                    }
                }
                return ['status' => 1, 'message' => 'Сообщения отправлены'];
            }
            // попробую отправить письмо, если не получится- добавлю его в очередь рассылки
        }
        return ['status' => 2, 'message' => 'Не найдено адресов электронной почты'];
    }

    /**
     * @param string $id
     * @param string $start
     * @param string $finish
     * @return array
     */
    public static function scheduleReport(string $id, string $start, string $finish): array
    {
        $cottageInfo = \app\models\Cottage::getCottageByLiteral($id);
        $mails = Mail::getCottageMails($cottageInfo->cottageNumber);
        if (!empty($mails)) {
            $report = new CottageReport(['cottage_number' => $id, 'start' => $start, 'finish' => $finish]);
            $report->save();
            $inQueue = [];
            foreach ($mails as $mail) {
                $schedule = new self(['mailId' => $mail->id, 'reportId' => $report->id]);
                $schedule->save();
                $result = ['schedule' => $schedule, 'mail' => $mail];
                $inQueue[] = $result;
                if(!empty($inQueue)){
                    foreach ($inQueue as $item) {
                        // попытаюсь отправить все сообщения
                        try {
                            $text = GrammarHandler::insertLexemes('
Для сверки расчетов Вам направляется отчет по платежам за участок №%COTTAGENUMBER%, произведенным на расчетный счет СНТ «Облепиха».  В отчете указаны даты поступления средств на расчетный счет.  Поскольку при оплате через Сбербанк средства зачисляются на следующий банковский день после платежа, даты фактической оплаты и даты в отчете могут различаться на 1-3 дня.', $item['mail'], $cottageInfo);
                            // сгенерирую PDF
                            $info = Report::cottageReport($start, $finish, $cottageInfo->cottageNumber);
                            $reportPdf = Yii::$app->controller->renderPartial('/print/cottage-report-pdf', ['transactionsInfo' => $info, 'start' => $start, 'end' => $finish, 'cottageInfo' => $cottageInfo]);
                            PDFHandler::renderPDF($reportPdf, 'report.pdf', 'landscape');
                            $email = new Email();
                            $email->setBody($text);
                            $email->setSubject('Сверка');
                            $email->setFrom(MailSettings::getInstance()->address);
                            $email->setAddress(MailSettings::getInstance()->is_test ? MailSettings::getInstance()->test_mail : $item['mail']->email);
                            $email->setReceiverName($item['mail']->fio);
                            $root = str_replace('\\', '/', Yii::getAlias('@app'));
                            $file = $root . '/public_html/report.pdf';
                            $email->setAttachment(['url' => $file, 'name' => 'отчёт по платежам.pdf']);
                            $email->send();
                            $email->sendToReserve();
                            $item['schedule']->delete();
                        } catch (Exception $e) {
                            return ['status' => 1, 'message' => 'Отправка не удалась, сообщения добавлены в очередь отправки. Отправьте их вручную.'];
                        }
                    }
                    return ['status' => 1, 'message' => 'Сообщения отправлены'];
                }
            }
        }
        return ['status' => 2, 'message' => 'Не найдено адресов электронной почты'];
    }
}