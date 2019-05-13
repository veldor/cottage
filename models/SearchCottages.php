<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 06.01.2019
 * Time: 12:21
 */

namespace app\models;


class SearchCottages extends Search {
	public $options;
	const SCENARIO_COTTAGES_SEARCH = 'cottages-search';
	const OR_SELECTOR = 'or';

	public $columns, $conditions, $values, $merge;

	public function scenarios():array
	{
		return [
			self::SCENARIO_COTTAGES_SEARCH => ['columns', 'conditions', 'values', 'merge'],
		];
	}

	public function __construct(array $config = []){
		parent::__construct($config);
		$info = Table_Columns::find()->where(['TABLE_SCHEMA' => 'cottage', 'TABLE_NAME' => 'cottages'])->select(['COLUMN_COMMENT', 'COLUMN_NAME', 'DATA_TYPE'])->all();
		if(!empty($info)){
			foreach ($info as $item){
				$this->options[$item->COLUMN_NAME] = ['comment' => $item->COLUMN_COMMENT, 'type' => $item->DATA_TYPE];
			}
		}
	}
	public function doSearch(): array
	{
		$answer = [];
		$conditions = [];
		if(!empty($this->columns)){
			// сформирую запрос
			$query = Table_cottages::find();
			$c = 0;
			while(!empty($this->columns[$c])){
				// найду условие запроса
				//
                // если объявлен аттрибут исключения- ищу с параметр orWhere
                if(!empty($this->merge[$c]) && $this->merge[$c] === self::OR_SELECTOR){
                    switch ($this->conditions[$c]){
                        case 'true':
                            $query->orWhere([$this->columns[$c] => 1]);
                            break;
                        case 'false':
                            $query->orWhere([$this->columns[$c] => 0])->andWhere([$this->columns[$c] => null]);
                            break;
                        case 'equal':
                            $query->orWhere([$this->columns[$c] => $this->values[$c]]);
                            break;
                        case 'not_equal':
                            $query->orWhere(['!=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'more':
                            $query->orWhere(['>', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'more_or_equal':
                            $query->orWhere(['>=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'less':
                            $query->orWhere(['<', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'less_or_equal':
                            $query->orWhere(['<=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'contains':
                            $query->orWhere(['LIKE', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'no-contains':
                            $query->andWhere(['NOT LIKE', $this->columns[$c], $this->values[$c]]);
                            break;
                    }
                }
                else{
                    switch ($this->conditions[$c]){
                        case 'true':
                            $query->andWhere([$this->columns[$c] => 1]);
                            break;
                        case 'false':
                            $query->andWhere([$this->columns[$c] => 0])->orWhere([$this->columns[$c] => null]);
                            break;
                        case 'equal':
                            $query->andWhere([$this->columns[$c] => $this->values[$c]]);
                            break;
                        case 'not_equal':
                            $query->andWhere(['!=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'more':
                            $query->andWhere(['>', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'more_or_equal':
                            $query->andWhere(['>=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'less':
                            $query->andWhere(['<', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'less_or_equal':
                            $query->andWhere(['<=', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'contains':
                            $query->andWhere(['LIKE', $this->columns[$c], $this->values[$c]]);
                            break;
                        case 'no-contains':
                            $query->andWhere(['NOT LIKE', $this->columns[$c], $this->values[$c]]);
                            break;
                    }
                }
                $conditions[] = [$this->columns[$c], $this->conditions[$c], !empty($this->values[$c]) ? $this->values[$c]:'', !empty($this->merge[$c]) ? $this->merge[$c]:''];
				$c++;
			}
			$result = $query->all();
		}
		if($result != null){
			foreach ($result as $item) {
				$answer[$item->cottageNumber] = true;
			}
		}
		return ['status' => 1, 'data' => $answer, 'conditions' => $conditions];
	}
}