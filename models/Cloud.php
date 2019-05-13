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

    public static function sendMessage($info, $subject, $body): array
    {
        $results = [];
        $mail = Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setSubject($subject);
        $name = explode(' ', $info->cottageOwnerPersonals);
        if (count($name) === 3) {
            $username = "$name[1] $name[2]";
        } else {
            $username = $info->cottageOwnerPersonals;
        }
        $sendTo = [];
        if (!empty($info->cottageOwnerEmail)) {
            $name = explode(' ', $info->cottageOwnerPersonals);
            if (count($name) === 3) {
                $username = "$name[1] $name[2]";
            } else {
                $username = $info->cottageOwnerPersonals;
            }
            $address = $info->cottageOwnerEmail;
            $sendTo[] = [$address, $info->cottageOwnerPersonals];
            $results['to-owner'] = true;
        }
        if (!empty($info->cottageContacterEmail)) {
            $name = explode(' ', $info->cottageContacterPersonals);
            if (count($name) === 3) {
                $username = "$name[1] $name[2]";
            } else {
                $username = $info->cottageContacterPersonals;
            }
            $address = $info->cottageContacterEmail;
            $sendTo[] = [$address, $info->cottageContacterPersonals];
            $results['to-contacter'] = true;
        }

        $sendTo[] = [Info::BOOKER_MAIL, Info::BOOKER_NAME];

        foreach ($sendTo as $value) {
            $name = GrammarHandler::handlePersonals($value[1]);
            $text = "
<html lang='ru-RU'>
	<head>
		<meta charset='UTF-8'/><title></title>
	</head>
	<body>
		<table style='max-width: 600px; width: 100%; margin:0; padding: 0; text-align: center;'>
			<tbody>
				<tr>
					<td colspan='2'><h2>Добрый день, $name!</h2></td>
				</tr>
				<tr>
					<td colspan='2'>
					<table style='max-width: 600px; width: 100%; margin:0; padding: 0;background-color: #fcfff6'>
						<tbody>
							$body
						</tbody>
					</table>
					</td>
				</tr>
				<tr>
					<td colspan='2'>
						<table style='max-width: 600px; width: 100%; margin:0; padding: 0; text-align: center; background-color:#f9f7ff'>
							<tbody>
								<tr>
									<td>
										<h2>Контактная информация</h2>
									</td>
								</tr>
								<tr>
									<td>
							Телефон бухгалтера: <a href='tel:" . Info::BOOKER_PHONE . "'>
											<b>" . Info::BOOKER_SMOOTH_PHONE . "</b>
										</a>
									</td>
								</tr>
								<tr>
									<td>
							Официальная группа ВКонтакте: <a target='_blank' href='" . Info::VK_GROUP_URL . "'>Посетить</a>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</tbody>
		</table>
	</body>
</html>
";
            $mail->setHtmlBody($text);
            $mail->setTo([$value[0] => $value[1]]);
            file_put_contents('Z:\\testmail.html', $text);
            // проверю подключение к интернету
            try {
                $url = 'https://ya.ru/';
                ini_set('default_socket_timeout', '2');
                $fp = fopen($url, 'rb');
                fclose($fp);
            } catch (Exception $e) {
                return ['status' => 2];
            }
            try {
                $mail->send();
            } catch (Exception $e) {
                return ['status' => 2];
            }
        }
        return ['status' => 1, 'results' => $results];
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