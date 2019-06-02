<?php


namespace app\models;


use app\models\small_classes\TransactionComparison;

class TransactionsHandler
{

    public static function handle($billId, $transactionId)
    {
        // получу информацию о счёте и транзакции
        $billInfo = ComplexPayment::getBill($billId);
        if(empty($billInfo)){
            throw new ExceptionWithStatus("Счёт не найден", 6);
        }
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber);
        $transactionInfo = Table_bank_invoices::findOne($transactionId);
        /*if($billInfo->isPayed){
            throw new ExceptionWithStatus("Счёт закрыт", 2);
        }*/
        if(!empty($transactionInfo->bounded_bill_id)){
            throw new ExceptionWithStatus("Транзакция уже связана со счётом " . $transactionInfo->bounded_bill_id, 3);
        }
        if($billInfo->isPartialPayed){
            throw new ExceptionWithStatus("Пока регистрирую только полные платежи", 4);
        }
        // проверю суммы счёта\транзакции
        $billSumm = CashHandler::toRubles($billInfo->totalSumm);
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        if($billSumm > $transactionSumm){
            throw new ExceptionWithStatus("Частичная оплата", 5);
        }
        // верну сравнение транзакции и счёта
        $comparsion = new TransactionComparison();
        $comparsion->billCottageNumber = $billInfo->cottageNumber;
        $comparsion->transactionCottageNumber = $transactionInfo->account_number;
        $comparsion->billFio = $cottageInfo->cottageOwnerPersonals;
        $comparsion->transactionFio = $transactionInfo->fio;
        $comparsion->billSumm = $billInfo->totalSumm;
        $comparsion->transactionSumm = $transactionInfo->payment_summ;
        return $comparsion;
    }
}