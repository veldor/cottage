<?php


namespace app\controllers;


use app\models\Checks;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ChecksController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/accessError', 403);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['individual'],
                        'roles' => ['reader'],
                    ],
                ],
            ],
        ];
    }
    public function actionIndividual(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Checks::checkIndividuals()){
            return ['hasErrors' => 1];
        }
        return ['hasErrors' => 0];
    }
}