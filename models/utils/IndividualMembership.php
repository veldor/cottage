<?php


namespace app\models\utils;


use app\models\CashHandler;
use app\models\database\Accruals_membership;
use yii\base\Model;

class IndividualMembership extends Model
{
    public string $fixed_part = '0';
    public string $square_part = '0';


    public function attributeLabels(): array
    {
        return [
            'fixed_part' => 'Стоимость с участка',
            'square_part' => 'Стоимость с сотки',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // name, email, subject и body атрибуты обязательны
            [['fixed_part', 'square_part'], 'required'],
        ];
    }

    public function submit(Accruals_membership $accrual): void
    {
        $accrual->fixed_part = CashHandler::toRubles($this->fixed_part);
        $accrual->square_part = CashHandler::toRubles($this->square_part);
        $accrual->save();
    }
}