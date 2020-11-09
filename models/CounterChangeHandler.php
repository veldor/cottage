<?php


namespace app\models;


use app\models\interfaces\CottageInterface;
use yii\base\Model;

class CounterChangeHandler extends Model
{

    /**
     * @param CottageInterface $globalInfo Table_cottages
     * @return Table_counter_changes
     */
    public static function checkChange(CottageInterface $globalInfo):?Table_counter_changes
    {
        return Table_counter_changes::find()->where(['cottageNumber' => $globalInfo->cottageNumber, 'changeMonth' => TimeHandler::getCurrentShortMonth()])->orWhere(['cottageNumber' => $globalInfo->cottageNumber, 'changeMonth' => TimeHandler::getPreviousShortMonth()])->orderBy('change_time')->one();
    }
}