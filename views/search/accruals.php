<?php

use app\models\CashHandler;
use yii\web\View;

/* @var $this View */
/* @var $data array */

?>

<table class="table table-hover table-condensed table-striped">
    <tr>
        <th>Номер участка</th>
        <th>Электроэнергия</th>
        <th>Членские взносы</th>
        <th>Целевые взносы</th>
    </tr>

<?php


if($data !== null){
    $counter = 1;
    echo "<b class='col-sm-12'>Электроэнергии начислено за год: <b class='text-success'>" . CashHandler::toSmoothRubles($data['power']) . '</b><br/>Членских взносов начислено за год: <b class="text-success">' . CashHandler::toSmoothRubles($data['membership']) . '</b><br/>Целевых взносов начислено за год: <b class="text-success">' . CashHandler::toSmoothRubles($data['target']) . '</b><br/>Всего начислено за год: <b class="text-success">' . CashHandler::toSmoothRubles($data['wholeAccrual']) . '</b></div>';
    foreach ($data['months'] as $month=>$accruals) {
        echo "<tr>
                <td colspan='5' class='text-center new-element cursor-pointer'  data-toggle='collapse' data-target='#body_{$counter}'>$month</td>
            </tr>
            <tbody class='collapse' id='body_{$counter}'>
            <tr>
                <td class='text-center'>Всего</td>
                <td><b class='text-danger'>" . CashHandler::toSmoothRubles($accruals['totalPower']) . "</b></td>
                <td><b class='text-danger'>" . CashHandler::toSmoothRubles($accruals['totalMembership']) . "</b></td>
                <td><b class='text-danger'>" . CashHandler::toSmoothRubles($accruals['totalTarget']) . '</b></td>
            </tr>
            ';
        foreach ($accruals['cottages'] as $cottage => $accrual) {
            echo "<tr>
                <td class='text-center'>$cottage</td>
                <td>" . ($accrual['power'] > 0 ? '<b class="text-success">' . CashHandler::toSmoothRubles($accrual['power']) . '</b>' : '--') . '</td>
                <td>' . ($accrual['membership'] > 0 ? '<b class="text-success">' . CashHandler::toSmoothRubles($accrual['membership']) . '</b>' : '--') . '</td>
                <td>' . ($accrual['target'] > 0 ? '<b class="text-success">' . CashHandler::toSmoothRubles($accrual['target']) . '</b>' : '--') . '</td>
            </tr>
            ';
        }
        echo '</tbody>';
        $counter++;
    }
}
?>

</table>

