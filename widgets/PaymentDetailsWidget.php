<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 11:51
 */

namespace app\widgets;


use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use app\models\TimeHandler;
use yii\base\Widget;


class PaymentDetailsWidget extends Widget
{
    public $info;
    public $content = '';
    public $isMail = false;


    public function run()
    {
        if (!empty($this->info['power'])) {
            ?>
            <div class="color-salad">
                <h3>Электроэнергия</h3>
                <p> Месяцев к оплате: <b class='text-success'><?= count($this->info['power']['values']) ?></b><br/>
                <p> Общая стоимость электроэнергии: <b
                            class='text-success'><?= CashHandler::toSmoothRubles($this->info['power']['summ']) ?></b><br/>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <tbody>
                        <?php
                        foreach ($this->info['power']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['summ']);
                            if (!empty($item['prepayed'])) {
                                $prepayed = CashHandler::toRubles($item['prepayed']);
                            } else {
                                $prepayed = 0;
                            }
                            $realSumm = CashHandler::rublesMath($summ + $prepayed);
                            $date = TimeHandler::getFullFromShotMonth($item['date']);
                            if ($summ > 0) {
                                ?>
                                <tr>
                                    <td>
                                        <b class="text-primary"><?= $date ?></b>
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($realSumm) ?></b>
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
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['powerCost']) ?></b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость льготной электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['in-limit-cost']) ?></b>
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
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['powerOvercost']) ?></b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['over-limit-cost']) ?></b>
                                    </td>
                                </tr>
                                <?php
                                if (!empty($item['prepayed'])) {
                                    echo '
                                    <tr>
                                        <td>
                                            Оплачено ранее
                                        </td>
                                        <td class="text-left">
                                            <b class="text-success">' . CashHandler::toSmoothRubles($item['prepayed']) . '</b>
                                        </td>
                                    </tr>
                                ';
                                }
                                ?>
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
        if (!empty($this->info['additionalPower'])) {
            ?>
            <div class="color-salad">
                <h3>Электроэнергия (Дополнительный участок)</h3>
                <p> Месяцев к оплате: <b class='text-success'><?= count($this->info['additionalPower']['values']) ?></b><br/>
                <p> Общая стоимость электроэнергии: <b
                            class='text-success'><?= CashHandler::toSmoothRubles($this->info['additionalPower']['summ']) ?></b><br/>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <tbody>
                        <?php
                        foreach ($this->info['additionalPower']['values'] as $item) {
                            $summ = CashHandler::toRubles($item['summ']);
                            if (!empty($item['prepayed'])) {
                                $prepayed = CashHandler::toRubles($item['prepayed']);
                            } else {
                                $prepayed = 0;
                            }
                            $realSumm = CashHandler::rublesMath($summ + $prepayed);
                            $date = TimeHandler::getFullFromShotMonth($item['date']);
                            if ($summ > 0) {
                                ?>
                                <tr>
                                    <td>
                                        <b class="text-primary"><?= $date ?></b>
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($realSumm) ?></b>
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
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['powerCost']) ?></b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость льготной электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['in-limit-cost']) ?></b>
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
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['powerOvercost']) ?></b>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Стоимость электроэнергии
                                    </td>
                                    <td class="text-left">
                                        <b class="text-success"><?= CashHandler::toSmoothRubles($item['over-limit-cost']) ?></b>
                                    </td>
                                </tr>
                                <?php
                                if (!empty($item['prepayed'])) {
                                    echo '
                                    <tr>
                                        <td>
                                            Оплачено ранее
                                        </td>
                                        <td class="text-left">
                                            <b class="text-success">' . CashHandler::toSmoothRubles($item['prepayed']) . '</b>
                                        </td>
                                    </tr>
                                ';
                                }
                                ?>
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
        if (!empty($this->info['membership'])) {
            ?>
            <div class="color-yellow row">
                <div class="col-lg-12">
                    <h3>Членские взносы</h3>
                    <p> Кварталов к оплате: <b
                                class='text-success'><?= count($this->info['membership']['values']) ?></b><br/>
                    <p> Общая сумма оплаты: <b
                                class='text-success'><?= CashHandler::toSmoothRubles($this->info['membership']['summ']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['membership']['values'] as $item) {
                            $date = TimeHandler::getFullFromShortQuarter($item['date']);
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за квартал
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['fixed']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Взнос за квартал с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float']) ?></b>
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
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['float-cost']) ?></b>
                                </td>
                            </tr>
                            <?php
                            if (!empty($item['prepayed'])) {
                                echo '
                                    <tr>
                                        <td>
                                            Оплачено ранее
                                        </td>
                                        <td class="text-left">
                                            <b class="text-success">' . CashHandler::toSmoothRubles($item['prepayed']) . '</b>
                                        </td>
                                    </tr>
                                ';
                            }
                            ?>
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
        if (!empty($this->info['additionalMembership'])) {
            ?>
            <div class="color-yellow row">
                <div class="col-lg-12">
                    <h3>Членские взносы (Дополнительный участок)</h3>
                    <p> Кварталов к оплате: <b
                                class='text-success'><?= count($this->info['additionalMembership']['values']) ?></b><br/>
                    <p> Общая сумма оплаты: <b
                                class='text-success'><?= CashHandler::toSmoothRubles($this->info['additionalMembership']['summ']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['additionalMembership']['values'] as $item) {
                            $date = TimeHandler::getFullFromShortQuarter($item['date']);
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за квартал
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['fixed']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Взнос за квартал с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float']) ?></b>
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
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['float-cost']) ?></b>
                                </td>
                            </tr>
                            <?php
                            if (!empty($item['prepayed'])) {
                                echo '
                                    <tr>
                                        <td>
                                            Оплачено ранее
                                        </td>
                                        <td class="text-left">
                                            <b class="text-success">' . CashHandler::toSmoothRubles($item['prepayed']) . '</b>
                                        </td>
                                    </tr>
                                ';
                            }
                            ?>
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
        if (!empty($this->info['target'])) {
            ?>
            <div class="color-orange row">
                <div class="col-lg-12">
                    <h3>Целевые платежи</h3>
                    <p> Лет к оплате: <b class='text-success'><?= count($this->info['target']['values']) ?></b><br/>
                    <p> Общая сумма целевых платежей: <b
                                class='text-success'><?= CashHandler::toSmoothRubles($this->info['target']['summ']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['target']['values'] as $item) {
                            $date = $item['year'] . ' год';
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за год
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['fixed']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Стоимость взноса за год с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float']) ?></b>
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
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float-cost']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Было оплачено ранее
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['payed-before']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Полная стоимость года
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['total-summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Осталось оплатить
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['left-pay']) ?></b>
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
        if (!empty($this->info['additionalTarget'])) {
            ?>
            <div class="color-orange row">
                <div class="col-lg-12">
                    <h3>Целевые платежи (Дополнительный участок)</h3>
                    <p> Лет к оплате: <b class='text-success'><?= count($this->info['additionalTarget']['values']) ?></b><br/>
                    <p> Общая сумма целевых платежей: <b
                                class='text-success'><?= CashHandler::toSmoothRubles($this->info['additionalTarget']['summ']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['additionalTarget']['values'] as $item) {
                            $date = $item['year'] . ' год';
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $date ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Фиксированный взнос за год
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['fixed']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Стоимость взноса за год с 1 сотки
                                </td>
                                <td class="text-left">
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float']) ?></b>
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
                                    <b class="text-info"><?= CashHandler::toSmoothRubles($item['float-cost']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Было оплачено ранее
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['payed-before']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Полная стоимость года
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['total-summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Осталось оплатить
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['left-pay']) ?></b>
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
                    <p> Платежей к оплате: <b
                                class='text-success'><?= count($this->info['single']['values']) ?></b><br/>
                    <p> Общая сумма разовых платежей: <b
                                class='text-success'><?= CashHandler::toSmoothRubles($this->info['single']['summ']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <?php
                        foreach ($this->info['single']['values'] as $item) {
                            $ds = $item['description'];
                            ?>
                            <tbody>
                            <tr>
                                <td>
                                    <b class="text-primary"><?= $ds ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['summ']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Было оплачено ранее
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['payed-before']) ?></b>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Осталось оплатить
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item['left-pay']) ?></b>
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
        if (!empty($this->info['fines'])) {
            ?>
            <div class="color-orange row">
                <div class="col-lg-12">
                    <h3>Пени</h3>
                    <p> Платежей к оплате: <b
                                class='text-success'><?= count($this->info['fines']) ?></b><br/>
                </div>
                <div class="col-lg-10">
                    <table class="table table-condensed table-hover">
                        <tr><th>Тип</th><th>Период</th><th>Стоимость</th><th>Дней</th><th>В день</th></tr>
                        <?php
                        $summ = 0;
                        /** @var Table_view_fines_info $item */
                        foreach ($this->info['fines'] as $item) {
                            $summ += $item->start_summ;
                            $forDay = CashHandler::toSmoothRubles($item->start_summ / $item->start_days);
                            ?>
                            <tr>
                                <td>
                                    <?=FinesHandler::$types[$item->pay_type]?>
                                </td>
                                <td>
                                    <b class="text-primary"><?= $item->period ?></b>
                                </td>
                                <td class="text-left">
                                    <b class="text-success"><?= CashHandler::toSmoothRubles($item->start_summ) ?></b>
                                </td>
                                <td class="text-left">
                                    <?=$item->start_days?>
                                </td>
                                <td class="text-left">
                                    <?=$forDay?>
                                </td>
                            </tr>
                            <?php
                        }
                        echo "
                    <p> Общая сумма: <b
                                class='text-success'>" . CashHandler::toSmoothRubles($summ) . "</b><br/>";
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