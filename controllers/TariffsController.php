<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 16:35
 */

namespace app\controllers;

use app\models\CashHandler;
use app\models\MembershipHandler;
use app\models\PowerHandler;
use app\models\Table_tariffs_power;
use app\models\TargetHandler;
use app\models\TariffsKeeper;
use app\models\TimeHandler;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class TariffsController extends Controller
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
                            'index',
                            'fill',
                            'check',
                            'make-personal',
                            'make-additional-personal',
                            'show-personal',
                            'show-personal-additional',
                            'fill-personal',
                            'change-personal',
                            'change-personal-additional',
                            'disable-personal',
                            'disable-personal-additional',
                            'create-target',
                            'target-more',
                            'change'
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $session = Yii::$app->session;
        if (Yii::$app->request->isPost) {
            // получен запрос на изменение настроек- проверяем, изменяем
            $form = new TariffsKeeper(['scenario' => TariffsKeeper::SCENARIO_FILL]);
            $form->load(Yii::$app->request->post());
            if ($form->save()) {
                $session->setFlash('success', 'Изменения применены.');
                return $this->redirect(Url::base(), 301);
            }
        }
        $tariffsKeeper = new TariffsKeeper();
        if ($tariffsKeeper->fill()) {
            // если заполнены тарифы на данный момент
            return $this->render('index', ['lastTariffs' => $tariffsKeeper]);
        }
        // если не заполнен какой-то из тарифов
        $tariffsKeeper->fillLastData();
        return $this->render('fillTariffs', ['lastTariffs' => $tariffsKeeper]);
    }

    public function actionFill($type, $period)
    {
        if ($type === 'membership') {
            if (Yii::$app->request->isPost) {
                $form = new MembershipHandler(['scenario' => MembershipHandler::SCENARIO_NEW_RECORD]);
                $form->load(Yii::$app->request->post());
                $form->save();
                echo '<script>window.close();</script>';
            } elseif (Yii::$app->request->isGet) {
                // проверю, нуждаются ли поля в заполнении
                if ($result = MembershipHandler::getUnfilled($period)) {
                    // Открою окно с заполнением незаполненных тарифов
                    $this->layout = 'empty';
                    return $this->render('fillMembershipTariffs', ['unfilledTariffs' => $result, 'period' => $period]);
                }
                echo '<script>window.close();</script>';
            }
        }
        if ($type === 'power') {
            if (Yii::$app->request->isPost) {
                $form = new PowerHandler(['scenario' => PowerHandler::SCENARIO_NEW_TARIFF]);
                $form->load(Yii::$app->request->post());
                $form->createTariff();
                echo '<script>window.close();</script>';
            } elseif (Yii::$app->request->isGet) {
                // проверю, нуждаются ли поля в заполнении
                if ($result = PowerHandler::getUnfilled($period)) {
                    // Открою окно с заполнением незаполненных тарифов
                    $this->layout = 'empty';
                    return $this->render('fillPowerTariffs', ['unfilledTariffs' => $result, 'period' => $period]);
                }
                echo '<script>window.close();</script>';
            }
        }
        return false;
    }

    public function actionCheck($type, $from)
    {
        // тут пока только проверка заполенности тарифов на членские взносы с даты, переданной в параметре по сей день
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MembershipHandler::validateFillTariff($from);
        }
        throw new NotFoundHttpException('Страница не существует');
    }

    /**
     * @return array|string|Response
     * @throws NotFoundHttpException
     */
    public function actionCreateTarget()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new TargetHandler(['scenario' => TargetHandler::SCENARIO_NEW_TARIFF]);
            return $this->renderAjax('targetForm', ['matrix' => $form]);
        }
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new TargetHandler(['scenario' => TargetHandler::SCENARIO_NEW_TARIFF]);
            $form->load(Yii::$app->request->post());
            return ActiveForm::validate($form);
        }
        if (Yii::$app->request->isPost) {
            $form = new TargetHandler(['scenario' => TargetHandler::SCENARIO_NEW_TARIFF]);
            $form->load(Yii::$app->request->post());
            if ($form->createTariff()) {
                return $this->redirect(Url::to('/tariffs/index'), 301);
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * Фотма изменения тарифа
     * @param $type
     * @param $period
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionChange($type, $period): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($type === 'power'){
                $tariff = Table_tariffs_power::findOne(['targetMonth' => $period]);
                if($tariff !== null){
                    // переведу float-значения в понятный для формы вид
                    $tariff->powerCost = CashHandler::toJsRubles($tariff->powerCost);
                    $tariff->powerOvercost = CashHandler::toJsRubles($tariff->powerOvercost);
                    // верну форму изменения тарифа
                    $view = $this->renderAjax('/form/change-power', ['matrix' => $tariff]);
                    return ['status' => 1,
                        'header' => 'Изменение данных тарифа электроэнергии на ' . $period,
                        'data' => $view,
                    ];
                }
            }
        }
        if(Yii::$app->request->isAjax && Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return PowerHandler::changeTariff($period);
        }
        throw new NotFoundHttpException();
    }

    public function actionTargetMore($year){
        Yii::$app->response->format = Response::FORMAT_JSON;
        // получу данные по тарифу
        $accruals = TargetHandler::getYearStatistics($year);
        $view = $this->renderAjax('target', ['data' => $accruals]);
        return ['status' => 1,
            'header' => 'Подробности по целевым за ' . $year,
            'data' => $view,
        ];
    }
}