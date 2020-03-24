<?php


namespace app\controllers;


use app\models\utils\TotalDutyReport;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class DownloadController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['download'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionDownload($file): void
    {
        switch ($file){
            case 'total-power-report':
                Yii::$app->response->sendFile(TotalDutyReport::getPowerFileName(), 'power_report.xml');
                break;
            case 'total-membership-report':
                Yii::$app->response->sendFile(TotalDutyReport::getMembershipFileName(), 'membership_report.xml');
                break;
            case 'total-target-report':
                Yii::$app->response->sendFile(TotalDutyReport::getTargetFileName(), 'target_report.xml');
                break;
        }
    }
}