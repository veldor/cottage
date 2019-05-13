<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 22:57
 */

namespace app\models;

use DOMElement;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;

/**
 * Class MembershipHandler
 * @package app\models
 */
class MembershipHandler extends Model {

	public $membership;


	const SCENARIO_NEW_RECORD = 'new_record';


    public function scenarios(): array
	{
		return [
			self::SCENARIO_NEW_RECORD => ['membership'],
		];
	}

	public static function getCottageStatus($cottageInfo)
	{
		// верну общую сумму неоплаченных членских взносов
		$summ = 0;
		// Сделаю выборку тарифов
		$start = TimeHandler::getQuarterTimestamp($cottageInfo->membershipPayFor);
		$now = TimeHandler::getQuarterTimestamp(TimeHandler::getCurrentQuarter());
		if ($start === $now) {
			return 0;
		}
		$tariffs = Table_tariffs_membership::find()
			->where(['and', "search_timestamp>$start", "search_timestamp<=$now"])
			->all();
		if (!empty($tariffs)) {
			foreach ($tariffs as $item) {
				$summ += $item->fixed_part;
				$summ += $cottageInfo->cottageSquare * ($item->changed_part / 100);
			}
			// вычту сумму частично оплаченного квартала, если она есть
            if($cottageInfo->partialPayedMembership){
                // получу данные о неполном платеже
                $dom = new DOMHandler($cottageInfo->partialPayedMembership);
                /** @var DOMElement $info */
                $info = $dom->query('/partial')->item(0);
                $summ -= CashHandler::rublesRound($info->getAttribute('summ'));
            }
			return CashHandler::rublesRound($summ);
		}
		return false;
	}

	/**
	 * @param $cottage int|string|Table_additional_cottages|Table_cottages
	 * @param $additional boolean
	 * @return array
	 */
	public static function getDebt($cottage, $additional = false): array
	{
		if (!is_object($cottage)) {
			if ($additional) {
				$cottage = AdditionalCottage::getCottage($cottage);
			}
			else {
				$cottage = Cottage::getCottageInfo($cottage);
			}
		}
		if ($additional && !$cottage->isMembership) {
			return [];
		}

		// получу данные о частично оплаченных кварталах
        $partialPayed = self::checkPartialPayedQuarter($cottage);

		$start = TimeHandler::getQuarterTimestamp($cottage->membershipPayFor);
		$now = TimeHandler::getQuarterTimestamp(TimeHandler::getCurrentQuarter());
		if ($start === $now) {
			return [];
		}
		if ($cottage->individualTariff) {
			$tariffs = PersonalTariff::getMembershipRates($cottage);
			$answer = [];
			foreach ($tariffs as $key =>$tariff) {
			    // проверю, не является ли квартал частично оплаченным

                if(!empty($partialPayed) && $partialPayed['date'] === $key){
                    $prepayed = $partialPayed['summ'];
                }
                else{
                    $prepayed = 0;
                }
                $summ = Calculator::countFixedFloatPlus($tariff['fixed'], $tariff['float'], $cottage->cottageSquare);
                $totalSumm = CashHandler::toRubles($summ['total']) - CashHandler::toRubles($prepayed);
				$answer[$key] = ['fixed' => $tariff['fixed'], 'float' => $tariff['float'], 'float_summ' => $summ['float'], 'total_summ' => $totalSumm, 'prepayed' => $prepayed];
			}
		}
		else {
			$tariffs = Table_tariffs_membership::find()
				->where(['and', "search_timestamp>$start", "search_timestamp<=$now"])
				->all();
			$answer = [];
			foreach ($tariffs as $tariff) {
                if(!empty($partialPayed) && $partialPayed['date'] === $tariff->quarter){
                    $prepayed = $partialPayed['summ'];
                }
                else{
                    $prepayed = 0;
                }
				$summ = Calculator::countFixedFloatPlus($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                $totalSumm = CashHandler::toRubles($summ['total']) - CashHandler::toRubles($prepayed);
				$answer[$tariff->quarter] = ['fixed' => $tariff->fixed_part, 'float' => $tariff->changed_part, 'float_summ' => $summ['float'], 'total_summ' => $totalSumm, 'prepayed' => $prepayed];
			}
		}
		return $answer;
	}

	/**
	 * @param $quartersNumber int|string
	 * @param $cottageNumber int|string
	 * @param $additional boolean
	 * @return array
	 */
	public static function getFutureQuarters($quartersNumber, $cottageNumber, $additional): array
	{
		if ($additional) {
			$cottage = AdditionalCottage::getCottage($cottageNumber);
            $info = PersonalTariff::getFutureQuarters($cottageNumber, $quartersNumber, true);
		}
		else {
			$cottage = Cottage::getCottageInfo($cottageNumber);
            $info = PersonalTariff::getFutureQuarters($cottageNumber, $quartersNumber);
		}
		if ($cottage->individualTariff) {
			if ($info['status'] === 1) {
				return $info;
			}
			return ['status' => 3, 'lastQuarterForFilling' => TimeHandler::getQuarterShift($quartersNumber)]; // если не заполнены тарифы- верну последний квартал, который должен быть заполнен
		}
		// получу список тарифов на данный период
		try {
			$tariffs = self::getTariffs(['start' => TimeHandler::getCurrentQuarter(), 'finish' => TimeHandler::getQuarterShift($quartersNumber, TimeHandler::getCurrentQuarter())]);
		} catch (InvalidValueException $e) {
			return ['status' => 2, 'lastQuarterForFilling' => TimeHandler::getQuarterShift($quartersNumber)];
		}
		$totalCost = 0;
		$content = '';
		foreach ($tariffs as $key => $value) {
			$summ = Calculator::countFixedFloatPlus($value['fixed'], $value['float'], $cottage->cottageSquare);
			$date = TimeHandler::getFullFromShortQuarter($key);
			if ($key > $cottage->membershipPayFor) {
				$description = "<p>Площадь расчёта- <b class='text-info'>{$cottage->cottageSquare}</b> М<sup>2</sup></p><p>Оплата за участок- <b class='text-info'>{$value['fixed']}</b> &#8381;</p><p>Оплата за сотку- <b class='text-info'>{$value['float']}</b> &#8381;</p><p>Начислено за сотки- <b class='text-info'>{$summ['float']}</b> &#8381;</p>";
				$content .= "<div class='col-lg-12 text-center membership-container hoverable additional' data-summ='{$summ['total']}'><table class='table table-condensed'><tbody><tr><td>{$date}</td><td><b class='text-danger popovered' data-toggle='popover' title='Детали платежа' data-placement='left' data-content=\"$description\">{$summ['total']} &#8381;</b></td></tr></tbody></table></div>";
				$totalCost += $summ['total'];
			}
			else {
				$content .= "<div class='col-lg-12 text-center'>{$date}: Квартал уже оплачен</div>";
			}

		}
		return ['status' => 1, 'content' => $content, 'totalSumm' => $totalCost];
	}

	/**
	 * @param $period string|array[start, finish]
	 * @param bool $skipCheckWholeness
	 * @return array
	 */
	public static function getTariffs($period, $skipCheckWholeness = false): array
	{
		$query = Table_tariffs_membership::find();
		if (is_string($period)) {
			$quarter = TimeHandler::isQuarter($period);
			$query->where(['quarter' => $quarter['full']]);
			$length = 1;
		}
		elseif (is_array($period)) {
			$start = TimeHandler::isQuarter($period['start']);
			if (!empty($period['finish'])) {
				$finish = TimeHandler::isQuarter($period['finish']);
				if($period['start'] === $period['finish']){
					return [];
				}
				if ($start['full'] < $finish['full']) {
					$query->where(['>', 'quarter', $start['full']]);
					$query->andWhere(['<=', 'quarter', $finish['full']]);
				}
				else {
					$query->where(['>', 'quarter', $finish['full']]);
					$query->andWhere(['<=', 'quarter', $start['full']]);
				}
				$length = abs(TimeHandler::checkQuarterDifference($start['full'], $finish['full']));
			}
			else {
				$query->where(['>', 'quarter', $start['full']]);
			}
		}
		$result = $query->all();
		$answer = [];
		foreach ($result as $item) {
			$answer[$item->quarter] = ['fixed' => $item->fixed_part, 'float' => $item->changed_part];
		}
		if ($skipCheckWholeness) {
			return $answer;
		}
		if (!empty($result)) {
			// проверю целостность тарифов
			if (empty($length)) {
				$start = key($answer);
				end($answer);
				$finish = key($answer);
				$length = abs(TimeHandler::checkQuarterDifference($start, $finish)) + 1;
			}
			if (count($answer) === $length) {
				return $answer;
			}
		}
		throw new InvalidValueException('Не заполнены тарифы!');
	}

	public static function createPayment($cottageInfo, $membershipPeriods, $additional = false): array
	{
        $partialPayed = self::checkPartialPayedQuarter($cottageInfo);
		$answer = '';
		$summ = 0;
		if ($cottageInfo->individualTariff) {
			$tariffs = PersonalTariff::getMembershipRates($cottageInfo, $membershipPeriods);
		}
		else {
			// получу ставки
			$tariffs = self::getTariffs(['start' => $cottageInfo->membershipPayFor, 'finish' => TimeHandler::getQuarterShift($membershipPeriods, $cottageInfo->membershipPayFor)]);
		}
		foreach ($tariffs as $key => $tariff) {

            if(!empty($partialPayed) && $partialPayed['date'] === $key){
                $prepayed = CashHandler::toRubles($partialPayed['summ']);
            }
            else{
                $prepayed = 0;
            }

			$cost = Calculator::countFixedFloatPlus($tariff['fixed'], $tariff['float'], $cottageInfo->cottageSquare);
            $totalSumm = CashHandler::toRubles($cost['total']) - $prepayed;
			$summ += $totalSumm;
			$answer .= "<quarter date='{$key}' summ='{$totalSumm}' square='{$cottageInfo->cottageSquare}' float-cost='{$cost['float']}' float='{$tariff['float']}' fixed='{$tariff['fixed']}' prepayed='$prepayed'/>";
		}
		if ($additional) {
			$answer = /** @lang xml */
				"<additional_membership cost='{$summ}'>" . $answer . '</additional_membership>';
		}
		else {
			$answer = /** @lang xml */
				"<membership cost='{$summ}'>" . $answer . '</membership>';
		}
		return ['text' => $answer, 'summ' => CashHandler::rublesRound($summ)];
	}

	public static function registerPayment($cottageInfo, $billInfo, $payments, $additional = false)
	{
		// зарегистрирую платежи
		foreach ($payments['values'] as $payment) {
			self::insertPayment($payment, $cottageInfo, $billInfo, $additional);
		}
		$cottageInfo->membershipPayFor = end($payments['values'])['date'];
	}

	public static function insertPayment($payment, $cottageInfo, $billInfo, $additional = false)
	{
        $partialPayed = self::checkPartialPayedQuarter($cottageInfo);
	    $summ = CashHandler::toRubles($payment['summ']);
		if ($summ > 0) {
            if(!empty($partialPayed) && $partialPayed['date'] === $payment['date']){
                $cottageInfo->partialPayedMembership = null;
            }
			if ($additional) {
				$write = new Table_additional_payed_membership();
				$write->cottageId = $cottageInfo->masterId;
			}
			else {
				$write = new Table_payed_membership();
				$write->cottageId = $cottageInfo->cottageNumber;
			}
			$write->billId = $billInfo->id;
			$write->quarter = $payment['date'];
			$write->summ = $summ;
			$write->paymentDate = $billInfo->paymentTime;
			$write->save();
			self::recalculateMembership($payment['date']);
		}
	}
	public static function insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime)
	{
			if (Cottage::isMain($cottageInfo)) {
                $write = new Table_payed_membership();
                $write->cottageId = $cottageInfo->cottageNumber;
			}
			else {
                $write = new Table_additional_payed_membership();
                $write->cottageId = $cottageInfo->masterId;
			}
			$write->billId = $billId;
			$write->quarter = $date;
			$write->summ = $summ;
			$write->paymentDate = $paymentTime;
			$write->save();
			self::recalculateMembership($date);
	}

	public static function recalculateMembership($period)
	{
		$quarter = TimeHandler::isQuarter($period);
        try{
            $tariff = self::getRowTariff($quarter['full']); // получу тарифные ставки за данный месяц

            $payedNow = 0; // оплачено внутри программы
            $payedCounter = 0; // счётчик оплаченных участков
            $additionalPayedCounter = 0; // счётчик дополнительных оплаченных участков

            // получу оплаченные счета за этот месяц
            $payed = Table_payed_membership::find()->where(['quarter' => $quarter['full']])->all();
            $additionalPayed = Table_additional_payed_membership::find()->where(['quarter' => $quarter['full']])->all();
            // составлю массив данных о фактической оплате (через программу)
            $insidePayed = [];

            if (!empty($payed)) {
                foreach ($payed as $item) {
                    $payedNow += $item->summ;
                    $payedCounter++;
                    $insidePayed[$item->cottageId] = true;
                }
            }
            if (!empty($additionalPayed)) {
                foreach ($additionalPayed as $item) {
                    $payedNow += $item->summ;
                    $additionalPayedCounter++;
                }
            }
            $cottages = Cottage::getRegistred();
            $additionalCottages = AdditionalCottage::getRegistred();

            $cottagesCount = count($cottages);
            $additionalCottagesCount = count($additionalCottages);

            $fullSquare = 0;
            $neededSumm = 0;
            $payedOutsideCounter = 0;
            $payedOutside = 0;

            foreach ($cottages as $cottage) {
                $summ = 0;
                if ($cottage->individualTariff) {
                    // получу тариф за данный месяц
                    $rates = PersonalTariff::getMembershipRate($cottage, $quarter['full']);
                    if(!empty($rates)){
                        $summ = Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                        $neededSumm += $summ;
                    }
                }
                else {
                    $summ = Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                    $neededSumm += $summ;
                }
                // дополнительный блок- буду считать оплату в теории- если у участка нет долгов- считаю, что он заплатил раньше по стандартному тарифу
                $fullSquare += $cottage->cottageSquare;
                if($cottage->membershipPayFor >= $quarter['full'] && empty($insidePayed[$cottage->cottageNumber])){
                    // если квартал считается оплаченным, но при этом не оплачен в программе - считаю его оплаченным вне программы по стандартному тарифу
                    $payedOutsideCounter ++;
                    $payedOutside += $summ;

                }
            }
            foreach ($additionalCottages as $cottage) {
                if ($cottage->isMembership) {
                    if ($cottage->individualTariff) {
                        if($cottage->isMembership){
                            $rates = PersonalTariff::getMembershipRate($cottage, $quarter['full']);
                            if(!empty($rates)) {
                                $neededSumm += Calculator::countFixedFloat($rates['fixed'], $rates['float'], $cottage->cottageSquare);
                            }
                        }
                    }
                    else {
                        $neededSumm += Calculator::countFixedFloat($tariff->fixed_part, $tariff->changed_part, $cottage->cottageSquare);
                    }
                }
                $fullSquare += $cottage->cottageSquare;
            }
            $untrustedPayed = CashHandler::rublesMath($payedNow + $payedOutside);
            $payUntrusted = $payedCounter + $payedOutsideCounter;
            $tariff->fullSumm = $neededSumm;
            $tariff->payedSumm = $payedNow;
            $tariff->paymentInfo = /** @lang xml */
                "<info><cottages_count>$cottagesCount</cottages_count><additional_cottages_count>$additionalCottagesCount</additional_cottages_count><pay>$payedCounter</pay><pay_outside>$payedOutsideCounter</pay_outside><pay_untrusted>$payUntrusted</pay_untrusted><pay_additional>$additionalPayedCounter</pay_additional><full_square>$fullSquare</full_square><payed_outside>$payedOutside</payed_outside><payed_untrusted>$untrustedPayed</payed_untrusted></info>";
            /** @var Table_tariffs_power $tariff */
            $tariff->save();
        }
        catch (Exception $e){
            // предполагается, что тут я буду считать данные, если основной тариф не заполнен
        }
	}

    /**
     * @param bool $period
     * @param bool $skipIntegrityErrors
     * @return Table_tariffs_membership
     */
	public static function getRowTariff($period = false, $skipIntegrityErrors = false): Table_tariffs_membership
	{
		if(!empty($period)){
			$quarter = TimeHandler::isQuarter($period);
			$data = Table_tariffs_membership::findOne(['quarter' => $quarter['full']]);
			if (!empty($data)) {
				return $data;
			}
			throw new InvalidArgumentException('Тарифа на данный квартал не существует!');
		}
		$data = Table_tariffs_membership::find()->orderBy('search_timestamp DESC')->one();
		if(!empty($data)){
			return $data;
		}
		throw new InvalidValueException('Тарифы  не обнаружены');
	}

	public static function validateFillTariff($from)
	{
		$thisQuarter = TimeHandler::getCurrentQuarter();
		$start = TimeHandler::isQuarter($from)['full'];
		if ($thisQuarter === $start) {
			return ['status' => 2];
		}
		try {
			$tariffs = self::getTariffs(['start' => $start, 'finish' => $thisQuarter]);
			return ['status' => 2, 'tariffs' => $tariffs];
		} catch (Exception $e) {
			return ['status' => 1];
		}
	}

	public static function getUnfilled($period): array
	{
		$quarters = TimeHandler::getQuarterList($period);
		$tariffs = self::getTariffs(['start' => $period, 'finish' => TimeHandler::getCurrentQuarter()], true);
		foreach ($quarters as $key => $value) {
			if (!empty($tariffs[$key])) {
				unset($quarters[$key]);
			}
		}
		if (!empty($tariffs)) {
			end($tariffs);
			$lastTariffData = ['fixed' => end($tariffs)['fixed'], 'float' => end($tariffs)['float']];
		}
		else {
			$data = Table_tariffs_membership::find()->orderBy('search_timestamp DESC')->one();
			if (!empty($data)) {
				$lastTariffData = ['fixed' => $data->fixed_part, 'float' => $data->changed_part];
			}
			else {
				$lastTariffData = ['fixed' => 0, 'float' => 0];
			}
		}
		return ['quarters' => $quarters, 'lastTariffData' => $lastTariffData];
	}

	public function save(): bool
	{
		foreach ($this->membership as $key => $value) {
			self::createTariff($key, $value['fixed'], $value['float']);
		}
		return true;
	}


	private static function createTariff($quarter, $fixed, $float)
	{
		$quarter = TimeHandler::isQuarter($quarter);
		if (Table_tariffs_membership::find()->where(['quarter' => $quarter['full']])->count()) {
			throw new InvalidArgumentException('Тариф на ' . $quarter['full'] . 'уже заполнен!');
		}
		$tariff = new Table_tariffs_membership();
		$tariff->quarter = $quarter['full'];
		$tariff->fixed_part = $fixed;
		$tariff->changed_part = $float;
		$tariff->search_timestamp = TimeHandler::getQuarterTimestamp($quarter['full']);
		$tariff->fullSumm = 0;
		$tariff->payedSumm = 0;
		$tariff->paymentInfo = '<info><cottages_count>0</cottages_count><additional_cottages_count>0</additional_cottages_count><pay>0</pay><pay_additional>0</pay_additional><full_square>0</full_square></info>';
		try{
			$tariff->save();
		}
		catch (Exception $e){
			var_dump($e);
			die ('Ошибка при работе с базой данных!');
		}
	}

    /**
     * @param $billDom DOMHandler
     * @param $paymentSumm
     * @param $cottageInfo
     * @param $billId
     * @param $paymentTime
     */
    public static function handlePartialPayment($billDom, $paymentSumm, $cottageInfo, $billId, $paymentTime){
        // проверка, оплачивается основной или дополнительный участок
        $main = Cottage::isMain($cottageInfo);
        $payedQuarters = null;
        $partialPayedQuarter = null;

        // добавлю оплаченную сумму в xml
        if($main){
            $membershipContainer = $billDom->query('//membership')->item(0);
        }
        else{
            $membershipContainer = $billDom->query('//additional_membership')->item(0);
        }
        // проверка на предыдущую неполную оплату категории
        /** @var DOMElement $membershipContainer */
        $payedBefore = CashHandler::toRubles( 0 . $membershipContainer->getAttribute('payed'));
        // записываю сумму прошлой и текущей оплаты в xml
        $membershipContainer->setAttribute('payed', $paymentSumm + $payedBefore);
        // получу данные о полном счёте за членские взносы
        if($main){
            $membershipQuarters = $billDom->query('//membership/quarter');
        }else{
            $membershipQuarters = $billDom->query('//additional_membership/quarter');
        }
        /** @var DOMElement $quarter */
        foreach ($membershipQuarters as $quarter){
            // переменная для хранения суммы, предоплаченной за квартал в прошлый раз
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($quarter, 'summ');

            // отсекаю кварталы, полностью оплаченные в прошлый раз
            if($summ <= $payedBefore){
                $payedBefore -= $summ;
                continue;
            }
            elseif($payedBefore > 0){
                // это сумма, которая была предоплачена по кварталу в прошлый раз
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if($summ - $prepayed <= $paymentSumm){
                // денег хватает на полую оплату квартала. Добавляю его в список полностью оплаченных и вычитаю из общей суммы стоимость месяца
                // сумма платежа учитывается с вычетом ранее оплаченного
                $payedQuarters [] = ['date' => $quarter->getAttribute('date'), 'summ' => $summ - $prepayed];
                if($prepayed > 0){
                    // ранее частично оплаченный квартал считаю полностью оплаченным
                    $cottageInfo->partialPayedMembership = null;
                }
                // корректирую сумму текущего платежа с учётом предыдущего
                $paymentSumm -= $summ - $prepayed;
            }
            elseif($paymentSumm > 0){
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                $partialPayedQuarter = ['date' => $quarter->getAttribute('date'), 'summ' => $paymentSumm];
                break;
            }
        }
        // если есть полностью оплаченные кварталы

        if(!empty($payedQuarters)){
            // зарегистрирую каждый месяц как оплаченный
            foreach ($payedQuarters as $payedQuarter) {
                $date = $payedQuarter['date'];
                $summ = $payedQuarter['summ'];
                MembershipHandler::insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
            }
        }
        if(!empty($partialPayedQuarter)){
            $date = $partialPayedQuarter['date'];
            $summ = $partialPayedQuarter['summ'];
            // переменная для хранения финального значения суммы оплаты за квартал
            $summForSave = $summ;
            // проверю существование частично оплаченного месяца у данного участка
            $savedPartial = self::checkPartialPayedQuarter($cottageInfo);
            if($savedPartial){
                $prevPayment = CashHandler::toRubles($savedPartial['summ']);
                // получу полную стоимость данного месяца
                /** @var DOMElement $monthInfo */
                $monthInfo = $billDom->query('//quarter[@date="' . $date . '"]')->item(0);
                $fullPaySumm = CashHandler::toRubles($monthInfo->getAttribute('summ'));
                if($prevPayment + $summ === $fullPaySumm){
                    // отмечу месяц как полностью оплаченный
                    MembershipHandler::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prevPayment, $paymentTime);
                    $cottageInfo->membershipPayFor = $date;
                    $cottageInfo->partialPayedMembership = null;
                    return;
                }
                else{
                    $summForSave += $prevPayment;
                }
            }
            // отмечу квартал как оплаченный частично
            $cottageInfo->partialPayedMembership = "<partial date='$date' summ='$summForSave'/>";
            // зарегистрирую платёж в таблице оплаты членских взносов
            if($main){
                $table = new Table_payed_membership();
                $table->cottageId = $cottageInfo->cottageNumber;
            }
            else{
                $table = new Table_additional_payed_membership();
                $table->cottageId = $cottageInfo->masterId;
            }
            $table->billId = $billId;
            $table->quarter = $date;
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
    public static function finishPartialPayment($billDom, $cottageInfo, $billId, $paymentTime){
        $main = Cottage::isMain($cottageInfo);
        $payedMonths = null;
        $partialPayedMonth = null;
        // добавлю оплаченную сумму в xml
        if($main){
            $membershipContainer = $billDom->query('//membership')->item(0);
        }
        else{
            $membershipContainer = $billDom->query('//additional_membership')->item(0);
        }
        // проверю, не оплачивалась ли часть платежа ранее
        /** @var DOMElement $membershipContainer */
        $payedBefore = CashHandler::toRubles(0 . $membershipContainer->getAttribute('payed'));
        // получу данные о полном счёте за электричество
        if($main){
            $membershipQuarters = $billDom->query('//membership/quarter');
        }
        else{
            $membershipQuarters = $billDom->query('//additional_membership/quarter');
        }

        /** @var DOMElement $quarter */
        foreach ($membershipQuarters as $quarter){
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($quarter, 'summ');
            if($summ <= $payedBefore){
                $payedBefore -= $summ;
                continue;
            }
            elseif($payedBefore > 0){
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if($prepayed > 0){
                // часть квартала оплачена заранее
                $date = $quarter->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
                // отмечу квартал последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
            }
            else{
                // отмечу месяц как оплаченный полностью
                $date = $quarter->getAttribute('date');
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime);
                // отмечу месяц последним оплаченным для участка
                $cottageInfo->membershipPayFor = $date;
            }
        }
        $cottageInfo->partialPayedMembership = null;
    }

    public static function checkPartialPayedQuarter($cottageInfo)
    {
        if($cottageInfo->partialPayedMembership){
            $dom = new DOMHandler($cottageInfo->partialPayedMembership);
            $root = $dom->query('/partial');
            return DOMHandler::getElemAttributes($root->item(0));
        }
        return null;
    }
}