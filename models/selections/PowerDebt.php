<?php

namespace app\models\selections;

use app\models\Table_additional_power_months;
use app\models\Table_power_months;
use app\models\Table_tariffs_power;

class PowerDebt
{

    /**
     * @var Table_power_months|Table_additional_power_months
     */
    public $powerData;
    /**
     * @var Table_tariffs_power
     */
    public $tariff;
    /**
     * @var string
     */
    public $partialPayed;
    /**
     * @var string
     */
    public $withoutLimitAmount;

}