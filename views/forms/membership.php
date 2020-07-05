<?php

use app\models\CashHandler;
use app\models\database\Accruals_membership;
use yii\helpers\Url;
use yii\web\View;



/* @var $this View */
/* @var $data Accruals_membership[] */



if(!empty($data) && count($data) > 0){
    ?>
    <table class="table table-striped table-condensed table-hover">
        <thead>
        <tr>
            <th>Квартал</th>
            <th>С участка</th>
            <th>С сотки</th>
            <th>Площадь</th>
            <th>Общая стоимость</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data as $item) {
            echo "<tr>
                                    <td>{$item->quarter}</td>
                                    <td>{$item->fixed_part}</td>
                                    <td>{$item->square_part}</td>
                                    <td>{$item->counted_square}</td>
                                    <td>" . \app\models\Calculator::countFixedFloat($item->fixed_part, $item->square_part, $item->counted_square) . "</td>
                                    <td><button class='btn btn-default activator' data-action='" . Url::toRoute(['forms/membership-individual', 'accrualId' => $item->id]) . "'><span class='text-danger'>Назначить индивидуально</span></button></td>
                            </tr>";
        }
        ?>
        </tbody>
    </table>
    <script>handleAjaxActivators()</script>
    <?php
}