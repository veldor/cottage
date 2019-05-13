<?php


namespace app\models;


use DOMDocument;
use yii\base\Model;

class Utils extends Model
{

    public static function makeAddressesList()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?><cottages>';
        // получу все участки
        $cottages = Cottage::getRegistred();
        if(!empty($cottages)){
            foreach ($cottages as $cottage) {
                $xml .= '<cottage><cottage_number>' . $cottage->cottageNumber . '</cottage_number><email>';
                if(!empty($cottage->cottageOwnerEmail) || !empty($cottage->cottageContacterEmail)){
                    $xml .= 'Присутствует';
                }
                else{
                    $xml .= 'Отсутствует';
                }
                $xml .= '</email><name>';
                // теперь обработаю данные почтового адреса
                if(!empty($cottage->cottageOwnerPersonals)){
                    $xml .= $cottage->cottageOwnerPersonals;
                }
                else{
                    $xml .= 'Отсутствует';
                }
                $xml .= '</name>';
                $xml .= '<address>';
                // теперь обработаю данные почтового адреса
                if(!empty($cottage->cottageOwnerAddress)){
                    $xml .= GrammarHandler::clearAddress($cottage->cottageOwnerAddress);
                }
                else{
                    $xml .= 'Отсутствует';
                }
                $xml .= '</address>';
                $xml .= '</cottage>';
            }
        }
        $xml .= '</cottages>';
        $dom = new DOMHandler($xml);
        $output = $dom->saveForFile();
        file_put_contents('post_addresses.xml', $output);
    }
}