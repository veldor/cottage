<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\Cottage;
use app\models\GrammarHandler;
use app\models\MembershipHandler;
use app\models\Table_additional_payed_membership;
use app\models\Table_payed_membership;

class BillMembershipEntity extends BillContentEntity
{
    public int $cottageSquare;
    public int $floatCost;
    public int $floatTariff;
    public int $fixedCost;
    public int $payedBefore;

    public function getTextContent(): string
    {
        // найду стоимость периода
        $periodCost = MembershipHandler::getAmount(Cottage::getCottageByLiteral($this->cottageNumber), $this->date);
        $payedInBillCount = $this->getPayedInside();
        $payedOutBillCount = $this->getPayedOutside();
        $leftToPay = CashHandler::toRubles(CashHandler::toRubles($periodCost) - CashHandler::toRubles($payedInBillCount) - CashHandler::toRubles($payedOutBillCount));
        return "<tr><td>Членские" . ($this->isAdditional ? '(доп)' : '') . "</td><td>{$this->date}</td><td>{$periodCost}</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::toRubles($payedInBillCount) . "</td><td>" . CashHandler::toRubles($payedOutBillCount) . "</td><td>$leftToPay</td></tr>";
    }

    public function getPayedOutside(): float
    {
        $sum = 0;
        if(GrammarHandler::isMain($this->cottageNumber)){
            $items = Table_payed_membership::find()->where([
                'cottageId' => $this->cottageNumber,
                'quarter' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        else{
            $items = Table_additional_payed_membership::find()->where([
                'cottageId' => (int) $this->cottageNumber,
                'quarter' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        if($items !== null){
            /** @var Table_payed_membership $item */
            foreach ($items as $item) {
                $sum += $item->summ;
            }
        }
        return $sum;
    }

    public function getPayedInside(): float
    {
        $sum = 0;
        if(GrammarHandler::isMain($this->cottageNumber)){
            $items = Table_payed_membership::findAll(['billId' => $this->billId, 'cottageId' => $this->cottageNumber, 'quarter' => $this->date]);
        }
        else{
            $items = Table_additional_payed_membership::findAll(['billId' => $this->billId, 'cottageId' => (int) $this->cottageNumber, 'quarter' => $this->date]);
        }
        if($items !== null){
            foreach ($items as $item) {
                $sum += $item->summ;
            }
        }
        return $sum;
    }

    public function getLeftToPay():float
    {
        return CashHandler::toRubles($this->totalAccrued - ($this->getPayedOutside() + $this->getPayedInside()));
    }
}