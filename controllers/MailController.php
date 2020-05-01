<?php


namespace app\controllers;


use app\models\database\MailingSchedule;
use app\models\ExceptionWithStatus;
use app\models\Mailing;
use app\models\MailSettings;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MailController extends Controller
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
                            'mailing-create',
                            'clear-mailing-schedule',
                            'cancel-mailing',
                            'send-message',
                            'edit-mail-settings',
                            'get-unsended-messages-count',
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     * @throws ExceptionWithStatus
     */
    public function actionMailingCreate(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Mailing::createMailing();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionClearMailingSchedule(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MailingSchedule::clearSchedule();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function actionCancelMailing(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Mailing::cancelMailing();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     * @throws StaleObjectException
     * @throws Throwable
     */
    public function actionSendMessage(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Mailing::sendMessage();
        }
        throw new NotFoundHttpException();
    }

    public function actionEditMailSettings(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $form = MailSettings::getInstance();
        $form->load(Yii::$app->request->post());
        return $form->saveSettings();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetUnsendedMessagesCount(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['status' => 1, 'count' => MailingSchedule::countWaiting()];
        }
        throw new NotFoundHttpException();
    }
}