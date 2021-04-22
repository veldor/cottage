<?php


namespace app\fixix;


use app\models\Cottage;
use app\models\database\Accruals_membership;
use app\models\TimeHandler;

class TarriffFixic
{

    public static function checkCottagesForAccruals()
    {
        // get all cottages
        $cottages = Cottage::getRegisteredList();
        $currentQuarter = TimeHandler::getCurrentQuarter();
        foreach ($cottages as $cottage) {
            $accrual = Accruals_membership::getItem($cottage, $currentQuarter);
            if(empty($accrual) && $cottage->getCottageNumber() > 0){
                echo "{$cottage->getCottageNumber()} is not filled";
                die;
            }
            if($cottage->haveAdditional()){
                $add = Cottage::getCottage($cottage->getCottageNumber(), true);
                $accrual = Accruals_membership::getItem($add, $currentQuarter);
                if(empty($accrual)){
                    echo "{$add->getCottageNumber()} is not filled";
                    die;
                }
            }
        }
    }
}