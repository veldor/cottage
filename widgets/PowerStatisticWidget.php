<?php

namespace app\widgets;

use app\models\DOMHandler;
use yii\base\Widget;

class PowerStatisticWidget extends Widget {

	public $monthInfo;
	public $content = '';

	public function run()
	{
		$month = $this->monthInfo->targetMonth;
		$values = DOMHandler::getXMLValues($this->monthInfo->paymentInfo);
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
					<td><b class="text-info"> <?=$values['used_energy']?> кВт.ч</b></td>
				</tr>
				<tr>
					<td>На общую сумму</td>
					<td><b class="text-info"> <?=$this->monthInfo->fullSumm?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Из них оплачено</td>
					<td><b class="text-info"> <?=$this->monthInfo->payedSumm?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Заполнено показаний счётчиков</td>
					<td><b class="text-info"> <?=$values['fill']?></b> из <b class="text-warning"> <?=$values['cottages_count']?></b></td>
				</tr>
				<tr>
					<td>Оплачено счетов</td>
					<td><b class="text-info"> <?=$values['pay']?></b> из <b class="text-warning"> <?=$values['cottages_count']?></b></td>
				</tr>
				<?php
				if($values['additional_cottages_count'] > 0){
					?>

					<tr>
						<td>Заполнено показаний счётчиков(доп)</td>
						<td><b class="text-info"> <?=$values['fill_additional']?></b> из <b class="text-warning"> <?=$values['additional_cottages_count']?></b></td>
					</tr>
					<tr>
						<td>Оплачено счетов(доп)</td>
						<td><b class="text-info"> <?=$values['pay_additional']?></b> из <b class="text-warning"> <?=$values['additional_cottages_count']?></b></td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
        <div class="clearfix"></div>
		<?php
	}
}