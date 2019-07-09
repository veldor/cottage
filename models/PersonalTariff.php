<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 14:29
 */

namespace app\models;


use app\validators\CheckCottageNoRegistred;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\ActiveRecord;

class PersonalTariff extends Model
{
    public $membership;
    public $target;
    public $cottageNumber;
    public $additional = false;

    /**
     * @var $currentCondition ActiveRecord
     */
    public $currentCondition;
    protected $existentTargets;


    const SCENARIO_ENABLE = 'enable';
    const SCENARIO_DISABLE = 'disable';
    const SCENARIO_FILL = 'fill';
    const SCENARIO_CHANGE = 'change';

    public static function checkTariffsFilling($cottageInfo): bool
    {
        return $cottageInfo->individualTariff === 1 && !empty($cottageInfo->membershipPayFor) && $cottageInfo->membershipPayFor < TimeHandler::getCurrentQuarter() && !self::getMembershipRate($cottageInfo, TimeHandler::getCurrentQuarter());
    }

    public static function getUnfilledInfo($cottageInfo): array
    {
        // получу данные о последнем заполненом квартале
        $lastFilled = self::getMembershipRate($cottageInfo);
        if (!empty($lastFilled)) {
            $quarterList = TimeHandler::getQuarterList($lastFilled['quarter']);
        } else {
            $quarterList[TimeHandler::getCurrentQuarter()] = TimeHandler::getCurrentQuarter();
        }
        $cottageNumber = !empty($cottageInfo->cottageNumber) ? $cottageInfo->cottageNumber : $cottageInfo->masterId;
        return ['lastFilled' => $lastFilled, 'quarterList' => $quarterList, 'cottageNumber' => $cottageNumber];
    }

    public static function registerTargetPayment($cottageInfo, $payment)
    {
        // зарегистрирую оплату целевого платежа
        // получу данные о периоде
        $date = $payment['year'];
        $dom = new DOMHandler($cottageInfo->individualTariffRates);
        $year = $dom->query("//year[@date='$date']");
        var_dump($year->item(0));
        die('here');
    }

    public static function getCottagesWithIndividual()
    {
        $cottages = Table_cottages::find()->where(['individualTariff' => 1])->all();
        $adds = Table_additional_cottages::find()->where(['individualTariff' => 1])->all();
        return array_merge($cottages, $adds);
    }

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     */
    public static function getLastMembership($cottage)
    {
        $tariffs = self::getMembershipRates($cottage);
        var_dump($tariffs);
        return array_key_last($tariffs);
        //return $tariffs[];
    }

    /**
     * @return array
     */
    public function scenarios(): array
    {
        return [
            self::SCENARIO_ENABLE => ['cottageNumber', 'membership', 'target', 'additional'],
            self::SCENARIO_FILL => ['cottageNumber', 'membership', 'target', 'additional'],
            self::SCENARIO_CHANGE => ['cottageNumber', 'membership', 'target', 'additional'],
            self::SCENARIO_DISABLE => ['cottageNumber', 'target', 'additional'],

        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['cottageNumber'], 'required'],
            ['cottageNumber', 'integer', 'min' => 1, 'max' => 300],
            ['cottageNumber', CheckCottageNoRegistred::class],
            ['additional', 'boolean'],
        ];
    }


    /**
     * @param $cottageNumber
     * @param bool $additional
     * @return array
     */
    public static function getRequirements($cottageNumber, $additional = false): array
    {
        if ($additional) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            if ($cottageInfo->hasDifferentOwner) {
                // проверю, нет ли неоплаченных платежей по участку. Если есть- отменяю операцию
                if (Table_payment_bills_double::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
                    return ['status' => 2];
                }
            } elseif (Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
                return ['status' => 2];
            }
            if ($cottageInfo->individualTariff === 1) {
                throw new InvalidArgumentException('Персональный тариф уже активирован!');
            }
            // проверю наличие неоплаченных счетов
            $quarterList = [];
            $yearList = [];
            if ($cottageInfo->isMembership) {
                // если дата оплаты членских взносов участка меньше, чем текущий квартал- необходимо заполнить ставки по текущий квартал включительно
                $currentQuarter = TimeHandler::getCurrentQuarter();
                if ($cottageInfo->membershipPayFor < $currentQuarter) {
                    $quarterList = TimeHandler::getQuarterList($cottageInfo->membershipPayFor);
                }
            }
            if ($cottageInfo->isTarget) {
                $yearList = Table_tariffs_target::find()->select('year')->all();
            }
            return ['quarters' => $quarterList, 'years' => $yearList, 'cottageNumber' => $cottageNumber, 'additional' => 1];
        }
        if (Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
            return ['status' => 2];
        }
        // Определю, какие данные нуждаются в заполнении
        $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        if ($cottageInfo->individualTariff === 1) {
            throw new InvalidArgumentException('Персональный тариф уже активирован!');
        }
        $quarterList = [];
        // если дата оплаты членских взносов участка меньше, чем текущий квартал- необходимо заполнить ставки по текущий квартал включительно
        $currentQuarter = TimeHandler::getCurrentQuarter();
        if ($cottageInfo->membershipPayFor < $currentQuarter) {
            $quarterList = TimeHandler::getQuarterList($cottageInfo->membershipPayFor);
        }
        $yearList = Table_tariffs_target::find()->select('year')->all();
        return ['quarters' => $quarterList, 'years' => $yearList, 'cottageNumber' => $cottageNumber, 'additonal' => 0];
    }

    /**
     * @param $cottageNumber int
     * @return array|bool
     * @var $existedYear \DOMElement
     */
    public static function getAvaliableChanges($cottageNumber, $additional = false)
    {
        /**
         * @var $existedYear \DOMElement
         * @var $existedQuarter \DOMElement
         */
        // Определю, какие данные могут быть изменены
        if ($additional) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            if ($cottageInfo->hasDifferentOwner) {
                // проверю, нет ли неоплаченных платежей по участку. Если есть- отменяю операцию
                if (Table_payment_bills_double::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
                    return ['status' => 2];
                }
            } elseif (Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
                return ['status' => 2];
            }
        } else {
            // проверю, нет ли неоплаченных платежей по участку. Если есть- отменяю операцию
            if (Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => 0])->count() > 0) {
                return ['status' => 2];
            }
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        }
        if (!empty($cottageInfo)) {
            $quarterList = [];
            $yearList = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $existedQuarters = $xpath->query('/tariffs/membership/quarter');
            if ($existedQuarters->length > 0) {
                foreach ($existedQuarters as $existedQuarter) {
                    $date = $existedQuarter->getAttribute('date');
                    if ($date > $cottageInfo->membershipPayFor) {
                        $fixed = CashHandler::toRubles($existedQuarter->getAttribute('fixed'));
                        $float = CashHandler::toRubles($existedQuarter->getAttribute('float'));
                        $quarterList[$date] = ['fixed' => $fixed, 'float' => $float];
                    }
                }
            }
            $existedYears = $xpath->query('/tariffs/target/year');
            if ($existedYears->length > 0) {
                // получу данные о текущем состоянии оплаты целевых платежей
                $targetDom = new \DOMDocument('1.0', 'UTF-8');
                $targetDom->loadXML($cottageInfo->targetPaysDuty);
                $targetXpath = new \DOMXpath($targetDom);
                foreach ($existedYears as $existedYear) {
                    $date = $existedYear->getAttribute('date');
                    $fixed = CashHandler::toRubles($existedYear->getAttribute('fixed'));
                    $float = CashHandler::toRubles($existedYear->getAttribute('float'));
                    $payedBefore = 0;
                    // проверю, не производилась ли оплата по данному году по данному участку
                    if (!Table_payed_target::find()->where(['cottageId' => $cottageNumber, 'year' => $date])->count()) {
                        // пробую найти информацию о задолженностях по оплате за этот год
                        $dutyInfo = $targetXpath->query("/targets/target[@year='{$date}']");
                        if ($dutyInfo->length === 1) {
                            // получу сумму оплаченных ранее средств
                            $payedBefore = CashHandler::toRubles($dutyInfo->item(0)->getAttribute('payed'));
                        }
                        $yearList[$date] = ['fixed' => $fixed, 'float' => $float, 'payed-before' => $payedBefore];

                    }
                }
            }
            if (empty($quarterList) && empty($yearList)) {
                return ['status' => 3];
            }
            return ['membership' => $quarterList, 'target' => $yearList];
        }
        return false;
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        if ($this->additional) {
            $cottageInfo = AdditionalCottage::getCottage($this->cottageNumber);
            $requirements = self::getRequirements($cottageInfo->masterId, true);
            $tariff = '<tariffs>';
            if ($cottageInfo->isMembership) {
                $tariff .= '<membership>';
                if (!empty($requirements['quarters'])) {
                    foreach ($requirements['quarters'] as $key => $quarter) {
                        if (!$data = $this->membership[$key]) {
                            $this->addError('membership', 'Не заполнены ставки');
                            return false;
                        }
                        $fixed = $data['fixed'];
                        $float = $data['float'];
                        $tariff .= "<quarter date='{$key}' fixed='$fixed' float='$float'/>";
                    }
                }
                $tariff .= '</membership>';
            }
            if ($cottageInfo->isTarget) {
                $tariff .= '<target>';
                if (!empty($requirements['years'])) {
                    $targetDutyText = '<targets>';
                    $targetDutySumm = 0;
                    foreach ($requirements['years'] as $year) {
                        if (!$this->target[$year->year]) {
                            $this->addError('target', 'Не заполнены ставки');
                            return false;
                        }
                        if (!$data = $this->target[$year->year]) {
                            $this->addError('target', 'Не заполнены ставки');
                            return false;
                        }
                        $fixed = $data['fixed'];
                        $float = $data['float'];
                        $payed = $data['payed-before'];

                        $summ = Calculator::countFixedFloat($fixed, $float, $cottageInfo->cottageSquare);

                        if ($summ > 0) {
                            if ($payed > $summ) {
                                $this->addError('target', 'Сумма оплаты не может быть больше суммы платежа!');
                                return false;
                            }
                            if ($payed < $summ) {
                                $targetDutySumm += $summ;
                                $targetDutyText .= "<target year='{$year->year}' payed='$payed' fixed='$fixed' float='$float' square='{$this->currentCondition->cottageSquare}' summ='$summ'/>";
                            }
                        }
                        $tariff .= "<year date='{$year->year}' fixed='$fixed' float='$float'/>";
                    }
                    $targetDutyText .= '</targets>';
                    $cottageInfo->targetDebt = CashHandler::rublesRound($targetDutySumm);
                    $cottageInfo->targetPaysDuty = $targetDutyText;
                }
                $tariff .= '</target>';
            }
            $tariff .= '</tariffs>';
            /**
             * @var $this ->currentCondition ActiveRecord
             */
            $cottageInfo->individualTariff = true;
            $cottageInfo->individualTariffRates = $tariff;
            $cottageInfo->save();
            $session = Yii::$app->session;
            $session->addFlash('success', 'Дополнительному участку подключен индивидуальный тариф');
            if ($cottageInfo->isMembership) {
                $membershipDifference = MembershipHandler::getTariffs(['start' => $cottageInfo->membershipPayFor]);
                if (!empty($membershipDifference)) {
                    foreach ($membershipDifference as $key => $item) {
                        MembershipHandler::recalculateMembership($key);
                    }
                }
            }
            if ($cottageInfo->isTarget) {
                $targets = TargetHandler::getCurrentRates();
                foreach ($targets as $key => $value) {
                    TargetHandler::recalculateTarget($key);
                }
            }
        } else {
            $tariff = '<tariffs><membership>';
            $requirements = self::getRequirements($this->cottageNumber);
            if (!empty($requirements['quarters'])) {
                foreach ($requirements['quarters'] as $key => $quarter) {
                    if (!$data = $this->membership[$key]) {
                        $this->addError('membership', 'Не заполнены ставки');
                        return false;
                    }
                    $fixed = $data['fixed'];
                    $float = $data['float'];
                    if ((empty($fixed) && $fixed !== '0') || (empty($float) && $float !== '0')) {
                        $this->addError('membership', 'Не заполнены ставки');
                        return false;
                    }
                    $fixed = CashHandler::toRubles($fixed);
                    $float = CashHandler::toRubles($float);
                    $tariff .= "<quarter date='{$key}' fixed='$fixed' float='$float'/>";
                }
            }
            $tariff .= '</membership><target>';
            if (!empty($requirements['years'])) {
                $targetDutyText = '<targets>';
                $targetDutySumm = 0;
                foreach ($requirements['years'] as $year) {
                    if (!$this->target[$year->year]) {
                        $this->addError('target', 'Не заполнены ставки');
                        return false;
                    }
                    if (!$data = $this->target[$year->year]) {
                        $this->addError('target', 'Не заполнены ставки');
                        return false;
                    }
                    $fixed = $data['fixed'];
                    $float = $data['float'];
                    $payed = $data['payed-before'];
                    if ((empty($fixed) && $fixed !== '0') || (empty($float) && $float !== '0') || (empty($payed) && $payed !== '0')) {
                        $this->addError('membership', 'Не заполнены ставки');
                        return false;
                    }
                    $fixed = CashHandler::toRubles($fixed);
                    $float = CashHandler::toRubles($float);
                    $payed = CashHandler::toRubles($payed);
                    $summ = Calculator::countFixedFloat($fixed, $float, $this->currentCondition->cottageSquare);

                    if ($summ > 0) {
                        if ($payed > $summ) {
                            $this->addError('target', 'Сумма оплаты не может быть больше суммы платежа!');
                            return false;
                        }
                        if ($payed < $summ) {
                            $targetDutySumm += $summ;
                            $targetDutyText .= "<target year='{$year->year}' payed='$payed' fixed='$fixed' float='$float' square='{$this->currentCondition->cottageSquare}' summ='$summ'/>";
                        }
                    }
                    $tariff .= "<year date='{$year->year}' fixed='$fixed' float='$float'/>";
                }
                $targetDutyText .= '</targets>';
                $this->currentCondition->targetDebt = CashHandler::rublesRound($targetDutySumm);
                $this->currentCondition->targetPaysDuty = $targetDutyText;
            }
            $tariff .= '</target></tariffs>';

            /**
             * @var $this ->currentCondition ActiveRecord
             */
            $this->currentCondition->individualTariff = true;
            $this->currentCondition->individualTariffRates = $tariff;
            $this->currentCondition->save();
            $session = Yii::$app->session;
            $session->addFlash('success', 'Подключен индивидуальный тариф');
            $membershipDifference = MembershipHandler::getTariffs(['start' => $this->currentCondition->membershipPayFor]);
            if (!empty($membershipDifference)) {
                foreach ($membershipDifference as $key => $item) {
                    MembershipHandler::recalculateMembership($key);
                }
            }
            $targets = TargetHandler::getCurrentRates();
            foreach ($targets as $key => $value) {
                TargetHandler::recalculateTarget($key);
            }
        }
        return true;
    }

    public static function countMembershipDebt($cottageInfo): array
    {
        $summ = 0;
        $unpayed = [];
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $quarters = $xpath->query('/tariffs/membership/quarter');
            if ($quarters->length > 0) {
                $nowQuarter = TimeHandler::getCurrentQuarter();
                /**
                 * @var $quarter \DOMElement
                 */
                foreach ($quarters as $quarter) {
                    $date = $quarter->getAttribute('date');
                    if ($date > $cottageInfo->membershipPayFor && $date <= $nowQuarter) {
                        $fixed = CashHandler::toRubles($quarter->getAttribute('fixed'));
                        $float = CashHandler::toRubles($quarter->getAttribute('float'));
                        $thisSumm = Calculator::countFixedFloat($fixed, $float, $cottageInfo->cottageSquare);
                        $summ += $thisSumm;
                        $unpayed[$date] = ['fixed' => $fixed, 'float' => $float, 'square' => $cottageInfo->cottageSquare];
                    }
                }
            }
            if ($cottageInfo->partialPayedMembership) {
                // получу данные о неполном платеже
                $dom = new DOMHandler($cottageInfo->partialPayedMembership);
                $info = $dom->query('/partial')->item(0);
                $summ -= CashHandler::rublesRound($info->getAttribute('summ'));
            }
            return ['summ' => $summ, 'unpayed' => $unpayed];
        }
        throw new \ErrorException('Участку не подключен персональый тариф');
    }

    public static function getQuarters($cottageNumber)
    {
        $unpayed = [];
        // проверю, подключен ли индивидуальный тариф
        $cottageInfo = Table_cottages::findOne($cottageNumber);
        if (!empty($cottageInfo) && $cottageInfo->individualTariff) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $quarters = $xpath->query('/tariffs/membership/quarter');
            if ($quarters->length > 0) {
                /**
                 * @var $quarter \DOMElement
                 */
                foreach ($quarters as $quarter) {
                    $date = $quarter->getAttribute('date');
                    if ($date > $cottageInfo->membershipPayFor) {
                        $fixed = $quarter->getAttribute('fixed');
                        $float = $quarter->getAttribute('float');
                        $unpayed[$date] = ['fixed' => $fixed, 'float' => $float, 'square' => $cottageInfo->cottageSquare];
                    }
                }
            }
            return $unpayed;
        }
        return false;
    }

    public static function getFutureQuarters($cottageNumber, $quantity, $additional = false)
    {
        if ($additional) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottageInfo = Table_cottages::findOne($cottageNumber);
        }
        if (!empty($cottageInfo) && $cottageInfo->individualTariff) {
            // проверю, заполнены ли ставки на данные кварталы
            $quarters = TimeHandler::getQuarterList(TimeHandler::getQuarterShift($quantity));
            $unfilled = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            /**
             * @var $result \DOMElement
             */
            $content = '';
            $totalCost = 0;
            foreach ($quarters as $key => $quarter) {
                $result = $xpath->query("/tariffs/membership/quarter[@date='{$key}']");
                if ($result->length === 1) {
                    if ($key > $cottageInfo->membershipPayFor) {
                        // добавлю элемент месяца
                        $fixed = $result->item(0)->getAttribute('fixed');
                        $float = $result->item(0)->getAttribute('float');
                        $cost = Calculator::countFixedFloat($fixed, $float, $cottageInfo->cottageSquare);
                        $totalCost += $cost;
                        $date = TimeHandler::getFullFromShortQuarter($key);
                        $description = "<p>Площадь расчёта- <b class='text-info'>{$cottageInfo->cottageSquare}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>{$fixed}</b> &#8381;</p><p>Оплата за сотку- <b class='text-info'>{$float}</b> &#8381;</p>";
                        $content .= "<div class='col-lg-12 text-center membership-container selected' data-summ='{$cost}'><h3>{$date} : <b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='top' data-content=\"$description\">{$cost} &#8381;</b></h3></div>";
                    } else {
                        $fullDate = TimeHandler::getFullFromShortQuarter($key);
                        $content .= "<div class='col-lg-12 text-center'><h3>{$fullDate}: квартал уже оплачен</h3></div>";
                    }
                } else {
                    $unfilled[$key] = true;
                }
            }
            if (!empty($unfilled)) {
                return ['status' => 2, 'unfilled' => $unfilled];
            }
            return ['status' => 1, 'content' => $content, 'totalCost' => CashHandler::rublesRound($totalCost)];
        }
        return false;
    }

    public function saveTariffs(): bool
    {
        if ($this->additional) {
            $this->currentCondition = AdditionalCottage::getCottage($this->cottageNumber);
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($this->currentCondition->individualTariffRates);
        $xpath = DOMHandler::getXpath($dom);
        $parent = $dom->documentElement->getElementsByTagName('membership')->item(0);
        if (!empty($this->membership)) {
            foreach ($this->membership as $key => $value) {
                // проверю, не существует ли ещё данного тарифа
                if ($xpath->query("//quarter[@date='{$key}']")->length === 1) {
                    throw new InvalidArgumentException('Тариф на данный квартал уже заполнен');
                }
                $quarter = $dom->createElement('quarter');
                $quarter->setAttribute('date', $key);
                $quarter->setAttribute('fixed', CashHandler::toRubles($value['fixed']));
                $quarter->setAttribute('float', CashHandler::toRubles($value['float']));
                $parent->appendChild($quarter);
            }
        }
        if (!empty($this->target)) {
            $parent = $dom->documentElement->getElementsByTagName('target')->item(0);
            // получу данные о текущем состоянии оплаты целевых платежей
            $targetDom = new \DOMDocument('1.0', 'UTF-8');
            $targetDom->loadXML($this->currentCondition->targetPaysDuty);
            $targetXpath = new \DOMXpath($targetDom);
            foreach ($this->target as $key => $value) {
                $elem = $dom->createElement('year');
                $elem->setAttribute('date', $key);
                $elem->setAttribute('fixed', $value['fixed']);
                $elem->setAttribute('float', $value['float']);
                $parent->appendChild($elem);
            }
            // Изменю данные о долгах
            $currentDebts = $targetXpath->query('/targets/target');
            $targetDebtSumm = 0;
            if ($currentDebts->length > 0) {
                foreach ($currentDebts as $debt) {
                    /** @var \DOMElement $debt */
                    $year = $debt->getAttribute('year');
                    if (!empty($this->target[$year])) {
                        $fixed = CashHandler::toRubles($this->target[$year]['fixed']);
                        $float = CashHandler::toRubles($this->target[$year]['float']);
                        $payed = CashHandler::toRubles($this->target[$year]['payed-before']);
                        $summ = Calculator::countFixedFloat($fixed, $float, $this->currentCondition->cottageSquare);
                        $targetDebtSumm += $summ - $payed;
                        // пересчитаю тариф
                        $debt->setAttribute('fixed', $fixed);
                        $debt->setAttribute('float', $float);
                        $debt->setAttribute('payed', $payed);
                        $debt->setAttribute('summ', $summ);
                        unset ($this->target[$year]);
                    } else {
                        $summ = CashHandler::toRubles($debt->getAttribute('summ'));
                        $payed = CashHandler::toRubles($debt->getAttribute('payed'));
                        $targetDebtSumm += $summ - $payed;
                    }
                }
            }
            foreach ($this->target as $key => $value) {
                $fixed = CashHandler::toRubles($value['fixed']);
                $float = CashHandler::toRubles($value['float']);
                $payed = CashHandler::toRubles($value['payed-before']);
                $summ = Calculator::countFixedFloat($fixed, $float, $this->currentCondition->cottageSquare);
                if ($payed < $summ) {
                    $elem = $targetDom->createElement('target');
                    $elem->setAttribute('year', $key);
                    $elem->setAttribute('fixed', $fixed);
                    $elem->setAttribute('float', $float);
                    $elem->setAttribute('payed', $payed);
                    $elem->setAttribute('square', $this->currentCondition->cottageSquare);
                    $elem->setAttribute('summ', $summ);
                    $targetDom->documentElement->appendChild($elem);
                    $targetDebtSumm += $summ - $payed;
                }
            }
            $this->currentCondition->targetDebt = CashHandler::rublesRound($targetDebtSumm);
            $data = html_entity_decode($targetDom->saveXML($targetDom->documentElement));
            $this->currentCondition->targetPaysDuty = $data;
        }
        $data = html_entity_decode($dom->saveXML($dom->documentElement));
        $this->currentCondition->individualTariffRates = $data;
        $this->currentCondition->save();
        return true;
    }

    public function saveChanges(): bool
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($this->currentCondition->individualTariffRates);
        $xpath = new \DOMXpath($dom);
        if ($this->membership !== null) {
            foreach ($this->membership as $key => $value) {
                // изменю данные тарифа
                $quarter = $xpath->query("/tariffs/membership/quarter[@date='{$key}']");
                if ($quarter->length === 1) {
                    $fixed = CashHandler::toRubles($value['fixed']);
                    $float = CashHandler::toRubles($value['float']);
                    $quarter->item(0)->setAttribute('fixed', $fixed);
                    $quarter->item(0)->setAttribute('float', $float);
                }
            }
        }
        if (!empty($this->target)) {
            // получу данные о текущем состоянии оплаты целевых платежей
            $targetDom = new \DOMDocument('1.0', 'UTF-8');
            $targetDom->loadXML($this->currentCondition->targetPaysDuty);
            $targetXpath = new \DOMXpath($targetDom);
            foreach ($this->target as $key => $value) {
                // изменю данные тарифа
                $year = $xpath->query("/tariffs/target/year[@date='{$key}']");
                if ($year->length === 1) {
                    $year->item(0)->setAttribute('fixed', CashHandler::toRubles($value['fixed']));
                    $year->item(0)->setAttribute('float', CashHandler::toRubles($value['float']));
                }
            }
            // Изменю данные о долгах
            $currentDebts = $targetXpath->query('/targets/target');
            $targetDebtSumm = 0;
            if ($currentDebts->length > 0) {
                foreach ($currentDebts as $debt) {
                    /** @var \DOMElement $debt */
                    $year = $debt->getAttribute('year');
                    if (!empty($this->target[$year])) {
                        $fixed = CashHandler::toRubles($this->target[$year]['fixed']);
                        $float = CashHandler::toRubles($this->target[$year]['float']);
                        $payed = CashHandler::toRubles($this->target[$year]['payed-before']);
                        $summ = Calculator::countFixedFloat($fixed, $float, $this->currentCondition->cottageSquare);
                        $targetDebtSumm += $summ - $payed;
                        // пересчитаю тариф
                        $debt->setAttribute('fixed', $fixed);
                        $debt->setAttribute('float', $float);
                        $debt->setAttribute('payed', $payed);
                        $debt->setAttribute('summ', $summ);
                        unset ($this->target[$year]);
                    } else {
                        $summ = CashHandler::toRubles($debt->getAttribute('summ'));
                        $payed = CashHandler::toRubles($debt->getAttribute('payed'));
                        $targetDebtSumm += $summ - $payed;
                    }
                }
            }
            foreach ($this->target as $key => $value) {
                $fixed = CashHandler::toRubles($value['fixed']);
                $float = CashHandler::toRubles($value['float']);
                $payed = CashHandler::toRubles($value['payed-before']);
                $summ = Calculator::countFixedFloat($fixed, $float, $this->currentCondition->cottageSquare);
                if ($payed < $summ) {
                    $elem = $targetDom->createElement('target');
                    $elem->setAttribute('year', $key);
                    $elem->setAttribute('fixed', $fixed);
                    $elem->setAttribute('float', $float);
                    $elem->setAttribute('payed', $payed);
                    $elem->setAttribute('square', $this->currentCondition->cottageSquare);
                    $elem->setAttribute('summ', $summ);
                    $targetDom->documentElement->appendChild($elem);
                    $targetDebtSumm += $summ - $payed;
                }
            }
            $this->currentCondition->targetDebt = CashHandler::rublesRound($targetDebtSumm);
            $data = html_entity_decode($targetDom->saveXML($targetDom->documentElement));
            $this->currentCondition->targetPaysDuty = $data;
        }
        $data = html_entity_decode($dom->saveXML($dom->documentElement));
        $this->currentCondition->individualTariffRates = $data;
        $this->currentCondition->save();
        $session = Yii::$app->session;
        $session->addFlash('success', 'Данные тарифа успешно изменены!');
        return true;
    }

    public function disableTariff(): bool
    {
        $this->fillTargets();
        $targetDebt = 0;
        $content = '<targets>';
        if (!empty($this->target)) {
            foreach ($this->target as $key => $value) {
                $full = Calculator::countFixedFloat($this->existentTargets[$key]['fixed'], $this->existentTargets[$key]['float'], $this->currentCondition->cottageSquare);
                // сравню тариф и сведения о нём
                if ($value['payed-of'] === 'no-payed') {
                    // Добавлю данный взнос как полностью неоплаченный
                    $content .= "<target payed='0' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->currentCondition->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                    $targetDebt += $full;
                } elseif ($value['payed-of'] === 'partial') {
                    $unpayed = CashHandler::rublesMath($full - $value['payed-summ']);
                    $content .= "<target payed='{$value['payed-summ']}' year='{$key}' float='{$this->existentTargets[$key]['float']}' fixed='{$this->existentTargets[$key]['fixed']}' square='{$this->currentCondition->cottageSquare}' summ='{$full}' description='{$this->existentTargets[$key]['description']}'/>";
                    $targetDebt += $unpayed;
                }
            }
        }
        $content .= '</targets>';
        $this->currentCondition->targetDebt = $targetDebt;
        $this->currentCondition->targetPaysDuty = $content;
        $this->currentCondition->individualTariff = 0;
        $this->currentCondition->individualTariffRates = '';
        $this->currentCondition->save();
        $session = Yii::$app->session;
        $session->addFlash('success', 'Индивидуальный тариф отменён!');
        return true;
    }

    public function fillTargets()
    {
        $this->existentTargets = Table_tariffs_target::find()->orderBy('year')->all();
        $targetsList = [];
        foreach ($this->existentTargets as $value) {
            $targetsList[$value->year] = ['fixed' => $value->fixed_part, 'float' => $value->float_part, 'description' => $value->description];
        }
        $this->existentTargets = $targetsList;
    }

    /**
     * @param $cottageNumber int
     * @return bool|string
     * @var $this ->currentCondition ActiveRecord
     */
    public static function showRates($cottageNumber, $additional = false)
    {
        // проверю, подключен ли индивидуальный тариф
        if ($additional) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        }
        if (!empty($cottageInfo) && $cottageInfo->individualTariff) {
            $tariffText = '';
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $quarters = $xpath->query('/tariffs/membership/quarter');
            if ($quarters->length > 0) {
                $tariffText .= '<h3>Членские взносы</h3>';
                /**
                 * @var $quarter \DOMElement
                 */
                foreach ($quarters as $quarter) {
                    $date = $quarter->getAttribute('date');
                    $fixed = CashHandler::toRubles($quarter->getAttribute('fixed'));
                    $float = CashHandler::toRubles($quarter->getAttribute('float'));
                    $tariffText .= "<p>$date: С участка: $fixed  &#8381;, с сотки: $float  &#8381;</p>";
                }
            }
            $years = $xpath->query('/tariffs/target/year');
            if ($years->length > 0) {
                $tariffText .= '<h3>Целевые взносы</h3>';
                /**
                 * @var $year \DOMElement
                 */
                foreach ($years as $year) {
                    $date = $year->getAttribute('date');
                    $fixed = CashHandler::toRubles($year->getAttribute('fixed'));
                    $float = CashHandler::toRubles($year->getAttribute('float'));
                    $tariffText .= "<p>$date: С участка: $fixed  &#8381;, с сотки: $float  &#8381;</p>";
                }
            }
            return $tariffText;
        }
        return false;
    }

    /**
     * @param $cottageInfo Table_cottages
     * @return array
     */
    public static function getTargetRates($cottageInfo): array
    {
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff) {
            $data = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $years = $xpath->query('/tariffs/target/year');
            if ($years->length > 0) {
                /**
                 * @var $year \DOMElement
                 */
                foreach ($years as $year) {
                    $date = $year->getAttribute('date');
                    $fixed = CashHandler::toRubles($year->getAttribute('fixed'));
                    $float = CashHandler::toRubles($year->getAttribute('float'));
                    $summ = Calculator::countFixedFloatPlus($fixed, $float, $cottageInfo->cottageSquare);
                    $realSumm = $summ['total'];
                    $data[$date] = ['fixed' => $fixed, 'float' => $float, 'summ' => $summ, 'realSumm' => $realSumm, 'description' => 'Индивидуальный тариф'];
                }
            }
            return $data;
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }

    public static function disable($cottageNumber, $additional = false)
    {
        if ($additional) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            if ($cottageInfo->hasDifferentOwner) {
                // проверю наличие неоплаченных платежей, если они есть- действие невозможно
                if (PayDouble::getUnpayedBillId($cottageNumber)) {
                    return ['status' => 2];
                }
            } else {
                // проверю наличие неоплаченных платежей, если они есть- действие невозможно
                if (Pay::getUnpayedBillId($cottageNumber)) {
                    return ['status' => 2];
                }
            }
            // если по участку оплачиваются целевые платежи- верну список платежей, если нет- просто отключу тариф
            if (!empty($cottageInfo) && $cottageInfo->individualTariff && !$cottageInfo->isTarget) {
                $cottageInfo->individualTariff = 0;
                $cottageInfo->individualTariffRates = '';
                $cottageInfo->save();
                return ['status' => 1];
            }
        } else {
            // проверю наличие неоплаченных платежей, если они есть- действие невозможно
            if (Pay::getUnpayedBillId($cottageNumber)) {
                return ['status' => 2];
            }
            $cottageInfo = Table_cottages::findOne($cottageNumber);
        }
        if (!empty($cottageInfo) && $cottageInfo->individualTariff) {
            $tariffs = [];
            // получу ставки по целевым взносам, чтобы перевести участок на обычный тариф
            $rates = Table_tariffs_target::find()->all();
            if ($rates) {
                foreach ($rates as $rate) {
                    $float = $rate->float_part;
                    $fixed = $rate->fixed_part;
                    $summ = Calculator::countFixedFloat($fixed, $float, $cottageInfo->cottageSquare);
                    $tariffs[$rate->year] = ['fixed' => $fixed, 'float' => $float, 'square' => $cottageInfo->cottageSquare, 'summ' => $summ];
                }
            }
            return $tariffs;
        }
        return false;
    }

    public static function getLastMembershipRate($cottageInfo)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($cottageInfo->individualTariffRates);
        $xpath = new \DOMXpath($dom);
        $quarters = $xpath->query('/tariffs/membership/quarter');
        if ($quarters->length > 0) {
            // верну номер последнего найденного квартала
            /** @var \DOMElement $lastQuarter */
            $lastQuarter = $quarters->item($quarters->length - 1);
            return $lastQuarter->getAttribute('date');
        }
        return null;
    }

    public static function getMembershipRates($cottageInfo, $membershipPeriods = false): array
    {
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff) {
            $data = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $quarters = $xpath->query('/tariffs/membership/quarter');
            if ($quarters->length > 0) {
                $start = TimeHandler::getQuarterTimestamp($cottageInfo->membershipPayFor);
                /**
                 * @var $quarter \DOMElement
                 */
                foreach ($quarters as $quarter) {
                    $rawDate = $quarter->getAttribute('date');
                    $date = TimeHandler::getQuarterTimestamp($rawDate);
                    if ($date > $start) {
                        if ($membershipPeriods != false) {
                            if ($membershipPeriods > 0) {
                                $fixed = CashHandler::toRubles($quarter->getAttribute('fixed'));
                                $float = CashHandler::toRubles($quarter->getAttribute('float'));
                                $data[$rawDate] = ['fixed' => $fixed, 'float' => $float];
                                --$membershipPeriods;
                                if ($membershipPeriods === 0) {
                                    break;
                                }
                            }
                        } else {
                            $fixed = CashHandler::toRubles($quarter->getAttribute('fixed'));
                            $float = CashHandler::toRubles($quarter->getAttribute('float'));
                            $data[$rawDate] = ['fixed' => $fixed, 'float' => $float];
                        }
                    }
                }
            }
            return $data;
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }

    public static function getMembershipTariffs($cottageInfo): array
    {
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff) {
            $data = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            try{
                $dom->loadXML($cottageInfo->individualTariffRates);
            }
            catch (\Exception $e){
                echo $cottageInfo->cottageNumber;
                die;
            }
            $xpath = new \DOMXpath($dom);
            $quarters = $xpath->query('/tariffs/membership/quarter');
            if ($quarters->length > 0) {
                /**
                 * @var $quarter \DOMElement
                 */
                foreach ($quarters as $quarter) {
                    $rawDate = $quarter->getAttribute('date');
                    $fixed = CashHandler::toRubles($quarter->getAttribute('fixed'));
                    $float = CashHandler::toRubles($quarter->getAttribute('float'));
                    $data[$rawDate] = ['fixed' => $fixed, 'float' => $float];
                }
            }
            return $data;
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }
    public static function getTargetTariffs($cottageInfo): array
    {
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff) {
            $data = [];
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $years = $xpath->query('/tariffs/target/year');
            if ($years->length > 0) {
                /**
                 * @var $year \DOMElement
                 */
                foreach ($years as $year) {
                    $rawDate = $year->getAttribute('date');
                    $fixed = CashHandler::toRubles($year->getAttribute('fixed'));
                    $float = CashHandler::toRubles($year->getAttribute('float'));
                    $data[$rawDate] = ['fixed' => $fixed, 'float' => $float];
                }
            }
            return $data;
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }

    /**
     * @param $cottageInfo
     * @param bool $targetQuarter
     * @return array|bool
     */
    public static function getMembershipRate($cottageInfo, $targetQuarter = false)
    {
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff === 1) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            if (!empty($targetQuarter)) {
                $q = TimeHandler::isQuarter($targetQuarter);
                $quarter = $xpath->query("/tariffs/membership/quarter[@date='{$q['full']}']");
            } else {
                $quarter = $xpath->query('/tariffs/membership/quarter[last()]');
            }
            if ($quarter->length === 1) {
                $q = $quarter->item(0)->getAttribute('date');
                $fixed = CashHandler::toRubles($quarter->item(0)->getAttribute('fixed'));
                $float = CashHandler::toRubles($quarter->item(0)->getAttribute('float'));
                return ['quarter' => $q, 'fixed' => $fixed, 'float' => $float];
            }
            return false;
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }

    public static function getTargetRate($cottageInfo, $targetYear): array
    {
        $targetYear = TimeHandler::isYear($targetYear);
        // проверю, подключен ли индивидуальный тариф
        if ($cottageInfo->individualTariff === 1) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($cottageInfo->individualTariffRates);
            $xpath = new \DOMXpath($dom);
            $year = $xpath->query("/tariffs/target/year[@date='{$targetYear}']");
            if ($year->length === 1) {
                $fixed = CashHandler::toRubles($year->item(0)->getAttribute('fixed'));
                $float = CashHandler::toRubles($year->item(0)->getAttribute('float'));
                return ['fixed' => $fixed, 'float' => $float];
            }
            return ['fixed' => 0, 'float' => 0];
        }
        throw new InvalidArgumentException('У данного участка не активирован персональный тариф.');
    }
}