<?php


namespace app\models;


use app\models\small_classes\IndividualMissing;
use Exception;
use Yii;
use yii\base\Model;

class PersonalTariffFilling extends Model
{
    const SCENARIO_FILL = 'fill';

    public function scenarios(): array
    {
        return [
            self::SCENARIO_FILL => ['membership', 'target'],

        ];
    }

    public $membership;
    public $target;

    public static function getCottagesWithMissing()
    {
        $needToFill = [];
        $thisQuarter = TimeHandler::getCurrentQuarter();
        $cottages = PersonalTariff::getCottagesWithIndividual();
        $targetTariffs = TargetHandler::getCurrentRates();
        /** @var Table_additional_cottages|Table_cottages $cottage */
        foreach ($cottages as $cottage) {
            $isMain = Cottage::isMain($cottage);
            $individualMissing = new IndividualMissing();
            $individualMissing->cottageInfo = $cottage;
            if ($isMain || $cottage->isTarget) {
                // заполненность целевых тарифов
                $tariffs = PersonalTariff::getTargetRates($cottage);
                if (count($tariffs) != count($targetTariffs)) {
                    foreach ($targetTariffs as $key => $value) {
                        if (empty($tariffs[$key])) {
                            $individualMissing->targetMissing[] = $key;
                        }
                    }
                }
            }
            if ($isMain || $cottage->isMembership) {
                // заполненость членских тарифов
                $lastMemTariff = PersonalTariff::getLastMembershipRate($cottage);
                if ($lastMemTariff < $thisQuarter) {
                    // получу список кварталов, которые надо заполнить
                    $individualMissing->membershipMissing = TimeHandler::getQuarterList(['start' => $lastMemTariff, 'finish' => $thisQuarter]);
                }
            }
            if (!empty($individualMissing->targetMissing) || !empty($individualMissing->membershipMissing)) {
                $needToFill[] = $individualMissing;
            }
        }
        return $needToFill;
    }

    public function fill()
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            if (!empty($this->membership)) {
                foreach ($this->membership as $key => $item) {
                    if (!empty($item)) {
                        try{
                            $cottageInfo = Cottage::getCottageByLiteral($key);
                            $pt = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_FILL]);
                            $pt->cottageNumber = (int)Cottage::getCottageNumber($cottageInfo);
                            $pt->additional = !Cottage::isMain($cottageInfo);
                            $pt->membership = $item;
                            if (!(($pt->validate() && $pt->saveTariffs()))) {
                                continue;
                            }
                        }
                        catch (Exception $e){
                            continue;
                        }
                    }
                }
            }
            if (!empty($this->target)) {
                foreach ($this->target as $key => $item) {
                    if (!empty($item)) {
                        try{
                            $cottageInfo = Cottage::getCottageByLiteral($key);
                            $pt = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_FILL]);
                            $pt->cottageNumber = (int)Cottage::getCottageNumber($cottageInfo);
                            $pt->additional = !Cottage::isMain($cottageInfo);
                            $pt->target = $item;
                            if (!(($pt->validate() && $pt->saveTariffs()))) {
                                continue;
                            }
                        }
                        catch (Exception $e){
                            continue;
                        }
                    }
                }
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }
}