<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.09.2018
 * Time: 19:18
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_additional_cottages
 * @package app\models
 * @property int $cottageNumber [int(5) unsigned]  Номер участка
 * @property string $cottageOwnerPersonals [varchar(200)]  Фамилия имя и отчество владельца
 * @property string $cottageOwnerPhone [char(18)]  Контактный номер владельца
 * @property string $cottageOwnerEmail [varchar(200)]  Адрес электронной почты владельца
 * @property string $cottageContacterPersonals [varchar(200)]  Фамилия имя и отчество контактного лица
 * @property string $cottageContacterPhone [char(18)]  Контактный номер контактного лица
 * @property string $cottageContacterEmail [varchar(200)]  Адрес электронной почты контактного лица
 * @property int $cottageSquare [int(4) unsigned]  Площадь участка, кв.м.
 * @property string $membershipPayFor [varchar(20)]  Последний оплаченный квартал
 * @property string $powerPayFor [varchar(8)]  Последний оплаченный месяц
 * @property string $targetDebt [float unsigned]  Сумма задолженности по целевым платежам
 * @property string $powerDebt [float unsigned]  Сумма задолженности по платежам за электроэнергию
 * @property string $singleDebt [float unsigned]  Сумма задолженности по разовым платежам
 * @property int $currentPowerData [int(20) unsigned]  Последние показания счётчика электроэнергии
 * @property string $deposit [float unsigned]  Сумма средств на депозите
 * @property string $cottageOwnerAddress Адрес владельца участка
 * @property bool $cottageHaveRights [tinyint(1)]  Наличие справки о праве на собственность
 * @property string $cottageOwnerDescription Дополнительная информация о владельце участка
 * @property string $targetPaysDuty Полная иформация о задолежнности по целевым платежам
 * @property string $singlePaysDuty Полная иформация о задолежнности по разовым платежам
 * @property bool $individualTariff [tinyint(1)]  Индивидуальный тариф
 * @property string $individualTariffRates Индивидуальные расценки
 * @property bool $haveAdditional [tinyint(1)]  Наличие дополнительного участка
 */

class Table_cottages extends ActiveRecord
{
    public static function tableName()
    {
        return 'cottages';
    }
}