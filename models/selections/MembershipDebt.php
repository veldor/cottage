<?php

namespace app\models\selections;

use app\models\Table_tariffs_membership;
use yii\base\Model;

class MembershipDebt extends Model
{

    /**
     * @var Table_tariffs_membership
     */
    public Table_tariffs_membership $tariff;
    /**
     * @var float
     */
    public float $tariffFixed;
    /**
     * @var float
     */
    public float $tariffFloat;
    /**
     * @var float
     */
    public float $amount;
    /**
     * @var string
     */
    public string $quarter;

    /**
     * @var float
     */
    public float $partialPayed;
}