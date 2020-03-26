<?php


namespace app\models;


use app\models\database\Bill;
use app\models\database\Cottage;
use app\models\database\Mail;
use app\models\database\MailingSchedule;
use app\models\database\Payer;
use app\models\utils\DbTransaction;
use yii\base\Model;

class MassMembership extends Model
{
    public $quarter;
    public $amount;


    public function attributeLabels(): array
    {
        return [
            'quarter' => 'Полугодие',
            'amount' => 'Стоимость взноса',
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['quarter', 'amount'], 'required'],
        ];
    }

    /**
     *
     */
    public function create(): void
    {
        $transaction = new DbTransaction();
        $amount = CashHandler::toDBCash($this->amount);
        $preferredAmount = round($amount / 2);
        // получу список всех участков
        $cottages = Cottage::getCottages();
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                // найду плательщиков
                $payers = Payer::getCottagePayers($cottage);
                if (!empty($payers)) {
                    // пока выставлю счёт только первому найденному
                    $payer = $payers[0];
                    $bill = new Bill(['scenario' => Bill::SCENARIO_CREATE]);
                    $bill->cottage = $cottage->id;
                    $bill->cottageNumber = $cottage->num;
                    $bill->payer = $payer->fio;
                    $bill->payer_address = $payer->address;
                    // если площадь участка меньше или равна указанному значению- они платят половину цены
                    if (!empty($cottage->square) && $cottage->square <= Cottage::PREFERRED_SQUARE) {
                        $bill->amount = $preferredAmount;
                    } else {
                        $bill->amount = $amount;
                    }
                    $bill->service_name = 'membership';
                    $bill->bill_destination = $this->quarter;
                    $bill->create_date = time();
                    $bill->save();
                    if (!empty($bill->id)) {
                        // после того, как счёт создан- создам рассылку с данными счёта для всех адресов электронной почты, зарегистрированных для данного участка
                        $mails = Mail::getCottageMails($cottage);
                        if (!empty($mails)) {
                            foreach ($mails as $mail) {
                                $newMailSchedule = new MailingSchedule();
                                $newMailSchedule->mailId = $mail->id;
                                $newMailSchedule->billId = $bill->id;
                                $newMailSchedule->save();
                            }
                        }
                    }
                }
            }
        }
        $transaction->commitTransaction();
    }
}