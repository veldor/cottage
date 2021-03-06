<?php


namespace app\models\database;


use app\models\Calculator;
use app\models\Cottage;
use app\models\interfaces\CottageInterface;
use app\models\Table_payed_target;
use Exception;
use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage_number [varchar(10)]
 * @property string $year [char(4)]
 * @property float $fixed_part [float]
 * @property float $square_part [float]
 * @property float $payed_outside [float]
 * @property int $counted_square [int(11)]
 */
class Accruals_target extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'accruals_target';
    }

    /**
     * @param $quarter
     * @param $fixed
     * @param $float
     * @throws Exception
     */
    public static function addQuarter($quarter, $fixed, $float): void
    {
        $cottages = Cottage::getRegister();
        $additionalCottages = Cottage::getRegister(true);
        $cottages = array_merge($cottages, $additionalCottages);
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                $square = CottageSquareChanges::getQuarterSquare($cottage, $quarter);
                (new self(['cottage_number' => $cottage->getCottageNumber(), 'year' => $quarter, 'fixed_part' => $fixed, 'square_part' => $float, 'counted_square' => $square]))->save();
            }
        }
    }

    public static function getItem(CottageInterface $cottageInfo, string $year)
    {
        return self::findOne(['cottage_number' => $cottageInfo->getCottageNumber(), 'year' => $year]);
    }

    public function countAmount()
    {
        return Calculator::countFixedFloat($this->fixed_part, $this->square_part, $this->counted_square);
    }

    public function countPayed()
    {
        return Table_payed_target::getPaysAmount($this->cottage_number, $this->year);
    }


    /**
     * @return float
     */
    public function getAccrual(): float
    {
        return Calculator::countFixedFloat($this->fixed_part, $this->square_part, $this->counted_square);
    }
}