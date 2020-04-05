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
use ErrorException;
use Exception;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class ManagementController extends Controller
{
    /**
     * @return array
     */
    public function behaviors() :array
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

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetUpdateForm(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $view = $this->renderAjax('UpdateForm', ['matrix' => $form]);
            return ['status' => 1,
                'data' => $view,
            ];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionValidateUpdate(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $form->load(Yii::$app->request->post());
            return ActiveForm::validate($form);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionCreateUpdate(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            $form->load(Yii::$app->request->post());
            return $form->createUpdate();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionCheckUpdate(): ?array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                $form = new UpdateSite();
                return $form->checkUpdate();
            } catch (Exception $e) {
                echo $e->getMessage();
                if ($e->getCode() === 8) {
                    return ['status' => 9, 'message' => 'Нужно авторизоваться в яндексе.'];
                }
                return ['status' => 10, 'message' => 'Проблемы с соединением.'];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array|null
     * @throws NotFoundHttpException
     * @throws ErrorException
     */
    public function actionInstallUpdate(): ?array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new UpdateSite();
            return $form->installUpdate();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSendBackup(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Cloud::sendBackup();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}