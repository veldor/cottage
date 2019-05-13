<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 17:03
 */

namespace app\controllers;


use app\models\Report;
use yii\web\Controller;
use yii\filters\AccessControl;

class PrintController extends Controller
{
    public $layout = 'print';
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
                        'actions' => ['cottage-report'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionCottageReport($start, $end, $cottageNumber){
        $start /= 1000;
        $end /= 1000;
        // получу информацию по всем транзакциям участка
        $info = Report::cottageReport($start, $end, $cottageNumber);
        return $this->render('cottage-report', ['transactionsInfo' => $info,'start' => $start,'end' => $end]);
    }
}