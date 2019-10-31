<?php


namespace app\models;


use app\models\Cottage;
use app\models\DOMHandler;
use app\models\ExceptionWithStatus;
use DateTime;
use Exception;
use yii\base\Model;

class PowerCounters extends Model
{

    const SCENARIO_PARSE = 'parse';

    public $file;

    public function scenarios(): array
    {
        return [
            self::SCENARIO_PARSE => ['file'],
        ];
    }

    public function rules(): array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'xml', 'maxFiles' => 1],
            [['file'], 'required', 'on' => self::SCENARIO_PARSE],
        ];
    }

    function mySort($a, $b)
    {
        return (int) $a->getAttribute('cottage') > (int) $b->getAttribute('cottage');
    }

    public function parseIndications()
    {

        if ($this->validate()) {
            $file = $this->file->tempName;
            $data = file_get_contents($file);
            $dom = new DOMHandler($data);
            // пройдусь по участкам и вынесу показания в таблицу
            $indications = $dom->query('//item');
            $values = iterator_to_array($indications);



            usort($values, array('app\models\PowerCounters','mySort'));

            if($indications->length > 0){
                $content = '<table class="table table-condensed table-striped"><thead><tr><th>Участок</th><th>Старые показания</th><th>Новые показания</th><th>Разница</th><th>Время снятия</th></tr></thead>';
                foreach ($values as $indication) {
                    $attributes = DOMHandler::getElemAttributes($indication);
                    // найду сведения об участке
                    try{
                        $cottageInfo = Cottage::getCottageByLiteral($attributes['cottage']);
                        $cottagePreviousData = $cottageInfo->currentPowerData;
                        $cottageNewData = round($attributes['data']);
                        $time = new DateTime();
                        $time->setTimestamp($attributes['date']);
                        $formattedTime = $time->format('Y-m-d H:i:s');
                        if($cottagePreviousData > $cottageNewData){
                            $difference = '--';
                        }
                        else{
                            $difference = $cottageNewData - $cottagePreviousData;
                        }
                        $content .= "<tr><td>{$cottageInfo->cottageNumber}</td><td>$cottagePreviousData</td><td>$cottageNewData</td><td>$difference</td><td>$formattedTime</td></tr>";
                    }
                    catch (Exception $e){

                    }
                }
                $content .= "</table>";
                return $content;
            }
        }
            throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }
}