<?php


namespace app\models;


use app\models\database\CottageReport;
use app\models\database\Mail;
use app\models\database\MailingSchedule;
use app\models\database\SingleMail;
use app\models\handlers\BillsHandler;
use app\models\utils\DbTransaction;
use app\models\utils\Email;
use Exception;
use Throwable;
use Yii;
use yii\db\StaleObjectException;

class Mailing
{

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function createMailing(): array
    {
        $transaction = new DbTransaction();
        $title = Yii::$app->request->post('title');
        $body = Yii::$app->request->post('body');
        $mails = Yii::$app->request->post('addresses');
        $mailsList = '<mails>';
        if (empty($title)) {
            return ['message' => 'Не заполнен заголовок рассылки!'];
        }
        if (empty($body)) {
            return ['message' => 'Не заполнен текст рассылки!'];
        }
        if (empty($mails)) {
            return ['message' => 'Не выбраны адреса для рассылки!'];
        }
        $parsedMails = explode(',', $mails);
        $mailing = new database\Mailing();
        $mailing->title = $title;
        $mailing->body = $body;
        $mailing->mails_info = '1';
        $mailing->mailing_time = time();
        $mailing->save();
        if (!empty($mailing->id)) {
            foreach ($parsedMails as $mail) {
                $existentMail = Mail::getMailById($mail);
                if ($existentMail === null) {
                    return ['message' => 'Не найден адрес почты, возможно, удалён!'];
                }
                $mailsList .= "<mail id='{$existentMail->id}'/>";
                $mailingSchedule = new MailingSchedule();
                $mailingSchedule->mailId = $existentMail->id;
                $mailingSchedule->mailingId = $mailing->id;
                $mailingSchedule->save();
            }
            $mailsList .= '</mails>';
            $mailing->mails_info = $mailsList;
            $mailing->save();
            $transaction->commitTransaction();
            return ['status' => 1];
        }
        throw new ExceptionWithStatus('Почему-то не удалось создать рассылку');
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public static function cancelMailing(): array
    {
        $id = trim(Yii::$app->request->post('id'));
        if (!empty($id)) {
            $waitingMail = MailingSchedule::find()->where(['id' => $id])->one();
            if (empty($waitingMail)) {
                return ['message' => 'Похоже, данное письмо уже удалено из очереди, попробуйте обновить данную страницу.'];
            }
            $waitingMail->delete();
            return ['status' => 1];
        }

        return ['message' => 'Не найден идентификатор сообщения.'];
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public static function sendMessage(): array
    {
        $sendingResult = null;
        $id = trim(Yii::$app->request->post('id'));
        if (!empty($id)) {
            $waitingMail = MailingSchedule::find()->where(['id' => $id])->one();
            if (empty($waitingMail)) {
                return ['message' => 'Похоже, данное письмо уже удалено из очереди, попробуйте обновить данную страницу.'];
            }
            // информация о письме
            $mailInfo = Mail::getMailById($waitingMail->mailId);
            // информация об участке
            $cottageInfo = Cottage::getCottageByLiteral($mailInfo->cottage);

            $mailSettings = MailSettings::getInstance();
            if (!empty($waitingMail->billId)) {
                $mail = self::compileBillMail($waitingMail, $cottageInfo, $mailSettings, $mailInfo);
                try {
                    $mail->send();
                    $mail->sendToReserve();
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . GrammarHandler::convert_from_latin1_to_utf8_recursively($e->getMessage()) . '"'];
                }
            } else if (!empty($waitingMail->mailingId)) {
                $mailing = database\Mailing::findOne($waitingMail->mailingId);
                if ($mailing === null) {
                    return ['message' => 'Рассылка не найдена'];
                }
                $body = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => GrammarHandler::insertLexemes(urldecode($mailing->body), $mailInfo, $cottageInfo)]);

                // создам и отправлю новое письмо
                $mail = new Email();
                $mail->setFrom($mailSettings->address);
                $mail->setAddress($mailSettings->is_test ? $mailSettings->test_mail : $mailInfo->email);
                $mail->setSubject(urldecode($mailing->title));
                $mail->setBody($body);
                $mail->setReceiverName($mailInfo->fio);
                try {
                    $mail->send();
                    $mail->sendToReserve();
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . $e->getMessage() . '"'];
                }
            } elseif (!empty($waitingMail->singleMailId)) {
                $singleMail = SingleMail::findOne($waitingMail->singleMailId);
                if ($singleMail === null) {
                    return ['message' => 'Письмо не найдена'];
                }
                $body = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => GrammarHandler::insertLexemes(urldecode($singleMail->body), $mailInfo, $cottageInfo)]);

                // создам и отправлю новое письмо
                $mail = new Email();
                $mail->setFrom($mailSettings->address);
                $mail->setAddress($mailSettings->is_test ? $mailSettings->test_mail : $mailInfo->email);
                $mail->setSubject(urldecode($singleMail->subject));
                $mail->setBody($body);
                $mail->setReceiverName($mailInfo->fio);
                try {
                    $mail->send();
                    $mail->sendToReserve();
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . $e->getMessage() . '"'];
                }
            } elseif (!empty($waitingMail->reportId)) {
                $reportInfo = CottageReport::findOne($waitingMail->reportId);
                if ($reportInfo === null) {
                    return ['message' => 'Письмо не найдена'];
                }
                // сгенерирую PDF
                $info = Report::cottageReport($reportInfo->start, $reportInfo->finish, $cottageInfo);
                $reportPdf = Yii::$app->controller->renderPartial('/print/cottage-report-pdf', ['transactionsInfo' => $info, 'start' => $reportInfo->start, 'end' => $reportInfo->finish, 'cottageInfo' => $cottageInfo]);
                PDFHandler::renderPDF($reportPdf, 'report.pdf', 'landscape');
                // отправлю письмо
                $text = GrammarHandler::insertLexemes('
Для сверки расчетов Вам направляется отчет по платежам за участок №%COTTAGENUMBER%, произведенным на расчетный счет СНТ «Облепиха».  В отчете указаны даты поступления средств на расчетный счет.  Поскольку при оплате через Сбербанк средства зачисляются на следующий банковский день после платежа, даты фактической оплаты и даты в отчете могут различаться на 1-3 дня.', $mailInfo, $cottageInfo);
                $email = new Email();
                $email->setBody($text);
                $email->setSubject('Сверка');
                $email->setFrom(MailSettings::getInstance()->address);
                $email->setAddress(MailSettings::getInstance()->is_test ? MailSettings::getInstance()->test_mail : $mailInfo->email);
                $email->setReceiverName($mailInfo->fio);
                $root = str_replace('\\', '/', Yii::getAlias('@app'));
                $file = $root . '/public_html/report.pdf';
                $email->setAttachment(['url' => $file, 'name' => 'отчёт по платежам.pdf']);
                try {
                    $email->send();
                    $email->sendToReserve();
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . GrammarHandler::convert_from_latin1_to_utf8_recursively($e->getMessage()) . '"'];
                }
            } else {
                return ['message' => 'Не найден контент письма'];
            }
            $waitingMail->delete();
            return ['status' => 1];
        }
        return ['message' => 'Не найден идентификатор сообщения.'];
    }

    /**
     * @param $waitingMail
     * @param $cottageInfo
     * @param MailSettings $mailSettings
     * @param Mail $mailInfo
     * @return Email
     * @throws ExceptionWithStatus
     */
    public static function compileBillMail($waitingMail, $cottageInfo, MailSettings $mailSettings, Mail $mailInfo): Email
    {
        $billInfo = BillsHandler::getBill($waitingMail->billId);
        $payDetails = Filling::getPaymentDetails($billInfo);
        $info = ComplexPayment::getBankInvoice($billInfo->id, $billInfo instanceof Table_payment_bills_double);
        $text = Yii::$app->controller->renderPartial('/site/mail', ['billInfo' => $payDetails]);
        $text = GrammarHandler::insertPersonalAppeal($text, $cottageInfo->cottageOwnerPersonals);
        $info['bankInfo']->saveQr();
        $invoice = Yii::$app->controller->renderPartial('/payments/bank-invoice-pdf', ['info' => $info]);
        PDFHandler::renderPDF($invoice, 'invoice.pdf', 'portrait');
        // создам и отправлю новое письмо
        $mail = new Email();
        $mail->setFrom($mailSettings->address);
        $mail->setAddress($mailSettings->is_test ? $mailSettings->test_mail : $mailInfo->email);
        $mail->setSubject('Квитанция на оплату');
        $mail->setBody($text);
        $mail->setReceiverName($mailInfo->fio);
        $pdfUrl = str_replace('\\', '/', Yii::getAlias('@app')) . '/public_html/invoice.pdf';
        $qrUrl = str_replace('\\', '/', Yii::getAlias('@app')) . '/files/qr.png';
        //$mail->setAttachments([['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf'], ['url' => $qrUrl, 'name' => 'QR для оплаты.png'] ]);
        $mail->setAttachment(['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf']);
        $mail->setEmbed(['url' => $qrUrl, 'name' => 'QR для оплаты.png']);
        return $mail;
    }
}