<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\ExceptionWithStatus;
use app\models\selections\PenaltyItem;
use app\models\Table_cottages;
use app\models\Table_payed_power;
use app\models\Table_power_months;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\TimeHandler;
use Exception;

class NewFinesHandler
{

    public const PERCENT = 0.5;
    /**
     * @var Table_cottages
     */
    private Table_cottages $cottageInfo;

    /**
     * NewFinesHandler constructor.
     * @param $cottageInfo Table_cottages
     */
    public function __construct(Table_cottages $cottageInfo)
    {
        $this->cottageInfo = $cottageInfo;
    }

    /**
     * Функция, которая будет расчитывать все возможные пени по участку
     *
     * Вызовет исключение, если за что-то оплачено больше, чем нужно
     * @throws ExceptionWithStatus
     * @throws Exception
     * @return PenaltyItem[] <b>Array of founded penalties</b>
     */
    public function getPowerFines(): array
    {
        $answer = [];
        // count power fines
        $powerAccruals = Table_power_months::getCottageAccruals($this->cottageInfo);
        if (!empty($powerAccruals)) {
            foreach ($powerAccruals as $powerAccrual) {
                // проверю, оплачен ли счёт вовремя
                // получу оплаты, если они были
                if ($powerAccrual->totalPay > 0) {
                    $pays = Table_payed_power::getPayed($powerAccrual);
                    $paymentTime = TimeHandler::getPayUpMonth($powerAccrual->month);
                    if (!empty($pays)) {
                        $payedSum = 0;
                        $payedForPeriodEnd = 0;
                        $previousPayDate = $paymentTime;
                        foreach ($pays as $pay) {
                            $payedSum = CashHandler::toRubles($payedSum + $pay->summ);
                            $difference = TimeHandler::checkDayDifference($previousPayDate, $pay->paymentDate);
                            if ($difference > 0) {
                                $newPenalty = new PenaltyItem();
                                $newPenalty->setType('Электроэнергия');
                                $newPenalty->setPeriod($powerAccrual->month);
                                $newPenalty->setCottageNumber($powerAccrual->cottageNumber);
                                $newPenalty->setPayUp($previousPayDate);
                                $newPenalty->setPayDate($pay->paymentDate);
                                // посчитаю оплату за день задолженности
                                $arrears = CashHandler::toRubles($powerAccrual->totalPay - $payedForPeriodEnd);
                                $newPenalty->setArrears($arrears);
                                $payPerDay = CashHandler::countPercent($arrears, self::PERCENT);
                                $newPenalty->setPayPerDay($payPerDay);
                                $newPenalty->setDayDifference($difference);
                                $totalAccrued = CashHandler::toRubles($difference * $payPerDay);
                                $newPenalty->setTotalAccrued($totalAccrued);
                                // проверю, зарегистрированы ли уже пени
                                $registeredFine = Table_penalties::findOne(['period' => $powerAccrual->month, 'cottage_number' => $powerAccrual->cottageNumber]);
                                if ($registeredFine !== null) {
                                    $newPenalty->setIsRegistered(true);
                                    $newPenalty->setIsActive($registeredFine->is_enabled);
                                    $newPenalty->setIsFullPayed($registeredFine->is_full_payed);
                                    $newPenalty->setIsLocked($registeredFine->locked);
                                    if ($registeredFine->locked) {
                                        $newPenalty->setLockedSum($registeredFine->summ);
                                    }
                                    $pays = Table_payed_fines::findAll(['fine_id' => $registeredFine->id]);
                                    if(!empty($pays)){
                                        $payed = 0;
                                        foreach ($pays as $finePay) {
                                            $payed = CashHandler::toRubles($payed + $finePay->summ);
                                        }
                                        $newPenalty->setThisFinePayedSum($payed);
                                    }
                                }
                                $answer[] = $newPenalty;
                                $previousPayDate = $pay->paymentDate;
                            }
                            $payedForPeriodEnd = CashHandler::toRubles($payedForPeriodEnd + $pay->summ);
                        }
                        // а вот если зарегистрировано оплата на большую сумму, чем начислено- это подозрительно, сообщу об этом
                        if ($payedSum > $powerAccrual->totalPay) {
                            throw new ExceptionWithStatus("Оплата за {$powerAccrual->month} по участку {$powerAccrual->cottageNumber} : {$payedSum} больше, чем начислено за месяц: {$powerAccrual->totalPay}");
                        }
                    } else {
                        $difference = TimeHandler::checkDayDifference($paymentTime);
                        $newPenalty = new PenaltyItem();
                        $newPenalty->setType('Электроэнергия');
                        $newPenalty->setPeriod($powerAccrual->month);
                        $newPenalty->setCottageNumber($powerAccrual->cottageNumber);
                        $newPenalty->setPayUp($paymentTime);
                        // посчитаю оплату за день задолженности
                        $arrears = CashHandler::toRubles($powerAccrual->totalPay);
                        $newPenalty->setArrears($arrears);
                        $payPerDay = CashHandler::countPercent($arrears, self::PERCENT);
                        $newPenalty->setPayPerDay($payPerDay);
                        $newPenalty->setDayDifference($difference);
                        $totalAccrued = CashHandler::toRubles($difference * $payPerDay);
                        $newPenalty->setTotalAccrued($totalAccrued);
                        // проверю, зарегистрированы ли уже пени
                        $registeredFine = Table_penalties::findOne(['period' => $powerAccrual->month, 'cottage_number' => $powerAccrual->cottageNumber]);
                        if ($registeredFine !== null) {
                            $newPenalty->setIsRegistered(true);
                            $newPenalty->setIsActive($registeredFine->is_enabled);
                            $newPenalty->setIsFullPayed($registeredFine->is_full_payed);
                            $newPenalty->setIsLocked($registeredFine->locked);
                            if ($registeredFine->locked) {
                                $newPenalty->setLockedSum($registeredFine->summ);
                            }
                        }
                        $answer[] = $newPenalty;
                    }
                }
            }
        }
        return $answer;
    }
}