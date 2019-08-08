<?php /** @noinspection ALL */


namespace app\models;


use app\models\small_classes\TransactionComparison;
use yii\base\Model;

class TransactionsHandler extends Model
{
    const SCENARIO_CHANGE_DATE = 'change_date';

    public $id;
    public $double;
    public $payDate;
    public $bankDate;

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CHANGE_DATE => ['id', 'double', 'payDate', 'bankDate'],
        ];
    }


    public static function handle($billId, $transactionId)
    {
        // получу информацию о счёте и транзакции
        $billInfo = ComplexPayment::getBill($billId);
        if(empty($billInfo)){
            throw new ExceptionWithStatus("Счёт не найден", 6);
        }
        $transactionInfo = self::getTransaction($transactionId);
        if(empty($transactionInfo)){
            throw new ExceptionWithStatus("Транзакция не найдена", 7);
        }
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber);
        if($billInfo->isPayed){
            throw new ExceptionWithStatus("Счёт закрыт", 2);
        }
        if(!empty($transactionInfo->bounded_bill_id)){
            throw new ExceptionWithStatus("Транзакция уже связана со счётом " . $transactionInfo->bounded_bill_id, 3);
        }
        if($billInfo->isPartialPayed){
            throw new ExceptionWithStatus("Пока регистрирую только полные платежи", 4);
        }
        // проверю суммы счёта\транзакции
        $billSumm = CashHandler::toRubles($billInfo->totalSumm);
        $fromDeposit = CashHandler::toRubles($billInfo->depositUsed);
        $discount = CashHandler::toRubles($billInfo->discount);
        $fullSumm = CashHandler::toRubles($billSumm - $fromDeposit - $discount);
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        if($fullSumm > $transactionSumm){
            throw new ExceptionWithStatus("Частичная оплата", 5);
        }
        // верну сравнение транзакции и счёта
        $comparsion = new TransactionComparison();
        $comparsion->billId = $billId;
        $comparsion->transactionId = $transactionId;
        $comparsion->billCottageNumber = $billInfo->cottageNumber;
        $comparsion->transactionCottageNumber = $transactionInfo->account_number;
        $comparsion->billFio = $cottageInfo->cottageOwnerPersonals;
        $comparsion->transactionFio = $transactionInfo->fio;
        $comparsion->billSumm = $fullSumm;
        $comparsion->transactionSumm = $transactionInfo->payment_summ;
        return $comparsion;
    }

    /**
     * @param $transactionId
     * @return Table_bank_invoices
     */
    public static function getTransaction($transactionId)
    {
        return Table_bank_invoices::findOne($transactionId);
    }

    public function changeDate()
    {
        // найду транзакцию
        if(!$this->double){
            $transaction = Table_transactions::findOne($this->id);
        }
        else{
            $transaction = Table_transactions_double::findOne($this->id);
        }
        $transaction->payDate = TimeHandler::getCustomTimestamp($this->payDate);
        $transaction->bankDate = TimeHandler::getCustomTimestamp($this->bankDate);
        $transaction->save();
        return ['status' => 1, 'message' => 'Дата транзакции изменена'];
    }

    public function fill($id)
    {
        if(GrammarHandler::isMain($id)){
            $transaction = Table_transactions::findOne($id);
            $this->double = 0;
        }
        else{
            $transaction = Table_transactions_double::findOne(GrammarHandler::getNumber($id));
            $this->double = 1;
        }
        $this->id = $transaction->id;
        $this->payDate = $transaction->payDate;
        $this->bankDate = $transaction->bankDate;

    }
}