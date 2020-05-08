<?php

use app\assets\MailingScheduleAsset;
use app\models\Cottage;
use app\models\database\CottageReport;
use app\models\database\Mail;
use app\models\database\Mailing;
use app\models\database\MailingSchedule;
use app\models\database\SingleMail;
use app\models\handlers\BillsHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Url;
use yii\web\View;


/* @var $this View */
/* @var $waiting MailingSchedule[] */

MailingScheduleAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Очередь сообщений';

if (!empty($waiting)) {
    echo "<h1 class='margin text-center'>Рассылка</h1>";
    echo "<div class='margin text-center'><span>Сообщений в очереди- <span id='unsendedMessagesCounter'>" . count($waiting) . '</span></span></div>';
    echo "<div class='text-center margin'><div class='btn-group-vertical'><button class='btn btn-default' id='beginSendingBtn'><span class='text-success'>Начать рассылку</span></button><button class='btn btn-default' id='clearSendingBtn'><span class='text-danger'>Очистить список</span></button></div></div>";
    echo '<table class="table table-bordered table-striped table-hover"><thead><tr><th>Тип</th><th>Номер участка</th><th>Заголовок</th><th>Адрес почты</th><th>ФИО</th><th>Статус</th><th>Действия</th></thead><tbody>';
    foreach ($waiting as $item) {
        // найду информацию о почте и о рассылке
        $mailInfo = Mail::getMailById($item->mailId);
        $cottage = Cottage::getCottageByLiteral($mailInfo->cottage);
        if (!empty($item->mailingId)) {
            $mailingInfo = Mailing::findOne($item->mailingId);
            // покажу информацию о ожидающем сообщении
            echo "<tr class='text-center'><td><b class='text-info'>Рассылка</b></td><td>{$cottage->cottageNumber}</td><td>" . urldecode($mailingInfo->title) . "</td><td>{$mailInfo->email}</td><td>{$mailInfo->fio}</td><td><b class='text-info mailing-status' data-schedule-id='{$item->id}'>Ожидает отправки</b></td><td><button class='mailing-cancel btn btn-default' data-schedule-id='{$item->id}'><span class='text-danger'>Отменить отправку</span></button></td></tr>";
        }
        else if(!empty($item->billId)){
            $billInfo = BillsHandler::getBill($item->billId);
            echo "<tr class='text-center'><td><b class='text-success'>Счёт</b></td><td><a href='" . Url::toRoute(['cottage/show', 'cottageNumber' => $cottage->cottageNumber]) . "' target='_blank'>{$cottage->cottageNumber}</a></td><td>{$billInfo->id} </td><td>{$mailInfo->email}</td><td>{$mailInfo->fio}</td><td><b class='text-info mailing-status' data-schedule-id='{$item->id}'>Ожидает отправки</b></td><td><button class='mailing-cancel btn btn-default' data-schedule-id='{$item->id}'><span class='text-danger'>Отменить отправку</span></button></td></tr>";
        }
        else if(!empty($item->singleMailId)){
            $textInfo = SingleMail::findOne($item->singleMailId);
            echo "<tr class='text-center'><td><b class='text-success'>Уведомление</b></td><td><a href='" . Url::toRoute(['cottage/show', 'cottageNumber' => $cottage->cottageNumber]) . "' target='_blank'>{$cottage->cottageNumber}</a></td><td>{$textInfo->title} </td><td>{$mailInfo->email}</td><td>{$mailInfo->fio}</td><td><b class='text-info mailing-status' data-schedule-id='{$item->id}'>Ожидает отправки</b></td><td><button class='mailing-cancel btn btn-default' data-schedule-id='{$item->id}'><span class='text-danger'>Отменить отправку</span></button></td></tr>";
        }
        else if(!empty($item->reportId)){
            echo "<tr class='text-center'><td><b class='text-success'>Уведомление</b></td><td><a href='" . Url::toRoute(['cottage/show', 'cottageNumber' => $cottage->cottageNumber]) . "' target='_blank'>{$cottage->cottageNumber}</a></td><td>Отчёт по платежам</td><td>{$mailInfo->email}</td><td>{$mailInfo->fio}</td><td><b class='text-info mailing-status' data-schedule-id='{$item->id}'>Ожидает отправки</b></td><td><button class='mailing-cancel btn btn-default' data-schedule-id='{$item->id}'><span class='text-danger'>Отменить отправку</span></button></td></tr>";
        }
    }
    echo '</tbody></table>';
} else {
    echo "<h1 class='text-center'>Неотправленных сообщений не найдено</h1>";
}