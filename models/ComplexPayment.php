<?php

namespace app\models;


use app\validators\CashValidator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class ComplexPayment extends Model
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
    public $double;

    public $unpayed;
    public $cottageInfo;
    public $additionalCottageInfo;

    const SCENARIO_CREATE = 'create';

    public static function getBankInvoice($identificator, $double = false)
    {
        $info = self::getBillInfo($identificator, $double);

        $purposeText = 'Оплата ';

        if (!empty($info['paymentContent']['power']) || !empty($info['paymentContent']['additionalPower'])) {
            $purposeText .= 'электроэнергии,';
        }
        if (!empty($info['paymentContent']['membership']) || !empty($info['paymentContent']['additionalMembership'])) {
            $purposeText .= ' членских взносов,';
        }
        if (!empty($info['paymentContent']['target']) || !empty($info['paymentContent']['additionalTarget'])) {
            $purposeText .= ' целевых взносов,';
        }
        if (!empty($info['paymentContent']['single'])) {
            $purposeText .= ' разовых взносов,';
        }

        $purposeText = substr($purposeText, 0, strlen($purposeText) - 1) . ' по сч. № ' . $info['billInfo']->id . ($double ? '-a' : '');

        $bankDetails = new BankDetails();
        $realSumm = CashHandler::rublesMath(CashHandler::toRubles($info['billInfo']->totalSumm) - CashHandler::toRubles($info['billInfo']->depositUsed) - CashHandler::toRubles($info['billInfo']->discount));
        $dividedSumm = CashHandler::dividedSumm($realSumm);
        $bankDetails->lastName = GrammarHandler::getPersonInitials($info['cottageInfo']->cottageOwnerPersonals);
        $bankDetails->purpose = $purposeText;
        $bankDetails->summ = $dividedSumm['rubles'] . $dividedSumm['cents'];
        $bankDetails->cottageNumber = $double ? $info['cottageInfo']->masterId . '-a' : $info['cottageInfo']->cottageNumber;
        return ['billInfo' => $info, 'bankInfo' => $bankDetails, 'double' => $double];

    }


    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['cottageNumber', 'membershipPeriods', 'additionalMembershipPeriods', 'powerPeriods', 'additionalPowerPeriods', 'target', 'additionalTarget', 'single', 'countedSumm', 'fromDeposit', 'discount', 'discountReason', 'noLimitPower', 'noLimitAdditionalPower', 'double'],
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


    public static function deleteBill($identificator, $double = false): bool
    {
        if ($double) {
            $bill = Table_payment_bills_double::findOne($identificator);
        } else {
            $bill = Table_payment_bills::findOne($identificator);
        }
        if ($bill === null) {
            throw new ExceptionWithStatus('Счёт не найден', 3);
        }
        if ($bill->isPayed) {
            throw new ExceptionWithStatus('Счёт оплачен, удаление невозможно', 4);
        }
        // удалить платёж можно только если он ещё не оплачен
        // отправлю письмо с извещением, если есть куда
        $bill->isPayed = 1;
        $bill->save();
//                if(!empty($cottageInfo->cottageOwnerEmail) || !empty($cottageInfo->cottageContacterEmail)){
//                    $dutyText = Filling::getCottageDutyText($cottageInfo);
//                    $mailText = "<div style='text-align: center'><h1>Добрый день!</h1></div><div><h2>Выставленный счёт №{$bill->id} отменён.</h2></div><div style='text-align: center'>{$dutyText}<h3>Депозит участка</h3><p>Средств на депозите: <b style='color: #3e8f3e'>{$cottageInfo->deposit}</b> &#8381;<br/><b style='color: #3e8f3e'>Средства, находящиеся на депозите вы можете использовать для оплаты любых услуг садоводства.</b></p></div>";
//                    //return Notifier::sendNotification($cottageInfo, 'Счёт за услуги отменён.', $mailText);
//                    return true;
        return true;
    }

    /**
     * @param $cottageNumber
     * @param bool $double
     * @return Table_payment_bills|Table_payment_bills_double|null
     */
    public static function checkUnpayed($cottageNumber, $double = false)
    {
        if ($double) {
            $find = Table_payment_bills_double::findOne(['cottageNumber' => $cottageNumber, 'isPayed' => '0']);
        } else {
            $find = Table_payment_bills::findOne(['cottageNumber' => $cottageNumber, 'isPayed' => '0']);
        }
        if (!empty($find)) {
            return $find;
        }
        return null;
    }

    /**
     * @return array
     * @throws ErrorException
     */
    public function save(): array
    {
        if (self::checkUnpayed($this->cottageNumber, $this->double)) {
            throw new ErrorException('Имеется неоплченный счёт. Создание нового невозможно! Оплатите или отмените предыдущий!');
        }
        $fromDeposit = $this->fromDeposit;
        $discount = $this->discount;
        if ($this->double) {
            $this->cottageInfo = AdditionalCottage::getCottage($this->cottageNumber);
        } else {
            $this->cottageInfo = Cottage::getCottageInfo($this->cottageNumber);
        }

        if (!empty($this->cottageInfo->haveAdditional)) {
            $this->additionalCottageInfo = AdditionalCottage::getCottage($this->cottageNumber);
        }
        if ($fromDeposit > $this->cottageInfo->deposit) {
            throw new InvalidArgumentException('Превышена сумма доступного депозита!');
        }
        $totalCost = 0;
        $power = '';
        $additionalPower = '';
        $membership = '';
        $additionalMembership = '';
        $target = '';
        $additionalTarget = '';
        $single = '';
        // ЭЛЕКТРОЭНЕРГИЯ========================================================================================================
        if ($this->powerPeriods > 0) {
            $powerData = PowerHandler::createPayment($this->cottageInfo, $this->powerPeriods, $this->noLimitPower, $this->double);
            $power = $powerData['text'];
            $totalCost += $powerData['summ'];
        }
        if ($this->additionalPowerPeriods > 0) {
            $additionalPowerData = PowerHandler::createPayment($this->additionalCottageInfo, $this->additionalPowerPeriods, $this->noLimitAdditionalPower, true);
            $additionalPower = $additionalPowerData['text'];
            $totalCost += $additionalPowerData['summ'];
        }
        if ($this->membershipPeriods > 0) {
            $membershipData = MembershipHandler::createPayment($this->cottageInfo, $this->membershipPeriods, $this->double);
            $membership = $membershipData['text'];
            $totalCost += $membershipData['summ'];
        }
        if ($this->additionalMembershipPeriods > 0) {
            $additionalMembershipData = MembershipHandler::createPayment($this->additionalCottageInfo, $this->additionalMembershipPeriods, true);
            $additionalMembership = $additionalMembershipData['text'];
            $totalCost += $additionalMembershipData['summ'];
        }
        if (!empty($this->target)) {
            $targetData = TargetHandler::createPayment($this->cottageInfo, $this->target, $this->double);
            $target = $targetData['text'];
            $totalCost += $targetData['summ'];
        }
        if (!empty($this->additionalTarget)) {
            $additionalTargetData = TargetHandler::createPayment($this->additionalCottageInfo, $this->additionalTarget, true);
            $additionalTarget = $additionalTargetData['text'];
            $totalCost += $additionalTargetData['summ'];
        }
        if (!empty($this->single)) {
            $singleData = SingleHandler::createPayment($this->cottageInfo, $this->single);
            $single = $singleData['text'];
            $totalCost += $singleData['summ'];
        }
        $totalCost = CashHandler::rublesRound($totalCost);
        $content = "<payment summ='{$totalCost}'>{$power}{$additionalPower}{$membership}{$additionalMembership}{$single}{$target}{$additionalTarget}</payment>";
        if ($fromDeposit > $totalCost || $discount > $totalCost || ($fromDeposit + $discount) > $totalCost) {
            throw new InvalidArgumentException('Сумма скидки и оплаты с депозита не должна превышать сумму платежа');
        }
        // сохраняю платёж
        if ($this->double) {
            $bill = new Table_payment_bills_double();
        } else {
            $bill = new Table_payment_bills();
        }
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
        return ['status' => 1, 'billId' => $bill->id, 'double' => (boolean)$this->double];
    }

    /**
     * @param $bill int|string|Table_payment_bills
     * @param bool $double
     * @return array
     */
    public static function getBillInfo($bill, $double = false): array
    {
        // погнали, получу сведения о платеже
        if (is_string($bill)) {
            $bill = self::getBill($bill, $double);
        }
        $cottageInfo = Cottage::getCottageInfo($bill->cottageNumber, $double);
        // Получу сведения об участке
        // если платёж совершён, получаю сведения о транзакции
        $payInfo = [];
        if ($bill->isPayed === 1) {
            if ($double) {
                $payInfo = Table_transactions_double::findOne(['billId' => $bill->id]);
            } else {
                $payInfo = Table_transactions::findOne(['billId' => $bill->id]);
            }

        }
        $dom = DOMHandler::getDom($bill->bill_content);
        $xpath = DOMHandler::getXpath($dom);
        $content = [];
        $content['power'] = self::getPaymentPart($dom, $xpath, 'power', 'month');
        $content['additionalPower'] = self::getPaymentPart($dom, $xpath, 'additional_power', 'month');
        $content['membership'] = self::getPaymentPart($dom, $xpath, 'membership', 'quarter');
        $content['additionalMembership'] = self::getPaymentPart($dom, $xpath, 'additional_membership', 'quarter');
        $content['target'] = self::getPaymentPart($dom, $xpath, 'target', 'pay');
        $content['additionalTarget'] = self::getPaymentPart($dom, $xpath, 'additional_target', 'pay');
        $content['single'] = self::getPaymentPart($dom, $xpath, 'single', 'pay');
        $summToPay = $bill->totalSumm - $bill->discount - $bill->depositUsed - $bill->payedSumm;
        return ['billInfo' => $bill, 'cottageInfo' => $cottageInfo, 'payInfo' => $payInfo, 'payedSumm' => $bill->payedSumm, 'summToPay' => $summToPay, 'paymentContent' => $content];
    }

    /**
     * @param $cottageNumber int|string
     * @param bool $double
     * @return array
     */
    public static function getBills($cottageNumber, $double = false): array
    {
        // найду список платежей по данному участку, отсортированный по времени выставления
        if ($double) {
            $list = Table_payment_bills_double::find()->where(['cottageNumber' => $cottageNumber])->orderBy('creationTime DESC')->all();
        } else {
            $list = Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber])->orderBy('creationTime DESC')->all();
        }

        $paymentsInfo = [];
        if (!empty($list)) {
            foreach ($list as $item) {
                if (!empty($item->paymentTime)) {
                    $pt = TimeHandler::getDateFromTimestamp($item->paymentTime);
                } else {
                    $pt = '';
                }
                if (!empty($item->payedSumm)) {
                    $payedSumm = CashHandler::rublesMath(CashHandler::toRubles($item->payedSumm) + CashHandler::toRubles($item->depositUsed) + CashHandler::toRubles($item->discount));
                }
                elseif(!empty($item->depositUsed) || !empty($item->discount)){
                    $payedSumm = CashHandler::rublesMath(CashHandler::toRubles($item->depositUsed) + CashHandler::toRubles($item->discount));
                }
                else {
                    $payedSumm = '';
                }
                $paymentsInfo[] = ['id' => $item->id, 'isPartialPayed' => $item->isPartialPayed, 'isPayed' => $item->isPayed, 'creationTime' => TimeHandler::getDatetimeFromTimestamp($item->creationTime), 'paymentTime' => $pt, 'summ' => CashHandler::toRubles($item->totalSumm), 'payed-summ' => $payedSumm, 'from-deposit' => $item->depositUsed, 'discount' => $item->discount];
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

    public function loadInfo($cottageNumber, $double = false)
    {
        $unpayed = [];
        if ($double) {
            $this->cottageInfo = AdditionalCottage::getCottage($cottageNumber);
        } else {
            $this->cottageInfo = Cottage::getCottageInfo($cottageNumber);
        }
        $this->double = $double;
        $this->cottageNumber = $cottageNumber;
        $unpayed['square'] = $this->cottageInfo->cottageSquare;
        $unpayed['powerDuty'] = PowerHandler::getDebtReport($this->cottageInfo, $double);
        $unpayed['membershipDuty'] = MembershipHandler::getDebt($this->cottageInfo, $double);
        $unpayed['targetDuty'] = TargetHandler::getDebt($this->cottageInfo, $double);
        $unpayed['singleDuty'] = SingleHandler::getDebtReport($this->cottageInfo, $double);
        if (!$this->double && $this->cottageInfo->haveAdditional) {
            $this->additionalCottageInfo = AdditionalCottage::getCottage($cottageNumber);
            if (!$this->additionalCottageInfo->hasDifferentOwner) {
                if ($this->additionalCottageInfo->isPower === 1) {
                    $unpayed['additionalPowerDuty'] = PowerHandler::getDebtReport($this->additionalCottageInfo, true);
                }
                $unpayed['additionalSquare'] = $this->additionalCottageInfo->cottageSquare;
                if ($this->additionalCottageInfo->isMembership === 1) {
                    $unpayed['additionalMembershipDuty'] = MembershipHandler::getDebt($this->additionalCottageInfo, true);
                }
                if ($this->additionalCottageInfo->isTarget === 1) {
                    $unpayed['additionalTargetDuty'] = TargetHandler::getDebt($this->additionalCottageInfo, true);
                }
            }
        }
        $this->unpayed = $unpayed;
    }

    /**
     * @param $identificator int|string
     * @param bool $double
     * @return Table_payment_bills
     */
    public static function getBill($identificator, $double = false)
    {
        if ($double) {
            $info = Table_payment_bills_double::findOne($identificator);
        } else {
            $info = Table_payment_bills::findOne($identificator);
        }
        if ($info !== null) {
            return $info;
        }
        throw new InvalidArgumentException('Платежа с данным идентификатором не существует');
    }

    /**
     * @param $dom DOMDocument
     * @param $xpath DOMXPath
     * @param $paymentPartName string
     * @param $paymentPartValues string
     * @return array
     */
    public static function getPaymentPart($dom, $xpath, $paymentPartName, $paymentPartValues): array
    {
        $answer = [];
        $tag = $dom->getElementsByTagName($paymentPartName);
        if ($tag->length === 1) {
            $tag = $tag->item(0);
            /** @var DOMElement $tag */
            /**
             * @var $value DOMElement
             */
            $values = $xpath->query("//$paymentPartName/$paymentPartValues");
            if ($values->length > 0) {
                foreach ($values as $value) {
                    $answer['values'][] = DOMHandler::getElemAttributes($value);
                }
                $payed = $tag->getAttribute('payed');
                if (!empty($payed)) {
                    $answer['payed'] = CashHandler::toRubles($payed);
                } else {
                    $answer['payed'] = 0;
                }
                $answer['summ'] = CashHandler::toRubles($summ = $tag->getAttribute('cost'));
            }
        }
        return $answer;
    }

    // выставлю счёт за все долги

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @throws ErrorException
     */
    public static function makeWholeBill($cottageInfo){
        $totalSumm = 0;
        $form = new ComplexPayment(['scenario' => ComplexPayment::SCENARIO_CREATE]);
        $main = Cottage::isMain($cottageInfo);
        $cottageNumber = null;
        if($main){
            $cottageNumber = $cottageInfo->cottageNumber;

        }
        else{
            $cottageNumber = $cottageInfo->masterId;
            $form->double = true;
        }
        $form->cottageNumber = $cottageNumber;

        // ЭЛЕКТРОЭНЕРГИЯ
        $additionalCottage = null;
        if($main && $cottageInfo->haveAdditional){
            $additionalCottage = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
        }

        $power = PowerHandler::getDebtReport($cottageInfo, !$main);

        if(!!$power){
            foreach ($power as $item) {
                $totalSumm += CashHandler::toRubles($item['totalPay']);
            }
            $form->powerPeriods = count($power);
        }
        $additionalPower = null;
        if($additionalCottage){
            if(!$additionalCottage->hasDifferentOwner){
                $additionalPower = PowerHandler::getDebtReport($additionalCottage, true);
            }
        }
        if(!!$additionalPower){
            foreach ($additionalPower as $item) {
                $totalSumm += CashHandler::toRubles($item['totalPay']);
            }
            $form->additionalPowerPeriods = count($additionalPower);
        }
        // ЧЛЕНСКИЕ ВЗНОСЫ
        $membership = MembershipHandler::getDebt($cottageInfo, !$main);
        if(!!$membership){
            foreach ($membership as $item) {
                $totalSumm += CashHandler::toRubles($item['total_summ']);
            }
            $form->membershipPeriods = count($membership);
        }
        $additionalMembership = null;
        if($additionalCottage){
            if(!$additionalCottage->hasDifferentOwner){
                $additionalMembership = MembershipHandler::getDebt($additionalCottage, true);
            }
        }
        if(!!$additionalMembership){
            foreach ($additionalMembership as $item) {
                $totalSumm += CashHandler::toRubles($item['total_summ']);
            }
            $form->additionalMembershipPeriods = count($additionalMembership);
        }
        // ЦЕЛЕВЫЕ ВЗНОСЫ
        $target = TargetHandler::getDebt($cottageInfo, !$main);

        if(!empty($target)){
            $targets = [];
            foreach ($target as $key=>$item) {
                // создам счёт на сумму, необходимую для оплаты
                $summ = CashHandler::toRubles($item['realSumm']);
                $targets[$key] = $summ;
                $totalSumm += $summ;
            }
            $form->target = $targets;
        }
        $additionalTarget = null;
        if($additionalCottage){
            if(!$additionalCottage->hasDifferentOwner){
                $additionalTarget = TargetHandler::getDebt($additionalCottage, true);
            }
        }
        if(!empty($additionalTarget)){
            $targets = [];
            foreach ($additionalTarget as $key=>$item) {
                // создам счёт на сумму, необходимую для оплаты
                $summ = CashHandler::toRubles($item['realSumm']);
                $targets[$key] = $summ;
                $totalSumm += $summ;
            }
            $form->additionalTarget = $targets;
        }
        // РАЗОВЫЕ ВЗНОСЫ
        $single = SingleHandler::getDebtReport($cottageInfo, !$main);
        if(!empty($single)){
            $pays = [];
            foreach ($single as $key=>$item) {
                // создам счёт на сумму, необходимую для оплаты
                $summ = CashHandler::toRubles($item['summ']);
                $payed = CashHandler::toRubles($item['payed']);
                $diff = $summ - $payed;
                $pays[$key] = $diff;
                $totalSumm += $diff;
            }
            $form->single = $pays;
        }
        if($cottageInfo->deposit > 0){
            if($cottageInfo->deposit <= $totalSumm){
                $form->fromDeposit = $cottageInfo->deposit;
            }
            else{
                $form->fromDeposit = $totalSumm;
            }
        }
        $openedBill = null;
        // Если у участка есть неоплаченный счёт- закрою его
        if($openedBill = self::checkUnpayed($cottageNumber, !$main)){
           $openedBill->isPayed = 1;
           $openedBill->save();
        }
        $result = $form->save();
        // получу идентификатор платежа
        $identificator = $result['billId'];
        return ['billId' => $identificator, 'cottageNumber' => $cottageNumber, 'double' => !$main];
    }
}