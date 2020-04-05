<?php


namespace app\controllers;


use app\models\ExceptionWithStatus;
use app\models\FinesHandler;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FinesController extends Controller
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
                        'actions' => ['change', 'recount-fines'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $action
     * @param $finesId
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionChange($action, $finesId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($action === 'disable') {
            return FinesHandler::disableFine($finesId);
        }
        if($action === 'enable') {
            return FinesHandler::enableFine($finesId);
        }
        throw new NotFoundHttpException();
    }

    /**
     * @param $cottageNumber
     * @return array
     * @throws ExceptionWithStatus
     */
    public function actionRecountFines($cottageNumber): array
    {
        // пересчитаю пени по участку
        FinesHandler::recalculateFines($cottageNumber);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['status' => 2];
    }
}