<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 08.12.2018
 * Time: 13:52
 */

namespace app\controllers;

use app\models\Cottage;
use app\models\database\MailingSchedule;
use app\models\ExceptionWithStatus;
use app\models\Reminder;
use app\models\Report;
use app\models\Table_cottages;
use app\models\TimeHandler;
use app\models\utils\NewFinesHandler;
use app\models\utils\TotalDutyReport;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class AdditionalCottageActionsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/login', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Отображу информацию по участку
     * @param $cottageNumber <b>Cottage number</b>
     * @return string <b>Generated view</b>
     * @throws NotFoundHttpException
     */
    public function actionIndex($cottageNumber): string
    {
        if (Yii::$app->request->isGet) {
            $this->layout = 'rubber_main';
            // тут рассчитаю все пени участка
            try {
                $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
                $finesHandler = new NewFinesHandler($cottageInfo);
                //$finesHandler->handleAllFines();
                return $this->render("index", ['cottage' => $cottageInfo, 'finesHandler' => $finesHandler]);
            } catch (InvalidArgumentException $e) {
                return $this->render("index", ['cottage' => null, 'finesHandler' => null]);
            }
        }
        throw new NotFoundHttpException();
    }
}