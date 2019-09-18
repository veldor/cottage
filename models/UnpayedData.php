<?php


namespace app\models;


use yii\base\Model;

class UnpayedData extends Model
{

    /**
     * @var int
     */
    public $square;
    /**
     * @var selections\PowerDebt[]
     */
    public $powerDuty;
    /**
     * @var selections\MembershipDebt[]
     */
    public $membershipDuty;
    /**
     * @var selections\TargetDebt[]
     */
    public $targetDuty;
    /**
     * @var selections\SingleDebt[]
     */
    public $singleDuty;
    /**
     * @var selections\PowerDebt[]
     */
    public $additionalPowerDuty;
    /**
     * @var selections\MembershipDebt[]
     */
    public $additionalMembershipDuty;
    /**
     * @var int
     */
    public $additionalSquare;
    /**
     * @var selections\TargetDebt[]|array
     */
    public $additionalTargetDuty;
}