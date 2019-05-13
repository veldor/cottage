<?php
namespace app\widgets;

//use Yii;
use app\models\CashHandler;
use app\models\Table_cottages;
use app\models\TimeHandler;
use yii\base\Widget;
use yii\helpers\Html;

class CottagesShowWidget extends Widget{

    public $cottages;
    public $content = '';

    public function init(){
        $index = 1;
        $max = 180;
        $nowQuarter = TimeHandler::getCurrentQuarter();
        /** @var Table_cottages $cottage */
        foreach($this->cottages as $cottage){
            while($cottage->cottageNumber !== $index){
                $this->content .= "<div class='col-md-1 col-sm-2 col-xs-3 text-center margened'><button class='btn empty cottage-button' data-index='$index' data-toggle='tooltip' data-placement='top' title='Регистрация участка № $index'>$index</button></div>";
                $index ++;
            }
            $additional = '';
            if($cottage->haveAdditional){
                $additional = "<span class='glyphicon glyphicon-plus'></span>";
            }
            // подсчитаю долги по участку
            if($cottage->targetDebt > 0 || $cottage->powerDebt > 0 || $cottage->singleDebt > 0 || $cottage->membershipPayFor < $nowQuarter){
                $content = '';
                $smoothSumm = null;
                if($cottage->targetDebt > 0){
                    $smoothSumm = CashHandler::toSmoothRubles($cottage->targetDebt);
                    $content .= "<p>Целевые: <b class=\"text-danger\">{$smoothSumm}</b></p>";
                }
                if($cottage->powerDebt > 0){
                    $smoothSumm = CashHandler::toSmoothRubles($cottage->powerDebt);
                    $content .= "<p>Электричество: <b class=\"text-danger\">{$smoothSumm}</b></p>";
                }
                if($cottage->singleDebt > 0){
                    $smoothSumm = CashHandler::toSmoothRubles($cottage->singleDebt);
                    $content .= "<p>Разовые: <b class=\"text-danger\">{$smoothSumm}</b></p>";
                }
                if($cottage->membershipPayFor < $nowQuarter){
                    $fullQuarter = TimeHandler::getFullFromShortQuarter(TimeHandler::getNextQuarter($cottage->membershipPayFor));

                    $content .= "<p>Членские c: <b class=\"text-danger\">{$fullQuarter}</b></p>";
                }
                if(!empty($cottage->deposit)){
                    $deposit = CashHandler::toSmoothRubles($cottage->deposit);
                }
                else{
                    $cottage->deposit = 0;
                    $cottage->save();
                    $deposit = CashHandler::toSmoothRubles($cottage->deposit);
                }
                $content .= "<p>Депозит участка: {$deposit}</p>";
                $this->content .= "<div class='col-md-1 col-sm-2 col-xs-3 text-center margened'><a href='/show-cottage/$cottage->cottageNumber' class='btn btn-danger popovered cottage-button' data-toggle='popover' data-placement='top' data-title='Имеются задолженности' data-content='{$content}'>$cottage->cottageNumber {$additional}</a></div>";
            }
            else{
                $this->content .= "<div class='col-md-1 col-sm-2 col-xs-3 text-center margened'><a href='/show-cottage/$cottage->cottageNumber' class='btn btn-success cottage-button'>$cottage->cottageNumber {$additional}</a></div>";
            }
            $index ++;
        }
        while($index <= $max){
            $this->content .= "<div class='col-md-1 col-sm-2 col-xs-3 text-center margened'><button class='btn empty cottage-button' data-index='$index' data-toggle='tooltip' data-placement='top' title='Регистрация участка № $index'>$index</button></div>";
            $index ++;
        }
    }
    public function run(){
        return Html::decode($this->content);
    }
}