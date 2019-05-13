<?php

namespace app\models;


use app\priv\Info;
use Yii;
use yii\base\Model;


class YaAuth extends Model
{
    private $client_id = Info::AUTH_CLIENT_ID; // Id приложения
    private $client_secret = Info::AUTH_SECRET; // Пароль приложения
    private $redirect_uri = Info::AUTH_REDIRECT_URL; // Callback URI
    public $link;

    private $url = 'https://oauth.yandex.ru/authorize';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $params = array(
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'display'       => 'popup'
        );
        $this->link = $this->url . '?' . urldecode(http_build_query($params));
    }
    public function authenticate($code){
        $params = array(
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret
        );

        $url = 'https://oauth.yandex.ru/token';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        $tokenInfo = json_decode($result, true);
        if(!empty($tokenInfo['access_token'])){
            unset($_SESSION['ya_auth']);
            $_SESSION['ya_auth'] = $tokenInfo;
            return true;
        }
        else{
            return false;
        }
    }
    public function uploadFile($filename){

    }
    public function downloadFile($filename){

    }
}