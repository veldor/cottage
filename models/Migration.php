<?php


namespace app\models;


use app\models\database\Cottage;
use app\models\database\Mail;
use app\models\database\Payer;
use app\models\database\Phone;
use app\models\utils\DbTransaction;
use Yii;
use yii\web\NotFoundHttpException;

class Migration
{
    public static function migrate()
    {
        $transaction = new DbTransaction();
        $file = dirname(Yii::getAlias('@webroot') . './/') . '/import/cottages.xml';
        if (is_file($file)) {
            $content = file_get_contents($file);
            $xmlHandler = new DOMHandler($content);
            $cottages = $xmlHandler->query('//record');
            if (!empty($cottages)) {
                /** @var \DOMElement $cottage */
                foreach ($cottages as $cottage) {
                    $cottageNumberChild = $cottage->getElementsByTagName('Cottage');
                    $cottageNumber = trim($cottageNumberChild->item(0)->textContent);
                    // найду участок
                    try {
                        $db_cottage = Cottage::getCottage($cottageNumber);
                        $db_cottage->setScenario(Cottage::SCENARIO_EDIT);
                    } catch (NotFoundHttpException $e) {
                        // создам новый участок
                        $db_cottage = new Cottage(['scenario' => Cottage::SCENARIO_CREATE]);
                        $db_cottage->num = $cottageNumber;
                    }
                    $cottageSquareChild = $cottage->getElementsByTagName('Square');
                    if ($cottageSquareChild->length > 0) {
                        $cottageSquare = $cottageSquareChild->item(0)->textContent;
                        if (!empty($cottageSquare)) {
                            $db_cottage->square = trim($cottageSquare);
                        }
                    }
                    $cottageMembershipChild = $cottage->getElementsByTagName('Membership');
                    if ($cottageMembershipChild->length > 0) {
                        $cottageMembership = $cottageMembershipChild->item(0)->textContent;
                        if (!empty($cottageMembership)) {
                            $db_cottage->membership = GrammarHandler::clearWhitespaces(trim($cottageMembership));
                        }
                    }
                    $cottageRightsChild = $cottage->getElementsByTagName('Rights');
                    if ($cottageRightsChild->length > 0) {
                        $cottageRights = $cottageRightsChild->item(0)->textContent;
                        if (!empty($cottageRights)) {
                            $db_cottage->rigths = GrammarHandler::clearWhitespaces(trim($cottageRights));
                        }
                    }
                    $db_cottage->save();
                    // теперь - определюсь с владельцами
                    $cottageOwnerChild = $cottage->getElementsByTagName('Name');
                    if ($cottageOwnerChild->length > 0) {
                        $cottageOwnerName = $cottageOwnerChild->item(0)->textContent;
                        if (!empty($cottageOwnerName)) {
                            // проверю, не зарегистрирован ли уже этот владелец
                            $registeredOwner = Payer::getPayerByName($cottageOwnerName);
                            if (empty($registeredOwner)) {
                                $registeredOwner = new Payer(['scenario' => Payer::SCENARIO_CREATE]);
                                $registeredOwner->fio = GrammarHandler::clearWhitespaces(trim($cottageOwnerName));
                                $registeredOwner->cottage = $db_cottage->id;
                                $registeredOwner->save();
                            } else {
                                $registeredOwner->setScenario(Payer::SCENARIO_EDIT);
                            }
                            // добавлю почтовый адрес
                            $addressChild = $cottage->getElementsByTagName('Address');
                            if ($addressChild->length > 0) {
                                $address = $addressChild->item(0)->textContent;
                                if (!empty($address)) {
                                    $registeredOwner->address = GrammarHandler::clearWhitespaces(trim($address));
                                }
                            }
                            // добавлю почтовый адрес
                            $mailChild = $cottage->getElementsByTagName('Email');
                            if ($mailChild->length > 0) {
                                $mail = $mailChild->item(0)->textContent;
                                if (!empty($mail)) {
                                    $mail = trim($mail);
                                    if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                                        // Добавлю адрес почты
                                        $newMail = new Mail(['scenario' => Mail::SCENARIO_CREATE]);
                                        $newMail->fio = $registeredOwner->fio;
                                        $newMail->email = $mail;
                                        $newMail->cottage_num = $db_cottage->id;
                                        $newMail->save();
                                    }
                                }
                            }
                            // добавлю телефон
                            $phoneChild = $cottage->getElementsByTagName('Phone');
                            if ($phoneChild->length > 0) {
                                $phone = $phoneChild->item(0)->textContent;
                                if (!empty($phone)) {
                                    // Добавлю адрес почты
                                    $newPhone = new Phone(['scenario' => Phone::SCENARIO_CREATE]);
                                    $newPhone->fio = $registeredOwner->fio;
                                    $newPhone->phone = GrammarHandler::clearWhitespaces(trim($phone));
                                    $newPhone->cottage_num = $db_cottage->id;
                                    $newPhone->save();
                                }
                            }
                            $registeredOwner->part = 1;
                            $registeredOwner->save();
                        }
                    }
                    $db_cottage->save();
                }
            }
        }
        $transaction->commitTransaction();
    }
}