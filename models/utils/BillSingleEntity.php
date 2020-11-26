<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\GrammarHandler;
use app\models\Table_additional_payed_single;
use app\models\Table_payed_single;

class BillSingleEntity extends BillContentEntity
{
    public string $payDescription;
    public int $timestampOfCreate;
    public bool $isPayed;
    public int $payedBefore;
    public int $leftToPay;

    public function getTextContent(): string
    {
        // найду стоимость периода
        $payedInBillCount = $this->getPayedInside();
        $payedOutBillCount = $this->getPayedOutside();
        $leftToPay = CashHandler::toRubles(CashHandler::sumFromInt($this->sum) - $payedInBillCount - $payedOutBillCount);
        return "<tr><td>Целевые" . ($this->isAdditional ? '(доп)' : '') . "</td><td>{$this->date}</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::sumFromInt($this->sum) . "</td><td>" . CashHandler::toRubles($payedInBillCount) . "</td><td>" . CashHandler::toRubles($payedOutBillCount) . "</td><td>$leftToPay</td></tr>";
    }

    public function getPayedOutside(): float
    {
        $sum = 0;
        if(GrammarHandler::isMain($this->cottageNumber)){
            $items = Table_payed_single::find()->where([
                'cottageId' => $this->cottageNumber,
                'time' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        else{
            $items = Table_additional_payed_single::find()->where([
                'cottageId' => (int) $this->cottageNumber,
                'time' => $this->date
            ])->andWhere(['<>', 'billId',  $this->billId])->all();
        }
        if($items !== null){
            /** @var Table_payed_single $item */
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
            $items = Table_payed_single::findAll(['billId' => $this->billId, 'cottageId' => $this->cottageNumber, 'time' => $this->date]);
        }
        else{
            $items = Table_additional_payed_single::findAll(['billId' => $this->billId, 'cottageId' => (int) $this->cottageNumber, 'time' => $this->date]);
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