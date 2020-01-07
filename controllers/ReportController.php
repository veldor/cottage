<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 08.12.2018
 * Time: 13:52
 */

namespace app\controllers;

use app\models\Cottage;
use app\models\GrammarHandler;
use app\models\Notifier;
use app\models\Report;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;


class ReportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['debt-details', 'send'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    public function actionDebtDetails($type, $cottageNumber){
        $name =  $type . 'DebtReport';
        return Report::$name($cottageNumber);
    }
    public function actionSend($id){
        $root = str_replace('\\', '/', Yii::getAlias('@app'));
        $file = $root . '/public_html/report.pdf';
        $cottageInfo = Cottage::getCottageByLiteral($id);
        $text = GrammarHandler::handleMailText("
Для сверки расчетов Вам направляется отчет по платежам за участок №%COTTAGENUMBER%, произведенным в 2019 году на расчетный счет СНТ «Облепиха».  В отчете указаны даты поступления средств на расчетный счет.  Поскольку при оплате через Сбербанк средства зачисляются на следующий банковский день после платежа, даты фактической оплаты и даты в отчете могут различаться на 1-3 дня.", $cottageInfo, "owner") ;
        Notifier::sendNotificationWithFile($cottageInfo, "Сверка", $text, $file, "отчёт по платежам.pdf");
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['status' => 1, 'message' => "Отправлено"];
    }
}