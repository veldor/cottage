<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 13:35
 */

namespace app\models;


use app\validators\CashValidator;
use app\validators\CheckCottageNoRegistred;
use app\validators\CheckMonthValidator;
use DOMElement;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;
use \Exception;

class PowerHandler extends Model
{
    const SCENARIO_NEW_RECORD = 'new_record';
    const SCENARIO_NEW_TARIFF = 'new_tariff';

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
        if(!empty($pays)){
            foreach ($pays as $pay) {
                /** @var Table_payed_power $pay */
                $pay->paymentDate = $timestamp;
                $pay->save();
            }
        }
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
            $db = Yii::$app->db;
            //$transaction = $db->beginTransaction();
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
                        foreach ($monthsList as $key => $item) {
                            $attributes = ['cottageNumber' => $this->cottageNumber, 'month' => $key, 'newPowerData' => $oldData->newPowerData, 'additional' => $this->additional];
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
                    $powerLimit = $tariff[$this->month]['powerLimit'] - $limitUsed;
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
     * @param $cottage int|string|Table_cottages|Table_additional_cottages
     * @param bool $additional
     * @return array
     * @throws ErrorException
     */
    public static function getDebtReport($cottage, $additional = false): array
    {
        // получу данные о частично оплаченных месяцах
        $partialPayed = self::checkPartialPayedMonth($cottage);
        $query = null;
        if ($additional) {
            if (!is_object($cottage)) {
                $cottage = AdditionalCottage::getCottage($cottage);
            }
            if (!$cottage->isPower) {
                return [];
            }
            $query = Table_additional_power_months::find()->where(['cottageNumber' => $cottage->masterId]);
        } else {
            if (!is_object($cottage)) {
                $cottage = Cottage::getCottageInfo($cottage);
            }
            $query = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber]);
        }
        $tariffs = self::getTariff(['start' => $cottage->powerPayFor]);
        $rates = $query->andWhere(['>', 'month', $cottage->powerPayFor])->orderBy('searchTimestamp')->all();
        $answer = [];
        if (!empty($rates)) {
            foreach ($rates as $rate) {
                foreach ($rate as $key => $value) {
                    $answer[$rate->month][$key] = $value;
                }
                if ($rate->difference > 0) {
                    if (!empty($partialPayed) && $partialPayed['date'] === $rate['month']) {
                        $prepayed = $partialPayed['summ'];
                    } else {
                        $prepayed = 0;
                    }
                    foreach ($tariffs[$rate->month] as $key => $value) {
                        $answer[$rate->month][$key] = $value;
                        $answer[$rate->month]['prepayed'] = $prepayed;
                    }
                }
            }
        }
        return $answer;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages|int|string
     * @param $powerPeriods int|string
     * @param $corrected string
     * @param bool $additional
     * @return array|string
     * @throws ErrorException
     */
    public static function createPayment($cottage, $powerPeriods, $corrected, $additional = false)
    {
        $partialPayed = self::checkPartialPayedMonth($cottage);
        if ($additional) {
            if (!is_object($cottage)) {
                $cottage = AdditionalCottage::getCottage($cottage);
            }
            if (!$cottage->isPower) {
                return [];
            }
        } else if (!is_object($cottage)) {
            $cottage = Cottage::getCottageInfo($cottage);
        }
        // получу все задолженности
        $duty = self::getDebtReport($cottage, $additional);
        if (!empty($corrected)) {
            $noLimArr = array_flip(explode(' ', trim($corrected)));
        }
        $answer = '';
        $summ = 0;
        if (!empty($duty)) {
            foreach ($duty as $key => $value) {
                if ($powerPeriods === 0) {
                    break;
                }
                if ($value['difference'] > 0) {

                    if (!empty($partialPayed) && $partialPayed['date'] === $key) {
                        $prepayed = $partialPayed['summ'];
                    } else {
                        $prepayed = 0;
                    }

                    if (isset($noLimArr[$key])) {
                        $payWithLimit = "pay-with-limit='{$value['totalPay']}'";
                        $difference = CashHandler::toRubles($value['difference']);
                        $powerOvercost = CashHandler::toRubles($value['powerOvercost']);
                        $correctedSumm = CashHandler::rublesMath($difference * $powerOvercost);

                        $summ += $correctedSumm - $prepayed;
                        $answer .= "<month date='$key' summ='{$correctedSumm}' prepayed='$prepayed' old-data='{$value['oldPowerData']}' new-data='{$value['newPowerData']}' powerLimit='0' powerCost='{$value['powerCost']}' in-limit-cost='0' in-limit='0' powerOvercost='{$value['powerOvercost']}' difference='{$value['difference']}' over-limit='{$value['difference']}' over-limit-cost='{$correctedSumm}' corrected='1' $payWithLimit/>";
                    } else {
                        $cost = CashHandler::toRubles($value['totalPay']) - $prepayed;
                        $answer .= "<month date='$key' summ='{$cost}' prepayed='$prepayed' old-data='{$value['oldPowerData']}' new-data='{$value['newPowerData']}' powerLimit='{$value['powerLimit']}' powerCost='{$value['powerCost']}' powerOvercost='{$value['powerOvercost']}' difference='{$value['difference']}' in-limit='{$value['inLimitSumm']}' over-limit='{$value['overLimitSumm']}' in-limit-cost='{$value['inLimitPay']}' over-limit-cost='{$value['overLimitPay']}' corrected='0'/>";
                        $summ += $cost;
                        --$powerPeriods;
                    }
                }
                /* else {
                     $answer .= "<month date='$key' summ='0' new-data='{$value['newPowerData']}' difference='0'/>";
                 }*/
            }
            if ($additional) {
                $answer = /** @lang xml */
                    "<additional_power cost='{$summ}'>" . $answer . '</additional_power>';
            } else {
                $answer = /** @lang xml */
                    "<power cost='{$summ}'>" . $answer . '</power>';
            }
        }
        return ['text' => $answer, 'summ' => CashHandler::rublesRound($summ)];
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $billInfo Table_payment_bills
     * @param $payments array
     * @param $additional boolean
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, $additional = false)
    {
        // зарегистрирую платежи
        $realSumm = 0;
        foreach ($payments['values'] as $payment) {
            self::insertPayment($payment, $cottageInfo, $billInfo, $additional);
            if (!empty($payment['corrected']) && $payment['corrected'] === '1') {
                $realSumm += $payment['pay-with-limit'];
            } else {
                $realSumm += CashHandler::toRubles($payment['summ']);
            }
        }
        $cottageInfo->powerPayFor = end($payments['values'])['date'];
        $cottageInfo->powerDebt -= CashHandler::rublesRound($realSumm);
    }

    public static function insertSinglePayment($cottageInfo, $billId, $date, $summ, $time)
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
        $write->billId = $billId;
        $write->month = $date;
        $write->summ = $summ;
        $write->paymentDate = $time;
        $write->save();
        $paymentMonth = self::getPaymentMonth($cottageId, $date, !Cottage::isMain($cottageInfo));
        $paymentMonth->payed = 'yes';
        $paymentMonth->save();
        self::recalculatePower($date);
    }

    public static function insertPayment($payment, $cottageInfo, $billInfo, $additional = false)
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
            $write->month = $payment['date'];
            $write->summ = $summ;
            $write->paymentDate = $billInfo->paymentTime;
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

    public static function recalculatePower($period)
    {
        $month = TimeHandler::isMonth($period);
        $cottagesCount = Table_cottages::find()->count();
        $additionalCottagesCount = Table_additional_cottages::find()->count();
        $tariff = Table_tariffs_power::findOne(['targetMonth' => $month['full']]);
        if (!empty($tariff)) {
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
            if (!empty($data)) {
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
     * @param $billDom DOMHandler
     * @param $paymentSumm
     * @param $cottageInfo
     * @param $billId
     * @param $paymentTime
     */
    public static function handlePartialPayment($billDom, $paymentSumm, $cottageInfo, $billId, $paymentTime)
    {
        $main = Cottage::isMain($cottageInfo);
        $payedMonths = null;
        $partialPayedMonth = null;

        /** @var DOMElement $powerContainer */
        // добавлю оплаченную сумму в xml
        if ($main) {
            $powerContainer = $billDom->query('//power')->item(0);
        } else {
            $powerContainer = $billDom->query('//additional_power')->item(0);
        }
        // проверю, не оплачивалась ли часть платежа ранее
        $payedBefore = CashHandler::toRubles(0 . $powerContainer->getAttribute('payed'));
        $powerContainer->setAttribute('payed', $paymentSumm + $payedBefore);
        // получу данные о полном счёте за электричество
        if ($main) {
            $powerMonths = $billDom->query('//power/month');
        } else {
            $powerMonths = $billDom->query('//additional_power/month');
        }
        /** @var DOMElement $month */
        foreach ($powerMonths as $month) {
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($month, 'summ');
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if ($summ - $prepayed <= $paymentSumm) {
                // денег хватает на полую оплату месяца. Добавляю его в список полностью оплаченных и вычитаю из общей суммы стоимость месяца
                $payedMonths [] = ['date' => $month->getAttribute('date'), 'summ' => $summ - $prepayed];
                $paymentSumm -= $summ - $prepayed;
                if ($prepayed > 0) {
                    // ранее частично оплаченный квартал считаю полностью оплаченным
                    $cottageInfo->partialPayedPower = null;
                }
            } elseif ($paymentSumm > 0) {
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                $partialPayedMonth = ['date' => $month->getAttribute('date'), 'summ' => $paymentSumm];
                break;
            }
        }
        if (!empty($payedMonths)) {
            // зарегистрирую каждый месяц как оплаченный
            foreach ($payedMonths as $payedMonth) {
                $date = $payedMonth['date'];
                $summ = $payedMonth['summ'];
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->powerPayFor = $date;
                $cottageInfo->powerDebt = CashHandler::rublesMath($cottageInfo->powerDebt - $summ);
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
                $monthInfo = $billDom->query('//month[@date="' . $date . '"]')->item(0);
                $fullPaySumm = CashHandler::toRubles($monthInfo->getAttribute('summ'));
                if ($prevPayment + $summ === $fullPaySumm) {
                    self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prevPayment, $paymentTime);
                    $cottageInfo->powerPayFor = $date;
                    $cottageInfo->partialPayedPower = null;
                    return;
                } else {
                    $summForSave += $prevPayment;
                }
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
            $table->billId = $billId;
            $table->month = $date;
            $table->summ = $summ;
            $table->paymentDate = $paymentTime;
            $table->save();
        }
    }

    /**
     * @param $billDom DOMHandler
     * @param $cottageInfo
     * @param $billId
     * @param $paymentTime
     */
    public static function finishPartialPayment($billDom, $cottageInfo, $billId, $paymentTime)
    {
        $main = Cottage::isMain($cottageInfo);
        $payedMonths = null;
        $partialPayedMonth = null;
        // добавлю оплаченную сумму в xml
        if ($main) {
            /** @var DOMElement $powerContainer */
            $powerContainer = $billDom->query('//power')->item(0);
        } else {
            $powerContainer = $billDom->query('//additional_power')->item(0);
        }
        // проверю, не оплачивалась ли часть платежа ранее
        $payedBefore = CashHandler::toRubles(0 . $powerContainer->getAttribute('payed'));
        // получу данные о полном счёте за электричество
        if ($main) {
            $powerMonths = $billDom->query('//power/month');
        } else {
            $powerMonths = $billDom->query('//additional_power/month');
        }

        /** @var DOMElement $month */
        foreach ($powerMonths as $month) {
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($month, 'summ');
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if ($prepayed > 0) {
                // часть месяца оплачена заранее
                $date = $month->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->powerPayFor = $date;
                $cottageInfo->powerDebt = CashHandler::rublesMath($cottageInfo->powerDebt - ($summ - $prepayed));
            } else {
                // отмечу месяц как оплаченный полностью
                $date = $month->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->powerPayFor = $date;
                $cottageInfo->powerDebt = CashHandler::rublesMath($cottageInfo->powerDebt - $summ);
            }
        }
        $cottageInfo->partialPayedPower = null;
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
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function cancelPowerFill($cottageNumber, $additional)
    {
        if ($additional) {
            $cottage = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottage = Cottage::getCottageInfo($cottageNumber);
        }
        $data = self::getMonthInfo($cottage);
        if (($data->month === TimeHandler::getCurrentShortMonth() || $data->month === TimeHandler::getPreviousShortMonth()) && $data->payed === 'no') {
            $cottage->powerDebt = CashHandler::rublesMath($cottage->powerDebt - $data->totalPay);
            $cottage->currentPowerData = $data->oldPowerData;
            $cottage->save();
            $data->delete();
            return ['status' => 1];
        }
        return false;
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param $period
     * @return Table_additional_power_months|Table_power_months
     */
    private static function getMonthInfo($cottage, $period = null)
    {
        if (empty($period)) {
            $main = Cottage::isMain($cottage);

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
        if (!empty($data)) {
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
        } else {
            return Table_additional_power_months::find()->where(['cottageNumber' => $cottageInfo->masterId])->orderBy('searchTimestamp DESC')->one();
        }

    }

    public static function checkCurrent($cottageNumber)
    {
        try {
            self::getTariff(TimeHandler::getCurrentShortMonth());
        } catch (ErrorException $e) {
            return ['status' => 3];
        }
        $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        $lastFilled = self::getLastFilled($cottageInfo);
        if (!empty($lastFilled)) {
            if ($lastFilled->month === TimeHandler::getCurrentShortMonth()) {
                return ['status' => 2];
            } else {
                return ['status' => 1, 'lastData' => $lastFilled->newPowerData];
            }
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
            $data = Table_tariffs_power::find()->orderBy('searchTimestamp DESC')->one();
            return $data;
        }
        return false;
    }

    private function countCost(int $difference)
    {
        // расчитаю стоимость электроэнергии
        $tariff = self::getTariff($this->month);
        if ($difference > $tariff[$this->month]['powerLimit']) {
            $inLimitSumm = $tariff[$this->month]['powerLimit'];
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
}