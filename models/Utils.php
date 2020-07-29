<?php


namespace app\models;


use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\database\CottageSquareChanges;
use app\models\utils\DbTransaction;
use DOMElement;
use yii\base\Model;

class Utils extends Model
{

    public static function makeAddressesList()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><cottages>';
        // получу все участки
        $cottages = Cottage::getRegistred();
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                $xml .= '<cottage><cottage_number>' . $cottage->cottageNumber . '</cottage_number><email>';
                if (!empty($cottage->cottageOwnerEmail) || !empty($cottage->cottageContacterEmail)) {
                    $xml .= 'Присутствует';
                } else {
                    $xml .= 'Отсутствует';
                }
                $xml .= '</email><name>';
                // теперь обработаю данные почтового адреса
                if (!empty($cottage->cottageOwnerPersonals)) {
                    $xml .= $cottage->cottageOwnerPersonals;
                } else {
                    $xml .= 'Отсутствует';
                }
                $xml .= '</name>';
                $xml .= '<address>';
                // теперь обработаю данные почтового адреса
                if (!empty($cottage->cottageOwnerAddress)) {
                    $xml .= GrammarHandler::clearAddress($cottage->cottageOwnerAddress);
                } else {
                    $xml .= 'Отсутствует';
                }
                $xml .= '</address>';
                $xml .= '</cottage>';
            }
        }
        $xml .= '</cottages>';
        $dom = new DOMHandler($xml);
        $output = $dom->saveForFile();
        file_put_contents('post_addresses.xml', $output);
    }

    /**
     * @throws \Exception
     */
    public static function fillMembershipAccruals(): void
    {
        $transaction = new DbTransaction();
        $cottages = Cottage::getRegistred();
        $additionalCottages = Table_additional_cottages::find()->orderBy('masterId')->all();
        $cottages = array_merge($cottages, $additionalCottages);
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                // если в таблице уже есть информация по этому участку- пропускаю
                if (Accruals_membership::find()->where(['cottage_number' => $cottage->getCottageNumber()])->count() > 0) {
                    continue;
                }
                $firstFilledQuarter = MembershipHandler::getFirstFilledQuarter($cottage);
                // внесу в таблицу данные по участку
                $quartersList = TimeHandler::getQuarterList(['start' => $firstFilledQuarter, 'finish' => Table_tariffs_membership::find()->orderBy('quarter DESC')->one()->quarter]);
                foreach ($quartersList as $key => $item) {
                    // получу данные по этому месяцу, с учётом того, что у участка может быть индивидуальный тариф
                    $tariff = PersonalTariff::getMembershipRate($cottage, $key);
                    $square = CottageSquareChanges::getQuarterSquare($cottage, $key);
                    (new Accruals_membership(['cottage_number' => $cottage->getCottageNumber(), 'quarter' => $key, 'fixed_part' => $tariff['fixed'], 'square_part' => $tariff['float'], 'counted_square' => $square]))->save();
                }
            }
        }
        $transaction->commitTransaction();
    }

    /**
     * @throws ExceptionWithStatus
     */
    public static function deleteTarget(): void
    {
        // найду тариф
        $tariff = Table_tariffs_target::findOne(['year' => '2020']);
        if ($tariff !== null) {
            $transaction = new DbTransaction();
            $cottages = Cottage::getRegistred();
            $additionalCottages = Cottage::getRegistred(true);
            $cottages = array_merge($cottages, $additionalCottages);
            if (!empty($cottages)) {
                foreach ($cottages as $cottage) {
                    $targetDetails = $cottage->targetPaysDuty;
                    if ($targetDetails !== null) {
                        $dom = new DOMHandler($targetDetails);
                        // найду счёт за 2020 год
                        $duty = $dom->query('/targets/target[@year="2020"]');
                        if ($duty !== null && $duty->length === 1) {
                            // найду сумму счёта
                            /** @var DOMElement $item */
                            $item = $duty->item(0);
                            $accrued = CashHandler::toRubles($item->getAttribute('summ'));
                            $cottage->targetDebt = CashHandler::toRubles($cottage->targetDebt - $accrued);
                            $item->parentNode->removeChild($item);
                            $cottage->targetPaysDuty = $dom->save();
                            $cottage->save();
                        }
                    }
                }
            }
            $transaction->commitTransaction();
        }
    }

    /**
     * @throws ExceptionWithStatus
     */
    public static function fillTargetAccruals()
    {
        $transaction = new DbTransaction();
        $cottages = Cottage::getRegistred();
        $additionalCottages = Table_additional_cottages::find()->orderBy('masterId')->all();
        $cottages = array_merge($cottages, $additionalCottages);
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                // если в таблице уже есть информация по этому участку- пропускаю
                if (Accruals_target::find()->where(['cottage_number' => $cottage->getCottageNumber()])->count() > 0) {
                    continue;
                }
                $firstFilledYear = TargetHandler::getFirstFilledYear();
                // внесу в таблицу данные по участку
                $yearsList = TimeHandler::getYearsList($firstFilledYear->year, TimeHandler::getThisYear());
                foreach ($yearsList as $year) {
                    $duty = 0;
                    // получу данные по этому месяцу, с учётом того, что у участка может быть индивидуальный тариф
                    $tariff = PersonalTariff::getTargetRate($cottage, $year);
                    $square = CottageSquareChanges::getQuarterSquare($cottage, $year . '-3');

                    // посчитаю оплату вне системы
                    // получу данные о текущем состоянии оплаты целевых платежей
                    if($cottage->targetPaysDuty !== null){
                        $targetDom = new DOMHandler($cottage->targetPaysDuty);
                        $yearDuty = $targetDom->query('/targets/target[@year="' . $year . '"]');
                        if ($yearDuty->length === 1) {
                            $item = $yearDuty->item(0);
                            $summ = $item->getAttribute('summ');
                            $payed = CashHandler::toRubles($item->getAttribute('payed'));
                            $payedInside = TargetHandler::getPartialPayed($cottage, $year);
                            (new Accruals_target(['cottage_number' => $cottage->getCottageNumber(), 'year' => $year, 'fixed_part' => $tariff['fixed'], 'square_part' => $tariff['float'], 'counted_square' => $square, 'payed_outside' => CashHandler::toRubles($payed - $payedInside)]))->save();

                        } else {
                            // долга нет. Значит, ищем оплаты, и если сумма оплат меньше, чем начислено- то остальное оплачено раньше
                            $payed = TargetHandler::getPartialPayed($cottage, $year);
                            $accrued = Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $square);
                            (new Accruals_target(['cottage_number' => $cottage->getCottageNumber(), 'year' => $year, 'fixed_part' => $tariff['fixed'], 'square_part' => $tariff['float'], 'counted_square' => $square, 'payed_outside' => CashHandler::toRubles($accrued - $payed)]))->save();
                        }
                    }
                    else{
                        $payed = TargetHandler::getPartialPayed($cottage, $year);
                        $accrued = Calculator::countFixedFloat($tariff['fixed'], $tariff['float'], $square);
                        (new Accruals_target(['cottage_number' => $cottage->getCottageNumber(), 'year' => $year, 'fixed_part' => $tariff['fixed'], 'square_part' => $tariff['float'], 'counted_square' => $square, 'payed_outside' => CashHandler::toRubles($accrued - $payed)]))->save();
                    }

                }
            }
        }
        $transaction->commitTransaction();
    }
}