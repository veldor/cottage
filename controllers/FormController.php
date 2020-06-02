<?php


namespace app\controllers;


use app\models\Cottage;
use app\models\database\Mail;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormController extends Controller
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
                            'mail-add',
                            'mail-change',
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param null $cottageNumber
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionMailAdd($cottageNumber = null): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $cottage = Cottage::getCottageByLiteral($cottageNumber);
            $form = new Mail(['scenario' => Mail::SCENARIO_CREATE]);
            $form->cottage = $cottage;
            $view = $this->renderAjax('add-mail', ['matrix' => $form]);
            return ['status' => 1,
                'header' => 'Добавление адреса электронной почты',
                'data' => $view,
            ];
        }
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new Mail(['scenario' => Mail::SCENARIO_CREATE]);
            $form->load(Yii::$app->request->post());
            if($form->validate()){
                $form->save();
                Yii::$app->session->addFlash('success', 'Добавлен адрес электронной почты.');
                return ['status' => 1];
            }

            return ['message' => $form->getErrors()];
        }
        throw new NotFoundHttpException();
    }


    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionMailChange($id): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = Mail::findOne($id);
            if($form !== null){
                $form->setScenario(Mail::SCENARIO_EDIT);
                $view = $this->renderAjax('change-mail', ['matrix' => $form]);
                return ['status' => 1,
                    'header' => 'Изменение данных адреса электронной почты',
                    'data' => $view,
                ];
            }
        }
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = Mail::findOne($id);
            if($form !== null){
                $form->setScenario(Mail::SCENARIO_EDIT);
                $form->load(Yii::$app->request->post());
                $form->save();
                Yii::$app->session->addFlash('success', 'Данные адреса электронной почты изменены.');
                return ['status' => 1];
            }
        }
        throw new NotFoundHttpException();
    }
}