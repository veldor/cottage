<?php

namespace app\models;

use app\priv\Info;
use Exception;
use Yandex\Disk\DiskClient;
use Yii;
use yii\base\Model;

class Cloud extends Model
{
    private $drive;
    public $updates;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->drive = new DiskClient($_SESSION['ya_auth']['access_token']);
        $this->drive->setServiceScheme(DiskClient::HTTPS_SCHEME);
    }


    public function uploadFile($filename, $source): bool
    {
        if (is_file($source)) {
            $this->drive->uploadFile(
                '/Updates/',
                array(
                    'path' => $source,
                    'size' => filesize($source),
                    'name' => $filename
                )
            );
            return true;
        }
        return false;
    }

    public function checkUpdates(): array
    {
        // проверю подключение к интернету
        try {
            $url = 'https://ya.ru/';
            ini_set('default_socket_timeout', '2');
            $fp = fopen($url, 'rb');
            fclose($fp);
        } catch (Exception $e) {
            return ['status' => 2];
        }
        $dirContent = $this->drive->directoryContents('/Updates');
        foreach ($dirContent as $dirItem) {
            if ($dirItem['resourceType'] === 'file') {
                $name = $dirItem['displayName'];
                if (preg_match('/^update\_[\d]{1,11}\-[\d]{1,11}\.info?/', $name)) {
                    $this->updates[] = $name;
                }
            }
        }
        return $this->updates;
    }

    public function getUpdates()
    {
        // Получаем список файлов из директории
        $dirContent = $this->drive->directoryContents('/Updates');
        foreach ($dirContent as $dirItem) {
            if ($dirItem['resourceType'] === 'file') {
                $name = $dirItem['displayName'];
                if (preg_match('/^update\_[\d]{1,11}\-[\d]{1,11}\.zip?/', $name)) {
                    $this->updates[] = $name;
                }
            }
        }
        return $this->updates;
    }

    public function downloadFile($path, $destination, $name): bool
    {
        if ($this->drive->downloadFile($path, $destination, $name)) {
            return true;
        }
        return false;
    }

    public static function sendInvoice($info): int
    {
        $address = $info['cottageInfo']->cottageOwnerEmail;
        if (empty($address)) {
            return 2;
        }
        $name = $info['cottageInfo']->cottageOwnerPersonals;
        $mailbody = '<h3>' . $name . ', привет!</h3>
                            <p>Это письмо отправлено автоматически. Отвечать на него не нужно.</p>
                            <p>Вам выставлен счет на оплату услуг садоводства.</p>
                            ';
        if (Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setTo([$address => $name])
            ->setSubject('Счет на оплату')
            ->attach('Z:\sites\cottage\mail\files\1.pdf', ['fileName' => 'Квитанция.pdf'])
            ->setHtmlBody($mailbody)
            ->send()
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * @param $address string
     * @param $name
     * @param $file
     * @return int
     */
    public static function sendErrors($address, $name, $file): int
    {
        if (empty($address)) {
            return 2;
        }
        $mailbody = '<h3>' . $name . ', привет!</h3>
                            <p>Это письмо отправлено автоматически. Отвечать на него не нужно.</p>
                            <p>В проекте появились какие-то ошибки.</p>
                            ';
        if (Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setTo([$address => $name])
            ->setSubject('Новая порция ошибок')
            ->attach($file, ['fileName' => 'Список ошибок.txt'])
            ->setHtmlBody($mailbody)
            ->send()
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * @param $info
     * @param $subject
     * @param $body
     * @return array
     */
    public static function sendMessage($info, $subject, $body): array
    {
        $body = "<h1 class='text-center'>Добрый день, %USERNAME%</h1>" . $body;
        $text = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => $body]);
        $main = Cottage::isMain($info);
        $ownerMail = $info->cottageOwnerEmail;
        if($main){
            $contacterEmail = $info->cottageContacterEmail;
        }
        try {
            if (!empty($ownerMail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
                self::send($ownerMail, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText);
                // отправлю письмо адресату
            }
            if (!empty($contacterEmail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageContacterPersonals);
                self::send($contacterEmail, GrammarHandler::handlePersonals($info->cottageContacterPersonals), $subject, $finalText);
                // отправлю письмо адресату
            }
            $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
            self::send(Info::MAIL_REPORTS_ADDRESS, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText);
            return ['status' => 1];
        } catch (ExceptionWithStatus $e) {
            die('dont send');
        }
    }

    public static function sendInvoiceMail($text, $billInfo)
    {
        // проверю получателей
        /** @var Table_cottages|Table_additional_cottages $cottageInfo */
        $pdfUrl = str_replace('\\', '/', Yii::getAlias('@app')) . '/public_html/invoice.pdf';
        $cottageInfo = $billInfo['billInfo']['cottageInfo'];
        $main = Cottage::isMain($cottageInfo);
        $ownerMail = $cottageInfo->cottageOwnerEmail;
        if($main){
            $contacterEmail = $cottageInfo->cottageContacterEmail;
        }
        $message = '';
        try {
            if (!empty($ownerMail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $cottageInfo->cottageOwnerPersonals);
                $message .= 'Отправлено владельцу ';
                self::send($ownerMail, GrammarHandler::handlePersonals($cottageInfo->cottageOwnerPersonals), 'Квитанция на оплату', $finalText, ['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf']);
                // отправлю письмо адресату
            }
            if (!empty($contacterEmail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $cottageInfo->cottageContacterPersonals);
                $message .= 'Отправлено к.л. ';
                self::send($contacterEmail, GrammarHandler::handlePersonals($cottageInfo->cottageContacterPersonals), 'Квитанция на оплату', $finalText, ['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf']);
                // отправлю письмо адресату
            }
            $finalText = GrammarHandler::insertPersonalAppeal($text, $cottageInfo->cottageOwnerPersonals);
            self::send(Info::MAIL_REPORTS_ADDRESS, GrammarHandler::handlePersonals($cottageInfo->cottageOwnerPersonals), 'Квитанция на оплату', $finalText, ['url' => $pdfUrl, 'name' => 'Квитанция на оплату.pdf']);
            if ($message === '') {
                $message = 'Почта не указана';
            }
            return $message;
        } catch (ExceptionWithStatus $e) {
            die('dont send');
        }
    }

    /**
     * @param $address
     * @param $receiverName
     * @param $subject
     * @param $body
     * @param null $attachment
     * @throws ExceptionWithStatus
     */
    public static function send($address, $receiverName, $subject, $body, $attachment = null)
    {
        $mail = Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTo([$address => $receiverName]);
        if (!empty($attachment)) {
            $mail->attach($attachment['url'], ['fileName' => $attachment['name']]);
        }
        try {
            $mail->send();
        } catch (Exception $e) {
            // отправка не удалась, переведу сообщение в неотправленные
            throw new ExceptionWithStatus($e->getMessage(), 3);
        }
    }

    public static function messageToUnsended($cottageId, $subject, $body)
    {
        $msg = new Table_unsended_messages();
        $msg->cottageNumber = $cottageId;
        $msg->subject = $subject;
        $msg->body = $body;
        $msg->save();
    }
}