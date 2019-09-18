<?php

namespace app\models\selections;

use app\models\Table_additional_power_months;
use app\models\Table_power_months;
use app\models\Table_tariffs_power;

class MembershipDebt
{

    /**
     * @var Table_tariffs_power
     */
    public $tariff;
    /**
     * @var string
     */
    public $tariffFixed;
    /**
     * @var string
     */
    public $tariffFloat;
    /**
     * @var string
     */
    public $amount;
    /**
     * @var string
     */
    public $quarter;

    /**
     * @var string
     */
    public $partialPayed;
}