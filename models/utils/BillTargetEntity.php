<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\Cottage;
use app\models\GrammarHandler;
use app\models\Table_additional_payed_target;
use app\models\Table_payed_target;
use app\models\TargetHandler;

class BillTargetEntity extends BillContentEntity
{
    public int $totalSum;
    public int $cottageSquare;
    public int $floatCost;
    public int $floatTariff;
    public int $fixedCost;
    public int $payedBefore;
    public int $leftToPay;

    public function getTextContent(): string
    {
        // найду стоимость периода
        $periodCost = TargetHandler::getAmount(Cottage::getCottageByLiteral($this->cottageNumber), $this->date);
        $payedInBillCount = $this->getPayedInside();
        $payedOutBillCount = $this->getPayedOutside();
        $shift = CashHandler::sumFromInt($this->totalSum - $this->sum);
        $leftToPay = CashHandler::toRubles($periodCost - $payedInBillCount - $payedOutBillCount);
        return "<tr><td>Целевые" . ($this->isAdditional ? '(доп)' : '') . "</td><td>{$this->date}</td><td>{$periodCost}</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::toRubles($payedInBillCount) . "</td><td>" . CashHandler::toRubles($payedOutBillCount) . "</td><td>$leftToPay</td></tr>";
    }

    public function getPayedOutside(): float
    {
        $sum = 0;
        if(GrammarHandler::isMain($this->cottageNumber)){
            $items = Table_payed_target::find()->where([
                'cottageId' => $this->cottageNumber,
                'year' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        else{
            $items = Table_additional_payed_target::find()->where([
                'cottageId' => (int) $this->cottageNumber,
                'year' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        if($items !== null){
            /** @var Table_payed_target $item */
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
            $items = Table_payed_target::findAll(['billId' => $this->billId, 'cottageId' => $this->cottageNumber, 'year' => $this->date]);
        }
        else{
            $items = Table_additional_payed_target::findAll(['billId' => $this->billId, 'cottageId' => (int) $this->cottageNumber, 'year' => $this->date]);
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
        return $this->totalAccrued - CashHandler::sumFromInt($this->getPayedOutside() + $this->getPayedInside());
    }
}