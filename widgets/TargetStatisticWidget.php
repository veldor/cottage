<?php

namespace app\widgets;

use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_target;
use app\models\selections\TargetInfo;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_target;
use app\models\TargetHandler;
use yii\base\Widget;

class TargetStatisticWidget extends Widget
{

    public $yearInfo;
    public $content = '';

    public function run()
    {
        if (!empty($this->yearInfo)) {
            $year = $this->yearInfo->year;
            $statistics = TargetHandler::getYearStatistics($year);
            // получу данные по начислениям
            $accrualTotal = 0;
            $payedTotal = 0;
            $fullPayedCounter = 0;
            $partialPayedCounter = 0;
            $noPayedCounter = 0;
            if(!empty($statistics)){
                /** @var TargetInfo $item */
                foreach ($statistics as $item) {
                    $accrualTotal += $item->amount;
                    $payedTotal += $item->payed;
                    if($item->amount === $item->payed){
                        $fullPayedCounter++;
                    }
                    else if(CashHandler::toRubles($item->payed) == 0){
                        $noPayedCounter++;
                    }
                    else{
                        $partialPayedCounter++;
                    }
                }
            }
            $payedPercent = CashHandler::countPartialPercent($accrualTotal, $payedTotal);
            ?>
            <div class="col-lg-6">
                <h3><?= $year ?></h3>
                <table class="table table-condensed table-hover">
                    <tr>
                        <td>Цена с сотки</td>
                        <td><b class="text-warning"> <?= $this->yearInfo->float_part ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Цена с участка</td>
                        <td><b class="text-warning"> <?= $this->yearInfo->fixed_part ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Сумма целевых платежей</td>
                        <td><b class="text-info"> <?= CashHandler::toSmoothRubles($accrualTotal) ?></b></td>
                    </tr>
                    <tr>
                        <td>Из них оплачено</td>
                        <td>
                            <b class="text-info"> <?= CashHandler::toSmoothRubles($payedTotal) ?></b>
                            <b class="text-info">(<?= $payedPercent ?>%)</b>
                        </td>
                    </tr>
                    <tr>
                        <td>Оплачено полностью</td>
                        <td><b class="text-info"> <?= $fullPayedCounter ?></b></td>
                    </tr>
                    <tr>
                        <td>Оплачено частично</td>
                        <td><b class="text-info"> <?= $partialPayedCounter ?></b></td>
                    </tr>
                    <tr>
                        <td>Не оплачено</td>
                        <td><b class="text-info"> <?= $noPayedCounter ?></b></td>
                    </tr>
                </table>
            </div>
            <div class="clearfix"></div>
            <div class="col-lg-6 text-center">
                <button class="btn btn-default activator" data-action="/target/more/<?=$this->yearInfo->year?>"><span class="text-success">Подробности</span></button>
            </div>
            <div class="clearfix"></div>
            <?php
        }
    }
}