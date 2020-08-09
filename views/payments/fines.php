<?php

use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\Table_additional_cottages;
use app\models\Table_cottages;
use app\models\tables\Table_penalties;
use app\models\TimeHandler;
use yii\web\View;



/* @var $this View */
/* @var $info Table_penalties[] */


if(!empty($info)){
    echo "<table class='table table-striped table-condensed table-hover'><tr><th>Тип</th><th>Период</th><th>Начислено</th><th>Оплачено</th></tr>";
    foreach ($info as $item) {
        $itemInfo = FinesHandler::getFineStructure($item);
        switch ($item->pay_type){
            case 'power':
                $type = '<b class="text-danger">Электроэнергия</b>';
                break;
            case 'membership':
                $type = '<b class="text-info">Членские взносы</b>';
                break;
            case 'target':
                $type = '<b class="text-success">Целевые взносы</b>';
                break;
        }
        if($item->payed_summ === $item->summ){
            $text = 'text-success';
        }
        else{
            $text = 'text-danger';
        }
        // расчитаю количество дней, за которые начисляются пени
        try {
            $dayDifference = TimeHandler::checkDayDifference($item->payUpLimit);
            if($dayDifference === 0){
                $dayDifference = 1;
            }
        } catch (Exception $e) {
        }
        if($item->is_enabled){
            $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default ajax-activator hidden'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default ajax-activator'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
        }
        else{
            $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default ajax-activator'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default ajax-activator hidden'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
        }

        if($item->locked){
            $locked = "<a class='btn btn-default ajax-activator' href='#' data-action='/fines/unlock/{$item->id}'><span class='glyphicon glyphicon-lock text-success'></span></a>";

        }
        else{
            $locked = "<a class='btn btn-default activator' href='#' data-action='/fines/lock/{$item->id}'><span class='glyphicon glyphicon-lock text-danger'></span></a>";
        }

        echo "<tr><td>$type</td><td>{$item->period}</td><td><b class='text-info popover-active' data-html='true' data-toggle='popover' data-parent='div#myModal' data-trigger='hover' data-placement='bottom' data-content='$itemInfo'>" . CashHandler::toSmoothRubles($item->summ) . "</b></td><td><b class='$text'>" . CashHandler::toSmoothRubles($item->payed_summ) . "</b></td><td>$controlItem $locked <a class='btn btn-default activator' data-action='/fines/delete/{$item->id}'><span class='glyphicon glyphicon-trash text-danger'></span></a></td></tr>";
    }
    echo '</table>';
}
else{
    echo "<h1 class='text-center'>Просроченных задолженностей не найдено!</h1>";
}
echo '<script>' . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/js/activatorsHandler.js') . '</script>';
echo '<script>$(".popover-active").popover()</script>';

