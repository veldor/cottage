<?php


use app\models\CashHandler;
use app\models\small_classes\TransactionComparison;
use yii\web\View;


/* @var $this View */
/* @var $info TransactionComparison */
?>

<?php
$billSumm = CashHandler::toRubles($info->billSumm);
$transactionSumm = CashHandler::toRubles($info->transactionSumm);

if($transactionSumm >= $billSumm){
    $difference = CashHandler::toRubles($transactionSumm - $billSumm);
    ?>
<table class="table table-condensed table-hover">
    <thead>
    <tr>
        <th>Транзакция №<?= $info->transactionId?></th>
<th>Счёт №<?= $info->billId?></th>
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
    echo "<h3 class='text-info text-center'>" . CashHandler::toSmoothRubles($difference) . " будет зачислено на депозит участка</h3>";
}

?>
<div class="row">
    <div class="col-sm-12">
        <label><input class="form-control" type="checkbox" id="sendConfirmation" checked>Отправить уведомление о получении
            платежа</label>
    </div>
    <div class="col-sm-12">
        <button id="submitComparsionButton" class="btn btn-success" data-bill-id="<?= $info->billId?>" data-transaction-id="<?= $info->transactionId?>">Подтвердить слияние</button>
    </div>
</div>
    <script>
        activator = $('button#submitComparsionButton');
        activator.on('click.send', function () {
            let url = "/chain/confirm";
            let attributes = {
                'ComparisonHandler[billId]': $(this).attr('data-bill-id'),
                'ComparisonHandler[transactionId]':$(this).attr('data-transaction-id'),
                'ComparisonHandler[sendConfirmation]':$('input#sendConfirmation').prop('checked'),
            };
            sendAjax('post', url, simpleAnswerHandler, attributes);
        })
    </script>
<?php
}
else{
    ?>
    <div class="row">
        <div class="col-sm-12">
    <table class="table table-condensed table-hover">
        <thead>
        <tr>
            <th>Транзакция №<?= $info->transactionId?></th>
            <th>Счёт №<?= $info->billId?></th>
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
        </div>
    <h2 class="text-center text-success">Частичная оплата счёта,нужно разнести платёж по категориям</h2>
    <div class="col-sm-12">
        <button id="submitComparisonButton" class="btn btn-success" data-bill-id="<?= $info->billId?>" data-transaction-id="<?= $info->transactionId?>">Распределить платёж</button>
    </div>
    </div>
    <script>
        function sendPartial() {
            let button = $('#submitComparisonButton');
            button.on('click.getForm', function () {
                let modal = $('.modal');
                modal.modal('hide');
                let url = 'get-form/pay/' + <?=$info->billId?> + '/' + <?=$info->transactionId?>;
                sendAjax('get', url, simpleModalHandler);
            });
        }
        sendPartial();
    </script>
<?php
}
?>


