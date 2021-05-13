<?php

use app\assets\AdditionalCottageAsset;
use app\models\MembershipHandler;
use app\models\Table_cottages;
use app\models\utils\NewFinesHandler;
use app\widgets\ShowPenaltiesWidget;
use yii\web\View;


/* @var $this View */
/* @var $cottage Table_cottages */
/* @var $finesHandler NewFinesHandler */

AdditionalCottageAsset::register($this);

?>

<div class="text-center">
    <div class="btn-group">
        <a href="<?= '/show-cottage/' . ($cottage->cottageNumber) ?>"
           class="btn btn-info"><span class="glyphicon glyphicon-level-up"></span></a>
        <a href="<?= $cottage->cottageNumber > 1 ? '/additional-actions/' . ($cottage->cottageNumber - 1) : '#' ?>"
           class="btn btn-success"><span class="glyphicon glyphicon-backward"></span></a>
        <a href="<?= $cottage->cottageNumber < 180 ? '/additional-actions/' . ($cottage->cottageNumber + 1) : '#' ?>"
           class="btn btn-success"><span class="glyphicon glyphicon-forward"></span></a></div>
</div>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#fines_tab" data-toggle="tab" class="active">Пени</a></li>
    <li><a href="#membership_details" data-toggle="tab">Членские взносы</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="fines_tab">
        <table class="table table-striped table-condensed table-hover">
            <tr>
                <th>Тип</th>
                <th>Период</th>
                <th>Статус</th>
                <th>Срок оплаты</th>
                <th>Дата оплаты</th>
                <th>Просрочено</th>
                <th>Долг</th>
                <th>В день</th>
                <th>Начислено</th>
                <th>Оплачено</th>
                <th>Оплачено полностью</th>
            </tr>
            <?= /** @noinspection PhpUnhandledExceptionInspection */
            (new ShowPenaltiesWidget(['penalties' => $finesHandler->getPowerFines()]))->run() ?>
        </table>
    </div>
    <div class="tab-pane" id="membership_details">
        <?php
        // получу все начисления по членским
        $accruals = MembershipHandler::getCottageAccruals($cottage);
        if (empty($accruals)) {
            echo "<h2 class='text-center'>Начислений нет</h2>";
        } else {
            echo "
            <table class=\"table table-striped table-condensed table-hover\">
            <tr>
                <th>Период</th>
                <th>Начислено</th>
                <th>Оплачено</th>
            </tr>
            ";
            foreach ($accruals as $accrual) {
                echo "
               <tr>
                   <td>
                        {$accrual->quarter}
                    </td>
                   <td>
                        {$accrual->getAccrual()}
                    </td>
                   <td>
                        {$accrual->getPayed()}
                    </td>
               </tr>
               <tr>
                    <td colspan='3'>
                        <div class='panel-group' id='accordion' role='tablist' aria-multiselectable='true'>
                            <div class='panel panel-default'>
                                <div class='panel-heading' role='tab' id='headingOne'>
                                    <h4 class='panel-title'>
                                        <a role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseOne' aria-expanded='true' aria-controls='collapseOne'>
                                            Детали
                                        </a>
                                    </h4>
                                </div>
                                <div id='collapseOne' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingOne'>
                                    <div class='panel-body'>
                                        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
               ";
            }
            echo "</table>";
        }
        ?>
    </div>
</div>
