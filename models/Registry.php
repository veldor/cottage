<?php


namespace app\models;


use app\models\small_classes\RegistryInfo;
use FontLib\Table\Table;
use yii\base\Model;

class Registry extends Model
{

    public $file;

    const SCENARIO_PARSE = 'parse';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_PARSE => ['file'],
        ];
    }

    public function rules(): array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'txt', 'maxFiles' => 1000],
            [['file'], 'required', 'on' => self::SCENARIO_PARSE],
        ];
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function handleRegistry()
    {
        if ($this->validate()) {

            $billsList = null;
            $newBillsCount = 0;
            $oldBillsCount = 0;

            foreach ($this->file as $item) {
                $file = $item->tempName;
                $handle = fopen($file, 'r');
                while (($buffer = fgets($handle, 4096)) !== false) {
                    $billInfo = $this->handleBill($buffer);
                    if(!empty($billInfo)){
                        $billsList[] = $billInfo;
                    }
                }
                if (!feof($handle)) {
                    throw new ExceptionWithStatus('Ошибка чтения файла',);
                }
                fclose($handle);
            }

            $newBills = null;

            if(!empty($billsList)){
                /** @var RegistryInfo $bill */
                foreach ($billsList as $bill) {
                    // если в базе данных платежей от сбербанка ещё нет данного- внесу.
                    if(!Table_bank_invoices::findOne(['bank_operation_id' => $bill->sberBillId])){
                        $invoice = new Table_bank_invoices();
                        $invoice->bank_operation_id = $bill->sberBillId;
                        $invoice->pay_date = $bill->payDate;
                        $invoice->pay_time = $bill->payTime;
                        $invoice->filial_number = $bill->departmentNumber;
                        $invoice->handler_number = $bill->handlerNumber;
                        $invoice->account_number = $bill->personalAcc;
                        $invoice->fio = $bill->fio;
                        $invoice->address = $bill->address;
                        $invoice->payment_period = $bill->period;
                        $invoice->payment_summ = $bill->operationSumm;
                        $invoice->transaction_summ = $bill->transactionSumm;
                        $invoice->commission_summ = $bill->commissionSumm;
                        $invoice->save();
                        $newBills[] = $bill;
                        $newBillsCount ++;
                    }
                    else{
                        $oldBillsCount++;
                    }
                }
                return ['billsList' => $newBills, 'newBillsCount' => $newBillsCount, 'oldBillsCount' => $oldBillsCount];
            }
            throw new ExceptionWithStatus('Не удалось прочитать содержимое файла', 3);
        }
        throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }

    private function handleBill($string)
    {
        if (!empty($string)) {
            $details = explode(';', $string);
            if (count($details) === 12) {
                // заполню объект данными о платеже

                $registryInfo = new RegistryInfo();

                $registryInfo->payDate = $details[0];
                $registryInfo->payTime = $details[1];
                $registryInfo->departmentNumber = $details[2];
                $registryInfo->handlerNumber = $details[3];
                $registryInfo->sberBillId = $details[4];
                $registryInfo->personalAcc = $details[5];
                $registryInfo->fio = iconv('CP1251', 'UTF-8', $details[6]);
                $registryInfo->address = iconv('CP1251', 'UTF-8', $details[7]);
                $registryInfo->operationSumm = $details[8];
                $registryInfo->transactionSumm = $details[9];
                $registryInfo->commissionSumm = $details[10];
                $registryInfo->period = iconv('CP1251', 'UTF-8', $details[11]);
                if($registryInfo->validate()){
                    return $registryInfo;
                }
                else{
                    throw new ExceptionWithStatus("Ошибка обработки платежа", 3);
                }

            }
        }
        return null;
    }

    private function getBillId($string)
    {
        // номер должен быть последним в строке, разделённым пробелом
        $substrs = explode(' ', $string);
        $num = $substrs[count($substrs) - 1];
        if ((int)$num > 0) {
            return $num;
        } else {
            $firstChar = mb_substr($num, 0, 1);
            if ($firstChar === '№') {
                $num = mb_substr($num, 1);
                if ((int)$num > 0) {
                    return $num;
                }
            }
        }
        throw new ExceptionWithStatus('Не распознан номер счёта!', 4);
    }

}