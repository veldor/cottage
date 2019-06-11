<?php

namespace app\models\migration;

use app\models\AdditionalCottage;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\DOMHandler;
use app\models\GrammarHandler;
use app\models\PersonalTariff;
use app\models\Table_additional_cottages;
use app\models\Table_additional_payed_membership;
use app\models\Table_additional_payed_power;
use app\models\Table_additional_power_months;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payment_bills;
use app\models\Table_payment_bills_double;
use app\models\Table_power_months;
use app\models\Table_tariffs_membership;
use app\models\Table_tariffs_power;
use app\models\Table_tariffs_target;
use app\models\TimeHandler;
use DOMElement;
use yii\base\Model;

class Migration extends Model
{
    public static function migrateCottages()
    {
        // миграция сведений об участках
        $contacts = '<?xml version="1.0" encoding="utf-8"?><contacts>';
        $text = '<?xml version="1.0" encoding="utf-8"?><cottages>';
        $emails = '<?xml version="1.0" encoding="utf-8"?><emails>';
        $phones = '<?xml version="1.0" encoding="utf-8"?><phones>';
        // перегоню необходимую информацию об участках в XML
        $cottages = Cottage::getRegistred();
        foreach ($cottages as $cottage) {
            $text .= "<cottage cottage_number='{$cottage->cottageNumber}' is_membership='1' membership_debt='0' is_power='1' power_debt='0' is_target='1' target_debt='0' single_debt='0' square='{$cottage->cottageSquare}' is_have_property_rights='{$cottage->cottageHaveRights}' is_cottage_register_data='{$cottage->cottageRegisterData}' property_data='{$cottage->cottageRightsData}' register_data='$cottage->cottageRegistrationInformation' is_individual_tariff='{$cottage->individualTariff}' is_additional='0' is_different_owner='0' main_cottage_id=''/>";

            if (!empty($cottage->cottageOwnerPersonals)) {
                $address = strlen(trim(str_replace('&', '', $cottage->cottageOwnerAddress))) > 0 ? $cottage->cottageOwnerAddress : '';
                $contacts .= "<contact cottage_id='$cottage->cottageNumber' contact_name='{$cottage->cottageOwnerPersonals}' contact_address='$address' contact_description='{$cottage->cottageOwnerDescription}' is_owner='1'/>";
                if (!empty($cottage->cottageOwnerPhone)) {
                    $phones .= "<phone cottage='{$cottage->cottageNumber}' is_main='1' phone_description='' number='" . GrammarHandler::normalizePhone($cottage->cottageOwnerPhone) . "'/>";
                }
                if (!empty($cottage->cottageOwnerEmail)) {
                    $emails .= "<email cottage='{$cottage->cottageNumber}' is_main='1' email_description='' email='{$cottage->cottageOwnerEmail}'/>";
                }
            }
            if (!empty($cottage->cottageContacterPersonals)) {
                $contacts .= "<contact cottage_id='$cottage->cottageNumber' contact_name='{$cottage->cottageContacterPersonals}' contact_address='' contact_description='' is_owner='0'/>";
                if (!empty($cottage->cottageContacterPhone)) {
                    $phones .= "<phone cottage='{$cottage->cottageNumber}' is_main='0' phone_description='' number='" . GrammarHandler::normalizePhone($cottage->cottageContacterPhone) . "'/>";
                }
                if (!empty($cottage->cottageContacterEmail)) {
                    $emails .= "<email cottage='{$cottage->cottageNumber}' is_main='0' email_description='' email='{$cottage->cottageContacterEmail}'/>";
                }
            }

            if ($cottage->haveAdditional) {
                /** @var Table_additional_cottages $additionalCottage */
                $additionalCottage = AdditionalCottage::getCottage($cottage->cottageNumber);
                if (!empty($additionalCottage)) {
                    $text .= "<cottage cottage_number='{$additionalCottage->masterId}-a' is_membership='{$additionalCottage->isMembership}' membership_debt='0' is_power='{$additionalCottage->isPower}' power_debt='0' is_target='$additionalCottage->isTarget' target_debt='0' single_debt='0' square='{$additionalCottage->cottageSquare}' is_have_property_rights='' is_cottage_register_data='' property_data='' register_data='' is_individual_tariff='{$additionalCottage->individualTariff}' is_additional='1' is_different_owner='{$additionalCottage->hasDifferentOwner}' main_cottage_id='{$additionalCottage->masterId}'/>";

                    if (!empty($additionalCottage->cottageOwnerPersonals)) {
                        $address = strlen(trim(str_replace('&', '', $additionalCottage->cottageOwnerAddress))) > 0 ? $additionalCottage->cottageOwnerAddress : '';
                        $contacts .= "<contact cottage_id='{$additionalCottage->masterId}-a' contact_name='{$additionalCottage->cottageOwnerPersonals}' contact_address='$address' contact_description='' is_owner='1'/>";
                        if (!empty($additionalCottage->cottageOwnerPhone)) {
                            $phones .= "<phone cottage='{$additionalCottage->masterId}-a' is_main='1' phone_description='' number='" . GrammarHandler::normalizePhone($additionalCottage->cottageOwnerPhone) . "'/>";
                        }
                        if (!empty($additionalCottage->cottageOwnerEmail)) {
                            $emails .= "<email cottage='{$additionalCottage->masterId}-a' is_main='1' email_description='' email='{$additionalCottage->cottageOwnerEmail}'/>";
                        }
                    }
                }
            }
        }
        $text .= '</cottages>';
        file_put_contents('Z:/migration/cottages.xml', $text);
        $contacts .= '</contacts>';
        file_put_contents('Z:/migration/contacts.xml', $contacts);
        $emails .= '</emails>';
        file_put_contents('Z:/migration/emails.xml', $emails);
        $phones .= '</phones>';
        file_put_contents('Z:/migration/phones.xml', $phones);
    }

    public static function migrateTariffs()
    {
        // миграция тарифов
        // электроэнергия
        $powers = '<?xml version="1.0" encoding="utf-8"?><powers>';
        $powerTariffs = Table_tariffs_power::find()->orderBy('targetMonth')->all();
        /** @var Table_tariffs_power $powerTariff */
        foreach ($powerTariffs as $powerTariff) {
            $powerCost = CashHandler::toNewRubles($powerTariff->powerCost);
            $powerOvercost = CashHandler::toNewRubles($powerTariff->powerOvercost);
            $payUpDate = TimeHandler::getPayUpMonth($powerTariff->targetMonth);
            $powers .= "<power_item month='{$powerTariff->targetMonth}' power_limit='{$powerTariff->powerLimit}' power_cost='$powerCost' power_overcost='$powerOvercost' pay_up_date='$payUpDate' search_timestamp='{$powerTariff->searchTimestamp}' />";
        }
        $powers .= '</powers>';
        file_put_contents('Z:/migration/tariff_power.xml', $powers);
        // членские взносы
        $memes = '<?xml version="1.0" encoding="utf-8"?><memberships>';
        $membershipTariffs = Table_tariffs_membership::find()->orderBy('quarter')->all();
        /** @var Table_tariffs_membership $membershipTariff */
        foreach ($membershipTariffs as $membershipTariff) {
            $forMeter = CashHandler::toNewRubles($membershipTariff->changed_part);
            $forCottage = CashHandler::toNewRubles($membershipTariff->fixed_part);
            $payUpDate = TimeHandler::getPayUpQuarterTimestamp($membershipTariff->quarter);
            $memes .= "<membership quarter='{$membershipTariff->quarter}' pay_for_meter='{$forMeter}' pay_for_cottage='$forCottage' pay_up_date='$payUpDate' search_timestamp='{$membershipTariff->search_timestamp}' />";
        }
        $memes .= '</memberships>';
        file_put_contents('Z:/migration/tariff_membership.xml', $memes);
        // целевые взносы
        $targets = '<?xml version="1.0" encoding="utf-8"?><targets>';
        $targetTariffs = Table_tariffs_target::find()->orderBy('year')->all();
        /** @var Table_tariffs_target $targetTariff */
        foreach ($targetTariffs as $targetTariff) {
            $forMeter = CashHandler::toNewRubles($targetTariff->float_part);
            $forCottage = CashHandler::toNewRubles($targetTariff->fixed_part);
            $targets .= "<target year='{$targetTariff->year}' pay_for_meter='{$forMeter}' pay_for_cottage='$forCottage' pay_up_date='{$targetTariff->payUpTime}'/>";
        }
        $targets .= '</targets>';
        file_put_contents('Z:/migration/tariff_target.xml', $targets);
    }

    public static function migratePaysData()
    {
        // миграция данных по взносам
        $cottages = Cottage::getRegistred();
        $powers = '<?xml version="1.0" encoding="utf-8"?><powers>';

        // тарифы членских взносов
        $rawMembershipTariffs = Table_tariffs_membership::find()->orderBy('quarter')->all();
        $membershipTariffs = [];
        foreach ($rawMembershipTariffs as $rawMembershipTariff) {
            $membershipTariffs[$rawMembershipTariff->quarter] = $rawMembershipTariff;
        }
        // тарифы целевых взносов
        $rawTargetTariffs = Table_tariffs_target::find()->orderBy('year')->all();
        $targetTariffs = [];
        foreach ($rawTargetTariffs as $rawTargetTariff) {
            $targetTariffs[$rawTargetTariff->year] = $rawTargetTariff;
        }
        $currentQuarter = TimeHandler::getCurrentQuarter();
        $membership = '<?xml version="1.0" encoding="utf-8"?><memberships>';
        $target = '<?xml version="1.0" encoding="utf-8"?><targets>';
        $single = '<?xml version="1.0" encoding="utf-8"?><singles>';
        foreach ($cottages as $cottage) {
            // получу первый заполненный месяц электроэнергии
            $filledPower = Table_power_months::find()->where(['cottageNumber' => $cottage->cottageNumber])->orderBy('searchTimestamp')->all();
            if (!empty($filledPower)) {
                /** @var Table_power_months $item */
                foreach ($filledPower as $item) {
                    $payUpDate = TimeHandler::getPayUpMonth($item->month);
                    if ($item->difference > 0) {
                        // попробую найти оплату за данный месяц
                        $payments = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => $item->month])->all();
                        $totalPay = CashHandler::toNewRubles($item->totalPay);
                        $inSumm = CashHandler::toNewRubles($item->inLimitPay);
                        $overSumm = CashHandler::toNewRubles($item->overLimitPay);
                        if (!empty($payments)) {
                            $payedSumm = 0;
                            /** @var Table_payed_power $payment */
                            foreach ($payments as $payment) {
                                $payedSumm += CashHandler::toNewRubles($payment->summ);
                            }
                            // теперь нужно проверить, не игнорировался ли лимит
                            $paymentInfo = Table_payment_bills::findOne($payments[0]->billId);
                            $dom = new DOMHandler($paymentInfo->bill_content);
                            /** @var DOMElement $elem */
                            $elem = $dom->query('//month[@date="' . $item->month . '"]')->item(0);
                            $limitIgnored = (bool)$elem->getAttribute('corrected');
                            if (!$limitIgnored) {
                                if ($payedSumm === $totalPay) {
                                    $fullPayed = 1;
                                    $partialPayed = 0;
                                    $active = 0;
                                } else if ($payedSumm < $totalPay) {
                                    $fullPayed = 0;
                                    $partialPayed = 1;
                                    $active = 1;
                                } else {
                                    die('переплата');
                                }
                            } else {
                                echo 'limit ignored ' . $payments[0]->billId;
                                die;
                            }
                            $powers .= "<power_data cottage_number='{$cottage->cottageNumber}' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='{$item->difference}' total_pay='{$totalPay}' in_limit_data='{$item->inLimitSumm}' over_limit_data='{$item->overLimitSumm}' in_limit_pay='$inSumm' over_limit_pay='$overSumm' is_limit_ignored='$limitIgnored' payed_summ='$payedSumm' is_full_payed='$fullPayed' is_partial_payed='$partialPayed' is_active='$active' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                        } else {
                            $powers .= "<power_data cottage_number='{$cottage->cottageNumber}' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='{$item->difference}' total_pay='{$totalPay}' in_limit_data='{$item->inLimitSumm}' over_limit_data='{$item->overLimitSumm}' in_limit_pay='$inSumm' over_limit_pay='$overSumm' is_limit_ignored='0' payed_summ='0' is_full_payed='0' is_partial_payed='0' is_active='1' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                        }
                    } else {
                        $powers .= "<power_data cottage_number='{$cottage->cottageNumber}' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='0' total_pay='0' in_limit_data='0' over_limit_data='0' in_limit_pay='0' over_limit_pay='0' is_limit_ignored='0' payed_summ='0' is_full_payed='1' is_partial_payed='0' is_active='0' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                    }
                }
            }
            // членские взносы
            $square = $cottage->cottageSquare;
            if ($cottage->individualTariff) {
                // получу индивидуальные тарифы по членским взносам
                $tariffs = PersonalTariff::getMembershipTariffs($cottage);
                if (!empty($tariffs)) {
                    foreach ($tariffs as $key => $tariff) {
                        $payForCottage = CashHandler::toNewRubles($tariff['fixed']);
                        $payForField = CashHandler::toNewRubles($tariff['float']);
                        $totalPay = (int)round($payForCottage + (double)$payForField / 100 * $square);
                        $payUpDate = TimeHandler::getPayUpQuarterTimestamp($key);
                        // проверю наличие оплаты
                        $payedSumm = 0;
                        $payments = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber, 'quarter' => $key])->all();
                        if (!empty($payments)) {
                            /** @var Table_payed_membership $payment */
                            foreach ($payments as $payment) {
                                $payedSumm += CashHandler::toNewRubles($payment->summ);
                            }
                        }
                        $isFullPayed = 0;
                        $isPartialPayed = 0;
                        if ($payedSumm === $totalPay) {
                            $isFullPayed = 1;
                        } elseif ($payedSumm > $totalPay) {
                            die('ошибка в расчётах');
                        } elseif ($payedSumm > 0) {
                            $isPartialPayed = 1;
                        }
                        $membership .= "<membership cottage_number='{$cottage->cottageNumber}' quarter='$key' search_timestamp='{$membershipTariffs[$key]->search_timestamp}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='1' individual_pay_for_cottage='$payForCottage' individual_pay_for_field='$payForField' pay_up_date='$payUpDate'/>";
                    }
                }
            } else {
                // получу квартал, с которого буду вести отсчёт
                $firstQuarter = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber])->orderBy('quarter')->one();
                if (!empty($firstQuarter)) {
                    $start = $firstQuarter->quarter;
                } else {
                    $start = TimeHandler::getNextQuarter($cottage->membershipPayFor);
                }
                // теперь найду квартал, до которого будет идти счёт
                // если последний оплаченный квартал больше, чем текущий - он крайний
                if ($cottage->membershipPayFor > $currentQuarter) {
                    $finish = $cottage->membershipPayFor;
                } else {
                    $finish = $currentQuarter;
                }
                $current = $start;
                // пройдусь по кварталам начиная со старта и заканчивая финишем
                do {
                    $thisTariff = $membershipTariffs[$current];
                    $totalPay = (int)round(CashHandler::toNewRubles($thisTariff->fixed_part) + (double)CashHandler::toNewRubles($thisTariff->changed_part) / 100 * $square);
                    $payedSumm = 0;
                    $payments = Table_payed_membership::find()->where(['cottageId' => $cottage->cottageNumber, 'quarter' => $current])->all();
                    if (!empty($payments)) {
                        /** @var Table_payed_membership $payment */
                        foreach ($payments as $payment) {
                            $payedSumm += CashHandler::toNewRubles($payment->summ);
                        }
                    }
                    $isFullPayed = 0;
                    $isPartialPayed = 0;
                    if ($payedSumm === $totalPay) {
                        $isFullPayed = 1;
                    } elseif ($payedSumm > $totalPay) {
                        die('ошибка в расчётах');
                    } elseif ($payedSumm > 0) {
                        $isPartialPayed = 1;
                    }
                    $membership .= "<membership cottage_number='{$cottage->cottageNumber}' quarter='$current' search_timestamp='{$membershipTariffs[$current]->search_timestamp}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='0'/>";
                    $current = TimeHandler::getNextQuarter($current);
                } while ($current <= $finish);
            }
            // целевые платежи
            if ($cottage->individualTariff) {
                // получу индивидуальные тарифы по членским взносам
                $tariffs = PersonalTariff::getTargetTariffs($cottage);
                if (!empty($tariffs)) {
                    foreach ($tariffs as $key => $tariff) {
                        $payForCottage = CashHandler::toNewRubles($tariff['fixed']);
                        $payForField = CashHandler::toNewRubles($tariff['float']);
                        $totalPay = (int)round($payForCottage + (double)$payForField / 100 * $square);
                        if(empty( $targetTariffs[$key])){
                            echo $cottage->cottageNumber;
                            var_dump($tariffs);
                            die;
                        }
                        $payUpDate = $targetTariffs[$key]->payUpTime;
                        // проверю наличие оплаты
                        $isFullPayed = 0;
                        $isPartialPayed = 0;
                        $dom = new DOMHandler($cottage->targetPaysDuty);
                        $pay = $dom->query('//target[@year="' . $key . '"]');
                        if(empty($payItem = $pay->item(0))){
                            $isFullPayed = 1;
                            $payedSumm = $totalPay;
                        }
                        else{
                            /** @var DOMElement $payItem */
                            $payedSumm = CashHandler::toNewRubles($payItem->getAttribute('payed'));
                            if($payedSumm > 0){
                                $isPartialPayed = 1;
                            }
                        }
                        $target .= "<target cottage_number='{$cottage->cottageNumber}' year='$key' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='1' individual_pay_for_cottage='$payForCottage' individual_pay_for_field='$payForField' pay_up_date='$payUpDate'/>";
                    }
                }
            } else {
                // пройдусь по годам
                /** @var Table_tariffs_target $targetTariff */
                foreach ($targetTariffs as $targetTariff) {
                    $totalPay = (int)round(CashHandler::toNewRubles($targetTariff->fixed_part) + (double)CashHandler::toNewRubles($targetTariff->float_part) / 100 * $square);
                    $isFullPayed = 0;
                    $isPartialPayed = 0;
                    $dom = new DOMHandler($cottage->targetPaysDuty);
                    $pay = $dom->query('//target[@year="' . $targetTariff->year . '"]');
                    if(empty($payItem = $pay->item(0))){
                        $isFullPayed = 1;
                        $payedSumm = $totalPay;
                    }
                    else{
                        /** @var DOMElement $payItem */
                        $payedSumm = CashHandler::toNewRubles($payItem->getAttribute('payed'));
                        if($payedSumm > 0){
                            $isPartialPayed = 1;
                        }
                    }
                    $target .= "<target cottage_number='{$cottage->cottageNumber}' year='{$targetTariff->year}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='0'/>";
                }
            }
            // разовые платежи
            if(!empty($cottage->singlePaysDuty)){
                $dom = new DOMHandler($cottage->singlePaysDuty);
                $singlePayments = $dom->query('//singlePayment');
                if(!empty($singlePayments)){
                    foreach ($singlePayments as $singlePayment) {
                        /** @var DOMElement $singlePayment */
                        $summ = CashHandler::toNewRubles($singlePayment->getAttribute('summ'));
                        $payed = CashHandler::toNewRubles($singlePayment->getAttribute('payed'));
                        $isPartialPayed = $payed > 0 ? 1 : 0;
                        $description = $singlePayment->getAttribute('description');
                        $time = $singlePayment->getAttribute('time');
                        $single .= "<single cottage_number='{$cottage->cottageNumber}' pay_description='$description' filling_date='$time' square='$square' total_pay='$summ' payed_summ='$payed' is_partial_payed='$isPartialPayed' is_full_payed='0'/>";
                    }
                }
            }
            if(!empty($cottage->haveAdditional)){
                $cottage = AdditionalCottage::getCottage($cottage->cottageNumber);
                // повторю всё то же для дополнительного участка
                if($cottage->isPower){
                    // получу первый заполненный месяц электроэнергии
                    $filledPower = Table_additional_power_months::find()->where(['cottageNumber' => $cottage->masterId])->orderBy('searchTimestamp')->all();
                    if (!empty($filledPower)) {
                        /** @var Table_additional_power_months $item */
                        foreach ($filledPower as $item) {
                            $payUpDate = TimeHandler::getPayUpMonth($item->month);
                            if ($item->difference > 0) {
                                // попробую найти оплату за данный месяц
                                $payments = Table_additional_payed_power::find()->where(['cottageId' => $cottage->masterId, 'month' => $item->month])->all();
                                $totalPay = CashHandler::toNewRubles($item->totalPay);
                                $inSumm = CashHandler::toNewRubles($item->inLimitPay);
                                $overSumm = CashHandler::toNewRubles($item->overLimitPay);
                                if (!empty($payments)) {
                                    $payedSumm = 0;
                                    /** @var Table_payed_power $payment */
                                    foreach ($payments as $payment) {
                                        $payedSumm += CashHandler::toNewRubles($payment->summ);
                                    }
                                    // теперь нужно проверить, не игнорировался ли лимит
                                    $paymentInfo = Table_payment_bills_double::findOne($payments[0]->billId);
                                    $dom = new DOMHandler($paymentInfo->bill_content);
                                    /** @var DOMElement $elem */
                                    $elem = $dom->query('//month[@date="' . $item->month . '"]')->item(0);
                                    $limitIgnored = (bool)$elem->getAttribute('corrected');
                                    if (!$limitIgnored) {
                                        if ($payedSumm === $totalPay) {
                                            $fullPayed = 1;
                                            $partialPayed = 0;
                                            $active = 0;
                                        } else if ($payedSumm < $totalPay) {
                                            $fullPayed = 0;
                                            $partialPayed = 1;
                                            $active = 1;
                                        } else {
                                            die('переплата');
                                        }
                                    } else {
                                        echo 'limit ignored ' . $payments[0]->billId;
                                        die;
                                    }
                                    $powers .= "<power_data cottage_number='{$cottage->masterId}-a' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='{$item->difference}' total_pay='{$totalPay}' in_limit_data='{$item->inLimitSumm}' over_limit_data='{$item->overLimitSumm}' in_limit_pay='$inSumm' over_limit_pay='$overSumm' is_limit_ignored='$limitIgnored' payed_summ='$payedSumm' is_full_payed='$fullPayed' is_partial_payed='$partialPayed' is_active='$active' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                                } else {
                                    $powers .= "<power_data cottage_number='{$cottage->masterId}-a' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='{$item->difference}' total_pay='{$totalPay}' in_limit_data='{$item->inLimitSumm}' over_limit_data='{$item->overLimitSumm}' in_limit_pay='$inSumm' over_limit_pay='$overSumm' is_limit_ignored='0' payed_summ='0' is_full_payed='0' is_partial_payed='0' is_active='1' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                                }
                            } else {
                                $powers .= "<power_data cottage_number='{$cottage->masterId}-a' month='{$item->month}' filling_date='{$item->fillingDate}' old_data='{$item->oldPowerData}' new_data='{$item->newPowerData}' search_timestamp='{$item->searchTimestamp}' difference='0' total_pay='0' in_limit_data='0' over_limit_data='0' in_limit_pay='0' over_limit_pay='0' is_limit_ignored='0' payed_summ='0' is_full_payed='1' is_partial_payed='0' is_active='0' is_individual_tariff='0' pay_up_date='$payUpDate'/>";
                            }
                        }
                    }
                }
                if($cottage->isMembership){
                    // членские взносы
                    $square = $cottage->cottageSquare;
                    if ($cottage->individualTariff) {
                        // получу индивидуальные тарифы по членским взносам
                        $tariffs = PersonalTariff::getMembershipTariffs($cottage);
                        if (!empty($tariffs)) {
                            foreach ($tariffs as $key => $tariff) {
                                $payForCottage = CashHandler::toNewRubles($tariff['fixed']);
                                $payForField = CashHandler::toNewRubles($tariff['float']);
                                $totalPay = (int)round($payForCottage + (double)$payForField / 100 * $square);
                                $payUpDate = TimeHandler::getPayUpQuarterTimestamp($key);
                                // проверю наличие оплаты
                                $payedSumm = 0;
                                $payments = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->masterId, 'quarter' => $key])->all();
                                if (!empty($payments)) {
                                    /** @var Table_additional_payed_membership $payment */
                                    foreach ($payments as $payment) {
                                        $payedSumm += CashHandler::toNewRubles($payment->summ);
                                    }
                                }
                                $isFullPayed = 0;
                                $isPartialPayed = 0;
                                if ($payedSumm === $totalPay) {
                                    $isFullPayed = 1;
                                } elseif ($payedSumm > $totalPay) {
                                    die('ошибка в расчётах');
                                } elseif ($payedSumm > 0) {
                                    $isPartialPayed = 1;
                                }
                                $membership .= "<membership cottage_number='{$cottage->masterId}-a' quarter='$key' search_timestamp='{$membershipTariffs[$key]->search_timestamp}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='1' individual_pay_for_cottage='$payForCottage' individual_pay_for_field='$payForField' pay_up_date='$payUpDate'/>";
                            }
                        }
                    } else {
                        // получу квартал, с которого буду вести отсчёт
                        $firstQuarter = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->masterId])->orderBy('quarter')->one();
                        if (!empty($firstQuarter)) {
                            $start = $firstQuarter->quarter;
                        } else {
                            $start = TimeHandler::getNextQuarter($cottage->membershipPayFor);
                        }
                        // теперь найду квартал, до которого будет идти счёт
                        // если последний оплаченный квартал больше, чем текущий - он крайний
                        if ($cottage->membershipPayFor > $currentQuarter) {
                            $finish = $cottage->membershipPayFor;
                        } else {
                            $finish = $currentQuarter;
                        }
                        $current = $start;
                        // пройдусь по кварталам начиная со старта и заканчивая финишем
                        do {
                            $thisTariff = $membershipTariffs[$current];
                            $totalPay = (int)round(CashHandler::toNewRubles($thisTariff->fixed_part) + (double)CashHandler::toNewRubles($thisTariff->changed_part) / 100 * $square);
                            $payedSumm = 0;
                            $payments = Table_additional_payed_membership::find()->where(['cottageId' => $cottage->masterId, 'quarter' => $current])->all();
                            if (!empty($payments)) {
                                /** @var Table_additional_payed_membership $payment */
                                foreach ($payments as $payment) {
                                    $payedSumm += CashHandler::toNewRubles($payment->summ);
                                }
                            }
                            $isFullPayed = 0;
                            $isPartialPayed = 0;
                            if ($payedSumm === $totalPay) {
                                $isFullPayed = 1;
                            } elseif ($payedSumm > $totalPay) {
                                die('ошибка в расчётах');
                            } elseif ($payedSumm > 0) {
                                $isPartialPayed = 1;
                            }
                            $membership .= "<membership cottage_number='{$cottage->masterId}-a' quarter='$current' search_timestamp='{$membershipTariffs[$current]->search_timestamp}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='0'/>";
                            $current = TimeHandler::getNextQuarter($current);
                        } while ($current <= $finish);
                    }
                }
if($cottage->isTarget){
    // целевые платежи
    if ($cottage->individualTariff) {
        // получу индивидуальные тарифы по членским взносам
        $tariffs = PersonalTariff::getTargetTariffs($cottage);
        if (!empty($tariffs)) {
            foreach ($tariffs as $key => $tariff) {
                $payForCottage = CashHandler::toNewRubles($tariff['fixed']);
                $payForField = CashHandler::toNewRubles($tariff['float']);
                $totalPay = (int)round($payForCottage + (double)$payForField / 100 * $square);
                if(empty( $targetTariffs[$key])){
                    echo $cottage->masterId;
                    var_dump($tariffs);
                    die;
                }
                $payUpDate = $targetTariffs[$key]->payUpTime;
                // проверю наличие оплаты
                $isFullPayed = 0;
                $isPartialPayed = 0;
                $dom = new DOMHandler($cottage->targetPaysDuty);
                $pay = $dom->query('//target[@year="' . $key . '"]');
                if(empty($payItem = $pay->item(0))){
                    $isFullPayed = 1;
                    $payedSumm = $totalPay;
                }
                else{
                    /** @var DOMElement $payItem */
                    $payedSumm = CashHandler::toNewRubles($payItem->getAttribute('payed'));
                    if($payedSumm > 0){
                        $isPartialPayed = 1;
                    }
                }
                $target .= "<target cottage_number='{$cottage->masterId}-a' year='$key' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='1' individual_pay_for_cottage='$payForCottage' individual_pay_for_field='$payForField' pay_up_date='$payUpDate'/>";
            }
        }
    } else {
        // пройдусь по годам
        /** @var Table_tariffs_target $targetTariff */
        foreach ($targetTariffs as $targetTariff) {
            $totalPay = (int)round(CashHandler::toNewRubles($targetTariff->fixed_part) + (double)CashHandler::toNewRubles($targetTariff->float_part) / 100 * $square);
            $isFullPayed = 0;
            $isPartialPayed = 0;
            $dom = new DOMHandler($cottage->targetPaysDuty);
            $pay = $dom->query('//target[@year="' . $targetTariff->year . '"]');
            if(empty($payItem = $pay->item(0))){
                $isFullPayed = 1;
                $payedSumm = $totalPay;
            }
            else{
                /** @var DOMElement $payItem */
                $payedSumm = CashHandler::toNewRubles($payItem->getAttribute('payed'));
                if($payedSumm > 0){
                    $isPartialPayed = 1;
                }
            }
            $target .= "<target cottage_number='{$cottage->masterId}-a' year='{$targetTariff->year}' square='$square' total_pay='$totalPay' payed_summ='$payedSumm' is_partial_payed='$isPartialPayed' is_full_payed='$isFullPayed' is_individual_tariff='0'/>";
        }
    }
}
                // разовые платежи
                if(!empty($cottage->singlePaysDuty)){
                    $dom = new DOMHandler($cottage->singlePaysDuty);
                    $singlePayments = $dom->query('//singlePayment');
                    if(!empty($singlePayments)){
                        foreach ($singlePayments as $singlePayment) {
                            /** @var DOMElement $singlePayment */
                            $summ = CashHandler::toNewRubles($singlePayment->getAttribute('summ'));
                            $payed = CashHandler::toNewRubles($singlePayment->getAttribute('payed'));
                            $isPartialPayed = $payed > 0 ? 1 : 0;
                            $description = $singlePayment->getAttribute('description');
                            $time = $singlePayment->getAttribute('time');
                            $single .= "<single cottage_number='{$cottage->masterId}-a' pay_description='$description' filling_date='$time' square='$square' total_pay='$summ' payed_summ='$payed' is_partial_payed='$isPartialPayed' is_full_payed='0'/>";
                        }
                    }
                }
            }
        }
        $powers .= '</powers>';
        file_put_contents('Z:/migration/data_power.xml', $powers);

        $membership .= '</memberships>';
        file_put_contents('Z:/migration/data_membership.xml', $membership);

        $target .= '</targets>';
        file_put_contents('Z:/migration/data_target.xml', $target);

        $single .= '</singles>';
        file_put_contents('Z:/migration/data_singles.xml', $single);
    }
}