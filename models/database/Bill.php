<?php


namespace app\models\database;


use app\models\CashHandler;
use Yii;
use yii\db\ActiveRecord;

/**
 * Class Table_bank_invoices
 * @package app\models
 *
 * @property int $id [int(10) unsigned]
 * @property int $cottage [int(10) unsigned]
 * @property string $payer [varchar(255)]
 * @property string $payer_address
 * @property string $amount [varchar(20)]
 * @property string $service_name [varchar(255)]
 * @property bool $is_printed [tinyint(1)]
 * @property bool $is_saved [tinyint(1)]
 * @property bool $is_sended [tinyint(1)]
 * @property string $bill_destination [varchar(255)]
 * @property string $cottageNumber [varchar(255)]
 * @property int $create_date [int(15) unsigned]
 */

class Bill extends ActiveRecord
{

    public const SCENARIO_CREATE = 'create';
    public const SCENARIO_EDIT = 'edit';


    /**
     * @return array
     */
    public function scenarios():array
    {
        return [
            self::SCENARIO_CREATE => ['cottage', 'payer', 'payer_address', 'amount', 'service_name', 'bill_destination', 'cottageNumber', 'create_date'],
            self::SCENARIO_EDIT => ['amount', 'service_name', 'bill_destination', 'cottage', 'payerId', 'id'],
        ];
    }

    public $payerId;

    /**
     * @return string
     */
    public static function tableName():string
    {
        return 'bills';
    }

    /**
     * @param string $service_name
     * @return string|null
     */
    public static function getType(string $service_name): ?string
    {
        switch ($service_name){
            case 'power':
                return 'Электроэнергия';
            case 'membership':
                return 'Членские взносы';
            case 'target':
                return 'Целевые взносы';
            default:
                return 'Неизвестная цель';
        }
    }

    public static function deleteBill()
    {
        $billId = trim(Yii::$app->request->post('id'));
        if(!empty($billId)){
            $bill = self::findOne(['id' => $billId]);
            if($bill !== null){
                $bill->delete();
                Yii::$app->session->addFlash('success', "Счёт удалён.");
                return ['status' => 1];

            }
            return ['message' => 'Не найден счёт'];
        }
        return ['message' => 'Не найден идентификатор счёта'];
    }

    public static function getMailingTemplate()
    {
        $filename = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/settings/bill_mail_template';
        if(is_file($filename)){
            $content = file_get_contents($filename);
        }
        if(empty($content)){
            return 'Заполните шаблон письма о счёте в разделе настроек';
        }
        return $content;
    }

    public static function saveMailTemplate()
    {
        $text = Yii::$app->request->post('template');
        if (!empty($text)) {
            $encodedText = urldecode($text);
            // сохраню шаблон в файл
            $filename = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/settings/bill_mail_template';
            file_put_contents($filename, $encodedText);
            return ['status' => 1];
        } else {
            return ['message' => 'Шаблон пуст'];
        }
    }

    public function attributeLabels():array
    {
        return [
            'payer' => 'Плательщик',
            'amount' => 'Стоимость',
            'service_name' => 'Тип платежа',
            'bill_destination' => 'Подробности',
            'payerId' => 'Плательщик',
        ];
    }

    /**
     * @return array
     */
    public function rules():array
    {
        return [
            ['service_name', 'in', 'range' => ['power', 'membership', 'target']],
        ];
    }

    public static function getCottageBills(Cottage $cottage)
    {
        return self::find()->where(['cottage' => $cottage->id])->orderBy('cast(create_date as unsigned) desc')->all();
    }

    public static function invoicePrinted(): array
    {
        $billId = trim(Yii::$app->request->post('billNumber'));
        $bill = self::findOne($billId);
        $bill->setScenario(self::SCENARIO_CREATE);
        $bill->is_printed = true;
        $bill->save();
        return ['status' => 1];
    }

    /**
     * @return array
     */
    public static function invoiceSaved(): array
    {
        $billId = trim(Yii::$app->request->post('billNumber'));
        $bill = self::findOne($billId);
        $bill->setScenario(self::SCENARIO_CREATE);
        $bill->is_saved = true;
        $bill->save();
        return ['status' => 1];
    }

    /**
     * @return bool
     */
    public function fillMore(): bool
    {
        // заполню данные об участке и плательщике
        $cottage = Cottage::findOne($this->cottage);
        if(empty($cottage)){
            $this->addError('cotage', 'не найден участок');
            return false;
        }
        $this->cottageNumber = $cottage->num;
        $payer = Payer::findOne($this->payer);
        if(empty($payer)){
            $this->addError('payer', 'не найден плательщик');
            return false;
        }
        $this->payer = $payer->fio;
        $this->payer_address = $payer->address;
        $this->create_date = time();
        $this->amount = CashHandler::toDBCash($this->amount);
        return true;
    }


}