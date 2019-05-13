<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.04.2019
 * Time: 12:06
 */

namespace app\models;


class Cottages
{
    public static function getCottage($cottageNumber, $additional = false){
        if($additional){
            return AdditionalCottage::getCottage($cottageNumber);
        }
        else{
            return Cottage::getCottageInfo($cottageNumber);
        }
    }
}