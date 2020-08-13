<?php


namespace app\controllers;


use app\models\database\Mail;
use app\models\ExceptionWithStatus;
use app\models\Fix;
use app\models\PenaltiesHandler;
use app\models\Utils;
use COM;
use Exception;
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
                        'actions' => [
                            'addresses',
                            'count-penalties',
                            'fix',
                            'mail-delete',
                            'delete-target',
                            'fill-membership-accruals',
                            'fill-target-accruals',
                            'refresh-main-data'
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionAddresses(): void
    {
        Utils::makeAddressesList();
        Yii::$app->response->xSendFile('post_addresses.xml');
    }

    public function actionCountPenalties()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return PenaltiesHandler::countPenalties();
    }

    public function actionFix(): array
    {
        // пофиксирую, если есть что
        Yii::$app->response->format = Response::FORMAT_JSON;
        Fix::fix();
        return ['status' => 1];
    }

    /**
     * @return array
     */
    public function actionMailDelete(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Mail::deleteMail();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionFillMembershipAccruals(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::fillMembershipAccruals();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionFillTargetAccruals(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::fillTargetAccruals();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public function actionDeleteTarget(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::deleteTarget();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Сделано'];
    }

    /**
     * @return array
     */
    public function actionRefreshMainData(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Utils::startRefreshMainData();
        return ['status' => 1, 'header' => 'Успешно', 'data' => 'Данные обновлены'];
    }
}