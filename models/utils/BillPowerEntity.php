<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\GrammarHandler;
use app\models\Table_additional_payed_power;
use app\models\Table_payed_power;

class BillPowerEntity extends BillContentEntity
{
    public int $oldCounterData;
    public int $newCounterData;
    public int $powerSocialLimit;
    public int $powerSocialCost;
    public int $powerRoutineCost;
    public int $powerIndicationDifference;
    public int $usedInLimit;
    public int $usedOverLimit;
    public int $costInLimit;
    public int $costOverLimit;

    public function getTextContent(): string
    {
        // найду стоимость периода
        $payedInBillCount = $this->getPayedInside();
        $payedOutBillCount = $this->getPayedOutside();
        $leftToPay = CashHandler::toRubles(CashHandler::toRubles($this->totalAccrued) - $payedInBillCount - $payedOutBillCount);
        return "<tr><td>Электроэнергия" . ($this->isAdditional ? '(доп)' : '') . "</td><td>{$this->date}</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::toRubles($payedInBillCount) . "</td><td>" . CashHandler::toRubles($payedOutBillCount) . "</td><td>$leftToPay</td></tr>";
    }

    public function getPayedOutside(): float
    {
        $sum = 0;
        if(GrammarHandler::isMain($this->cottageNumber)){
            $items = Table_payed_power::findAll(['cottageId' => $this->cottageNumber, 'month' => $this->date]);
        }
        else{
            $items = Table_additional_payed_power::findAll(['cottageId' => (int) $this->cottageNumber, 'month' => $this->date]);
        }
        if($items !== null){
            foreach ($items as $item) {
                $sum += $item->summ;
            }
        }
        return $sum;
    }

    public function getPayedInside(): float
    {
        return 0;
    }

    public function getLeftToPay():float
    {
        return $this->totalAccrued - CashHandler::sumFromInt($this->getPayedOutside() + $this->getPayedInside());
    }
}