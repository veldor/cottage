<?php


namespace app\models\utils;


use app\models\Calculator;
use app\models\CashHandler;
use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\database\CottageSquareChanges;
use app\models\FinesHandler;
use app\models\interfaces\CottageInterface;
use app\models\MembershipHandler;
use app\models\PersonalTariff;
use app\models\SingleHandler;
use app\models\Table_cottages;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_target;
use app\models\tables\Table_penalties;
use app\models\TargetHandler;
use app\models\TimeHandler;
use Exception;

class CottageDutyReport
{
    private CottageInterface $cottage;
    private int $periodEnd;

    public string $fineDetails = '';
    public string $powerDetails = '';
    public string $membershipDetails = '';
    public string $targetDetails = '';
    public string $singleDetails = '';
    public float $fineAmount = 0;
    public float $powerAmount = 0;
    public float $membershipAmount = 0;
    public float $targetAmount = 0;
    public float $singleAmount = 0;

    /**
     * CottageDutyReport constructor.
     * @param $cottage CottageInterface
     * @param $periodEnd int
     */
    public function __construct($cottage, $periodEnd)
    {
        $this->cottage = $cottage;
        $this->periodEnd = $periodEnd;
        $this->calculate();
    }

    /**
     * @throws Exception
     */
    private function calculate(): void
    {
        //todo если появятся дополнительные участки с электроэнергией- тут нужно будет производить расчёты по ним
        if ($this->cottage->isMain()) {
            // последний месяц, за который считаются долги
            $lastMonth = TimeHandler::getShortMonthFromTimestamp($this->periodEnd);
            $accrued = 0;
// получу все месяцы оплаты до текущего
            $months = Table_power_months::find()->where(['<=', 'month', $lastMonth])->andWhere(['cottageNumber' => $this->cottage->cottageNumber])->all();
// если есть неоплаченные месяцы- для каждого из них предоставлю детализацию
            if (!empty($months)) {
                foreach ($months as $month) {
                    // получу начисленную сумму
                    $total = CashHandler::toRubles($month->totalPay);
                    if ($total > 0) {
                        // поищу оплаты по этому месяцу. Если их нет- заношу месяц в долги
                        $payed = Table_payed_power::find()->where(['month' => $month->month, 'cottageId' => $this->cottage->cottageNumber])->andWhere(['<=', 'paymentDate', $this->periodEnd])->all();
                        if (empty($payed)) {
                            $this->powerDetails .= $month->month . ' : ' . $total . "<br/>\n";
                            $accrued += $total;
                            // обработка пени
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
                        }
                        // проверю, не начислялись ли пени
                        $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'power', 'period' => $month->month]);
                        // ага, платёж просрочен. подсчитаю количество дней просрочки на момент конца периода
                        // определю срок оплаты
                        // если месяц не оплачен и прошел срок выплат- считаю пени
                        if (($savedFine !== null) && $savedFine->payUpLimit < $this->periodEnd && $savedFine->is_enabled) {
                            // посчитаю сумму пени
                            // посчитаю количество дней задолженности
                            try {
                                $dayDifference = TimeHandler::checkDayDifference($savedFine->payUpLimit, $this->periodEnd);
                                if ($dayDifference > 0) {
                                    // тут посчитаю общую сумму задолженности.
                                    //Если платежей по счёту не было- это просто 5% в день
                                    if (empty($payed)) {
                                        $finesPerDay = CashHandler::countPercent($total, FinesHandler::PERCENT);
                                        $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                        $this->fineDetails .= 'Э* ' . $month->month . ' : ' . $totalFines . "<br/>\n";
                                        $this->fineAmount += $totalFines;
                                    } else {
                                        // тут уже сложнее, придётся считать оплаты
                                        $fineAmount = CashHandler::toRubles($this->handlePeriodPayments($payed, $savedFine->payUpLimit, $total, $this->periodEnd));
                                        if ($fineAmount > 0) {
                                            $this->fineDetails .= 'Э* ' . $month->month . ' : ' . $fineAmount . "<br/>\n";
                                            $this->fineAmount += $fineAmount;
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                            }
                        }
                    }
                }
            }
            $this->powerAmount = $accrued;
        }

// членские
        $total = 0;
// получу окончательный квартал расчёта
        $lastQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($this->periodEnd));
// получу первый квартал расчёта
        $firstQuarterValue = MembershipHandler::getFirstPayedQuarter($this->cottage);
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
                // получу площадь участка на данный момент времени
                $cottageSquare = CottageSquareChanges::getQuarterSquare($this->cottage, $item);
                // получу начисление за квартал
                $accrued = Accruals_membership::getItem($this->cottage, $item);
                if($accrued !== null){
                    // найду оплаты за данный период
                    $payed = MembershipHandler::getPaysBefore($item, $this->cottage, $this->periodEnd);
                    if (empty($payed)) {
                        $this->membershipDetails .= $item . ' : ' . CashHandler::toRubles($accrued->getAccrual()) . "<br/>\n";
                        $total += $accrued->getAccrual();
                    } else {
                        $payedAmount = 0;
                        // если сумма оплаченного меньше суммы начисленного - добавляю месяц в детализацию
                        foreach ($payed as $pay) {
                            $payedAmount += CashHandler::toRubles($pay->summ);
                        }
                        $payedAmount = CashHandler::toRubles($payedAmount);
                        if ($payedAmount < $accrued->getAccrual()) {
                            $difference = CashHandler::toRubles($accrued->getAccrual() - $payedAmount);
                            $this->membershipDetails .= $item . ' : ' . $difference . "<br/>\n";
                            $total += $difference;
                        }
                    }
                    // проверю, не начислялись ли пени
                    $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->getCottageNumber(), 'pay_type' => 'membership', 'period' => $item]);
                    // ага, платёж просрочен. подсчитаю количество дней просрочки на момент конца периода
                    // определю срок оплаты
                    // если месяц не оплачен и прошел срок выплат- считаю пени
                    if (($savedFine !== null) && $savedFine->payUpLimit < $this->periodEnd && $savedFine->is_enabled) {
                        if($savedFine->locked){
                            $this->fineDetails .= 'Ч* ' . $item . ' : ' . $savedFine->summ . "<br/>\n";
                        }
                        else{
                            // посчитаю сумму пени
                            // посчитаю количество дней задолженности
                            try {
                                $dayDifference = TimeHandler::checkDayDifference($savedFine->payUpLimit, $this->periodEnd);
                                if ($dayDifference > 0) {
                                    // тут посчитаю общую сумму задолженности.
                                    //Если платежей по счёту не было- это просто 5% в день
                                    if (empty($payed)) {
                                        $finesPerDay = CashHandler::countPercent($accrued->getAccrual(), FinesHandler::PERCENT);
                                        $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                        $this->fineDetails .= 'Ч* ' . $item . ' : ' . $totalFines . "<br/>\n";
                                        $this->fineAmount += $totalFines;
                                    } else {
                                        // тут уже сложнее, придётся считать оплаты
                                        $fineAmount = CashHandler::toRubles($this->handlePeriodPayments($payed, $savedFine->payUpLimit, $total, $this->periodEnd));
                                        if ($fineAmount > 0) {
                                            $this->fineDetails .= 'Ч* ' . $item . ' : ' . $fineAmount . "<br/>\n";
                                            $this->fineAmount += $fineAmount;
                                        }
                                    }
                                }
                            } catch (Exception $e) {
                                echo $e->getMessage();
                                die();
                            }
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
        foreach ($yearsList as $year) {
            // получу данные
            // получу начисление за год
            $thisDuty = Accruals_target::getItem($this->cottage, $year);
            if($thisDuty !== null){
                $accrued = $thisDuty->countAmount();
            }
            if (!empty($accrued)) {
                // найду оплаты за данный период
                $payed = TargetHandler::getPaysBefore($year, $this->cottage, $this->periodEnd);
                if (empty($payed)) {
                    // возможно, оплата была до введения системы
                    // проверю, если год присутствует в задолженностях участка- значит он не оплачен, если нет- значит, оплачен ранее
                    if ($accrued - $thisDuty->payed_outside - $thisDuty->countPayed() > 0) {
                        $payed = $thisDuty->countPayed();
                        $unpayed = CashHandler::toRubles($accrued - $payed - $thisDuty->payed_outside);
                        if ($unpayed > 0) {
                            $this->targetDetails .= $year . ' : ' . $unpayed . "<br/>\n";
                            $total += $unpayed;
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
                }
                $this->targetAmount = CashHandler::toRubles($total);
                // проверю, не начислялись ли пени
                $savedFine = Table_penalties::findOne(['cottage_number' => $this->cottage->cottageNumber, 'pay_type' => 'target', 'period' => $year]);
                // ага, платёж просрочен. подсчитаю количество дней просрочки на момент конца периода
                // определю срок оплаты
                // если месяц не оплачен и прошел срок выплат- считаю пени
                if (($savedFine !== null) && $savedFine->payUpLimit < $this->periodEnd && $savedFine->is_enabled) {
                    if($savedFine->locked){
                        $this->fineDetails .= 'Ц* ' . $year . ' : ' . $savedFine->summ . "<br/>\n";
                    }
                    else{
                        // посчитаю сумму пени
                        // посчитаю количество дней задолженности
                        try {
                            $dayDifference = TimeHandler::checkDayDifference($savedFine->payUpLimit, $this->periodEnd);
                            if ($dayDifference > 0) {
                                // тут посчитаю общую сумму задолженности.
                                //Если платежей по счёту не было- это просто 5% в день
                                if (empty($payed)) {
                                    $finesPerDay = CashHandler::countPercent($thisDuty->getAccrual(), FinesHandler::PERCENT);
                                    $totalFines = CashHandler::toRubles($finesPerDay * $dayDifference);
                                    $this->fineDetails .= 'Ц* ' . $year . ' : ' . $totalFines . "<br/>\n";
                                    $this->fineAmount += $totalFines;
                                } else {
                                    // тут уже сложнее, придётся считать оплаты
                                    $fineAmount = CashHandler::toRubles($this->handlePeriodPayments($payed, $savedFine->payUpLimit, $total, $this->periodEnd));
                                    if ($fineAmount > 0) {
                                        $this->fineDetails .= 'Ц* ' . $year . ' : ' . $fineAmount . "<br/>\n";
                                        $this->fineAmount += $fineAmount;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                        }
                    }
                }
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
                    if (stripos($description, 'электроэнергии')) {
                        $this->singleDetails .= '(Э*) ' . $accrued;
                    }
                }
            }
        }
        $this->singleAmount = CashHandler::toRubles($accrued);
    }

    /**
     * @param $pays
     * @param int $payUp
     * @param $totalPay
     * @param $endPeriod
     * @return float
     * @throws Exception
     */
    private function handlePeriodPayments($pays, int $payUp, $totalPay, $endPeriod): float
    {
        $lastPayDate = null;
        $payed = 0;
        $fullAmount = 0;
        $lastPay = null;
        foreach ($pays as $pay) {
            // проверю, если дата платежа раньше ограничения- просто сохраню сумму платежа
            if ($pay->paymentDate < $payUp) {
                $payed = CashHandler::toRubles($payed + $pay->summ);
                $lastPayDate = $pay->paymentDate;
            } elseif ($pay->paymentDate < $endPeriod) {
                // платёж просрочен но оплачен в рамках периода
                //Если есть дата последнего платежа, считаю разницу дней от неё, иначе- от срока оплаты платежа
                if ($lastPayDate === null) {
                    $lastPayDate = $payUp;
                }
                // посчитаю количество просроченных дней
                $daysLeft = TimeHandler::checkDayDifference($lastPayDate, $pay->paymentDate);
                if ($daysLeft > 0) {
                    // добавлю сумму к пени
                    $fullAmount += FinesHandler::countFine($totalPay - $payed, $daysLeft);
                }
                $lastPayDate = $pay->paymentDate;
                $payed = CashHandler::toRubles($payed + $pay->summ);
                $lastPay = $pay;
            }
        }
        // если после всех платежей ещё остался долг- считаю начисления начиная с последней даты оплаты до этого дня
        if (CashHandler::toRubles($payed) < CashHandler::toRubles($totalPay)) {
            $daysLeft = TimeHandler::checkDayDifference($lastPayDate, $lastPay->paymentDate);
            if ($daysLeft > 0) {
                $fullAmount += FinesHandler::countFine($totalPay - $payed, $daysLeft);
            }
        }
        return $fullAmount;
    }
}