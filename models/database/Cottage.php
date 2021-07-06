<?php


namespace app\models\database;


use app\models\utils\DbTransaction;
use Exception;
use Throwable;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\NotFoundHttpException;

/**
 * Class Table_bank_invoices
 * @package app\models
 *
 * @property int $id [int(10) unsigned]
 * @property string $num [varchar(100)]
 * @property int $square [int(10) unsigned]
 * @property string $membership [varchar(100)]
 * @property string $rigths [varchar(100)]
 * @property string $description
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
 * @property string $passportData Паспортные данные
 * @property string $cottageRightsData Данные права собственности
 * @property string $cottageRegistrationInformation Данные кадастрового учёта
 * @property string $partialPayedPower Частично оплаченное электричество
 * @property string $partialPayedMembership Частично оплаченный членский взнос
 * @property bool $cottageRegisterData [tinyint(1)]  Данные для реестра
 * @property string $bill_payers Имена плательщиков
 * @property bool $is_mail [tinyint(1)]  Наличие электронной почты
 */

class Cottage extends ActiveRecord
{
    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';
    const PREFERRED_SQUARE = 270;

    public static function exist(string $num)
    {
        return self::find()->where(['num' => $num])->count();
    }

    public static function getPreviousCottage()
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/http\:\/\/linda\.snt\/show\/(\d+)/', $link, $matches)) {
            // найду участок, который по номеру меньше текущего
            while ($previous = --$matches[1]) {
                try{
                    if (Cottage::getCottage($previous) !== null){
                        return 'http://linda.snt/show/' . $previous;
                    }
                    if ($previous < 1) {
                        break;
                    }
                }
                catch (Exception $e){

                }
            }
        }
        return 'http://linda.snt/show/' . self::COTTAGES_QUANTITY;
    }

    public static function getNextCottage()
    {
        $link = $_SERVER['HTTP_REFERER'];
        if (preg_match('/http\:\/\/linda\.snt\/show\/(\d+)/', $link, $matches)) {
            // найду участок, который по номеру меньше текущего
            while ($next = ++$matches[1]) {
                if ($next > self::COTTAGES_QUANTITY) {
                    break;
                }
                try{
                    if (!empty(Cottage::getCottage($next))){
                        return 'http://linda.snt/show/' . $next;
                    }
                }
                catch (Exception $e){

                }
            }
        }
        return 'http://linda.snt/show/1';
    }


    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['num', 'square', 'membership', 'rights', 'description'],
            self::SCENARIO_EDIT => ['square', 'membership', 'rights', 'description'],
        ];
    }


    public function attributeLabels():array
    {
        return [
            'num' => 'Номер участка',
            'square' => 'Площадь участка',
            'membership' => 'Сведения о членстве',
            'rigths' => 'Сведения о правах владения',
            'description' => 'Дополнительные сведения об участке',
        ];
    }


    /**
     * @return array
     */
    public function rules():array
    {
        return [
            [['num'], 'required'],
        ];
    }

    const COTTAGES_QUANTITY = 400;

    public static function tableName()
    {
        return 'cottages';
    }

    public static function getCottages()
    {
        $result = self::find()->orderBy('cast(num as unsigned) asc')->all();
        if(empty($result)){
            $transaction = new DbTransaction();
            $counter = 1;
            while ($counter <= self::COTTAGES_QUANTITY){
                $new = new self(['scenario' => self::SCENARIO_CREATE]);
                $new->num = $counter;
                $new->save();
                $counter++;
            }
            $transaction->commitTransaction();
            $result = self::find()->orderBy('cast(num as unsigned) asc')->all();
        }
        return $result;
    }

    public static function registerNew()
    {
        // получу номер участка
        $cottageNum = trim(Yii::$app->request->post('number'));
        if(!empty($cottageNum)){
            // если участок ещё не зарегистрирован- регистрирую
            if(!self::find()->where(['num' => $cottageNum])->count()){
                $new = new self();
                $new->num = $cottageNum;
                $new->save();
            }
            else{
                return ['message' => 'Участок с этим номером уже зарегистрирован'];
            }
            Yii::$app->session->addFlash('success', 'Участок добавлен');
            return ['status' => 1];
        }
        return ['message' => 'Не найден номер участка'];
    }

    /**
     * @param $cottageNumber
     * @return Cottage
     * @throws NotFoundHttpException
     */
    public static function getCottage($cottageNumber)
    {
        $cottage = self::findOne(['num' => $cottageNumber]);
        if(empty($cottage)){
            throw new  NotFoundHttpException();
        }
        return $cottage;
    }

    /**
     * @return array
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function deleteCottage()
    {
        $cottageNum = trim(Yii::$app->request->post('cottageNumber'));
        if(!empty($cottageNum)){
            $registered = self::findOne(['num' => $cottageNum]);
            if(!empty($registered)){
                $registered->delete();
                Yii::$app->session->addFlash('success', 'Участок удалён');
                return ['status' => 1];
            }
            return ['message' => 'Участок с этим номером не зарегистрирован'];
        }
        return ['message' => 'Не найден номер участка'];
    }
}