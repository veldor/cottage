<?php


namespace app\models;

use Yii;
use yii\base\Model;

class MailSettings extends Model
{
    public $user_pass;
    public $user_name;
    public $address;
    public $snt_name;
    public $is_test;
    public $test_mail;


    public function attributeLabels():array
    {
        return [
            'address' => 'Адрес почты',
            'user_pass' => 'Пароль',
            'user_name' => 'Логин',
            'snt_name' => 'Название СНТ',
            'is_test' => 'Тестовая отправка электронной почты',
            'test_mail' => 'Адрес почты для теста',
        ];
    }

    /**
     * @return array
     */
    public function rules():array
    {
        return [
            // name, email, subject и body атрибуты обязательны
            [['address', 'user_pass', 'user_name', 'snt_name', 'is_test', 'test_mail'], 'required'],
        ];
    }

    public function __construct()
    {
        parent::__construct();
        // прочитаю настройки из файла
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/settings/mail_settings';
        if (!is_file($file)) {
            // создаю файл
            file_put_contents($file, "test\ntest\ntest\ntest\n\n");
        }
        $content = file_get_contents($file);
        $settingsArray = mb_split("\n", $content);
        $this->address = $settingsArray[0];
        $this->user_name = $settingsArray[1];
        $this->user_pass = $settingsArray[2];
        $this->snt_name = $settingsArray[3];
        $this->is_test = (bool) $settingsArray[4];
        $this->test_mail = $settingsArray[5];
    }

    public function saveSettings()
    {
        $file = dirname(Yii::getAlias('@webroot') . './/') . '/settings/mail_settings';
        file_put_contents($file, "{$this->address}\n{$this->user_name}\n{$this->user_pass}\n{$this->snt_name}\n{$this->is_test}\n{$this->test_mail}");
        Yii::$app->session->addFlash('success', 'Данные сервера электронной почты обновлены.');
        return ['status' => 1];
    }
}