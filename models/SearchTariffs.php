<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.12.2018
 * Time: 12:48
 */

namespace app\models;


use DateTime;
use Exception;

class SearchTariffs extends Search
{
    public const SCENARIO_TARIFFS_SEARCH = 'tariffs-search';

    public function scenarios():array
    {
        return [
            self::SCENARIO_TARIFFS_SEARCH => ['startDate', 'finishDate'],
        ];
    }

    public function rules():array
    {
        return [
            [['startDate', 'finishDate', 'summary'], 'required'],
            [['startDate', 'finishDate'], 'date', 'format' => 'y-M-d'],
            [['summary'], 'boolean'],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function doSearch(): array
    {
        $start = new DateTime('0:0:00'. $this->startDate);
        $finish = new DateTime('23:59:50'. $this->finishDate);
        $interval = ['start' => $start->format('U'), 'finish' => $finish->format('U')];
        $yearInterval = ['start' => $start->format('Y'), 'finish' => $finish->format('Y')];
        $answer = [];
	    $answer['membership'] = Table_tariffs_membership::find()->where(['>=', 'search_timestamp', $interval['start']])->andWhere(['<=', 'search_timestamp', $interval['finish']])->all();
	    $answer['power'] = Table_tariffs_power::find()->where(['>=', 'searchTimestamp', $interval['start']])->andWhere(['<=', 'searchTimestamp', $interval['finish']])->all();
	    $answer['target'] = Table_tariffs_target::find()->where(['>=', 'year', $yearInterval['start']])->andWhere(['<=', 'year', $yearInterval['finish']])->all();
	    return ['status' => 1, 'data' => $answer];
    }
}