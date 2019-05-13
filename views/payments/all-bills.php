<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.04.2019
 * Time: 15:23
 */

use app\models\CashHandler;
use app\models\Table_payment_bills;

/* @var $this \yii\web\View */
/* @var $info array */
echo '<table class="table table-bordered table-striped table-condensed table-hover"><thead><tr><th>№ участка</th><th>№ счёта</th><th>Сумма</th><th class="text-center"><span class="glyphicon glyphicon-envelope" title="Статус уведомления"></span></th></tr></thead><tbody>';
if (!empty($info['bills'])) {
    /** @var Table_payment_bills $bill */
    foreach ($info['bills'] as $bill) {
        $mesageSendedBlock = $bill->isMessageSend ? '<span class="glyphicon glyphicon-ok text-success" title="Уведомление отправлено"></span>' : "<span class='glyphicon glyphicon-envelope btn btn-info unsended' data-bill-id='{$bill->id}' data-double='0' title='Отправить уведомление'></span>";
        echo "<tr class='tr-selected' data-bill-id='{$bill->id}' data-double='0'><td>{$bill->cottageNumber}</td><td>{$bill->id}</td><td>" . CashHandler::toSmoothRubles($bill->totalSumm) . "</td><td class='text-center'>" . $mesageSendedBlock . "</td></tr>";
    }
}
if (!empty($info['doubleBills'])) {
    /** @var Table_payment_bills $bill */
    foreach ($info['doubleBills'] as $bill) {
        $mesageSendedBlock = $bill->isMessageSend ? '<span class="glyphicon glyphicon-ok text-success" title="Уведомление отправлено"></span>' : "<span class='glyphicon glyphicon-envelope btn btn-info unsended' data-bill-id='{$bill->id}' data-double='0' title='Отправить уведомление'></span>";
        echo "<tr class='tr-selected' data-bill-id='{$bill->id}' data-double='1'><td>{$bill->cottageNumber}-a</td><td>{$bill->id}-a</td><td>" . CashHandler::toSmoothRubles($bill->totalSumm) . "</td><td class='text-center'>" . $mesageSendedBlock . "</td></tr>";
    }
}
echo '</tbody></table>';

