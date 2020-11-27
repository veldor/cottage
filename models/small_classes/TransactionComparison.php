<?php


namespace app\models\small_classes;


use app\models\Table_payment_bills;

class TransactionComparison
{
    public $billId;
    /**
     * @var Table_payment_bills
     */
    public $bill;
    public $transactionId;
    public $transactionSumm;
    public $billSumm;
    public $transactionCottageNumber;
    public $billCottageNumber;
    public $transactionFio;
    public $billFio;
    public string $payDate;
    public string $realPayDate = '0';
}