<?php


namespace app\models;


use yii\base\Model;

class Checks extends Model
{
    /**
     * @return bool
     */
    public static function checkIndividuals(){
        // найду все участки с индивидуальными тарифами
        $cottages = PersonalTariff::getCottagesWithIndividual();
        $targetTariffs = TargetHandler::getCurrentRates();
        if(!empty($cottages)){
            foreach ($cottages as $cottage){
                if(self::checkTariffs($cottage, $targetTariffs)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param $targetTariffs
     * @return bool
     */
    private static function checkTariffs($cottage, $targetTariffs){
        $isMain = Cottage::isMain($cottage);
        // проверю целевые взносы.
        // если участок дополнительный- проверю, оплачиваются ли с него целевые
        if($isMain || ($cottage->isTarget)){
            $tariffs = PersonalTariff::getTargetRates($cottage);
            if(count($tariffs) != count($targetTariffs)){
                return true;
            }
        }
        return false;
    }
}