<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 *
 * @property \app\models\User|null $user
 */
class AuthForm extends Model{

    const SCENARIO_LOGIN = 'login';
    const SCENARIO_SIGNUP = 'signup';
    const SCENARIO_LOGOUT = 'logout';

    public $name;
    public $password;
    public $rememberMe = true;

    public $pass;

    private $_user = false;

    public function scenarios(){
        return [
            self::SCENARIO_LOGIN => ['name', 'password'],
            self::SCENARIO_SIGNUP => ['name'],
            self::SCENARIO_LOGOUT => [],
        ];
    }

    public function attributeLabels(){
        return [
            'name' => 'Имя пользователя',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['name', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
            ['name', 'validateUniqueName', 'on' => self::SCENARIO_SIGNUP],
            ['name', 'string', 'min' => 3, 'max' => 100],
            ['name', 'match', 'pattern' => '/^[a-z]*$/iu'],
            ['password', 'string', 'min' => 1,],
        ];
    }
    public function validateUniqueName($attribute){
        if (User::findByUsername($this->name)){
            $this->addError($attribute, 'Пользователь с таким именем уже существует.');
            Yii::$app->session->setFlash('error', 'Пользователь с таким именем уже существует.');
        }
    }
    public function validatePassword($attribute)
    {
        if (!$this->hasErrors()){
            $user = $this->getUser();
            if($user->failed_try > 5){
                $this->addError($attribute, 'Слишком много попыток входа.');
                Yii::$app->session->setFlash('error', 'Слишком много неудачных попыток входа. Повторите попытку через 5 минут.');
            }
            if (!$user || !$user->validatePassword($this->password)) {
                $user->failed_try = $user->failed_try + 1;
                $this->addError($attribute, 'Неверный логин или пароль.');
                Yii::$app->session->setFlash('error', 'Неверный логин или пароль.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login(){
        return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
    }
    /* 	public function signup(){
            $password = Yii::$app->getSecurity()->generateRandomString(10);
            $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $auth_key = Yii::$app->getSecurity()->generateRandomString(32);
            $newuser = new User;
            $newuser->username = $this->name;
            $newuser->auth_key = $auth_key;
            $newuser->password_hash = $hash;
            $newuser->status = 1;
            if($newuser->save()){
                $this->pass = $password;
                // получаем id нового пользователя
                $id = User::findByUsername($this->name)->id;
                $auth = Yii::$app->authManager;
                $authorRole = $auth->getRole('reader');
                $auth->assign($authorRole, $id);
                return true;
            }
        } */

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser(){
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->name);
        }
        return $this->_user;
    }
    /*public function permissions(){
            // Добавление роли =================================================
            $auth = Yii::$app->authManager;
            $managerRole = $auth->getRole('manager');
            $auth->assign($managerRole, 5);

            // добавляем разрешение "readSite"
            $read = $auth->createPermission('read');
            $read->description = 'Возможность чтения';
            $auth->add($read);

            // добавляем разрешение "redactSite"
            $write = $auth->createPermission('write');
            $write->description = 'Возможность редактирования';
            $auth->add($write);

            // добавляем разрешение "manageSite"
            $manage = $auth->createPermission('manage');
            $manage->description = 'Возможность управления';
            $auth->add($manage);


            // добавляем роль "author" и даём роли разрешение "createPost"
            $reader = $auth->createRole('reader');
            $reader->description = 'Учётная запись читателя';
            $auth->add($reader);
            $auth->addChild($reader, $read);
            // добавляем роль "author" и даём роли разрешение "createPost"
            $writer = $auth->createRole('writer');
            $writer->description = 'Учётная запись редактора';
            $auth->add($writer);
            $auth->addChild($writer, $write);
            $auth->addChild($writer, $reader);
            // добавляем роль "author" и даём роли разрешение "createPost"
            $manager = $auth->createRole('manager');
            $manager->description = 'Учётная запись администратора';
            $auth->add($manager);
            $auth->addChild($manager, $manage);
            $auth->addChild($manager, $writer);

            // Назначение ролей пользователям. 1 и 2 это IDs возвращаемые IdentityInterface::getId()
            // обычно реализуемый в модели User.
             $auth->assign($reader, $id);
            $auth->assign($writer, $id);
            $auth->assign($manager, $id);
    }*/
}