<?php

namespace app\models;


use app\validators\CashValidator;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class ComplexPaymentDouble extends Model
{
    public $cottageNumber; // тип оплаты
    public $membershipPeriods = 0; // тип оплаты
    public $additionalMembershipPeriods = 0; // тип оплаты
    public $powerPeriods = 0; // тип оплаты
    public $additionalPowerPeriods = 0; // тип оплаты
    public $countedSumm = 0;
    public $target;
    public $additionalTarget;
    public $single;
    public $fromDeposit = 0;
    public $discount = 0;
    public $discountReason;
    public $noLimitPower;
    public $noLimitAdditionalPower;

    public $unpayed;
    public $cottageInfo;
    public $additionalCottageInfo;

    const SCENARIO_CREATE = 'create';


    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['cottageNumber', 'membershipPeriods', 'additionalMembershipPeriods', 'powerPeriods', 'additionalPowerPeriods', 'target', 'additionalTarget', 'single', 'countedSumm', 'fromDeposit', 'discount', 'discountReason', 'noLimitPower', 'noLimitAdditionalPower'],
        ];
    }


    public function rules(): array
    {
        return [
            [['cottageNumber'], 'required', 'on' => self::SCENARIO_CREATE],
            [['countedSumm', 'fromDeposit', 'discount'], CashValidator::class],
            [['cottageNumber', 'membershipPeriods', 'additionalMembershipPeriods', 'powerPeriods', 'additionalPowerPeriods'], 'integer', 'min' => 0],
            [['discount', 'fromDeposit'], 'checkModifiers'],
        ];
    }


    public function checkModifiers($attribute)
    {
        if ($this->discount + $this->fromDeposit > $this->countedSumm) {
            $this->addError($attribute, 'Неверная сумма скидки/депозита');
        }
    }


    public static function deleteBill($identificator): bool
    {
        $bill = Table_payment_bills_double::findOne($identificator);
        if ($bill !== null && $bill->isPayed === 0) {
            // удалить платёж можно только если он ещё не оплачен
            // отправлю письмо с извещением, если есть куда
            $bill->delete();
//                if(!empty($cottageInfo->cottageOwnerEmail) || !empty($cottageInfo->cottageContacterEmail)){
//                    $dutyText = Filling::getCottageDutyText($cottageInfo);
//                    $mailText = "<div style='text-align: center'><h1>Добрый день!</h1></div><div><h2>Выставленный счёт №{$bill->id} отменён.</h2></div><div style='text-align: center'>{$dutyText}<h3>Депозит участка</h3><p>Средств на депозите: <b style='color: #3e8f3e'>{$cottageInfo->deposit}</b> &#8381;<br/><b style='color: #3e8f3e'>Средства, находящиеся на депозите вы можете использовать для оплаты любых услуг садоводства.</b></p></div>";
//                    //return Notifier::sendNotification($cottageInfo, 'Счёт за услуги отменён.', $mailText);
//                    return true;
            return true;
        }
        throw new InvalidArgumentException('Платёж не существует или оплачен');
    }

    public static function checkUnpayed($cottageNumber)
    {
        $find = Table_payment_bills_double::findOne(['cottageNumber' => $cottageNumber, 'isPayed' => '0']);
        if (!empty($find)) {
            return $find->id;
        }
        return false;
    }

    /**
     * @return array
     * @throws \yii\base\ErrorException
     */
    public function save(): array
    {
        if (self::checkUnpayed($this->cottageNumber)) {
            throw new ErrorException('Имеется неоплченный счёт. Создание нового невозможно! Оплатите или отмените предыдущий!');
        }
        $fromDeposit = $this->fromDeposit;
        $discount = $this->discount;
        $this->cottageInfo = AdditionalCottage::getCottage($this->cottageNumber);
        if ($fromDeposit > $this->cottageInfo->deposit) {
            throw new InvalidArgumentException('Превышена сумма доступного депозита!');
        }
        $totalCost = 0;
        $power = '';
        $membership = '';
        $target = '';
        $single = '';
        // ЭЛЕКТРОЭНЕРГИЯ========================================================================================================
        if ($this->powerPeriods > 0) {
            $powerData = PowerHandler::createPayment($this->cottageInfo, $this->powerPeriods, $this->noLimitPower, true);
            $power = $powerData['text'];
            $totalCost += $powerData['summ'];
        }
        if ($this->membershipPeriods > 0) {
            $membershipData = MembershipHandler::createPayment($this->cottageInfo, $this->membershipPeriods, true);
            $membership = $membershipData['text'];
            $totalCost += $membershipData['summ'];
        }
        if (!empty($this->target)) {
            $targetData = TargetHandler::createPayment($this->cottageInfo, $this->target, true);
            $target = $targetData['text'];
            $totalCost += $targetData['summ'];
        }
        if (!empty($this->single)) {
            $singleData = SingleHandler::createPayment($this->cottageInfo, $this->single);
            $single = $singleData['text'];
            $totalCost += $singleData['summ'];
        }
        $totalCost = CashHandler::rublesRound($totalCost);
        $content = "<payment summ='{$totalCost}'>{$power}{$membership}{$single}{$target}</payment>";
        if ($fromDeposit > $totalCost || $discount > $totalCost || ($fromDeposit + $discount) > $totalCost) {
            throw new InvalidArgumentException('Сумма скидки и оплаты с депозита не должна превышать сумму платежа');
        }
        // сохраняю платёж
        $bill = new Table_payment_bills_double();
        $bill->cottageNumber = $this->cottageNumber;
        $bill->bill_content = $content;
        $bill->isPayed = 0;
        $bill->creationTime = time();
        $bill->totalSumm = $totalCost;
        $bill->depositUsed = $this->fromDeposit;
        $bill->discount = $this->discount;
        $bill->discountReason = urlencode($this->discountReason);
        $bill->save();
        // отправлю письмо о созданном счёте
//            if($this->cottageInfo->cottageOwnerEmail || $this->cottageInfo->cottageContacterEmail){
//                $payDetails = Filling::getPaymentDetails($bill);
//                $dutyText = Filling::getCottageDutyText($this->cottageInfo);
//                $mailBody = "<div style='text-align: center'><h1>Добрый день!</h1></div><div><p>Добрый день. Вам выставлен счёт за услуги садоводства. Вы можете оплатить его наличными у бухгалтера. Контактная информация в конце письма.";
//                $mailBody .= $payDetails;
//                $mailBody .= $dutyText;
//               // $result = Notifier::sendNotification($this->cottageInfo, 'Выставлен новый счёт за услуги.', $mailBody, true);
//                $result['billId'] = $bill->id;
//                return $result;
//            }
        return ['status' => 1, 'billId' => $bill->id];
    }

	/**
	 * @param $bill int|string|Table_payment_bills
	 * @return array
	 */
	public static function getBillInfo($bill): array
    {
        // погнали, получу сведения о платеже
	    if(is_string($bill)){
		    $bill = self::getBill($bill);
	    }
        $cottageInfo = AdditionalCottage::getCottage($bill->cottageNumber);
        // Получу сведения об участке
        // если платёж совершён, получаю сведения о транзакции
        $payInfo = [];
        if ($bill->isPayed === 1) {
            $payInfo = Table_transactions_double::findOne(['billId' => $bill->id]);
        }
        $dom = DOMHandler::getDom($bill->bill_content);
        $xpath = DOMHandler::getXpath($dom);
        $content = [];
        $content['additionalPower'] = self::getPaymentPart($dom, $xpath, 'additional_power', 'month');
        $content['additionalMembership'] = self::getPaymentPart($dom, $xpath, 'additional_membership', 'quarter');
        $content['additionalTarget'] = self::getPaymentPart($dom, $xpath, 'additional_target', 'pay');
        $content['single'] = self::getPaymentPart($dom, $xpath, 'single', 'pay');
        $summToPay = $bill->totalSumm - $bill->discount - $bill->depositUsed;
        return ['billInfo' => $bill, 'cottageInfo' => $cottageInfo, 'payInfo' => $payInfo, 'payedSumm' => $bill->payedSumm, 'summToPay' => $summToPay, 'paymentContent' => $content];
    }

    /**
     * @param $cottageNumber int|string
     * @return array
     */
    public static function getBills($cottageNumber): array
    {
        // найду список платежей по данному участку, отсортированный по времени выставления
        $list = Table_payment_bills_double::find()->where(['cottageNumber' => $cottageNumber])->orderBy('creationTime DESC')->all();
        $paymentsInfo = [];
        if (!empty($list)) {
            foreach ($list as $item) {
            	if(!empty($item->paymentTime)){
            		$pt = TimeHandler::getDateFromTimestamp($item->paymentTime);
	            }
	            else{
            		$pt = '';
	            }
                $paymentsInfo[] = ['id' => $item->id, 'isPayed' => $item->isPayed, 'creationTime' => TimeHandler::getDatetimeFromTimestamp($item->creationTime), 'paymentTime' =>  $pt, 'summ' => $item->totalSumm, 'payed-summ' => $item->payedSumm];
            }
            return $paymentsInfo;
        }
        return [];
    }

    /**
     * @return bool
     */
    public function fill(): bool
    {
        return (bool)$this->cottageNumber && $this->cottageInfo = Table_cottages::findOne($this->cottageNumber);
    }

    public function loadInfo($cottageNumber)
    {
        $unpayed = [];
        $this->cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        $this->cottageNumber = $cottageNumber;
        $unpayed['square'] = $this->cottageInfo->cottageSquare;
        $unpayed['powerDuty'] = PowerHandler::getDebtReport($this->cottageInfo, true);
        $unpayed['membershipDuty'] = MembershipHandler::getDebt($this->cottageInfo, true);
        $unpayed['targetDuty'] = TargetHandler::getDebt($this->cottageInfo, true);
        $unpayed['singleDuty'] = SingleHandler::getDebtReport($this->cottageInfo);
        $this->unpayed = $unpayed;
    }

    /**
     * @param $identificator int|string
     * @return Table_payment_bills_double
     */
    public static function getBill($identificator): Table_payment_bills_double
    {
        $info = Table_payment_bills_double::findOne($identificator);
        if ($info !== null) {
            return $info;
        }
        throw new InvalidArgumentException('Платежа с данным идентификатором не существует');
    }

    /**
     * @param $dom \DOMDocument
     * @param $xpath \DOMXPath
     * @param $paymentPartName string
     * @param $paymentPartValues string
     * @return array
     */
    public static function getPaymentPart($dom, $xpath, $paymentPartName, $paymentPartValues): array
    {
        $answer = [];
        $tag = $dom->getElementsByTagName($paymentPartName);
        if($tag->length === 1){
            /**
             * @var $value \DOMElement
             */
            $values = $xpath->query("//$paymentPartName/$paymentPartValues");
            if($values->length > 0){
                foreach ($values as $value) {
                        $answer['values'][] = DOMHandler::getElemAttributes($value);
                }
	            $answer['summ'] = CashHandler::toRubles($tag->item(0)->getAttribute('cost'));
            }
        }
        return $answer;
    }
}