<?php


namespace app\models\database;


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
 * @property string $fio [varchar(255)]
 * @property int $cottage [int(10) unsigned]
 * @property string $address [varchar(255)]
 * @property int $part [int(11)]
 */

class Payer extends ActiveRecord
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';

    public static function tableName()
    {
        return 'payers';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['fio', 'address', 'part', 'cottage'],
            self::SCENARIO_EDIT => ['fio', 'address', 'part'],
        ];
    }

    public function attributeLabels():array
    {
        return [
            'fio' => 'ФИО',
            'address' => 'Почтовый адрес',
            'part' => 'Доля собственности',
        ];
    }


    /**
     * @return array
     */
    public function rules():array
    {
        return [
            [['fio'], 'required'],
        ];
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public static function addPayer()
    {
        // получу номер участка
        $cottageNum = trim(Yii::$app->request->post('cottage'));
        if(empty($cottageNum)){
            return ['message' => 'Не найден номер участка'];
        }
        $fio = trim(Yii::$app->request->post('fio'));
        if(empty($fio)){
            return ['message' => 'Не заполнено имя плательщика'];
        }
       $cottage = Cottage::getCottage($cottageNum);
        $payer = new self();
        $payer->cottage = $cottage->id;
        $payer->fio = $fio;
        $payer->save();
        Yii::$app->session->addFlash('success', "Плательщик $payer->fio добавлен.");
        return ['status' => 1];
    }

    /**
     * @param Cottage $cottage
     * @return Payer[]
     */
    public static function getCottagePayers(Cottage $cottage)
    {
        return self::findAll(['cottage' => $cottage->id]);
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public static function deletePayer()
    {
        $payerId = trim(Yii::$app->request->post('id'));
        if(!empty($payerId)){
            $payer = self::findOne(['id' => $payerId]);
            if(!empty($payer)){
                $payer->delete();
                Yii::$app->session->addFlash('success', "Плательщик удалён.");
                return ['status' => 1];

            }
            return ['message' => 'Не найден плательщик'];
        }
        return ['message' => 'Не найден идентификатор плательщика'];
    }

    public static function getPayerByName($payerName)
    {
        return self::findOne(['fio' => $payerName]);
    }

}