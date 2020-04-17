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
use app\models\PersonalTariff;
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
                'denyCallback' => function ($rule, $action) {
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

    public function actionFillPersonal($cottageNumber, $type, $period)
    {
        if (Yii::$app->request->isPost) {
            $form = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_FILL]);
            $form->load(Yii::$app->request->post());
            $form->cottageNumber = $cottageNumber;
            if ($type === 'membership-personal-additional') {
                $form->additional = true;
            }
            if ($form->validate() && $form->saveTariffs()) {
                $referer = $_SERVER['HTTP_REFERER'];
                if ($referer !== Url::current([], true)) {
                    return $this->redirect($referer, 301);
                }
                echo '<script>window.close();</script>';
            }
        } elseif (Yii::$app->request->isGet) {
            if ($type === 'membership-personal-additional') {
                // проверю, нуждаются ли поля в заполнении
                $result = PersonalTariff::getFutureQuarters($cottageNumber, abs(TimeHandler::checkQuarterDifference($period)), true);
            }
            else{
                $result = PersonalTariff::getFutureQuarters($cottageNumber, abs(TimeHandler::checkQuarterDifference($period)));
            }
            // проверю, нуждаются ли поля в заполнении
            if (!empty($result['unfilled'])) {
                // Открою окно с заполнением незаполненных тарифов
                $this->layout = 'empty';
                return $this->render('fillMembershipPersonalTariffs', ['unfilledTariffs' => $result['unfilled'], 'period' => $period, 'cottageNumber' => $cottageNumber]);
            }
            echo '<script>window.close();</script>';
        }
        return false;
    }

    public function actionMakePersonal($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $requirements = PersonalTariff::getRequirements($cottageNumber);
            if(isset($requirements['status']) && $requirements['status'] === 2){
                return $requirements;
            }
            return $this->renderAjax('fillRequirements', ['requirements' => $requirements]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_ENABLE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->save()) {
                return ['status' => 1,
                    'message' => 'Данные об участке сохранены',
                ];
            }
            return ['status' => 2,
                'message' => $model->errors,
            ];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionMakeAdditionalPersonal($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // проверю, если существует неоплаченный счёт- операция невозможна, предложу сначала всё оплатить

            $requirements = PersonalTariff::getRequirements($cottageNumber, true);
            if(isset($requirements['status']) && $requirements['status'] === 2){
                return $requirements;
            }
            return $this->renderAjax('fillRequirements', ['requirements' => $requirements]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_ENABLE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->save()) {
                return ['status' => 1,
                    'message' => 'Данные об участке сохранены',
                ];
            }
            return ['status' => 0,
                'errors' => $model->errors,
            ];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionDisablePersonal($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $requirements = PersonalTariff::disable($cottageNumber);
            if (isset($requirements['status']) && $requirements['status'] === 2) {
                return ['status' => 2, 'message', 'Присутствуют неоплаченные платежи'];
            }
            return $this->renderAjax('disable', ['requirements' => $requirements, 'cottageNumber' => $cottageNumber]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_DISABLE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->disableTariff()) {
                return ['status' => 1,
                    'message' => "Возвращён общий тариф",
                ];
            } else {
                return ['status' => 0,
                    'errors' => $model->errors,
                ];
            }
        }
        return false;
    }
    public function actionDisablePersonalAdditional($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $requirements = PersonalTariff::disable($cottageNumber, true);
            if (isset($requirements['status']) && $requirements['status'] === 2) {
                return ['status' => 2, 'message', 'Присутствуют неоплаченные платежи'];
            }
            if (isset($requirements['status']) && $requirements['status'] === 1) {
                return ['status' => 1, 'message', 'Индивидуальный тарифный план отключен'];
            }
            return $this->renderAjax('disable', ['requirements' => $requirements, 'cottageNumber' => $cottageNumber]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_DISABLE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->disableTariff()) {
                return ['status' => 1,
                    'message' => "Возвращён общий тариф",
                ];
            } else {
                return ['status' => 0,
                    'errors' => $model->errors,
                ];
            }
        }
        return false;
    }

    public function actionChangePersonal($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $availableChanges = PersonalTariff::getAvaliableChanges($cottageNumber);
            if (isset($availableChanges['status']) && $availableChanges['status'] === 2) {
                return ['status' => 2, 'message', 'Присутствуют неоплаченные платежи'];
            }
            elseif (isset($availableChanges['status']) && $availableChanges['status'] === 3){
                return ['status' => 3, 'message', 'Нет доступных для изменения тарифов'];
            }
            return $this->renderAjax('changePersonalTariff', ['requirements' => $availableChanges, 'cottageNumber' => $cottageNumber]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_CHANGE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->saveChanges()) {
                return ['status' => 1,
                    'message' => "Данные об участке сохранены",
                ];
            } else {
                return ['status' => 0,
                    'errors' => $model->errors,
                ];
            }
        }
        return false;
    }
    public function actionChangePersonalAdditional($cottageNumber)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $availableChanges = PersonalTariff::getAvaliableChanges($cottageNumber, true);
            if (isset($availableChanges['status']) && $availableChanges['status'] === 2) {
                return ['status' => 2, 'message', 'Присутствуют неоплаченные платежи'];
            }
            elseif (isset($availableChanges['status']) && $availableChanges['status'] === 3){
                return ['status' => 3, 'message', 'Нет доступных для изменения тарифов'];
            }
            return $this->renderAjax('changeAdditionalPersonalTariff', ['requirements' => $availableChanges, 'cottageNumber' => $cottageNumber]);
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PersonalTariff(['scenario' => PersonalTariff::SCENARIO_CHANGE]);
            $model->load(Yii::$app->request->post());
            if ($model->validate() && $model->saveChanges()) {
                return ['status' => 1,
                    'message' => "Данные об участке сохранены",
                ];
            } else {
                return ['status' => 0,
                    'errors' => $model->errors,
                ];
            }
        }
        return false;
    }

    public function actionShowPersonal($cottageNumber, $additional = false)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return PersonalTariff::showRates($cottageNumber);
        }
        return false;
    }
    public function actionShowPersonalAdditional($cottageNumber, $additional = false)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return PersonalTariff::showRates($cottageNumber, true);
        }
        return false;
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
}