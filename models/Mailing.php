<?php


namespace app\models;


use app\models\database\Mail;
use app\models\database\MailingSchedule;
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
                $billInfo = BillsHandler::getBill($waitingMail->billId);
                $payDetails = Filling::getPaymentDetails($billInfo);
                $text = Yii::$app->controller->renderPartial('/site/mail', ['billInfo' => $payDetails]);
                $text = GrammarHandler::insertPersonalAppeal($text, $cottageInfo->cottageOwnerPersonals);

                $info = ComplexPayment::getBankInvoice($waitingMail->billId);
                $invoice =  Yii::$app->controller->renderPartial('/payments/bank-invoice-pdf', ['info' => $info]);
                PDFHandler::renderPDF($invoice, 'invoice.pdf', 'portrait');
                // создам и отправлю новое письмо
                $mail = new Email();
                $mail->setFrom($mailSettings->address);
                $mail->setAddress($mailSettings->is_test ? $mailSettings->test_mail : $mailInfo->email);
                $mail->setSubject('Квитанция на оплату');
                $mail->setBody($text);
                $mail->setReceiverName($mailInfo->fio);
                $pdfUrl = str_replace('\\', '/', Yii::getAlias('@app')) . '/public_html/invoice.pdf';
                $mail->setAttachment(['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf']);
                try {
                    $mail->send();
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . $e->getMessage() . '"'];
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
                } catch (Exception $e) {
                    return ['message' => 'Отправка не удалась, текст ошибки- "' . $e->getMessage() . '"'];
                }
            } else {
                return ['message' => 'Не найден контент письма'];
            }
            $waitingMail->delete();
            return ['status' => 1];
        }
        return ['message' => 'Не найден идентификатор сообщения.'];
    }
}