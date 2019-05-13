<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 22.11.2018
 * Time: 19:26
 */

namespace app\models;

use yii\base\Model;

class GrammarHandler extends Model
{
    public const COTTAGE_PERSONALS_PRESET = '%USERNAME%';
    public const COTTAGE_FULL_PERSONALS_PRESET = '%FULLUSERNAME%';
    public const COTTAGE_NUMBER_PRESET = '%COTTAGENUMBER%';
    public const REGISTRIATION_INFO_PRESET = '%REGINFO%';
    public const DUTY_INFO_PRESET = '%DUTYINFO%';

    public static function clearWhitespaces($string){
        // заменю встречающиеся несколько пробелов подряд на один и обрежу пробелы в начале и конце
        $regexp = '/ {2,}/';
        $string = preg_replace($regexp, ' ', $string);
        return trim($string);
    }
    public static function clearAddress($string){
        if(!empty($string)){
            // разобью адрес на категории
            $result = explode('&', $string);
            if(count($result) === 5){
                return trim("{$result[0]}, {$result[1]}, {$result[2]}, дом {$result[3]}, квартира {$result[4]}");
            }
            return trim($string);
        }
        return null;
    }
    public static function personalsToArray($string){
        // извлекаю имя и отчество из персональных данных
        $result = explode(' ', $string);
        if(count($result) === 3){
            return ['lname' => $result[0], 'name' => $result[1], 'fname' => $result[2]];
        }
        return $string;
    }
    public static function handleMonthsDifference($difference){
        if($difference === 1){
            return 'месяц';
        }
        $param = (string) $difference;
        $last = substr($param, strlen($param) - 1);
        $prelast = $param[strlen($param) - 2];
        if($prelast === '1'){
            return "$difference месяцев";
        }
        switch ($last){
            case '1' : return "$difference месяц";
            case '2' :
            case '3' :
            case '4' : return "$difference месяца";
            case '5' :
            case '6' :
            case '7' :
            case '8' :
            case '9' :
            case '0' : return "$difference месяцев";
        }
        return false;
    }
    public static function handlePersonals($name):string
    {
	    if($data = self::personalsToArray($name)){
	        if (is_array($data)){
	            return "{$data['name']} {$data['fname']}";
            }
            else{
	            return $data;
            }

	    }
	    return $name;
    }

    public static function getPersonInitials($cottageOwnerPersonals)
    {
        $personalsArray = self::personalsToArray($cottageOwnerPersonals);
        if(is_array($personalsArray)){
            return $personalsArray['lname'] . ' ' . substr($personalsArray['name'],0, 2) . '. ' . substr($personalsArray['fname'],0, 2) . '.';
        }
        else{
            return $personalsArray;
        }
    }

    public static function insertPersonalAppeal($text, $fullPersonals)
    {
        // найду обращение
        $name = self::handlePersonals($fullPersonals);
        return str_replace('%USERNAME%', $name, $text);
    }

    /**
     * @param $text
     * @param $cottageInfo Table_cottages|Table_additional_cottages
     * @param $type
     * @return mixed
     */
    public static function handleMailText($text, $cottageInfo, $type)
    {
        $main = Cottage::isMain($cottageInfo);
        // проверю наличие в тексте слов, которые надо заменить
        if(strpos($text, self::COTTAGE_PERSONALS_PRESET)){
            if($type === 'owner'){
                $personals = GrammarHandler::handlePersonals($cottageInfo->cottageOwnerPersonals);
            }
            else{
                $personals = GrammarHandler::handlePersonals($cottageInfo->cottageContacterPersonals);
            }
            $text = str_replace(self::COTTAGE_PERSONALS_PRESET, $personals, $text);
        }
        if(strpos($text, self::COTTAGE_FULL_PERSONALS_PRESET)){
            if($type === 'owner'){
                $personals = $cottageInfo->cottageOwnerPersonals;
            }
            else{
                $personals =$cottageInfo->cottageContacterPersonals;
            }
            $text = str_replace(self::COTTAGE_FULL_PERSONALS_PRESET, $personals, $text);
        }
        if(strpos($text, self::COTTAGE_NUMBER_PRESET)){
            if($main){
                $cottageNumber = $cottageInfo->cottageNumber;
            }
            else{
                $cottageNumber = $cottageInfo->masterId . '-a';
            }
            $text = str_replace(self::COTTAGE_NUMBER_PRESET, $cottageNumber, $text);
        }
        if(strpos($text, self::REGISTRIATION_INFO_PRESET)){
            // получу информацию о регистрационных данных участка
            $text = str_replace(self::REGISTRIATION_INFO_PRESET, Notifier::getRegInfo($cottageInfo), $text);
        }
        if(strpos($text, self::DUTY_INFO_PRESET)){
            // получу информацию о регистрационных данных участка
            $text = str_replace(self::DUTY_INFO_PRESET, Filling::getCottageDutyText($cottageInfo), $text);
        }
        return $text;
    }
}