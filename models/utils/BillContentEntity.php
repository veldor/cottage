<?php


namespace app\models\utils;


abstract class BillContentEntity
{
    public string $cottageNumber;
    public string $billId;
    public string $date;
    /**
     * Стоимость периода, обозначенная в счёте
     * @var int
     */
    public int $sum;
    /**
     * Начисления по периоду
     * @var int
     */
    public float $totalAccrued;
    public bool $isAdditional = false;
    public bool $isItemCorrected = false;

    abstract public function getTextContent(): string;
    abstract public function getPayedOutside(): float;
    abstract public function getPayedInside(): float;
    abstract public function getLeftToPay(): float;
}