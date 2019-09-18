<?php

echo 'mysqldump --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' cottage --skip-add-locks > Z:/sites/cottage/errors/backup.sql';

exec('mysqldump --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' cottage --skip-add-locks > Z:/sites/cottage/errors/backup.sql');
die;

//use app\assets\AppAsset;
//use app\models\CashHandler;
//use app\models\Table_cottages;
//use app\models\Table_payed_power;
//use app\models\Table_power_months;
//use yii\web\View;
//
///* @var $this View */
//
//AppAsset::register($this);
//
//$aprilPowerData = 0;
//$mayPowerData = 0;
//$junePowerData = 0;
//$julePowerData = 0;
//$augustPowerData = 0;
//
//$aprilTPay = 0;
//$mayTPay = 0;
//$juneTPay = 0;
//$juleTPay = 0;
//$augustTPay = 0;
//
//echo "<table class='table table-bordered'><thead><tr><th></th><th colspan='2'>Апрель</th><th colspan='2'>Май</th><th colspan='2'>Июнь</th><th colspan='2'>Июль</th><th colspan='2'>Август</th></tr>
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
//

