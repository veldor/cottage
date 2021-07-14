<?php


namespace app\models\handlers;


use app\models\Cottage;
use app\models\database\Bill;
use app\models\DOMHandler;
use app\models\PowerHandler;
use app\models\Table_additional_cottages;
use app\models\Table_additional_power_months;
use app\models\Table_cottages;
use app\models\Table_payment_bills;
use app\models\Table_payment_bills_double;
use app\models\Table_power_months;
use DOMElement;
use yii\base\Model;

class BillsHandler extends Model
{

    /**
     * @param $cottage Table_cottages|Table_additional_cottages
     * @param $lastFilled Table_power_months|Table_additional_power_months
     * @return Table_payment_bills[]|Table_payment_bills_double[]|null
     */
    public static function getMonthContains($cottage, $lastFilled): array
    {
        $containedBills = [];
        // получу платежи по данному участку
        if (Cottage::isMain($cottage)) {
            $bills = Table_payment_bills::findAll(['cottageNumber' => $cottage->cottageNumber]);
            if (!empty($bills)) {
                foreach ($bills as $bill) {
                    // проверю, не входит ли в счет данный месяц
                    if (self::billHaveMonth($bill, $lastFilled->month)) {
                        $containedBills[] = $bill;
                    }
                }
            }
        }
        return $containedBills;
    }

    /**
     * @param $bill Table_payment_bills
     * @return array
     */
    private static function getMonths($bill): array
    {
        $result = [];
        $xml = new DOMHandler($bill->bill_content);
        $months = $xml->query('/payment/power/month');
        if ($months->length > 0) {
            /** @var DOMElement $month */
            foreach ($months as $month) {
                $result[] = $month->getAttribute('date');
            }
        }
        return $result;
    }

    /**
     * @param Table_payment_bills $bill
     * @param string $month
     * @return bool
     */
    private static function billHaveMonth(Table_payment_bills $bill, string $month): bool
    {

        $months = self::getMonths($bill);
        if (!empty($months)) {
            return in_array($month, $months, true);
        }
        return false;
    }

    /**
     * @param $identificator
     * @return Table_payment_bills
     */
    public static function getBill($identificator, $double = false): Table_payment_bills
    {
        if ($double || strpos($identificator, 'a')) {
            return Table_payment_bills_double::findOne((int)$identificator);
        }
        return Table_payment_bills::findOne($identificator);
    }
}