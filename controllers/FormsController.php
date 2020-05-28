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
use app\models\PDFHandler;
use app\models\PowerHandler;
use app\models\Report;
use app\models\Table_power_months;
use app\models\utils\IndividualPower;
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
                            'power-individual'
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
}