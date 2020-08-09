<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 11.11.2018
 * Time: 9:03
 */

namespace app\models;

use app\models\database\MailingSchedule;
use app\priv\Info;
use Yii;
use yii\base\Model;


class Notifier extends Model
{
    public static function sendDuties($cottageNumber)
    {
        $cottage = Cottage::getCottageInfo($cottageNumber);
        $dutyText = Filling::getCottageDutyText($cottage);
        $depositInfo = $cottage->deposit > 0 ? "<tr><td><h3>Депозит участка</h3></td></tr><tr><td>Средств на депозите: <b style='color: #3e8f3e'>{$cottage->deposit}</b> &#8381;</td></tr><tr><td><b style='color: #3e8f3e'>Средства, находящиеся на депозите, вы можете использовать для оплаты любых взносов СНТ или потребленной электроэнергии.</b></td></tr>" : '';
        $text = <<<EOT
<table style='max-width: 600px; width: 100%; margin:0; padding: 0; text-align: center;'>
<tr>
<td>
	<h1>Напоминание о задолженностях</h1>
</td>
</tr>
<tr>
<td>
$dutyText
</td>
</tr>
$depositInfo
</table>
EOT;
        return MailingSchedule::addSingleMailing($cottage, 'Напоминание о задолженностях', $text);
    }

    /**
     * @param $cottageInfo Table_additional_cottages|Table_cottages
     * @return string
     */
    public static function getRegInfo($cottageInfo): string
    {
        $main = Cottage::isMain($cottageInfo);
        $depositInfo = $cottageInfo->deposit > 0 ? "<tr><td colspan='2'><h3>Депозит участка</h3></td></tr><tr><td colspan='2'>Средств на депозите: <b style='color: #3e8f3e'>{$cottageInfo->deposit}</b> &#8381;</td></tr><tr><td colspan='2'><b style='color: #3e8f3e'>Средства, находящиеся на депозите, вы можете использовать для оплаты любых взносов СНТ или потребленной электроэнергии.</b></td></tr>" : '';
        if ($main) {
            $regData = Filling::getRow('Номер участка', $cottageInfo->cottageNumber);
        } else {
            $regData = Filling::getRow('Номер участка', $cottageInfo->masterId . '-a');
        }
        $regData .= Filling::getRow('Площадь участка', $cottageInfo->cottageSquare, '', 'м<sup>2</sup>');
        $regData .= Filling::getRow('ФИО владельца участка', $cottageInfo->cottageOwnerPersonals, '');
        if (!empty($cottageInfo->cottageOwnerPhone)) {
            $regData .= Filling::getRow('Номер телефона владельца участка', $cottageInfo->cottageOwnerPhone, '');
        }
        if (!empty($cottageInfo->cottageOwnerEmail)) {
            $regData .= Filling::getRow('Адрес электронной почты владельца участка', $cottageInfo->cottageOwnerEmail, '');
        }
        if (!empty(GrammarHandler::clearAddress($cottageInfo->cottageOwnerAddress))) {
            $regData .= Filling::getRow('Адрес владельца участка', GrammarHandler::clearAddress($cottageInfo->cottageOwnerAddress), '');
        }
        if (!empty($cottageInfo->cottageContacterPersonals)) {
            $regData .= Filling::getRow('ФИО контактного лица', $cottageInfo->cottageContacterPersonals, '');
        }
        if (!empty($cottageInfo->cottageContacterPhone)) {
            $regData .= Filling::getRow('Номер телефона контактного лица', $cottageInfo->cottageContacterPhone, '');
        }
        if (!empty($cottageInfo->cottageContacterEmail)) {
            $regData .= Filling::getRow('Адрес электронной почты контактного лица', $cottageInfo->cottageContacterEmail, '');
        }

        // проверю наличие дополнительного участка
        if (Cottage::isMain($cottageInfo) && $cottageInfo->haveAdditional) {
            /** @var Table_additional_cottages $additionalCottage */
            $additionalCottage = Cottage::getCottageInfo($cottageInfo->cottageNumber, true);
            if (!$additionalCottage->hasDifferentOwner) {
                $regData .= Filling::getSingleRow('<h2>Зарегистрирован дополнительный участок</h2>');
                $regData .= Filling::getEmptyRow();
                $regData .= Filling::getRow('Площадь дополнительного участка', $additionalCottage->cottageSquare, '', 'м<sup>2</sup>');
            }
        }
        $text = <<<EOT
<table style='max-width: 600px; width: 100%; margin:0; padding: 0; text-align: center;'>
$regData
$depositInfo
</table>
EOT;
        return $text;
    }

    public static function sendRegInfo($cottageNumber)
    {
        if ($cottage = Table_cottages::findOne(['cottageNumber' => (int)$cottageNumber])) {
            $dutyText = Filling::getCottageDutyText($cottage);
            $depositInfo = $cottage->deposit > 0 ? "<tr><td colspan='2'><h3>Депозит участка</h3></td></tr><tr><td colspan='2'>Средств на депозите: <b style='color: #3e8f3e'>{$cottage->deposit}</b> &#8381;</td></tr><tr><td colspan='2'><b style='color: #3e8f3e'>Средства, находящиеся на депозите, вы можете использовать для оплаты любых взносов СНТ или потребленной электроэнергии.</b></td></tr>" : '';

            $regData = Filling::getRow('Номер участка', $cottage->cottageNumber);
            $regData .= Filling::getRow('Площадь участка', $cottage->cottageSquare, '', 'м<sup>2</sup>');
            $regData .= Filling::getRow('ФИО владельца участка', $cottage->cottageOwnerPersonals, '');
            if (!empty($cottage->cottageOwnerPhone)) {
                $regData .= Filling::getRow('Номер телефона владельца участка', $cottage->cottageOwnerPhone, '');
            }
            if (!empty($cottage->cottageOwnerEmail)) {
                $regData .= Filling::getRow('Адрес электронной почты владельца участка', $cottage->cottageOwnerEmail, '');
            }
            if (!empty(GrammarHandler::clearAddress($cottage->cottageOwnerAddress))) {
                $regData .= Filling::getRow('Адрес владельца участка', GrammarHandler::clearAddress($cottage->cottageOwnerAddress), '');
            }
            if (!empty($cottage->cottageContacterPersonals)) {
                $regData .= Filling::getRow('ФИО контактного лица', $cottage->cottageContacterPersonals, '');
            }
            if (!empty($cottage->cottageContacterPhone)) {
                $regData .= Filling::getRow('Номер телефона контактного лица', $cottage->cottageContacterPhone, '');
            }
            if (!empty($cottage->cottageContacterEmail)) {
                $regData .= Filling::getRow('Адрес электронной почты контактного лица', $cottage->cottageContacterEmail, '');
            }


            $text = <<<EOT
<table style='max-width: 600px; width: 100%; margin:0; padding: 0; text-align: center;'>
$regData
<tr>
<td colspan="2" style="padding-top: 50px">
$dutyText
</td>
</tr>
$depositInfo
</table>
EOT;
            return MailingSchedule::addSingleMailing($cottage, 'Участок зарегистрирован', $text);
        }
        return false;
    }

    /**
     * @param $billId
     * @return array|bool
     * @throws ExceptionWithStatus
     */
    public static function sendPayReminder($billId)
    {
        $billInfo = Table_payment_bills::find()->where(['id' => $billId])->one();
        if (!empty($billInfo)) {
            $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber);
            $payDetails = Filling::getPaymentDetails($billInfo);
            $dutyText = Filling::getCottageDutyText($cottageInfo);
            $mailBody = $payDetails;
            $mailBody .= $dutyText;
            return MailingSchedule::addSingleMailing($cottageInfo, 'Вам выставлен счёт.', $mailBody);
        }
        return false;
    }

    /**
     * @param $billId
     * @return array|bool
     * @throws ExceptionWithStatus
     */
    public static function sendDoublePayReminder($billId)
    {
        $billInfo = Table_payment_bills_double::find()->where(['id' => $billId])->one();
        if (!empty($billInfo)) {
            $cottageInfo = AdditionalCottage::getCottage($billInfo->cottageNumber);
            $payDetails = Filling::getPaymentDetails($billInfo);
            $dutyText = Filling::getCottageDutyText($cottageInfo);
            $mailBody = $payDetails;
            $mailBody .= $dutyText;
            return MailingSchedule::addSingleMailing($cottageInfo, 'Вам выставлен счёт.', $mailBody);
        }
        return false;
    }

    public static function checkUnsended()
    {
        $answer = [];
        $count = Table_unsended_messages::find()->count();
        if ($count > 0) {
            $answer['status'] = 1;
        } else {
            $answer['status'] = 0;
        }
        // если есть файл с ошибками- добавляю сведения о нём
        if (is_file(Yii::getAlias('@app') . '/errors/errors.txt')) {
            $answer['errorsStatus'] = 1;
        } else {
            $answer['errorsStatus'] = 0;
        }
        return $answer;
    }

    public static function resendNotifications()
    {
        // найду все неотправленные сообщения и отправлю каждое из них.
        $unsended = Table_unsended_messages::find()->all();
        $loadedCottageInfo = [];
        foreach ($unsended as $item) {
            $id = $item->cottageNumber;
            if (empty($loadedCottageInfo[$id])) {
                // загружу данные о участке
                $loadedCottageInfo[$id] = Table_cottages::findOne($id);
            }
            // отправлю сообщение.
            $result = Cloud::sendMessage($loadedCottageInfo[$id], $item->subject, $item->body);
            if ($result['status'] == 1) {
                $item->delete();
            }
        }
        return Table_unsended_messages::find()->count();
    }

    public static function getCottagesWithMails()
    {
        // получу список участков
        $cottages = Cottage::getRegistred();
        $doubleCottages = Cottage::getRegistred(true);
        $cottagesInfo = self::getMails($cottages);
        $doubleCottagesInfo = self::getMails($doubleCottages);
        return ['info' => array_merge($cottagesInfo, $doubleCottagesInfo)];
    }

    private static function getMails($cottages)
    {
        if (!empty($cottages)) {
            $answer = [];
            /** @var Table_cottages|Table_additional_cottages $cottage */
            foreach ($cottages as $cottage) {
                $info = [];
                $main = Cottage::isMain($cottage);
                if ($main) {
                    $info['cottageNumber'] = $cottage->cottageNumber;
                    $info['double'] = false;
                    if (!empty($cottage->cottageOwnerEmail)) {
                        $info['mail'] = true;
                    } else {
                        $info['mail'] = false;
                    }
                    if (!empty($cottage->cottageContacterEmail)) {
                        $info['contacterMail'] = true;
                    } else {
                        $info['contacterMail'] = false;
                    }
                } else {
                    $info['cottageNumber'] = $cottage->masterId;
                    $info['double'] = true;
                    if (!empty($cottage->cottageOwnerEmail)) {
                        $info['mail'] = true;
                    } else {
                        $info['mail'] = false;
                    }
                }
                $answer[] = $info;
            }
            return $answer;
        }
        return [];
    }

    public static function sendMailing($cottageInfo, $type, $subject, $text)
    {
        if ($type === 'owner') {
            $address = $cottageInfo->cottageOwnerEmail;
            $person = $cottageInfo->cottageOwnerPersonals;
        } else {
            $address = $cottageInfo->cottageContacterEmail;
            $person = $cottageInfo->cottageContacterPersonals;
        }
        if (empty($address)) {
            throw new ExceptionWithStatus('Не найден адрес', 2);
        }
        if (empty($subject)) {
            $subject = 'Информация от ' . Info::COTTAGE_NAME;
        }
        // отправлю сообщение с заданным текстом по заданному адресу
        Cloud::send($address, $person, $subject, $text);
    }

    public static function sendNotificationWithFile($cottageInfo, $subject, $body, $file, $filename)
    {
        Cloud::sendMessageWithFile($cottageInfo, $subject, $body, $file, $filename);
    }
}