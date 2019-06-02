<?php


use app\models\CashHandler;
use app\models\small_classes\TransactionComparison;
use yii\web\View;


/* @var $this View */
/* @var $info TransactionComparison */

$billSumm = CashHandler::toRubles($info->billSumm);
$transactionSumm = CashHandler::toRubles($info->transactionSumm);

$difference = CashHandler::toSmoothRubles($transactionSumm - $billSumm);
?>

<table class="table table-condensed table-hover">
    <thead>
    <tr>
        <th>Транзакция</th>
        <th>Счёт</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            Сумма платежа: <?= $transactionSumm ?>
        </td>
        <td>
            Сумма платежа: <?= $billSumm ?>
        </td>
    </tr>
    <tr>
        <td>
            ФИО плательщика: <?= $info->transactionFio ?>
        </td>
        <td>
            ФИО плательщика: <?= $info->billFio ?>
        </td>
    </tr>
    <tr>
        <td>
            Номер участка: <?= $info->transactionCottageNumber ?>
        </td>
        <td>
            Номер участка: <?= $info->billCottageNumber ?>
        </td>
    </tr>
    </tbody>
</table>
<h2 class="text-center text-success">Платёж будет оплачен полностью и закрыт</h2>
<?php
if($difference > 0){
    echo "<h3>{$difference} будет зачислено на депозит участка</h3>";
}
?>
<button class="btn btn-success">Подтвердить слияние</button>
