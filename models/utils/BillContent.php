<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\Cottage;
use app\models\DOMHandler;
use app\models\MembershipHandler;
use app\models\PowerHandler;
use app\models\SingleHandler;
use app\models\Table_payment_bills;
use app\models\TargetHandler;
use DOMElement;

class BillContent
{
    /**
     * @var BillPowerEntity[]
     */
    public array $powerEntities;
    /**
     * @var BillMembershipEntity[]
     */
    public array $membershipEntities;
    /**
     * @var BillTargetEntity[]
     */
    public array $targetEntities;
    /**
     * @var BillSingleEntity[]
     */
    public array $singleEntities;
    public bool $isAdditional = false;
    public int $billSum;
    private Table_payment_bills $info;

    public function __construct(Table_payment_bills $info)
    {
        $this->info = $info;
        $this->decompile($info->bill_content);
    }

    /**
     * Генерация строки для счёта
     * @return string
     */
    public function compile(): string
    {
        return '';
    }

    /**
     * Разбор строки bill_content из счёта на сущности
     * @param $text
     */
    public function decompile($text): void
    {
        $dom = new DOMHandler($text);
        $paymentItems = $dom->query('/payment');
        if ($paymentItems->length === 1) {
            /** @var DOMElement $paymentItem */
            $paymentItem = $paymentItems->item(0);
            $this->billSum = CashHandler::floatToInt($paymentItem->getAttribute('summ'));
        }
        // электроэнергия
        $paymentItems = $dom->query('/payment/power/month');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handlePowerEntity($paymentItem);
                $newItem->cottageNumber = $this->info->cottageNumber;
                $newItem->totalAccrued = PowerHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->powerEntities[] = $newItem;
            }
        }
        $paymentItems = $dom->query('/payment/additional_power/month');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handlePowerEntity($paymentItem);
                $newItem->isAdditional = true;
                $newItem->cottageNumber = $this->info->cottageNumber . '-a';
                $newItem->totalAccrued = PowerHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->powerEntities[] = $newItem;
            }
        }
        // членские взносы
        $paymentItems = $dom->query('/payment/membership/quarter');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleMembershipEntity($paymentItem);
                $newItem->cottageNumber = $this->info->cottageNumber;
                $newItem->totalAccrued = MembershipHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->membershipEntities[] = $newItem;
            }
        }
        $paymentItems = $dom->query('/payment/additional_membership/quarter');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleMembershipEntity($paymentItem);
                $newItem->isAdditional = true;
                $newItem->cottageNumber = $this->info->cottageNumber . '-a';
                $newItem->totalAccrued = MembershipHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->membershipEntities[] = $newItem;
            }
        }
        // целевые взносы
        $paymentItems = $dom->query('/payment/target/pay');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleTargetEntity($paymentItem);
                $newItem->cottageNumber = $this->info->cottageNumber;
                $newItem->totalAccrued = TargetHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->targetEntities[] = $newItem;
            }
        }
        $paymentItems = $dom->query('/payment/additional_target/pay');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleTargetEntity($paymentItem);
                $newItem->isAdditional = true;
                $newItem->cottageNumber = $this->info->cottageNumber . '-a';
                $newItem->totalAccrued = MembershipHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->targetEntities[] = $newItem;
            }
        }
        // разовые взносы
        $paymentItems = $dom->query('/payment/single/pay');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleSingleEntity($paymentItem);
                $newItem->cottageNumber = $this->info->cottageNumber;
                $newItem->totalAccrued = SingleHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->singleEntities[] = $newItem;
            }
        }
        $paymentItems = $dom->query('/payment/additional_single/pay');
        if ($paymentItems->length > 0) {
            foreach ($paymentItems as $paymentItem) {
                $newItem = $this->handleSingleEntity($paymentItem);
                $newItem->isAdditional = true;
                $newItem->cottageNumber = $this->info->cottageNumber . '-a';
                $newItem->totalAccrued = SingleHandler::getAmount(Cottage::getCottageByLiteral($newItem->cottageNumber), $newItem->date);
                $this->singleEntities[] = $newItem;
            }
        }
    }

    private function handlePowerEntity(DOMElement $paymentItem): BillPowerEntity
    {
        $newEntity = new BillPowerEntity();
        $newEntity->billId = $this->info->id;
        $newEntity->date = $paymentItem->getAttribute('date');
        $newEntity->sum = CashHandler::floatToInt($paymentItem->getAttribute('summ'));
        $newEntity->oldCounterData = $paymentItem->getAttribute('old-data');
        $newEntity->newCounterData = $paymentItem->getAttribute('new-data');
        $newEntity->powerSocialLimit = $paymentItem->getAttribute('powerLimit');
        $newEntity->powerSocialCost = CashHandler::floatToInt($paymentItem->getAttribute('powerCost'));
        $newEntity->powerRoutineCost = CashHandler::floatToInt($paymentItem->getAttribute('powerOvercost'));
        $newEntity->powerIndicationDifference = $paymentItem->getAttribute('difference');
        $newEntity->usedInLimit = $paymentItem->getAttribute('in-limit');
        $newEntity->usedOverLimit = $paymentItem->getAttribute('over-limit');
        $newEntity->costInLimit = CashHandler::floatToInt($paymentItem->getAttribute('in-limit-cost'));
        $newEntity->costOverLimit = CashHandler::floatToInt($paymentItem->getAttribute('over-limit-cost'));
        $newEntity->isItemCorrected = $paymentItem->getAttribute('corrected');
        return $newEntity;
    }

    private function handleMembershipEntity(DOMElement $paymentItem): BillMembershipEntity
    {
        $newEntity = new BillMembershipEntity();
        $newEntity->billId = $this->info->id;
        $newEntity->date = $paymentItem->getAttribute('date');
        $newEntity->sum = CashHandler::floatToInt($paymentItem->getAttribute('summ'));
        $newEntity->cottageSquare = $paymentItem->getAttribute('square');
        $newEntity->floatTariff = CashHandler::floatToInt($paymentItem->getAttribute('float'));
        $newEntity->floatCost = CashHandler::floatToInt($paymentItem->getAttribute('float-cost'));
        $newEntity->fixedCost = CashHandler::floatToInt($paymentItem->getAttribute('fixed'));
        $newEntity->payedBefore = CashHandler::floatToInt($paymentItem->getAttribute('prepayed'));
        return $newEntity;
    }

    private function handleTargetEntity(DOMElement $paymentItem): BillTargetEntity
    {
        $newEntity = new BillTargetEntity();
        $newEntity->billId = $this->info->id;
        $newEntity->date = $paymentItem->getAttribute('year');
        $newEntity->sum = CashHandler::floatToInt($paymentItem->getAttribute('summ'));
        $newEntity->totalSum = CashHandler::floatToInt($paymentItem->getAttribute('total-summ'));
        $newEntity->cottageSquare = $paymentItem->getAttribute('square');
        $newEntity->floatTariff = CashHandler::floatToInt($paymentItem->getAttribute('float'));
        $newEntity->floatCost = CashHandler::floatToInt($paymentItem->getAttribute('float-cost'));
        $newEntity->payedBefore = CashHandler::floatToInt($paymentItem->getAttribute('payed-before'));
        $newEntity->leftToPay = CashHandler::floatToInt($paymentItem->getAttribute('left-pay'));
        return $newEntity;
    }

    private function handleSingleEntity(DOMElement $paymentItem): BillSingleEntity
    {
        $newEntity = new BillSingleEntity();
        $newEntity->billId = $this->info->id;
        $newEntity->date = $paymentItem->getAttribute('timestamp');
        $newEntity->payDescription = $paymentItem->getAttribute('description');
        $newEntity->isPayed = $paymentItem->getAttribute('payed');
        $newEntity->payedBefore = CashHandler::floatToInt($paymentItem->getAttribute('payed-before'));
        $newEntity->sum = CashHandler::floatToInt($paymentItem->getAttribute('summ'));
        $newEntity->leftToPay = CashHandler::floatToInt($paymentItem->getAttribute('left-pay'));
        return $newEntity;
    }

    public function getTextContent(): string
    {
        $answer = '';
        if (!empty($this->powerEntities)) {
            foreach ($this->powerEntities as $powerEntity) {
                $answer .= $powerEntity->getTextContent();
            }
        }
        if (!empty($this->membershipEntities)) {
            foreach ($this->membershipEntities as $membershipEntity) {
                $answer .= $membershipEntity->getTextContent();
            }
        }
        if (!empty($this->targetEntities)) {
            foreach ($this->targetEntities as $targetEntity) {
                $answer .= $targetEntity->getTextContent();
            }
        }
        return $answer;
    }

    public function getRequiredSum()
    {
        // возьму сумму счёта, вычту из неё то, что было оплачено по этому счёту ранее и то,
        // что, что должно быть оплачено в этом счёте, но оплачено ранее

        $result = $this->info->totalSumm - $this->info->payedSumm - $this->info->depositUsed - $this->info->discount;
        $payedOutside = 0;
        if (!empty($this->powerEntities)) {
            foreach ($this->powerEntities as $powerEntity) {
                $shift = $powerEntity->totalAccrued - CashHandler::sumFromInt($powerEntity->sum);
                $payedOutside += $powerEntity->getPayedOutside() - $shift;
            }
        }
        if (!empty($this->membershipEntities)) {
            foreach ($this->membershipEntities as $membershipEntity) {
                $shift = $membershipEntity->totalAccrued - CashHandler::sumFromInt($membershipEntity->sum);
                $payedOutside += $membershipEntity->getPayedOutside() - $shift;
            }
        }
        if (!empty($this->targetEntities)) {
            foreach ($this->targetEntities as $targetEntity) {
                // скорректирую вычисления на случай, если в счёте не полная оплата периода
                $shift = $targetEntity->totalSum - $targetEntity->sum;
                $payedOutside += $targetEntity->getPayedOutside() - CashHandler::sumFromInt($shift);
            }
        }
        if (!empty($this->singleEntities)) {
            foreach ($this->singleEntities as $singleEntity) {
                $payedOutside += $singleEntity->getPayedOutside();
            }
        }
        $result -= $payedOutside;
        return $result;
    }
}