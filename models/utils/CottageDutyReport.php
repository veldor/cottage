<?php


namespace app\models\utils;


use app\models\Calculator;
use app\models\CashHandler;
use app\models\FinesHandler;
use app\models\PersonalTariff;
use app\models\SingleHandler;
use app\models\Table_cottages;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_target;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\TargetHandler;
use app\models\TimeHandler;

class CottageDutyReport
{
    private $cottage;
    private $periodEnd;

    public $fineDetails = '';
    public $powerDetails = '';
    public $membershipDetails = '';
    public $targetDetails = '';
    public $signleDetails = '';
    public $fineAmount = 0;
    public $powerAmount = 0;
    public $membershipAmount = 0;
    public $targetAmount = 0;
    public $singleAmount = 0;

    /**
     * CottageDutyReport constructor.
     * @param $cottage Table_cottages
     * @param $periodEnd int
     */
    public function __construct($cottage, $periodEnd)
    {
        $this->cottage = $cottage;
        $this->periodEnd = $periodEnd;
        $this->calculate();
    }

    private function calculate()
    {
        // определю конец периода
        $lastMonth = TimeHandler::getShortMonthFromTimestamp($this->periodEnd);
        $accrued = 0;
// получу все месяцы оплаты до текущего
        $months = Table_power_months::find()->where(['<=', 'month', $lastMonth])->andWhere(['cottageNumber' => $this->cottage->cottageNumber])->all();

// если есть неоплаченные месяцы- для каждого из них предоставлю детализацию
        if (!empty($months)) {
            foreach ($months as $month) {
                $total = CashHandler::toRubles($month->totalPay);
                if ($total > 0) {
                    // поищу оплаты по этому месяцу. Если их нет- заношу месяц в долги
                    $payed = Table_payed_power::find()->where(['month' => $month->month, 'cottageId' => $this->cottage->cottageNumber])->andWhere(['<=', 'paymentDate', $this->periodEnd])->all();
                    if (empty($payed)) {
                        $this->powerDetails .= $month->month . ' : ' . $total . "<br/>\n";
                        $accrued += $total;
                        // обработка пени
                        $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'power', 'period' => $month->month]);
                        if (!empty($savedFine) && $savedFine->is_enabled) {
                            // определю срок оплаты
                            $payUp = TimeHandler::getPayUpMonth($month->month);
                            if ($payUp < FinesHandler::START_POINT) {
                                $payUp = FinesHandler::START_POINT;
                            }
                            // если месяц не оплачен и прошел срок выплат- считаю пени
                            if ($payUp < $this->periodEnd) {
                                // посчитаю сумму пени
                                // посчитаю количество дней задолженности
                                $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                                if ($dayDifference > 0) {
                                    $finesPerDay = CashHandler::countPercent($total, FinesHandler::PERCENT);
                                    $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                    // теперь попробую найти оплату по данному пени
                                    if ($savedFine->is_enabled) {
                                        $payedFineAmount = $this->getPayedAmount($savedFine->id);
                                        $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                                        if ($fineDuty > 0) {
                                            $this->fineDetails .= "Э* " . $month->month . ' : ' . $fineDuty . "<br/>\n";
                                            $this->fineAmount += $fineDuty;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $payedAmount = 0;
                        // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
                        foreach ($payed as $pay) {
                            $payedAmount += CashHandler::toRubles($pay->summ);
                        }
                        $payedAmount = CashHandler::toRubles($payedAmount);
                        if ($payedAmount < $total) {
                            $difference = CashHandler::toRubles($total - $payedAmount);
                            $this->powerDetails .= $month->month . ' : ' . $difference . "<br/>\n";
                            $accrued += $difference;
                        }

                        $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'power', 'period' => $month->month]);
                        if (!empty($savedFine) && $savedFine->is_enabled) {
                            $totalFines = 0;
                            // найду факты оплаты счёта
                            $payUp = TimeHandler::getPayUpMonth($month->month);
                            if ($payUp < FinesHandler::START_POINT) {
                                $payUp = FinesHandler::START_POINT;
                            }
                            // если месяц не оплачен и прошел срок выплат- считаю пени
                            if ($payUp < $this->periodEnd) {
                                $pays = Table_payed_power::find()->where(['month' => $month->month, 'cottageId' => $this->cottage->cottageNumber])->all();$totalPayed = 0;
                                foreach ($pays as $pay) {
                                    $totalPayed += $pay->summ;
                                }
                                $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                                if($dayDifference > 0){
                                    $finesPerDay = CashHandler::countPercent($accrued - $totalPayed, FinesHandler::PERCENT);
                                    $countedFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                    $this->fineDetails .= "Ч* " . $month->month . ' : ' . $countedFines . "<br/>\n";
                                    $this->fineAmount += $countedFines;
                                }
                                /*$dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                                if ($dayDifference > 0) {
                                    // получу дату первого платежа
                                    if (!empty($pays)) {
                                        $previousDate = $payUp;
                                        $payDate = null;
                                        $spendDuty = $accrued;
                                        foreach ($pays as $pay) {
                                            // получу дату платежа
                                            $payDate = $pay->paymentDate;
                                            // если дата меньше, чем дата конца периода- она будет использована в расчёте пени
                                            $dayDiff = TimeHandler::checkDayDifference($previousDate, $payDate);
                                            if ($dayDiff < $this->periodEnd) {
                                                // посчитаю стоимость периода
                                                $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                                $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                                $spendDuty -= $pay->summ;
                                                $totalFines += $countedFines;
                                            } else {
                                                // обычный расчёт стоимости
                                                $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                                                $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                                break;
                                            }
                                            //
                                        }
                                        // проверю дату поледнего платежа- если она за пределами периода- ничего не делаю, если в пределах- расчитаю сумму, которую нужно заплатить
                                        if ($payDate < $this->periodEnd) {
                                            $dayDiff = TimeHandler::checkDayDifference($payDate, $this->periodEnd);
                                            $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                            $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                            $totalFines += $countedFines;
                                        }
                                        // посчитаю уже оплаченное
                                        $payed = $this->getPayedAmount($savedFine->id);
                                        $totalFines = CashHandler::toRubles($totalFines - $payed);
                                        if ($totalFines > 0) {
                                            $this->fineDetails .= "Э* " . $month->month . ' : ' . $totalFines . "<br/>\n";
                                            $this->fineAmount += $totalFines;
                                        }
                                    }
                                }*/
                            }
                        }
                    }
                }
            }
        }
        $this->powerAmount = $accrued;

// членские
        $total = 0;
// получу окончательный квартал расчёта
        $lastQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($this->periodEnd));
// получу первый квартал расчёта
        $firstQuarterValue = Table_payed_membership::find()->where(['cottageId' => $this->cottage->cottageNumber])->orderBy('quarter')->one();
        if (!empty($firstQuarterValue)) {
            $firstQuarter = $firstQuarterValue->quarter;
        } else {
            $firstQuarter = $this->cottage->membershipPayFor;
        }
// получу список кварталов
        $list = TimeHandler::getQuartersList($firstQuarter, $lastQuarter);
// обработаю список
        if (!empty($list)) {
            foreach ($list as $item) {
                // получу начисление за квартал
                if ($this->cottage->individualTariff) {
                    $tariff = PersonalTariff::getMembershipRate($this->cottage, $item);
                    $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $this->cottage->cottageSquare));
                } else {
                    $tariff = Table_tariffs_membership::findOne(['quarter' => $item]);
                    $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $this->cottage->cottageSquare));
                }
                // найду оплаты за данный период
                $payed = Table_payed_membership::find()->where(['quarter' => $item, 'cottageId' => $this->cottage->cottageNumber])->andWhere(['<=', 'paymentDate', $this->periodEnd])->all();
                if (empty($payed)) {
                    $this->membershipDetails .= $item . ' : ' . $accrued . "<br/>\n";
                    $total += $accrued;
                    $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'membership', 'period' => $item]);
                    if (!empty($savedFine) && $savedFine->is_enabled) {
                        $payUp = TimeHandler::getPayUpQuarterTimestamp($item);
                        if ($payUp < FinesHandler::START_POINT) {
                            $payUp = FinesHandler::START_POINT;
                        }
                        // если месяц не оплачен и прошел срок выплат- считаю пени
                        if ($payUp < $this->periodEnd) {
                            // посчитаю сумму пени
                            // посчитаю количество дней задолженности
                            $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                            if ($dayDifference > 0) {
                                $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                                $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                // теперь попробую найти оплату по данному пени
                                $payedFines = Table_payed_fines::find()->where(['fine_id' => $savedFine->id])->andWhere(['<', 'pay_date', $this->periodEnd])->all();
                                $payedFineAmount = 0;
                                if (!empty($payedFines)) {
                                    foreach ($payedFines as $payedFine) {
                                        $payedFineAmount += CashHandler::toRubles($payedFine->summ);
                                    }
                                }
                                $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                                if ($fineDuty > 0) {
                                    $this->fineDetails .= "Ч* " . $item . ' : ' . $fineDuty . "<br/>\n";
                                    $this->fineAmount += $fineDuty;
                                }

                            }
                        }
                    }
                } else {
                    $payedAmount = 0;
                    // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
                    foreach ($payed as $pay) {
                        $payedAmount += CashHandler::toRubles($pay->summ);
                    }
                    $payedAmount = CashHandler::toRubles($payedAmount);
                    if ($payedAmount < $accrued) {
                        $difference = CashHandler::toRubles($accrued - $payedAmount);
                        $this->membershipDetails .= $item . ' : ' . $difference . "<br/>\n";
                        $total += $difference;
                    }
                    // проверю наличие пени по платежу
                    $savedFine = Table_penalties::find()->where(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'membership', 'period' => $item])->one();
                    if (!empty($savedFine) && $savedFine->is_enabled && $savedFine->cottage_number === $this->cottage->cottageNumber) {
                        $totalFines = 0;
                        // найду факты оплаты счёта
                        $payUp = TimeHandler::getPayUpQuarterTimestamp($item);
                        if ($payUp < FinesHandler::START_POINT) {
                            $payUp = FinesHandler::START_POINT;
                        }
                        // если месяц не оплачен и прошел срок выплат- считаю пени
                        if ($payUp < $this->periodEnd) {
                            $pays = Table_payed_membership::find()->where(['quarter' => $item, 'cottageId' => $this->cottage->cottageNumber])->all();
                            $totalPayed = 0;
                            foreach ($pays as $pay) {
                                $totalPayed += $pay->summ;
                            }
                            $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                            if($dayDifference > 0){
                                $finesPerDay = CashHandler::countPercent($accrued - $totalPayed, FinesHandler::PERCENT);
                                $countedFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                $this->fineDetails .= "Ч* " . $item . ' : ' . $countedFines . "<br/>\n";
                                $this->fineAmount += $countedFines;
                            }
                            /*$dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                            if ($dayDifference > 0) {
                                // получу дату первого платежа
                                if (!empty($pays)) {
                                    $previousDate = $payUp;
                                    $payDate = null;
                                    $spendDuty = $accrued;
                                    foreach ($pays as $pay) {
                                        // получу дату платежа
                                        $payDate = $pay->paymentDate;
                                        // если дата меньше, чем дата конца периода- она будет использована в расчёте пени
                                        $dayDiff = TimeHandler::checkDayDifference($previousDate, $payDate);
                                        if ($dayDiff < $this->periodEnd) {
                                            // посчитаю стоимость периода
                                            $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                            $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                            $spendDuty -= $pay->summ;
                                            $totalFines += $countedFines;
                                        } else {
                                            // обычный расчёт стоимости
                                            $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                                            $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                            break;
                                        }
                                        //
                                    }
                                    // проверю дату поледнего платежа- если она за пределами периода- ничего не делаю, если в пределах- расчитаю сумму, которую нужно заплатить
                                    if ($payDate < $this->periodEnd) {
                                        $dayDiff = TimeHandler::checkDayDifference($payDate, $this->periodEnd);
                                        $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                        $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                        $totalFines += $countedFines;
                                    }
                                    // посчитаю уже оплаченное
                                    $payed = $this->getPayedAmount($savedFine->id);
                                    $totalFines = CashHandler::toRubles($totalFines - $payed);
                                    if ($totalFines > 0) {
                                        $this->fineDetails .= "Ч* " . $item . ' : ' . $totalFines . "<br/>\n";
                                        $this->fineAmount += $totalFines;
                                    }
                                }
                            }*/
                        }
                    }
                }
            }
            $this->membershipAmount = CashHandler::toRubles($total);
        }
// целевые
        $total = 0;
// получу первый и последний годы расчёта
        $lastYear = TimeHandler::getYearFromTimestamp($this->periodEnd);

        $firstYear = Table_tariffs_target::find()->orderBy('year')->one()->year;

//  получу список лет
        $yearsList = TimeHandler::getYearsList($firstYear, $lastYear);
        $existentTargets = TargetHandler::getDebt($this->cottage);
        foreach ($yearsList as $year) {
            // получу данные
            // получу начисление за квартал
            if ($this->cottage->individualTariff) {
                $tariff = PersonalTariff::getTargetRate($this->cottage, $year);
                if (!empty($tariff)) {
                    $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $this->cottage->cottageSquare));
                }
            } else {
                $tariff = Table_tariffs_target::findOne(['year' => $year]);
                if (!empty($tariff)) {
                    $accrued = CashHandler::toRubles(Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $this->cottage->cottageSquare));
                }
            }
            if (!empty($accrued)) {
                // найду оплаты за данный период
                $payed = Table_payed_target::find()->where(['year' => $year, 'cottageId' => $this->cottage->cottageNumber])->andWhere(['<=', 'paymentDate', $this->periodEnd])->all();
                if (empty($payed)) {
                    // возможно, оплата была до введения системы
                    // проверю, если год присутствует в задолженностях участка- значит он не оплачен, если нет- значит, оплачен ранее
                    if (!empty($existentTargets[$year])) {
                        $payed = $existentTargets[$year]->partialPayed;
                        $unpayed = CashHandler::toRubles($accrued - $payed);
                        if ($unpayed > 0) {
                            $this->targetDetails .= $year . ' : ' . $unpayed . "<br/>\n";
                            $total += $unpayed;
                        }
                        $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'target', 'period' => $year]);
                        if (!empty($savedFine) && $savedFine->is_enabled) {
                            $payUp = Table_tariffs_target::find()->where(['year' => $year])->one()->payUpTime;
                            if ($payUp < FinesHandler::START_POINT) {
                                $payUp = FinesHandler::START_POINT;
                            }
                            // если месяц не оплачен и прошел срок выплат- считаю пени
                            if ($payUp < $this->periodEnd) {
                                // посчитаю сумму пени
                                // посчитаю количество дней задолженности
                                $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                                if ($dayDifference > 0) {
                                    $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                                    $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                    // теперь попробую найти оплату по данному пени

                                    $payedFines = Table_payed_fines::find()->where(['fine_id' => $savedFine->id])->andWhere(['<', 'pay_date', $this->periodEnd])->all();
                                    $payedFineAmount = 0;
                                    if (!empty($payedFines)) {
                                        foreach ($payedFines as $payedFine) {
                                            $payedFineAmount += CashHandler::toRubles($payedFine->summ);
                                        }
                                    }
                                    $fineDuty = CashHandler::toRubles($totalFines - $payedFineAmount);
                                    if ($fineDuty > 0) {
                                        $this->fineDetails .= "Ц* " . $year . ' : ' . $fineDuty . "<br/>\n";
                                        $this->fineAmount += $fineDuty;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $payedAmount = 0;
                    // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
                    foreach ($payed as $pay) {
                        $payedAmount += CashHandler::toRubles($pay->summ);
                    }
                    $payedAmount = CashHandler::toRubles($payedAmount);
                    if ($payedAmount < $accrued) {
                        // проверю, есть ли задолженность в таблице, если нет- пропущу
                        $dutyInTable = TargetHandler::getDebt($this->cottage);
                        if (!empty($dutyInTable[$year])) {
                            $difference = CashHandler::toRubles($accrued - $payedAmount);
                            $this->targetDetails .= $year . ' : ' . $difference . "<br/>\n";
                            $total += $difference;
                        }
                    }
                    // проверю наличие пени по платежу
                    $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'target', 'period' => $year]);
                    if (!empty($savedFine) && $savedFine->is_enabled) {
                        $totalFines = 0;
                        // найду факты оплаты счёта
                        $payUp = Table_tariffs_target::find()->where(['year' => $year])->one()->payUpTime;
                        if ($payUp < FinesHandler::START_POINT) {
                            $payUp = FinesHandler::START_POINT;
                        }
                        // если месяц не оплачен и прошел срок выплат- считаю пени
                        if ($payUp < $this->periodEnd) {
                            $pays = Table_payed_target::find()->where(['year' => $year, 'cottageId' => $this->cottage->cottageNumber])->all();
                            $totalPayed = 0;
                            foreach ($pays as $pay) {
                                $totalPayed += $pay->summ;
                            }
                            $dayDifference = TimeHandler::checkDayDifference($payUp, $this->periodEnd);
                            if($dayDifference > 0){
                                $finesPerDay = CashHandler::countPercent($accrued - $totalPayed, FinesHandler::PERCENT);
                                $countedFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                $this->fineDetails .= "Ц* " . $year . ' : ' . $countedFines . "<br/>\n";
                                $this->fineAmount += $countedFines;
                            }
                            /*if ($dayDifference > 0) {
                                // получу дату первого платежа
                                if (!empty($pays)) {
                                    $previousDate = $payUp;
                                    $payDate = null;
                                    $spendDuty = $accrued;
                                    foreach ($pays as $pay) {
                                        $beginSpend = $spendDuty;
                                        // получу дату платежа
                                        $payDate = $pay->paymentDate;
                                        // если дата меньше, чем дата конца периода- она будет использована в расчёте пени
                                        $dayDiff = TimeHandler::checkDayDifference($previousDate, $payDate);
                                        if ($dayDiff < $this->periodEnd) {
                                            // посчитаю стоимость периода
                                            $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                            $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                            $spendDuty -= $pay->summ;
                                            $totalFines += $countedFines;
                                        } else {
                                            // обычный расчёт стоимости
                                            $finesPerDay = CashHandler::countPercent($accrued, FinesHandler::PERCENT);
                                            $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                            break;
                                        }
                                        echo "Период: с " . TimeHandler::getDateFromTimestamp($previousDate) . " по " . TimeHandler::getDateFromTimestamp($payDate) . ", итого " . $dayDiff . " дней, расчётная стоимость: {$beginSpend}, переплата в день: {$finesPerDay} на общую сумму: {$countedFines}<br/>";
                                        //
                                        $previousDate = $payDate;
                                    }
                                    // проверю дату поледнего платежа- если она за пределами периода- ничего не делаю, если в пределах- расчитаю сумму, которую нужно заплатить
                                    if ($payDate < $this->periodEnd) {
                                        $dayDiff = TimeHandler::checkDayDifference($payDate, $this->periodEnd);
                                        $finesPerDay = CashHandler::countPercent($spendDuty, FinesHandler::PERCENT);
                                        $countedFines = CashHandler::toRubles($finesPerDay * $dayDiff);
                                        echo "Период: с " . TimeHandler::getDateFromTimestamp($previousDate) . " по " . TimeHandler::getDateFromTimestamp($this->periodEnd) . ", итого " . $dayDiff . " дней, расчётная стоимость: {$spendDuty}, переплата в день: {$finesPerDay} на общую сумму: {$countedFines}<br/>";
                                        $totalFines += $countedFines;
                                    }
                                    // посчитаю уже оплаченное
                                    $payed = $this->getPayedAmount($savedFine->id);
                                    $totalFines = CashHandler::toRubles($totalFines - $payed);
                                    if ($totalFines > 0) {
                                        $this->fineDetails .= "Ц* " . $year . ' : ' . $totalFines . "<br/>\n";
                                        $this->fineAmount += $totalFines;
                                    }
                                    echo "Просрочено дней всего: ". $dayDifference . "<br/>";
                                    echo "Пени всего: " . $totalFines;
                                }
                            }*/
                        }
                    }

                }
                $this->targetAmount = CashHandler::toRubles($total);
            }
        }

        $accrued = 0;
// получу разовые платежи по участку
        $duties = SingleHandler::getDebtReport($this->cottage);
// если дата задолженности раньше конца периода- считаю в задолженность
        if (!empty($duties)) {
            foreach ($duties as $duty) {
                if ($duty->time < $this->periodEnd) {
                    // проверю назначение платежа
                    $description = urldecode($duty->description);
                    $accrued += CashHandler::toRubles(CashHandler::toRubles($duty->amount) - CashHandler::toRubles($duty->partialPayed));
                    if (stripos($description, "электроэнергии")) {
                        $this->signleDetails .= "(Э*) " . $accrued;
                    }
                }
            }
        }
        $this->singleAmount = CashHandler::toRubles($accrued);
    }

    private function getPayedAmount($fineId)
    {
        $payedFines = Table_payed_fines::find()->where(['fine_id' => $fineId])->andWhere(['<', 'pay_date', $this->periodEnd])->all();
        $payedFineAmount = 0;
        if (!empty($payedFines)) {
            foreach ($payedFines as $payedFine) {
                $payedFineAmount += CashHandler::toRubles($payedFine->summ);
            }
        }
        return $payedFineAmount;
    }
}