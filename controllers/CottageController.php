<?php

namespace app\controllers;

use app\models\AddCottage;
use app\models\AdditionalCottage;
use app\models\Cottage;
use app\models\Filling;
use app\models\FinesHandler;
use app\models\PersonalTariff;
use ErrorException;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class CottageController extends Controller {
	public function behaviors(): array
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
						'actions' => ['add', 'change', 'save', 'show', 'additional', 'additional-save', 'previous', 'next'],
						'roles' => ['writer'],
					],
				],
			],
		];
	}
    /**
     * @param string $cottageNumber
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAdd($cottageNumber = ''): array
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$form = new AddCottage(['scenario' => AddCottage::SCENARIO_ADD]);
			if ($cottageNumber) {
				$form->cottageNumber = $cottageNumber;
			}
			$form->fillTargets();
			$view = $this->renderAjax('addCottageForm', ['matrix' => $form]);
			return ['status' => 1,
				'data' => $view,
			];
		}

		if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$form = new AddCottage(['scenario' => AddCottage::SCENARIO_ADD]);
			$form->load(Yii::$app->request->post());
			return ActiveForm::validate($form);
		}
		throw new NotFoundHttpException('Страница не найдена');
	}

    /**
     * @param string $cottageNumber
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionChange($cottageNumber = '', $add = false): array
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$cottageNumber = $cottageNumber . ($add ? "-a" : '');
			$cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
			if (AddCottage::checkFullChangable($cottageInfo)) {
				$form = new AddCottage(['scenario' => AddCottage::SCENARIO_FULL_CHANGE]);
			}
			else {
				$form = new AddCottage(['scenario' => AddCottage::SCENARIO_CHANGE]);
			}
			if ($form->fill($cottageInfo)) {
				$view = $this->renderAjax('changeCottageForm', ['matrix' => $form]);
				return ['status' => 1,
					'data' => $view,
				];
			}
			return ['status' => 0,
				'errors' => 'Не удалось получить данные о участке!',
			];
		}
		if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$form = new AddCottage(['scenario' => AddCottage::SCENARIO_CHANGE]);
			$form->load(Yii::$app->request->post());
			return ActiveForm::validate($form);
		}
		throw new NotFoundHttpException('Страница не найдена');
	}

    /**
     * @param $type
     * @return array
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionSave($type): array
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
			$form = false;
			Yii::$app->response->format = Response::FORMAT_JSON;
			if ($type === 'add') {
				$form = new AddCottage(['scenario' => AddCottage::SCENARIO_ADD]);
			}
			elseif ($type === 'change') {
			    $cottageInfo = Cottage::getCottageByLiteral(Yii::$app->request->post()['AddCottage']['cottageNumber']);
				if (AddCottage::checkFullChangable($cottageInfo)) {
					$form = new AddCottage(['scenario' => AddCottage::SCENARIO_FULL_CHANGE,]);
				}
				else {
					$form = new AddCottage(['scenario' => AddCottage::SCENARIO_CHANGE,]);
				}
			}
			elseif ($type === 'change-add') {
			    $cottageInfo = Cottage::getCottageByLiteral(Yii::$app->request->post()['AddCottage']['masterId'] . '-a');
                if (AddCottage::checkFullChangable($cottageInfo)) {
					$form = new AddCottage(['scenario' => AddCottage::SCENARIO_FULL_CHANGE,]);
				}
				else {
					$form = new AddCottage(['scenario' => AddCottage::SCENARIO_CHANGE,]);
				}
                $form->additional = 1;
			}
			$form->load(Yii::$app->request->post());
			if ($form->validate() && $form->save()) {
				return ['status' => 1,
					'message' => 'Данные об участке сохранены',
				];
			}
			return ['status' => 0,
				'errors' => $form->errors,
			];
		}
		throw new NotFoundHttpException('Страница не найдена');
	}

    /**
     * @param $cottageNumber
     * @return string
     * @throws NotFoundHttpException
     * @throws ErrorException
     */
	public function actionShow($cottageNumber): string
	{
		// Проверю тарифы за данный месяц. Если они не заполнены- решу проблему :)
		if (Filling::checkTariffsFilling()) {
            FinesHandler::check($cottageNumber);
			$info = new Cottage($cottageNumber);
			// посчитаю пени
			if (PersonalTariff::checkTariffsFilling($info['globalInfo'])) {
				$unfliiedInfo = PersonalTariff::getUnfilledInfo($info['globalInfo']);
				return $this->render('fill-individual-tariff', ['info' => $unfliiedInfo]);
			}
			if ($info['globalInfo']->haveAdditional && $info->additionalCottageInfo['cottageInfo']->isMembership) {
				if (PersonalTariff::checkTariffsFilling($info->additionalCottageInfo['cottageInfo'])) {
					$unfliiedInfo = PersonalTariff::getUnfilledInfo($info->additionalCottageInfo['cottageInfo']);
					return $this->render('fill-individual-additional-tariff', ['info' => $unfliiedInfo]);
				}
			}
			return $this->render('show', ['cottageInfo' => $info]);
		}
		return $this->render('fill-tariff');
	}

	public function actionAdditional($cottageNumber)
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$model = new AdditionalCottage(['scenario' => AdditionalCottage::SCENARIO_CREATE]);
			$model->fill($cottageNumber);
			return $this->renderAjax('createAdditionalCottage', ['matrix' => $model]);
		}
		if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$model = new AdditionalCottage(['scenario' => AdditionalCottage::SCENARIO_CREATE]);
			$model->load(Yii::$app->request->post());
			return ActiveForm::validate($model);
		}
		return false;
	}

    /**
     * @return array|bool
     * @throws \yii\base\ErrorException
     */
	public function actionAdditionalSave()
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
			Yii::$app->response->format = Response::FORMAT_JSON;
			$model = new AdditionalCottage(['scenario' => AdditionalCottage::SCENARIO_CREATE]);
			$model->load(Yii::$app->request->post());
			if ($model->validate()) {
				return $model->create();
			}
			return $model->errors;
		}
		return false;
	}

	public function actionPrevious(){
	    $url = Cottage::getPreviousCottage();
        return $this->redirect($url, 301);
    }
	public function actionNext(){
	    $url = Cottage::getNextCottage();
        return $this->redirect($url, 301);
    }
}
