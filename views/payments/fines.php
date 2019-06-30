<?php

use app\models\CashHandler;
use app\models\Table_additional_cottages;
use app\models\Table_cottages;
use app\models\tables\Table_penalties;
use yii\web\View;



/* @var $this View */
/* @var $info Table_additional_cottages[]|Table_cottages[]|Table_penalties[] */


if(!empty($info)){
    echo "<table class='table table-striped table-condensed table-hover'>";
    foreach ($info as $item) {
        switch ($item->pay_type){
            case 'power':
                $type = 'Электроэнергия';
                break;
            case 'membership':
                $type = 'Членские взносы';
                break;
            case 'target':
                $type = 'Целевые взносы';
                break;
        }
        if($item->is_enabled){
            $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default activator hidden'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default activator'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
        }
        else{
            $controlItem = "<a href='#' id='fines_{$item->id}_enable' data-action='/fines/enable/{$item->id}' class='btn btn-default activator'><span class='glyphicon glyphicon-plus text-success'></span></a><a href='#' id='fines_{$item->id}_disable' data-action='/fines/disable/{$item->id}' class='btn btn-default activator hidden'><span class='glyphicon glyphicon-minus text-danger'></span></a>";
        }
        echo "<tr><td>$type</td><td>{$item->period}</td><td>" . CashHandler::toSmoothRubles($item->summ) . "</td><td>$controlItem</td></tr>";
    }
    echo "</table>";
}
else{
    echo "<h1 class='text-center'>Просроченных задолженностей не найдено!</h1>";
}
echo "<script>" . file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/js/activatorsHandler.js') . "</script>";

