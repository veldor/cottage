<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.09.2018
 * Time: 15:52
 */

namespace app\models;

use app\models\selections\PowerDebt;
use app\models\tables\Table_penalties;
use app\models\tables\Table_view_fines_info;
use Exception;
use yii\base\ErrorException;
use yii\base\Model;

class Filling extends Model
{

    private static bool $odd = false;

    /**
     * @param $billInfo
     * @return string
     * @throws ExceptionWithStatus
     */
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
        $content .= self::getRow('Платеж на сумму', $info['billInfo']->totalSumm, Colors::COLOR_DATA, CashHandler::RUB);
        if ($info['billInfo']->isPayed) {
            [$summChanged, $content] = self::insertAmountChanges($info, $content);
            if ($info['billInfo']->toDeposit > 0) {
                $summChanged = true;
                $content .= self::getRow('Зачислено на депозит', $info['billInfo']->toDeposit, Colors::COLOR_DATA, CashHandler::RUB);
            }
        } else {
            [$summChanged, $content] = self::insertAmountChanges($info, $content);
        }
        if ($summChanged) {
            $content .= self::getRow('<b>Итого к оплате</b>', CashHandler::toSmoothRubles($info['billInfo']->totalSumm - $info['billInfo']->payedSumm - $info['billInfo']->depositUsed - $info['billInfo']->discount), Colors::COLOR_DATA);
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
                    $content = self::getPowerContent($value, $content);
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
                $content = self::getPowerContent($value, $content);
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
                $content = self::insertMembershipContent($value, $content);
            }
        }
        if (!empty($info['paymentContent']['additionalMembership'])) {
            $content = self::insertMembershipPayedInfo($content, $info);
            foreach ($info['paymentContent']['additionalMembership']['values'] as $value) {
                $content = self::insertMembershipContent($value, $content);
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
                $content = self::insertTargetContent($value, $content);
            }
        }
        if (!empty($info['paymentContent']['additionalTarget'])) {
            $content = self::insertTargetText($content, $info);
            foreach ($info['paymentContent']['additionalTarget']['values'] as $value) {
                $content = self::insertTargetContent($value, $content);
            }
        }
        if (!empty($info['paymentContent']['single'])) {
            $content = self::insertSingleText($content, $info);
            foreach ($info['paymentContent']['single']['values'] as $value) {
                $content = self::insertSingleContent($value, $content);
            }
        }
        // пени
        $fines = Table_view_fines_info::find()->where(['bill_id' => $billInfo->id])->all();
        if (!empty($fines)) {
            $content .= self::getSingleRow('<h3 style="text-align: center">Пени</h3>');
            $totalSumm = 0;
            foreach ($fines as $fine) {
                $totalSumm += $fine->summ;
                switch ($fine->pay_type) {
                    case 'power':
                        $type = 'Электроэнергия';
                        $fullPeriod = TimeHandler::getFullFromShotMonth($fine->period);
                        break;
                    case 'membership':
                        $type = 'Членские взносы';
                        $fullPeriod = TimeHandler::getFullFromShortQuarter($fine->period);
                        break;
                    case 'target':
                        $type = 'Целевые взносы';
                        $fullPeriod = $fine->period;
                        break;
                    default:
                        throw new ExceptionWithStatus('Не смог распознать тип пени: ' . $fine->pay_type);
                }
                $content .= self::getRow('Вид платежа', $type, Colors::COLOR_INFO);
                $content .= self::getRow('Период платежа', $fullPeriod, Colors::COLOR_INFO);
                try {
                    $content .= self::getRow('Дней просрочено', FinesHandler::getFineDaysLeft(Table_penalties::findOne($fine->fines_id)), Colors::COLOR_INFO);
                } catch (Exception $e) {
                }
                $content .= self::getRow('К оплате', CashHandler::toSmoothRubles($fine->start_summ));
                $content .= self::getEmptyRow();
            }
            $content .= self::getRow('Итого пени', CashHandler::toSmoothRubles($totalSumm));

        }
        return $content;
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
        if (!empty($cottageInfo->powerDebt) && $cottageInfo->powerDebt > 0) {
            $power = '<tbody>';
            $power .= '<tr><td colspan="2"><h2 style="color:#FF21A2">Электроэнергия</h2></td></tr>';
            [$content, $totalCost] = self::generatePowerDebtReport($cottageInfo, $powerCost, $power, $content, $totalCost);
        }
        // если есть задолженность за членские взносы
        if (!empty($cottageInfo->membershipPayFor) && $cottageInfo->membershipPayFor < TimeHandler::getCurrentQuarter()) {
            $membership = '<tbody>';
            $membership .= '<tr><td colspan="2"><h2 style="color: #007155">Членские взносы</h2></td></tr>';
            [$content, $totalCost] = self::generateMembershipDebtReport($cottageInfo, $membershipCost, $membership, $content, $totalCost);
        }
        // список неоплаченных целевых взносов
        if (!empty($cottageInfo->targetDebt) && $cottageInfo->targetDebt > 0) {
            $target = '<tbody>';
            $target .= '<tr><td colspan="2"><h2 style="color: #1E1470">Целевые взносы</h2></td></tr>';
            [$content, $totalCost] = self::generateTargetDebtReport($cottageInfo, $targetCost, $target, $content, $totalCost);
        }
        // список неоплаченных разовых взносов
        if (!empty($cottageInfo->singleDebt) && $cottageInfo->singleDebt > 0) {
            $single = '<tbody>';
            $single .= '<tr><td colspan="2"><h2 style="color: #FF8426">Разовые взносы</h2></tr></td>';
            $debt = SingleHandler::getDebtReport($cottageInfo);
            foreach ($debt as $key => $value) {
                $single .= self::getRow('Дата регистрации платежа', TimeHandler::getDatetimeFromTimestamp($key));
                self::$odd = !self::$odd;
                $summ = CashHandler::toRubles($value['summ']);
                $single .= '<tr><td colspan="2" style="' . (self::$odd ? 'background:#e2e2e2;' : '') . '">Назначение платежа: ' . $value['description'] . '</td></tr>';
                $single .= self::getRow('Сумма платежа', CashHandler::toSmoothRubles($summ), Colors::COLOR_WARNING);
                if (!empty($value['payed'])) {
                    $payed = CashHandler::toRubles($value['payed']);
                    if ($payed > 0) {
                        $single .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($payed), Colors::COLOR_INFO, CashHandler::RUB);
                        $single .= self::getRow('Итого к оплате', $summ - $payed, Colors::COLOR_INFO);
                    }
                } else {
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
            $content .= self::getEmptyRow();
            $content .= '</tbody>';
        }

        // если участок главный, проверю, нет ли дополнительного, если есть и без отдельного вдадельца- посчитаю и его долги тоже
        /** @noinspection NotOptimalIfConditionsInspection */
        if (Cottage::isMain($cottageInfo) && $cottageInfo->haveAdditional) {
            /** @var Table_additional_cottages $additionalCottage */
            $additionalCottage = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
            if (!$additionalCottage->hasDifferentOwner) {
                // считаю ещё и задолженности по дополнительному участку
                $powerCost = 0;
                $membershipCost = 0;
                $targetCost = 0;
                // найду данные по задолженности электроэнергии
                if (!empty($additionalCottage->powerDebt) && $additionalCottage->powerDebt > 0) {
                    $power = '<tbody>';
                    $power .= '<tr><td colspan="2"><h2 style="color:#FF21A2">Электроэнергия (дополнительный участок)</h2></td></tr>';
                    [$content, $totalCost] = self::generatePowerDebtReport($cottageInfo, $powerCost, $power, $content, $totalCost);
                }
                // если есть задолженность за членские взносы
                if (!empty($additionalCottage->membershipPayFor) && $additionalCottage->membershipPayFor < TimeHandler::getCurrentQuarter()) {
                    $membership = '<tbody>';
                    $membership .= '<tr><td colspan="2"><h2 style="color: #007155">Членские взносы (дополнительный участок)</h2></td></tr>';
                    [$content, $totalCost] = self::generateMembershipDebtReport($cottageInfo, $membershipCost, $membership, $content, $totalCost);
                }
                // список неоплаченных целевых взносов
                if (!empty($additionalCottage->targetDebt) && $additionalCottage->targetDebt > 0) {
                    $target = '<tbody>';
                    $target .= '<tr><td colspan="2"><h2 style="color: #1E1470">Целевые взносы (дополнительный участок)</h2></td></tr>';
                    [$content, $totalCost] = self::generateTargetDebtReport($cottageInfo, $targetCost, $target, $content, $totalCost);
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

    /**
     * @return array
     * @throws ErrorException
     */
    public static function getFillingInfo(): array
    {
        $cottages = Cottage::getRegisteredList();
        $additionalCottages = AdditionalCottage::getRegistredList();
        return PowerHandler::getInserted($cottages, $additionalCottages, TimeHandler::getPreviousShortMonth());
    }

    /**
     * @param array $info
     * @param string $content
     * @return array
     */
    public static function insertAmountChanges(array $info, string $content): array
    {
        $summChanged = false;
        if ($info['billInfo']->discount > 0) {
            $summChanged = true;
            $content .= self::getRow('Сумма скидки по платежу', $info['billInfo']->discount, Colors::COLOR_DATA, CashHandler::RUB);
        }
        if ($info['billInfo']->depositUsed > 0) {
            $summChanged = true;
            $content .= self::getRow('Будет списано с депозита', $info['billInfo']->depositUsed, Colors::COLOR_DATA, CashHandler::RUB);
        }
        return array($summChanged, $content);
    }

    /**
     * @param $value
     * @param string $content
     * @return string
     */
    public static function getPowerContent($value, string $content): string
    {
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
        return $content;
    }

    /**
     * @param $value
     * @param string $content
     * @return string
     */
    private static function insertMembershipContent($value, string $content): string
    {
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
        return $content;
    }

    /**
     * @param string $content
     * @param array $info
     * @return string
     */
    private static function insertMembershipPayedInfo(string $content, array $info): string
    {
        $content .= self::getSingleRow('<h3 style="text-align: center">Членские взносы (доп. участок)</h3>');
        if ($info['billInfo']->isPayed) {
            $content .= self::getRow('Оплачено кварталов', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_DATA);
            $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
        } else {
            $content .= self::getRow('Кварталов к оплате', count($info['paymentContent']['additionalMembership']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', $info['paymentContent']['additionalMembership']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
        }
        $content .= self::getEmptyRow();
        return $content;
    }

    /**
     * @param $value
     * @param string $content
     * @return string
     */
    private static function insertTargetContent($value, string $content): string
    {
        $content .= self::getRow($value['year'] . ' год', $value['summ']);
        $description = Table_tariffs_target::findOne(['year' => $value['year']])->description;
        $content .= self::getRow('Назначение платежа: ', $description);
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
        return $content;
    }

    /**
     * @param string $content
     * @param array $info
     * @return string
     */
    private static function insertTargetText(string $content, array $info): string
    {
        $content .= self::getSingleRow('<h3 style="text-align: center">Целевые взносы (доп. участок)</h3>');
        if ($info['billInfo']->isPayed) {
            $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_DATA);
            $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
        } else {
            $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['additionalTarget']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', $info['paymentContent']['additionalTarget']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
        }
        $content .= self::getEmptyRow();
        return $content;
    }

    /**
     * @param string $content
     * @param array $info
     * @return string
     */
    private static function insertSingleText(string $content, array $info): string
    {
        $content .= self::getSingleRow('<h3 style="text-align: center">Разовые взносы</h3>');
        if ($info['billInfo']->isPayed) {
            $content .= self::getRow('Оплачено взносов', count($info['paymentContent']['single']['values']), Colors::COLOR_DATA);
            $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_DATA, CashHandler::RUB);
        } else {
            $content .= self::getRow('Взносов к оплате', count($info['paymentContent']['single']['values']), Colors::COLOR_INFO);
            $content .= self::getRow('На сумму', $info['paymentContent']['single']['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
        }
        $content .= self::getEmptyRow();
        return $content;
    }

    /**
     * @param $value
     * @param string $content
     * @return string
     */
    private static function insertSingleContent($value, string $content): string
    {
        $content .= self::getRow('Дата регистрации платежа', TimeHandler::getDatetimeFromTimestamp($value['timestamp']));
        $content .= self::getRow('Сумма платежа', $value['summ'], Colors::COLOR_WARNING, CashHandler::RUB);
        $content .= self::getRow('Назначение платежа', urldecode($value['description']), Colors::COLOR_INFO);
        if ($value['payed'] > 0) {
            $content .= self::getRow('Оплачено ранее', $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
            $content .= self::getRow('Итого к оплате', $value['summ'] - $value['payed'], Colors::COLOR_INFO, CashHandler::RUB);
        }
        $content .= self::getEmptyRow();
        return $content;
    }

    /**
     * @param $cottageInfo
     * @param $powerCost
     * @param string $power
     * @param string $content
     * @param float $totalCost
     * @return array
     */
    private static function generatePowerDebtReport($cottageInfo, $powerCost, string $power, string $content, float $totalCost): array
    {
        $debt = PowerHandler::getDebtReport($cottageInfo);
        /** @var PowerDebt $value */
        foreach ($debt as $value) {
            if (!empty($value->powerData->totalPay)) {
                $totalPay = CashHandler::toRubles($value->powerData->totalPay);
            } else {
                $totalPay = 0;
            }
            if ($totalPay > 0) {
                $powerCost += $totalPay;
                $month = TimeHandler::getFullFromShotMonth($value->tariff->targetMonth);
                $power .= self::getRow('Месяц', $month);
                $power .= self::getRow('Показания начальные', $value->powerData->oldPowerData, Colors::COLOR_INFO, CashHandler::KW);
                $power .= self::getRow('Показания конечные', $value->powerData->newPowerData, Colors::COLOR_INFO, CashHandler::KW);
                $power .= self::getRow('Расход за месяц', $value->powerData->difference, Colors::COLOR_DATA, CashHandler::KW);
                $power .= self::getRow('Соц. норма', $value->tariff->powerLimit, Colors::COLOR_INFO, CashHandler::KW);

                $socialNorm = CashHandler::toSmoothRubles($value->powerData->inLimitSumm);

                $power .= self::getRow('Тариф соц. нормы', $value->tariff->powerCost, Colors::COLOR_DATA, CashHandler::RUB);
                $socialSumm = CashHandler::toSmoothRubles($value->powerData->inLimitPay);

                $power .= self::getRow('Начислено по соц. норме', "{$value->tariff->powerCost} * {$value->powerData->inLimitSumm} = <b style='color:#d43f3a;'>{$socialSumm}</b>", '');
                if ($value->powerData->difference > $value->tariff->powerLimit) {
                    $power .= self::getRow('Потрачено сверх соц. нормы', $value->powerData->overLimitSumm, Colors::COLOR_INFO, CashHandler::KW);
                    $overSocialNorm = CashHandler::toSmoothRubles($value->powerData->overLimitPay);
                    $power .= self::getRow('Тариф сверх соц. нормы', $overSocialNorm, Colors::COLOR_DATA);
                    $overSocialSumm = CashHandler::toSmoothRubles($value->powerData->overLimitPay);
                    $power .= self::getRow('Начислено сверх соц. нормы', "{$overSocialNorm} * {$value->powerData->overLimitSumm} = <b style='color:#d43f3a;'>{$overSocialSumm}</b>", '', CashHandler::RUB);
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
        $content .= self::getEmptyRow();
        $content .= '</tbody>';
        return array($content, $totalCost);
    }

    /**
     * @param $cottageInfo
     * @param float $membershipCost
     * @param string $membership
     * @param string $content
     * @param float $totalCost
     * @return array
     */
    private static function generateMembershipDebtReport($cottageInfo, float $membershipCost, string $membership, string $content, float $totalCost): array
    {
        $debt = MembershipHandler::getDebt($cottageInfo);
        foreach ($debt as  $value) {
            $totalSumm = CashHandler::toRubles($value->amount);
            $membershipCost += $totalSumm;
            $quarter = TimeHandler::getFullFromShortQuarter($value->quarter);
            $membership .= self::getRow('Квартал', $quarter);
            $fixed = CashHandler::toRubles($value->tariffFixed);
            if ($fixed > 0) {
                $fixedSumm = CashHandler::toSmoothRubles($fixed);
                $membership .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
            }
            $float = CashHandler::toRubles($value->tariffFloat);
            if ($float > 0) {
                $floatSumm = CashHandler::toSmoothRubles(($float * $cottageInfo->cottageSquare) / 100);
                $membership = self::countFixedFloat($cottageInfo, $membership, $float, $floatSumm);
            }
            $membership .= self::getRow('Итого с участка', CashHandler::toSmoothRubles($totalSumm), Colors::COLOR_WARNING);
            $membership .= '<tr><td colspan="2">&nbsp;</td></tr>';
        }
        $content .= $membership;
        $membershipCost = CashHandler::rublesRound($membershipCost);
        $totalCost += $membershipCost;
        $membershipSumm = CashHandler::toSmoothRubles($membershipCost);
        $content .= "<tr><td colspan=\"2\"><h2 style='margin-top: 10px'>Задолженность по членским взносам: <b style='color:" . Colors::COLOR_WARNING . ";'>$membershipSumm</b></h2></td></tr>";
        $content .= self::getEmptyRow();
        $content .= '</tbody>';
        return array($content, $totalCost);
    }

    /**
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param float $targetCost
     * @param string $target
     * @param string $content
     * @param float $totalCost
     * @return array
     */
    private static function generateTargetDebtReport($cottageInfo, float $targetCost, string $target, string $content, float $totalCost): array
    {
        $debts = TargetHandler::getDebt($cottageInfo);
        foreach ($debts as $debt) {
            $realSumm = CashHandler::toRubles($debt->amount);
            $targetCost += $realSumm;
            $target .= self::getRow('Год', $debt->year);
            self::$odd = !self::$odd;
            $target .= '<tr><td colspan="2" style="text-align:left;' . (self::$odd ? 'background:#e2e2e2;' : '') . '">Назначение платежа: <b style="color:' . Colors::COLOR_DATA . ';">' . $debt->description . '</b></td></tr>';
            $conditions = 0;
            $fixed = CashHandler::toRubles($debt->tariffFixed);
            if ($fixed > 0) {
                $fixedSumm = CashHandler::toSmoothRubles($fixed);
                ++$conditions;
                $target .= self::getRow('Фиксированный взнос', $fixedSumm, Colors::COLOR_WARNING);
            }
            $float = CashHandler::toRubles($debt->tariffFloat);
            if ($float > 0) {
                ++$conditions;
                $floatSumm = CashHandler::toSmoothRubles(($debt->tariffFloat * $cottageInfo->cottageSquare) / 100);
                $target = self::countFixedFloat($cottageInfo, $target, $float, $floatSumm);
            }
            if ($debt->partialPayed > 0) {
                ++$conditions;
                $target .= self::getRow('Оплачено ранее', CashHandler::toSmoothRubles($debt->partialPayed), Colors::COLOR_INFO);
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
        $content .= self::getEmptyRow();
        $content .= '</tbody>';
        return array($content, $totalCost);
    }

    /**
     * @param $cottageInfo
     * @param string $membership
     * @param float $float
     * @param string $floatSumm
     * @return string
     */
    private static function countFixedFloat($cottageInfo, string $membership, float $float, string $floatSumm): string
    {
        $membership .= self::getRow('Расчётная площадь участка', $cottageInfo->cottageSquare, Colors::COLOR_INFO, 'м<sup>2</sup>');
        $fromMeter = round($float / 100, 4);
        $membership .= self::getRow('Взнос с сотки', $float, Colors::COLOR_INFO, CashHandler::RUB);
        $membership .= self::getRow('Взнос с м<sup>2</sup>', $fromMeter, Colors::COLOR_INFO, CashHandler::RUB);
        $membership .= self::getRow('С общей площади', "{$cottageInfo->cottageSquare} * {$fromMeter} = <b style='color:#d43f3a;'>{$floatSumm}</b>", '');
        return $membership;
    }
}