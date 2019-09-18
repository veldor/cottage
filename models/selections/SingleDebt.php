<?php

namespace app\models\selections;

use app\models\Table_additional_power_months;
use app\models\Table_power_months;
use app\models\Table_tariffs_power;

class SingleDebt
{
    public $amount;
    /**
     * @var string
     */
    public $partialPayed;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $time;
}