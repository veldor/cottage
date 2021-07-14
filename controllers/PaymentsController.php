<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 16:35
 */

namespace app\controllers;

use app\models\CashHandler;
use app\models\Cloud;
use app\models\ComparisonHandler;
use app\models\ComplexPayment;
use app\models\Cottage;
use app\models\database\MailingSchedule;
use app\models\DepositHandler;
use app\models\ExceptionWithStatus;
use app\models\FinesHandler;
use app\models\GlobalActions;
use app\models\handlers\BillsHandler;
use app\models\Pay;
use app\models\Payments;
use app\models\SingleHandler;
use app\models\Table_payment_bills;
use app\models\TransactionsHandler;
use Yii;
use yii\base\InvalidValueException;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

class PaymentsController extends Controller
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
                            'form',
                            'save',
                            'history',
                            'invoice-show',
                            'show-previous',
                            'validate-payment',
                            'create-complex',
                            'bill-info',
                            'print-invoice',
                            'print-bank-invoice',
                            'send-bank-invoice',
                            'send-invoice',
                            'use-deposit',
                            'no-use-deposit',
                            'save-bill',
                            'get-bills',
                            'get-pay-confirm-form',
                            'confirm-deposit-pay',
                            'get-deposit-pay-confirm-form',
                            'validate-pay-confirm',
                            'validate-cash-double',
                            'validate-single',
                            'confirm-pay',
                            'confirm-cash-double',
                            'delete-bill',
                            'edit-single',
                            'direct-to-deposit',
                            'close',
                            'show-all-bills',
                            'chain',
                            'chain-confirm',
                            'change-transaction-date',
                            'chain-confirm-manual',
                            'count-fines',
                            'bill-reopen',
                            'change-transaction-date',
                            'confirm-payment',
                            'bank-to-deposit'
                        ],
                        'roles' => ['writer'],
                    ],
                ],
            ],
        ];
    }

    public function actionForm($type, $cottageNumber, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = null;
            if ($type === 'single') {
                $form = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY, 'cottageNumber' => $cottageNumber, 'double' => $double]);
            } else if ($type === 'complex') {
                try {
                    $form = new ComplexPayment(['scenario' => ComplexPayment::SCENARIO_CREATE]);
                    $form->loadInfo($cottageNumber, $double);
                } catch (InvalidValueException $e) {
                    $billId = Pay::getUnpayedBillId($cottageNumber);
                    if ($billId) {
                        return ['status' => 2, 'unpayedBillId' => $billId];
                    }
                    throw $e;
                }
            }
            $view = $this->renderAjax($type . 'Form', ['matrix' => $form]);
            return ['status' => 1,
                'data' => $view,
            ];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionSave($type): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($type === 'single') {
                $form = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY]);
                $form->load(Yii::$app->request->post());
                if ($form->validate()) {
                    return $form->insert();
                }
                return ['status' => 0,
                    'errors' => $form->errors
                ];
            }
            if ($type === 'complex') {
                $form = new ComplexPayment(['scenario' => ComplexPayment::SCENARIO_CREATE]);
                $form->load(Yii::$app->request->post());
                if ($form->validate()) {
                    return $form->save();
                }
                return ['status' => 0,
                    'errors' => $form->errors
                ];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionHistory($type, $cottageNumber): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = Payments::paymentsHistory($type, $cottageNumber);
            return ['data' => $data];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionInvoiceShow($invoiceId): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($data = Payments::invoiceInfo($invoiceId)) {
                $view = $this->renderAjax('powerInvoice', ['info' => $data]);
                return ['status' => 1,
                    'data' => $view,
                ];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionValidateSingle()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $form = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY]);
            $form->load(Yii::$app->request->post());
            return ActiveForm::validate($form);
        }
        return false;
    }

    public function actionBillInfo($id, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info = ComplexPayment::getBillInfo($id, $double);
            if ($info) {
                $view = $this->renderAjax('billView', ['info' => $info]);
                return ['status' => 1, 'view' => $view];
            }
            return ['status' => 2, 'errors' => 'Счёт не найден'];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionPrintInvoice($identificator): string
    {
        // получу данные о платеже
        $info = ComplexPayment::getBillInfo($identificator);
        return $this->renderPartial('invoice', ['info' => $info]);
    }

    public function actionPrintBankInvoice($identificator, $double = false): string
    {
        // получу данные о платеже
        $info = ComplexPayment::getBankInvoice($identificator, $double);
        ComplexPayment::setInvoicePrinted($info['billInfo']['billInfo']);
        return $this->renderPartial('bank-invoice', ['info' => $info]);
    }

    public function actionSendBankInvoice($identificator, $double = false): array
    {
        // поставлю в очередь отправки
        Yii::$app->response->format = Response::FORMAT_JSON;
        return MailingSchedule::addBankInvoiceSending($identificator, $double);
    }

    public function actionSendInvoice($identificator)
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info = Payments::invoiceInfo($identificator);
            return ['status' => Cloud::sendInvoice($info)];
        }
        return false;
    }

    public function actionGetBills($cottageNumber, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($data = ComplexPayment::getBills($cottageNumber, $double)) {
                return ['status' => 1, 'data' => $data];
            }
            return ['status' => 2];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionGetPayConfirmForm($identificator, $bankTransaction = null, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            if ($model->fillInfo($identificator, $bankTransaction, $double)) {
                $view = $this->renderAjax('payConfirmForm', ['model' => $model]);
                return ['status' => 1, 'view' => $view, 'header' => 'Распределение средств'];
            }
            return ['status' => 2, 'errors' => $model->errors];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
    public function actionGetDepositPayConfirmForm($identificator, $bankTransaction = null, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // проверю, если средств на депозите хватает для полной оплаты- предложу оплатить всё и закрыть счёт
            $billInfo = ComplexPayment::getBill($identificator, $double);
            $cottageInfo = Cottage::getCottageInfo($billInfo->cottageNumber, $double);
            if(CashHandler::toRubles($cottageInfo->deposit) === 0.0){
                return ['status' => 2, 'message' => 'Нет средств на депозите'];
            }
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            $model->payFromDeposit = true;
            if ($model->fillInfo($identificator, $bankTransaction, $double)) {
                if(CashHandler::toRubles($cottageInfo->deposit) >= CashHandler::toRubles($model->totalSumm) - CashHandler::toRubles($model->payedBefore)){
                    $view = $this->renderAjax('fullPayFromDepositConfirmForm', ['model' => $model]);
                    return ['status' => 1, 'view' => $view, 'header' => 'Распределение средств'];
                }
                $view = $this->renderAjax('payConfirmForm', ['model' => $model]);
                return ['status' => 1, 'view' => $view, 'header' => 'Распределение средств'];
            }
            return ['status' => 2, 'errors' => $model->errors];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionValidatePayConfirm($identificator): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            try {
                $model->load(Yii::$app->request->post());
                if ($model->fillInfo($identificator)) {
                    return ActiveForm::validate($model);
                }
            } catch (ExceptionWithStatus $e) {
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionConfirmPay($identificator): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            $model->load(Yii::$app->request->post());
            $model->fillInfo($identificator);
            if ($model->validate() && $model->confirm()) {
                $session = Yii::$app->session;
                $session->addFlash('success', 'Счёт успешно оплачен.');
                return ['status' => 1];
            }

            return ['status' => 2, 'message' => $model->errors];
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionDeleteBill($identificator, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                ComplexPayment::deleteBill($identificator, $double);
                return ['status' => 1, 'message' => "Счёт успешно закрыт"];
            } catch (ExceptionWithStatus $e) {
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionEditSingle($type, $cottageNumber, $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            switch ($type) {
                case 'delete':
                    return SingleHandler::delete($cottageNumber, $id);
                case 'delete_double':
                    return SingleHandler::delete($cottageNumber, $id, true);
                case 'edit':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    $model->load(Yii::$app->request->post());
                    if ($model->validate()) {
                        return $model->change();
                    }
                    break;
                case 'edit_double':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    $model->load(Yii::$app->request->post());
                    if ($model->validate()) {
                        return $model->change();
                    }
            }
        } elseif (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            switch ($type) {
                case 'edit':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    try {
                        ($model->fill($cottageNumber, $id));
                    } catch (ExceptionWithStatus $e) {
                        return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                    }
                    $view = $this->renderAjax('editSingle', ['matrix' => $model]);
                    return ['status' => 1, 'header' => 'Редактирование разового платежа', 'view' => $view];
                case 'edit_double':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    try {
                        ($model->fill($cottageNumber, $id, true));
                    } catch (ExceptionWithStatus $e) {
                        return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                    }
                    $view = $this->renderAjax('editSingle', ['matrix' => $model]);
                    return ['status' => 1, 'header' => 'Редактирование разового платежа', 'view' => $view];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionDirectToDeposit(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // добавлю данные
            $handler = new DepositHandler(['scenario' => DepositHandler::SCENARIO_DIRECT_ADD]);
            $handler->load(Yii::$app->request->post());
            if ($handler->validate()) {
                try {
                    return $handler->save();
                } catch (ExceptionWithStatus $e) {
                    return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                }
            } else {
                return $handler->errors;
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionClose($identificator, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try {
                return Pay::closeBill($identificator, $double);
            } catch (ExceptionWithStatus $e) {
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionShowAllBills(): string
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info = GlobalActions::showAllBills();
            return $this->renderPartial('all-bills', ['info' => $info]);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @param $billId
     * @param $transactionId
     * @return array|null
     */
    public function actionChain($billId, $transactionId): ?array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $info = TransactionsHandler::handle($billId, $transactionId);
            $view = $this->renderPartial('transactionComparison', ['info' => $info]);
            return ['status' => 1, 'header' => 'Связывание счёта', 'view' => $view, 'delay' => true];
        } catch (ExceptionWithStatus $e) {
            return ['status' => 2, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }

    public function actionChainConfirm(): ?array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $handler = new ComparisonHandler(['scenario' => ComparisonHandler::SCENARIO_COMPARISON]);
            $handler->load(Yii::$app->request->post());
            return $handler->compare();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionChainConfirmManual(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $handler = new ComparisonHandler(['scenario' => ComparisonHandler::SCENARIO_MANUAL_COMPARISON]);
            $handler->load(Yii::$app->request->post());
            return $handler->manualCompare();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionChangeTransactionDate($id = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            // todo разработать обработку платежей дополнительных участков с отдельными хозяевами
            $data = new TransactionsHandler(['scenario' => TransactionsHandler::SCENARIO_CHANGE_DATE]);
            $data->fill($id);
            return ['status' => 1, 'header' => 'Смена даты транзакции', 'view' => $this->renderAjax('changeTransactionDate', ['data' => $data])];
        }

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $model = new TransactionsHandler(['scenario' => TransactionsHandler::SCENARIO_CHANGE_DATE]);
            $model->load(Yii::$app->request->post());
            return $model->changeDate();
        }
        return null;
    }

    /**
     * @param $cottageNumber
     * @return array
     */
    public function actionCountFines($cottageNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $info = FinesHandler::getFines($cottageNumber);
        $text = $this->renderAjax('fines', ['info' => $info]);
        return ['status' => 1, 'text' => $text];
    }

    /**
     * @param $billId
     * @param bool $double
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionBillReopen($billId, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return Pay::reopenBill($billId, $double);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionConfirmPayment()
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            $model->load(Yii::$app->request->post());
            $model->validate();
            return $model->confirm();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    /**
     * @return array
     * @throws ExceptionWithStatus
     */
    public function actionBankToDeposit(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ComparisonHandler::insertToDeposit();
    }

    public function actionConfirmDepositPay(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $billId = Yii::$app->request->post('billId');
            if(!empty($billId)){
                $billInfo = Table_payment_bills::getBill($billId, Yii::$app->request->post('double'));
                if($billInfo !== null){
                    $cottageInfo = Cottage::getCottage($billInfo->cottageNumber, Yii::$app->request->post('double'));
                    // найду сумму, которую осталось оплатить по счёту
                    $totalBillInfo = ComplexPayment::getBillInfo($billInfo);
                    if(CashHandler::toRubles($cottageInfo->deposit) >= CashHandler::toRubles($totalBillInfo['summToPay'])){
                        // проведу оплату с депозита
                        $billInfo->acceptFullPayFromDeposit($cottageInfo);
                    }
                    die;
                }
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}