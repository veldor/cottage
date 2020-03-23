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
                    if (!empty(Cottage::getCottage($previous))){
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