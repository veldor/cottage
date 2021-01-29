<?php


namespace app\models\selections;


class PenaltyItem
{

    private string $type;
    private string $period;
    private string $cottageNumber;
    private int $payUp;
    private int $payDate = 0;
    private float $arrears;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getPeriod(): string
    {
        return $this->period;
    }

    /**
     * @param string $period
     */
    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    /**
     * @return string
     */
    public function getCottageNumber(): string
    {
        return $this->cottageNumber;
    }

    /**
     * @param string $cottageNumber
     */
    public function setCottageNumber(string $cottageNumber): void
    {
        $this->cottageNumber = $cottageNumber;
    }

    /**
     * @return int
     */
    public function getPayUp(): int
    {
        return $this->payUp;
    }

    /**
     * @param int $payUp
     */
    public function setPayUp(int $payUp): void
    {
        $this->payUp = $payUp;
    }

    /**
     * @return int
     */
    public function getPayDate(): int
    {
        return $this->payDate;
    }

    /**
     * @param int $payDate
     */
    public function setPayDate(int $payDate): void
    {
        $this->payDate = $payDate;
    }

    /**
     * @return float
     */
    public function getArrears(): float
    {
        return $this->arrears;
    }

    /**
     * @param float $arrears
     */
    public function setArrears(float $arrears): void
    {
        $this->arrears = $arrears;
    }

    /**
     * @return float
     */
    public function getPayPerDay(): float
    {
        return $this->payPerDay;
    }

    /**
     * @param float $payPerDay
     */
    public function setPayPerDay(float $payPerDay): void
    {
        $this->payPerDay = $payPerDay;
    }

    /**
     * @return int
     */
    public function getDayDifference(): int
    {
        return $this->dayDifference;
    }

    /**
     * @param int $dayDifference
     */
    public function setDayDifference(int $dayDifference): void
    {
        $this->dayDifference = $dayDifference;
    }

    /**
     * @return float
     */
    public function getTotalAccrued(): float
    {
        return $this->totalAccrued;
    }

    /**
     * @param float $totalAccrued
     */
    public function setTotalAccrued(float $totalAccrued): void
    {
        $this->totalAccrued = $totalAccrued;
    }

    private float $payPerDay;
    private int $dayDifference;
    private float $totalAccrued;

    private bool $isRegistered = false;
    private bool $isActive = false;
    private bool $isLocked = false;

    /**
     * @return bool
     */
    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }

    /**
     * @param bool $isRegistered
     */
    public function setIsRegistered(bool $isRegistered): void
    {
        $this->isRegistered = $isRegistered;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    /**
     * @param bool $isLocked
     */
    public function setIsLocked(bool $isLocked): void
    {
        $this->isLocked = $isLocked;
    }

    /**
     * @return bool
     */
    public function isFullPayed(): bool
    {
        return $this->isFullPayed;
    }

    /**
     * @param bool $isFullPayed
     */
    public function setIsFullPayed(bool $isFullPayed): void
    {
        $this->isFullPayed = $isFullPayed;
    }

    /**
     * @return float
     */
    public function getLockedSum(): float
    {
        return $this->lockedSum;
    }

    /**
     * @param float $lockedSum
     */
    public function setLockedSum(float $lockedSum): void
    {
        $this->lockedSum = $lockedSum;
    }
    private bool $isFullPayed = false;
    private float $lockedSum;

    private float $thisFinePayedSum = 0;

    /**
     * @return float|int
     */
    public function getThisFinePayedSum()
    {
        return $this->thisFinePayedSum;
    }

    /**
     * @param float|int $thisFinePayedSum
     */
    public function setThisFinePayedSum($thisFinePayedSum): void
    {
        $this->thisFinePayedSum = $thisFinePayedSum;
    }

}