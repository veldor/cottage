<?php


namespace app\models\database;


use app\models\Calculator;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\GrammarHandler;
use app\models\interfaces\CottageInterface;
use app\models\Table_cottages;
use app\models\Table_payed_membership;
use Exception;
use yii\db\ActiveRecord;

/**
 * Class Mailing
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage_number [varchar(10)]
 * @property string $quarter [varchar(5)]
 * @property float $fixed_part [float]
 * @property float $square_part [float]
 * @property int $counted_square [int(11)]
 */
class Accruals_membership extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'accruals_membership';
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
                (new self(['cottage_number' => $cottage->getCottageNumber(), 'quarter' => $quarter, 'fixed_part' => $fixed, 'square_part' => $float, 'counted_square' => $square]))->save();
            }
        }
    }

    public static function getItem(CottageInterface $cottageInfo, string $quarter): ?Accruals_membership
    {
        return self::findOne(['cottage_number' => $cottageInfo->getCottageNumber(), 'quarter' => $quarter]);
    }

    /**
     * @return float
     */
    public function getAccrual(): float
    {
        return Calculator::countFixedFloat($this->fixed_part, $this->square_part, $this->counted_square);
    }

    /**
     * @return float
     */
    public function getPayed(): float
    {
        $pays = Table_payed_membership::getPays(Cottage::getCottageByLiteral($this->cottage_number), $this->quarter);
        $result = 0;
        if (!empty($pays)) {
            foreach ($pays as $pay) {
                $result = CashHandler::toRubles($result + $pay->summ);
            }
        }
        return $result;
    }
}