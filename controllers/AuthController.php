<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\AuthForm;

class AuthController extends Controller{
	
	public $layout = 'auth';
	
	public function behaviors(){
		return [
			'access' => [
				'class' => AccessControl::class,
				'denyCallback' => function(){
					return $this->redirect('/', 301);
				},
				'rules' => [
					[
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
					],
					[
                        'allow' => true,
                        'actions' => ['logout'],
                        'roles' => ['@'],
					],
					[
                        'allow' => true,
                        'actions' => ['signup'],
                        'roles' => ['?'],
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
	
	public function actionLogin(){
		$auth = new AuthForm(['scenario' => AuthForm::SCENARIO_LOGIN]);
		if(Yii::$app->request->isPost and $auth->load(Yii::$app->request->post()) and $auth->validate() and $auth->login()){
			return $this->goHome();
		}
		return $this->render('login', [
										'auth' => $auth,
									]);
	}
	public function actionLogout(){
		if(Yii::$app->request->isPost){
			Yii::$app->user->logout();
			return $this->redirect('/login', 301);
		}
		return $this->redirect('/', 301);
	}
/* 	public function actionSignup(){
		$auth = new AuthForm(['scenario' => AuthForm::SCENARIO_SIGNUP]);
		if(Yii::$app->request->isPost and $auth->load(Yii::$app->request->post()) and $auth->validate() and $auth->signup()){
			return $this->render('signup-result', [
										'auth' => $auth,
									]);
		}
		return $this->render('signup', [
										'auth' => $auth,
									]);
	} */
}
