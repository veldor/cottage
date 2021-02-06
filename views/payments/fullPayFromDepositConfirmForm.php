<?php

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.10.2018
 * Time: 19:16
 */

use app\models\Pay;
use yii\web\View;

/* @var $this View */
/* @var $model Pay */
?>
<div class="row">

    <div class="col-sm-12 text-center">
        <h2>Платёж будет полностью оплачен с депозита</h2>
        <button class="btn btn-success" id="confirmPayFromDeposit">Да, оплатить с депозита</button>
    </div>
</div>

<script>
    function handleModal() {
        let payConfirmBtn = $('button#confirmPayFromDeposit');
        if (payConfirmBtn) {
            payConfirmBtn.on('click.send', function () {
                let modal = $('div#myModal');
                if (modal.length) {
                    modal.modal('hide');
                    sendAjax(
                        'post',
                        '/payments/confirm-deposit-pay',
                        simpleAnswerHandler,
                        {'billId': '<?=$model->billIdentificator?>', 'double': '<?=$model->double?>'}
                    )
                }
            });
        }
    }

    handleModal();
</script>