<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 13:35
 */

namespace app\models;


use app\models\database\PersonalPower;
use app\models\handlers\BillsHandler;
use app\models\selections\PowerDebt;
use app\models\utils\DbTransaction;
use app\validators\CashValidator;
use app\validators\CheckCottageNoRegistred;
use app\validators\CheckMonthValidator;
use DOMElement;
use Exception;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;

class PowerHandler extends Model
{
    public const SCENARIO_NEW_RECORD = 'new_record';
    public const SCENARIO_NEW_TARIFF = 'new_tariff';

    public $cottageNumber;
    public $month;
    public $newPowerData;
    public $additional = false;

    public $powerCost;
    public $powerOvercost;
    public $powerLimit;

    public $doChangeCounter;
    public $counterChangeType;
    public $newCounterStartData;
    public $newCounterFinishData;

    public $currentCondition;

    public static function changePayTime(int $id, $timestamp)
    {
        // найду все платежи данного счёта
        $pays = Table_payed_power::find()->where(['billId' => $id])->all();
        if (!empty($pays)) {
            foreach ($pays as $pay) {
                /** @var Table_payed_power $pay */
                $pay->paymentDate = $timestamp;
                $pay->save();
            }
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param string $powerMonth
     * @return int|mixed|string
     */
    private static function getPreviousPayed($cottageInfo, string $powerMonth)
    {
        $amount = 0;
        $previous = Cottage::isMain($cottageInfo) ? Table_payed_power::find()->where(['cottageId' => $cottageInfo->cottageNumber, 'month' => $powerMonth])->all() : Table_additional_payed_power::find()->where(['cottageId' => $cottageInfo->masterId, 'month' => $powerMonth])->all();
        if (!empty($previous)) {
            foreach ($previous as $item) {
                $amount += $item->summ;
            }
        }
        return $amount;
    }


    /**
     * Проверю, можно ли отменить последние показания по электроэнергии
     * @param Table_cottages $cottage <p>Экземляр участка</p>
     * @return string|null <p>Верну статус ошибки в случае невозможности отмены или null, если можно отменить платёж</p>
     */
    public static function isDataCancellable(Table_cottages $cottage): ?string
    {
        // для начала, получу данные о последнем заполненном месяце
        $lastData = Table_power_months::getLastFilled($cottage);
        // если поступали оплаты по данному счёту- верну ошибку
        if ($lastData !== null) {
            if (Table_payed_power::isPayed($lastData) > 0) {
                return 'Невозможно отменить показания: по ним уже получена оплата';
            }
            // получу счета в которых участвует данный период. Если какой-то из них частично или полностью оплачен- операция недоступна
            $bills = BillsHandler::getMonthContains($cottage, $lastData);
            if (!empty($bills)) {
                foreach ($bills as $bill) {
                    if ($bill->payedSumm > 0) {
                        return "Невозможно отменить показания: счёт № {$bill->id}, в котором присутствует данный период, частично или полностью оплачен!";
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @return array
     */
    public static function checkLastFilledDelete($cottage): array
    {
        $isMain = Cottage::isMain($cottage);
        // получу последний заполненный месяц
        $lastFilled = self::getLastFilled($cottage);
        if ($isMain) {
            // проверю, не выставлены ли счета по данному периоду. Если выставлены- предупрежу, что они будут удалены
            $billsWithPeriod = BillsHandler::getMonthContains($cottage, $lastFilled);
        }
        if (!empty($billsWithPeriod)) {
            $answer = "Вместе с заполненными данными будут удалены данные счета:\n";
            foreach ($billsWithPeriod as $item) {
                $answer .= $item->id . "\n";
            }
        } else {
            $answer = 'Точно удаляем данные?';
        }
        return ['answer' => $answer, 'main' => $isMain];
    }

    /**
     * @param $data Table_power_months|Table_additional_power_months
     * @return int
     */
    private static function isPayed($data): int
    {
        if ($data instanceof Table_power_months) {
            return Table_payed_power::isPayed($data);
        }
        return Table_additional_payed_power::isPayed($data);
    }

    /**
     * Получение платежей за данный период
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param string $period
     * @return Table_additional_payed_power[]|Table_payed_power[]
     */
    public static function getPaysForPeriod($cottage, string $period): array
    {
        if (Cottage::isMain($cottage)) {
            return Table_payed_power::findAll(['cottageId' => $cottage->cottageNumber, 'month' => $period]);
        }
        return Table_additional_payed_power::findAll(['cottageId' => $cottage->masterId, 'month' => $period]);
    }

    /**
     * Получу стоимость периода
     * @param $cottage
     * @param string $period
     * @return float
     */
    public static function getAmount($cottage, string $period): float
    {
        $data = self::getPeriod($cottage, $period);
        if ($data !== null) {
            return $data->totalPay;
        }
        return 0;
    }

    /**
     * Получу данные по периоду оплаты
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param string $period
     * @return Table_additional_power_months|Table_power_months|null
     */
    public static function getPeriod($cottage, string $period)
    {
        if (Cottage::isMain($cottage)) {
            return Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => $period]);
        }
        return Table_additional_power_months::findOne(['cottageNumber' => $cottage->masterId, 'month' => $period]);
    }

    /**
     * @param $period
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function changeTariff($period): array
    {
        $transaction = new DbTransaction();
        $message = '';
        $tariff = Table_tariffs_power::findOne(['targetMonth' => $period]);
        if ($tariff === null) {
            throw new ExceptionWithStatus('Не удалось найти тариф для изменения: электроэнергия, ' . $period);
        }
        $tariff->load(Yii::$app->request->post());
        if ($tariff->validate()) {
            $tariff->save();
        }
        // теперь пересчитаю все данные по всем участкам, связанные с этим тарифом
        $existentValues = Table_power_months::findAll(['month' => $tariff->targetMonth]);
        if (!empty($existentValues)) {
            foreach ($existentValues as $existentValue) {
                // todo Нужно: найти участки, которые затрагивает данное изменение, найти все счета, в которых присутствует данное значение, изменить значение в счетах, общую сумму счёта, проверить все транзакции данных счетов, скорректировать их с учётом изменения суммы платежа, найти все оплаченные значения электроэнергии, скорректировать их сумму, зачислить излишне оплаченное на депозит, скорректировать собственно данные по электроэнергии, скорректировать задолженность участка
                $cottageInfo = Cottage::getCottageByLiteral($existentValue->cottageNumber);
                // проверю, была ли переплата или недоплата по тарифу
                $realAmount = self::countCostDetails($existentValue, $tariff);
                if ($realAmount['total'] !== $existentValue->totalPay) {
                    $oldAmount = $existentValue->totalPay;
                    $diff = $existentValue->totalPay - CashHandler::toRubles($realAmount['total'], true);
                    //todo пока обрабатываю только действия с переплатой
                    if ($diff < 0) {
                        throw new ExceptionWithStatus("Пока обрабатываю только действия с переплатой! {$cottageInfo->cottageNumber} {$diff}");
                    }
                    $message .= "{$existentValue->cottageNumber} : старая стоимость- {$existentValue->totalPay} новая стоимость - {$realAmount['total']}<br/>";
                    $existentValue->totalPay = $realAmount['total'];
                    $existentValue->inLimitPay = $realAmount['cost'];
                    $existentValue->overLimitPay = $realAmount['over_cost'];
                    $existentValue->save();

                    $message .= "Разница составляет $diff<br/>";

                    // найду счета, в которых участвует данный период
                    $existentBills = BillsHandler::getMonthContains($cottageInfo, $existentValue);
                    foreach ($existentBills as $existentBill) {
                        $message .= "Выставлен счёт {$existentBill->id}<br/>";
                        // изменю данные счёта. Так делать не надо, но придётся
                        $billContent = new DOMHandler($existentBill->bill_content);
                        /** @var DOMElement $monthValue */
                        /** @var DOMElement $monthValue */
                        $monthValue = $billContent->query('/payment/power/month[@date="' . $existentValue->month . '"]')->item(0);
                        // изменю данные
                        // информации о месяце
                        $monthValue->setAttribute('summ', $realAmount['total']);
                        $monthValue->setAttribute('powerCost', $tariff->powerCost);
                        $monthValue->setAttribute('powerOvercost', $tariff->powerOvercost);
                        $monthValue->setAttribute('in-limit-cost', $realAmount['cost']);
                        $monthValue->setAttribute('over-limit-cost', $realAmount['over_cost']);
                        // информации в целом об электроэнергии
                        /** @var DOMElement $powerValue */
                        $powerValue = $billContent->query('/payment/power')->item(0);
                        $oldPowerValue = CashHandler::toRubles($powerValue->getAttribute('cost'));
                        $powerValue->setAttribute('cost', CashHandler::toRubles($oldPowerValue - $diff));
                        // общая стоимость
                        $powerValue = $billContent->query('/payment')->item(0);
                        $oldValue = CashHandler::toRubles($powerValue->getAttribute('summ'));
                        $newValue = CashHandler::toRubles($oldValue - $diff);
                        $powerValue->setAttribute('summ', $newValue);
                        // сохраню изменённое значение
                        $existentBill->bill_content = $billContent->save();
                        $oldBillAmount = $existentBill->totalSumm;
                        $existentBill->totalSumm = CashHandler::toRubles($existentBill->totalSumm - $diff);
                        $message .= "Старая стоимость счёта: {$oldBillAmount} новая: {$existentBill->totalSumm }<br/>";
                        $existentBill->save();

                        // найду транзакции по данному счёту
                        $transactions = Table_transactions::findAll(['billId' => $existentBill->id]);
                        if (!empty($transactions)) {
                            // todo пока обрабатываю одну транзакцию за платёж, если появятся счета, оплаченные в несколько раз- обработаю их
                            if (count($transactions) > 1) {
                                throw new ExceptionWithStatus('Найдено больше одной транзакции при изменении тарифа электроэнергии по счёту №' . $existentBill->id);
                            }
                            // получу сумму транзакции. Если она больше, чем новая стоимость платежа и по ней ещё не начислялись данные на депозит- начислю остаток на депозит
                            $transactionItem = $transactions[0];
                            if ($transactionItem->transactionSumm > $existentBill->totalSumm) {
                                // если сумма оплаты меньше старой суммы счёта- значит, счёт оплачен не полностью, это скорректрирует сумму возмещения
                                if ($transactionItem->transactionSumm < $oldBillAmount) {
                                    $correction = $oldBillAmount - $transactionItem->transactionSumm;
                                } else {
                                    $correction = 0;
                                }
                                $correctedDepositGain = $diff - $correction;
                                $message .= "Оплачено по счёту {$transactionItem->transactionSumm}<br/>";
                                if (!empty($transactionItem->gainedDeposit)) {
                                    $previousDeposit = $transactionItem->gainedDeposit;
                                } else {
                                    $previousDeposit = 0;
                                }
                                if ($correctedDepositGain > 0) {
                                    // добавлю сумму начисление в данные участка
                                    $oldDepositValue = $cottageInfo->deposit;
                                    $cottageInfo->deposit = CashHandler::toRubles($cottageInfo->deposit + $correctedDepositGain);
                                    // зачислю разницу на депозит
                                    (new Table_deposit_io([
                                        'cottageNumber' => $cottageInfo->cottageNumber,
                                        'summ' => $diff,
                                        'destination' => 'in',
                                        'actionDate' => $transactionItem->payDate,
                                        'billId' => $existentBill->id,
                                        'transactionId' => $transactionItem->id,
                                        'summBefore' => $oldDepositValue,
                                        'summAfter' => $cottageInfo->deposit
                                    ]))->save();
                                    $message .= "На депозит участка начислено {$correctedDepositGain}<br/>";
                                    // изменю данные о поступлении на депозит в счёте
                                    $existentBill->toDeposit = $transactionItem->gainedDeposit = CashHandler::toRubles($correctedDepositGain + $previousDeposit);
                                    // изменю данные об оплаченной электроэнергии
                                    $payed = Table_payed_power::findOne(['cottageId' => $existentValue->cottageNumber, 'month' => $existentValue->month]);
                                    if ($payed === null) {
                                        throw new ExceptionWithStatus('Не найден факт оплаты электроэнергии при смене тарифа: ' . $existentValue->month);
                                    }
                                    // скорректрирую данные
                                    // если старая сумма равна старому значению- счёт оплачен полностью, заменю значение
                                    if ($payed->summ === $oldAmount) {
                                        $payed->summ = $realAmount['total'];
                                    } else {
                                        $payed->summ = CashHandler::toRubles($payed->summ - $diff);
                                    }
                                    $payed->save();
                                }
                                $existentBill->save();
                                $transactionItem->save();
                            }
                        } else if ($cottageInfo->powerDebt > $diff) {
                            $cottageInfo->powerDebt = CashHandler::toRubles($cottageInfo->powerDebt + $diff);
                            $message .= "Скорректрированная задолженность участка: {$cottageInfo->powerDebt}<br/>";
                        }
                    }
                    $message .= '<br/>';
                }
                $cottageInfo->save();
            }
        }

        // пересчитаю разницу
        self::recalculatePower($period);

        $transaction->commitTransaction();
        return ['status' => 1, 'message' => $message];
    }

    /**
     * Возвращает сумму долга по участку
     * @param Table_cottages $globalInfo
     * @return float
     */
    public static function getDebt(Table_cottages $globalInfo): float
    {
        // получу актуальную задолженность
        $duties = Table_power_months::getAllData($globalInfo);
        return self::countDuty($duties);
    }

    /**
     * Считает задолженности по электроэнергии по переданному массиву (с учётом оплаты)
     * @param Table_power_months[] $duties
     * @return float
     */
    private static function countDuty(array $duties): float
    {
        $duty = 0;
        if (!empty($duties)) {
            foreach ($duties as $dutyItem) {
                $duty += $dutyItem->totalPay;
                // поищу все оплаты по данному счёту и вычту их из суммы долга
                $pays = Table_payed_power::getPayed($dutyItem);
                if ($pays !== null) {
                    foreach ($pays as $pay) {
                        $duty = CashHandler::rublesMath($duty - $pay->summ);
                    }
                }
            }
        }
        return CashHandler::toRubles($duty);
    }

    public static function getCottagePowerData(Table_cottages $cottage)
    {
        return Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->orderBy('month DESC')->all();
    }

    public static function getYearConsumption(int $year)
    {
        // верну потребление электроэнергии по месяцам
        $monthCounter = 1;
        $consumption = [];
        $consumption[] = [($year - 1) . '-12', 0];
        while ($monthCounter <= 12){
            if($monthCounter > 9){
                $month = "$year-$monthCounter";
            }
            else{
                $month = "$year-0$monthCounter";
            }
            // получу все потребление за месяц
            $consumptionItems = Table_power_months::findAll(['month' => $month]);
            $spend = 0;
            if(!empty($consumptionItems)){
                foreach ($consumptionItems as $consumptionItem) {
                    $spend += CashHandler::toRubles($consumptionItem->difference);
                }
            }
            $consumption[] = [$month, $spend];
            ++$monthCounter;
        }
        $consumption[] = [($year + 1) . '-01', 0];
        return $consumption;
    }

    public static function getYearPayments(string $year)
    {
        $accrual = [];
        $pays = [];
        $monthCounter = 1;
        while ($monthCounter <= 12) {
            if ($monthCounter > 9) {
                $month = "$year-$monthCounter";
            } else {
                $month = "$year-0$monthCounter";
            }
            // начисления за месяц
            $accruals = Table_power_months::findAll(['month' => $month]);
            $amount = 0;
            if(!empty($accruals)){
                foreach ($accruals as $accrualItem) {
                    $amount += CashHandler::toRubles($accrualItem->totalPay);
                }
            }
            $accrual[] = [$month, CashHandler::toRubles($amount)];
            $payed = Table_payed_power::findAll(['month' => $month]);
            $totalPayed = 0;
            if(!empty($payed)){
                foreach ($payed as $payedItem) {
                    $totalPayed += CashHandler::toRubles($payedItem->summ);
                }
            }
            $pays[] = [$month, CashHandler::toRubles($totalPayed)];
            $monthCounter++;
        }
        return [['name' => 'Начислено к оплате, руб.', 'data' => $accrual], ['name' => 'Оплачено, руб.', 'data' => $pays]];
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_NEW_RECORD => ['cottageNumber', 'month', 'newPowerData', 'additional', 'doChangeCounter', 'counterChangeType', 'newCounterStartData', 'newCounterFinishData'],
            self::SCENARIO_NEW_TARIFF => ['month', 'powerCost', 'powerOvercost', 'powerLimit'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'doChangeCounter' => 'Замена счётчика',
            'counterChangeType' => 'Вариант замены',
        ];
    }

    public function rules(): array
    {
        return [
            [['cottageNumber', 'newPowerData'], 'required', 'on' => self::SCENARIO_NEW_RECORD],
            [['powerCost', 'powerOvercost', 'month', 'powerLimit'], 'required', 'on' => self::SCENARIO_NEW_TARIFF],
            ['cottageNumber', CheckCottageNoRegistred::class],
            ['month', CheckMonthValidator::class, 'skipOnEmpty' => false],
            ['additional', 'boolean'],
            ['cottageNumber', 'integer', 'min' => 1, 'max' => 300],
            [['newPowerData', 'powerLimit', 'newCounterStartData', 'newCounterFinishData'], 'integer', 'min' => 0],
            [['powerCost', 'powerOvercost'], CashValidator::class],
        ];
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (!empty($config['attributes'])) {
            $this->cottageNumber = $config['attributes']['cottageNumber'];
            $this->month = $config['attributes']['month'];
            $this->newPowerData = $config['attributes']['newPowerData'];
            if (!empty($config['attributes']['additional']) && $config['attributes']['additional'] === true) {
                $this->additional = true;
            }
        }
    }

    public function createTariff(): bool
    {
        if ($this->validate()) {
            // проверю, не заполнен ли уже тариф за месяц
            try {
                self::getRowTariff($this->month);
                throw new InvalidValueException('Тариф на ' . $this->month . ' уже заполнен!');
            } catch (InvalidArgumentException $e) {
            }
            $newRecord = new Table_tariffs_power();
            $newRecord->targetMonth = $this->month;
            $newRecord->powerLimit = $this->powerLimit;
            $newRecord->powerCost = $this->powerCost;
            $newRecord->powerOvercost = $this->powerOvercost;
            $newRecord->searchTimestamp = TimeHandler::getMonthTimestamp($this->month);
            $newRecord->fullSumm = 0;
            $newRecord->payedSumm = 0;
            $newRecord->paymentInfo = '';
            $newRecord->save();
            self::recalculatePower($this->month);
            return true;
        }
        throw new InvalidArgumentException('Ошибка в данных: ' . serialize($this->errors));
    }

    /**
     * @return array
     */
    public function insert(): array
    {
        if ($this->validate()) {
            try {
                $limitUsed = 0;
                // проверю, заносились ли уже данные по этому участку
                if ($this->additional) {
                    $oldData = Table_additional_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->orderBy('searchTimestamp DESC')->one();
                } else {
                    $oldData = Table_power_months::find()->where(['cottageNumber' => $this->cottageNumber])->orderBy('searchTimestamp DESC')->one();
                }
                $oldPowerData = $this->currentCondition->currentPowerData;
                // обработка смены счётчика
                if ($this->doChangeCounter) {
                    // получу тип смены счётчика
                    if ($this->counterChangeType === 'simple') {
                        // внесу данные о замене счётчика
                        $change = new Table_counter_changes();
                        $change->cottageNumber = $this->cottageNumber;
                        $change->oldCounterStartData = $oldPowerData;
                        $change->oldCounterNewData = $this->newPowerData;
                        $change->newCounterData = $this->newCounterStartData;
                        $change->change_time = time();
                        $change->changeMonth = $this->month;
                        $change->save();
                    } elseif ($this->counterChangeType === 'difficult') {
                        if (empty($this->newCounterFinishData)) {
                            throw new ExceptionWithStatus('Не заполнены конечные показания нового счётчика', 6);
                        }
                        // проверю, тратилась ли электроэнергия по старому счётчику. Если тратилась- рассчитаю сумму и вынесу её в разовый платёж.
                        $oldDifference = $this->newPowerData - $oldData->newPowerData;
                        if ($oldDifference > 0) {
                            $summ = $this->countCost($oldDifference);
                            // создам разовый платёж
                            $singlePay = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY]);
                            $singlePay->cottageNumber = $this->cottageNumber;
                            $singlePay->double = $this->additional;
                            $singlePay->summ = $summ;
                            $singlePay->description = "Оплата электроэнергии по старому счётчику за " . TimeHandler::getFullFromShotMonth($this->month) . " при замене на новый";
                            $singlePay->insert();
                            // отмечу, что использовался льготный лимит
                            $limitUsed = $oldDifference;
                        }
                        // заменю показания старого счётчика на показания нового
                        $oldPowerData = $this->newCounterStartData;
                        $this->newPowerData = $this->newCounterFinishData;
                        $change = new Table_counter_changes();
                        $change->cottageNumber = $this->cottageNumber;
                        $change->oldCounterStartData = $oldPowerData;
                        $change->oldCounterNewData = $this->newPowerData;
                        $change->newCounterData = $this->newCounterFinishData;
                        $change->change_time = time();
                        $change->changeMonth = $this->month;
                        $change->save();
                    } else {
                        throw new ExceptionWithStatus('Не выбран тип замены счётчика', 4);
                    }
                }
                if (!empty($oldData)) {
                    // если уже вносились данные и предыдущий месяц не является последним найденным- заполню предыдущие месяцы нулевыми значениями
                    $prev = TimeHandler::getPrevMonth($this->month);
                    if ($oldData->month >= $this->month) {
                        throw new ErrorException($prev . ' - данные за этот месяц уже заполнены!');
                    }
                    if ($oldData->month !== $prev) {
                        $monthsList = TimeHandler::getMonthsList($oldData->month, $prev);
                        // найду информацию об участке
                        $cottageInfo = Cottage::getCottageByLiteral($this->cottageNumber);
                        foreach ($monthsList as $key => $item) {
                            $attributes = ['cottageNumber' => $this->cottageNumber, 'month' => $key, 'newPowerData' => $cottageInfo->currentPowerData, 'additional' => $this->additional];
                            $power = new PowerHandler(['scenario' => self::SCENARIO_NEW_RECORD, 'attributes' => $attributes]);
                            $power->insert();
                        }
                    }
                }
                // расчитаю данные
                $this->newPowerData = (int)$this->newPowerData;
                if ($this->newPowerData < $oldPowerData) {
                    return ['status' => 0,
                        'errors' => 'Новые значения не могут быть меньше старых!',
                    ];
                }
                $fillingDate = time();
                $searchTimestamp = TimeHandler::getMonthTimestamp($this->month);
                $difference = 0;
                $totalPay = 0;
                $inLimitSumm = 0;
                $inLimitPay = 0;
                $overLimitPay = 0;
                $overLimitSumm = 0;
                if ($this->newPowerData > $oldPowerData) {
                    $difference = $this->newPowerData - $oldPowerData;
                    // получу тариф на электричество по данному месяцу. Если его не существует- исключение незаполненного тарифа
                    $tariff = self::getTariff($this->month);
                    $realPowerLimit = $this->cottageNumber == "88" ? 100 : $tariff[$this->month]['powerLimit'];
                    $powerLimit = $realPowerLimit - $limitUsed;
                    if ($powerLimit < 0) {
                        $powerLimit = 0;
                    }
                    if ($difference > $powerLimit) {
                        $inLimitSumm = $powerLimit;
                        $inLimitPay = CashHandler::rublesMath($inLimitSumm * $tariff[$this->month]['powerCost']);
                        $overLimitSumm = $difference - $inLimitSumm;
                        $overLimitPay = CashHandler::rublesMath($overLimitSumm * $tariff[$this->month]['powerOvercost']);
                        $totalPay = CashHandler::rublesMath($inLimitPay + $overLimitPay);
                    } else {
                        $inLimitSumm = $difference;
                        $inLimitPay = CashHandler::rublesMath($difference * $tariff[$this->month]['powerCost']);
                        $totalPay = $inLimitPay;
                    }
                }
                if ($this->additional) {
                    $newData = new Table_additional_power_months();
                } else {
                    $newData = new Table_power_months();
                }
                $newData->cottageNumber = $this->cottageNumber;
                $newData->month = $this->month;
                $newData->fillingDate = $fillingDate;
                $newData->oldPowerData = $oldPowerData;
                $newData->newPowerData = $this->newPowerData;
                $newData->searchTimestamp = $searchTimestamp;
                $newData->payed = 'no';
                $newData->difference = $difference;
                $newData->totalPay = $totalPay;
                $newData->inLimitSumm = $inLimitSumm;
                $newData->inLimitPay = $inLimitPay;
                $newData->overLimitSumm = $overLimitSumm;
                $newData->overLimitPay = $overLimitPay;

                if ($this->additional) {
                    $this->currentCondition = AdditionalCottage::getCottage($this->cottageNumber);
                }
                /**
                 * @var $ref Table_cottages
                 */
                $ref = $this->currentCondition;
                $ref->powerDebt = CashHandler::rublesMath(CashHandler::toRubles($ref->powerDebt) + $totalPay);
                if ($this->doChangeCounter && $this->counterChangeType === 'simple') {
                    $ref->currentPowerData = $this->newCounterStartData;
                } else {
                    $ref->currentPowerData = $this->newPowerData;
                }
                $newData->save();
                $ref->save();
                self::recalculatePower($this->month);
                //$transaction->commit();
            } catch (Exception $e) {
                //$transaction->rollBack();
                return ['status' => 3, 'message' => 'Ошибка базы данных'];
            }
            return ['status' => 1, 'totalSumm' => $totalPay, 'data' => $newData];
        }
        throw new InvalidArgumentException('Ошибка в данных: ' . serialize($this->errors));
    }

    /**
     * @param $period string|array
     * @return array
     * @throws ErrorException|InvalidArgumentException
     */
    public static function getTariff($period): array
    {
        $data = [];
        if (is_string($period) && TimeHandler::isMonth($period)) {
            $tariff = Table_tariffs_power::findOne(['targetMonth' => $period]);
            if ($tariff !== null) {
                $data[$tariff->targetMonth] = ['powerLimit' => $tariff->powerLimit, 'powerCost' => $tariff->powerCost, 'powerOvercost' => $tariff->powerOvercost];
            } else {
                throw new ErrorException('Тарифа на данный месяц не существует!');
            }
        }
        if (is_array($period)) {
            $start = TimeHandler::isMonth($period['start']);
            $tariff = Table_tariffs_power::find()->where("targetMonth>{$start['full']}")->all();
            if (!empty($tariff)) {
                foreach ($tariff as $item) {
                    $data[$item->targetMonth] = ['powerLimit' => $item->powerLimit, 'powerCost' => $item->powerCost, 'powerOvercost' => $item->powerOvercost];
                }
            } else {
                throw new ErrorException('Тарифа на данный месяц не существует!');
            }
        }
        return $data;
    }

    /**
     * @param $cottages
     * @param $additionalCottages
     * @param $period
     * @return array
     * @throws ErrorException
     */
    public static function getInserted($cottages, $additionalCottages, $period): array
    {
        $answer = [];
        $tariff = self::getTariff($period);
        $answer['tariff'] = $tariff[$period];
        $mainData = self::getPowerData($period);
        $additionalData = self::getAdditionalPowerData($period);
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                $info = [];
                if (!empty($mainData[$cottage->cottageNumber])) {
                    $info['filled'] = true;
                }
                $info['currentData'] = $cottage->currentPowerData;
                if ($cottage->haveAdditional && $additionalCottages[$cottage->cottageNumber]->isPower) {
                    if (!empty($additionalData[$cottage->cottageNumber])) {
                        $info['additionalFilled'] = true;
                    }
                    $info['additionalData'] = $additionalCottages[$cottage->cottageNumber]->currentPowerData;
                }
                $answer['cottages'][$cottage->cottageNumber] = $info;
            }
        }
        return $answer;
    }

    /**
     * @param $cottageInfo Table_cottages
     * @return array
     */
    public static function getCottageStatus($cottageInfo): array
    {
        // проверю, выставлен ли счет на электроэнергию за предыдущий месяц
        $filledPower = Table_power_months::find()->where(['cottageNumber' => $cottageInfo->cottageNumber])->orderBy('month DESC')->one();
        if ($filledPower->month === TimeHandler::getPreviousShortMonth() || $filledPower->month === TimeHandler::getCurrentShortMonth()) {
            $filled = true;
            // проверю, можно ли отменить платёж
        } else {
            $filled = false;
        }
        // проверю, как давно оплачено электричество
        $difference = TimeHandler::checkMonthDifference($cottageInfo->powerPayFor);
        $powerPayDifference = '';
        if ($difference > 1) {
            $powerPayDifference = '(' . GrammarHandler::handleMonthsDifference(--$difference) . ' назад)';
        }
        return ['filledPower' => $filled, 'lastPowerFillDate' => $filledPower->month, 'powerPayDifference' => $powerPayDifference, 'powerDebt' => $cottageInfo->powerDebt,
            'powerPayed' => $filledPower->payed];
    }

    /**
     * @param $cottageInfo Table_additional_cottages
     * @return array
     */
    public static function getAdditionalCottageStatus($cottageInfo): array
    {
        if ($cottageInfo->isPower) {
            // проверю, выставлен ли счет на электроэнергию за предыдущий месяц
            $filledPower = Table_additional_power_months::findOne(['cottageNumber' => $cottageInfo->masterId, 'month' => TimeHandler::getPreviousShortMonth()]);
            if ($filledPower === null) {
                $filledPower = Table_additional_power_months::find()->where(['cottageNumber' => $cottageInfo->masterId])->orderBy('month DESC')->one();
                $filled = false;
            } else {
                $filled = true;
            }
            // проверю, как давно оплачено электричество
            $difference = TimeHandler::checkMonthDifference($cottageInfo->powerPayFor);
            $powerPayDifference = '';
            if ($difference > 1) {
                $powerPayDifference = '(' . GrammarHandler::handleMonthsDifference(--$difference) . ' назад)';
            }
            return ['filledPower' => $filled, 'lastPowerFillDate' => $filledPower->month, 'powerPayDifference' => $powerPayDifference, 'powerDebt' => $cottageInfo->powerDebt, 'powerPayed' => $filledPower->payed];
        }
        return [];
    }

    /**
     * @param $period string|array
     * @return array
     */
    public static function getPowerData($period): array
    {
        $info = null;
        $values = [];
        if (is_string($period)) {
            $info = TimeHandler::isMonth($period);
        }
        $data = Table_power_months::find()->where(['month' => $info['full']])->all();
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    $values[$item->cottageNumber] = $item;
                }
            } else {
                $values[$data->month] = $data;
            }
        }
        return $values;
    }

    public static function getAdditionalPowerData($period): array
    {
        $info = 'null';
        $values = [];
        if (is_string($period)) {
            $info = TimeHandler::isMonth($period);
        }
        $data = Table_additional_power_months::find()->where(['month' => $info['full']])->all();
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    $values[$item->cottageNumber] = $item;
                }
            } else {
                $values[$data->month] = $data;
            }
        }
        return $values;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @return PowerDebt[]
     */
    public static function getDebtReport($cottage): array
    {
        $answer = [];
        $isMain = Cottage::isMain($cottage);
        $query = null;
        if (!$isMain) {
            if (empty($cottage->isPower)) {
                return [];
            }
            $query = Table_additional_power_months::find()->where(['cottageNumber' => $cottage->masterId, 'payed' => 'no'])->andWhere(['>', 'difference', 0]);
        } else {
            $query = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber, 'payed' => 'no'])->andWhere(['>', 'difference', 0]);
        }

        $rates = $query->orderBy('searchTimestamp')->all();
        if (!empty($rates)) {
            foreach ($rates as $rate) {
                if ($isMain) {
                    $payedQuery = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => $rate->month])->all();
                } else {
                    $payedQuery = Table_additional_payed_power::find()->where(['cottageId' => $cottage->masterId, 'month' => $rate->month])->all();
                }
                if (!empty($payedQuery)) {
                    $partialPayed = 0;
                    foreach ($payedQuery as $item) {
                        $partialPayed += $item->summ;
                    }
                } else {
                    $partialPayed = 0;
                }
                // если оплачено меньше суммы долга
                if (CashHandler::toRubles($partialPayed) != $rate->totalPay) {
                    $answerItem = new PowerDebt();
                    $answerItem->powerData = $rate;
                    $answerItem->tariff = Table_tariffs_power::findOne(['targetMonth' => $rate->month]);
                    // проверю оплату
                    $answerItem->partialPayed = $partialPayed;
                    // посчитаю сумму без учёта льготного лимита
                    $answerItem->withoutLimitAmount = CashHandler::toRubles($answerItem->powerData->difference * $answerItem->tariff->powerOvercost);
                    $answer[] = $answerItem;
                }
            }
        }
        return $answer;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages|int|string
     * @param $powerPeriods array
     * @param bool $additional
     * @return array|string
     * @throws ErrorException
     * @throws ExceptionWithStatus
     */
    public static function createPayment($cottage, $powerPeriods, $additional = false)
    {
        $answer = '';
        $summ = 0;
        foreach ($powerPeriods as $key => $value) {
            $toPay = CashHandler::toRubles($value['value']);
            // найду тариф
            $tariff = Table_tariffs_power::findOne(['targetMonth' => $key]);
            if ($additional) {
                $data = Table_additional_power_months::findOne(['cottageNumber' => $cottage->masterId, 'month' => $key]);
            } else {
                $data = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => $key]);
            }

            if ($data !== null && $tariff !== null) {
                // посчитаю максимальную сумму
                if (!empty($value['no_limit'])) {
                    $maxAmount = $data->difference * $tariff->powerOvercost;
                    $cottage->powerDebt += $maxAmount - $data->totalPay;
                    $cottage->save();
                    // изменю информацию в базе
                    $data->totalPay = $maxAmount;
                    $data->inLimitSumm = 0;
                    $data->inLimitPay = 0;
                    $data->overLimitSumm = $data->difference;
                    $data->overLimitPay = $data->totalPay;
                    $data->save();
                } else {
                    $maxAmount = self::count($data, $tariff, $cottage->cottageNumber);
                }
            }

            if ($additional) {
                $payedBefore = Table_additional_payed_power::find()->where(['month' => $key, 'cottageId' => $cottage->masterId])->all();
            } else {
                $payedBefore = Table_payed_power::find()->where(['month' => $key, 'cottageId' => $cottage->cottageNumber])->all();
            }
            $payedSumm = 0;
            if (!empty($payedBefore)) {
                foreach ($payedBefore as $item) {
                    $payedSumm += CashHandler::toRubles($item->summ);
                }
            }
            // todo сделать проверку на сумму для оплаты после замены счётчика, когда часть лимита потрачена по другому счётчику
            /*if (CashHandler::toRubles($toPay) > CashHandler::toRubles($maxAmount - $payedSumm)) {
                throw new ExceptionWithStatus('Сумма оплаты ' . CashHandler::toRubles($toPay) . ' за электроэнергию за ' . $key . ' больше максимальной- ' . CashHandler::toRubles($maxAmount - $payedSumm));
            }*/
            $realPowerLimit = $cottage->cottageNumber === 88 ? 100 : $tariff->powerLimit;
            // проверю наличие индивидуальных данных
            $personalRate = PersonalPower::findOne(['month' => $key, 'cottage_number' => $cottage->cottageNumber]);
            if($personalRate !== null){
                if(!empty($personalRate->fixed_amount)){
                    $answer .= "<month date='$key' summ='{$personalRate->fixed_amount}' prepayed='$payedSumm' old-data='{$data->oldPowerData}' new-data='{$data->newPowerData}' powerLimit='{$realPowerLimit}' powerCost='{$tariff->powerCost}' powerOvercost='{$tariff->powerOvercost}' difference='{$data->difference}' in-limit='0' over-limit='{$data->difference}' in-limit-cost='0' over-limit-cost='{$personalRate->fixed_amount}' corrected='" . (!empty($value['no_limit']) ? '1' : '0') . "'/>";
                }
                else{
                    $answer .= "<month date='$key' summ='{$toPay}' prepayed='$payedSumm' old-data='{$data->oldPowerData}' new-data='{$data->newPowerData}' powerLimit='{$realPowerLimit}' powerCost='{$personalRate->cost}' powerOvercost='{$personalRate->over_cost}' difference='{$data->difference}' in-limit='{$data->inLimitSumm}' over-limit='{$data->overLimitSumm}' in-limit-cost='{$data->inLimitPay}' over-limit-cost='{$data->overLimitPay}' corrected='" . (!empty($value['no_limit']) ? '1' : '0') . "'/>";
                }
            }
            else{
                $answer .= "<month date='$key' summ='{$toPay}' prepayed='$payedSumm' old-data='{$data->oldPowerData}' new-data='{$data->newPowerData}' powerLimit='{$realPowerLimit}' powerCost='{$tariff->powerCost}' powerOvercost='{$tariff->powerOvercost}' difference='{$data->difference}' in-limit='{$data->inLimitSumm}' over-limit='{$data->overLimitSumm}' in-limit-cost='{$data->inLimitPay}' over-limit-cost='{$data->overLimitPay}' corrected='" . (!empty($value['no_limit']) ? '1' : '0') . "'/>";
            }
            $summ += $toPay;
        }
        if ($additional) {
            $answer = /** @lang xml */
                "<additional_power cost='{$summ}'>" . $answer . '</additional_power>';
        } else {
            $answer = /** @lang xml */
                "<power cost='{$summ}'>" . $answer . '</power>';
        }
        return ['text' => $answer, 'summ' => CashHandler::rublesRound($summ)];
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $billInfo Table_payment_bills
     * @param $payments array
     * @param $transaction Table_transactions
     * @param $additional boolean
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, $transaction, $additional = false)
    {
        // зарегистрирую платежи
        $realSumm = 0;
        foreach ($payments['values'] as $payment) {
            self::insertPayment($payment, $cottageInfo, $billInfo, $transaction, $additional);
            if (!empty($payment['corrected']) && $payment['corrected'] === '1') {
                $realSumm += $payment['pay-with-limit'];
            } else {
                $realSumm += CashHandler::toRubles($payment['summ']);
            }
        }
        $cottageInfo->powerPayFor = end($payments['values'])['date'];
        $cottageInfo->powerDebt -= CashHandler::rublesRound($realSumm);
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $date
     * @param $summ
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ)
    {
        // проверю тип участка
        if (Cottage::isMain($cottageInfo)) {
            $cottageId = $cottageInfo->cottageNumber;
            $write = new Table_payed_power();
            $write->cottageId = $cottageInfo->cottageNumber;
        } else {
            $cottageId = $cottageInfo->masterId;
            $write = new Table_additional_payed_power();
            $write->cottageId = $cottageInfo->masterId;
        }
        $write->billId = $bill->id;
        $write->month = $date;
        $write->summ = $summ;
        $write->paymentDate = $transaction->bankDate;
        $write->transactionId = $transaction->id;
        $write->save();
        $paymentMonth = self::getPaymentMonth($cottageId, $date, !Cottage::isMain($cottageInfo));
        $paymentMonth->save();
        self::recalculatePower($date);
    }

    /**
     * @param $payment
     * @param $cottageInfo
     * @param $billInfo
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function insertPayment($payment, $cottageInfo, $billInfo, $transaction, $additional = false)
    {
        $partialPayed = self::checkPartialPayedMonth($cottageInfo);
        $summ = CashHandler::toRubles($payment['summ']);
        if ($summ > 0) {
            if (!empty($partialPayed) && $partialPayed['date'] === $payment['date']) {
                $cottageInfo->partialPayedPower = null;
            }


            if ($additional) {
                $cottageId = $cottageInfo->masterId;
                $write = new Table_additional_payed_power();
                $write->cottageId = $cottageInfo->masterId;
            } else {
                $cottageId = $cottageInfo->cottageNumber;
                $write = new Table_payed_power();
                $write->cottageId = $cottageInfo->cottageNumber;
            }
            $write->billId = $billInfo->id;
            $write->transactionId = $transaction->id;
            $write->month = $payment['date'];
            $write->summ = $summ;
            $write->paymentDate = $transaction->bankDate;
            $write->save();
            self::recalculatePower($payment['date']);
        } else if ($additional) {
            $cottageId = $cottageInfo->masterId;
        } else {
            $cottageId = $cottageInfo->cottageNumber;
        }
        $paymentMonth = self::getPaymentMonth($cottageId, $payment['date'], $additional);
        $paymentMonth->payed = 'yes';
        $paymentMonth->save();
    }

    /**
     * @param $cottageNumber int|string
     * @param $period string
     * @param bool $additional
     * @return Table_additional_power_months|Table_power_months
     */
    public static function getPaymentMonth($cottageNumber, $period, $additional = false)
    {
        if ($additional) {
            $data = Table_additional_power_months::findOne(['cottageNumber' => $cottageNumber, 'month' => $period]);
        } else {
            $data = Table_power_months::findOne(['cottageNumber' => $cottageNumber, 'month' => $period]);
        }
        if ($data !== null) {
            return $data;
        }
        throw new InvalidArgumentException("Данные по участку $cottageNumber за $period не заполнены!");
    }

    /**
     * Пересчёт статистики использования электроэнергии за месяц
     * @param $period
     */
    public static function recalculatePower($period): void
    {
        $month = TimeHandler::isMonth($period);
        $cottagesCount = Table_cottages::find()->count();
        $additionalCottagesCount = Table_additional_cottages::find()->count();
        $tariff = Table_tariffs_power::findOne(['targetMonth' => $month['full']]);
        if ($tariff !== null) {
            $payedNow = 0;
            $payedCounter = 0;
            $additionalPayedCounter = 0;
            // получу оплаченные счета за этот месяц
            $payed = Table_payed_power::find()->where(['month' => $month['full']])->all();
            $additionalPayed = Table_additional_payed_power::find()->where(['month' => $month['full']])->all();
            if (!empty($payed)) {
                foreach ($payed as $item) {
                    $payedNow += $item->summ;
                    $payedCounter++;
                }
            }
            if (!empty($additionalPayed)) {
                foreach ($additionalPayed as $item) {
                    $payedNow += $item->summ;
                    $additionalPayedCounter++;
                }
            }
            $fill = 0;
            $additionalFill = 0;
            $usedEnergy = 0;
            $neededSumm = 0;
            // следующий этап- получу данные о потраченной за месяц энергии и количество участков, по которым заполнены данные
            $filled = Table_power_months::find()->where(['month' => $month['full']])->all();
            $additionalFilled = Table_additional_power_months::find()->where(['month' => $month['full']])->all();
            if (!empty($filled)) {
                foreach ($filled as $item) {
                    $fill++;
                    $usedEnergy += $item->difference;
                    $neededSumm += $item->totalPay;
                }
            }
            if (!empty($additionalFilled)) {
                foreach ($additionalFilled as $item) {
                    $additionalFill++;
                    $usedEnergy += $item->difference;
                    $neededSumm += $item->totalPay;
                }
            }
            $tariff->fullSumm = $neededSumm;
            $tariff->payedSumm = $payedNow;
            $tariff->paymentInfo = /** @lang xml */
                "<info><cottages_count>$cottagesCount</cottages_count><additional_cottages_count>$additionalCottagesCount</additional_cottages_count><pay>$payedCounter</pay><pay_additional>$additionalPayedCounter</pay_additional><fill>$fill</fill><fill_additional>$additionalFill</fill_additional><used_energy>$usedEnergy</used_energy></info>";
            /** @var Table_tariffs_power $tariff */
            $tariff->save();
        }
    }

    /**
     * @param $period string|boolean
     * @return Table_tariffs_power
     * @throws InvalidArgumentException
     * @throws InvalidValueException
     */
    public static function getRowTariff($period = false): Table_tariffs_power
    {
        if (!empty($period)) {
            $month = TimeHandler::isMonth($period);
            $data = Table_tariffs_power::findOne(['targetMonth' => $month['full']]);
            if ($data !== null) {
                return $data;
            }
            throw new InvalidArgumentException('Тарифа на данный месяц не существует!');
        }
        $data = Table_tariffs_power::find()->orderBy('searchTimestamp DESC')->one();
        if (!empty($data)) {
            return $data;
        }
        throw new InvalidValueException('Тарифы  не обнаружены');
    }

    /**
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $paymentSumm double
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function handlePartialPayment($bill, $paymentSumm, $cottageInfo, $transaction)
    {
        $main = Cottage::isMain($cottageInfo);
        $payedMonths = null;
        $partialPayedMonth = null;
        $dom = new DOMHandler($bill->bill_content);
        // получу данные о полном счёте за электричество
        if ($main) {
            $powerMonths = $dom->query('//power/month');
        } else {
            $powerMonths = $dom->query('//additional_power/month');
        }
        // если ранее производилась оплата электричества по данному счёту- посчитаю сумму оплаты
        if ($main) {
            $payedBefore = Table_payed_power::find()->where(['billId' => $bill->id])->all();
        } else {
            $payedBefore = Table_additional_payed_power::find()->where(['billId' => $bill->id])->all();
        }
        $payedSumm = 0;
        if (!empty($payedBefore)) {
            foreach ($payedBefore as $item) {
                $payedSumm += CashHandler::toRubles($item->summ);
            }
        }
        /** @var DOMElement $month */
        foreach ($powerMonths as $month) {
            // получу сумму платежа
            $powerMonth = $month->getAttribute('date');
            // получу данные о месяце оплаты

            // проверю предыдущие оплаты месяца
            $summ = CashHandler::toRubles(DOMHandler::getFloatAttribute($month, 'summ'));
            // если ранее по счёту оплачено
            if ($payedSumm >= $summ) {
                $payedSumm -= $summ;
                continue;
            }
            $summWithPrepay = $summ - $payedSumm;
            if ($summWithPrepay <= $paymentSumm) {
                // денег хватает на полую оплату месяца. Добавляю его в список полностью оплаченных и вычитаю из общей суммы стоимость месяца
                $payedMonths [] = ['date' => $month->getAttribute('date'), 'summ' => $summWithPrepay, 'is_limit_ignored' => $month->getAttribute('corrected')];
                $paymentSumm -= $summWithPrepay;
                $payedSumm = 0;
            } elseif ($paymentSumm > 0) {
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                $partialPayedMonth = ['date' => $month->getAttribute('date'), 'summ' => $paymentSumm, 'is_limit_ignored' => $month->getAttribute('corrected')];
                break;
            }
        }
        if (!empty($payedMonths)) {
            // зарегистрирую каждый месяц как оплаченный
            foreach ($payedMonths as $payedMonth) {
                $date = $payedMonth['date'];
                $summ = $payedMonth['summ'];

                $payed = self::getPreviousPayed($cottageInfo, $date);
                $data = $main ? Table_power_months::findOne(['cottageNumber' => $cottageInfo->cottageNumber, 'month' => $date]) : Table_additional_power_months::findOne(['cottageNumber' => $cottageInfo->masterId, 'month' => $date]);
                if ($data !== null) {
                    // проверю, не игнорируется ли лимит
                    if ($payedMonth['is_limit_ignored'] === 1) {
                        $requiredAmount = $data->difference * Table_tariffs_power::findOne(['targetMonth' => $powerMonth])->powerOvercost;
                    } else {
                        $requiredAmount = $data->totalPay;
                    }
                    self::insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ);
                    $cottageInfo->powerDebt = CashHandler::rublesMath($cottageInfo->powerDebt - $summ);
                    if ($requiredAmount - $payed - $summ == 0) {
                        // отмечу месяц последним оплаченным для участка
                        $cottageInfo->powerPayFor = $date;
                        $cottageInfo->partialPayedPower = null;
                    }
                    $cottageInfo->save();
                }
            }
        }
        if (!empty($partialPayedMonth)) {
            $date = $partialPayedMonth['date'];
            $summ = $partialPayedMonth['summ'];
            $summForSave = $summ;
            // проверю существование частично оплаченного месяца у данного участка
            $savedPartial = self::checkPartialPayedMonth($cottageInfo);
            // проверю, хватит ли совместных средств для полной оплаты месяца
            if ($savedPartial) {
                $prevPayment = CashHandler::toRubles($savedPartial['summ']);
                // получу полную стоимость данного месяца
                /** @var DOMElement $monthInfo */
                if ($main) {
                    $monthInfo = $dom->query('//power/month[@date="' . $date . '"]')->item(0);
                } else {
                    $monthInfo = $dom->query('//additional_power/month[@date="' . $date . '"]')->item(0);
                }
                if($monthInfo !== null){
                    $fullPaySumm = CashHandler::toRubles($monthInfo->getAttribute('summ'));
                    if ($prevPayment + $summ === $fullPaySumm) {
                        self::insertSinglePayment($cottageInfo, $bill, $transaction, $date, $summ - $prevPayment);
                        $cottageInfo->powerPayFor = $date;
                        $cottageInfo->partialPayedPower = null;
                        return;
                    }
                }
                $summForSave += $prevPayment;
            }
            $cottageInfo->powerDebt = CashHandler::rublesMath($cottageInfo->powerDebt - $summ);
            // зарегистрирую платёж в таблице оплаты электроэнергии
            if ($main) {
                $table = new Table_payed_power();
                $table->cottageId = $cottageInfo->cottageNumber;
            } else {
                $table = new Table_additional_payed_power();
                $table->cottageId = $cottageInfo->masterId;
            }
            // отмечу месяц как оплаченный частично
            $cottageInfo->partialPayedPower = "<partial date='$date' summ='$summForSave'/>";
            $table->billId = $bill->id;
            $table->month = $date;
            $table->summ = $summ;
            $table->paymentDate = $transaction->bankDate;
            $table->transactionId = $transaction->id;
            $table->save();
        }
    }

    private static function checkPartialPayedMonth($cottageInfo)
    {
        if (!empty($cottageInfo->partialPayedPower)) {
            $dom = new DOMHandler($cottageInfo->partialPayedPower);
            $root = $dom->query('/partial');
            return DOMHandler::getElemAttributes($root->item(0));
        }
        return null;
    }

    public function prepare($cottageNumber)
    {
        $this->currentCondition = Cottage::getCottageInfo($cottageNumber);
        $this->cottageNumber = $this->currentCondition->cottageNumber;
        $this->month = TimeHandler::getPreviousShortMonth();
    }

    public function prepareCurrent($cottageNumber)
    {
        $this->currentCondition = Cottage::getCottageInfo($cottageNumber);
        $this->cottageNumber = $this->currentCondition->cottageNumber;
        $this->month = TimeHandler::getCurrentShortMonth();
    }

    /**
     * @param $cottageNumber
     * @param $additional
     * @return array|bool
     * @throws Throwable
     */
    public static function cancelPowerFill($cottageNumber, $additional)
    {
        $transaction = new DbTransaction();
        if ($additional) {
            $cottage = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottage = Cottage::getCottageInfo($cottageNumber);
        }
        $data = self::getMonthInfo($cottage);
        // проверю, что счёт не оплачивался, если оплачивался- верну предупреждение об этом
        if (self::isPayed($data)) {
            return ['status' => 2, 'message' => 'Невозможно удалить период, так как он уже частично или полностью оплачен'];
        }
        // получу счета, в которых присутствует данный месяц
        $billsForDelete = BillsHandler::getMonthContains($cottage, $data);
        if (!empty($billsForDelete)) {
            foreach ($billsForDelete as $item) {
                // если счёт уже частично оплачен- верну ошибку удаления
                if ($item->payedSumm > 0) {
                    return ['status' => 2, 'message' => "Удаление периода невозможно, счёт № {$item->id}, в котором содержится данный период, уже частично или полностью оплачен. Удаление периода привело бы к непредвиденным сложностям."];
                }
                $item->delete();
            }
        }
        // проверю наличие пени за данный период
        // todo реализовать проверку пени
        $cottage->powerDebt = CashHandler::rublesMath($cottage->powerDebt - $data->totalPay);
        $data->delete();
        $cottage->currentPowerData = self::getLastFilled($cottage)->newPowerData;
        $cottage->save();
        $transaction->commitTransaction();
        return ['status' => 1];
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param $period
     * @return Table_additional_power_months|Table_power_months
     */
    private static function getMonthInfo($cottage, $period = null)
    {{
        $main = Cottage::isMain($cottage);
        if (empty($period))
            if ($main) {
                $data = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->orderBy('searchTimestamp DESC')->one();
            } else {
                $data = Table_additional_power_months::find()->where(['cottageNumber' => $cottage->masterId])->orderBy('searchTimestamp DESC')->one();
            }
            if (!empty($data)) {
                return $data;
            }
        }
        if ($main) {
            $data = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => $period]);
        } else {
            $data = Table_additional_power_months::findOne(['cottageNumber' => $cottage->masterId, 'month' => $period]);
        }
        if ($data !== null) {
            return $data;
        }
        throw new InvalidArgumentException('Данные за месяц не заполнены');
    }


    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return Table_power_months|Table_additional_power_months
     */
    public static function getLastFilled($cottageInfo)
    {
        if (Cottage::isMain($cottageInfo)) {
            return Table_power_months::find()->where(['cottageNumber' => $cottageInfo->cottageNumber])->orderBy('searchTimestamp DESC')->one();
        }

        return Table_additional_power_months::find()->where(['cottageNumber' => $cottageInfo->masterId])->orderBy('searchTimestamp DESC')->one();

    }

    /**
     * @param $cottageNumber
     * @return array
     */
    public static function checkCurrent($cottageNumber): array
    {
        try {
            self::getTariff(TimeHandler::getCurrentShortMonth());
        } catch (ErrorException $e) {
            return ['status' => 3];
        }
        $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        $lastFilled = self::getLastFilled($cottageInfo);
        if ($lastFilled !== null) {
            if ($lastFilled->month === TimeHandler::getCurrentShortMonth()) {
                return ['status' => 2];
            }
            return ['status' => 1, 'lastData' => $lastFilled->newPowerData];
        }
        return ['status' => 1];
    }

    public static function getUnfilled($period)
    {
        // попробую получить тариф за месяц
        try {
            self::getTariff($period);
        } catch (ErrorException $e) {
            // если тариф не заполнен- верну данные последнего заполненного тарифа
            return Table_tariffs_power::find()->orderBy('searchTimestamp DESC')->one();
        }
        return false;
    }

    /**
     * @param int $difference
     * @return false|float|int
     * @throws ErrorException
     */
    private function countCost(int $difference)
    {
        // расчитаю стоимость электроэнергии
        $tariff = self::getTariff($this->month);
        // для участка 88 добавлю лимит в 100 квтч

        $powerLimit = $this->cottageNumber === '88' ? 100 : $tariff[$this->month]['powerLimit'];

        if ($difference > $powerLimit) {
            $inLimitSumm = $powerLimit;
            $inLimitPay = CashHandler::rublesMath($inLimitSumm * $tariff[$this->month]['powerCost']);
            $overLimitSumm = $difference - $inLimitSumm;
            $overLimitPay = CashHandler::rublesMath($overLimitSumm * $tariff[$this->month]['powerOvercost']);
            $totalPay = CashHandler::rublesMath($inLimitPay + $overLimitPay);
        } else {
            $inLimitPay = CashHandler::rublesMath($difference * $tariff[$this->month]['powerCost']);
            $totalPay = $inLimitPay;
        }
        return $totalPay;
    }

    /**
     * @param $data Table_power_months
     * @param $tariff Table_tariffs_power
     * @param $cottageNumber
     * @return float
     */
    private static function count($data, $tariff, $cottageNumber): float
    {
        $powerLimit = $cottageNumber === 88 ? 100 : $tariff->powerLimit;
        if ($data->difference > $powerLimit) {
            $inLimitSumm = $powerLimit;
            $inLimitPay = CashHandler::rublesMath($inLimitSumm * $tariff->powerCost);
            $overLimitSumm = $data->difference - $inLimitSumm;
            $overLimitPay = CashHandler::rublesMath($overLimitSumm * $tariff->powerOvercost);
            $totalPay = CashHandler::rublesMath($inLimitPay + $overLimitPay);
        } else {
            $inLimitPay = CashHandler::rublesMath($data->difference * $tariff->powerCost);
            $totalPay = $inLimitPay;
        }
        return $totalPay;
    }


    /**
     * @param Table_power_months $existentValue
     * @param Table_tariffs_power $tariff
     * @return array
     */
    private static function countCostDetails(Table_power_months $existentValue, Table_tariffs_power $tariff): array
    {
        $powerLimit = $existentValue->cottageNumber === 88 ? 100 : $tariff->powerLimit;
        $over_cost = 0;
        if ($existentValue->difference > $powerLimit) {
            $cost = CashHandler::rublesMath($powerLimit * $tariff->powerCost);
            $over_cost = CashHandler::rublesMath(($existentValue->difference - $powerLimit) * $tariff->powerOvercost);
            $total = CashHandler::rublesMath($cost + $over_cost);
        } else {
            $cost = CashHandler::rublesMath($existentValue->difference * $tariff->powerCost);
            $total = $cost;
        }
        return ['cost' => $cost, 'over_cost' => $over_cost, 'total' => $total];
    }
}