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
use app\models\Report;
use yii\web\Controller;
use yii\filters\AccessControl;

class PrintController extends Controller
{
    public string $layout = 'print';
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
                        'actions' => ['cottage-report'],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionCottageReport($start, $end, $cottageNumber): string
    {
        $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
        $start /= 1000;
        $end /= 1000;
        // прибавлю 23 часа к значению конца периода
        $end += 60 * 60 * 20;
        // получу информацию по всем транзакциям участка
        $info = Report::cottageReport($start, $end, $cottageNumber);
        // получу информацию о задолженностях участка
        // сохраню PDF
        $reportPdf =  $this->renderPartial('cottage-report-pdf', ['transactionsInfo' => $info,'start' => $start,'end' => $end, 'cottageInfo' => $cottageInfo]);
        PDFHandler::renderPDF($reportPdf, 'report.pdf', 'landscape');
        return $this->render('cottage-report', ['transactionsInfo' => $info,'start' => $start,'end' => $end, 'cottageInfo' => $cottageInfo]);
    }
}