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

    public function actionSendNotification(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Notifier::checkUnsended();
        }
        return false;
    }
    public function actionResend(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Notifier::resendNotifications();
        }
        return false;
    }
    public function actionSendErrors(){
    	// отправлю письмо с ошибками
	    return ErrorsHandler::sendErrors();
    }
    public function actionGetMailList(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // получу список всех участков c почтовыми адресами
            $data = Notifier::getCottagesWithMails();
            return $this->renderPartial('cottage_list', ['info' => $data]);


        }
        throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionMailing($own, $type, $cottageNumber){

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // обработаю текст письма на наличие лексем
            // получу сведения об участке
            $cottageInfo = Cottage::getCottageInfoForMail($own, $type, $cottageNumber);
            $text = GrammarHandler::handleMailText($_POST['text'], $cottageInfo, $type);
            // получу шаблон письма
            $template = $this->renderPartial('/mail/simple_template', ['text' => $text]);
            try{
                Notifier::sendMailing($cottageInfo, $type, $_POST['subject'], $template);
                return ['status' => 1];
            }
            catch (Exception $e){
                return ['status' => 2];
            }
        }
        throw new NotFoundHttpException("Страница не найдена");
    }
}