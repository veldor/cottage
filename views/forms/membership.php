<?php

use app\models\CashHandler;
use app\models\database\Accruals_membership;
use app\models\MembershipHandler;
use yii\helpers\Url;
use yii\web\View;


/* @var $this View */
/* @var $data Accruals_membership[] */


if (!empty($data) && count($data) > 0) {
    ?>
    <table class="table table-striped table-condensed table-hover">
        <thead>
        <tr>
            <th>Квартал</th>
            <th>С участка</th>
            <th>С сотки</th>
            <th>Площадь</th>
            <th>Общая стоимость</th>
            <th>Оплата</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($data as $item) {
            $payed = MembershipHandler::getPeriodPaysAmount($item->cottage_number, $item->quarter);
            $accrual = $item->getAccrual();
            if ($payed === $accrual) {
                $payed = '<b class="text-success">Оплачено полностью</b>';
            } elseif ($payed === 0 && $accrual !== 0) {
                $payed = '<b class="text-danger">Не оплачено</b>';
            } else if ($payed > 0) {
                $payed = '<b class="text-info">Оплачено частично</b>';
            } else {
                $payed = '<b class="text-danger">Не оплачено</b>';
            }
            echo "<tr>
                                    <td>{$item->quarter}</td>
                                    <td>{$item->fixed_part}</td>
                                    <td>{$item->square_part}</td>
                                    <td>{$item->counted_square}</td>
                                    <td>" . \app\models\Calculator::countFixedFloat($item->fixed_part, $item->square_part, $item->counted_square) . "</td>
                                    <td>{$payed}</td>
                                    <td><button class='btn btn-default activator' data-action='" . Url::toRoute(['forms/membership-individual', 'accrualId' => $item->id]) . "'><span class='text-danger'>Назначить индивидуально</span></button></td>
                            </tr>";
        }
        ?>
        </tbody>
    </table>
    <script>handleAjaxActivators()</script>
    <?php
}