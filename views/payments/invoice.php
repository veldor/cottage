<?php

/* @var $this \yii\web\View */

/* @var $content string */

use app\assets\InvoiceAsset;
use app\models\TimeHandler;
use yii\helpers\Html;
use app\widgets\PaymentDetailsWidget;

/* @var $info */

InvoiceAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">

    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center"><h1>Информация о счёте №<?= $info['billInfo']->id ?></h1>
                <h3>Информация о плательщике</h3>
                <p>Номер дачного участка: <b class="text-success"><?= $info['cottageInfo']->cottageNumber ?></b></p>
                <p>Ф.И.О. владельца дачи: <b class="text-success"><?= $info['cottageInfo']->cottageOwnerPersonals ?></b>
                </p>
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
                        <td><b class='text-success'><?= $info['billInfo']->totalSumm ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Оплачено с депозита:</td>
                        <td><b class='text-success'><?= $info['billInfo']->depositUsed ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Скидка:</td>
                        <td><b class='text-success'><?= $info['billInfo']->discount ?> &#8381;</b></td>
                    </tr>
                    <?php
                    if ($info['billInfo']->isPayed === 1) {
                        if ($info['billInfo']->toDeposit !== null) {
                            ?>

                            <tr>
                                <td>Зачислено на депозит:</td>
                                <td><b class='text-success'><?= $info['billInfo']->toDeposit ?> &#8381;</b></td>
                            </tr>
                            <?php
                        } ?>
                        <tr>
                            <td>Итого оплачено:</td>
                            <td><b class='text-success'><?= $info['payedSumm'] ?> &#8381;</b></td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <tr>
                            <td>Итого к оплате:</td>
                            <td><b class='text-success'><?= $info['summToPay'] ?> &#8381;</b></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <h3>Подробная информация:</h3>

                <?= PaymentDetailsWidget::widget(['info' => $info['paymentContent']]); ?>

            </div>
        </div>
    </div>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

