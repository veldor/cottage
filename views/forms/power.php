<?php

use app\models\CashHandler;
use app\models\Table_additional_power_months;
use yii\helpers\Url;
use yii\web\View;



/* @var $this View */
/* @var $powerData Table_additional_power_months[] */

if(!empty($powerData) && count($powerData) > 0){
?>
    <table class="table table-striped table-condensed table-hover">
        <thead>
        <tr>
            <th>Месяц</th>
            <th>Нач.</th>
            <th>Фин.</th>
            <th>Общая стоимость</th>
            <th>Детали</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($powerData as $item) {
            $amount = '';
            if($item->totalPay > 0){
                $amount = '<b class="text-danger">' . CashHandler::toSmoothRubles($item->totalPay) . '</b>';
            }
            echo "<tr>
                                    <td>{$item->month}</td>
                                    <td>{$item->oldPowerData}</td>
                                    <td>{$item->newPowerData}</td>
                                    <td>$amount</td>
                                    <td>Детали</td>
                                    <td><button class='btn btn-default activator' data-action='" . Url::toRoute(['forms/power-individual', 'monthId' => $item->id]) . "'><span class='text-danger'>Назначить индивидуально</span></button></td>
                            </tr>";
        }
        ?>
        </tbody>
    </table>
    <script>handleAjaxActivators()</script>
<?php
}
