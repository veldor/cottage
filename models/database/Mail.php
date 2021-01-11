<?php


namespace app\models\database;


use app\models\interfaces\CottageInterface;
use Exception;
use Throwable;
use Yii;
use yii\db\ActiveRecord;

/**
 * Class Mail
 *
 * @package app\models
 *
 * @property int $id [int(10) unsigned]
 * @property int $cottage [int(10)]
 * @property string $email [varchar(255)]
 * @property string $fio [varchar(255)]
 * @property bool $cottage_is_double [tinyint(1)]
 * @property string $comment
 */
class Mail extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'mails';
    }

    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_EDIT = 'edit';

    /**
     * Верну все адреса почты
     * @return Mail[]
     */
    public static function getAllRegistered(): array
    {
        return self::find()->orderBy('cottage')->all();
    }

    /**
     * @param $mail
     * @return Mail
     */
    public static function getMailById($mail): Mail
    {
        return self::findOne($mail);
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_CREATE => ['fio', 'email', 'cottage', 'cottage_is_double', 'comment'],
            self::SCENARIO_EDIT => ['fio', 'email', 'cottage', 'cottage_is_double', 'comment'],
        ];
    }


    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['fio', 'email'], 'required'],
            ['email', 'email'],
            ['cottage_is_double', 'validateDoubleCottageMail'],
        ];
    }

    public function validateDoubleCottageMail($attribute): void
    {
        if ($this->$attribute && (!\app\models\Cottage::getCottageByLiteral($this->cottage))->haveAdditional) {
            $this->addError($attribute, 'У участка нет дополнительного.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'fio' => 'ФИО',
            'email' => 'Адрес электронной почты',
            'cottage_is_double' => 'Почта второго участка',
            'comment' => 'Комментарий',
        ];
    }

    /**
     * @param CottageInterface $cottage
     * @return Mail[]
     */
    public static function getCottageMails(CottageInterface $cottage): array
    {
        if($cottage->isMain()){
            return self::findAll(['cottage' => $cottage->getCottageNumber()]);
        }
        return self::findAll(['cottage' => $cottage->getBaseCottageNumber(), 'cottage_is_double' => true]);
    }


    public static function deleteMail(): array
    {
        $mailId = trim(Yii::$app->request->post('id'));
        if (!empty($mailId)) {
            $mail = self::findOne(['id' => $mailId]);
            if ($mail !== null) {
                try {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $mail->delete();
                } catch (Exception $e) {
                }
                Yii::$app->session->addFlash('success', 'Почта удалёна.');
                return ['status' => 1];

            }
            return ['message' => 'Не найдена почта'];
        }
        return ['message' => 'Не найден идентификатор почты'];
    }

}