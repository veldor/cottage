<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.10.2018
 * Time: 12:31
 */

use app\models\TimeHandler;
use app\widgets\PaymentDoubleDetailsWidget;
/* @var $this \yii\web\View */
/* @var $info */
/* @var $power \DOMElement */
/* @var $target \DOMElement */
/* @var $item \DOMElement */
?>
<div class="row">
    <div class="col-lg-12 text-center"><h1>Информация о счёте №<?= $info['billInfo']->id ?></h1>
        <h3>Информация о плательщике</h3>
        <p>Номер дачного участка: <b class="text-success"><?= $info['cottageInfo']->masterId?>-а</b></p>
        <p>Ф.И.О. владельца дачи: <b class="text-success"><?= $info['cottageInfo']->cottageOwnerPersonals ?></b></p>
    </div>

    <div class='col-lg-12'>
        <?php
        if ($info['billInfo']->isPayed === 1) {
            $payDate = TimeHandler::getDatetimeFromTimestamp($info['billInfo']->paymentTime);
            ?>
            <h3>Статус: <b class='text-success'>Оплачен</b></h3>
            <p>Дата оплаты: <b class='text-success'><?= $payDate ?></b><br/>
            <?php
        } else {
            ?>
            <h3>Статус: <b class='text-danger'>Не оплачен</b></h3>
            <?php
        }
        ?>
        <table class="table table-condensed table-hover">
            <tbody>
            <tr>
                <td>К оплате по счёту:</td>
                <td><b class='text-success'><?= $info['billInfo']->totalSumm?> &#8381;</b></td>
            </tr>
            <tr>
                <td>Оплачено с депозита:</td>
                <td><b class='text-success'><?= $info['billInfo']->depositUsed?> &#8381;</b></td>
            </tr>
            <tr>
                <td>Скидка:</td>
                <td><b class='text-success'><?= $info['billInfo']->discount?> &#8381;</b></td>
            </tr>
            <?php
            if ($info['billInfo']->isPayed === 1 ) {
                if($info['billInfo']->toDeposit !== null){
            ?>

                <tr>
                    <td>Зачислено на депозит:</td>
                    <td><b class='text-success'><?= $info['billInfo']->toDeposit?> &#8381;</b></td>
                </tr>
            <?php
                }?>
                 <tr>
                <td>Итого оплачено:</td>
                <td><b class='text-success'><?= $info['payedSumm']?> &#8381;</b></td>
            </tr>
                <?php
    }
            else{
            ?>
            <tr>
                <td>Итого к оплате:</td>
                <td><b class='text-success'><?= $info['summToPay']?> &#8381;</b></td>
            </tr>
            <?php
            }
    ?>
            </tbody>
        </table>
        <h3>Подробная информация:</h3>

        <?=PaymentDoubleDetailsWidget::widget(['info' => $info['paymentContent']]);?>

    </div>
    <?php
    if ($info['billInfo']->isPayed === 0) {
        ?>
        <div class="col-lg-12 margened">
            <button class="btn btn-info" id="printInvoice">Распечатать квитанцию</button>
            <button class="btn btn-info" id="sendInvoice">Квитанцию на мыло</button>
        </div>
        <div class="col-lg-12 margened">
            <button class="btn btn-success" id="payedCash">Оплачено наличными</button>
            <button class="btn btn-success" id="payedNoCash">Оплачено безналом</button>
            <button class="btn btn-danger" id="deleteBill">Удалить счёт</button>
        </div>
        <?php
    }
    ?>
    <div class="col-lg-12">
        <button class="btn btn-info" id="remindAbout">Напомнить о платеже</button>
    </div>
</div>
