<?php


namespace app\controllers;


use app\models\CashHandler;
use app\models\ExceptionWithStatus;
use app\models\FinesHandler;
use app\models\PenaltiesHandler;
use app\models\tables\Table_penalties;
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
                        'actions' => [
                            'change',
                            'recount-fines',
                            'lock',
                            'unlock'
                        ],
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
     * @param bool $total
     * @return array
     * @throws ExceptionWithStatus
     */
    public function actionRecountFines($cottageNumber, $total = false): array
    {
        // пересчитаю пени по участку
        FinesHandler::recalculateFines($cottageNumber, (bool)$total);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['status' => 2];
    }

    public function actionLock($id){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if(Yii::$app->request->isGet){
            $fineInfo = Table_penalties::findOne($id);
            $view = $this->renderAjax('lock', ['matrix' => $fineInfo]);
            return ['status' => 1,
                'header' => 'Фиксировать пени',
                'data' => $view,
            ];
        }
        if(Yii::$app->request->isPost){
            $summ = CashHandler::toRubles(Yii::$app->request->post("Table_penalties")['summ']);
            $fine = Table_penalties::findOne($id);
            $fine->summ = $summ;
            $fine->locked = 1;
            $fine->save();
            return [
                'status' => 1,
                'message' => 'Сумма пени заблокирована'
            ];
        }
    }

    public function actionUnlock($id){
        PenaltiesHandler::unlockFine($id);
    }
}