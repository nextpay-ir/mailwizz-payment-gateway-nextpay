<?php defined('MW_PATH') || exit('No direct script access allowed');
/** 
 * Controller file for service process.
 * 
 * @package MailWizz EMA
 * @subpackage Payment Gateway Nextpay
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2014 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
class Payment_gateway_ext_nextpayController extends Controller
{
    // the extension instance
    public $extension;

    /**
     * Process the Dop
     */
    public function actionDop()
    {
        @session_start();

        if (!Yii::app()->request->isPostRequest) {
            $this->redirect(array('price_plans/index'));
        }

        $postData 			= Yii::app()->params['POST'];
        $postData['custom'] = $_SESSION['custom'];
        $postData['custon'] = $_SESSION['custon'];

        if (!$postData->itemAt('custom')) {
            header('location: '.$_SESSION['cancelUrl']);
            Yii::app()->end();
        }

        $model = $this->extension->getExtModel();
        $postData = Yii::app()->params['POST'];

        $parameters = array
        (
            "api_key"=>$model->api_key,
            "order_id"=> $postData->itemAt('order_id'),
            "amount"=> $postData->itemAt('amount'),
            "callback_uri"=> $postData->itemAt('callback_uri'),
        );


        $soap_client = new SoapClient("http://api.nextpay.org/gateway/token.wsdl", array('encoding' => 'UTF-8'));
        $result = $soap_client->TokenGenerator($parameters);
        $result = $result->TokenGeneratorResult;

        if(intval($result->code) == -1){
            header('location: http://api.nextpay.org/gateway/payment/' . $result->trans_id);
            Yii::app()->end();
        }else{
            header('location: '.$_SESSION['cancelUrl']);
            Yii::app()->end();
        }


    }


    /**
     * Process the IPN
     */
    public function actionIpn()
    {
		@session_start();

        if (!Yii::app()->request->isPostRequest) {
            $this->redirect(array('price_plans/index'));
        }
        
        $postData 			= Yii::app()->params['POST'];
		$postData['custom'] = $_SESSION['custom'];
		$postData['custon'] = $_SESSION['custon'];

        if (!$postData->itemAt('custom')) {
            header('location: '.$_SESSION['cancelUrl']);
            Yii::app()->end();
        }


        $transaction = PricePlanOrderTransaction::model()->findByAttributes(array(
            'payment_gateway_transaction_id' => $postData->itemAt('custom'),
            'status'                         => PricePlanOrderTransaction::STATUS_PENDING_RETRY,
        ));
        if (empty($transaction)) {
            header('location: '.$_SESSION['cancelUrl']);
            Yii::app()->end();
        }
        
        $newTransaction = clone $transaction;
        $newTransaction->transaction_id                 = null;
        $newTransaction->transaction_uid                = null;
        $newTransaction->isNewRecord                    = true;
        $newTransaction->date_added                     = new CDbExpression('NOW()');
        $newTransaction->status                         = PricePlanOrderTransaction::STATUS_FAILED;
        $newTransaction->payment_gateway_response       = print_r($postData->toArray(), true);
        $newTransaction->payment_gateway_transaction_id = $postData->itemAt('trans_id');
        
        $model = $this->extension->getExtModel();

        $parameters = array
        (
            'api_key'	=> $model->api_key,
            'order_id'	=> $postData->itemAt('order_id'),
            'trans_id' 	=> $postData->itemAt('trans_id'),
            'amount'	=> round($transaction->order->total, 2)
        );

		$result = '0';

        $soap_client = new SoapClient('http://api.nextpay.org/gateway/verify.wsdl', array('encoding' => 'UTF-8'));
        $res = $soap_client->PaymentVerification($parameters);
        $res = $res->PaymentVerificationResult;

        if (intval($res->code) == 0) {
            $result = '1';
        } else {
            $result = '0';
        }


        $paymentStatus  = $result=='1'?'completed':'failed';//strtolower(trim($postData->itemAt('payment_status'))); 
        $paymentPending = strpos($paymentStatus, 'pending') === 0;
        $paymentFailed  = strpos($paymentStatus, 'failed') === 0;
        $paymentSuccess = strpos($paymentStatus, 'completed') === 0;
        
        $verified  = $result=='1'?true:false;//strpos(strtolower(trim($request['message'])), 'verified') === 0;
        $order     = $transaction->order;
        
        if ($order->status == PricePlanOrder::STATUS_COMPLETE) {
            $newTransaction->save(false);
				header('location: '.$_SESSION['cancelUrl']);
				echo('double spending');
            Yii::app()->end();
        }
        
        if (!$verified || $paymentFailed) {
            $order->status = PricePlanOrder::STATUS_FAILED;
            $order->save(false);
            
            $transaction->status = PricePlanOrderTransaction::STATUS_FAILED;
            $transaction->save(false);
            
            $newTransaction->save(false);
				header('location: '.$_SESSION['cancelUrl']);
            echo('failed');
            Yii::app()->end();
        }
        
        if ($paymentPending) {
            $newTransaction->status = PricePlanOrderTransaction::STATUS_PENDING_RETRY;
            $newTransaction->save(false);
				echo('pending');
				header('location: '.$_SESSION['cancelUrl']);
            Yii::app()->end();
        }
        
        $order->status = PricePlanOrder::STATUS_COMPLETE;
        $order->save(false);
        
        $transaction->status = PricePlanOrderTransaction::STATUS_SUCCESS;
        $transaction->save(false);
        
        $newTransaction->status = PricePlanOrderTransaction::STATUS_SUCCESS;
        $newTransaction->save(false);
		  header('location: '.$_SESSION['returnUrl']);
		  echo('completed');
        Yii::app()->end();
    }
}