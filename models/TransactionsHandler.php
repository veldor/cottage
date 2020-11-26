<?php /** @noinspection ALL */


namespace app\models;


use app\models\interfaces\TransactionsInterface;
use app\models\small_classes\TransactionComparison;
use yii\base\Model;

class TransactionsHandler extends Model
{
    const SCENARIO_CHANGE_DATE = 'change_date';

    public $id;
    public $double;
    public $payDate;
    public $bankDate;

    /**
     * @param interfaces\CottageInterface $cottage участок
     * @param string $start начало периода
     * @param string $end конец периода
     * @return TransactionsInterface
     */
    public static function getTransactionsByPeriod(interfaces\CottageInterface $cottage, $start, $end)
    {
        if($cottage->isMain()){
            return Table_transactions::find()->where(['cottageNumber' => $cottage->getBaseCottageNumber()])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
        }
        return Table_transactions_double::find()->where(['cottageNumber' => $cottage->getBaseCottageNumber()])->andWhere(['>=', 'transactionDate', $start])->andWhere(['<=', 'transactionDate', $end])->all();
    }

    /**
     * @param string $cottageNumber
     * @param $item Table_payment_bills|Table_payment_bills_double
     */
    public static function getLastTransaction(string $cottageNumber, $item)
    {
        if(GrammarHandler::isMain($cottageNumber)){
            return Table_transactions::find()->where(['billId' => $item->id])->orderBy('bankDate')->one();
        }
        return Table_transactions_double::find()->where(['billId' => $item->id])->orderBy('bankDate')->one();
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CHANGE_DATE => ['id', 'double', 'payDate', 'bankDate'],
        ];
    }


    /**
     * @param $billId
     * @param $transactionId
     * @return TransactionComparison
     * @throws ExceptionWithStatus
     */
    public static function handle($billId, $transactionId)
    {
        // обработаю счёт с дополнительного участка
        $isDouble = ComplexPayment::isDouble($billId);

        // получу информацию о счёте и транзакции
        $billInfo = ComplexPayment::getBill($billId, $isDouble);
        if (empty($billInfo)) {
            throw new ExceptionWithStatus("Счёт не найден", 6);
        }
        $transactionInfo = self::getTransaction($transactionId);
        if (empty($transactionInfo)) {
            throw new ExceptionWithStatus("Транзакция не найдена", 7);
        }
        $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber, $isDouble);

        if ($billInfo->isPayed) {
            throw new ExceptionWithStatus("Счёт закрыт", 2);
        }
        if (!empty($transactionInfo->bounded_bill_id)) {
            throw new ExceptionWithStatus("Транзакция уже связана со счётом " . $transactionInfo->bounded_bill_id, 3);
        }

        // проверю, не осуществляется ли попытка преждевременной оплаты членских взносов
        if (MembershipHandler::noTimeForPay($billInfo, $cottageInfo)) {
            throw new ExceptionWithStatus("Попытка оплаты кватрала членских платежей вне очереди ", 4);
        }

        // проверю суммы счёта\транзакции
        $billSumm = CashHandler::toRubles($billInfo->totalSumm);
        $fromDeposit = CashHandler::toRubles($billInfo->depositUsed);
        $discount = CashHandler::toRubles($billInfo->discount);
        if (!empty($billInfo->payedSumm)) {
            $payedBefore = CashHandler::toRubles($billInfo->payedSumm);
        } else {
            $payedBefore = 0;
        }
        $fullSumm = CashHandler::toRubles($billSumm - $fromDeposit - $discount - $payedBefore);
        $transactionSumm = CashHandler::toRubles($transactionInfo->payment_summ);
        // верну сравнение транзакции и счёта
        $comparsion = new TransactionComparison();
        $comparsion->billId = $billId;
        $comparsion->bill = $billInfo;
        $comparsion->transactionId = $transactionId;
        if ($isDouble)
            $comparsion->billCottageNumber = $billInfo->cottageNumber . '-A';
        else
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
        if (!$this->double) {
            $transaction = Table_transactions::findOne($this->id);
        } else {
            $transaction = Table_transactions_double::findOne($this->id);
        }
        $transaction->payDate = TimeHandler::getCustomTimestamp($this->payDate);
        $transaction->bankDate = TimeHandler::getCustomTimestamp($this->bankDate);
        $transaction->save();
        return ['status' => 1, 'message' => 'Дата транзакции изменена'];
    }

    public function fill($id)
    {
        if (GrammarHandler::isMain($id)) {
            $transaction = Table_transactions::findOne($id);
            $this->double = 0;
        } else {
            $transaction = Table_transactions_double::findOne(GrammarHandler::getNumber($id));
            $this->double = 1;
        }
        $this->id = $transaction->id;
        $this->payDate = $transaction->payDate;
        $this->bankDate = $transaction->bankDate;

    }
}