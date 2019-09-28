<?php

use app\assets\CottageAsset;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\DOMHandler;
use app\models\GrammarHandler;
use app\models\Table_payed_power;
use app\models\Table_power_months;
use app\models\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

/* @var $this View */


ShowLoadingAsset::register($this);
CottageAsset::register($this);


/** @var Cottage $cottageInfo */
$this->title = 'Участок № ' . $cottageInfo->globalInfo->cottageNumber;

$haveAdditional = $cottageInfo->globalInfo->haveAdditional;
$differentOwner = $haveAdditional && $cottageInfo->additionalCottageInfo['cottageInfo']->hasDifferentOwner;

$firstCottageName = $haveAdditional ? $differentOwner ? 'Подучасток 1 ' : 'Основной участок ' : 'Действия с участком ';

$firstName = $haveAdditional ? $differentOwner ? 'Подучасток 1 ' : 'Основной участок ' : 'Информация о платежах ';

$secondName = $differentOwner ? 'Подучасток 2 ' : 'Дополнительный участок';

$hasSingleDebt = $cottageInfo->globalInfo->singleDebt > 0;
$hasDoubleSingleDebt = $haveAdditional && $cottageInfo->additionalCottageInfo['cottageInfo']->singleDebt > 0;

$registrationNumber = $cottageInfo->globalInfo->cottageRegistrationInformation ? $cottageInfo->globalInfo->cottageRegistrationInformation : 'Не зарегистрирован';

?>

<div class="row">
    <div class="col-lg-12">
        <h1>Участок № <?= $cottageInfo->globalInfo->cottageNumber ?></h1>

        <div class="input-group margened col-sm-2 col-lg-offset-5"><label for="goToCottageInput"></label><input type="text"
                                                                                                                id="goToCottageInput"
                                                                                                                class="form-control"
                                                                                                                value="<?= $cottageInfo->globalInfo->cottageNumber ?>"><span
                    class="input-group-btn"><button class="btn btn-default" type="button" id="goToCottageActivator"><span
                            class="glyphicon glyphicon-play"></span></button></span></div>

        <table class="table table-hover">
            <caption><?= $firstName ?></caption>
            <tbody>
            <?php
            if ($cottageInfo['globalInfo']->individualTariff) {
                echo '
                <tr>
                <td><b class="text-primary">Участку подключён индивидуальный тариф</b></td>
                <td><div class="btn-group">
                      <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">Действия с индивидуальным тарифом <span class="caret"></span></button>
                      <ul class="dropdown-menu" role="menu">
                        <li><a id="showPersonalTariff" href="#">Просмотреть данные тарифа</a></li>
                        <li><a id="editPersonalTariff" href="#">Редактировать данные тарифа</a></li>
                        <li><a id="indivTariffOffBtn" href="#">Отключить индивидуальный тариф</a></li>
                      </ul>
                    </div>
                </td>
                </tr>
                ';
            }
            ?>
            <tr>
                <td>Электроэнергия</td>
                <td><?= $cottageInfo->powerDebts > 0 ? "<a class='btn btn-default detail-debt' data-type='power' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->powerDebts) . "</b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
            </tr>
            <tr>
                <td>Электроэнергия- последний оплаченный месяц</td>
                <td>
                    <b class="text-info"><?= TimeHandler::getFullFromShotMonth($cottageInfo->globalInfo->powerPayFor) ?></b> <?= $cottageInfo->powerPayDifference ?>
                    <?php

                    // проверю частично оплаченные счета
                    $months = Table_power_months::find()->where(['cottageNumber' => $cottageInfo->globalInfo->cottageNumber])->all();
                    if(!empty($months)){
                        foreach ($months as $month) {
                            if($month->totalPay > 0){
                                // найду платежи по счёту
                                $payedAmount = 0;
                                $pays = Table_payed_power::findAll(['cottageId' => $cottageInfo->globalInfo->cottageNumber, 'month' => $month->month]);
                                if(!empty($pays)){
                                    foreach ($pays as $pay) {
                                        $payedAmount += CashHandler::toRubles($pay->summ);
                                    }
                                    if(CashHandler::toRubles($payedAmount) != CashHandler::toRubles($month->totalPay)){
                                        echo '<p><b class="text-info">' . TimeHandler::getFullFromShotMonth($month->month) . '</b>: оплачено частично, <b class="text-success">' . CashHandler::toSmoothRubles($payedAmount) . '</b> из ' . CashHandler::toRubles($month->totalPay) . '</p>';
                                    }
                                }
                            }
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Электроэнергия- последние показания счётчика</td>
                <td><b class="text-info"><?= $cottageInfo->globalInfo->currentPowerData ?> кВт.ч</b>
                    (<?= TimeHandler::getFullFromShotMonth($cottageInfo->lastPowerFillDate) ?>
                    )
                    <?= $cottageInfo->powerDataCancellable ? "<button id='cancelFillPower' class='btn btn-danger'>Удалить</button>" : '' ?>
                    <?= !$cottageInfo->filledPower ? "<button id='fillPower' class='btn btn-info'>Заполнить " . TimeHandler::getFullFromShotMonth(TimeHandler::getPreviousShortMonth()) . "</button>" : '' ?>
                </td>
            </tr>
            <tr>
                <td>Членские взносы</td>
                <td><?= $cottageInfo->membershipDebts > 0 ? "<a class='btn btn-default detail-debt' data-type='membership' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->membershipDebts) . "</b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
            </tr>
            <tr>
                <td>Членские взносы- последний оплаченный квартал</td>
                <td>
                    <b class="text-info"><?= TimeHandler::getFullFromShortQuarter($cottageInfo->globalInfo->membershipPayFor) ?></b>
                    <?php
                    if ($cottageInfo->globalInfo->partialPayedMembership) {
                        // получу данные о неполном платеже
                        $dom = new DOMHandler($cottageInfo->globalInfo->partialPayedMembership);
                        $info = $dom->query('/partial')->item(0);
                        echo '<p><b class="text-info">' . TimeHandler::getFullFromShortQuarter($info->getAttribute('date')) . '</b>: оплачено частично, <b class="text-success">' . CashHandler::toSmoothRubles($info->getAttribute('summ')) . '</b></p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td>Целевые платежи</td>
                <td><?= $cottageInfo->globalInfo->targetDebt > 0 ? "<a class='btn btn-default detail-debt' data-type='target' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->globalInfo->targetDebt) . "</b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
            </tr>
            <tr>
                <td>Разовые платежи</td>
                <td><?= $hasSingleDebt ? "<a class='btn btn-default detail-debt' data-type='single' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->globalInfo->singleDebt) . "</b></a>" : "Задолженностей не найдено  " ?></td>
            </tr>
            <?php
            // просмотрю пени
            if (!empty($cottageInfo->fines)) {
                $total = 0;
                foreach ($cottageInfo->fines as $fine) {
                    if ($fine->is_enabled) {
                        $total += CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ);
                    }
                }
                if ($total > 0) {
                    echo "<tr><td>Пени</td><td><button id='finesSumm' class='btn btn-danger'>" . CashHandler::toSmoothRubles($total) . "</button></td></tr>";
                    $cottageInfo->totalDebt += $total;
                }
            }
            ?>
            </tbody>
            <tr>
                <td>Итоговая задолженность</td>
                <td><?= $cottageInfo->totalDebt > 0 ? "<b class='text-danger'>" . CashHandler::toSmoothRubles($cottageInfo->totalDebt) . "</b>" : "<b class='text-success'>Отсутствует</b>" ?></td>
            </tr>
            <tr>
                <td>Депозит участка</td>
                <td><b class="text-info"><?= CashHandler::toSmoothRubles($cottageInfo->globalInfo->deposit) ?></b></td>
            </tr>
            <tr>
                <td>Площадь участка</td>
                <td><b class="text-info"><?= $cottageInfo->globalInfo->cottageSquare ?> м<sup>2</sup></b></td>
            </tr>
            <tr>
                <td>Кадастровый номер</td>
                <td><b class="text-info"><?= $registrationNumber ?></b></td>
            </tr>
        </table>


        <?php
        if ($cottageInfo->globalInfo->haveAdditional) {
            ?>

            <table class="table table-hover">
                <caption><?= $secondName ?></caption>
                <tbody>

                <?php

                if ($cottageInfo->additionalCottageInfo['cottageInfo']->individualTariff) {
                    echo '
                <tr>
                <td><b class="text-primary">Дополнительному участку подключён индивидуальный тариф</b></td>
                <td><div class="btn-group">
                      <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">Действия с индивидуальным тарифом <span class="caret"></span></button>
                      <ul class="dropdown-menu" role="menu">
                        <li><a id="showAdditionalPersonalTariff" href="#">Просмотреть данные тарифа</a></li>
                        <li><a id="editAdditionalPersonalTariff" href="#">Редактировать данные тарифа</a></li>
                        <li><a id="additionalIndivTariffOffBtn" href="#">Отключить индивидуальный тариф</a></li>
                      </ul>
                    </div>
                </td>
                </tr>
                ';
                }

                if ($cottageInfo->additionalCottageInfo['cottageInfo']->isPower) {
                    ?>

                    <tr class="info">
                        <td>Электроэнергия</td>
                        <td><?= $cottageInfo->additionalCottageInfo['cottageInfo']->powerDebt > 0 ? "<a class='btn btn-default detail-debt' data-type='power_additional' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['cottageInfo']->powerDebt) . " </b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
                    </tr>
                    <tr class="info">
                        <td>Электроэнергия- последний оплаченный месяц</td>
                        <td>
                            <b class="text-info"><?= TimeHandler::getFullFromShotMonth($cottageInfo->additionalCottageInfo['cottageInfo']->powerPayFor) ?></b> <?= $cottageInfo->additionalCottageInfo['powerStatus']['powerPayDifference'] ?>
                            <?php
                            if ($cottageInfo->additionalCottageInfo['cottageInfo']->partialPayedPower) {
                                // получу данные о неполном платеже
                                $dom = new DOMHandler($cottageInfo->additionalCottageInfo['cottageInfo']->partialPayedPower);
                                /** @var DOMElement $info */
                                $info = $dom->query('/partial')->item(0);
                                echo '<p><b class="text-info">' . TimeHandler::getFullFromShotMonth($info->getAttribute('date')) . '</b>: оплачено частично, <b class="text-success">' . CashHandler::toSmoothRubles($info->getAttribute('summ')) . '</b></p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr class="info">
                        <td>Электроэнергия- последние показания счётчика</td>
                        <td>
                            <b class="text-info"><?= $cottageInfo->additionalCottageInfo['cottageInfo']->currentPowerData ?>
                                кВт.ч</b>
                            <?= $cottageInfo->powerDataAdditionalCancellable ? "<button id='cancelFillAdditionalPower' class='btn btn-danger'>Удалить</button>" : '' ?>
                            <?= !$cottageInfo->additionalCottageInfo['powerStatus']['filledPower'] ? "<button id='fillAdditionalPower' class='btn btn-info'>Заполнить " . TimeHandler::getFullFromShotMonth(TimeHandler::getPreviousShortMonth()) . "</button>" : '' ?>
                        </td>
                    </tr>

                    <?php
                } else {
                    ?>
                    <tr class="info">
                        <td>Электроэнергия</td>
                        <td>Участок не электрифицирован</td>
                    </tr>

                    <?php
                }
                if ($cottageInfo->additionalCottageInfo['cottageInfo']->isMembership) {
                    ?>
                    <tr class="info">
                        <td>Членские взносы</td>
                        <td><?= $cottageInfo->additionalCottageInfo['membershipDebt'] > 0 ? "<a class='btn btn-default detail-debt' data-type='membership_additional' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['membershipDebt']) . "</b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
                    </tr>
                    <tr class="info">
                        <td>Членские взносы- последний оплаченный квартал</td>
                        <td>
                            <b class="text-info"><?= TimeHandler::getFullFromShortQuarter($cottageInfo->additionalCottageInfo['cottageInfo']->membershipPayFor) ?></b>
                            <?php
                            if ($cottageInfo->additionalCottageInfo['cottageInfo']->partialPayedMembership) {
                                // получу данные о неполном платеже
                                $dom = new DOMHandler($cottageInfo->additionalCottageInfo['cottageInfo']->partialPayedMembership);
                                $info = $dom->query('/partial')->item(0);
                                echo '<p><b class="text-info">' . TimeHandler::getFullFromShortQuarter($info->getAttribute('date')) . '</b>: оплачено частично, <b class="text-success">' . CashHandler::toSmoothRubles($info->getAttribute('summ')) . '</b></p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <tr class="info">
                        <td>Членские взносы</td>
                        <td>Членские взносы за участок не оплачиваются</td>
                    </tr>
                    <?php
                }
                if ($cottageInfo->additionalCottageInfo['cottageInfo']->isTarget) {
                    ?>
                    <tr class="info">
                        <td>Целевые платежи</td>
                        <td><?= $cottageInfo->additionalCottageInfo['targetDebt'] > 0 ? "<a class='btn btn-default detail-debt' data-type='target_additional' href='#'><b class='text-danger'>Задолженность " . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['targetDebt']) . "</b></a>" : "<b class='text-success'>Оплачено</b>" ?></td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <tr class="info">
                        <td>Целевые платежи</td>
                        <td>Целевые платежи за участок не оплачиваются</td>
                    </tr>

                    <?php
                }
                if ($differentOwner) {
                    if ($hasDoubleSingleDebt) {
                        // покажу задолженность по разовым платежам
                        echo '
                        <tr class="info">
                        <td>Разовые платежи</td>
                        <td><a class="btn btn-default detail-debt" data-type="single_additional" href="#"><b class="text-danger">Задолженность ' . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['cottageInfo']->singleDebt) . '</b></a></td>
                    </tr>
                        ';
                    } else {
                        echo '
                        <tr class="info">
                            <td>Разовые платежи</td>
                            <td>Задолженностей не найдено</td>
                        </tr>';

                    }
                    echo '
                     <tr class="info">
                        <td>Депозит участка</td>
                        <td><b class="text-info">' . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['cottageInfo']->deposit) . '</b></td>
                    </tr>
                    ';
                    $registrationNumber = $cottageInfo->additionalCottageInfo['cottageInfo']->cottageRegistrationInformation ? $cottageInfo->additionalCottageInfo['cottageInfo']->cottageRegistrationInformation : 'Не зарегистрирован';
                    echo '
                     <tr class="info">
                        <td>Кадастровый номер</td>
                        <td><b class="text-info">' . $registrationNumber . '</b></td>
                    </tr>
                    ';
                }
                $total = 0;
                if(!empty($cottageInfo->additionalCottageInfo['fines'])){
                    // есть задолженности по пени
                    foreach ($cottageInfo->additionalCottageInfo['fines'] as $fine) {
                        if ($fine->is_enabled) {
                            $total += CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ);
                        }
                    }
                    if ($total > 0) {
                        echo "<tr class='info'><td>Пени</td><td><button id='finesSummDouble' class='btn btn-danger'>" . CashHandler::toSmoothRubles($total) . "</button></td></tr>";
                        $cottageInfo->totalDebt += $total;
                    }
                }

                $fullDuty = CashHandler::toRubles($cottageInfo->additionalCottageInfo['totalDebt']) + CashHandler::toRubles($cottageInfo->totalDebt);
                ?>
                </tbody>
                <tr class="info">
                    <td>Итоговая задолженность дополнительного участка</td>
                    <td><?= $cottageInfo->additionalCottageInfo['totalDebt'] > 0 ? "<b class='text-danger'>" . CashHandler::toSmoothRubles($cottageInfo->additionalCottageInfo['totalDebt'] + $total) . "</b>" : "<b class='text-success'>Отсутствует</b>" ?></td>
                </tr>
                <tr class="info">
                    <td>Общая задолженность обоих участков</td>
                    <td><?= $fullDuty > 0 ? "<b class='text-danger'>" . CashHandler::toSmoothRubles($fullDuty) . "</b>" : "<b class='text-success'>Отсутствует</b>" ?></td>
                </tr>
                <tr class="info">
                    <td>Площадь дополнительного участка участка</td>
                    <td><b class="text-info"><?= $cottageInfo->additionalCottageInfo['cottageInfo']->cottageSquare ?>
                            м<sup>2</sup></b></td>
                </tr>
            </table>

            <?php

        }
        if (!empty($cottageInfo->counterChanged)) {
            echo "<div class='alert alert-info'>" . TimeHandler::getFullFromShotMonth($cottageInfo->counterChanged->changeMonth) . ": Заменён счётчик электроэнергии. <button id='discardCounterChange' class='btn btn-default' data-month='{$cottageInfo->counterChanged->changeMonth}'><span class='text-danger'>Отменить операцию</span></button></div>";
        }
        if (empty($cottageInfo->globalInfo->cottageHaveRights)) {
            echo "<div class='alert alert-warning'>Нет документов на участок.</div>";
        }
        if (empty($cottageInfo->globalInfo->cottageRegisterData)) {
            echo "<div class='alert alert-warning'>Нет данных для реестра.</div>";
        }
        if ($cottageInfo->unpayedBills) {
            if ($cottageInfo->unpayedBills->isPartialPayed) {
                echo "<div class='alert alert-warning'>Имеется частично оплаченный счёт.</div>";
            } else {
                echo "<div class='alert alert-danger'>Имеется неоплаченный счёт.</div>";
            }
        }
        ?>
        <div class="row">
            <div class="col-lg-6">
                <table class="table table-striped table-hover">
                    <caption>Информация о владельце</caption>
                    <tbody>
                    <tr>
                        <td>Владелец</td>
                        <td><?= $cottageInfo->globalInfo->cottageOwnerPersonals ?></td>
                    </tr>
                    <tr>
                        <td>Телефон владельца</td>
                        <td><?= $cottageInfo->globalInfo->cottageOwnerPhone ? "<a href='tel:{$cottageInfo->globalInfo->cottageOwnerPhone}'>{$cottageInfo->globalInfo->cottageOwnerPhone}<br/>
<a href='viber://chat?number={$cottageInfo->globalInfo->cottageOwnerPhone}'><img class='social-button' src='/graphics/viber.png' alt='viber icon'>" : 'Отсутствует' ?></td>
                    </tr>
                    <tr>
                        <td>Адрес электронной почты владельца</td>
                        <td><?= $cottageInfo->globalInfo->cottageOwnerEmail ? "<a href='mailto:{$cottageInfo->globalInfo->cottageOwnerEmail}'>{$cottageInfo->globalInfo->cottageOwnerEmail}" : 'Отсутствует' ?></td>
                    </tr>
                    <tr>
                        <td>Почтовый адрес</td>
                        <td><?= GrammarHandler::clearAddress($cottageInfo->globalInfo->cottageOwnerAddress) ?: 'Отсутствует' ?></td>
                    </tr>
                    <tr>
                        <td>Паспортные данные</td>
                        <td><?= $cottageInfo->globalInfo->passportData ?: 'Отсутствуют' ?></td>
                    </tr>
                    <tr>
                        <td>Информация о праве собственности</td>
                        <td><?= $cottageInfo->globalInfo->cottageRightsData ?: 'Отсутствует' ?></td>
                    </tr>
                    <tr>
                        <td>Информация о владельце</td>
                        <td><?= $cottageInfo->globalInfo->cottageOwnerDescription ?: 'Отсутствует' ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-6">
                <table class="table table-striped table-hover">
                    <caption>Информация о контактном лице</caption>
                    <tbody>
                    <?php
                    if (!empty($cottageInfo->globalInfo->cottageContacterPersonals)) {
                        ?>
                        <tr>
                            <td>Контактное лицо</td>
                            <td><?= $cottageInfo->globalInfo->cottageContacterPersonals ?></td>
                        </tr>
                        <tr>
                            <td>Телефон контактного лица</td>
                            <td><?= $cottageInfo->globalInfo->cottageContacterPhone ? "<a href='tel:{$cottageInfo->globalInfo->cottageContacterPhone}'>{$cottageInfo->globalInfo->cottageContacterPhone}" : 'Отсутствует' ?></td>
                        </tr>
                        <tr>
                            <td>Адрес электронной почты контактного лица</td>
                            <td><?= $cottageInfo->globalInfo->cottageContacterEmail ? "<a href='mailto:{$cottageInfo->globalInfo->cottageContacterEmail}'>{$cottageInfo->globalInfo->cottageContacterEmail}" : 'Отсутствует' ?></td>
                        </tr>
                        <?php
                    } else {
                        echo '<tr><td>Отсутствует</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="clearfix"></div>
            <?php
            if ($cottageInfo->globalInfo->haveAdditional && $cottageInfo->additionalCottageInfo['cottageInfo']->hasDifferentOwner) {
                $phone = $cottageInfo->additionalCottageInfo['cottageInfo']->cottageOwnerPhone;
                $email = $cottageInfo->additionalCottageInfo['cottageInfo']->cottageOwnerEmail;
                ?>

                <div class="col-lg-6">
                    <table class="table table-striped table-hover">
                        <caption>Информация о владельце дополнительного участка</caption>
                        <tbody>
                        <tr>
                            <td>Владелец</td>
                            <td><?= $cottageInfo->additionalCottageInfo['cottageInfo']->cottageOwnerPersonals ?></td>
                        </tr>
                        <tr>
                            <td>Телефон владельца</td>
                            <td><?= $phone ? "<a href='tel:$phone'>$phone<br/>
<a href='viber://chat?number=$phone'><img class='social-button' src='/graphics/viber.png' alt='viber image'>" : 'Отсутствует' ?></td>
                        </tr>
                        <tr>
                            <td>Адрес электронной почты владельца</td>
                            <td><?= $email ? "<a href='mailto:$email'>$email" : 'Отсутствует' ?></td>
                        </tr>
                        <tr>
                            <td>Почтовый адрес</td>
                            <td><?= GrammarHandler::clearAddress($cottageInfo->additionalCottageInfo['cottageInfo']->cottageOwnerAddress) ?: 'Отсутствует' ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <?php
            }
            ?>

        </div>

        <div class="btn-group dropup">
            <button class="btn btn-success dropdown-toggle" type="button"
                    data-toggle="dropdown"><?= $firstCottageName ?><span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <?php
                if ($differentOwner) {
                        echo '<li><a id="payForCottageButton" href="#">Оплатить</a></li>';
                    ?>
                    <li><a id="buttonShowPaymentsStory" href="#">История платежей</a></li>
                    <li><a id="changeInfoButton" href="#">Изменить данные</a></li>
                    <br/>
                    <?php
                }
                ?>
                <li><a id="createSinglePayButton" href="#">Создать разовый платёж</a></li>
                <li><a id="fillCurrentPowerMonth" href="#">Электроэнергия досрочно</a></li>
                <!-- <li><a id="changePowerCounter" href="#">Замена счётчика</a></li>-->
                <li><a id="sendNotificationBtn" href="#">Отправить напоминание о долгах</a></li>
                <li><a id="sendRegInfoNotificationBtn" href="#">Отправить регистрационные данные</a></li>
                <li><a id="showReports" href="#">Отчёт о платежах</a></li>
                <?php

                if ($hasSingleDebt) {
                    echo '<li><a id="editSinglesActivator" href="#">Редактировать разовые платежи</a></li>';
                }

                if (!$cottageInfo->globalInfo->individualTariff) {
                    echo '<br/>
                <li class="text-info"><a id="indivTariffBtn" href="#">Активировать индивидуальный тарифный план</a></li>';
                }
                ?>
                <li><a id="addToDepositActivator" href="#">Зачислить на депозит</a></li>
            </ul>
        </div>
        <?php
        if ($haveAdditional) {
            if ($differentOwner) {
                ?>
                <div class="btn-group dropup">
                    <button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown">Подучасток 2 <span
                                class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <?php
                        if ($cottageInfo->additionalCottageInfo['unpayedBills']) {
                            echo "<li><a id='handleDoubleUnpayedBtn' data-identificator='{$cottageInfo->additionalCottageInfo['unpayedBills']->id}' href='#'>Завершить оформление выставленного счёта</a></li>";
                        } else {
                            echo '<li><a id="payForDoubleCottageBtn" href="#">Оплатить</a></li>';
                        }
                        ?>

                        <li><a id="showDoublePaymentsStory" href="#">История платежей</a></li>
                        <?php
                        if (!$cottageInfo->additionalCottageInfo['cottageInfo']->individualTariff) {
                            echo "
                <li class='text-info'><a id='additionalIndivTariffBtn' href='#'>Активировать индивидуальный тарифный план</a></li>";
                        }
                        ?>
                        <li><a id="createSinglePayDoubleButton" href="#">Создать разовый платёж</a></li>
                        <?php
                        if ($hasDoubleSingleDebt) {
                            echo '<li><a id="editSinglesDoubleActivator" href="#">Редактировать разовые платежи</a></li>';
                        }
                        ?>
                        <li><a id="addToDepositDoubleActivator" href="#">Зачислить на депозит</a></li>
                    </ul>
                </div>

                <?php

            } else {
                ?>
                <div class="btn-group">
                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">Дополнительный
                        участок <span class="caret"></span></button>
                    <ul class="dropdown-menu">
                        <?php
                        if (!$cottageInfo->additionalCottageInfo['cottageInfo']->individualTariff) {
                            echo "<li><a id='additionalIndivTariffBtn' href='#'>Активировать индивидуальный тариф</a></li>";
                        }
                        ?>
                    </ul>
                </div>
                <?php
            }
        } else {
            ?>
            <button type="button" id="createAdditionalCottage" class="btn btn-info">Зарегистрировать дополнительный
                участок
            </button>
            <?php
        }
        ?>
        <div class="clearfix"></div>

        <?php
        if (!$differentOwner) {
            ?>
            <div class="col-lg-6">
                <h2>Основные действия</h2>
                <div class="btn-group-vertical">
                    <?php
                        echo "<button id='payForCottageButton' class='btn btn-success'>Оплатить</button>";
                    ?>
                    <button id="buttonShowPaymentsStory" class="btn btn-default">История платежей</button>
                    <button id="changeInfoButton" class="btn btn-info">Изменить данные</button>
                </div>
            </div>

            <?php

        }
        ?>

    </div>
</div>
