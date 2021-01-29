<?php


namespace app\models\utils;


use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Yii;

class QRImageGenerator
{
    /**
     * Герерация PNG с QR кодом
     * @param $text <b>Текст, который нужно закодировать</b>
     * @return bool <b>Результат</b>
     */
    public function generateQr($text): bool
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'addQuietzone' => true,
            'imageTransparent' => false,
            'scale' => 10,

        ]);

        $data = "hello, world!";

// invoke a fresh QRCode instance
        $qrcode = new QRCode($options);

// ...with additional cache file
        $qrcode->render($text, Yii::$app->basePath . "/files/qr.png");
        return true;
    }
}