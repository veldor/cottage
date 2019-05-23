<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 15.12.2018
 * Time: 13:51
 */

namespace app\models;


use app\validators\CashValidator;
use DOMElement;
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
            ['double', 'boolean'],
        ];
    }


    public static function delete($cottageNumber, $id, $double = false)
    {
        if ($double) {
            $cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        }
        // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
        if (Pay::getUnpayedBillId($cottageInfo)) {
            return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
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
        } else {
            $cottageInfo = Cottage::getCottageInfo($cottageNumber);
        }
        // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
        if (Pay::getUnpayedBillId($cottageInfo)) {
            return ['status' => 2, 'message' => 'Сначала завершите работу с неоплаченным счётом по участку'];
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
     */
    public function insert(): array
    {
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
            $dom->dom->appendChild($elem);
            $data = $dom->save();
            $cottage->singlePaysDuty = $data;
        } else {
            // если платежей нет- создам
            $content = "<singlePayments><singlePayment time='{$time}' payed='0' summ='{$this->summ}' description='{$description}'/></singlePayments>";
            $cottage->singlePaysDuty = $content;
        }
        $cottage->singleDebt += $this->summ;
        $cottage->save();
        return ['status' => 1];
    }

    /**
     * @param $cottage int|string|Table_cottages
     * @param bool $double
     * @return array
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
            $pays = $dom->query('/singlePayments/singlePayment');
            /**
             * @var  $pay DOMElement
             */
            foreach ($pays as $pay) {
                $time = $pay->getAttribute('time');
                $answer[$time]['summ'] = CashHandler::toRubles($pay->getAttribute('summ'));
                $answer[$time]['payed'] = CashHandler::toRubles($pay->getAttribute('payed'));
                $answer[$time]['description'] = urldecode($pay->getAttribute('description'));
            }
        }
        return $answer;
    }

    public static function createPayment($cottageInfo, $singles): array
    {
        $answer = '';
        $summ = 0;
        $debt = self::getDebtReport($cottageInfo);
        foreach ($singles as $key => $value) {
            if (!empty($value)) {
                $pay = CashHandler::toRubles($value);
                if ($value > 0) {
                    if (!empty($debt[$key])) {
                        if ($pay > $debt[$key]['summ']) {
                            throw new InvalidArgumentException('Сумма платежа превышает сумму задолженности');
                        }
                        $summ += $pay;
                        $leftPay = CashHandler::toRubles($debt[$key]['summ']) - CashHandler::toRubles($debt[$key]['payed']);
                        $answer .= "<pay description='{$debt[$key]['description']}' timestamp='$key' payed='{$debt[$key]['payed']}' summ='{$pay}' payed-before='{$debt[$key]['payed']}' left-pay='{$leftPay}'/>";

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

    public static function registerPayment($cottageInfo, $billInfo, $payments, $additional = false)
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
            self::insertPayment($cottageInfo, $billInfo, $payment, $additional);
        }
    }

    public static function insertSinglePayment($cottageInfo, $billId, $date, $summ, $paymentTime)
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
        $write->billId = $billId;
        $write->time = $date;
        $write->summ = $summ;
        $write->paymentDate = $paymentTime;
        $write->save();
    }

    public static function insertPayment($cottageInfo, $billInfo, $payment, $additional = false)
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
            $write->summ =$summ;
            $write->paymentDate = $billInfo->paymentTime;
            $write->save();
        }
    }

    public function fill($cottageNumber, $id, $double = false)
    {
        // стандартные проверки
        $cottageInfo = Cottages::getCottage($cottageNumber, $double);
        // нужно проверить, что : нет неоплаченных платежей, платёж существует, платёж не был частично оплачен
        if (Pay::getUnpayedBillId($cottageInfo)) {
            throw new ExceptionWithStatus('Сначала завершите работу с неоплаченным счётом по участку', '2');
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
        if (Pay::getUnpayedBillId($cottageInfo)) {
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

    public static function handlePartialPayment(DOMHandler $billDom, float $paymentSumm, $cottageInfo, $billId, int $paymentTime)
    {
        /** @var DOMElement $PayContainer */
        $PayContainer = $billDom->query('//single')->item(0);
        // проверка на предыдущую неполную оплату категории
        $payedBefore = CashHandler::toRubles(0 . $PayContainer->getAttribute('payed'));
        // записываю сумму прошлой и текущей оплаты в xml
        $PayContainer->setAttribute('payed', $paymentSumm + $payedBefore);
        // получу данные о полном счёте за членские взносы
        $singlePays = $billDom->query('//single/pay');
        /** @var DOMElement $pay */
        foreach ($singlePays as $pay) {
            // переменная для хранения суммы, предоплаченной за платёж в прошлый раз
            $prepayed = 0;
            // получу сумму платежа
            $summ = DOMHandler::getFloatAttribute($pay, 'summ');
            $date = $pay->getAttribute('timestamp');
            // отсекаю платежи, полностью оплаченные в прошлый раз
            if ($summ <= $payedBefore) {
                $payedBefore -= $summ;
                continue;
            } elseif ($payedBefore > 0) {
                // это сумма, которая была предоплачена по кварталу в прошлый раз
                $prepayed = $payedBefore;
                $payedBefore = 0;
            }
            if ($summ - $prepayed <= $paymentSumm) {
                // денег хватает на полную оплату платежа. Плачу за него
                // сумма платежа учитывается с вычетом ранее оплаченного
                self::insertSinglePayment($cottageInfo, $billId, $date, $summ - $prepayed, $paymentTime);
                // корректирую сумму текущего платежа с учётом предыдущего
                $paymentSumm -= $summ - $prepayed;
            } elseif ($paymentSumm > 0) {
                // денег не хватает на полую оплату месяца, но ещё есть остаток- помечаю месяц как частично оплаченный
                self::insertSinglePayment($cottageInfo, $billId, $date, $paymentSumm, $paymentTime);
                break;
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