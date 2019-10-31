<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 16:35
 */

namespace app\controllers;

use app\models\Cloud;
use app\models\UpdateSite;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class ManagementController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function($rule, $action){
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'get-update-form', 'validate-update', 'create-update', 'check-update', 'install-update', 'send-backup'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    public function actionIndex()
    {
        if (empty($_SESSION['ya_auth'])) {
            return $this->redirect('/site/auth', 301);
        }
        return $this->render('index');
    }
    public function actionGetUpdateForm(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $view = $this->renderAjax('UpdateForm', ['matrix' => $form]);
            return ['status' => 1,
                'data' => $view,
            ];
        }
        else
            throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionValidateUpdate(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $form->load(Yii::$app->request->post());
            return ActiveForm::validate($form);
        }
        else
            throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionCreateUpdate(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $form->load(Yii::$app->request->post());
            return $form->createUpdate();
        }
        else
            throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionCheckUpdate(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try{
                $form = new UpdateSite();
                return $form->checkUpdate();
            }
            catch (\Exception $e){
                if($e->getCode() === 8){
                    return ['status' => 9, 'message' => 'Нужно авторизоваться в яндексе.'];
                }
                return ['status' => 10, 'message' => 'Проблемы с соединением.'];
            }
        }
        else
            throw new NotFoundHttpException("Страница не найдена");
    }
    public function actionInstallUpdate(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            return $form->installUpdate();
        }
        else
            throw new NotFoundHttpException("Страница не найдена");
    }

    public function actionSendBackup(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Cloud::sendBackup();
        }
    }
}