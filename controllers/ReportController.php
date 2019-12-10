<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 08.12.2018
 * Time: 13:52
 */

namespace app\controllers;

use app\models\Cottage;
use app\models\Report;
use yii\filters\AccessControl;
use yii\web\Controller;


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
        echo $id;
        $cottageInfo = Cottage::getCottageByLiteral($id);
        $info = Cottage::getCottageInfo($id);
        var_dump($info);
    }
}