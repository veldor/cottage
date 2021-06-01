<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 02.12.2018
 * Time: 12:56
 */

namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class Table_payed_target
 * @package app\models
 * @property int $id [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property int $year [int(4) unsigned]
 * @property float $summ [float unsigned]
 * @property int $paymentDate [int(20) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class Table_payed_target extends ActiveRecord
{
    public static function tableName()
    {
        return 'payed_target';
    }

    public static function getPaysAmount(string $cottage_number, string $year)
    {
        $result = 0;
        if(GrammarHandler::isMain($cottage_number)){
            $pays = self::findAll(['year' => $year, 'cottageId' => $cottage_number]);
        }
        else{
            $pays = Table_additional_payed_target::findAll(['year' => $year, 'cottageId' => $cottage_number]);
        }
        if(!empty($pays)){
            foreach ($pays as $pay) {
                $result += $pay->summ;
            }
        }
        return $result;
    }

    public static function getPays(interfaces\CottageInterface $cottage, $item)
    {
        if($cottage->isMain()){
            return self::findAll(['cottageId' => $cottage->getCottageNumber(), 'year' => $item]);
        }
        return Table_additional_payed_target::findAll(['cottageId' => $cottage->getCottageNumber(), 'year' => $item]);

    }
}