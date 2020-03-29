<?php


namespace app\controllers;


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
                        'actions' => ['change'],
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
}