<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 15.12.2018
 * Time: 13:51
 */

namespace app\models;


use app\models\interfaces\CottageInterface;
use app\models\selections\SingleDebt;
use app\models\utils\DbTransaction;
use app\validators\CashValidator;
use DOMElement;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class SingleHandler extends Model
{
    public $cottageNumber;
    public $summ;
    public $description;
    public $double;
    public $payedBefore;
    public $payId;

    const SCENARIO_NEW_DUTY = 'new_duty';
    const SCENARIO_EDIT = 'edit';

    public static function changePayTime(int $id, $timestamp)
    {
        // найду все платежи данного счёта
        $pays = Table_payed_single::find()->where(['billId' => $id])->all();
        if (!empty($pays)) {
            foreach ($pays as $pay) {
                /** @var Table_payed_single $pay */
                $pay->paymentDate = $timestamp;
                $pay->save();
            }
        }
    }

    public static function removeDebtByName(Table_cottages $cottageInfo, string $string)
    {
        $existedPays = self::getDebtReport($cottageInfo);
        if(!empty($existedPays)){
            foreach ($existedPays as $existedPay) {
                if(strripos($existedPay->description, $string) > -1){
                    // удалю платёж
                    $dom = new DOMHandler($cottageInfo->singlePaysDuty);
                    /** @var DOMElement $item */
                    $item = $dom->query('//singlePayment[@time=' . $existedPay->time . ']')->item(0);
                    $cottageInfo->singleDebt -= CashHandler::toRubles($item->getAttribute('summ'));
                    $dom->deleteElem($item);
                    $cottageInfo->singlePaysDuty = $dom->save();
                    $cottageInfo->save();
                }
            }
        }
    }

    public static function getDebtAmount(CottageInterface $globalInfo)
    {
        $duties = self::getDebtReport($globalInfo);
        $debt = 0;
        if(!empty($duties)){
            foreach ($duties as $duty) {
                $debt += $duty->amount - $duty->partialPayed;
            }
        }
        return $debt;
    }

    public static function getAmount(Table_cottages $cottage, string $date)
    {
        // получу все задолженности
        $pays = self::getDebtReport($cottage);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                if((int)$pay->time === (int) $date){
                    return $pay->amount;
                }
            }
        }
        return 0;
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_NEW_DUTY => ['cottageNumber', 'summ', 'description', 'double'],
            self::SCENARIO_EDIT => ['cottageNumber', 'summ', 'description', 'double', 'payId'],
        ];
    }

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        if (!empty($config['attributes'])) {
            $this->cottageNumber = $config['attributes']['cottageNumber'];
            $this->double = $config['attributes']['double'];
        }
    }

    public function attributeLabels(): array
    {
        return [
            'summ' => 'Сумма платежа',
            'description' => 'Назначение платежа',
        ];
    }

    public function rules(): array
    {
        return [
            [['cottageNumber', 'summ'], 'required', 'on' => self::SCENARIO_NEW_DUTY],
            ['summ', CashValidator::class],
            ['description', 'string', 'max' => 500],
        ];
    }


    public static function delete($cottageNumber, $id, $double = false)
    {
        if ($double) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
            if (Pay::getUnpayedBillId($cottageInfo->masterId)) {
                return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
            }
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
            // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
            if (Pay::getUnpayedBillId($cottageInfo->cottageNumber)) {
                return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
            }
        }
        $existedPays = self::getDebtReport($cottageInfo);
        if ($pay = $existedPays[$id]) {
            if ($pay['payed'] > 0) {
                return ['status' => 4, 'message' => 'Платёж частично оплачен- удаление невозможно'];
            }
            $dom = new DOMHandler($cottageInfo->singlePaysDuty);
            /** @var DOMElement $item */
            $item = $dom->query('//singlePayment[@time=' . $id . ']')->item(0);
            $cottageInfo->singleDebt -= CashHandler::toRubles($item->getAttribute('summ'));
            $dom->deleteElem($item);
            $cottageInfo->singlePaysDuty = $dom->save();
            $cottageInfo->save();
            return ['status' => 1];
        }
        return ['status' => 3, 'message' => 'Платёж с данным идентификатором не найден'];
    }

    public static function edit($cottageNumber, $id, $double = false)
    {

        if ($double) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
            // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
            if (Pay::getUnpayedBillId($cottageInfo->masterId)) {
                return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
            }
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
            // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
            if (Pay::getUnpayedBillId($cottageInfo->cottageNumber)) {
                return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
            }
        }
        $existedPays = self::getDebtReport($cottageInfo);
        if ($pay = $existedPays[$id]) {
            if ($pay['payed'] > 0) {
                return ['status' => 4, 'message' => 'Платёж частично оплачен- удаление невозможно'];
            }
            $dom = new DOMHandler($cottageInfo->singlePaysDuty);
            $item = $dom->query('//singlePayment[@time=' . $id . ']')->item(0);
            if (!empty($item)) {
                return ['item' => $item];
            }
        }
        return ['status' => 3, 'message' => 'Платёж с данным идентификатором не найден'];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function insert(): array
    {
        $transaction = new DbTransaction();
        try {
            $description = urlencode($this->description);
            $time = time();
            if ($this->double) {
                $cottage = AdditionalCottage::getCottage($this->cottageNumber);
            } else {
                $cottage = Cottage::getCottageInfo($this->cottageNumber);
            }
            // добавлю сведения о платеже в поле;
            if (!empty($cottage->singlePaysDuty)) {
                // если уже есть платежи- добавлю ещё один
                $dom = new DOMHandler($cottage->singlePaysDuty);
                $elem = $dom->createElement('singlePayment');
                $elem->setAttribute('time', $time);
                $elem->setAttribute('payed', '0');
                $elem->setAttribute('summ', $this->summ);
                $elem->setAttribute('description', $description);
                $dom->appendToRoot($elem);
                $data = $dom->save();
                $cottage->singlePaysDuty = $data;
            } else {
                // если платежей нет- создам
                $content = "<singlePayments><singlePayment time='{$time}' payed='0' summ='{$this->summ}' description='{$description}'/></singlePayments>";
                $cottage->singlePaysDuty = $content;
            }
            $cottage->singleDebt += $this->summ;
            $cottage->save();
            $transaction->commitTransaction();
            return ['status' => 1];
        } catch (Exception $e) {
            $transaction->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param $cottage int|string|Table_cottages
     * @param bool $double
     * @return SingleDebt[]
     */
    public static function getDebtReport($cottage, $double = false): array
    {
        $answer = [];
        if ($double) {
            if (!is_object($cottage)) {
                $cottage = AdditionalCottage::getCottage($cottage);
            }
        } else {
            if (!is_object($cottage)) {
                $cottage = Cottage::getCottageInfo($cottage);
            }
        }
        if (!empty($cottage->singlePaysDuty)) {
            $dom = new DOMHandler($cottage->singlePaysDuty);
            $answer = [];
            $pays = $dom->query('/singlePayments/singlePayment');
            /**
             * @var  $pay DOMElement
             */
            foreach ($pays as $pay) {
                $answerItem = new SingleDebt();
                $answerItem->id = $pay->getAttribute('id');
                $answerItem->time = $pay->getAttribute('time');
                $answerItem->amount = CashHandler::toRubles($pay->getAttribute('summ'));
                $answerItem->partialPayed = CashHandler::toRubles($pay->getAttribute('payed'));
                $answerItem->description = urldecode($pay->getAttribute('description'));
                $answer[] = $answerItem;
            }
        }
        return $answer;
    }

    public static function createPayment($cottageInfo, $singles): array
    {
        $summ = 0;
        $answer = '';
        $debt = self::getDebtReport($cottageInfo);
        foreach ($singles as $key => $value) {
            if (!empty($value)) {
                $pay = CashHandler::toRubles($value['value']);
                if ($value > 0) {
                    // найду подходящий долг
                    foreach ($debt as $item) {
                        if ($item->time == $key) {
                            $targetDebt = $item;
                        }
                    }
                    if (!empty($targetDebt)) {
                        if ($pay > CashHandler::toRubles($targetDebt->amount - $targetDebt->partialPayed)) {
                            throw new InvalidArgumentException('Сумма платежа(' . $pay . ') превышает сумму задолженности ' . CashHandler::toRubles($targetDebt->amount - $targetDebt->partialPayed));
                        }
                        $summ += $pay;
                        $leftPay = CashHandler::toRubles($targetDebt->amount) - CashHandler::toRubles($targetDebt->partialPayed);
                        $answer .= "<pay description='" . urlencode($targetDebt->description) . "' timestamp='$key' payed='{$targetDebt->partialPayed}' summ='{$pay}' payed-before='{$targetDebt->partialPayed}' left-pay='{$leftPay}'/>";

                    } else {
                        throw new InvalidArgumentException('Счёт не найден в списке задолженностей');
                    }
                }

            }
        }
        if ($summ > 0) {
            $answer = "<single cost='{$summ}'>" . $answer . '</single>';
        } else {
            $answer = '';
        }
        return ['text' => $answer, 'summ' => $summ];
    }

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payments
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function registerPayment($cottageInfo, $billInfo, $payments, $transaction, $additional = false)
    {
        $dom = DOMHandler::getDom($cottageInfo->singlePaysDuty);
        $xpath = DOMHandler::getXpath($dom);
        // зарегистрирую платежи
        foreach ($payments['values'] as $payment) {
            $summ = CashHandler::toRubles($payment['summ']);
            /** @var DOMElement $pay */
            $pay = $xpath->query("/singlePayments/singlePayment[@time='{$payment['timestamp']}']")->item(0);
            $attrs = DOMHandler::getElemAttributes($pay);
            $payed = CashHandler::toRubles($attrs['payed']);
            $fullSumm = CashHandler::toRubles($attrs['summ']);
            if ($fullSumm === $summ + $payed) {
                // долг оплачен полностью, удаляю его из списка долгов
                $pay->parentNode->removeChild($pay);
            } else {
                $pay->setAttribute('payed', $summ + $payed);
            }
            $cottageInfo->singleDebt -= $summ;
            $cottageInfo->singlePaysDuty = DOMHandler::saveXML($dom);
            self::insertPayment($cottageInfo, $billInfo, $payment, $transaction, $additional);
        }
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $bill Table_payment_bills|Table_payment_bills_double
     * @param $date
     * @param $summ
     * @param $transaction Table_transactions|Table_transactions_double
     */
    public static function insertSinglePayment($cottageInfo, $bill, $date, $summ, $transaction)
    {
        $main = Cottage::isMain($cottageInfo);
        // получу информацию о задолженностях
        $dom = new DOMHandler($cottageInfo->singlePaysDuty);
        // найду информацию о платеже
        /** @var DOMElement $pay */
        $pay = $dom->query("/singlePayments/singlePayment[@time='{$date}']")->item(0);
        $attrs = DOMHandler::getElemAttributes($pay);
        $payed = CashHandler::toRubles($attrs['payed']);
        $fullSumm = CashHandler::toRubles($attrs['summ']);
        if ($fullSumm === $summ + $payed) {
            // платёж оплачен полностью, удаляю его из списка долгов
            $pay->parentNode->removeChild($pay);
        } else {
            $pay->setAttribute('payed', $summ + $payed);
        }
        $cottageInfo->singleDebt -= $summ;
        $cottageInfo->singlePaysDuty = $dom->save();
        // зарегистрирую платёж
        if ($main) {
            $write = new Table_payed_single();
            $write->cottageId = $cottageInfo->cottageNumber;
        } else {
            $write = new Table_additional_payed_single();
            $write->cottageId = $cottageInfo->masterId;
        }
        $write->billId = $bill->id;
        $write->time = $date;
        $write->summ = $summ;
        $write->paymentDate = $transaction->bankDate;
        $write->transactionId = $transaction->id;
        $write->save();
    }

    /**
     * @param $cottageInfo
     * @param $billInfo
     * @param $payment
     * @param $transaction Table_transactions
     * @param bool $additional
     */
    public static function insertPayment($cottageInfo, $billInfo, $payment, $transaction, $additional = false)
    {
        $summ = CashHandler::toRubles($payment['summ']);
        if ($summ > 0) {
            if ($additional) {
                $write = new Table_additional_payed_single();
                $write->cottageId = $cottageInfo->masterId;
            } else {
                $write = new Table_payed_single();
                $write->cottageId = $cottageInfo->cottageNumber;
            }
            $write->billId = $billInfo->id;
            $write->time = $payment['timestamp'];
            $write->transactionId = $transaction->id;
            $write->summ = $summ;
            $write->paymentDate = $transaction->bankDate;
            $write->save();
        }
    }

    public function fill($cottageNumber, $id, $double = false)
    {
        // стандартные проверки
        $cottageInfo = Cottages::getCottage($cottageNumber, $double);
        // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
        if ($double) {
            if (Pay::getUnpayedBillId($cottageInfo->masterId)) {
                throw new ExceptionWithStatus('Сначала завершите работу с неоплаченным счётом по участку', '2');
            }
        } else {
            if (Pay::getUnpayedBillId($cottageInfo->cottageNumber)) {
                throw new ExceptionWithStatus('Сначала завершите работу с неоплаченным счётом по участку', '2');
            }
        }
        $existedPays = self::getDebtReport($cottageInfo);
        if ($pay = $existedPays[$id]) {
            $this->payedBefore = CashHandler::toRubles($pay['payed']);
            $this->summ = $pay['summ'];
            $this->cottageNumber = $cottageNumber;
            $this->description = $pay['description'];
            $this->payId = $id;
            $this->double = $double;
            return;
        }
        throw new ExceptionWithStatus('Платёж с данным идентификатором не найден', '3');
    }

    public function change()
    {
        // тут дополнительные проверки
        $cottageInfo = Cottages::getCottage($this->cottageNumber, $this->double);
        if (Pay::getUnpayedBillId($cottageInfo->cottageNumber)) {
            throw new ExceptionWithStatus('Сначала завершите работу с неоплаченным счётом по участку', '2');
        }
        $dom = new DOMHandler($cottageInfo->singlePaysDuty);
        /** @var DOMElement $item */
        $item = $dom->query('//singlePayment[@time=' . $this->payId . ']')->item(0);
        if (!empty($item)) {
            // проверю, новая сумма не должна превышать уже оплаченную сумму
            $payedBefore = DOMHandler::getFloatAttribute($item, 'payed');
            if ($this->summ < $payedBefore) {
                throw new ExceptionWithStatus('Новая сумма не может быть больше оплаченной ранее', 4);
            }
            $cottageInfo->singleDebt -= DOMHandler::getFloatAttribute($item, 'summ') - $this->summ;
            if ($this->summ === $payedBefore) {
                // считаю платёж полностью погашенным, удаляю его из истории
                $dom->deleteElem($item);
                $statusMessage = 'Так как новая сумма совпадает с ранее оплаченной- считаю платёж полностью погашенным.';
            } else {
                $item->setAttribute('summ', $this->summ);
                $item->setAttribute('description', $this->description);
                $statusMessage = 'Разовый платёж изменён';
            }
            $cottageInfo->singlePaysDuty = $dom->save();
            $cottageInfo->save();
            return ['status' => 1, 'message' => $statusMessage];
        }
        throw new ExceptionWithStatus('Платёж с данным идентификатором не найден', '3');
    }

    public static function handlePartialPayment($bill, $paymentInfo, $cottageInfo, $transaction)
    {
        foreach ($paymentInfo as $key => $value) {
            if ($value > 0) {
                self::insertSinglePayment($cottageInfo, $bill, $key, $value, $transaction);
            }
        }
    }

    /**
     * @param $billDom DOMHandler
     * @param $cottageInfo
     * @param $billId
     * @param $paymentTime
     */
    public static function finishPartialPayment($billDom, $cottageInfo, $billId, $paymentTime)
    {
        // добавлю оплаченную сумму в xml
        /** @var DOMElement $membershipContainer */
        $membershipContainer = $billDom->query('//single')->item(0);
        // проверю, не оплачивалась ли часть платежа ранее
        $payedBefore = CashHandler::toRubles(0 . $membershipContainer->getAttribute('payed'));
        // получу данные о полном счёте за электричество
        $pays = $billDom->query('//single/pay');
        /** @var DOMElement $pay */
        foreach ($pays as $pay) {
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($pay, 'summ');
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            // часть квартала оплачена заранее
            $date = $pay->getAttribute('timestamp');
            self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
        }
    }
}