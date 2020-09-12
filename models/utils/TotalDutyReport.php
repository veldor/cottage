<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\Cottage;
use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\ExceptionWithStatus;
use app\models\MembershipHandler;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
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
     */
    public function createReport(): void
    {
        // ЭЛЕКТРИЧЕСТВО =======================================================================================
        $powerDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        $membershipDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        $targetDetailsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><задолженность>';
        // переведу дату в timestamp и прибавлю 23 часа, чтобы захватить всех на эту дату
        $timestamp = strtotime($this->date) + (60 * 60 * 23);
        // создам папку для отчётов, если её не существует
        if (!is_dir(self::getDIrName()) && !mkdir($concurrentDirectory = self::getDIrName()) && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        // получу данные по задолженностям на это число
        $cottages = Cottage::getRegister();
        $additionalCottages = Cottage::getRegister(true);
        $cottages = array_merge($cottages, $additionalCottages);
        if (!empty($cottages) && count($cottages) > 0) {
            foreach ($cottages as $cottage) {
                if ($cottage->getCottageNumber() === '0') {
                    continue;
                }
                $powerDetailsXml .= '<участок><номер>' . $cottage->cottageNumber . '</номер>';
                $membershipCottageDetails = '<участок><номер>' . $cottage->cottageNumber . '</номер>';
                $targetCottageDetails = '<участок><номер>' . $cottage->cottageNumber . '</номер>';
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

                //новый расчёт.
                // посчитаю квартал, по который считается задолженность
                $lastQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($timestamp));
                // получу начисления включая данный квартал
                /** @var Accruals_membership[] $accruals */
                $accruals = Accruals_membership::find()->where(['cottage_number' => $cottage->getCottageNumber()])->andWhere(['<=', 'quarter', $lastQuarter])->all();
                // посчитаю оплаты по каждому периоду
                if ($accruals !== null) {
                    $membershipDutyDetails .= '<членские_взносы_детали>';
                    foreach ($accruals as $accrual) {
                        $accrualTotal = $accrual->getAccrual();
                        if ($accrualTotal > 0) {
                            $payedTotal = MembershipHandler::getPeriodPaysAmount($cottage->getCottageNumber(), $accrual->quarter);
                            if ($payedTotal < $accrualTotal) {
                                $duty = $accrualTotal - $payedTotal;
                                $totalMembershipDuty += $duty;
                                $membershipDutyDetails .= $accrual->quarter . ': ' . CashHandler::toRubles($duty) . " \r\n";
                            }
                        }
                    }
                    $membershipDutyDetails .= '</членские_взносы_детали>';
                }
                if ($totalMembershipDuty > 0) {
                    $membershipCottageDetails .= '<членские_взносы_общая_задолженность>' . CashHandler::toRubles($totalMembershipDuty, true) . '</членские_взносы_общая_задолженность>' . $membershipDutyDetails;
                }
                // посчитаю переплаты
                $overpays = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->andWhere(['<', 'paymentDate', $timestamp])->all();
                $thisQuarter = TimeHandler::quarterFromYearMonth(TimeHandler::getShortMonthFromTimestamp($timestamp));
                if (!empty($overpays)) {
                    $overpayDetails = '';
                    $overpayAmount = 0;
                    foreach ($overpays as $overpay) {
                        if ($overpay->quarter > $thisQuarter) {
                            $overpayAmount += $overpay->summ;
                            $overpayDetails .= $overpay->quarter . ': ' . CashHandler::toRubles($overpay->summ) . "\r\n";
                        }
                    }
                    if ($overpayAmount > 0) {
                        $membershipCottageDetails .= '<членские_взносы_переплата>' . CashHandler::toRubles($overpayAmount) . '</членские_взносы_переплата>' . '<членские_взносы_делали_переплаты>' . $overpayDetails . '</членские_взносы_делали_переплаты>';
                    }
                }
                $membershipCottageDetails .= '</участок>';
                if ($totalMembershipDuty > 0 || $overpayAmount > 0) {
                    $membershipDetailsXml .= $membershipCottageDetails;
                }
                // ЦЕЛЕВЫЕ ВЗНОСЫ ======================================================================================
                $totalTargetDuty = 0;
                $targetDutyDetails = '';

                //новый расчёт.
                // получу год
                $lastYear = TimeHandler::getYearFromTimestamp($timestamp);
                // получу начисления включая данный год
                /** @var Accruals_target[] $accruals */
                $accruals = Accruals_target::find()->where(['cottage_number' => $cottage->getCottageNumber()])->andWhere(['<=', 'year', $lastQuarter])->all();
                // посчитаю оплаты по каждому периоду
                if ($accruals !== null) {
                    $targetDutyDetails .= '<целевые_взносы_детали>';
                    foreach ($accruals as $accrual) {
                        $accrualTotal = $accrual->getAccrual();
                        if ($accrualTotal > 0) {
                            $payedTotal = TargetHandler::getPeriodPaysAmount($cottage->getCottageNumber(), $accrual->year) + $accrual->payed_outside;
                            if ($payedTotal < $accrualTotal) {
                                $duty = $accrualTotal - $payedTotal;
                                $totalTargetDuty += $duty;
                                $targetDutyDetails .= $accrual->year . ': ' . CashHandler::toRubles($duty) . " \r\n";
                            }
                        }
                    }
                    $targetDutyDetails .= '</целевые_взносы_детали>';
                }
                if ($totalTargetDuty > 0) {
                    $targetCottageDetails .= '<целевые_взносы_общая_задолженность>' . CashHandler::toRubles($totalTargetDuty, true) . '</целевые_взносы_общая_задолженность>' . $targetDutyDetails;
                }
                $targetCottageDetails .= '</участок>';
                if ($totalTargetDuty > 0) {
                    $targetDetailsXml .= $targetCottageDetails;
                }
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