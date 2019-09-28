<?php

namespace app\controllers;

use app\models\ExceptionWithStatus;
use app\models\Filling;
use app\models\MembershipHandler;
use app\models\PersonalTariffFilling;
use app\models\PowerHandler;
use app\models\Registry;
use app\models\SerialInvoices;
use app\models\TimeHandler;
use Yii;
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
    public function behaviors() :array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    //return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['view', 'fill', 'create', 'future-quarters', 'cancel-power', 'fill-current', 'get-serial-cottages', 'confirm-serial-payments', 'fill-missing-individuals', 'discard-counter-change'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionView()
    {
        if (!$model = Filling::getFillingInfo())
            return $this->redirect('/tariffs/index', 301);

        if(Yii::$app->request->isPost){
            $errorMessage = null;
            $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
            $registryModel->file = UploadedFile::getInstances($registryModel, 'file');
            $details = null;
            try{
                $details = $registryModel->handleRegistry();
            }
            catch (ExceptionWithStatus $e){
                $errorMessage = $e->getMessage();
            }
            $registryModel->getUnhandled();
            return $this->render('filling', ['info' => $model, 'model' => $registryModel, 'tab' => 'registry', 'errorMessage' => $errorMessage, 'billDetails' => $details]);
        }
        else{
            $registryModel = new Registry(['scenario' => Registry::SCENARIO_PARSE]);
            $registryModel->getUnhandled();
            return $this->render('filling', ['info' => $model, 'model' => $registryModel, 'tab' => 'power', 'errorMessage' => null, 'billDetails' => null]);
        }
    }

    /**
     * @param $cottageNumber
     * @param bool $additional
     * @return array|bool
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionCancelPower($cottageNumber, $additional = false)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return PowerHandler::cancelPowerFill($cottageNumber, $additional);
        } else
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
            try{
                if ($model->validate()){
                    return $model->insert();
                }
                return ['status' => 0,
                    'errors' => $model->errors,
                ];
            }
            catch (ExceptionWithStatus $e){
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
        elseif(Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // верну форму для заполнения данных
            $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD]);
            $model->prepare($cottageNumber);
            return ['data' => $this->renderAjax('fillPowerForm', ['model' => $model])];
        }
        throw new NotFoundHttpException("Страница не найдена");
    }

    public function actionFillCurrent($cottageNumber){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $status = PowerHandler::checkCurrent($cottageNumber);
            if($status['status'] === 1){
                $model = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_RECORD]);
                $model->prepareCurrent($cottageNumber);
                $view = $this->renderAjax('fillPowerForm', ['model' => $model]);
                return ['status' => 1,
                    'data' => $view,
                ];
            }
            elseif ($status['status'] === 3){
                // не заполнен тариф на данный месяц
                return ['status' => 3, 'month' => TimeHandler::getCurrentShortMonth()];
            }
            else return ['status' => 2];
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
    public function actionFutureQuarters($quartersNumber, $cottageNumber, $additional = false)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MembershipHandler::getFutureQuarters($quartersNumber, $cottageNumber, $additional);
            }
        throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionGetSerialCottages()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // получу список всех участков с подробностями
            $model = new SerialInvoices(['scenario' => SerialInvoices::SCENARIO_FILL]);
            return $this->renderPartial('cottages-list', ['cottages' => SerialInvoices::getCottagesInfo()]);

            }
        throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionConfirmSerialPayments()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new SerialInvoices(['scenario' => SerialInvoices::SCENARIO_FILL]);
            $model->load(Yii::$app->request->post());
            $bills = $model->makeInvoices();
            return ['status' =>1 ,'message' => "Счета сформированы", 'bills' => $bills];
            }
        throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionFillMissingIndividuals(){
        $hasError = false;
        if (Yii::$app->request->isPost) {
            $model = new PersonalTariffFilling(['scenario' => PersonalTariffFilling::SCENARIO_FILL]);
            $model->load(Yii::$app->request->post());
            if($model->fill()){
                return $this->redirect('/', 301);
            }
            $hasError = true;
        }
        // получу сведения о незаполненных тарифах
        $cottagesWithMissing = PersonalTariffFilling::getCottagesWithMissing();
        return $this->render("fill-missed-individuals", ['items' => $cottagesWithMissing, 'error' => $hasError]);
    }
    public function actionDiscardCounterChange($cottageNumber, $month){
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return PowerHandler::discardCounterChange($cottageNumber, $month);
        }
    }
}
