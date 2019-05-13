<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 10:48
 */

namespace app\controllers;

use app\models\PowerCounter;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ServiceController extends Controller
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
                        'actions' => ['change-counter'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    public function actionChangeCounter($cottageNumber){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet){
            Yii::$app->response->format = Response::FORMAT_JSON;
            // верну форму заполнения необходимых данных
            $model = new PowerCounter(['scenario' => PowerCounter::SCENARIO_CHANGE, 'cottageNumber' => $cottageNumber]);
            if(!$model->errors){
                $view = $this->renderAjax('changeCounter', ['matrix' => $model]);
                return ['status' => 1,
                    'data' => $view,
                ];
            }
        }
        elseif(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PowerCounter(['scenario' => PowerCounter::SCENARIO_CHANGE, 'cottageNumber' => $cottageNumber]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->save()) {
                return ['status' => 1];
            }
            else{
                return ['status' => 0, 'errors' => $model->errors];
            }
        }
        return false;
    }
}