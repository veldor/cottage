<?php


namespace app\models\database;


use app\models\ComplexPayment;
use app\models\Cottage;
use app\models\FinesHandler;
use app\models\interfaces\CottageInterface;
use app\models\MembershipHandler;
use app\models\PowerHandler;
use app\models\SingleHandler;
use app\models\TargetHandler;
use yii\db\ActiveRecord;

/**
 * Class CottagesDebt
 * @package app\models\database
 *
 * @property int $id [int(10) unsigned]
 * @property string $cottage_number [char(5)]
 * @property string $power_debt [float unsigned]
 * @property string $membership_debt [float unsigned]
 * @property string $target_debt [float unsigned]
 * @property string $single_debt [float unsigned]
 * @property string $fines [float unsigned]
 * @property bool $expired [tinyint(1)]
 * @property bool $has_mail [tinyint(1)]
 * @property bool $has_opened_bill [tinyint(1)]
 */
class CottagesFastInfo extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'cottage_fast_info';
    }

    /**
     * @param $cottage CottageInterface
     */
    public static function recalculatePowerDebt($cottage): void
    {
        $debt = PowerHandler::getDebtAmount($cottage);
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->power_debt = $debt;
            $existentItem->save();
        }
    }

    /**
     * Получу всю информацию о задолженностях
     * @return CottagesFastInfo[]
     */
    public static function getFullInfo(): array
    {
        $results = self::find()->all();
        $answer = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $answer[$result->cottage_number] = $result;
            }
        }
        return $answer;
    }

    /**
     * @param CottageInterface $cottage
     */
    public static function recalculateMembershipDebt(CottageInterface $cottage): void
    {
        $debt = MembershipHandler::getDebtAmount($cottage);
        if($cottage->haveAdditional()){
            $debt += MembershipHandler::getDebtAmount($cottage->getAdditional());
        }
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->membership_debt = $debt;
            $existentItem->save();
        }
    }

    /**
     * @param CottageInterface $cottage
     */
    public static function recalculateTargetDebt(CottageInterface $cottage): void
    {
        $debt = TargetHandler::getDebtAmount($cottage);
        if($cottage->haveAdditional()){
            $debt += TargetHandler::getDebtAmount($cottage->getAdditional());
        }
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->target_debt = $debt;
            $existentItem->save();
        }
    }

    /**
     * @param CottageInterface $cottage
     */
    public static function recalculateSingleDebt(CottageInterface $cottage): void
    {
        $debt = SingleHandler::getDebtAmount($cottage);
        if($cottage->haveAdditional()){
            $debt += SingleHandler::getDebtAmount($cottage->getAdditional());
        }
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->single_debt = $debt;
            $existentItem->save();
        }
    }

    /**
     * @param CottageInterface $cottage
     */
    public static function recalculateFines(CottageInterface $cottage): void
    {
        $debt = FinesHandler::getFinesSumm($cottage->getCottageNumber());
        if($cottage->haveAdditional()){
            $debt += FinesHandler::getFinesSumm($cottage->getAdditional()->getCottageNumber());
        }
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->fines = $debt;
            $existentItem->save();
        }
    }

    public static function checkMail(CottageInterface $cottage): void
    {
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->has_mail = Cottage::hasMail($cottage);
            $existentItem->save();
        }
    }

    public static function checkUnpaidBill(CottageInterface $cottage)
    {
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->has_opened_bill = (bool)ComplexPayment::checkUnpayed($cottage);
            $existentItem->save();
        }
    }

    public static function checkExpired(CottageInterface $cottage)
    {
        $existentItem = self::findOne(['cottage_number' => $cottage->getCottageNumber()]);
        if ($existentItem !== null) {
            $existentItem->expired = Cottage::hasPayUpDuty($cottage);
            $existentItem->save();
        }
    }
}