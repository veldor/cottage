<?php


namespace app\models;


use app\models\database\Bill;
use chillerlan\QRCode\QRCode;
use Yii;
use yii\base\Model;

class Invoice extends Model
{
    public $st;
    public $snt_name;
    public $personalAcc;
    public $bankName;
    public $bik;
    public $correspAcc;
    public $payerInn;
    public $kpp;
    // personal info
    public $persAcc;
    public $lastname;
    public $payerAddress;
    public $category;
    public $purpose;
    public $sum;

    public static function getInstance(database\Bill $billInfo)
    {
        $template = self::getBankInfo();
        $template->persAcc = $billInfo->cottageNumber;
        $template->lastname = $billInfo->payer;
        $template->payerAddress = $billInfo->payer_address;
        $template->category = $billInfo->service_name;
        $template->purpose = $billInfo->bill_destination;
        $template->sum  = $billInfo->amount;
        return $template;
    }

    public function attributeLabels():array
    {
        return [
            'st' => 'st',
            'snt_name' => 'Название СНТ',
            'personalAcc' => 'Расчётный счёт',
            'bankName' => 'Название банка',
            'bik' => 'БИК',
            'correspAcc' => 'Корр.счет',
            'payerInn' => 'ИНН',
            'kpp' => 'КПП',
        ];
    }


    /**
     * @return array
     */
    public function rules():array
    {
        return [
            // name, email, subject и body атрибуты обязательны
            [['st', 'snt_name', 'personalAcc', 'bankName', 'bik', 'correspAcc', 'payerInn', 'kpp'], 'required'],
        ];
    }

    public static function getBankInfo()
    {
        // прочитаю настройки из файла
        $file = dirname(Yii::getAlias('@webroot') . './/') . '/settings/bank_settings';
        if (!is_file($file)) {
            // создаю файл
            file_put_contents($file, '');
        }
        $content = file_get_contents($file);
        $settingsArray = mb_split("\n", $content);
        if (count($settingsArray) !== 9) {
            // файл неправильный, заполню данные значениями по умолчанию
            $invoice = new self();
            $invoice->st = 'не заполнено';
            $invoice->snt_name = 'не заполнено';
            $invoice->personalAcc = 'не заполнено';
            $invoice->bankName = 'не заполнено';
            $invoice->bik = 'не заполнено';
            $invoice->correspAcc = 'не заполнено';
            $invoice->payerInn = 'не заполнено';
            $invoice->kpp = 'не заполнено';
            return $invoice;
        }

        $invoice = new self();
        $invoice->st = $settingsArray[0];
        $invoice->snt_name = $settingsArray[1];
        $invoice->personalAcc = $settingsArray[2];
        $invoice->bankName = $settingsArray[3];
        $invoice->bik = $settingsArray[4];
        $invoice->correspAcc = $settingsArray[5];
        $invoice->payerInn = $settingsArray[6];
        $invoice->kpp = $settingsArray[7];
        return $invoice;
    }

    public function saveSettings()
    {
        $file = dirname(Yii::getAlias('@webroot') . './/') . '/settings/bank_settings';
        file_put_contents($file, "{$this->st}\n{$this->snt_name}\n{$this->personalAcc}\n{$this->bankName}\n{$this->bik}\n{$this->correspAcc}\n{$this->payerInn}\n{$this->kpp}\n");
        Yii::$app->session->addFlash('success', "Данные обновлены.");
        return ['status' => 1];
    }

    public function drawQR()
    {
        $type = Bill::getType($this->category);
        $data = "$this->st|Name=$this->snt_name|PersonalAcc=$this->personalAcc|BankName=$this->bankName|BIC=$this->bik|CorrespAcc=$this->correspAcc|PayeeINN=$this->payerInn|KPP=$this->kpp|persAcc={$this->persAcc}|LASTNAME=$this->lastname|payerAddress=$this->payerAddress|category=$type|Purpose=$this->purpose|Sum=$this->sum";
        return (new QRCode)->render($data);
    }
}