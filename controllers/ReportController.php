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
use app\models\utils\TotalDutyReport;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class ReportController extends Controller
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
                            'debt-details',
                            'send',
                            'remind-membership',
                            'send-membership-remind',
                            'membership-remind-finished',
                            'choose-date'
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionDebtDetails($type, $cottageNumber)
    {
        $name = $type . 'DebtReport';
        return Report::$name($cottageNumber);
    }

    /**
     * @param $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionSend($id): array
    {
        // добавлю в очередь отправки
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return MailingSchedule::scheduleReport($id, Yii::$app->request->post('start'), Yii::$app->request->post('finish'));
        }
        throw new NotFoundHttpException();
    }

    public function actionRemindMembership(): string
    {
        // получу участки, которые не оплатили текущий квартал
        $debtors = Table_cottages::find()->where(['<', 'membershipPayFor', TimeHandler::getCurrentQuarter()])->all();
        return $this->render('debtors_list', ['debtors' => $debtors]);
    }

    public function actionSendMembershipRemind($cottageNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
        MailingSchedule::addSingleMailing($cottageInfo, 'Срок оплаты взносов', 'Сегодня последний день установленного срока для оплаты членского взноса за ' . TimeHandler::getFullFromShortQuarter(TimeHandler::getCurrentQuarter()) . '. Пожалуйста, не забывайте производить платежи своевременно. С завтрашнего дня при отсутствии оплаты начнется начисление пени.');
        return ['status' => 1];
    }

    public function actionMembershipRemindFinished(): void
    {
        // завершу рассылку
        Reminder::finishRemind();
    }

    /**
     * @throws NotFoundHttpException
     * @throws ExceptionWithStatus
     */
    public function actionChooseDate(): array
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $matrix = new TotalDutyReport(['scenario' => TotalDutyReport::SCENARIO_CHOOSE_DATE]);
            if (Yii::$app->request->isGet) {
                // верну форму выбора даты
                $view = $this->renderAjax('/forms/choose-report-date', ['matrix' => $matrix]);
                return ['status' => 1,
                    'header' => 'Составление отчёта на выбранную дату',
                    'data' => $view,
                ];
            }
            if (Yii::$app->request->isPost) {
                // сформирую список
                $matrix->load(Yii::$app->request->post());
                if($matrix->validate()){
                    // верну xml
                    $matrix->createReport();
                    return [
                        'status' => 1,
                        'href' => ['/download/total-power-report', '/download/total-membership-report', '/download/total-target-report'],
                    ];
                }
            }
        }
        throw new NotFoundHttpException();
    }
}