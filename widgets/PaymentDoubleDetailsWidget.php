<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 11:51
 */

namespace app\widgets;


use app\models\CashHandler;
use app\models\TimeHandler;
use yii\base\Widget;


class PaymentDoubleDetailsWidget extends Widget
{
    public $info;
    public $content = '';
    public $isMail = false;


    public function run()
    {
        if (!empty($this->info['additionalPower'])) {
            ?>
            <div class="color-salad row">
                <h3>Электроэнергия</h3>
                <p> Оплачено месяцев: <b class='text-success'><?= count($this->info['additionalPower']['values']) ?></b><br/>
                <p> Общая сумма платежей за электроэнергию: <b
                            class='text-success'><?= $this->info['additionalPower']['summ'] ?>
                        &#8381;</b><br/>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <tbody>
                        <?php
                        foreach ($this->info['additionalPower']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['summ']);
                            $date = TimeHandler::getFullFromShotMonth($item['date']);
                            if ($summ > 0) {
                                ?>
                                <tr>
                                    <td>
                                        <b class="text-primary"><?= $date ?></b>
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= $summ ?> &#8381;</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Показания счётчика на начало месяца
                                    </td>
                                    <td class="text-left">
                                        <b class="text-warning"><?= $item['old-data'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Показания счётчика на конец месяца
                                    </td>
                                    <td class="text-left">
                                        <b class="text-warning"><?= $item['new-data'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Израсходовано за месяц
                                    </td>
                                    <td class="text-left">
                                        <b class="text-warning"><?= $item['difference'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Льготный лимит
                                    </td>
                                    <td class="text-left">
                                        <?php
                                        if ($item['corrected'] === '1') {
                                            echo '(Льготный лимит сброшен)';
                                        }
                                        ?>
                                        <b class="text-info"><?= $item['powerLimit'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        В пределах льготного лимита
                                    </td>
                                    <td class="text-left">
                                        <b class="text-warning"><?= $item['in-limit'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость 1 кВт.ч (льготная)
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= $item['powerCost'] ?> &#8381;</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость льготной электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= $item['in-limit-cost'] ?> &#8381;</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        За пределами льготного лимита
                                    </td>
                                    <td class="text-left">
                                        <b class="text-warning"><?= $item['over-limit'] ?> кВт.ч</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость 1 кВт.ч
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= $item['powerOvercost'] ?> &#8381;</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= $item['over-limit-cost'] ?> &#8381;</b>
                                    </td>
                                </tr>
                                <tr>
                                <?php
                            } else {
                                ?>
                                <tr>
                                    <td>
                                        <b class="text-primary"><?= $date ?></b>
                                    </td>
                                    <td class="text-left">
                                        <b class="text-info"><?= $summ ?></b>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="margened clearfix"></div>
            <?php
        }
        if (!empty($this->info['additionalMembership'])) {
            ?>
            <div class="color-yellow row">
                <div class="col-lg-12">
                    <h3>Членские взносы</h3>
                    <p> Оплачено кварталов: <b
                                class='text-success'><?= count($this->info['additionalMembership']['values']) ?></b><br/>
                    <p> Общая сумма членских взносов: <b
                                class='text-success'><?= $this->info['additionalMembership']['summ'] ?>&#8381;</b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <tbody>
                        <?php
                        foreach ($this->info['additionalMembership']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['summ']);
                            $date = TimeHandler::getFullFromShortQuarter($item['date']);
                            ?>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $summ ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за квартал
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['fixed'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Стоимость взноса за квартал с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['float'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Площадь участка
                                </td>
                                <td class="text-left">
                                    <b class="text-warning"><?= $item['square'] ?> м<sup>2</sup></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Сумма взноса по метражу
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['float-cost'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="margened clearfix"></div>
            <?php
        }
        if (!empty($this->info['additionalTarget'])) {
            ?>
            <div class="color-orange row">
                <div class="col-lg-12">
                    <h3>Целевые платежи</h3>
                    <p> Оплачено лет: <b class='text-success'><?= count($this->info['additionalTarget']['values']) ?></b><br/>
                    <p> Общая сумма целевых платежей: <b class='text-success'><?= $this->info['additionalTarget']['summ'] ?>
                            &#8381;</b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['additionalTarget']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['summ']);
                            $date = $item['year'] . ' год';
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $summ ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за квартал
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['fixed'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Стоимость взноса за квартал с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['float'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Сумма взноса по метражу
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['float-cost'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Площадь участка
                                </td>
                                <td class="text-left">
                                    <b class="text-warning"><?= $item['square'] ?> м<sup>2</sup></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Было оплачено ранее
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['payed-before'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Полная стоимость года
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['total-summ'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Осталось оплатить
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['left-pay'] ?> &#8381;</b>
                                </td>
                            </tr>
                            </tbody>
                            <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="margened clearfix"></div>
            <?php
        }
        if (!empty($this->info['single'])) {
            ?>
            <div class="color-orange row">
                <div class="col-lg-12">
                    <h3>Разовые платежи</h3>
                    <p> Оплачено платежей: <b class='text-success'><?= count($this->info['single']['values']) ?></b><br/>
                    <p> Общая сумма разовых платежей: <b class='text-success'><?= $this->info['single']['summ'] ?>
                            &#8381;</b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['single']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['payed']);
                            $ds = $item['description'];
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?=$ds?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?=$summ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Полная цена
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= $item['summ'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Было оплачено ранее
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['payed-before'] ?> &#8381;</b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Осталось оплатить
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= $item['left-pay'] ?> &#8381;</b>
                                </td>
                            </tr>
                            </tbody>
                            <?php
                        }
                        ?>
                    </table>
                </div>
            </div>
            <div class="margened clearfix"></div>
            <?php
        }
    }
}

?>