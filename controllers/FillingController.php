<?php

namespace app\controllers;

use app\models\Cottage;
use app\models\database\Mail;
use app\models\ExceptionWithStatus;
use app\models\Filling;
use app\models\MembershipHandler;
use app\models\PowerCounters;
use app\models\PowerHandler;
use app\models\Registry;
use app\models\SerialInvoices;
use app\models\TimeHandler;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class FillingController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => static function ($rule, $action) {
                    //return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'view',
                            'fill',
                            'create',
                            'future-quarters',
                            'cancel-power',
                            'fill-current',
                            'get-serial-cottages',
                            'confirm-serial-payments',
                            'fill-missing-individuals',
                            'fill-counters'],
                        'roles' => ['writer'
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string|Response
     * @throws ErrorException
     * @throws ExceptionWithStatus
     */
    public function actionView()
    {
        if (!$model = Filling::getFillingInfo()) {
            return $this->redirect('/tariffs/index', 301);
        }

        if (Yii::$app->request->isPost) {
            $errorMessage = null;
            $emails = Mail::getAllRegistered();
            $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
            $registryModel->file = UploadedFile::getInstances($registryModel, 'file');
            $registryModel->handleRegistry();
            $registryModel->getUnhandled();
            $countersModel = new PowerCounters(['scenario' => PowerCounters::SCENARIO_PARSE]);
            return $this->render('filling', ['countersModel' => $countersModel, 'model' => $registryModel, 'tab' => 'registry', 'errorMessage' => $errorMessage, 'countersData' => null, 'emails' => $emails]);
        }
        $emails = Mail::getAllRegistered();
        $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
        $registryModel->getUnhandled();
        $countersModel = new PowerCounters(['scenario' => PowerCounters::SCENARIO_PARSE]);
        return $this->render('filling', ['countersModel' => $countersModel, 'model' => $registryModel, 'tab' => 'power', 'errorMessage' => null, 'countersData' => null, 'emails' => $emails]);
    }

    /**
     * @param $cottageNumber
     * @param bool $additional
     * @return array|bool
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionCancelPower($cottageNumber, $additional = false)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            $cottage = Cottage::getCottage($cottageNumber, $additional);
            // верну текст подтверждения удаления
            return PowerHandler::checkLastFilledDelete($cottage);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            return PowerHandler::cancelPowerFill($cottageNumber, $additional);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param $cottageNumber int|string
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionFill($cottageNumber = null): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD]);
            $model->load(Yii::$app->request->post());
            if ($model->validate()) {
                return $model->insert();
            }
            return ['status' => 0,
                'errors' => $model->errors,
            ];
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // верну форму для заполнения данных
            $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD]);
            $model->prepare($cottageNumber);
            return ['data' => $this->renderAjax('fillPowerForm', ['model' => $model])];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionFillCurrent($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $status = PowerHandler::checkCurrent($cottageNumber);
            if ($status['status'] === 1) {
                $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD]);
                $model->prepareCurrent($cottageNumber);
                $view = $this->renderAjax('fillPowerForm', ['model' => $model]);
                return ['status' => 1,
                    'data' => $view,
                ];
            }

            if ($status['status'] === 3) {
                // не заполнен тариф на данный месяц
                return ['status' => 3, 'month' => TimeHandler::getCurrentShortMonth()];
            }
            return ['status' => 2];
        }
        return false;
    }

    /**
     * @param $quartersNumber
     * @param $cottageNumber
     * @param bool $additional
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionFutureQuarters($quartersNumber, $cottageNumber, $additional = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MembershipHandler::getFutureQuarters($quartersNumber, $cottageNumber, $additional);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionGetSerialCottages(): string
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->renderPartial('cottages-list', ['cottages' => SerialInvoices::getCottagesInfo()]);

        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionConfirmSerialPayments(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new SerialInvoices(['scenario' => SerialInvoices::SCENARIO_FILL]);
            $model->load(Yii::$app->request->post());
            $bills = $model->makeInvoices();
            return ['status' => 1, 'message' => 'Счета сформированы', 'bills' => $bills];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }


    /**
     * @return string
     * @throws ExceptionWithStatus
     */
    public function actionFillCounters(): string
    {
        $emails = Mail::getAllRegistered();
        $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
        $registryModel->getUnhandled();
        $countersModel = new PowerCounters(['scenario' => PowerCounters::SCENARIO_PARSE]);
        $countersModel->file = UploadedFile::getInstance($countersModel, 'file');
        $countersData = $countersModel->parseIndications();
        return $this->render('filling', ['countersModel' => $countersModel, 'model' => $registryModel, 'tab' => 'counters', 'errorMessage' => '', 'countersData' => $countersData, 'emails' => $emails]);
    }
}
