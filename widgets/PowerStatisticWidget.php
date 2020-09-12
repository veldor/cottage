<?php

namespace app\widgets;

use app\models\CashHandler;
use app\models\DOMHandler;
use app\models\Table_payed_power;
use app\models\Table_power_months;
use yii\base\Widget;

class PowerStatisticWidget extends Widget {

	public $monthInfo;
	public $content = '';

	public function run()
	{
		$month = $this->monthInfo->targetMonth;
		$indications = Table_power_months::findAll(['month' => $month]);
		$totalSpend = 0;
		$spendInLimit = 0;
		$spendOverLimit = 0;
		$totalAccrued = 0;
		$accruedInLimit = 0;
		$accruedOverLimit = 0;
		$totalPayed = 0;
		$fullPayedCounter = 0;
		$partialPayedCounter = 0;
		$noPayedCounter = 0;

		if(!empty($indications)){
            foreach ($indications as $indication) {
                // если потрачено на сумму больше 0- считаю
                if($indication->totalPay > 0){
                    $totalSpend += $indication->difference;
                    $totalAccrued += $indication->totalPay;
                    $spendInLimit += $indication->inLimitSumm;
                    $spendOverLimit += $indication->overLimitSumm;
                    $accruedInLimit += $indication->inLimitPay;
                    $accruedOverLimit += $indication->overLimitPay;
                    $pays = Table_payed_power::findAll(['month' => $month, 'cottageId' => $indication->cottageNumber]);
                    if(empty($pays)){
                        $noPayedCounter++;
                    }
                    else{
                        $payed = 0;
                        foreach ($pays as $pay) {
                            $payed += $pay->summ;
                        }
                        if($payed < $indication->totalPay){
                            $partialPayedCounter++;
                        }
                        else{
                            $fullPayedCounter++;
                        }
                        $totalPayed += $payed;
                    }
                }
		    }
        }

		?>
		<div class="col-lg-6">
			<h3><?=$month?> <button class="btn btn-default activator" data-action="/change-tariff/power/<?=$month?>"><span class="glyphicon-pencil text-info"></span></button></h3>
			<table class="table table-condensed table-hover">
				<tr>
					<td>Льготный лимит</td>
					<td><b class="text-warning"> <?=$this->monthInfo->powerLimit?> кВт.ч</b></td>
				</tr>
				<tr>
					<td>Льготная цена кВт</td>
					<td><b class="text-warning"> <?=$this->monthInfo->powerCost?>  &#8381;</b></td>
				</tr>
				<tr>
					<td>Цена кВт</td>
					<td><b class="text-warning"> <?=$this->monthInfo->powerOvercost?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Израсходовано садоводством</td>
					<td><b class="text-info"> <?=$totalSpend?> кВт.ч</b></td>
				</tr>
				<tr>
					<td>Израсходовано льготно</td>
					<td><b class="text-info"> <?=$spendInLimit?> кВт.ч</b></td>
				</tr>
				<tr>
					<td>Израсходовано сверх льготного</td>
					<td><b class="text-info"> <?=$spendOverLimit?> кВт.ч</b></td>
				</tr>
				<tr>
					<td>На общую сумму</td>
					<td><b class="text-info"> <?=CashHandler::toSmoothRubles($totalAccrued)?></b></td>
				</tr>
				<tr>
					<td>Льготная сумма</td>
					<td><b class="text-info"> <?=CashHandler::toSmoothRubles($accruedInLimit)?></b></td>
				</tr>
				<tr>
					<td>Сумма сверх льготного лимита</td>
					<td><b class="text-info"> <?=CashHandler::toSmoothRubles($accruedOverLimit)?></b></td>
				</tr>
				<tr>
					<td>Из них оплачено</td>
					<td><b class="text-info"> <?=CashHandler::toSmoothRubles($totalPayed)?></b></td>
				</tr>
				<tr>
					<td>Заполнено показаний счётчиков</td>
					<td><b class="text-info"> <?=count($indications)?></b></td>
				</tr>
				<tr>
					<td>Оплачено счетов полностью</td>
					<td><b class="text-info"> <?=$fullPayedCounter?></b></td>
				</tr>
				<tr>
					<td>Оплачено счетов частично</td>
					<td><b class="text-info"> <?=$partialPayedCounter?></b></td>
				</tr>
				<tr>
					<td>Не оплачено счетов</td>
					<td><b class="text-info"> <?=$noPayedCounter?></b></td>
				</tr>
			</table>
		</div>
        <div class="clearfix"></div>
		<?php
	}
}