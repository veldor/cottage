<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.04.2019
 * Time: 18:31
 */

namespace app\models\small_classes;
use app\models\Table_payment_bills;
use app\models\Table_payment_bills_double;

/**
 * Class SerialCottageInfo
 * @package app\models\small_classes
 */
class SerialCottageInfo
{
    public $cottageNumber;
    public $haveDebt = false;
    public $isUnfilledPower = false;
    /**
     * @var bool|Table_payment_bills|Table_payment_bills_double
     */
    public $unpayedBill = false;
    public $isDouble = false;
}