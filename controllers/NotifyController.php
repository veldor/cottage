<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 9:01
 */

namespace app\controllers;

use app\models\Cottage;
use app\models\ErrorsHandler;
use app\models\GrammarHandler;
use app\models\LogHandler;
use app\models\Notifier;
use app\models\SerialInvoices;
use Exception;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class NotifyController extends Controller
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
                        'actions' => ['duties', 'reg-info', 'pay', 'pay-double', 'check-unsended', 'resend', 'send-errors', 'get-mail-list', 'mailing'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    public function actionPay($billId)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($result = Notifier::sendPayReminder($billId)) {
               return $result;
            }
        }
        return false;
    }
    public function actionPayDouble($billId)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($result = Notifier::sendDoublePayReminder($billId)) {
               return $result;
            }
        }
        return false;
    }
    public function actionCheckUnsended(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Notifier::checkUnsended();
        }
        return false;
    }
    public function actionSendErrors(){
    	// отправлю письмо с ошибками
	    return ErrorsHandler::sendErrors();
    }


    public function actionDuties($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($result = Notifier::sendDuties($cottageNumber)) {
                return $result;
            }
        }
        return false;
    }
    public function actionRegInfo($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($result = Notifier::sendRegInfo($cottageNumber)) {
                return $result;
            }
        }
        return false;
    }
}