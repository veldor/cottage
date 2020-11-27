<?php


use app\models\CashHandler;
use app\models\small_classes\TransactionComparison;
use app\models\TimeHandler;
use app\models\utils\BillContent;
use yii\web\View;


/* @var $this View */
/* @var $info TransactionComparison */
?>

<?php
$billSumm = CashHandler::toRubles($info->billSumm);
$transactionSumm = CashHandler::toRubles($info->transactionSumm);
$billContentInfo = new BillContent($info->bill);
$billContentText = $billContentInfo->getTextContent();
// тут придётся проверять целостность оплаты. Посчитаю, сколько осталось доплатить, чтобы счёт был оплачен

$leftToPay = $billContentInfo->getRequiredSum();

echo "<h3 class='text-center'>Осталось оплатить по счёту: <b class='text-success'>{$leftToPay}</b></h3>";

?>
<div class="row">
    <div class="col-sm-12">
        <table class="table table-condensed table-hover">
            <thead>
            <tr>
                <th>Транзакция №<?= $info->transactionId ?></th>
                <th>Счёт №<?= $info->billId ?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Поступило: <b class="text-success"><?= $transactionSumm ?></b>
                </td>
                <td>
                    К оплате: <b class="text-success"><?= $billSumm ?></b>
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
        <h3 class="text-center">Дата платежа: <b class="text-success"><?= !empty($info->realPayDate) ? $info->realPayDate : ' не определена'?></b></h3>
        <h3 class="text-center">Дата поступления на счёт: <b class="text-success"><?= $info->payDate?></b></h3>
        <h3 class="text-center">Состав счёта</h3>
        <table class="table table-condensed">
            <tr>
                <th>Тип</th>
                <th>Период</th>
                <th>К оплате</th>
                <th>Оплачивается в счёте</th>
                <th>Оплачено в счёте</th>
                <th>Оплачено вне счёта</th>
                <th>Осталось оплатить</th>
            </tr>
            <?= $billContentText ?>
        </table>
    </div>
    <?php

    if ($transactionSumm >= $leftToPay) {
        $difference = CashHandler::toRubles($transactionSumm - $leftToPay);
        ?>
        <h2 class="text-center text-success">Платёж будет оплачен полностью и закрыт</h2>
    <?php
    if ($difference > 0) {
        echo "<h3 class='text-info text-center'>" . CashHandler::toSmoothRubles($difference) . " будет зачислено на депозит участка</h3>";
    }

    ?>
        <div class="row">
            <div class="col-sm-12 margened">
                <div class="col-sm-6"><label for="sendConfirmation">Отправить уведомление о получении
                        платежа</label></div>
                <div class="col-sm-1"><input class="form-control" type="checkbox" id="sendConfirmation" checked></div>
            </div>
            <div class="col-sm-12">
                <button id="submitComparisonButton" class="btn btn-success" data-bill-id="<?= $info->billId ?>"
                        data-transaction-id="<?= $info->transactionId ?>">Подтвердить слияние
                </button>
            </div>
        </div>
        <script>
            activator = $('button#submitComparisonButton');
            activator.on('click.send', function () {
                let url = "/chain/confirm";
                let attributes = {
                    'ComparisonHandler[billId]': $(this).attr('data-bill-id'),
                    'ComparisonHandler[transactionId]': $(this).attr('data-transaction-id'),
                    'ComparisonHandler[sendConfirmation]': $('input#sendConfirmation').prop('checked'),
                };
                sendAjax('post', url, simpleAnswerHandler, attributes);
            });

            setTimeout(function () {
                $('#submitComparisonButton').focus();
                console.log('focus button');
            }, 500);
        </script>
    <?php
    }
    else{
    ?>
        <h3 class="text-center text-success">Частичная оплата счёта,нужно разнести платёж по категориям</h3>
        <div class="col-sm-12">
            <button id="submitComparisonButton" class="btn btn-success" data-bill-id="<?= $info->billId ?>"
                    data-transaction-id="<?= $info->transactionId ?>">Распределить платёж
            </button>
        </div>
        <script>
            function sendPartial() {
                let button = $('#submitComparisonButton');
                button.on('click.getForm', function () {
                    let modal = $('.modal');
                    modal.modal('hide');
                    modal.on('hidden.bs.modal', function (){
                        let url = 'get-form/pay/' + <?=$info->billId?> + '/' + <?=$info->transactionId?>;
                        sendAjax('get', url, simpleModalHandler);
                    })

                });
            }

            sendPartial();

            setTimeout(function () {
                $('#submitComparisonButton').focus();
                console.log('focus button');
            }, 500);
        </script>
        <?php
    }
    ?>


