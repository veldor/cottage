<?php


namespace app\models\utils;


use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\ExceptionWithStatus;
use app\models\PersonalTariff;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_target;
use app\models\TargetHandler;
use app\models\TimeHandler;
use RuntimeException;
use yii\base\Model;

class TotalDutyReport extends Model
{
    public const SCENARIO_CHOOSE_DATE = 'choose date';

    /**
     * Верну путь к файлу сохранения отчёта по электроэнергии
     * @return string
     */
    public static function getPowerFileName(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/files/cottage_report_power.xml';
    }

    /**
     * Верну путь к файлу сохранения отчёта по членским
     * @return string
     */
    public static function getMembershipFileName(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/files/cottage_report_membership.xml';
    }

    /**
     * Верну путь к файлу сохранения отчёта по целевым
     * @return string
     */
    public static function getTargetFileName(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/files/cottage_report_target.xml';
    }

    public static function getDIrName(): string
    {
        return dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/files';
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CHOOSE_DATE => ['date'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'date' => 'Дата формирования отчёта',
        ];
    }

    public function rules(): array
    {
        return [
            [['date'], 'required'],
        ];
    }

    public string $date = '';

    /**
     * Создам отчёт
     * @throws ExceptionWithStatus
     */
    public function createReport(): void
    {
        // ЭЛЕКТРИЧЕСТВО =======================================================================================
        $powerDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        $membershipDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        $targetDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        // переведу дату в timestamp
        $timestamp = strtotime($this->date);
        // создам папку для отчётов, если её не существует
        if (!is_dir(self::getDIrName()) && !mkdir($concurrentDirectory = self::getDIrName()) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        // получу данные по задолженностям на это число
        $cottages = Cottage::getRegistred();
        if (!empty($cottages) && count($cottages) > 0) {
            foreach ($cottages as $cottage) {
                $powerDetailsXml .= '<участок><номер>' . $cottage->cottageNumber . '</номер>';
                $membershipDetailsXml .= '<участок><номер>' . $cottage->cottageNumber . '</номер>';
                $targetDetailsXml .= '<участок><номер>' . $cottage->cottageNumber . '</номер>';
                $totalPowerDuty = 0;
                $powerDutyDetails = '';
                // тут раскладка по задолженностям на данный период
                // получу счета по электричеству для этого участка
                $powerBills = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->andWhere(['<', 'searchTimestamp', $timestamp])->all();
                if (!empty($powerBills)) {
                    $powerDutyDetails .= '<электричество_детали>';
                    foreach ($powerBills as $powerBill) {
                        // если сумма больше 0: найду оплаты по этому счёту
                        if ($powerBill->totalPay > 0) {
                            $dutyForMonth = CashHandler::toRubles($powerBill->totalPay);
                            $payments = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => $powerBill->month])->andWhere(['<', 'paymentDate', $timestamp])->all();
                            if (!empty($payments)) {
                                foreach ($payments as $payment) {
                                    $dutyForMonth = CashHandler::toRubles($dutyForMonth - $payment->summ);
                                }
                            }
                            if ($dutyForMonth > 0) {
                                $totalPowerDuty += $dutyForMonth;
                                $powerDutyDetails .= $powerBill->month . ': ' . CashHandler::toRubles($dutyForMonth) . " \r\n";
                            }
                        }
                    }
                    $powerDutyDetails .= '</электричество_детали>';
                }
                if ($totalPowerDuty > 0) {
                    $powerDetailsXml .= '<электричество_общая_задолженность>' . CashHandler::toRubles($totalPowerDuty) . '</электричество_общая_задолженность>' . $powerDutyDetails;
                }
                $powerDetailsXml .= '</участок>';
                // ЧЛЕНСКИЕ ВЗНОСЫ =====================================================================================
                $totalMembershipDuty = 0;
                $membershipDutyDetails = '';
                // нужно получить значение квартала, с которого начинается оплата
                // перенесу данные по членским взносам
                $payedMembership = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->orderBy('quarter')->all();
                if (empty($payedMembership)) {
                    // оплаты членских взносов не было- считаю долг с последнего оплаченного месяца по данным участка
                    $firstCountedQuarter = TimeHandler::getNextQuarter($cottage->membershipPayFor);
                } else {
                    $firstCountedQuarter = $payedMembership[0]->quarter;
                }
                $quartersList = TimeHandler::getQuartersList($firstCountedQuarter, TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($timestamp)));
                if (!empty($quartersList)) {
                    $membershipDutyDetails .= '<членские_взносы_детали>';
                    foreach ($quartersList as $quarter) {
                        // посчитаю, какая сумма должна быть оплачена
                        if ($cottage->individualTariff) {
                            $tariff = PersonalTariff::getMembershipRate($cottage, $quarter);
                            if ($tariff) {
                                $amount = Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $cottage->cottageSquare);
                            } else {
                                // если тарифы на данный квартал не заполнены- считаю по стандартным
                                $tariff = Table_tariffs_membership::findOne(['quarter' => $quarter]);
                                if ($tariff !== null) {
                                    $amount = Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                                } else {
                                    throw new ExceptionWithStatus("Не найдены данные по тарифу членских взносов за {$quarter}");
                                }
                            }
                        } else {
                            // получу тариф на данный квартал
                            $tariff = Table_tariffs_membership::findOne(['quarter' => $quarter]);
                            if ($tariff !== null) {
                                $amount = Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                            } else {
                                throw new ExceptionWithStatus("Не найдены данные по тарифу членских взносов за {$quarter}");
                            }
                        }
                        // найду оплаты за период
                        $payments = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber, 'quarter' => $quarter])->andWhere(['<', 'paymentDate', $timestamp])->all();
                        if (!empty($payments)) {
                            foreach ($payments as $payment) {
                                $amount = CashHandler::toRubles($amount - $payment->summ, true);
                            }
                        }
                        if ($amount > 0) {
                            $membershipDutyDetails .= $quarter . ': ' . CashHandler::toRubles($amount) . " \r\n";
                            $totalMembershipDuty += $amount;
                        }
                    }
                    $membershipDutyDetails .= '</членские_взносы_детали>';
                    if ($totalMembershipDuty > 0) {
                        $membershipDetailsXml .= '<членские_взносы_общая_задолженность>' . CashHandler::toRubles($totalMembershipDuty, true) . '</членские_взносы_общая_задолженность>' . $membershipDutyDetails;
                    }
                    // посчитаю переплаты
                    $overpays = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->andWhere(['<', 'paymentDate', $timestamp])->all();
                    $thisQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($timestamp));
                    if(!empty($overpays)){
                        $overpayDetails = '';
                        $overpayAmount = 0;
                        foreach ($overpays as $overpay) {
                            if($overpay->quarter > $thisQuarter){
                                $overpayAmount += $overpay->summ;
                                $overpayDetails .= $overpay->quarter . ': ' . CashHandler::toRubles($overpay->summ) . "\r\n";
                            }
                        }
                        if($overpayAmount > 0){
                            $membershipDetailsXml .= '<членские_взносы_переплата>' . CashHandler::toRubles($overpayAmount) . '</членские_взносы_переплата>' . '<членские_взносы_делали_переплаты>' . $overpayDetails . '</членские_взносы_делали_переплаты>';
                        }
                    }
                }
                $membershipDetailsXml .= '</участок>';
                // ЦЕЛЕВЫЕ ВЗНОСЫ ======================================================================================
                $totalTargetDuty = 0;
                $targetDutyDetails = '';
                // нужно получить год, с которого начинается оплата
                $payedTargets = Table_payed_target::find()->where(['cottageId' => $cottage->cottageNumber])->orderBy('year')->all();
                $targets = TargetHandler::getDebt($cottage);
                $firstPayedYear = 0;
                $firstDutyYear = 0;
                if(!empty($targets)){
                    // получу первый год задолженности
                    $firstDutyYear = array_key_first($targets);
                }
                if(!empty($payedTargets)){
                    $firstPayedYear = $payedTargets[0]->year;
                }
                $firstYear = 2014;
                if($firstDutyYear > 0 && $firstPayedYear > 0){
                    if($firstPayedYear > $firstDutyYear){
                        $firstYear = $firstDutyYear;
                    }
                    else{
                        $firstYear = $firstPayedYear;
                    }
                }
                elseif($firstDutyYear > 0){
                    $firstYear = $firstDutyYear;
                }
                elseif($firstPayedYear > 0){
                    $firstYear = $firstPayedYear;
                }
                // получу список лет по которым будут проводиться расчёты
                $yearsList = TimeHandler::getYearsList($firstYear, TimeHandler::getYearFromTimestamp($timestamp));
                if(!empty($yearsList)){
                    $targetDutyDetails .= '<целевые_взносы_детали>';
                    foreach ($yearsList as $year) {
                        // посчитаю, какая сумма должна быть оплачена
                        if ($cottage->individualTariff) {
                            $tariff = PersonalTariff::getTargetRate($cottage, $year);
                            if ($tariff) {
                                $amount = Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $cottage->cottageSquare);
                            } else {
                                // если тарифы на данный квартал не заполнены- считаю по стандартным
                                $tariff = Table_tariffs_target::findOne(['quarter' => $year]);
                                if ($tariff !== null) {
                                    $amount = Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $cottage->cottageSquare);
                                } else {
                                    // если тарифов нет-значит, они не назначены
                                    continue;
                                }
                            }
                        } else {
                            // получу тариф на данный год
                            $tariff = Table_tariffs_target::findOne(['year' => $year]);
                            if ($tariff !== null) {
                                $amount = Calculator::countFixedFloat($tariff->fixed_part, $tariff->float_part, $cottage->cottageSquare);
                            } else {
                                // если тарифов нет-значит, они не назначены
                                continue;
                            }
                        }
                        // найду оплаты за период
                        $payments = Table_payed_target::find()->where(['cottageId' => $cottage->cottageNumber, 'year' => $year])->andWhere(['<', 'paymentDate', $timestamp])->all();
                        if (!empty($payments)) {
                            foreach ($payments as $payment) {
                                $amount = CashHandler::toRubles($amount - $payment->summ, true);
                            }
                        }
                        if ($amount > 0) {
                            $targetDutyDetails .= $year . ': ' . CashHandler::toRubles($amount) . " \r\n";
                            $totalTargetDuty += $amount;
                        }
                    }
                    $targetDutyDetails .= '</целевые_взносы_детали>';
                    if ($totalTargetDuty > 0) {
                        $targetDetailsXml .= '<целевые_взносы_общая_задолженность>' . CashHandler::toRubles($totalTargetDuty, true) . '</целевые_взносы_общая_задолженность>' . $targetDutyDetails;
                    }

                    // посчитаю переплаты
                    $overpays = Table_payed_target::find()->where(['cottageId' => $cottage->cottageNumber])->andWhere(['>', 'paymentDate', $timestamp])->all();
                    if(!empty($overpays)){
                        $overpayDetails = '';
                        $overpayAmount = 0;
                        foreach ($overpays as $overpay) {
                            $overpayAmount += $overpay->summ;
                            $overpayDetails .= $overpay->year . ': ' . CashHandler::toRubles($overpay->summ) . "\r\n";
                        }
                        if($overpayAmount > 0){
                            $targetDetailsXml .= '<целевые_взносы_переплата>' . CashHandler::toRubles($overpayAmount) . '</целевые_взносы_переплата>' . '<целевые_взносы_делали_переплаты>' . $overpayDetails . '</целевые_взносы_делали_переплаты>';
                        }
                    }
                }
                $targetDetailsXml .= '</участок>';
            }
        }


        $powerDetailsXml .= '</задолженность>';
        $membershipDetailsXml .= '</задолженность>';
        $targetDetailsXml .= '</задолженность>';
        file_put_contents(self::getPowerFileName(), $powerDetailsXml);
        file_put_contents(self::getMembershipFileName(), $membershipDetailsXml);
        file_put_contents(self::getTargetFileName(), $targetDetailsXml);
    }

}