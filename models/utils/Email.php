<?php


namespace app\models\utils;


use app\models\GrammarHandler;
use app\models\MailSettings;
use Yii;
use yii\base\Model;

class Email extends Model
{
    private string $address;
    private string $receiverName;
    private string $subject;
    private string $body;
    private array $attachment;
    private const RESERVE_MAIL_ADDRESS = 'oblepiha.reports@gmail.com';

    /**
     * @var string
     */
    private string $from;

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @param string $receiverName
     */
    public function setReceiverName(string $receiverName): void
    {
        $this->receiverName = $receiverName;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * @param array $attachment
     */
    public function setAttachment(array $attachment): void
    {
        $this->attachment = $attachment;
    }

    /**
     * @param string $address
     */
    public function setFrom(string $address): void
    {
        $this->from = $address;
    }

    /**
     *
     */
    public function send(): void
    {
        $mail = Yii::$app->mailer->compose()
            ->setFrom([$this->from => MailSettings::getInstance()->snt_name])
            ->setSubject($this->subject)
            ->setHtmlBody($this->body)
            ->setTo([$this->address => $this->receiverName]);
        if (!empty($this->attachment)) {
            $mail->attach($this->attachment['url'], ['fileName' => $this->attachment['name']]);
        }
        // попробую отправить письмо, в случае ошибки- вызову исключение
        $mail->send();
    }

    /**
     * отправка почты на резервный адрес
     *
     */
    public function sendToReserve(): void
    {
        // если это админская учётка- действие не выполняется
        if(!Yii::$app->user->can('manage')){
            $mail = Yii::$app->mailer->compose()
                ->setFrom([$this->from => MailSettings::getInstance()->snt_name])
                ->setSubject($this->subject)
                ->setHtmlBody($this->body)
                ->setTo([self::RESERVE_MAIL_ADDRESS => $this->receiverName]);
            if (!empty($this->attachment)) {
                $mail->attach($this->attachment['url'], ['fileName' => $this->attachment['name']]);
            }
            // попробую отправить письмо, в случае ошибки- вызову исключение
            $mail->send();
        }
    }


}