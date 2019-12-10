<?php

namespace app\widgets;

use app\models\AdditionalCottage;
use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\DOMHandler;
use app\models\MembershipHandler;
use app\models\PersonalTariff;
use app\models\Table_additional_payed_membership;
use app\models\Table_payed_membership;
use yii\base\Widget;

class MembershipStatisticWidget extends Widget {

	public $quarterInfo;
	public $content = '';

	public function run()
	{
		$quarter = $this->quarterInfo->quarter;
		$values = DOMHandler::getXMLValues($this->quarterInfo->paymentInfo);

		// получу всё фактически оплаченное за месяц
        $payed = Table_payed_membership::find()->where(['quarter' => $quarter])->all();
        $payedMain = 0;
        if(!empty($payed)){
            foreach ($payed as $item) {
                $payedMain += $item->summ;
            }
        }
        $payedAdds = Table_additional_payed_membership::find()->where(['quarter' => $quarter])->all();
        $payedDouble = 0;
        if(!empty($payedAdds)){
            foreach ($payedAdds as $item) {
                $payedDouble += $item->summ;
            }
        }

        $plannedPay = 0;
        $plannedIndividual = 0;
        // посчитаю план оплаты
        $cottages = Cottage::getRegistred();
        foreach ($cottages as $cottage) {
            // если не назначен индивидуальный тариф
            if(!$cottage->individualTariff){
                $plannedPay += Calculator::countFixedFloat($this->quarterInfo->fixed_part, $this->quarterInfo->changed_part, $cottage->cottageSquare);
            }
            else{
                // получу данные по индивидуальному тарифу
                $data = PersonalTariff::getMembershipRate($cottage, $quarter);
                if(!empty($data)){
                    $plannedIndividual += Calculator::countFixedFloat($data['fixed'], $data['float'], $cottage->cottageSquare);
                }
            }
        }
        $additionalCottages = AdditionalCottage::getRegistred();
        foreach ($additionalCottages as $cottage){
            if($cottage->isMembership){
                if(!$cottage->individualTariff){
                    $plannedPay += Calculator::countFixedFloat($this->quarterInfo->fixed_part, $this->quarterInfo->changed_part, $cottage->cottageSquare);
                }
                else {
                    // получу данные по индивидуальному тарифу
                    $data = PersonalTariff::getMembershipRate($cottage, $quarter);
                    if (!empty($data)) {
                        $plannedIndividual += Calculator::countFixedFloat($data['fixed'], $data['float'], $cottage->cottageSquare);
                    }
                }
            }
        }

        $plannedTotal = CashHandler::toRubles($plannedPay + $plannedIndividual);

        $payedTotal = CashHandler::toRubles($payedMain + $payedDouble);

        $payedWhole = CashHandler::toRubles($payedTotal + CashHandler::toRubles($values['payed_outside']));

        $payedPercent = CashHandler::countPartialPercent($plannedTotal, $payedWhole);

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
					<td>Сумма членских взносов (стандарт) (план)</td>
					<td><b class="text-info"> <?=CashHandler::toRubles($plannedPay)?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Сумма членских взносов (индивидуальные тарифы) (план)</td>
					<td><b class="text-info"> <?=CashHandler::toRubles($plannedIndividual)?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Сумма членских взносов (всего) (план)</td>
					<td><b class="text-info"> <?=$plannedTotal?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Из них оплачено (через программу)</td>
					<td><b class="text-info"> <?=$payedTotal?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Из них оплачено (вне программы)</td>
					<td><b class="text-info"> <?=$values['payed_outside']?> &#8381;</b></td>
				</tr>
				<tr>
					<td>Оплачено всего (теоретически)</td>
                    <td><b class="text-info"> <?=$payedWhole?> &#8381;</b> из <b class="text-warning"> <?=$plannedTotal?> &#8381;</b> <b class="text-info">(<?=$payedPercent?>%)</b></td>
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