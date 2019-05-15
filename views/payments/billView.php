<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.10.2018
 * Time: 12:31
 */

use app\models\Table_payment_bills;
use app\models\TimeHandler;
use app\models\CashHandler;
use app\widgets\PaymentDetailsWidget;
/* @var $this yii\web\View */
/* @var $info */
/* @var $power DOMElement */
/* @var $target DOMElement */
/* @var $item DOMElement */

$double = !empty($info['cottageInfo']->hasDifferentOwner);

?>
<div class="row">
    <div class="col-lg-12 text-center"><h1>Информация о счёте №<?= $double ? $info['billInfo']->id . '-a' : $info['billInfo']->id ?></h1>
        <h3>Информация о плательщике</h3>
        <p>Номер дачного участка: <b class="text-success"><?= $double ? $info['cottageInfo']->masterId . '-a' :$info['cottageInfo']->cottageNumber ?></b></p>
        <p>Ф.И.О. владельца дачи: <b class="text-success"><?= $info['cottageInfo']->cottageOwnerPersonals ?></b></p>
    </div>

    <div class='col-lg-12'>
        <?php
        /** @var Table_payment_bills $info['billInfo'] */
        if ($info['billInfo']->isPayed === 1) {
            // если платёж закрыт- посчитаю, оплачен ли он, если оплачен- то частично или полностью
            if(!empty($info['billInfo']->payedSumm)){
                $payedSumm = CashHandler::rublesMath(CashHandler::toRubles($info['billInfo']->payedSumm) + CashHandler::toRubles($info['billInfo']->depositUsed) + CashHandler::toRubles($info['billInfo']->discount));
            }
            else{
                $payedSumm = CashHandler::rublesMath(CashHandler::toRubles($info['billInfo']->depositUsed) + CashHandler::toRubles($info['billInfo']->discount));

            }
            if($payedSumm === 0 || ($payedSumm < $info['billInfo']->totalSumm && !$info['billInfo']->isPartialPayed)){
                echo "<h3>Статус: <b class='text-warning'>Закрыт. Не оплачен.</b></h3>";
            }
            elseif($payedSumm >= $info['billInfo']->totalSumm){
                $payDate = TimeHandler::getDatetimeFromTimestamp($info['billInfo']->paymentTime);
                echo "<h3>Статус: <b class='text-success'>Закрыт. Оплачен полностью.</b></h3>";
                echo " <p>Дата оплаты: <b class='text-success'>$payDate</b><br/>";
            }
            else{
                echo "<h3>Статус: <b class='text-info'>Закрыт. Оплачен частично.</b></h3>";
            }
            ?>

            <?php
        }
        elseif($info['billInfo']->isPartialPayed === 1){
            ?>
            <h3>Статус: <b class='text-info'>Оплачен частично</b></h3>
            <?php
        }
        else {
            ?>
            <h3>Статус: <b class='text-danger'>Не оплачен</b></h3>
            <?php
        }
        ?>
        <table class="table table-condensed table-hover">
            <tbody>
            <tr>
                <td>К оплате по счёту:</td>
                <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['billInfo']->totalSumm)?></b></td>
            </tr>
            <tr>
                <td>Оплата с депозита:</td>
                <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['billInfo']->depositUsed)?></b></td>
            </tr>
            <tr>
                <td>Скидка:</td>
                <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['billInfo']->discount)?></b></td>
            </tr>
            <?php
            if ($info['billInfo']->isPartialPayed === 1 ) {
                ?>
                <tr>
                    <td>Сумма частичной оплаты:</td>
                    <td><b class='text-info'><?= CashHandler::toSmoothRubles($info['billInfo']->payedSumm)?></b></td>
                </tr>
                <?php
            }
            if ($info['billInfo']->isPayed === 1 ) {
                if($info['billInfo']->toDeposit !== null){
            ?>

                <tr>
                    <td>Зачислено на депозит:</td>
                    <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['billInfo']->toDeposit)?></b></td>
                </tr>
            <?php
                }
                if(!empty($info['payedSumm'])){
                    ?>
                    <tr>
                        <td>Итого оплачено:</td>
                        <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['payedSumm'])?></b></td>
                    </tr>
                    <?php
                }
                ?>
                <?php
    }
            else{
            ?>
            <tr>
                <td>Итого к оплате:</td>
                <td><b class='text-success'><?= CashHandler::toSmoothRubles($info['summToPay'])?></b></td>
            </tr>
            <?php
            }
    ?>
            </tbody>
        </table>
        <h3>Подробная информация:</h3>

        <?=PaymentDetailsWidget::widget(['info' => $info['paymentContent']]);?>

    </div>
    <?php

    if($info['billInfo']->isPartialPayed){
        // продолжаю оплату
        echo '
        <div class="col-lg-12 margened btn-group">
            <button type="button" class="btn btn-success" id="payedActivator">Продолжить оплату</button>
            <button type="button" class="btn btn-warning" id="payClosedActivator">Закрыть счёт</button>
            <button class="btn btn-info" id="printInvoice">Распечатать счёт</button>
        </div>
        ';
    }

    if ($info['billInfo']->isPayed === 0 && $info['billInfo']->isPartialPayed === 0 ) {
        ?>
       <!-- <div class="col-lg-12 margened">
            <button class="btn btn-info" id="printInvoice">Распечатать квитанцию</button>
            <button class="btn btn-info" id="sendInvoice">Квитанцию на мыло</button>
        </div>-->
        <div class="col-lg-12 margened btn-group">
            <button class="btn btn-success" id="payedActivator">Подтвердить оплату</button>
            <button class="btn btn-danger" id="deleteBill">Закрыть счёт</button>
            <button class="btn btn-info" id="printInvoice">Распечатать счёт</button>
        </div>
        <?php
    }
    ?>
    <div class="col-lg-12 btn-group">
        <button class="btn btn-info" id="remindAbout">Напомнить о счёте</button>
        <button class="btn btn-info" id="formBankInvoice">Распечатать квитанцию</button>
        <button class="btn btn-info" id="sendBankInvoice">Отправить квитанцию</button>
    </div>
</div>
