<?php


namespace app\models;


use yii\base\Model;

class Reminder extends Model
{
    public static function requreRemind(): bool
    {
        // если сегодня последний день первого месяца квартала
        if(TimeHandler::isLastDay()){
            // получу последний проверенный квартал
            if(is_file('../priv/lastCheckedQuarter')){
                $lastCheckedQuarter = file_get_contents('../priv/lastCheckedQuarter');
            }
            else{
                $lastCheckedQuarter = TimeHandler::getPrevQuarter(TimeHandler::getCurrentQuarter());
            }
            if($lastCheckedQuarter < TimeHandler::getCurrentQuarter()){
                return true;
            }
        }
        return false;
    }

    public static function finishRemind(): void
    {
        file_put_contents('../priv/lastCheckedQuarter', TimeHandler::getCurrentQuarter());
    }
}