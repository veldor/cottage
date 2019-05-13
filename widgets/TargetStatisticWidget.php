<?php

namespace app\widgets;

use app\models\DOMHandler;
use yii\base\Widget;

class TargetStatisticWidget extends Widget {

	public $yearInfo;
	public $content = '';

	public function run()
	{
		if (!empty($this->yearInfo)) {
			$year = $this->yearInfo->year;
			$values = DOMHandler::getXMLValues($this->yearInfo->paymentInfo);
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
                        <td><b class="text-info"> <?= $this->yearInfo->fullSumm ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Из них оплачено</td>
                        <td><b class="text-info"> <?= $this->yearInfo->payedSumm ?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Из них оплачено (вне программы)</td>
                        <td><b class="text-info"> <?=$values['payed_outside']?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Оплачено всего (теоретически)</td>
                        <td><b class="text-info"> <?=$values['payed_untrusted']?> &#8381;</b> из <b class="text-warning"> <?=$this->yearInfo->fullSumm?> &#8381;</b></td>
                    </tr>
                    <tr>
                        <td>Оплачено полностью</td>
                        <td><b class="text-info"> <?= $values['full_payed'] ?></b> из <b
                                    class="text-warning"> <?= $values['cottages_count'] ?></b></td>
                    </tr>
                    <tr>
                        <td>Оплачено частично</td>
                        <td><b class="text-info"> <?= $values['partial_payed'] ?></b> из <b
                                    class="text-warning"> <?= $values['cottages_count'] ?></b></td>
                    </tr>
					<?php
					if ($values['additional_cottages_count'] > 0) {
						?>
                        <tr>
                            <td>Оплачено полностью(доп)</td>
                            <td><b class="text-info"> <?= $values['additional_full_payed'] ?></b> из <b
                                        class="text-warning"> <?= $values['additional_cottages_count'] ?></b></td>
                        </tr>
						<?php
						?>
                        <tr>
                            <td>Оплачено частично(доп)</td>
                            <td><b class="text-info"> <?= $values['additional_partial_payed'] ?></b> из <b
                                        class="text-warning"> <?= $values['additional_cottages_count'] ?></b></td>
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
}