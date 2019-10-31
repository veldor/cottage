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
     * @var selections\TargetDebt[]|array
     */
    public $targetDuty;
    /**
     * @var selections\SingleDebt[]|array
     */
    public $singleDuty;
    /**
     * @var selections\PowerDebt[]
     */
    public $additionalPowerDuty;
    /**
     * @var int
     */
    public $additionalSquare;
    /**
     * @var selections\MembershipDebt[]
     */
    public $additionalMembershipDuty;
    /**
     * @var selections\TargetDebt[]|array
     */
    public $additionalTargetDuty;
}