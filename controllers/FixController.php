<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 05.12.2018
 * Time: 12:19
 */

namespace app\controllers;


use app\models\Fix;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FixController extends Controller
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
                        'actions' => ['refresh-power', 'bills', 'recalculate-power', 'recalculate-membership', 'recalculate-target', 'targets', 'recount-payments'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionRefreshPower(): array
    {

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::refreshPower();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionBills(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::bills();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionTargets(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::checkTargets();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionRecalculatePower(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::recalculateAllPower();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionRecalculateMembership(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::recalculateAllMemberships();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionRecalculateTarget(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Fix::recalculateAllTargets();
        }
	    throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionRecountPayments(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Fix::recountPayments();
            return ['status' => 1];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}