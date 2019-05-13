<?php

namespace app\widgets;

use app\models\DOMHandler;
use yii\base\Widget;

class MembershipStatisticWidget extends Widget {

	public $quarterInfo;
	public $content = '';

	public function run()
	{
		$quarter = $this->quarterInfo->quarter;
		$values = DOMHandler::getXMLValues($this->quarterInfo->paymentInfo);
		?>
		<div class="col-lg-6">
			<h3><?=$quarter?></h3>
			<table class="table table-condensed table-hover">
				<tr>
					<td>Цена с сотки</td>
					<td><b class="text-warning"> <?=$this->quarterInfo->changed_part?>  &#8381;</b></td>
				</tr>
				<tr>
					<td>Цена с участка</td>
					<td><b class="text-warning"> <?=$this->quarterInfo->fixed_part?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Общая площадь участков</td>
					<td><b class="text-info"> <?=$values['full_square']?> м<sup>2</sup></b></td>
				</tr>
				<tr>
					<td>Сумма членских взносов (план)</td>
					<td><b class="text-info"> <?=$this->quarterInfo->fullSumm?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Из них оплачено (через программу)</td>
					<td><b class="text-info"> <?=$this->quarterInfo->payedSumm?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Из них оплачено (вне программы)</td>
					<td><b class="text-info"> <?=$values['payed_outside']?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Оплачено всего (теоретически)</td>
					<td><b class="text-info"> <?=$values['payed_untrusted']?> &#8381;</b> из <b class="text-warning"> <?=$this->quarterInfo->fullSumm?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Оплачено счетов (по данным программы)</td>
					<td><b class="text-info"> <?=$values['pay']?></b> из <b class="text-warning"> <?=$values['cottages_count']?></b></td>
				</tr>
				<tr>
					<td>Оплачено счетов (вне программы)</td>
					<td><b class="text-info"> <?=$values['pay_outside']?></b> из <b class="text-warning"> <?=$values['cottages_count']?></b></td>
				</tr>
				<tr>
					<td>Оплачено счетов всего (теоретически)</td>
					<td><b class="text-info"> <?=$values['pay_untrusted']?></b> из <b class="text-warning"> <?=$values['cottages_count']?></b></td>
				</tr>
				<?php
				if($values['additional_cottages_count'] > 0){
					?>
					<tr>
						<td>Оплачено счетов (доп) (по данным программы)</td>
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