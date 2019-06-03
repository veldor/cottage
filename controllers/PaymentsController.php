<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.09.2018
 * Time: 16:35
 */

namespace app\controllers;

use app\models\Cloud;
use app\models\ComparisonHandler;
use app\models\ComplexPayment;
use app\models\DepositHandler;
use app\models\ExceptionWithStatus;
use app\models\Filling;
use app\models\GlobalActions;
use app\models\Pay;
use app\models\Payments;
use app\models\PDFHandler;
use app\models\SingleHandler;
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
                        'actions' => ['index', 'form', 'save', 'history', 'invoice-show', 'show-previous', 'validate-payment', 'create-complex', 'bill-info', 'print-invoice', 'print-bank-invoice', 'send-bank-invoice', 'send-invoice', 'use-deposit', 'no-use-deposit', 'save-bill', 'get-bills', 'get-pay-confirm-form', 'validate-pay-confirm', 'validate-cash-double', 'validate-single', 'confirm-pay', 'confirm-cash-double', 'delete-bill', 'edit-single', 'direct-to-deposit', 'close', 'show-all-bills', 'chain', 'chain-confirm'],
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
                $form = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY, 'cottageNumber' => $cottageNumber]);
            }
            elseif ($type === 'single-double') {
                $form = new SingleHandler(['scenario' => SingleHandler::SCENARIO_NEW_DUTY, 'cottageNumber' => $cottageNumber]);
                $form->double = true;
            }
            else if ($type === 'complex') {
            	try{
		            if (ComplexPayment::checkUnpayed($cottageNumber, $double)) {
			            throw new InvalidValueException('Имеется неоплаченный счёт. Создание нового невозможно до его оплаты');
		            }
		            $form = new ComplexPayment(['scenario' => ComplexPayment::SCENARIO_CREATE]);
		            $form->loadInfo($cottageNumber, $double);
	            }
	            catch (InvalidValueException $e){
            		$billId = Pay::getUnpayedBillId($cottageNumber);
            		if($billId){
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

    public function actionBillInfo($identificator, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info = ComplexPayment::getBillInfo($identificator, $double);
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

    public function actionSendBankInvoice($identificator, $double = false)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // генерирую PDF
            $info = ComplexPayment::getBankInvoice($identificator, $double);
            $invoice =  $this->renderPartial('bank-invoice-pdf', ['info' => $info]);
            PDFHandler::renderPDF($invoice, $info['billInfo']['billInfo']->id, $info['billInfo']['billInfo']->cottageNumber);
            // отправлю письмо
            $billInfo = ComplexPayment::getBill($identificator, $double);
            $payDetails = Filling::getPaymentDetails($billInfo);
            $message = Cloud::sendInvoiceMail($this->renderPartial('/site/mail', ['billInfo' => $payDetails]), $info);
            $billInfo->isMessageSend = 1;
            $billInfo->save();
            return['status' => 1, 'message' => $message];
        }
            /*// получу данные о платеже
            $info = ComplexPayment::getBankInvoice($identificator, $double);
            $invoice =  $this->renderPartial('bank-invoice-pdf', ['info' => $info]);
            PDFHandler::renderPDF($invoice, $info['billInfo']['billInfo']->id, $info['billInfo']['billInfo']->cottageNumber);
            return $this->renderFile('invoice.pdf');*/
        throw new NotFoundHttpException('Страница не найдена');
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

    public function actionGetPayConfirmForm($identificator, $double = false): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Pay(['scenario' => Pay::SCENARIO_PAY]);
            if ($model->fillInfo($identificator, $double)) {
                $view = $this->renderAjax('payConfirmForm', ['model' => $model]);
                return ['status' => 1, 'view' => $view];
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
            try{
                if ($model->load(Yii::$app->request->post()) && $model->fillInfo($identificator)) {
                    return ActiveForm::validate($model);
                }
            }
            catch (ExceptionWithStatus $e){
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
            else{
                return ['status' => 2, 'message' => $model->errors];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionDeleteBill($identificator, $double = false): array
    {

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try{
                ComplexPayment::deleteBill($identificator, $double);
                    return ['status' => 1, 'message' => "Счёт успешно закрыт"];
            }
            catch (ExceptionWithStatus $e){
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionEditSingle($type, $cottageNumber, $id){
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            switch ($type){
                case 'delete':
                    return SingleHandler::delete($cottageNumber, $id);
                case 'delete_double':
                    return SingleHandler::delete($cottageNumber, $id, true);
                case 'edit':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    if($model->load(Yii::$app->request->post()) && $model->validate()){
                        return $model->change();
                    }
                    break;
                case 'edit_double':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                    if($model->load(Yii::$app->request->post()) && $model->validate()){
                        return $model->change();
                    }
            }
        }
        elseif (Yii::$app->request->isAjax && Yii::$app->request->isGet){
            switch ($type){
                case 'edit':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                        try{
                            ($model->fill($cottageNumber, $id));
                        }
                        catch(ExceptionWithStatus $e){
                            return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                        }
                        $view = $this->renderAjax('editSingle', ['matrix' => $model]);
                        return ['status' => 1, 'header' => 'Редактирование разового платежа' , 'view' => $view];
                case 'edit_double':
                    $model = new SingleHandler(['scenario' => SingleHandler::SCENARIO_EDIT]);
                        try{
                            ($model->fill($cottageNumber, $id, true));
                        }
                        catch(ExceptionWithStatus $e){
                            return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                        }
                        $view = $this->renderAjax('editSingle', ['matrix' => $model]);
                        return ['status' => 1, 'header' => 'Редактирование разового платежа' , 'view' => $view];
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionDirectToDeposit(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // добавлю данные
            $handler = new DepositHandler(['scenario' => DepositHandler::SCENARIO_DIRECT_ADD]);
            if($handler->load(Yii::$app->request->post()) && $handler->validate()){
                try{
                    return $handler->save();
                }
                catch (ExceptionWithStatus $e){
                    return ['status' => $e->getCode(), 'message' => $e->getMessage()];
                }
            }
            else{
                return $handler->errors;
            }
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionClose($identificator, $double = false){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            try{
                return Pay::closeBill($identificator, $double);
            }
            catch (ExceptionWithStatus $e){
                return ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }
            throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionShowAllBills(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $info = GlobalActions::showAllBills();
            return $this->renderPartial('all-bills', ['info' => $info]);
        }
        throw new NotFoundHttpException('Страница не найдена');
    }

    public function actionChain($billId, $transactionId){
        Yii::$app->response->format = Response::FORMAT_JSON;
        try{
            $info = TransactionsHandler::handle($billId, $transactionId);
            $view = $this->renderPartial("transactionComparsion", ['info' => $info]);
            return ['status' => 1, 'html' => $view];
        }
        catch (ExceptionWithStatus $e){
            return ['status' => 2, 'message' => $e->getMessage(), 'code' => $e->getCode()];
        }
    }
    public function actionChainConfirm(){
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $handler = new ComparisonHandler(['scenario' => ComparisonHandler::SCENARIO_COMPARISON]);
            $handler->load(Yii::$app->request->post());
            return $handler->compare();
        }
        throw new NotFoundHttpException('Страница не найдена');
    }
}