<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 13.12.2018
 * Time: 8:54
 */

namespace app\models;


use app\models\interfaces\CottageInterface;
use yii\db\ActiveRecord;

/**
 * Class Table_additional_cottages
 * @package app\models
 *
 * @property int $masterId [int(10) unsigned]
 * @property int $cottageSquare [int(4) unsigned]
 * @property bool $isPower [tinyint(1)]
 * @property string $powerPayFor [varchar(10)]
 * @property int $currentPowerData [int(10)]
 * @property float $powerDebt [float]
 * @property bool $isMembership [tinyint(1)]
 * @property string $membershipPayFor [varchar(10)]
 * @property bool $isTarget [tinyint(1)]
 * @property float $targetDebt [float]
 * @property string $targetPaysDuty
 * @property bool $individualTariff [tinyint(1)]
 * @property string $individualTariffRates
 * @property string $cottageOwnerPersonals [varchar(200)]  Имя владельца части участка
 * @property string $cottageOwnerPhone [char(18)]  Телефон владельца части участка
 * @property string $cottageOwnerEmail [varchar(200)]  Адрес электронной почты владельца участка
 * @property bool $hasDifferentOwner [tinyint(1)]
 * @property string $cottageOwnerAddress
 * @property float $deposit [float]
 * @property float $singleDebt [float]
 */
class Table_additional_cottages extends ActiveRecord implements CottageInterface
{
    public static function tableName():string
    {
        return 'additional_cottages';
    }

    /**
     * @return string
     */
    public function getCottageNumber():string
    {
        return $this->masterId . '-a';
    }
}