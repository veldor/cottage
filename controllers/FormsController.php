<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 04.12.2018
 * Time: 17:03
 */

namespace app\controllers;


use app\models\Cottage;
use app\models\Cottages;
use app\models\database\Accruals_membership;
use app\models\database\Accruals_target;
use app\models\MembershipHandler;
use app\models\PDFHandler;
use app\models\PowerHandler;
use app\models\Report;
use app\models\Table_power_months;
use app\models\TargetHandler;
use app\models\utils\IndividualMembership;
use app\models\utils\IndividualPower;
use app\models\utils\IndividualTarget;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends Controller
{
    public function behaviors():array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/accessError', 403);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'power',
                            'membership',
                            'target',
                            'power-individual',
                            'membership-individual',
                            'target-individual'
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionPower($cottageId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // верну форму с данными по электроэнергии для выбранного участка
        $cottage = Cottage::getCottageByLiteral($cottageId);
        $powerData = PowerHandler::getCottagePowerData($cottage);
        $view = $this->renderAjax('power', ['powerData' => $powerData]);
        return ['status' => 1,
            'header' => 'Электроэнергия по участку',
            'data' => $view,
        ];
    }
    public function actionMembership($cottageId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // верну форму с данными по электроэнергии для выбранного участка
        $cottage = Cottage::getCottageByLiteral($cottageId);
        $data = MembershipHandler::getCottageAccruals($cottage);
        $view = $this->renderAjax('membership', ['data' => $data]);
        return ['status' => 1,
            'header' => 'Членские взносы по участку',
            'data' => $view,
        ];
    }
    public function actionTarget($cottageId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // верну форму с данными по целевым для выбранного участка
        $cottage = Cottage::getCottageByLiteral($cottageId);
        $data = TargetHandler::getCottageAccruals($cottage);
        $view = $this->renderAjax('target', ['data' => $data]);
        return ['status' => 1,
            'header' => 'Целевые взносы по участку',
            'data' => $view,
        ];
    }

    /**
     * @param $monthId
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionPowerIndividual($monthId): array
    {
        $month = Table_power_months::findOne($monthId);
        if($month !== null){
            // получу данные
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (Yii::$app->request->isPost) {
                $form = new IndividualPower();
                $form->load(Yii::$app->request->post());
                $form->submit($month);
                return ['status' => 1];
            }

            $model = new IndividualPower();
            if($month !== null){
                $view = $this->renderAjax('power-individual', ['powerData' => $month, 'model' => $model]);
                return ['status' => 1,
                    'header' => 'Назначить индивидуальный тариф',
                    'data' => $view,
                ];
            }
        }
        throw new NotFoundHttpException();
    }
    /**
     * @param $monthId
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionMembershipIndividual($accrualId): array
    {
        $accrual = Accruals_membership::findOne($accrualId);
        if($accrual !== null){
            // получу данные
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (Yii::$app->request->isPost) {
                $form = new IndividualMembership();
                $form->load(Yii::$app->request->post());
                $form->submit($accrual);
                return ['status' => 1];
            }

            $model = new IndividualMembership();
            if($accrual !== null){
                $view = $this->renderAjax('membership-individual', ['data' => $accrual, 'model' => $model]);
                return ['status' => 1,
                    'header' => 'Назначить индивидуальный тариф',
                    'data' => $view,
                ];
            }
        }
        throw new NotFoundHttpException();
    }
    /**
     * @param $monthId
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionTargetIndividual($accrualId): array
    {
        $accrual = Accruals_target::findOne($accrualId);
        if($accrual !== null){
            // получу данные
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (Yii::$app->request->isPost) {
                $form = new IndividualTarget();
                $form->load(Yii::$app->request->post());
                $form->submit($accrual);
                return ['status' => 1];
            }

            $model = new IndividualTarget();
            if($accrual !== null){
                $view = $this->renderAjax('target-individual', ['data' => $accrual, 'model' => $model]);
                return ['status' => 1,
                    'header' => 'Назначить индивидуальный тариф',
                    'data' => $view,
                ];
            }
        }
        throw new NotFoundHttpException();
    }
}