<?php

namespace app\models;

use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\base\Model;

class TariffsKeeper extends Model {

	public ?array $powerMonthsForFilling = [];
	public ?array $membershipQuartersForFilling = [];
	public ?string $targetMonth;

	public ?Table_tariffs_power $lastPowerData;
	public ?Table_tariffs_membership $lastMembershipData;


	public ?Table_tariffs_power $power;
	public ?Table_tariffs_membership $membership;
	public ?Table_tariffs_target $target;

	const SCENARIO_FILL = 'fill';

	public function scenarios():array
	{
		return [
			self::SCENARIO_FILL => ['power', 'membership'],
		];
	}


	public function save(): bool
	{
		if (!empty($this->power)) {
			foreach ($this->power as $key => $item) {
				$insertPower = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_TARIFF]);
				$insertPower->month = $key;
				$insertPower->powerCost = $item['cost'];
				$insertPower->powerOvercost = $item['overcost'];
				$insertPower->powerLimit = $item['limit'];
				$insertPower->createTariff();
			}
		}
		if (!empty($this->membership)) {
			$form = new MembershipHandler(['scenario' => MembershipHandler::SCENARIO_NEW_RECORD]);
			$form->membership = $this->membership;
			$form->save();
			foreach ($this->membership as $key => $value){
				MembershipHandler::recalculateMembership($key);
			}
		}
		return true;
	}


	/**
	 * @return bool
	 * @var $membershipTariff Table_tariffs_membership
	 */
	public function fill(): bool
	{
		// ищу в базе значения тариф на электроэнергию за предыдущий месяц. Если нахожу- заполняю значения модели, если не нахожу- возвращаю false, значит- необходимо их заполнить.
		try {
			$powerTariff = PowerHandler::getRowTariff(TimeHandler::getPreviousShortMonth());
		} catch (InvalidArgumentException $e) {
			return false;
		}
		$this->power = $powerTariff;
		$this->targetMonth = TimeHandler::getPreviousShortMonth();
		// Ищу значение тарифа на членские взносы за данный квартал.
		try {
			$membershipTariff = MembershipHandler::getRowTariff(TimeHandler::getCurrentQuarter());
		} catch (InvalidArgumentException $e) {
			return false;
		}
		$this->membership = $membershipTariff;
		$targetTariff = Table_tariffs_target::find()->where(['year' => TimeHandler::getThisYear()])->one();
		if (!empty($targetTariff)) {
			$this->target = $targetTariff;
		}
		return true;
	}

	public function fillLastData()
	{
		// Получу данные о последнем заполненном месяце тарифов за электроэнергию
		try{
			$lastPowerTariff = PowerHandler::getRowTariff();
			// если последний заполненный месяц указан, проверю- если он предыдущий- всё нормально, возвращаю данные для заполнения, если нет- считаю, сколько месяцев нужно заполнить
			$point = TimeHandler::getMonthTimestamp(TimeHandler::getPreviousShortMonth());
			if (!($lastPowerTariff->searchTimestamp === $point)) {
				// тарифы на последний месяц не заполнены. Возвращаю список месяцев для заполненния
				$this->powerMonthsForFilling = TimeHandler::getMonthsList($lastPowerTariff->targetMonth, TimeHandler::getPreviousShortMonth());
				$this->lastPowerData = $lastPowerTariff;
			}
		}
		catch (InvalidValueException $e){
		// если не найдено ни одного тарифа- значит, база только создана и нужно заполнить данные только за предыщущий месяц.
			$this->powerMonthsForFilling = TimeHandler::getMonthsList(TimeHandler::getPreviousShortMonth());
		}
		// то же с членскими взносами
		// Получу данные о последнем заполненном квартале
		try{
			$lastMembershipTariff = MembershipHandler::getRowTariff();
			// если последний заполненный месяц указан, проверю- если он предыдущий- всё нормально, возвращаю данные для заполнения, если нет- считаю, сколько месяцев нужно заполнить
			$point = TimeHandler::getQuarterTimestamp(TimeHandler::getCurrentQuarter());
			if (!($lastMembershipTariff->search_timestamp >= $point)) {
				// тарифы на последний месяц не заполнены. Возвращаю список кварталов для заполненния
				$this->membershipQuartersForFilling = TimeHandler::getQuarterList($lastMembershipTariff->quarter);
				$this->lastMembershipData = $lastMembershipTariff;
			}
		}
		catch (InvalidValueException $e){
			// если не найдено ни одного тарифа- значит, база только создана и нужно заполнить данные только за этот квартал.
			$this->membershipQuartersForFilling[TimeHandler::getCurrentQuarter()] = TimeHandler::getCurrentQuarter();
		}
	}

	public static function checkFilling(): bool
	{
		// проверю заполненность тарифов на электроэнергию и членских взносов на данный момент.
		try{
			$lastPowerTariff = PowerHandler::getRowTariff()['searchTimestamp'];
			$point = TimeHandler::getMonthTimestamp(TimeHandler::getPreviousShortMonth());
			if ($lastPowerTariff < $point) {
				return false;
			}
		}
		catch (InvalidValueException $e){
			return false;
		}
		try{
			$lastMembershipTariff = MembershipHandler::getRowTariff()['search_timestamp'];
			$point = TimeHandler::getQuarterTimestamp(TimeHandler::getCurrentQuarter());
			if (!($lastMembershipTariff >= $point)) {
				return false;
			}
		}
		catch (InvalidValueException $e){
			return false;
		}
		return true;
	}
}