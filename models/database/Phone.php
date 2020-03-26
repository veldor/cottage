<?php


namespace app\models\database;


use app\models\selections\CottageMail;
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
 * @property int $cottage_num [int(10) unsigned]
 * @property string $phone [varchar(255)]
 * @property string $fio [varchar(255)]
 */

class Phone extends ActiveRecord
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';

    public static function tableName()
    {
        return 'phones';
    }

    /**
     * @return array
     * @throws StaleObjectException
     * @throws Throwable
     */
    public static function deletePhone()
    {
        $id = trim(Yii::$app->request->post('id'));
        if(!empty($id)){
            $phone = self::findOne(['id' => $id]);
            if(!empty($phone)){
                $phone->delete();
                Yii::$app->session->addFlash('success', "Номер телефона удалён.");
                return ['status' => 1];

            }
            return ['message' => 'Не найден номер телефона'];
        }
        return ['message' => 'Не найден идентификатор номера телефона'];
    }


    public function attributeLabels():array
    {
        return [
            'fio' => 'ФИО',
            'phone' => 'Номер телефона',
        ];
    }


    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['fio', 'phone', 'cottage_num'],
            self::SCENARIO_EDIT => ['fio', 'phone'],
        ];
    }


    /**
     * @return array
     */
    public function rules():array
    {
        return [
            [['fio', 'phone'], 'required'],
        ];
    }

    public static function getCottagePhones(Cottage $cottage)
    {
        return self::findAll(['cottage_num' => $cottage->id]);
    }
}