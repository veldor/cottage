<?php

namespace app\widgets;

use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_target;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_target;
use yii\base\Widget;

class TargetStatisticWidget extends Widget
{

    public $yearInfo;
    public $content = '';

    public function run()
    {
        if (!empty($this->yearInfo)) {
            $year = $this->yearInfo->year;
            // получу данные по начислениям
            $accrualsData = Accruals_target::findAll(['year' => $year]);
            $accrualTotal = 0;
            $payedTotal = 0;
            $fullPayedCounter = 0;
            $partialPayedCounter = 0;
            $noPayedCounter = 0;
            if (!empty($accrualsData)) {
                foreach ($accrualsData as $item) {
                    $accrued = Calculator::countFixedFloat($item->fixed_part, $item->square_part, $item->counted_square);
                    $accrualTotal += $accrued;
                    // проверю оплату
                    $cottageInfo = Cottage::getCottageByLiteral($item->cottage_number);
                    $payed = 0;
                    if ($cottageInfo->isMain()) {
                        $pays = Table_payed_target::findAll(['year' => $year, 'cottageId' => $cottageInfo->getBaseCottageNumber()]);

                    } else {
                        $pays = Table_additional_payed_target::findAll(['year' => $year, 'cottageId' => $cottageInfo->getBaseCottageNumber()]);

                    }
                    if (!empty($pays)) {
                        foreach ($pays as $pay) {
                            $payed += $pay->summ;
                        }
                    }
                    if($payed === 0){
                        $noPayedCounter++;
                    }
                    elseif ($payed === $accrued){
                        $fullPayedCounter++;
                    }
                    else{
                        $partialPayedCounter++;
                    }
                    $payedTotal += $payed;
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
            <?php
        }
    }
}