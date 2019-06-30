<?php


namespace app\controllers;


use app\models\PenaltiesHandler;
use app\models\Utils;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class UtilsController extends Controller
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
                        'actions' => ['addresses', 'count-penalties'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionAddresses(){
        Utils::makeAddressesList();
        Yii::$app->response->xSendFile('post_addresses.xml');
    }
    public function actionCountPenalties(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        return PenaltiesHandler::countPenalties();
    }
}