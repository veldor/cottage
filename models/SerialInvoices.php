<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.04.2019
 * Time: 18:22
 */

namespace app\models;


use app\models\small_classes\SerialCottageInfo;
use yii\base\Model;

class SerialInvoices extends Model
{
    public $autofill;

    const SCENARIO_FILL = 'fill';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_FILL => ['autofill'],
        ];
    }

    public static $month;
    public static $quarter;

    public static function getCottagesInfo(){
        // найду все основные участки
        $cottages = Cottage::getRegister();
        // получу текущий квартал и предыдущий месяц
        self::$quarter = TimeHandler::getCurrentQuarter();
        self::$month = TimeHandler::getPreviousShortMonth();
        $answer = [];
         if(!empty($cottages)){
             /** @var Table_cottages $cottage */
             foreach ($cottages as $cottage) {
                 $answer[] = self::getCottageInfo($cottage);
                 // проверю, есть ли дополнительный участок
                 if($cottage->haveAdditional){
                     // получу информацию о дополнительном участке
                     /** @var Table_additional_cottages $additionalInfo */
                     $additionalInfo = Cottage::getCottageInfo($cottage->cottageNumber, true);
                     if($additionalInfo->hasDifferentOwner){
                         $answer[] = self::getCottageInfo($additionalInfo);
                     }
                 }
             }
         }
         return $answer;
    }

    /**
     * @param $cottage Table_additional_cottages|Table_cottages
     * @return SerialCottageInfo
     */
    private static function getCottageInfo($cottage)
    {
        $answer = new SerialCottageInfo();
        if(Cottage::isMain($cottage)){
            $answer->cottageNumber = $cottage->cottageNumber;
        }
        else{
            $answer->cottageNumber = $cottage->masterId;
            $answer->isDouble = true;
        }
        $answer->hasMail = Cottage::hasMail($cottage);
        // получу необходимые данные об участке
        // проверю, есть ли вообще задолженности
        if($cottage->powerDebt > 0 || $cottage->targetDebt > 0 || $cottage->singleDebt > 0 || ( !empty($cottage->membershipPayFor) && $cottage->membershipPayFor < self::$quarter)){
            $answer->haveDebt = true;
        }
        $powerInfo = PowerHandler::getLastFilled($cottage);
        // проверю, заполнены ли показания счётчика за предыдущий месяц
        if(!empty($powerInfo) && $powerInfo->month < self::$month){
            $answer->isUnfilledPower = true;
        }
        // проверю, есть ли незавершённые счета
        $answer->unpayedBill = Pay::getUnpayedBill($cottage);
        return $answer;
    }

    public function makeInvoices()
    {
        if(!empty($this->autofill)){
            $bills = [];
            foreach ($this->autofill as $key => $value) {
                $cottageInfo = Cottage::getLiteralInfo($key);
                $bills[] = ComplexPayment::makeWholeBill($cottageInfo);
            }
            return $bills;
        }
        return 0;
    }
}