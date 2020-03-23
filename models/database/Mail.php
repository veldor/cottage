<?php


namespace app\models\database;


use app\models\selections\CottageMail;
use Yii;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * Class Table_bank_invoices
 * @package app\models
 *
 * @property int $id [int(10) unsigned]
 * @property int $cottage_num [int(10) unsigned]
 * @property string $email [varchar(255)]
 * @property string $fio [varchar(255)]
 */

class Mail extends ActiveRecord
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_EDIT = 'edit';

    public static function tableName()
    {
        return 'mails';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['fio', 'email', 'cottage_num'],
            self::SCENARIO_EDIT => ['fio', 'email'],
        ];
    }

    public function attributeLabels():array
    {
        return [
            'fio' => 'ФИО',
            'email' => 'Адрес электронной почты',
        ];
    }


    /**
     * @return array
     */
    public function rules():array
    {
        return [
            [['fio', 'email'], 'required'],
            ['email', 'email'],
        ];
    }

    public static function deletePayer()
    {
        $payerId = trim(Yii::$app->request->post('id'));
        if(!empty($payerId)){
            $payer = self::findOne(['id' => $payerId]);
            if(!empty($payer)){
                $payer->delete();
                Yii::$app->session->addFlash('success', "Плательщик удалён.");
                return ['status' => 1];

            }
            return ['message' => 'Не найден плательщик'];
        }
        return ['message' => 'Не найден идентификатор плательщика'];
    }

    public static function getCottageMails(Cottage $cottage)
    {
        return self::findAll(['cottage_num' => $cottage->id]);
    }

    public static function deleteMail()
    {
        $mailId = trim(Yii::$app->request->post('id'));
        if(!empty($mailId)){
            $mail = self::findOne(['id' => $mailId]);
            if(!empty($mail)){
                $mail->delete();
                Yii::$app->session->addFlash('success', "Почта удалёна.");
                return ['status' => 1];

            }
            return ['message' => 'Не найдена почта'];
        }
        return ['message' => 'Не найден идентификатор почты'];
    }

    /**
     * @return CottageMail[]
     */
    public static function getAllMailsByCottages()
    {
        $cottages = Cottage::getCottages();
        $result = [];
        foreach ($cottages as $cottage) {
            $mails = self::getCottageMails($cottage);
            if(!empty($mails)){
                foreach ($mails as $mail) {
                    $newMail = new CottageMail();
                    $newMail->cottageNumber = $cottage->num;
                    $newMail->mail = $mail->email;
                    $newMail->mailId = $mail->id;
                    $newMail->name = $mail->fio;
                    $result[] = $newMail;
                }
            }
        }
        return $result;
    }

    /**
     * @param $mail
     * @return Mail|null
     * @throws NotFoundHttpException
     */
    public static function getMailById($mail)
    {
        $existentMail = self::findOne($mail);
        if(empty($existentMail)){
            throw new NotFoundHttpException();
        }
        return $existentMail;
    }

}