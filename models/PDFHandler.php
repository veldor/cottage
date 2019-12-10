<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 25.04.2019
 * Time: 8:43
 */

namespace app\models;


use Dompdf\Dompdf;
use Yii;

class PDFHandler
{
    public static function renderPDF($text, $filename, $orientation)
    {
        $dompdf = new Dompdf([
            'defaultFont' => "arial",//делаем наш шрифт шрифтом по умолчанию
        ]);
        $dompdf->loadHtml($text);
// (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($filename, $output);
    }
}