<?php


namespace app\controllers;


use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\database\Cottage;
use app\models\database\Mail;
use app\models\ExceptionWithStatus;
use app\models\Fix;
use app\models\interfaces\CottageInterface;
use app\models\PenaltiesHandler;
use app\models\Table_payed_membership;
use app\models\Table_payed_power;
use app\models\Table_payed_target;
use app\models\Table_power_months;
use app\models\Utils;
use app\priv\Info;
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
                            'refresh-main-data',
                            'synchronize'
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

    /**
     * @throws \JsonException
     */
    public function actionSynchronize($cottageNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return Utils::sendInfoToApi($cottageNumber);
    }
}