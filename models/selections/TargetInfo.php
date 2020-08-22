<?php


namespace app\models\selections;


use yii\base\Model;

class TargetInfo extends Model
{
    public int $year;
    public float $amount = 0;
    public float $payed = 0;
    public string $cottageNumber;
}