<?php

use app\assets\MembershipReportAsset;
use app\models\Table_cottages;
use yii\web\View;

MembershipReportAsset::register($this);

/* @var $this View */
/* @var $debtors Table_cottages[] */

echo '<table class="table table-striped"><tbody>';
foreach ($debtors as $debtor) {
    echo "<tr class='debtor' data-cottage='$debtor->cottageNumber'><td class='debt-owner' data-cottage='$debtor->cottageNumber'>$debtor->cottageNumber</td><td>{$debtor->membershipPayFor}</td><td class='status' data-cottage='$debtor->cottageNumber'>Не отправлено</td><td><label>Отправить <input type='checkbox' checked class='accept-send'></label></td></tr>";
}
echo '</tbody></table>';
echo '<button id="sendBtn" class="btn btn-default"><span class="text-success">Отправить напоминания</span></button>';
echo '<button id="clearBtn" class="btn btn-default"><span class="text-danger">Снять галочки</span></button>';
