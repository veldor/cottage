<?php

use app\models\CashHandler;
use app\models\Table_transactions;
use yii\web\View;



/* @var $this View */
/* @var $transactions Table_transactions[] */

if($transactions !== null){
    echo '<table class="table table-striped table-hover"><thead><tr><th>Номер счёта</th><th>Сумма</th><th>Назначение</th></tr></thead>';
    /** @var Table_transactions $transaction */
    foreach ($transactions as $transaction) {
        echo "<tr><td><a class='activator' data-action='/get-info/bill/{$transaction->billId}'>{$transaction->billId}</a></td><td>" . CashHandler::toShortSmoothRubles($transaction->transactionSumm) . "</td><td>{$transaction->transactionReason}</td></tr>";
    }
    echo '</table>';
}
