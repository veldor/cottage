<?php

use app\models\CashHandler;
use app\models\selections\TargetInfo;
use yii\web\View;



/* @var $this View */
/* @var $data TargetInfo[] */

$amount = 0;
$payedCounter = 0;
$total = 0;
$payed = 0;

$content = '';

if(!empty($data)){
    foreach ($data as $info) {
        $total++;
        if($info->payed > 0){
            $payedCounter++;
        }
        $amount += $info->amount;
        $payed += $info->payed;
        $content .= "
            <tr>
            <td>{$info->cottageNumber}</td>
            <td><b class='text-info'>" . CashHandler::toSmoothRubles($info->amount) . "</b></td>
            <td>" . ($info->payed > 0 ? '<b class="text-success">' . CashHandler::toSmoothRubles($info->payed) . '</b>': '<b class="text-danger">0</b>'). "</td>
            </tr>
        ";
    }
}

echo '<h2>Всего счетов: ' . $total . '</h2>';
echo '<h2>Оплачено: ' . $payedCounter . '</h2>';
echo '<h2>Начислено: ' . CashHandler::toSmoothRubles($amount) . '</h2>';
echo '<h2>Оплачено: ' . CashHandler::toSmoothRubles($payed) . '</h2>';
echo "<table class='table table-condensed table-striped table-hover'>$content</table>";
