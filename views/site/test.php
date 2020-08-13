<?php

use app\assets\AppAsset;
use app\models\CashHandler;
use app\models\Cottage;
use app\models\MembershipHandler;
use app\models\PowerHandler;
use app\models\Table_cottages;
use app\models\Table_payed_power;
use app\models\Table_power_months;
use yii\web\View;

/* @var $this View */

AppAsset::register($this);

// получу задолженности по членским
echo '<?xml version="1.0"?><debt>';
$cottages = Cottage::getRegister();
foreach ($cottages as $cottage) {
    // получу задолженность по членским взносам
    $debt = MembershipHandler::getDebt($cottage);
    $duty = 0;
    if(!empty($debt)){
        $details = "";
        foreach ($debt as $item) {
            if($item->quarter < "2020-1"){
                $duty += $item->amount;
                $details .= "{$item->quarter} : {$item->amount} \n";
            }
        }
        if($duty > 0){
            echo "<item><участок>" . $cottage->cottageNumber . "</участок><детали>$details</детали><долг>" . CashHandler::toRubles($duty) . "</долг></item>\n";

        }
    }
    //echo $cottage-> cottageNumber . " > " . $debt->
}
echo "</debt>";
// получу все задолженности по всем участкам
/*$cottages = Cottage::getRegistred();
foreach ($cottages as $cottage) {
    $debt = Cottage::getFullDebt($cottage);
}*/

/*$array = ["Апрель" => "2019-04", "Май" => "2019-05", "Июнь" => "2019-06", "Июль" => "2019-07", "Август" => "2019-08", "Сентябрь" => "2019-09", ];
$cottages = Table_cottages::find()->orderBy('cottageNumber')->all();

//echo "<div class='row for-print'>";


$wholeUsed = 0;
$wholePayed = 0;

foreach ($array as $key => $value) {

    $xml = '<?xml version="1.0" encoding="utf-8"?><month name="' . $key . '">';

    $totalUsedInMonth = 0;
    $totalPayedInMonth = 0;

echo "<div class='col-sm-4'><table class='table table-bordered table-condensed'><caption>$key</caption><thead><tr><th>Участок</th><th>Последние показания</th><th>Расход</th><th>Оплачено</th></tr>";
    // поищу по всем участкам данные за месяц
    foreach ($cottages as $cottage) {
        // поищу данные о потреблённой энергии за месяц
        $used = Table_power_months::findOne(['month' => $value, 'cottageNumber' => $cottage->cottageNumber]);
        if(!empty($used)){
            $payedAmount = 0;
            $payed = Table_payed_power::find()->where(['month' => $value, 'cottageId' => $cottage->cottageNumber])->all();
            if(!empty($payed)){
                foreach ($payed as $payedItem) {
                    $payedAmount += $payedItem->summ;
                }
            }
            $totalUsedInMonth += $used->difference;
            $totalPayedInMonth += $payedAmount;
            $xml .= "<cottage number='{$cottage->cottageNumber}' power_data='{$used->newPowerData}' used_power='{$used->difference}' payed='" . CashHandler::toRubles($payedAmount) . "' />";
            echo "<tr><td><b class='text-info'>{$cottage->cottageNumber}</b></td><td><b class='text-primary'>{$used->newPowerData}</b></td><td>{$used->difference}</td><td>" . CashHandler::toShortSmoothRubles($payedAmount) . "</td></tr>";
        }
        else{
            $xml .= "<cottage number='{$cottage->cottageNumber}' power_data='--' used_power='--' payed='--' />";
            echo "<tr><td>{$cottage->cottageNumber}</td><td>--</td><td>--</td><td>--</td></tr>";
        }
    }
    $wholeUsed += $totalUsedInMonth;
    $wholePayed += $totalPayedInMonth;
    echo "<tr><td><b class='text-info'>Итого за месяц</b></td><td><b class='text-primary'>--</b></td><td>{$totalUsedInMonth}</td><td>" . CashHandler::toShortSmoothRubles($totalPayedInMonth) . "</td></tr>";
    echo "</table></div>";

    $xml .= "</month>";
    file_put_contents('Z:/' . $key . '.xml', $xml);
}

echo "<div class='col-sm-12'>Всего потрачено: $wholeUsed Квт.</div> Всего оплачено: " . CashHandler::toShortSmoothRubles($wholePayed) . "</div>";

echo "</div>";*/



//echo "<table class='table table-bordered'><thead><tr><th></th><th colspan='2'>Апрель</th><th colspan='2'>Май</th><th colspan='2'>Июнь</th><th colspan='2'>Июль</th><th colspan='2'>Август</th><th colspan='2'>Сентябрь</th></tr>
//        <tr><th>Участок</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th></tr></thead>";
//$cottages = Table_cottages::find()->all();
//foreach ($cottages as $cottage) {
//    $aprilData = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => '2019-04']);
//    $aprilCount = $aprilData ? $aprilData->difference : 0;
//    $aprilPayedAmount = 0;
//    if ($aprilCount > 0) {
//        $aprilPayed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => '2019-04'])->all();
//        if (!empty($aprilPayed)) {
//            foreach ($aprilPayed as $aprilPay) {
//                $aprilPayedAmount += $aprilPay->summ;
//            }
//        }
//    }
//    $aprilPowerData += $aprilCount;
//    $aprilTPay += $aprilPayedAmount;
//
//    $mayData = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => '2019-05']);
//    $mayCount = $mayData ? $mayData->difference : 0;
//    $mayPayedAmount = 0;
//    if ($mayCount > 0) {
//        $mayPayed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => '2019-05'])->all();
//        if (!empty($mayPayed)) {
//            foreach ($mayPayed as $mayPay) {
//                $mayPayedAmount += $mayPay->summ;
//            }
//        }
//    }
//    $mayPowerData += $mayCount;
//    $mayTPay += $mayPayedAmount;
//
//    $juneData = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => '2019-06']);
//    $juneCount = $juneData ? $juneData->difference : 0;
//    $junePayedAmount = 0;
//    if ($juneCount > 0) {
//        $junePayed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => '2019-06'])->all();
//        if (!empty($junePayed)) {
//            foreach ($junePayed as $junePay) {
//                $junePayedAmount += $junePay->summ;
//            }
//        }
//    }
//    $junePowerData += $juneCount;
//    $juneTPay += $juneCount;
//
//    $juleData = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => '2019-07']);
//    $juleCount = $juleData ? $juleData->difference : 0;
//    $julePayedAmount = 0;
//    if ($juleCount > 0) {
//        $julePayed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => '2019-07'])->all();
//        if (!empty($julePayed)) {
//            foreach ($julePayed as $julePay) {
//                $julePayedAmount += $julePay->summ;
//            }
//        }
//    }
//    $julePowerData += $juleCount;
//    $juleTPay += $julePayedAmount;
//
//    $augustData = Table_power_months::findOne(['cottageNumber' => $cottage->cottageNumber, 'month' => '2019-08']);
//    $augustCount = $augustData ? $augustData->difference : 0;
//    $augustPayedAmount = 0;
//    if ($augustCount > 0) {
//        $augustPayed = Table_payed_power::find()->where(['cottageId' => $cottage->cottageNumber, 'month' => '2019-08'])->all();
//        if (!empty($augustPayed)) {
//            foreach ($augustPayed as $augustPay) {
//                $augustPayedAmount += $augustPay->summ;
//            }
//        }
//    }
//    $augustPowerData += $augustCount;
//    $augustTPay += $augustPayedAmount;
//
//    // найду сведения об элетроэнергии, потраченной участком
//    echo "<tr><td>{$cottage->cottageNumber}</td>
//            <td>$aprilCount</td><td>" . ($aprilPayedAmount ? CashHandler::toSmoothRubles($aprilPayedAmount) : '') . "</td>
//            <td>$mayCount</td><td>" . ($mayPayedAmount ? CashHandler::toSmoothRubles($mayPayedAmount) : '') . "</td>
//            <td>$juneCount</td><td>" . ($junePayedAmount ? CashHandler::toSmoothRubles($junePayedAmount) : '') . "</td>
//            <td>$juleCount</td><td>" . ($julePayedAmount ? CashHandler::toSmoothRubles($julePayedAmount) : '') . "</td>
//            <td>$augustCount</td><td>" . ($augustPayedAmount ? CashHandler::toSmoothRubles($augustPayedAmount) : '') . "</td>
//        </tr>";
//}
//echo "<tr><td></td>
//         <td>$aprilPowerData</td><td>" . CashHandler::toSmoothRubles($aprilTPay) . "</td>
//         <td>$mayPowerData</td><td>" . CashHandler::toSmoothRubles($mayTPay) . "</td>
//         <td>$junePowerData</td><td>" . CashHandler::toSmoothRubles($juneTPay) . "</td>
//         <td>$julePowerData</td><td>" . CashHandler::toSmoothRubles($juleTPay) . "</td>
//         <td>$augustPowerData</td><td>" . CashHandler::toSmoothRubles($augustTPay) . "</td>
//    </tr>";
//echo "<tfoot><tr><th>Участок</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th><th>Расход</th><th>Оплачено</th></tr><tr><th></th><th colspan='2'>Апрель</th><th colspan='2'>Май</th><th colspan='2'>Июнь</th><th colspan='2'>Июль</th><th colspan='2'>Август</th></tr>
//        </tfoot>";
//echo "</table>";
//
//echo "<h2>Израсходовано электроэнергии всего: " . ($aprilPowerData + $mayPowerData + $junePowerData + $julePowerData + $augustPowerData) . " Квт.ч</h2>";
//echo "<h2>Оплачено электроэнергии всего: " . CashHandler::toSmoothRubles($aprilTPay + $mayTPay + $juneTPay + $juleTPay + $augustTPay) . "</h2>";


