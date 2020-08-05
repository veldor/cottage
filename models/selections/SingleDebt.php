<?php

namespace app\models\selections;

use app\models\Table_additional_power_months;
use app\models\Table_power_months;
use app\models\Table_tariffs_power;

class SingleDebt
{
    public float $amount;
    /**
     * @var float
     */
    public float $partialPayed;
    /**
     * @var float
     */
    public $description;
    /**
     * @var string
     */
    public $time;
    /**
     * @var string
     */
}