<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.09.2018
 * Time: 10:42
 */

/* @var $this \yii\web\View */
/* @var $info \app\models\Payments_power|bool */
?>
<div class="row">
    <div class="col-lg-12 text-center"><h2>Информация о счёте за <?=\app\models\TimeHandler::getFullFromShotMonth($info['info']->paymentMonth)?></h2></div>
    <div class="col-lg-6 text-center"><p>Предыдущие показания счётчика: <b class="text-success"><?=$info['info']->currentPowerData?> кВТ∙ч</b></p></div>
    <div class="col-lg-6 text-center">
        <p>Текущие показания счётчика: <b class="text-success"><?=$info['info']->newPowerData?> кВТ∙ч</b></p>
        <p>Дата регистрации показаний: <b class="text-success"><?=\app\models\TimeHandler::getDatetimeFromTimestamp($info['info']->registerDate)?></b></p>
    </div>
    <div class="col-lg-12 text-center"><h3>Информация о тарифных ставках</h3></div>
    <div class="col-lg-4 text-center">Льготный лимит: <b class="text-success"><?=$info['info']->powerLimit?> кВТ∙ч</b></div>
    <div class="col-lg-4 text-center">Льготная цена за кВТ∙ч: <b class="text-success"><?=$info['info']->powerCost?> &#8381;</b></div>
    <div class="col-lg-4 text-center">Стандартная цена за кВТ∙ч <b class="text-success"><?=$info['info']->powerOvercost?> &#8381;</b></div>
    <div class="col-lg-12 text-center"><h3>Статус оплаты</h3></div>
    <div class="col-lg-4 text-center"><p>Всего к оплате: <b class="text-success"><?=$info['info']->paymentTotal?> &#8381;</b></p></div>
    <?php
    if($info['info']->paymentConfirm == 'full_payed'){
    ?>
        <div class="col-lg-4 text-center"><p>Статус оплаты: <b class="text-success">Полностью оплачено!</b></p></div>
    <?php
    }
    elseif($info['info']->paymentConfirm == 'no_payed'){
    ?>
        <div class="col-lg-4 text-center"><p>Статус оплаты: <b class="text-danger">Не оплачено!</b></p></div>
    <?php
    }
    elseif($info['info']->paymentConfirm == 'partial_payed') {
    ?>
        <div class="col-lg-4 text-center"><p>Статус оплаты: <b class="text-danger">Частично оплачено</b></p></div>
        <div class="col-lg-4 text-center">
            <p>Оплачено: <b class="text-success"><?=$info['info']->reallyPayedSumm?> &#8381;</b></p>
            <p>Не оплачено: <b class="text-danger"><?=$info['info']->paymentTotal - $info['info']->reallyPayedSumm?> &#8381;</b></p>
        </div>
        <div class="col-lg-12 text-center"><h3>Список транзакций по счёту</h3></div>
        <div class="col-lg-12">
    <table class="table table-bordered">
        <thead>
        <tr><th>Дата оплаты</th><th>Статус платежа</th><th>Сумма платежа</th><th>Тип платежа</th></tr>
        </thead>
        <tbody>
        <?php
            foreach ($info['transactions'] as $transaction){
                $operationType = '';
                $transactionType = '';
                $transaction->paymentType == 'cash'? $operationType = 'Наличные': $operationType = 'Безналичный расчёт';
                if($transaction->paymentConfirm == 'partial')
                    $transactionType = "Частичный платёж";
                elseif($transaction->paymentConfirm == 'full')
                    $transactionType = "Полный платёж";
                else
                    $transactionType = "Завершающий платёж";
        ?>
                <tr><td><b class="text-info"><?=\app\models\TimeHandler::getDatetimeFromTimestamp($transaction->paymentDate)?></b></td><td><b class="text-primary"><?=$transactionType?></b></td><td><b class="text-success"><?=$transaction->paymentSumm?> &#8381;</b></td><td><b class="text-primary"><?=$operationType?></b></td></tr>
        <?php
        }
        ?>
            </tbody>
    </table>
        </div>
        <div class="col-lg-12 text-center"><h3>Возможности оплаты</h3></div>
        <div class="col-lg-12 text-center"><button class="btn btn-success">Оплатить полностью</button><button class="btn btn-info">Оплатить частично</button></div>
    <?php
    }
    ?>
</div>
