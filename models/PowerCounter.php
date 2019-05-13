<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 10:52
 */

namespace app\models;

use yii\base\Model;

class PowerCounter extends Model
{
    public $cottageNumber;
    public $oldCounterStartData;
    public $oldCounterEndData;
    public $newCounterData;

    public $cottageInfo;
    public $counterInfo;

    const SCENARIO_CHANGE = 'change';
    public function scenarios()
    {
        return [
            self::SCENARIO_CHANGE => ['cottageNumber', 'oldCounterStartData', 'oldCounterEndData', 'newCounterData'],
        ];
    }
    public function rules()
    {
        return [
            [['cottageNumber', 'oldCounterStartData', 'oldCounterEndData', 'newCounterData'], 'required', 'on' => self::SCENARIO_CHANGE],
            ['cottageNumber', 'integer', 'min' => 1, 'max' => 300],
            [['oldCounterStartData', 'newCounterData'], 'integer', 'min' => 0],
            ['oldCounterEndData', 'integer', 'min' => $this->oldCounterStartData],
        ];
    }
    public function attributeLabels()
    {
        return [
            'cottageNumber' => 'Номер участка',
            'oldCounterStartData' => 'Предыдущие показания старого счётчика',
            'oldCounterEndData' => 'Последние показания старого счётчика',
            'newCounterData' => 'Показания нового счётчика',
        ];
    }

    public function __construct(array $config = [])
    {
        $this->cottageInfo = Cottage::getCottageInfo($config['cottageNumber']);
            // найду последние внесённые показания счётчика
            $this->counterInfo = PowerHandler::getLastFilled($this->cottageInfo);
            $this->cottageNumber = $this->counterInfo->cottageNumber;
            $this->oldCounterStartData = $this->counterInfo->newPowerData;
        parent::__construct($config);
    }

    public function save()
    {
        // сохраню данные о замене счётчика
        $data = new Table_counter_changes;
        $data->cottageNumber = $this->cottageNumber;
        $data->oldCounterStartData = $this->oldCounterStartData;
        $data->oldCounterNewData = $this->oldCounterEndData;
        $data->newCounterData = $this->newCounterData;
        $data->change_time = time();
        $data->save();
        $time = TimeHandler::getDatetimeFromTimestamp($data->change_time);
        $messageText = "<div><h2>Добрый день.</h2><p>Это оповещение о замене счётчика электроэнергии. <br>Дата замены: {$time}<br/>Последние показания старого счётчика: {$data->oldCounterNewData}<br/>Показания нового счётчика: {$data->newCounterData}</p></div>";
        // теперь смотрю. Если не заполнены данные электроэнергии за прошлый месяц- заполняю их данными старого счётчика. Если заполнены- буду думать
        if($this->counterInfo->month == TimeHandler::getPreviousShortMonth()){
            // Если показания уже заполнены...
            // внесу показания нового счётчика как начальные для отсчёта нового месяца
            $this->cottageInfo->currentPowerData = $this->newCounterData;
            $this->cottageInfo->save();
            // рассчитаю разовый платёж за потраченную энергию, считая, что она потрачена в прошлом месяце.
            $difference = $data->oldCounterNewData - $data->oldCounterStartData;
            if($difference > 0){
                $messageText .= "<div>Вам будет выставлен дополнительный счёт за электроэнергию, потраченную по старому счётчику в этом месяце.<br/>В этом месяце электроэнергия расчитывается по новому счётчику.</div>";
            }
            else{
                // если ничего не потрачено- круто, ничего дополнительно не меняем.
                $messageText .= "<div>Этот месяц будет оплачиваться по новому счётчику.</div>";
            }
        }
        else{
            // Если показания не заполнены
            // заполню данные за предыдущий месяц
            $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD, 'cottageNumber' => $this->cottageNumber]);
            $model->cottageNumber = $this->cottageNumber;
            $model->newPowerData = $this->oldCounterEndData;
            $model->month = TimeHandler::getPreviousShortMonth();
            $model->insert();
            // внесу показания нового счётчика как начальные для отсчёта нового месяца
            $this->cottageInfo->currentPowerData = $this->newCounterData;
            $this->cottageInfo->save();
            $messageText .= "<div>Вам отправлен счёт за услуги электроэнергии, основанный на показаниях старого счётчика электроэнергии. <br/>Новый месяц будет высчитываться по новому счётчику.</div>";
        }
        //Cloud::sendMessage($this->cottageInfo, 'Установка нового счётчика электроэнергии', $messageText);
        return true;
    }
}