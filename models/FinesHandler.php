<?php

namespace app\models;


use app\models\database\Accruals_membership;
use app\models\selections\MembershipDebt;
use app\models\tables\Table_payed_fines;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use Exception;
use yii\base\Model;

class FinesHandler extends Model
{

    public static array $types = ['membership' => 'членские взносы', 'target' => 'целевые взносы', 'power' => 'электроэнергия'];

    public const PERCENT = 0.5;
    public const START_POINT = 1561939201;

    /**
     * Получение списка всех пени по номеру участка
     * @param $cottageNumber
     * @return Table_penalties[]
     */
    public static function getFines($cottageNumber): array
    {
        return Table_penalties::find()->where(['cottage_number' => $cottageNumber])->orderBy(['pay_type' => SORT_ASC, 'period' => SORT_ASC,])->all();
    }

    /**
     * @param $cottageNumber
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public static function check($cottageNumber): void
    {
        $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
        self::checkCottage($cottageInfo);
        if ($cottageInfo->haveAdditional) {
            $cottageNumber .= '-a';
            // расчитаю пени для дополнительного участка
            $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
            self::checkCottage($cottageInfo);
        }
    }

    public static function disableFine($finesId): array
    {
        $fine = Table_penalties::findOne($finesId);
        if ($fine === null) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 0;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').removeClass('hidden');$('#fines_{$fine->id}_disable').addClass('hidden');$('#finesSumm').html('" . CashHandler::toSmoothRubles($summ) . "');</script>";
        return ['status' => 1, 'message' => 'Пени за период не расчитываются' . $js];
    }

    public static function enableFine($finesId): array
    {
        $fine = Table_penalties::findOne($finesId);
        if ($fine === null) {
            return ['status' => 2, 'message' => 'Пени за данный период не найдены'];
        }
        $fine->is_enabled = 1;
        $fine->save();
        $summ = self::getFinesSumm($fine->cottage_number);
        $js = "<script>$('#fines_{$fine->id}_enable').addClass('hidden');$('#fines_{$fine->id}_disable').removeClass('hidden');$('#finesSumm').html('" . CashHandler::toSmoothRubles($summ) . "');</script>";
        return ['status' => 1, 'message' => 'Пени за период расчитываются' . $js];
    }

    public static function getFinesSumm($cottageId): float
    {
        $summ = 0;
        $fines = Table_penalties::find()->where(['cottage_number' => $cottageId])->all();
        if (!empty($fines)) {
            /** @var Table_penalties $fine */
            foreach ($fines as $fine) {
                if ($fine->is_enabled) {
                    $summ += CashHandler::toRubles($fine->summ) - CashHandler::toRubles($fine->payed_summ);
                }
            }
        }
        return CashHandler::toRubles($summ);
    }

    /**
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $paymentSumm double
     * @param $transaction Table_transactions|Table_transactions_double
     * @throws ExceptionWithStatus
     */
    public static function handlePartialPayment($bill, $paymentSumm, $transaction): void
    {
        // найду пени
        $fines = Table_view_fines_info::find()->where(['bill_id' => $bill->id])->all();
        /** @var Table_view_fines_info $fine */
        foreach ($fines as $fine) {
            // определю, какая сумма нужна для погашения платежа
            $summToPay = CashHandler::toRubles(CashHandler::toRubles($fine->start_summ) - $fine->payed_summ);
            if ($summToPay > 0) {
                // найду главную запись пени
                $fineInfo = Table_penalties::findOne($fine->fines_id);
                if ($fineInfo !== null) {
                    // если введённой суммы хватает для погашения- регистрирую оплату, если меньше
                    if ($paymentSumm > $summToPay) {
                        $paymentSumm -= $summToPay;
                        // оплачиваю полностью
                        self::registerPay($transaction, $fine, $summToPay, $fineInfo);
                    } else {
                        self::registerPay($transaction, $fine, $paymentSumm, $fineInfo);
                        break;
                    }
                } else {
                    throw new ExceptionWithStatus('Не найдена основная запись');
                }
            }
        }
    }

    /**
     * @param int|null $cottageNumber
     * @param $total
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    public static function recalculateFines($cottageNumber, $total): void
    {
        $time = time();
        // получу список участков
        if ($cottageNumber === null) {
            $cottages = Cottage::getRegister();
        } else {
            $cottages = Table_cottages::findAll(['cottageNumber' => $cottageNumber]);
        }
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                // получу все данные по электроэнергии
                $registeredPowerData = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->all();
                if (!empty($registeredPowerData)) {
                    /** @var Table_power_months $registeredPowerDatum */
                    foreach ($registeredPowerData as $registeredPowerDatum) {
                        $payUp = self::getPayUp('month', $registeredPowerDatum->month);
                        if ($registeredPowerDatum->totalPay > 0) {
                            // поищу оплаты по этому месяцу
                            $pays = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => $registeredPowerDatum->month])->all();
                            // если платежей за период не было- тогда платёж не оплачен до сих пор, проверю, не просрочен ли он, если просрочен- просто посчитаю сумму просрочки по обычной формуле
                            if (empty($pays)) {
                                if ($payUp < $time) {
                                    $fineAmount = self::countFine($registeredPowerDatum->totalPay, TimeHandler::checkDayDifference($payUp));
                                    // пересчитаю пени
                                    self::setPowerFineData($cottage, $registeredPowerDatum, $fineAmount, $payUp, $total);
                                }
                            } else {
                                // тут нужно проверить, были ли платежи проведены в отведённое время или просрочены
                                $fullAmount = self::handlePeriodPayments($pays, $payUp, $registeredPowerDatum->totalPay);
                                // если начислено пени- сохраню его
                                if ($fullAmount > 0) {
                                    // обновлю данные по пени
                                    self::setPowerFineData($cottage, $registeredPowerDatum, $fullAmount, $payUp, $total);
                                }
                            }
                        }
                    }
                }
                // теперь пересчитаю данные по членским взносам
                $firstCountedQuarter = MembershipHandler::getFirstFilledQuarter($cottage);
                $quarterList = TimeHandler::getQuarterList($firstCountedQuarter);
                if (!empty($quarterList)) {
                    foreach ($quarterList as $key => $value) {
                        $payUp = self::getPayUp('quarter', $key);
                        $tariff = MembershipHandler::getCottageTariff($key);
                        $totalPay = Calculator::countFixedFloat($tariff->fixed, $tariff->float, $cottage->cottageSquare);
                        // поищу оплаты по кварталу
                        $pays = Table_payed_membership::findAll(['cottageId' => $cottage->cottageNumber, 'quarter' => $key]);
                        if (empty($pays)) {
                            if ($payUp < $time) {
                                $fineAmount = self::countFine($totalPay, TimeHandler::checkDayDifference($payUp));
                                // пересчитаю пени
                                self::setMembershipFineData($cottage, $key, $fineAmount, $payUp, $total);
                            }
                        } else {
                            $fullAmount = self::handlePeriodPayments($pays, $payUp, $totalPay);
                            // если начислено пени- сохраню его
                            if ($fullAmount > 0) {
                                // обновлю данные по пени
                                self::setMembershipFineData($cottage, $key, $fullAmount, $payUp, $total);
                            }
                        }
                    }
                }
                // получу полные сведения о целевых взносах по участку
                $targetInfo = TargetHandler::getTargetInfo($cottage);
                if (!empty($targetInfo)) {
                    foreach ($targetInfo as $item) {
                        // получу тариф на год
                        $targetTariff = Table_tariffs_target::findOne(['year' => $item->year]);
                        if ($targetTariff !== null) {
                            $payUp = self::getPayUp('year', $targetTariff->payUpTime);
                            $pays = Table_payed_target::findAll(['cottageId' => $cottage->cottageNumber, 'year' => $item->year]);
                            if (empty($pays)) {
                                if ($payUp < $time) {
                                    $fineAmount = self::countFine($item->amount, TimeHandler::checkDayDifference($payUp));
                                    // пересчитаю пени
                                    self::setTargetFineData($cottage, $item->year, $fineAmount, $payUp, $total);
                                }
                            } else {
                                $fullAmount = self::handlePeriodPayments($pays, $payUp, $item->amount);
                                // если начислено пени- сохраню его
                                if ($fullAmount > 0) {
                                    // обновлю данные по пени
                                    self::setTargetFineData($cottage, $item->year, $fullAmount, $payUp, $total);
                                }
                            }
                        } else {
                            throw new ExceptionWithStatus('Не удалось найти тарифы целевых взносов за ' . $item->year);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $totalPay
     * @param $daysLeft
     * @return float|int
     */
    public static function countFine(string $totalPay, $daysLeft)
    {
        $perDay = CashHandler::countPercent($totalPay, self::PERCENT);
        return $perDay * $daysLeft;
    }

    /**
     * Добавление пени
     * @param $cottageNumber <p>Номер участка</p>
     * @param $type <p>Тип платежа</p>
     * @param $period <p>Период оплаты</p>
     * @param $payUpLimit <p>Срок оплаты</p>
     * @param $amount <p>Сумма пени</p>
     */
    private static function createFine($cottageNumber, $type, $period, $payUpLimit, $amount): void
    {
        $newFine = new Table_penalties();
        $newFine->cottage_number = $cottageNumber;
        $newFine->pay_type = $type;
        $newFine->period = $period;
        $newFine->payUpLimit = $payUpLimit;
        $newFine->payed_summ = 0;
        $newFine->is_full_payed = 0;
        $newFine->is_partial_payed = 0;
        $newFine->is_enabled = 1;
        $newFine->summ = CashHandler::toRubles($amount);
        $newFine->save();
    }

    /**
     * Получение срока оплаты перида
     * @param string $type <p>Тип периода</p>
     * @param string $period <p>Период</p>
     * @return int <p>Метка времени срока оплаты</p>
     * @throws ExceptionWithStatus
     */
    private static function getPayUp(string $type, string $period): int
    {
        switch ($type) {
            case 'month':
                $payUp = TimeHandler::getPayUpMonth($period);
                break;
            case 'quarter':
                $payUp = TimeHandler::getPayUpQuarterTimestamp($period);
                break;
            case 'year':
                $payUp = $period;
                break;
            default:
                throw new ExceptionWithStatus('Неизвестный тип оплаты ' . $type);
        }
        if ($payUp < self::START_POINT) {
            $payUp = self::START_POINT;
        }
        return $payUp;
    }

    /**
     * Обновление информации о пени
     * @param $cottage <p>Номер участка</p>
     * @param $registeredPowerDatum <p>Данные о потреблении электроэнергии</p>
     * @param $fullAmount <p>Сумма пени</p>
     * @param int $payUpDate <p>Срок оплаты</p>
     * @param bool $createIfNotFound
     */
    private static function setPowerFineData($cottage, $registeredPowerDatum, $fullAmount, int $payUpDate, $createIfNotFound = false): void
    {
        if (Cottage::isMain($cottage)) {
            $cottageNumber = $cottage->cottageNumber;
        } else {
            $cottageNumber = $cottage->masterId . '-a';
        }
        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $registeredPowerDatum->month, 'pay_type' => 'power'])->one();
        if ($existentFine) {
            if (!$existentFine->locked) {
                $existentFine->summ = CashHandler::toRubles($fullAmount);
                $existentFine->save();
            }
            //echo "электричество {$cottage->cottageNumber} {$registeredPowerDatum->month} Пересчитано\n";
        } elseif ($createIfNotFound) {
            self::createFine(
                $cottageNumber,
                'power',
                $registeredPowerDatum->month,
                $payUpDate,
                $fullAmount
            );
            //echo "электричество {$cottage->cottageNumber} {$registeredPowerDatum->month} Создано\n";
        }
    }

    /**
     * Обновление информации о пени
     * @param $cottage <p>Номер участка</p>
     * @param $quarter <p>Квартал оплаты</p>
     * @param $fullAmount <p>Сумма пени</p>
     * @param int $payUpDate <p>Срок оплаты</p>
     * @param bool $createIfNotFound
     */
    private static function setMembershipFineData($cottage, $quarter, $fullAmount, int $payUpDate, $createIfNotFound = false): void
    {
        if (Cottage::isMain($cottage)) {
            $cottageNumber = $cottage->cottageNumber;
        } else {
            $cottageNumber = $cottage->masterId . '-a';
        }
        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $quarter, 'pay_type' => 'membership'])->one();
        if ($existentFine) {
            if (!$existentFine->locked) {
                $existentFine->summ = CashHandler::toRubles($fullAmount);
                $existentFine->save();
            }
            //echo "членские {$cottage->cottageNumber} {$quarter} Пересчитано\n";
        } elseif ($createIfNotFound) {
            self::createFine(
                $cottageNumber,
                'membership',
                $quarter,
                $payUpDate,
                $fullAmount
            );
            //echo "членские {$cottage->cottageNumber} {$quarter} Создано\n";
        }
    }

    /**
     * Обновление информации о пени
     * @param $cottage <p>Номер участка</p>
     * @param $year <p>Квартал оплаты</p>
     * @param $fullAmount <p>Сумма пени</p>
     * @param int $payUpDate <p>Срок оплаты</p>
     * @param bool $createIfNotFound
     */
    private static function setTargetFineData($cottage, $year, $fullAmount, int $payUpDate, $createIfNotFound = false): void
    {
        if (Cottage::isMain($cottage)) {
            $cottageNumber = $cottage->cottageNumber;
        } else {
            $cottageNumber = $cottage->masterId . '-a';
        }
        $existentFine = Table_penalties::find()->where(['cottage_number' => $cottageNumber, 'period' => $year, 'pay_type' => 'target'])->one();
        if ($existentFine) {
            if (!$existentFine->locked) {
                $existentFine->summ = CashHandler::toRubles($fullAmount);
                $existentFine->save();
            }
            //echo "целевые {$cottage->cottageNumber} {$year} Пересчитано {$fullAmount}\n";
        } elseif ($createIfNotFound) {
            self::createFine(
                $cottageNumber,
                'target',
                $year,
                $payUpDate,
                $fullAmount
            );
        }
    }

    /**
     * Получение данных о расчётах пени
     * @param Table_penalties $item
     * @return string
     * @throws Exception
     */
    public static function getFineStructure(Table_penalties $item): string
    {
        // получу информацию о платеже
        switch ($item->pay_type) {
            case 'power':
                return self::getPowerFineData($item);
            case 'membership':
                return self::getMembershipFineData($item);
            case 'target':
                return self::getTargetFineData($item);
        }
        throw new ExceptionWithStatus('Неверные данные о пени');
    }

    /**
     * @param Table_penalties $item
     * @return string
     * @throws Exception
     */
    private static function getPowerFineData(Table_penalties $item): string
    {
        $answer = '';
        // найду информацию об участке
        $cottageInfo = Cottage::getCottageByLiteral($item->cottage_number);
        // получу стоимость месяца
        $amount = Table_power_months::findOne(['cottageNumber' => $item->cottage_number, 'month' => $item->period])->totalPay;
        $answer .= 'Сумма платежа: <b class="text-info">' . CashHandler::toSmoothRubles($amount) . '</b><br/>';
        // получу оплаты по кварталу
        $pays = Table_payed_power::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'month' => $item->period])->all();
        $payUp = self::getPayUp('month', $item->period);
        $answer .= 'Срок оплаты: <b class="text-info">' . TimeHandler::getDatetimeFromTimestamp(TimeHandler::getPayUpMonth($item->period)) . '</b><br/>';
        $answer = self::handleInnerPays($pays, $payUp, $answer, $amount);
        return $answer;
    }

    /**
     * @param Table_penalties $item
     * @return string
     * @throws Exception
     */
    private static function getMembershipFineData(Table_penalties $item): string
    {
        $answer = '';
        // найду информацию об участке
        $cottageInfo = Cottage::getCottageByLiteral($item->cottage_number);
        $isMain = Cottage::isMain($cottageInfo);

        // получу стоимость квартала
        $tariff = Accruals_membership::findOne(['quarter' => $item->period, 'cottage_number' => $cottageInfo->getCottageNumber()]);
        if (!empty($tariff)) {
            $fixed = $tariff->fixed_part;
            $float = $tariff->square_part;

            $startAmount = Calculator::countFixedFloat(
                $fixed,
                $float,
                $tariff->counted_square);
            $answer .= 'Сумма взноса: <b class="text-info">' . CashHandler::toSmoothRubles($startAmount) . '</b><br/>';
            // получу оплаты по кварталу
            if ($isMain) {
                $pays = Table_payed_membership::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'quarter' => $item->period])->all();
            } else {
                $pays = Table_additional_payed_membership::find()->where(['cottageId' => $cottageInfo->masterId, 'quarter' => $item->period])->all();
            }
            $payUp = self::getPayUp('quarter', $item->period);
            $answer .= 'Срок оплаты: <b class="text-info">' . TimeHandler::getPayUpQuarter($item->period) . '</b><br/>';
            $answer = self::handleInnerPays($pays, $payUp, $answer, $startAmount);
        }
        return $answer;
    }

    /**
     * @param Table_penalties $item
     * @return string
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    private static function getTargetFineData(Table_penalties $item): string
    {
        $answer = '';
        // найду информацию об участке
        $cottageInfo = Cottage::getCottageByLiteral($item->cottage_number);
        $isMain = Cottage::isMain($cottageInfo);
        $yearTariff = Table_tariffs_target::findOne(['year' => $item->period]);
        if ($yearTariff !== null) {
            // получу стоимость квартала
            $fixed = $yearTariff->fixed_part;
            $float = $yearTariff->float_part;
            $startAmount = Calculator::countFixedFloat(
                $fixed,
                $float,
                $cottageInfo->cottageSquare);
            $answer .= 'Сумма взноса: <b class="text-info">' . CashHandler::toSmoothRubles($startAmount) . '</b><br/>';
            // получу оплаты по кварталу
            if ($isMain) {
                $pays = Table_payed_target::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'year' => $item->period])->all();
            } else {
                $pays = Table_additional_payed_target::find()->where(['cottageId' => $cottageInfo->masterId, 'year' => $item->period])->all();
            }
            $payUp = self::getPayUp('year', $yearTariff->payUpTime);
            $answer .= 'Срок оплаты: <b class="text-info">' . TimeHandler::getDatetimeFromTimestamp($yearTariff->payUpTime) . '</b><br/>';
            $answer = self::handleInnerPays($pays, $payUp, $answer, $startAmount);
            return $answer;
        }
        throw new ExceptionWithStatus('Не найден тариф на год: ' . $item->period);
    }

    /**
     * @param $pays
     * @param int $payUp
     * @param string $answer
     * @param float $amount
     * @return string
     * @throws Exception
     */
    private static function handleInnerPays($pays, int $payUp, string $answer, float $amount): string
    {
        if (empty($pays)) {
            // кажется, платёж вообще не оплачен, расчитаю оплату с момента просрочки до текущей даты
            $difference = TimeHandler::checkDayDifference($payUp);
            if ($difference === 0) {
                $difference = 1;
            }
            $answer .= 'Просрочено дней: <b class="text-danger">' . $difference . '</b><br/>';
            $answer .= '<b class="text-danger">Платёж ещё не поступил</b><br/>';
            $perDay = CashHandler::countPercent($amount, self::PERCENT);
            $answer .= 'За день просрочки: <b class="text-danger">' . CashHandler::toSmoothRubles($perDay) . '</b><br/>';
            $accruals = self::countFine($amount, $difference);
            $answer .= 'Начислено пени: <b class="text-danger">' . CashHandler::toSmoothRubles($accruals) . '</b><br/>';
        } else {
            $lastPayDate = null;
            $payed = 0;
            foreach ($pays as $pay) {
                $answer .= 'Платёж <b class="text-success">' . TimeHandler::getDatetimeFromTimestamp($pay->paymentDate) . '</b><br/>';
                $answer .= 'Сумма: <b class="text-success">' . CashHandler::toSmoothRubles($pay->summ) . '</b><br/>';
                $answer .= 'Осталось заплатить: <b class="text-info">' . CashHandler::toSmoothRubles(CashHandler::toRubles($amount - CashHandler::toRubles($payed + $pay->summ))) . '</b><br/>';
                // если платёж просрочен
                if ($pay->paymentDate > $payUp) {
                    $answer .= '<b class="text-danger">Платёж просрочен!</b><br/>';
                    // посчитаю количество дней просрочки
                    if ($lastPayDate === null) {
                        $lastPayDate = $payUp;
                    }
                    $difference = TimeHandler::checkDayDifference($lastPayDate, $pay->paymentDate);
                    $answer .= "Просрочено дней: <b class=\"text-danger\">$difference</b><br/>";
                    $nowAmount = CashHandler::toRubles($amount - $payed);
                    $perDay = CashHandler::countPercent($nowAmount, self::PERCENT);
                    $answer .= 'За день просрочки: <b class="text-danger">' . CashHandler::toSmoothRubles($perDay) . '</b><br/>';
                    $accruals = self::countFine($nowAmount, $difference);
                    $answer .= 'Начислено пени: <b class="text-danger">' . CashHandler::toSmoothRubles($accruals) . '</b><br/>';
                    $payed = CashHandler::toRubles($payed + $pay->summ);
                    $lastPayDate = $pay->paymentDate;
                } else {
                    $answer .= '<b class="text-success">Оплачено в срок</b><br/>';
                    $payed = CashHandler::toRubles($payed + $pay->summ);
                    $lastPayDate = $payUp;
                }
            }
            // проверю полноту оплаты
            if ($payed < $amount) {
                // кажется, платёж вообще не оплачен, расчитаю оплату с момента просрочки до текущей даты
                $difference = TimeHandler::checkDayDifference($lastPayDate);
                $nowAmount = CashHandler::toRubles($amount - $payed);
                $answer .= '<b class="text-danger">Счёт оплачен не полностью!</b><br/>';
                $answer .= 'Осталось заплатить: <b class="text-info">' . CashHandler::toSmoothRubles($nowAmount) . '</b><br/>';
                $answer .= 'Просрочено дней: <b class="text-danger">' . $difference . '</b><br/>';
                $perDay = CashHandler::countPercent($nowAmount, self::PERCENT);
                if ($perDay < 0.01) {
                    $answer .= 'За день просрочки: <b class="text-danger">' . $perDay . ' коп.</b><br/>';
                } else {
                    $answer .= 'За день просрочки: <b class="text-danger">' . CashHandler::toSmoothRubles($perDay) . '</b><br/>';
                }
                $accruals = self::countFine($nowAmount, $difference);
                $answer .= 'Начислено пени: <b class="text-danger">' . CashHandler::toSmoothRubles($accruals) . '</b><br/>';
            }
        }
        return $answer;
    }

    /**
     * @param $pays
     * @param int $payUp
     * @param $totalPay
     * @return float
     * @throws Exception
     */
    private static function handlePeriodPayments($pays, int $payUp, $totalPay): float
    {
        $lastPayDate = null;
        $payed = 0;
        $fullAmount = 0;
        foreach ($pays as $pay) {
            // проверю, если дата платежа раньше ограничения- просто сохраню сумму платежа
            if ($pay->paymentDate < $payUp) {
                $payed = CashHandler::toRubles($payed + $pay->summ);
                $lastPayDate = $pay->paymentDate;
            } else {
                // платёж просрочен.
                //Если есть дата последнего платежа, считаю разницу дней от неё, иначе- от срока оплаты платежа
                if ($lastPayDate === null) {
                    $lastPayDate = $payUp;
                }
                // посчитаю количество просроченных дней
                $daysLeft = TimeHandler::checkDayDifference($lastPayDate, $pay->paymentDate);
                if ($daysLeft > 0) {
                    // добавлю сумму к пени
                    $fullAmount += self::countFine($totalPay - $payed, $daysLeft);
                }
                $lastPayDate = $pay->paymentDate;
                $payed = CashHandler::toRubles($payed + $pay->summ);
            }
        }
        // если после всех платежей ещё остался долг- считаю начисления начиная с последней даты оплаты до этого дня
        if (CashHandler::toRubles($payed) < CashHandler::toRubles($totalPay)) {
            $daysLeft = TimeHandler::checkDayDifference($lastPayDate);
            if ($daysLeft > 0) {
                $fullAmount += self::countFine($totalPay - $payed, $daysLeft);
            }
        }
        return $fullAmount;
    }

    /**
     * @param array $powerDuties
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    private static function recalculatePowerDuties(array $powerDuties, $cottageInfo): void
    {
        $isMain = Cottage::isMain($cottageInfo);
        foreach ($powerDuties as $powerDuty) {
            $payUp = self::getPayUp('month', $powerDuty->powerData->month);
            if ($powerDuty->powerData->totalPay > 0) {
                // поищу оплаты по этому месяцу
                if ($isMain) {
                    $pays = Table_payed_power::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'month' => $powerDuty->powerData->month])->all();
                } else {
                    $pays = Table_additional_payed_power::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'month' => $powerDuty->powerData->month])->all();
                }
                // если платежей за период не было- тогда платёж не оплачен до сих пор, проверю, не просрочен ли он, если просрочен- просто посчитаю сумму просрочки по обычной формуле
                if (empty($pays)) {
                    if ($payUp < time()) {
                        $fineAmount = self::countFine($powerDuty->powerData->totalPay, TimeHandler::checkDayDifference($payUp));
                        // пересчитаю пени
                        self::setPowerFineData($cottageInfo, $powerDuty->powerData, $fineAmount, $payUp, true);
                    }
                } else {
                    // тут нужно проверить, были ли платежи проведены в отведённое время или просрочены
                    $fullAmount = self::handlePeriodPayments($pays, $payUp, $powerDuty->powerData->totalPay);
                    // если начислено пени- сохраню его
                    if ($fullAmount > 0) {
                        // обновлю данные по пени
                        self::setPowerFineData($cottageInfo, $powerDuty->powerData, $fullAmount, $payUp, true);
                    }
                }
            }
        }
    }

    /**
     * @param MembershipDebt[] $membershipDuties
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    private static function recalculateMembershipDuties(array $membershipDuties, $cottageInfo): void
    {
        $isMain = Cottage::isMain($cottageInfo);
        foreach ($membershipDuties as $membershipDuty) {
            $payUp = self::getPayUp('quarter', $membershipDuty->quarter);
            if ($membershipDuty->partialPayed > 0) {
                if ($isMain) {
                    $pays = Table_payed_membership::findAll(['cottageId' => $cottageInfo->cottageNumber, 'quarter' => $membershipDuty->quarter]);
                } else {
                    $pays = Table_additional_payed_membership::findAll(['cottageId' => $cottageInfo->masterId, 'quarter' => $membershipDuty->quarter]);
                }
                $fullAmount = self::handlePeriodPayments($pays, $payUp, $membershipDuty->amount);
                // если начислено пени- сохраню его
                if ($fullAmount > 0) {
                    // обновлю данные по пени
                    self::setMembershipFineData($cottageInfo, $membershipDuty->quarter, $fullAmount, $payUp, true);
                }
            } else if ($payUp < time()) {
                $fineAmount = self::countFine($membershipDuty->amount, TimeHandler::checkDayDifference($payUp));
                // пересчитаю пени
                self::setMembershipFineData($cottageInfo, $membershipDuty->quarter, $fineAmount, $payUp, true);
            }
        }
    }

    /**
     * @param $targetDuties
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @throws ExceptionWithStatus
     * @throws Exception
     */
    private static function recalculateTargetDuties($targetDuties, $cottageInfo): void
    {
        $isMain = Cottage::isMain($cottageInfo);
        foreach ($targetDuties as $targetDuty) {
            // неоплаченный остаток
            $leftToPay = CashHandler::toRubles($targetDuty->amount - $targetDuty->partialPayed);
            $targetTariff = Table_tariffs_target::findOne(['year' => $targetDuty->year]);
            if ($targetTariff !== null) {
                $payUp = self::getPayUp('year', $targetTariff->payUpTime);
                if ($isMain) {
                    $pays = Table_payed_target::findAll(['cottageId' => $cottageInfo->cottageNumber, 'year' => $targetDuty->year]);
                } else {
                    $pays = Table_additional_payed_target::findAll(['cottageId' => $cottageInfo->masterId, 'year' => $targetDuty->year]);
                }
                if (empty($pays)) {
                    if ($payUp < time()) {
                        $fineAmount = self::countFine($leftToPay, TimeHandler::checkDayDifference($payUp));
                        // пересчитаю пени
                        self::setTargetFineData($cottageInfo, $targetDuty->year, $fineAmount, $payUp, true);
                    }
                } else {
                    $fullAmount = self::handlePeriodPayments($pays, $payUp, $targetDuty->amount);
                    // если начислено пени- сохраню его
                    if ($fullAmount > 0) {
                        // обновлю данные по пени
                        self::setTargetFineData($cottageInfo, $targetDuty->year, $fullAmount, $payUp, true);
                    }
                }
            } else {
                throw new ExceptionWithStatus('Не удалось найти тарифы целевых взносов за ' . $targetDuty->year);
            }
        }
    }

    /**
     * Пересчёт всех пени по участку
     * @param $cottageInfo
     * @throws ExceptionWithStatus
     */
    public static function checkCottage($cottageInfo): void
    {
        $powerDuties = PowerHandler::getDebtReport($cottageInfo);
        if (!empty($powerDuties)) {
            self::recalculatePowerDuties($powerDuties, $cottageInfo);
        }
        $membershipDuties = MembershipHandler::getDebt($cottageInfo);
        if (!empty($membershipDuties)) {
            self::recalculateMembershipDuties($membershipDuties, $cottageInfo);
        }
        $targetDuties = TargetHandler::getDebt($cottageInfo);
        if (!empty($targetDuties)) {
            self::recalculateTargetDuties($targetDuties, $cottageInfo);
        }
    }

    /**
     * @param $transaction
     * @param $fine
     * @param float $summToPay
     * @param Table_penalties|null $fineInfo
     */
    public static function registerPay($transaction, $fine, float $summToPay, Table_penalties $fineInfo): void
    {
        $payedFine = new Table_payed_fines();
        $payedFine->fine_id = $fine->fines_id;
        $payedFine->transaction_id = $transaction->id;
        $payedFine->pay_date = $transaction->bankDate;
        $payedFine->summ = $summToPay;
        $payedFine->save();
        $fineInfo->payed_summ += $summToPay;
        if (CashHandler::toRubles($fineInfo->summ) === CashHandler::toRubles($fineInfo->payed_summ)) {
            $fineInfo->is_partial_payed = 0;
            $fineInfo->is_full_payed = 1;
        } else {
            $fineInfo->is_partial_payed = 1;
        }
        $fineInfo->save();
    }

    /**
     * @param Table_penalties $fine
     * @return int
     * @throws Exception
     */
    public static function getFineDaysLeft(Table_penalties $fine): int
    {
        $amount = 0;
        $cottage = Cottage::getCottageByLiteral($fine->cottage_number);
        switch ($fine->pay_type) {
            case 'membership':
                $pays = MembershipHandler::getPaysForPeriod($cottage, $fine->period);
                $amount = MembershipHandler::getAmount($cottage, $fine->period);
                break;
            case 'power':
                $pays = PowerHandler::getPaysForPeriod($cottage, $fine->period);
                $amount = PowerHandler::getAmount($cottage, $fine->period);
                break;
            case 'target':
                $pays = TargetHandler::getPaysForPeriod($cottage, $fine->period);
                $amount = TargetHandler::getAmount($cottage, $fine->period);
                break;
        }
        // теперь посчитаю просроченные дни
        if (!empty($pays)) {
            $payed = 0;
            // получу полную стоимость периода
            foreach ($pays as $pay) {
                // если выплачена полная стоимость платежа- последним днём задержки считается день выплаты
                $payed = CashHandler::toRubles($payed) + CashHandler::toRubles($pay->summ);
                if (CashHandler::toRubles($amount) === CashHandler::toRubles($payed)) {
                    return TimeHandler::checkDayDifference($fine->payUpLimit, $pay->paymentDate);
                }
            }
        }
        return TimeHandler::checkDayDifference($fine->payUpLimit);
    }

}