<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.09.2018
 * Time: 15:52
 */

namespace app\models;

use ErrorException;
use yii\base\Model;

class Filling extends Model
{

    private static $odd = false;

    public static function getPaymentDetails($billInfo): string
    {
        $content = '';
        $info = ComplexPayment::getBillInfo($billInfo);
        $content .= self::getSingleRow('<p style="text-align: center">Счёт на оплату</p>');
        $content .= self::getRow('Идентификатор платежа', $info['billInfo']->id);
        $content .= self::getRow('Статус', $info['billInfo']->isPayed ? 'Оплачен' : 'Не оплачен', $info['billInfo']->isPayed ? Colors::COLOR_DATA : Colors::COLOR_WARNING);
        $content .= self::getRow('Дата выставления счёта', TimeHandler::getDatetimeFromTimestamp($info['billInfo']->creationTime), '');
        if ($info['billInfo']->isPayed) {
            $content .= self::getRow('Дата оплаты', TimeHandler::getDatetimeFromTimestamp($info['billInfo']->paymentTime), '');
        }
        $summChanged = false;
        $content .= self::getRow('Платеж на сумму', $info['billInfo']->totalSumm, Colors::COLOR_DATA, CashHandler::RUB);
        if ($info['billInfo']->isPayed) {
            if ($info['billInfo']->discount > 0) {
                $summChanged = true;
                $content .= self::getRow('Сумма скидки по платежу', $info['billInfo']->discount, Colors::COLOR_DATA, CashHandler::RUB);
            }
            if ($info['billInfo']->depositUsed > 0) {
                $summChanged = true;
                $content .= self::getRow('Оплачено с депозита', $info['billInfo']->depositUsed, Colors::COLOR_DATA, CashHandler::RUB);
            }
            if ($info['billInfo']->toDeposit > 0) {
                $summChanged = true;
                $content .= self::getRow('Зачислено на депозит', $info['billInfo']->toDeposit, Colors::COLOR_DATA, CashHandler::RUB);
            }
        } else {
            if ($info['billInfo']->discount > 0) {
                $summChanged = true;
                $content .= self::getRow('Сумма скидки по платежу', $info['billInfo']->discount, Colors::COLOR_DATA, CashHandler::RUB);
            }
            if ($info['billInfo']->depositUsed > 0) {
                $summChanged = true;
                $content .= self::getRow('К оплате с депозита', $info['billInfo']->depositUsed, Colors::COLOR_DATA, CashHandler::RUB);
            }
        }
        if ($summChanged) {
            $content .= self::getRow('Итого оплачено', $info['billInfo']->payedSumm, Colors::COLOR_DATA, CashHandler::RUB);
        }

        $content .= self::getSingleRow('<h2 style="text-align: center">Детали платежа</h2>');

        if (!empty($info['paymentContent']['power'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Электроэнергия</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено месяцев', count($info['paymentContent']['power']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['power']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Месяцев к оплате', count($info['paymentContent']['power']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['power']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                $content .= self::getEmptyRow();
            }
            foreach ($info['paymentContent']['power']['values'] as $value) {

                $floatSumm = CashHandler::toRubles($value['summ']);

                if ($floatSumm > 0) {
                    $content .= self::getRow(TimeHandler::getFullFromShotMonth($value['date']), $floatSumm, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('Показания начальные', $value['old-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Показания конечные', $value['new-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                    if ($value['corrected']) {
                        $content .= self::getSingleRow('<b style="color:' . Colors::COLOR_WARNING . ';">Соц. норма не используется</b>', true);
                    } else {
                        $content .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф соц. нормы', $value['powerCost'], Colors::COLOR_DATA, CashHandler::RUB);
                        $floatPowerCost = CashHandler::toRubles($value['powerCost']);
                        $content .= self::getRow('Начислено по соц. норме', "{$floatPowerCost} * {$value['in-limit']} = <b style='color:#d43f3a;'>{$value['in-limit-cost']}</b>", '', CashHandler::RUB);
                    }
                    if ($value['difference'] > $value['powerLimit']) {

                        $content .= self::getRow('Потрачено сверх соц. нормы', $value['over-limit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф сверх соц. нормы', $value['powerOvercost'], Colors::COLOR_DATA, CashHandler::RUB);
                        $floatPowerOvercost = CashHandler::toRubles($value['powerOvercost']);
                        $content .= self::getRow('Начислено сверх соц. нормы', "{$floatPowerOvercost} * {$value['over-limit']} = <b style='color:#d43f3a;'>{$value['over-limit-cost']}</b>", '', CashHandler::RUB);
                        $content .= self::getRow('Итого за месяц', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                        $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
                        $content .= self::getEmptyRow();
                    }
                }
            }
        }
        if (!empty($info['paymentContent']['additionalPower'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Электроэнергия (доп. участок)</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено месяцев', count($info['paymentContent']['additionalPower']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalPower']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Месяцев к оплате', count($info['paymentContent']['additionalPower']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalPower']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            foreach ($info['paymentContent']['additionalPower']['values'] as $value) {
                $content .= self::getRow(TimeHandler::getFullFromShotMonth($value['date']), $value['summ'], Colors::COLOR_INFO, CashHandler::RUB);
                $content .= self::getRow('Показания начальные', $value['old-data'], Colors::COLOR_INFO, CashHandler::KW);
                $content .= self::getRow('Показания конечные', $value['new-data'], Colors::COLOR_INFO, CashHandler::KW);
                $content .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                if ($value['corrected']) {
                    $content .= self::getSingleRow('<b style="color:' . Colors::COLOR_WARNING . ';">Соц. норма не используется</b>', true);
                } else {
                    $content .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Тариф соц. нормы', $value['powerCost'], Colors::COLOR_DATA, CashHandler::RUB);
                    $floatPowerCost = CashHandler::toRubles($value['powerCost']);
                    $content .= self::getRow('Начислено по соц. норме', "{$floatPowerCost} * {$value['in-limit']} = <b style='color:#d43f3a;'>{$value['in-limit-cost']}</b>", '', CashHandler::RUB);
                }
                if ($value['difference'] > $value['powerLimit']) {

                    $content .= self::getRow('Потрачено сверх соц. нормы', $value['over-limit'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Тариф сверх соц. нормы', $value['powerOvercost'], Colors::COLOR_DATA, CashHandler::RUB);
                    $floatPowerOvercost = CashHandler::toRubles($value['powerOvercost']);
                    $content .= self::getRow('Начислено сверх соц. нормы', "{$floatPowerOvercost} * {$value['over-limit']} = <b style='color:#d43f3a;'>{$value['over-limit-cost']}</b>", '', CashHandler::RUB);
                    $content .= self::getRow('Итого за месяц', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                    $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
                    $content .= self::getEmptyRow();
                }
            }
        }
        if (!empty($info['paymentContent']['membership'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Членские взносы</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено кварталов', count($info['paymentContent']['membership']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['membership']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Кварталов к оплате', count($info['paymentContent']['membership']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['membership']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['membership']['values'] as $value) {
                $content .= self::getRow(TimeHandler::getFullFromShortQuarter($value['date']), $value['summ'], Colors::COLOR_DATA, CashHandler::RUB);
                if ($value['fixed'] > 0) {
                    $content .= self::getRow('Фиксированный взнос', $value['fixed'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);
                    $content .= self::getRow('Взнос с сотки', $value['float'], Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>{$value['float-cost']}</b>", '', CashHandler::RUB);
                }
                $content .= self::getRow('Итого с участка', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['additionalMembership'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Членские взносы (доп. участок)</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено кварталов', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Кварталов к оплате', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['additionalMembership']['values'] as $value) {
                $content .= self::getRow(TimeHandler::getFullFromShortQuarter($value['date']), $value['summ'], Colors::COLOR_DATA, CashHandler::RUB);
                if ($value['fixed'] > 0) {
                    $content .= self::getRow('Фиксированный взнос', $value['fixed'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);
                    $content .= self::getRow('Взнос с сотки', $value['float'], Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>{$value['float-cost']}</b>", '', CashHandler::RUB);
                }
                $content .= self::getRow('Итого с участка', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['target'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Целевые взносы</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['target']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['target']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['target']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['target']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['target']['values'] as $value) {
                $content .= self::getRow($value['year'] . ' год', $value['summ']);
                $content .= self::getSingleRow('Назначение платежа: ', true);
                $conditions = 0;
                if ($value['fixed'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Фиксированный взнос', $value['fixed'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    ++$conditions;
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);

                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>{$value['summ']['float']}</b>", '', CashHandler::RUB);
                }
                if ($value['payed-before'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Оплачено ранее', $value['payed-before'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                if ($value['left-pay'] > 0) {
                    $content .= self::getRow('Осталось оплатить', $value['left-pay'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                if ($conditions > 1) {
                    $content .= self::getRow('Итого', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['additionalTarget'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Целевые взносы (доп. участок)</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['additionalTarget']['values'] as $value) {
                $content .= self::getRow($value['year'] . ' год', $value['summ']);
                $content .= self::getSingleRow('Назначение платежа: ', true);
                $conditions = 0;
                if ($value['fixed'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Фиксированный взнос', $value['fixed'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    ++$conditions;
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);

                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>{$value['summ']['float']}</b>", '', CashHandler::RUB);
                }
                if ($value['payed-before'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Оплачено ранее', $value['payed-before'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                if ($value['left-pay'] > 0) {
                    $content .= self::getRow('Осталось оплатить', $value['left-pay'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                if ($conditions > 1) {
                    $content .= self::getRow('Итого', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['single'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Разовые взносы</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['single']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['single']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['single']['values'] as $value) {
                $content .= self::getRow('Дата регистрации платежа', TimeHandler::getDatetimeFromTimestamp($value['timestamp']));
                $content .= self::getRow('Сумма платежа', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                if ($value['payed'] > 0) {
                    $content .= self::getRow('Оплачено ранее', $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('Итого к оплате', $value['summ'] - $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                $content .= self::getEmptyRow();
            }
        }
        return $content;
    }

    public static function cancelPowerFill($cottageNumber, $additional = false): array
    {
        // несколько проверок- нужно убедиться, что данные заполнены, ещё не оплачены и нет выставленных неоплаченных платежей по данному участку
        $result = Table_power_months::findOne(['cottageNumber' => $cottageNumber, 'month' => TimeHandler::getPreviousShortMonth(), 'payed' => 'no']);
        if (!empty($result) && Table_payment_bills::find()->where(['cottageNumber' => $cottageNumber, 'isPayed' => '0'])->count() === 0) {
            // убавлю долг по электричеству, выставленный по этому платежу
            if ($additional) {
                $cottage = AdditionalCottage::getCottage($cottageNumber);
            } else {
                $cottage = Cottage::getCottageInfo($cottageNumber);
            }
            $cottage->powerDebt = CashHandler::rublesMath($cottage->powerDebt - $result->totalPay);
            $cottage->currentPowerData = $result->oldPowerData;
            $cottage->save();
            $result->delete();
            return ['status' => 1];
        }
        return ['status' => 0];
    }

    public static function getRow($name, $value, $color = Colors::COLOR_DATA, $sign = ''): string
    {
        self::$odd = !self::$odd;
        return "<tr><td style='text-align:left;" . (self::$odd ? 'background:#e2e2e2;' : '') . "'>$name</td><td  style='text-align:left;" . (self::$odd ? 'background:#e2e2e2;' : '') . "'><b style='color: $color'>$value</b>$sign</td></tr>";
    }

    public static function getSingleRow($text, $bg = false): string
    {
        if ($bg) {
            self::$odd = !self::$odd;
            return "<tr><td colspan='2' style='text-align:left;" . (self::$odd ? 'background:#e2e2e2;' : '') . "'>$text</td></tr>";
        }
        return "<tr><td colspan='2' style='text-align:left;'>$text</td></tr>";
    }

    public static function getEmptyRow(): string
    {
        return "<tr><td colspan='2' style='text-align:left;'>&nbsp;</td></tr>";
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @return string
     * @throws ErrorException
     */
    public static function getCottageDutyText($cottageInfo): string
    {
        $content = '';
        $powerCost = 0;
        $membershipCost = 0;
        $targetCost = 0;
        $singleCost = 0;
        $totalCost = 0;
        // найду данные по задолженности электроэнергии
        if(!empty($cottageInfo->powerDebt)){
            if ($cottageInfo->powerDebt > 0) {
                $power = '<tbody>';
                $power .= '<tr><td colspan="2"><h2 style="color:#FF21A2">Электроэнергия</h2></td></tr>';
                $debt = PowerHandler::getDebtReport($cottageInfo);
                foreach ($debt as $key => $value) {
                    if(!empty($value['totalPay'])){
                        $totalPay = CashHandler::toRubles($value['totalPay']);
                    }
                    else{
                        $totalPay = 0;
                    }
                    if ($totalPay > 0) {
                        $powerCost += $totalPay;
                        $month = TimeHandler::getFullFromShotMonth($key);
                        $power .= self::getRow('Месяц', $month);
                        $power .= self::getRow('Показания начальные', $value['oldPowerData'], Colors::COLOR_INFO, CashHandler::KW);
                        $power .= self::getRow('Показания конечные', $value['newPowerData'], Colors::COLOR_INFO, CashHandler::KW);
                        $power .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                        $power .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);

                        $socialNorm = CashHandler::toSmoothRubles($value['powerCost']);

                        $power .= self::getRow('Тариф соц. нормы', $socialNorm , Colors::COLOR_DATA);
                        $socialSumm = CashHandler::toSmoothRubles($value['inLimitPay']);

                        $power .= self::getRow('Начислено по соц. норме', "{$socialNorm} * {$value['inLimitSumm']} = <b style='color:#d43f3a;'>{$socialSumm}</b>", '');
                        if ($value['difference'] > $value['powerLimit']) {
                            $power .= self::getRow('Потрачено сверх соц. нормы', $value['overLimitSumm'], Colors::COLOR_INFO, CashHandler::KW);
                            $overSocialNorm = CashHandler::toSmoothRubles($value['powerOvercost']);
                            $power .= self::getRow('Тариф сверх соц. нормы', $overSocialNorm, Colors::COLOR_DATA);
                            $overSocialSumm = CashHandler::toSmoothRubles($value['overLimitPay']);
                            $power .= self::getRow('Начислено сверх соц. нормы', "{$overSocialNorm} * {$value['overLimitSumm']} = <b style='color:#d43f3a;'>{$overSocialSumm}</b>", '', CashHandler::RUB);
                            $power .= self::getRow('Итого за месяц', CashHandler::toSmoothRubles($totalPay), Colors::COLOR_WARNING);
                            $power .= '<tr><td colspan="2">&nbsp;</td></tr>';
                        }
                    }
                }
                $content .= $power;
                $powerCost = CashHandler::rublesRound($powerCost);
                $totalCost += $powerCost;
                $powerSumm = CashHandler::toSmoothRubles($powerCost);
                $content .= "<tr><td colspan='2'><h2 style='margin-top: 10px'>Задолженность за электроэнергию: <b style='color:" . Colors::COLOR_WARNING . ";'>$powerSumm</b></h2></td></tr>";
                $content .= Filling::getEmptyRow();
                $content .= '</tbody>';
            }
        }
        // если есть задолженность за членские взносы
        if(!empty($cottageInfo->membershipPayFor)) {
            if ($cottageInfo->membershipPayFor < TimeHandler::getCurrentQuarter()) {
                $membership = '<tbody>';
                $membership .= '<tr><td colspan="2"><h2 style="color: #007155">Членские взносы</h2></td></tr>';
                $debt = MembershipHandler::getDebt($cottageInfo);
                foreach ($debt as $key => $value) {
                    $totalSumm = CashHandler::toRubles($value['total_summ']);
                    $membershipCost += $totalSumm;
                    $quarter = TimeHandler::getFullFromShortQuarter($key);
                    $membership .= self::getRow('Квартал', $quarter);
                    $fixed = CashHandler::toRubles($value['fixed']);
                    if ($fixed > 0) {
                        $fixedSumm = CashHandler::toSmoothRubles($fixed);
                        $membership .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
                    }
                    $float = CashHandler::toRubles($value['float']);
                    if ($float > 0) {
                        $floatSumm = CashHandler::toSmoothRubles($float);
                        $membership .= self::getRow('Расчётная площадь участка', $cottageInfo->cottageSquare, Colors::COLOR_INFO, 'м<sup>2</sup>');
                        $fromMeter = round($float / 100, 4);
                        $membership .= self::getRow('Взнос с сотки', $floatSumm, Colors::COLOR_INFO);
                        $membership .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                        $membership .= self::getRow('С общей площади', "{$cottageInfo->cottageSquare} * {$fromMeter} = <b style='color:#d43f3a;'>{$floatSumm}</b>", '');
                    }
                    $membership .= self::getRow('Итого с участка', CashHandler::toSmoothRubles($totalSumm), Colors::COLOR_WARNING);
                    $membership .= '<tr><td colspan="2">&nbsp;</td></tr>';
                }
                $content .= $membership;
                $membershipCost = CashHandler::rublesRound($membershipCost);
                $totalCost += $membershipCost;
                $membershipSumm = CashHandler::toSmoothRubles($membershipCost);
                $content .= "<tr><td colspan=\"2\"><h2 style='margin-top: 10px'>Задолженность по членским взносам: <b style='color:" . Colors::COLOR_WARNING . ";'>$membershipSumm</b></h2></td></tr>";
                $content .= Filling::getEmptyRow();
                $content .= '</tbody>';
            }
        }
        if(!empty($cottageInfo->targetDebt)) {
            // список неоплаченных целевых взносов
            if ($cottageInfo->targetDebt > 0) {
                $target = '<tbody>';
                $target .= '<tr><td colspan="2"><h2 style="color: #1E1470">Целевые взносы</h2></td></tr>';
                $debt = TargetHandler::getDebt($cottageInfo);
                foreach ($debt as $key => $value){
                    $realSumm = CashHandler::toRubles($value['realSumm']);
                    $targetCost += $realSumm;
                    $target .= self::getRow('Год', $key);
                    self::$odd = !self::$odd;
                    $target .= '<tr><td colspan="2" style="text-align:left;' . (self::$odd ? 'background:#e2e2e2;' : '') . '">Назначение платежа: <b style="color:' . Colors::COLOR_DATA . ';">' . $value['description'] . '</b></td></tr>';
                    $conditions = 0;
                    $fixed = CashHandler::toRubles($value['fixed']);
                    if ($fixed > 0) {
                        $fixedSumm = CashHandler::toSmoothRubles($fixed);
                        ++$conditions;
                        $target .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
                    }
                $float = CashHandler::toRubles($value['float']);
                if ($float > 0) {
                    ++$conditions;
                    $target .= self::getRow('Расчётная площадь участка', $cottageInfo->cottageSquare, Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($float / 100, 4);
                    $floatSumm = CashHandler::toSmoothRubles($value['summ']['float']);
                    $target .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $target .= self::getRow('С общей площади', "{$cottageInfo->cottageSquare} * {$fromMeter} = <b style='color:#d43f3a;'>{$floatSumm}</b>", '');
                }
                if ($value['payed'] > 0) {
                    ++$conditions;
                    $target .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['payed']), Colors::COLOR_INFO);
                }
                if ($conditions > 1) {
                    $target .= self::getRow('Итого', CashHandler::toSmoothRubles($realSumm), Colors::COLOR_WARNING);
                }
                $target .= '<tr><td colspan="2">&nbsp;</td></tr>';
            }
                $content .= $target;
                $targetCost = CashHandler::rublesRound($targetCost);
                $totalCost += $targetCost;
                $targetSumm = CashHandler::toSmoothRubles($targetCost);
                $content .= "<tr><td colspan=\"2\" style=\"border-bottom:1px solid black;\"><h2 style='margin-top: 10px'>Задолженность по целевым взносам: <b style='color:" . Colors::COLOR_WARNING . ";'>$targetSumm</b></h2></td></tr>";
                $content .= Filling::getEmptyRow();
                $content .= '</tbody>';
            }
        }
        if(!empty($cottageInfo->singleDebt)) {
            // список неоплаченных разовых взносов
            if ($cottageInfo->singleDebt > 0) {
                $single = '<tbody>';
                $single .= '<tr><td colspan="2"><h2 style="color: #FF8426">Разовые взносы</h2></tr></td>';
                $debt = SingleHandler::getDebtReport($cottageInfo);
                foreach ($debt as $key => $value) {
                    $single .= self::getRow('Дата регистрации платежа', TimeHandler::getDatetimeFromTimestamp($key));
                    self::$odd = !self::$odd;
                    $summ = CashHandler::toRubles($value['summ']);
                    $single .= '<tr><td colspan="2" style="' . (self::$odd ? 'background:#e2e2e2;' : '') . '">Назначение платежа: ' . $value['description'] . '</td></tr>';
                    $single .= self::getRow('Сумма платежа', CashHandler::toSmoothRubles($summ), Colors::COLOR_WARNING);
                    if (!empty($value['payed'])){
                        $payed = CashHandler::toRubles($value['payed']);
                        if($payed > 0){
                            $single .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($payed), Colors::COLOR_INFO, CashHandler::RUB);
                            $single .= self::getRow('Итого к оплате', $summ - $payed, Colors::COLOR_INFO);
                        }
                    }
                    else{
                        $payed = 0;
                    }
                    $singleCost += CashHandler::rublesMath($summ - $payed);
                    $single .= '<tr><td colspan="2">&nbsp;</td></tr>';
                }

                $content .= $single;
                $singleCost = CashHandler::rublesRound($singleCost);
                $totalCost += $singleCost;
                $singleSumm = CashHandler::toSmoothRubles($singleCost);
                $content .= "<tr><td colspan=\"2\"><h2 style='margin-top: 10px'>Задолженность по разовым взносам: <b style='color:" . Colors::COLOR_WARNING . "'>$singleSumm</b></h2></td></tr>";
                $content .= Filling::getEmptyRow();
                $content .= '</tbody>';
            }
        }

        // если участок главный, проверю, нет ли дополнительного, если есть и без отдельного вдадельца- посчитаю и его долги тоже
        if(Cottage::isMain($cottageInfo) && $cottageInfo->haveAdditional){
            /** @var Table_additional_cottages $additionalCottage */
            $additionalCottage = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
            if(!$additionalCottage->hasDifferentOwner){
                // считаю ещё и задолженности по дополнительному участку
                $powerCost = 0;
                $membershipCost = 0;
                $targetCost = 0;
                // найду данные по задолженности электроэнергии
                if(!empty($additionalCottage->powerDebt)){
                    if ($additionalCottage->powerDebt > 0) {
                        $power = '<tbody>';
                        $power .= '<tr><td colspan="2"><h2 style="color:#FF21A2">Электроэнергия (дополнительный участок)</h2></td></tr>';
                        $debt = PowerHandler::getDebtReport($additionalCottage, true);
                        foreach ($debt as $key => $value) {
                            if(!empty($value['totalPay'])){
                                $totalPay = CashHandler::toRubles($value['totalPay']);
                            }
                            else{
                                $totalPay = 0;
                            }
                            if ($totalPay > 0) {
                                $powerCost += $totalPay;
                                $month = TimeHandler::getFullFromShotMonth($key);
                                $power .= self::getRow('Месяц', $month);
                                $power .= self::getRow('Показания начальные', $value['oldPowerData'], Colors::COLOR_INFO, CashHandler::KW);
                                $power .= self::getRow('Показания конечные', $value['newPowerData'], Colors::COLOR_INFO, CashHandler::KW);
                                $power .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                                $power .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);

                                $socialNorm = CashHandler::toSmoothRubles($value['powerCost']);

                                $power .= self::getRow('Тариф соц. нормы', $socialNorm , Colors::COLOR_DATA);
                                $socialSumm = CashHandler::toSmoothRubles($value['inLimitPay']);
                                $power .= self::getRow('Начислено по соц. норме', "{$socialNorm} * {$value['inLimitSumm']} = <b style='color:#d43f3a;'>{$socialSumm}</b>", '');
                                if ($value['difference'] > $value['powerLimit']) {
                                    $power .= self::getRow('Потрачено сверх соц. нормы', $value['overLimitSumm'], Colors::COLOR_INFO, CashHandler::KW);
                                    $overSocialNorm = CashHandler::toSmoothRubles($value['powerOvercost']);
                                    $power .= self::getRow('Тариф сверх соц. нормы', $overSocialNorm, Colors::COLOR_DATA);
                                    $overSocialSumm = CashHandler::toSmoothRubles($value['overLimitPay']);
                                    $power .= self::getRow('Начислено сверх соц. нормы', "{$overSocialNorm} * {$value['overLimitSumm']} = <b style='color:#d43f3a;'>{$overSocialSumm}</b>", '', CashHandler::RUB);
                                    $power .= self::getRow('Итого за месяц', CashHandler::toSmoothRubles($totalPay), Colors::COLOR_WARNING);
                                    $power .= '<tr><td colspan="2">&nbsp;</td></tr>';
                                }
                            }
                        }
                        $content .= $power;
                        $powerCost = CashHandler::rublesRound($powerCost);
                        $totalCost += $powerCost;
                        $powerSumm = CashHandler::toSmoothRubles($powerCost);
                        $content .= "<tr><td colspan='2'><h2 style='margin-top: 10px'>Задолженность за электроэнергию: <b style='color:" . Colors::COLOR_WARNING . ";'>$powerSumm</b></h2></td></tr>";
                        $content .= Filling::getEmptyRow();
                        $content .= '</tbody>';
                    }
                }
                // если есть задолженность за членские взносы
                if(!empty($additionalCottage->membershipPayFor)) {
                    if ($additionalCottage->membershipPayFor < TimeHandler::getCurrentQuarter()) {
                        $membership = '<tbody>';
                        $membership .= '<tr><td colspan="2"><h2 style="color: #007155">Членские взносы (дополнительный участок)</h2></td></tr>';
                        $debt = MembershipHandler::getDebt($additionalCottage, true);
                        foreach ($debt as $key => $value) {
                            $totalSumm = CashHandler::toRubles($value['total_summ']);
                            $membershipCost += $totalSumm;
                            $quarter = TimeHandler::getFullFromShortQuarter($key);
                            $membership .= self::getRow('Квартал', $quarter);
                            $fixed = CashHandler::toRubles($value['fixed']);
                            if ($fixed > 0) {
                                $fixedSumm = CashHandler::toSmoothRubles($fixed);
                                $membership .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
                            }
                            $float = CashHandler::toRubles($value['float']);
                            if ($float > 0) {
                                $floatSumm = CashHandler::toSmoothRubles($float);
                                $membership .= self::getRow('Расчётная площадь участка', $additionalCottage->cottageSquare, Colors::COLOR_INFO, 'м<sup>2</sup>');
                                $fromMeter = round($float / 100, 4);
                                $membership .= self::getRow('Взнос с сотки', $floatSumm, Colors::COLOR_INFO);
                                $membership .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                                $membership .= self::getRow('С общей площади', "{$additionalCottage->cottageSquare} * {$fromMeter} = <b style='color:#d43f3a;'>{$floatSumm}</b>", '');
                            }
                            $membership .= self::getRow('Итого с участка', CashHandler::toSmoothRubles($totalSumm), Colors::COLOR_WARNING);
                            $membership .= '<tr><td colspan="2">&nbsp;</td></tr>';
                        }
                        $content .= $membership;
                        $membershipCost = CashHandler::rublesRound($membershipCost);
                        $totalCost += $membershipCost;
                        $membershipSumm = CashHandler::toSmoothRubles($membershipCost);
                        $content .= "<tr><td colspan=\"2\"><h2 style='margin-top: 10px'>Задолженность по членским взносам: <b style='color:" . Colors::COLOR_WARNING . ";'>$membershipSumm</b></h2></td></tr>";
                        $content .= Filling::getEmptyRow();
                        $content .= '</tbody>';
                    }
                }
                if(!empty($additionalCottage->targetDebt)) {
                    // список неоплаченных целевых взносов
                    if ($additionalCottage->targetDebt > 0) {
                        $target = '<tbody>';
                        $target .= '<tr><td colspan="2"><h2 style="color: #1E1470">Целевые взносы (дополнительный участок)</h2></td></tr>';
                        $debt = TargetHandler::getDebt($additionalCottage, true);
                        foreach ($debt as $key => $value){
                            $realSumm = CashHandler::toRubles($value['realSumm']);
                            $targetCost += $realSumm;
                            $target .= self::getRow('Год', $key);
                            self::$odd = !self::$odd;
                            $target .= '<tr><td colspan="2" style="text-align:left;' . (self::$odd ? 'background:#e2e2e2;' : '') . '">Назначение платежа: <b style="color:' . Colors::COLOR_DATA . ';">' . $value['description'] . '</b></td></tr>';
                            $conditions = 0;
                            $fixed = CashHandler::toRubles($value['fixed']);
                            if ($fixed > 0) {
                                $fixedSumm = CashHandler::toSmoothRubles($fixed);
                                ++$conditions;
                                $target .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
                            }
                            $float = CashHandler::toRubles($value['float']);
                            if ($float > 0) {
                                ++$conditions;
                                $target .= self::getRow('Расчётная площадь участка', $additionalCottage->cottageSquare, Colors::COLOR_INFO, 'м<sup>2</sup>');
                                $fromMeter = round($float / 100, 4);
                                $floatSumm = CashHandler::toSmoothRubles($value['summ']['float']);
                                $target .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                                $target .= self::getRow('С общей площади', "{$additionalCottage->cottageSquare} * {$fromMeter} = <b style='color:#d43f3a;'>{$floatSumm}</b>", '');
                            }
                            if ($value['payed'] > 0) {
                                ++$conditions;
                                $target .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['payed']), Colors::COLOR_INFO);
                            }
                            if ($conditions > 1) {
                                $target .= self::getRow('Итого', CashHandler::toSmoothRubles($realSumm), Colors::COLOR_WARNING);
                            }
                            $target .= '<tr><td colspan="2">&nbsp;</td></tr>';
                        }
                        $content .= $target;
                        $targetCost = CashHandler::rublesRound($targetCost);
                        $totalCost += $targetCost;
                        $targetSumm = CashHandler::toSmoothRubles($targetCost);
                        $content .= "<tr><td colspan=\"2\" style=\"border-bottom:1px solid black;\"><h2 style='margin-top: 10px'>Задолженность по целевым взносам: <b style='color:" . Colors::COLOR_WARNING . ";'>$targetSumm</b></h2></td></tr>";
                        $content .= Filling::getEmptyRow();
                        $content .= '</tbody>';
                    }
                }
            }
        }
        if ($totalCost > 0) {
            $totalCost = CashHandler::toSmoothRubles($totalCost);
            $content = "<tr><td colspan='2'><table style='max-width: 600px; width: 100%; margin:0; padding: 0;background-color: #f6fbff'><tbody><tr><td colspan='2' style='text-align: center;color:" . Colors::COLOR_WARNING . ";'><h2>У вас имеются задолженности</h2></td></tr><tr><td colspan='2'><h3 style='text-align: center;'>Общая сумма задолженностей: <b style='color:" . Colors::COLOR_WARNING . ";'>{$totalCost}</b></h3></td></tr></tbody>$content</table></td></tr>";
        } else {
            $content = "<tr><td colspan='2'><h2 style='color: #3e8f3e'>Задолженность по платежам отсутствует</h2></td> </tr>";
        }
        return $content;
    }
    public static function checkTariffsFilling(): bool
    {
        // проверю тарифы на членские взносы за данный квартал и электричество за данный месяц
        return Table_tariffs_power::find()->where(['targetMonth' => TimeHandler::getPreviousShortMonth()])->count() && Table_tariffs_membership::find()->where(['quarter' => TimeHandler::getCurrentQuarter()])->count();
    }

    public static function getFillingInfo(): array
    {
        $cottages = Cottage::getRegistredList();
        $additionalCottages = AdditionalCottage::getRegistredList();
        $inserted = PowerHandler::getInserted($cottages, $additionalCottages, TimeHandler::getPreviousShortMonth());
        return $inserted;
    }

    /**
     * @param $billInfo Table_payment_bills|Table_payment_bills_double
     * @param bool $double
     * @return string
     */
    public static function getBillDetails($billInfo, $double = false)
    {
        $content = '';
        $info = ComplexPayment::getBillInfo($billInfo, $double);
        $content .= self::getSingleRow('<h2 style="text-align: center">Детали счёта</h2>');
        $content .= self::getRow('Номер участка', $info['billInfo']->cottageNumber);
        $content .= self::getRow('Номер счёта', $info['billInfo']->id);
        $content .= self::getRow('Дата выставления счёта', TimeHandler::getDatetimeFromTimestamp($info['billInfo']->creationTime), Colors::COLOR_INFO);
        $summChanged = false;
        $content .= self::getRow('Платеж на сумму', CashHandler::toSmoothRubles($info['billInfo']->totalSumm), Colors::COLOR_WARNING);
        if ($info['billInfo']->discount > 0) {
            $summChanged = true;
            $content .= self::getRow('Сумма скидки по платежу', $info['billInfo']->discount, Colors::COLOR_DATA, CashHandler::RUB);
        }
        if ($info['billInfo']->depositUsed > 0) {
            $summChanged = true;
            $content .= self::getRow('К оплате с депозита', $info['billInfo']->depositUsed, Colors::COLOR_DATA, CashHandler::RUB);
        }
        if ($summChanged) {
            $summToPay = CashHandler::toRubles($info['billInfo']->totalSumm) - $info['billInfo']->discount - $info['billInfo']->depositUsed;
            $content .= self::getRow('Итого к оплате', CashHandler::toSmoothRubles($summToPay), Colors::COLOR_WARNING);
        }
        $content .= self::getSingleRow('<h2 style="text-align: center">Детали платежа</h2>');

        if (!empty($info['paymentContent']['power'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Электроэнергия</h3>');
            $content .= self::getRow('Месяцев к оплате', count($info['paymentContent']['power']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', CashHandler::toSmoothRubles($info['paymentContent']['power']['summ']), Colors::COLOR_WARNING);
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['power']['values'] as $value) {
                $floatSumm = CashHandler::toRubles($value['summ']);
                if ($floatSumm > 0) {
                    $content .= self::getRow(TimeHandler::getFullFromShotMonth($value['date']), CashHandler::toSmoothRubles($floatSumm), Colors::COLOR_WARNING);
                    $content .= self::getRow('Показания начальные', $value['old-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Показания конечные', $value['new-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                    if ($value['corrected']) {
                        $content .= self::getSingleRow('<b style="color:' . Colors::COLOR_WARNING . ';">Соц. норма не используется</b>', true);
                    } else {
                        $content .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф соц. нормы', CashHandler::toSmoothRubles($value['powerCost']), Colors::COLOR_DATA);
                        $floatPowerCost = CashHandler::toRubles($value['powerCost']);
                        $content .= self::getRow('Начислено по соц. норме', "{$floatPowerCost} * {$value['in-limit']} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['in-limit-cost']) . "</b>", '');
                    }
                    if ($value['difference'] > $value['powerLimit']) {
                        $content .= self::getRow('Потрачено сверх соц. нормы', $value['over-limit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф сверх соц. нормы', CashHandler::toSmoothRubles($value['powerOvercost']), Colors::COLOR_DATA);
                        $floatPowerOvercost = CashHandler::toRubles($value['powerOvercost']);
                        $content .= self::getRow('Начислено сверх соц. нормы', "{$floatPowerOvercost} * {$value['over-limit']} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['over-limit-cost']) . "</b>", '');
                    }
                    if (!empty($value['prepayed'])) {
                        $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['prepayed']), Colors::COLOR_DATA);
                    }
                    $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
                    $content .= self::getEmptyRow();
                }
            }
        }
        if (!empty($info['paymentContent']['additionalPower'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Электроэнергия (доп. участок)</h3>');
            $content .= self::getRow('Месяцев к оплате', count($info['paymentContent']['additionalPower']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', CashHandler::toSmoothRubles($info['paymentContent']['additionalPower']['summ']), Colors::COLOR_WARNING);
            foreach ($info['paymentContent']['additionalPower']['values'] as $value) {
                $floatSumm = CashHandler::toRubles($value['summ']);
                if ($floatSumm > 0) {
                    $content .= self::getRow(TimeHandler::getFullFromShotMonth($value['date']), CashHandler::toSmoothRubles($floatSumm), Colors::COLOR_WARNING);
                    $content .= self::getRow('Показания начальные', $value['old-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Показания конечные', $value['new-data'], Colors::COLOR_INFO, CashHandler::KW);
                    $content .= self::getRow('Расход за месяц', $value['difference'], Colors::COLOR_DATA, CashHandler::KW);
                    if ($value['corrected']) {
                        $content .= self::getSingleRow('<b style="color:' . Colors::COLOR_WARNING . ';">Соц. норма не используется</b>', true);
                    } else {
                        $content .= self::getRow('Соц. норма', $value['powerLimit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф соц. нормы', CashHandler::toSmoothRubles($value['powerCost']), Colors::COLOR_DATA);
                        $floatPowerCost = CashHandler::toRubles($value['powerCost']);
                        $content .= self::getRow('Начислено по соц. норме', "{$floatPowerCost} * {$value['in-limit']} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['in-limit-cost']) . "</b>", '');
                    }
                    if ($value['difference'] > $value['powerLimit']) {

                        $content .= self::getRow('Потрачено сверх соц. нормы', $value['over-limit'], Colors::COLOR_INFO, CashHandler::KW);
                        $content .= self::getRow('Тариф сверх соц. нормы', CashHandler::toSmoothRubles($value['powerOvercost']), Colors::COLOR_DATA);
                        $floatPowerOvercost = CashHandler::toRubles($value['powerOvercost']);
                        $content .= self::getRow('Начислено сверх соц. нормы', "{$floatPowerOvercost} * {$value['over-limit']} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['over-limit-cost']) . "</b>", '');
                    }
                    if (!empty($value['prepayed'])) {
                        $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['prepayed']), Colors::COLOR_DATA);
                    }
                    $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
                    $content .= self::getEmptyRow();
                }
            }
        }
        if (!empty($info['paymentContent']['membership'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Членские взносы</h3>');
            $content .= self::getRow('Кварталов к оплате', count($info['paymentContent']['membership']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', CashHandler::toSmoothRubles($info['paymentContent']['membership']['summ']), Colors::COLOR_WARNING);
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['membership']['values'] as $value) {
                $content .= self::getRow(TimeHandler::getFullFromShortQuarter($value['date']), CashHandler::toSmoothRubles($value['summ']), Colors::COLOR_WARNING);
                if ($value['fixed'] > 0) {
                    $content .= self::getRow('Фиксированный взнос', CashHandler::toSmoothRubles($value['fixed']), Colors::COLOR_INFO);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);
                    $content .= self::getRow('Взнос с сотки', CashHandler::toSmoothRubles($value['float']), Colors::COLOR_INFO);
                    $content .= self::getRow('Взнос с м<sup>2</sup>', CashHandler::toSmoothRubles($fromMeter), Colors::COLOR_INFO);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['float-cost']) . "</b>", '');
                    if (!empty($value['prepayed'])) {
                        $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['prepayed']), Colors::COLOR_DATA);
                    }
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['additionalMembership'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Членские взносы (доп. участок)</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено кварталов', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Кварталов к оплате', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['additionalMembership']['values'] as $value) {
                $content .= self::getRow(TimeHandler::getFullFromShortQuarter($value['date']), CashHandler::toSmoothRubles($value['summ']), Colors::COLOR_WARNING);
                if ($value['fixed'] > 0) {
                    $content .= self::getRow('Фиксированный взнос', CashHandler::toSmoothRubles($value['fixed']), Colors::COLOR_INFO);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);
                    $content .= self::getRow('Взнос с сотки', CashHandler::toSmoothRubles($value['float']), Colors::COLOR_INFO);
                    $content .= self::getRow('Взнос с м<sup>2</sup>', CashHandler::toSmoothRubles($fromMeter), Colors::COLOR_INFO);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['float-cost']) . "</b>", '');
                    if (!empty($value['prepayed'])) {
                        $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['prepayed']), Colors::COLOR_DATA);
                    }
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['target'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Целевые взносы</h3>');
            $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['target']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', CashHandler::toSmoothRubles($info['paymentContent']['target']['summ']), Colors::COLOR_WARNING);
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['target']['values'] as $value) {
                $content .= self::getRow($value['year'] . ' год', CashHandler::toSmoothRubles($value['summ']));
                //$content .= self::getSingleRow('Назначение платежа: ', true);
                $conditions = 0;
                if ($value['fixed'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Фиксированный взнос', CashHandler::toSmoothRubles($value['fixed']), Colors::COLOR_WARNING);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    ++$conditions;
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);

                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['summ']['float']) . "</b>", '');
                }
                if ($value['payed-before'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['payed-before']), Colors::COLOR_INFO);
                }
                if ($value['left-pay'] > 0) {
                    $content .= self::getRow('Осталось оплатить', CashHandler::toSmoothRubles($value['left-pay']), Colors::COLOR_INFO);
                }
                if ($conditions > 1) {
                    $content .= self::getRow('Итого', $value['summ'], Colors::COLOR_WARNING);
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['additionalTarget'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Целевые взносы (доп. участок)</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['additionalTarget']['values'] as $value) {
                $content .= self::getRow($value['year'] . ' год', CashHandler::toSmoothRubles($value['summ']));
                //$content .= self::getSingleRow('Назначение платежа: ', true);
                $conditions = 0;
                if ($value['fixed'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Фиксированный взнос', CashHandler::toSmoothRubles($value['fixed']), Colors::COLOR_WARNING);
                }
                $floatSumm = CashHandler::toRubles($value['float']);
                if ($floatSumm > 0) {
                    ++$conditions;
                    $content .= self::getRow('Расчётная площадь участка', $value['square'], Colors::COLOR_INFO, 'м<sup>2</sup>');
                    $fromMeter = round($floatSumm / 100, 4);

                    $content .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('С общей площади', "{$value['square']} * {$fromMeter} = <b style='color:#d43f3a;'>" . CashHandler::toSmoothRubles($value['summ']['float']) . "</b>", '');
                }
                if ($value['payed-before'] > 0) {
                    ++$conditions;
                    $content .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($value['payed-before']), Colors::COLOR_INFO);
                }
                if ($value['left-pay'] > 0) {
                    $content .= self::getRow('Осталось оплатить', CashHandler::toSmoothRubles($value['left-pay']), Colors::COLOR_INFO);
                }
                if ($conditions > 1) {
                    $content .= self::getRow('Итого', $value['summ'], Colors::COLOR_WARNING);
                }
                $content .= self::getEmptyRow();
            }
        }
        if (!empty($info['paymentContent']['single'])) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Разовые взносы</h3>');
            if ($info['billInfo']->isPayed) {
                $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['single']['values']), Colors::COLOR_DATA);
                $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
            } else {
                $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['single']['values']), Colors::COLOR_INFO);
                $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
            }
            $content .= self::getEmptyRow();
            foreach ($info['paymentContent']['single']['values'] as $value) {
                $content .= self::getRow('Дата регистрации платежа', TimeHandler::getDatetimeFromTimestamp($value['timestamp']));
                $content .= self::getRow('Сумма платежа', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
                if ($value['payed'] > 0) {
                    $content .= self::getRow('Оплачено ранее', $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
                    $content .= self::getRow('Итого к оплате', $value['summ'] - $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
                }
                $content .= self::getEmptyRow();
            }
        }
        return $content;
    }
}