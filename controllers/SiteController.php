<?php

namespace app\controllers;

use app\models\Cottage;
use app\models\database\MailingSchedule;
use app\models\MailSettings;
use app\models\migration\Migration;
use app\models\Reminder;
use app\models\TariffsKeeper;
use app\models\Utils;
use app\models\YaAuth;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors():array
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
                        'actions' => [
                            'index',
                            'error',
                            'auth',
                            'mailing-schedule'
                        ],
                        'roles' => ['@'],

                    ],
                    [
                        'allow' => true,
                        'actions' => ['test'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }
    public function actions(){
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // запущу обновление данных
        Utils::startRefreshMainData();
        // Получу информацию о зарегистрированных участках
        $existedCottages = Cottage::getRegister();
        // Проверю заполненность тарифов на данный момент
        if(TariffsKeeper::checkFilling()){
            // проверю заполненность информации о долгах
            Utils::checkDebtFilling($existedCottages);
            return $this->render('index',['existedCottages' => $existedCottages]);
        }
        return $this->redirect('/tariffs/index', 301);
    }
    public function actionAuth()
    {
        if(empty($_SESSION['ya_auth'])){
            $model = new YaAuth();
            if (Yii::$app->request->isGet && !empty(Yii::$app->request->get('code'))) {
                $code = Yii::$app->request->get('code');
                if($model->authenticate($code)){
                    return $this->redirect('/site/auth', 301);
                }
            }
            else {
                return $this->render('auth', ['authModel' => $model]);
            }
        }
        else{
            return $this->redirect('/', 301);
        }
        return false;
    }

    /**
     *
     */
    public function actionTest(): string
    {
        return $this->render('test');
    }

    /**
     * @return string
     */
    public function actionMailingSchedule(): string
    {
        $waitingMessages = MailingSchedule::getWaiting();
        return $this->render('mailing-schedule', ['waiting' => $waitingMessages]);
    }
}
