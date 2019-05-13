<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:43
 */

namespace app\models;

use app\priv\Info;
use chillerlan\QRCode\QRCode;
use Dompdf\Dompdf;
use yii\base\Model;

class BankDetails extends Model
{
    private $st = 'ST00012';
    public $name = Info::BANK_INFO_NAME;
    public $personalAcc = Info::BANK_INFO_PERSONAL_ACC;
    public $bankName = Info::BANK_INFO_BANK_NAME;
    public $bik = Info::BANK_INFO_BIK;
    private $correspAcc = Info::BANK_INFO_CORRESP_ACC;
    public $payerInn = Info::BANK_INFO_PAYER_INN;
    public $kpp = Info::BANK_INFO_KPP;
    // personal info
    public $purpose;
    public $lastName;
    public $summ;
    public $payerAddress;
    public $cottageNumber;

    public function drawQR()
    {
        $data = "$this->st|Name=$this->name|PersonalAcc=$this->personalAcc|BankName=$this->bankName|BIC=$this->bik|CorrespAcc=$this->correspAcc|PayeeINN=$this->payerInn|KPP=$this->kpp|LASTNAME=$this->lastName|Purpose=$this->purpose|Sum=$this->summ|PersAcc={$this->cottageNumber}";
        return (new QRCode)->render($data);
    }
}
