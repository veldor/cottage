<?php


namespace app\models;


use app\models\small_classes\RegistryInfo;
use app\models\utils\DbTransaction;
use Exception;
use FontLib\Table\Table;
use Yii;
use yii\base\Model;

class Registry extends Model
{

    public $file;

    const SCENARIO_PARSE = 'parse';
    /**
     * @var Table_bank_invoices[]
     */
    public $unhandled;

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
    public function handleRegistry(): ?array
    {
        if ($this->validate()) {
            $billsList = null;
            $date = null;
            $newBillsCount = 0;
            $oldBillsCount = 0;

            $transaction = new DbTransaction();

            $newBills = null;
            foreach ($this->file as $item) {
                $file = $item->tempName;
                $handle = fopen($file, 'rb');
                while (($buffer = fgets($handle, 4096)) !== false) {
                    try {
                        $billInfo = $this->handleBill($buffer);
                    } catch (ExceptionWithStatus $e) {
                        Yii::$app->session->addFlash('danger', $e->getMessage());
                        $transaction->rollbackTransaction();
                        return null;
                    }
                    if ($billInfo !== null) {
                        $billsList[] = $billInfo;
                    } else {
                        // проверю на строчку с датой рееста
                        $registerDate = $this->getRegisterDate($buffer);
                        if (!empty($registerDate)) {
                            $date = $registerDate;
                            echo $date;
                        }
                    }
                }
                if (!feof($handle)) {
                    throw new ExceptionWithStatus('Ошибка чтения файла',);
                }
                fclose($handle);
                if (!empty($billsList)) {
                    /** @var RegistryInfo $bill */
                    foreach ($billsList as $bill) {
                        // если в базе данных платежей от сбербанка ещё нет данного- внесу.
                        if (!Table_bank_invoices::findOne(['bank_operation_id' => $bill->sberBillId])) {
                            $invoice = new Table_bank_invoices();
                            $invoice->bank_operation_id = $bill->sberBillId;
                            $invoice->pay_date = $date;
                            $invoice->pay_time = $bill->payTime;
                            $invoice->filial_number = $bill->departmentNumber;
                            $invoice->handler_number = $bill->handlerNumber;
                            $invoice->account_number = str_replace('№', '', $bill->personalAcc);
                            $invoice->fio = $bill->fio;
                            $invoice->address = $bill->address;
                            $invoice->payment_period = $bill->period;
                            $invoice->payment_summ = $bill->operationSumm;
                            $invoice->transaction_summ = $bill->transactionSumm;
                            $invoice->commission_summ = $bill->commissionSumm;
                            $invoice->real_pay_date = $bill->payDate;
                            $invoice->save();
                            $newBills[] = $bill;
                            $newBillsCount++;
                        } else {
                            $oldBillsCount++;
                        }
                    }
                }
            }
            $transaction->commitTransaction();
            return ['billsList' => $newBills, 'newBillsCount' => $newBillsCount, 'oldBillsCount' => $oldBillsCount];
        }
        throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }

    /**
     * @param $string
     * @return RegistryInfo|null
     * @throws ExceptionWithStatus
     */
    private function handleBill($string): ?RegistryInfo
    {
        if (!empty($string)) {
            $string = GrammarHandler::convertToUTF($string);
            $details = explode(';', $string);
            // тут нужно проверить данные. Тут могут быть два варианта- или это строка с платежом
            // или строка с отчётными данными. Строка с платежом состоит из 12 параметров, с отчётом- из 6.
            // Если найдена другая строка- там ошибка данных, выведу ошибку с ней
            if (count($details) === 12) {
                // заполню объект данными о платеже
                $registryInfo = new RegistryInfo();
                $registryInfo->payDate = $details[0];
                $registryInfo->payTime = $details[1];
                $registryInfo->departmentNumber = $details[2];
                $registryInfo->handlerNumber = $details[3];
                $registryInfo->sberBillId = $details[4];
                $registryInfo->personalAcc = $details[5];
                $registryInfo->fio = $details[6];
                $registryInfo->address = $details[7];
                $registryInfo->operationSumm = $details[8];
                $registryInfo->transactionSumm = $details[9];
                $registryInfo->commissionSumm = $details[10];
                $registryInfo->period = $details[11];
                if ($registryInfo->validate()) {
                    return $registryInfo;
                }
                throw new ExceptionWithStatus('Ошибка обработки платежа', 3);
            }
            if (count($details) === 6) {
                // тут проверю введённую информацию
            } else {
                throw new ExceptionWithStatus("Значение<br/>$string<br/>не распознано. Проверьте правильность введённых данных!");
            }
        }
        return null;
    }

    private function getRegisterDate($string)
    {
        if (!empty($string)) {
            $details = explode(';', $string);
            if (count($details) === 6) {
                return $details[5];
            }
        }
        return null;
    }

    public function getUnhandled()
    {
        $this->unhandled = Table_bank_invoices::find()->where(['bounded_bill_id' => null])->orderBy('pay_date')->all();
    }

    public static function getBillId($string)
    {
        // номер должен быть последним в строке, разделённым пробелом
        $substr = explode(' ', $string);
        $num = $substr[count($substr) - 1];
        if ((int)$num > 0) {
            return $num;
        }

        $firstChar = mb_substr($num, 0, 1);
        if ($firstChar === '№') {
            $num = mb_substr($num, 1);
            if ((int)$num > 0) {
                return $num;
            }
        }
        return null;
    }

}