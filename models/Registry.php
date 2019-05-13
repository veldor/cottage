<?php


namespace app\models;


use app\models\small_classes\RegistryInfo;
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

    public function rules():array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'txt'],
            [['file'], 'required', 'on' => self::SCENARIO_PARSE],
            ];
    }

    /**
     * @throws ExceptionWithStatus
     */
    public function handleRegistry()
    {
        if($this->validate()){
            $info = file_get_contents($this->file->tempName);
            if(!empty($info)){
                $bills = explode("\n", $info);
                $bill = $bills[0];
                return $this->handleBill($bill);
            }
            throw new ExceptionWithStatus('Не удалось прочитать содержимое файла', 3);
        }
        throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }

    private function handleBill($string){
        if(!empty($string)){
            $details = explode(';', $string);
            if(count($details) === 12){
                $registryInfo = new RegistryInfo();
                $registryInfo->date = $details[0];
                $registryInfo->time = $details[1];
                $registryInfo->name = iconv('CP1251','UTF-8',$details[6]);
                $registryInfo->payInfo = iconv('CP1251','UTF-8',$details[7]);
                $registryInfo->billId = $this->getBillId($registryInfo->payInfo);
                $registryInfo->summ = CashHandler::toRubles($details[8]);
                return $registryInfo;
            }
        }
        return null;
    }

    private function getBillId($string){
        // номер должен быть последним в строке, разделённым пробелом
        $substrs = explode(' ', $string);
        $num = $substrs[count($substrs) - 1];
        if((int) $num > 0){
            return $num;
        }
        else{
            $firstChar = mb_substr($num, 0, 1);
            if($firstChar === '№'){
                $num = mb_substr($num, 1);
                if((int) $num > 0){
                    return $num;
                }
            }
        }
        throw new ExceptionWithStatus('Не распознан номер счёта!', 4);
    }

}