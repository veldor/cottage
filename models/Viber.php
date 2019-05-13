<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 26.04.2019
 * Time: 16:42
 */

namespace app\models;


use app\priv\Info;

class Viber
{

    private static $key = Info::VIBER_TOKEN;

    private static function send($message){
        self::send_message(self::$key, $message);
    }

    private static function send_message($receiverID, $TextMessage)
    {
        $curl = curl_init();
        $json_data = '{
                        "receiver":"' . $receiverID . '",
                        "min_api_version":1,
                        "sender":{
                        "name":"NameBot",
                        "avatar":"avatar.example.com"
                        },
                        "tracking_data":"tracking data",
                        "type":"text",
                        "text":"' . $TextMessage . '"
                        }
                        ';
        $data = json_decode($json_data); // Преобразовываем в json код

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://chatapi.viber.com/pa/send_message",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data), // отправка кода

            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/JSON",
                "X-Viber-Auth-Token: " . Info::VIBER_TOKEN
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

}