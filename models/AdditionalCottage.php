<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 21:40
 */

namespace app\models;


use app\validators\CheckCottageNoRegistred;
use app\validators\CheckMonthValidator;
use app\validators\CheckPhoneNumberValidator;
use app\validators\CheckQuarterValidator;
use app\validators\CheckTargetValidator;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;

/**
 *
 * @property int $masterId [int(10) unsigned]
 * @property int $cottageSquare [int(4) unsigned]
 * @property int $isPower [int(1) unsigned]
 * @property string $powerPayFor [varchar(10)]
 * @property int $currentPowerData [int(10) unsigned]
 * @property int $hasDifferentOwner [int(1) unsigned]
 * @property float $singleDebt [float unsigned]
 */

class AdditionalCottage extends Model
{
    public $cottageNumber;
    public $cottageSquare;
    public $isPower;
    public $currentPowerData;
    public $lastPayedMonth;
    public $isMembership;
    public $membershipPayFor;
    public $isTarget;
    public $target;
    public $targetFilled = true;

    public $differentOwner = false;

    public $cottageOwnerPersonals; // личные данные владельца участка.
    public $cottageOwnerPhone; // контактный телефон владельца участка.
    public $cottageOwnerEmail; // адрес почты владельца участка.
    public $ownerAddressIndex = ''; // Индекс места проживания
    public $ownerAddressTown = ''; // Город проживания
    public $ownerAddressStreet = ''; // Улица проживания
    public $ownerAddressBuild = ''; // Номер дома
    public $ownerAddressFlat = ''; // Номер квартиры

    public $targetInfo;

    public $currentCondition;

    const SCENARIO_CREATE = 'create';

    /**
     * @return Table_additional_cottages[]|array
     */
    public static function getRegistred():array
    {
        return Table_additional_cottages::find()->orderBy('masterId')->all();
    }

    /**
     * @return array
     */
    public static function getRegistredList(): array
    {
        $answer = [];
        $data = Table_additional_cottages::find()->orderBy('masterId')->all();
        if (!empty($data)) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    $answer[$item->masterId] = $item;
                }
            } else {
                $answer[$data->masterId] = $data;
            }
        }
        return $answer;
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['cottageNumber', 'cottageSquare', 'isPower', 'currentPowerData', 'lastPayedMonth', 'isMembership', 'membershipPayFor', 'isTarget', 'target', 'targetFilled', 'cottageOwnerPersonals', 'cottageOwnerPhone', 'cottageOwnerEmail', 'ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat', 'differentOwner']
        ];
    }


    public function attributeLabels(): array
    {
        return [
            'cottageNumber' => 'Номер участка',
            'cottageSquare' => 'Площадь участка',
            'currentPowerData' => 'Текущие показания счётчика электроэнергии',
            'membershipPayFor' => 'Квартал, по который оплачены членские взносы',
            'targetPayFor' => 'Месяц, по который оплачены целевые взносы',
        ];
    }

    public function rules(): array
    {
        return [
            [['cottageNumber', 'cottageSquare', 'isPower', 'isMembership', 'isTarget'], 'required', 'on' => self::SCENARIO_CREATE],
            [['cottageOwnerPersonals'], 'required', 'when' => function () {
                return $this->differentOwner;
            }, 'whenClient' => "function () {return $('input#additionalcottage-differentowner').prop('checked');}"],
            [['currentPowerData'], 'required', 'when' => function () {
                return $this->isPower;
            }, 'whenClient' => "function () {return $('input#additionalcottage-ispower').prop('checked');}"],
            [['membershipPayFor'], 'required', 'when' => function () {
                return $this->isMembership;
            }, 'whenClient' => "function () {return $('input#additionalcottage-ismembership').prop('checked');}"],
            ['cottageNumber', CheckCottageNoRegistred::class],
            ['cottageSquare', 'integer', 'min' => 1, 'max' => 1000],
            ['currentPowerData', 'integer', 'min' => 0, 'max' => 9999999999],
            ['lastPayedMonth', CheckMonthValidator::class],
            ['membershipPayFor', CheckQuarterValidator::class],
            ['targetFilled', CheckTargetValidator::class, 'when' => function () {
                return $this->isTarget;
            }, 'whenClient' => "function () {return $('input#additionalcottage-istarget').prop('checked');}"],


            ['cottageOwnerPersonals', 'match', 'pattern' => '/^[ёа-я- ]*$/iu', 'message' => 'Проверьте правильность данных. Разрешены буквы, тире и пробел!'],
            ['cottageOwnerPhone', CheckPhoneNumberValidator::class],
            ['cottageOwnerEmail', 'email'],
            [['ownerAddressIndex', 'ownerAddressTown', 'ownerAddressStreet', 'ownerAddressBuild', 'ownerAddressFlat'], 'string', 'max' => 200],
        ];
    }

    public function checkTarget($attribute)
    {
        // разберу массив целевых взносов
        $tariffs = TargetHandler::getCurrentRates();
        if (count($tariffs) !== count($this->target)) {
            $this->addError($attribute, 'Заполнены не все данные');
        } elseif (empty($this->cottageSquare)) {
            $this->addError($attribute, 'Не заполнена площадь участка');
        }
    }

    public function fill($cottageNumber)
    {
        if (!$this->currentCondition = Table_cottages::findOne($cottageNumber)) {
            throw new InvalidArgumentException('Неверный номер участка');
        }
        $this->cottageNumber = $cottageNumber;
        $this->targetInfo = TargetHandler::getRegistrationRates(Cottage::getCottageInfo($cottageNumber));
    }

    /**
     * @return array
     * @throws \yii\base\ErrorException
     */
    public function create(): array
    {
        // Получу информацию о родительском участке
        if (!$this->currentCondition->haveAdditional) {
            // создам новый участок
            $newCottage = new Table_additional_cottages();
            $newCottage->masterId = $this->cottageNumber;
            $newCottage->cottageSquare = $this->cottageSquare;
            $newCottage->isPower = $this->isPower;
            if ($this->isPower) {
                $newCottage->isPower = true;
                $newCottage->currentPowerData = $this->currentPowerData;
                $newCottage->powerDebt = 0;
                if ($this->lastPayedMonth) {
                    $newCottage->powerPayFor = $this->lastPayedMonth;
                } else {
                    $newCottage->powerPayFor = TimeHandler::getTwoMonthAgo();
                }
            }
            if ($this->isMembership) {
                $newCottage->isMembership = true;
                $newCottage->membershipPayFor = $this->membershipPayFor;
            }
            if ($this->isTarget) {
                $newCottage->isTarget = true;
                $dutyInfo = TargetHandler::calculateDuty($this->target, $newCottage);
                $newCottage->targetDebt = $dutyInfo['totalDuty'];
                $newCottage->targetPaysDuty = $dutyInfo['dutyDetails'];
            }

            if($this->differentOwner){
                $newCottage->hasDifferentOwner = true;
                $newCottage->cottageOwnerPersonals = GrammarHandler::clearWhitespaces($this->cottageOwnerPersonals);
                $newCottage->cottageOwnerPhone = $this->cottageOwnerPhone;
                $newCottage->cottageOwnerEmail = $this->cottageOwnerEmail;
                $newCottage->cottageOwnerAddress = "$this->ownerAddressIndex & $this->ownerAddressTown & $this->ownerAddressStreet & $this->ownerAddressBuild & $this->ownerAddressFlat";
            }

            $newCottage->save();
            /**
             * @var $ref Table_additional_cottages|Table_cottages
             */
            $ref = $this->currentCondition;
            $ref->haveAdditional = true;
            $ref->save();
            if ($this->isPower) {
                $attributes = ['cottageNumber' => $this->cottageNumber, 'month' => $newCottage->powerPayFor, 'newPowerData' => $this->currentPowerData, 'additional' => true];
                $insertPower = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD, 'attributes' => $attributes]);
                $powerInfo = $insertPower->insert();
                $powerInfo['data']->payed = 'yes';
	            /** @var Table_power_months|Table_additional_power_months $powerInfo */
	            $powerInfo['data']->save();
            }
            if($this->isMembership){
	            $membershipDifference = MembershipHandler::getTariffs(['start' => $newCottage->membershipPayFor]);
	            if(!empty($membershipDifference)){
		            foreach ($membershipDifference as $key => $item){
			            MembershipHandler::recalculateMembership($key);
		            }
	            }
            }
            if($this->isTarget){
	            $targets = TargetHandler::getCurrentRates();
	            foreach ($targets as $key => $value){
		            TargetHandler::recalculateTarget($key);
	            }
            }
            $session = Yii::$app->session;
            $session->addFlash('success', 'Дополнительный участок добавлен');
            return ['status' => 1];
        }
        throw new InvalidArgumentException('У этого участка уже есть дополнительный');
    }

    /**
     * @param $cottageId int|string
     * @return array
     */
    public static function getCottageInfo($cottageId): array
    {
        if (is_int((int)$cottageId)) {
            $cottageInfo = Table_additional_cottages::findOne($cottageId);
            if ($cottageInfo) {
                $totalDebt = 0;
                if ($cottageInfo->isPower) {
                    $powerStatus = PowerHandler::getAdditionalCottageStatus($cottageInfo);
                    $totalDebt += $powerStatus['powerDebt'];
                } else {
                    $powerStatus = [];
                }
                $membershipDebt = 0;
                if ($cottageInfo->isMembership) {
                    if ($cottageInfo->individualTariff) {
                        $membershipDebt = PersonalTariff::countMembershipDebt($cottageInfo)['summ'];
                    } else {
                        $membershipDebt = MembershipHandler::getCottageStatus($cottageInfo);
                    }
                    $totalDebt += $membershipDebt;
                }
                $targetDebt = 0;
                if ($cottageInfo->isTarget) {
                    $targetDebt = $cottageInfo->targetDebt;
                    $totalDebt += $targetDebt;
                }

                if($cottageInfo->hasDifferentOwner){
                    $singleDebt = $cottageInfo->singleDebt;
                    $totalDebt += $singleDebt;
                }

                $unpayedBills = false;
                // проверю, есть ли дополнительный владелец
                if($cottageInfo->hasDifferentOwner){
                    $unpayedBills = Table_payment_bills_double::findOne(['cottageNumber' => $cottageInfo->masterId, 'isPayed' => 0]);
                }
                return ['cottageInfo' => $cottageInfo, 'powerStatus' => $powerStatus, 'totalDebt' => $totalDebt, 'membershipDebt' => $membershipDebt, 'targetDebt' => $targetDebt, 'unpayedBills' => $unpayedBills];
            }
        }
        throw new InvalidArgumentException('Ошибка получения информации о дополнительном участке');
    }

    /**
     * @param $cottageId int|string
     * @return Table_additional_cottages
     */
    public static function getCottage($cottageId): Table_additional_cottages
    {
        if (is_int((int)$cottageId)) {
            return Table_additional_cottages::findOne($cottageId);
        }
        throw new InvalidArgumentException('Ошибка получения информации о дополнительном участке');
    }
}