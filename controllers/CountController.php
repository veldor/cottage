<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 22.10.2018
 * Time: 16:48
 */

namespace app\controllers;

use app\models\Balance;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;


class CountController extends Controller
{
    public string $layout = 'main';

    public function behaviors():array
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
                        'actions' => ['index', 'show', 'show-transactions', 'show-summary'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $model = new Balance();
        return $this->render('index', ['model' => $model]);
    }

    public function actionShow($type)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $answer = false;
            switch($type){
                case 'day-in': $answer = Balance::getDayIn();
                break;
                case 'month-in': $answer = Balance::getMonthIn();
                break;
                case 'year-in': $answer = Balance::getYearIn();
                break;
                case 'day-out': $answer = Balance::getDayOut();
                break;
                case 'month-out': $answer = Balance::getMonthOut();
                break;
                case 'year-out': $answer = Balance::getYearOut();
                break;
            }
            return $answer;
        }
        return false;
    }
    public function actionShowTransactions($type)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $answer = false;
            switch($type){
                case 'day': $answer = Balance::getDayTransactions();
                break;
                case 'month': $answer = Balance::getMonthTransactions();
                break;
                case 'year': $answer = Balance::getYearTransactions();
            }
            return $answer;
        }
        return false;
    }
    public function actionShowSummary($type)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $answer = false;
            switch($type){
                case 'day': $answer = Balance::getDaySummary();
                break;
                case 'month': $answer = Balance::getMonthSummary();
                break;
                case 'year': $answer = Balance::getYearSummary();
            }
            return $answer;
        }
        return false;
    }
}