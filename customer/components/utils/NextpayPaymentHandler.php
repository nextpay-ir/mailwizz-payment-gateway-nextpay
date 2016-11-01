<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * NextpayPaymentHandler
 * 
 * @package MailWizz EMA
 * @subpackage Payment Gateway Nextpay
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2014 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 * @since 1.0
 */
 
class NextpayPaymentHandler extends PaymentHandlerAbstract
{
    // render the payment form
    public function renderPaymentView()
    {
        $order = $this->controller->getData('order');
        $model = $this->extension->getExtModel();
        
        $cancelUrl = Yii::app()->createAbsoluteUrl('price_plans/index');
        $returnUrl = Yii::app()->createAbsoluteUrl('price_plans/index');
        $dopaymentUrl = Yii::app()->createAbsoluteUrl('payment_gateway_ext_nextpay/dop');
        $notifyUrl = Yii::app()->createAbsoluteUrl('payment_gateway_ext_nextpay/ipn');
        
        $assetsUrl = Yii::app()->assetManager->publish(Yii::getPathOfAlias($this->extension->getPathAlias()) . '/assets/customer', false, -1, MW_DEBUG);
        Yii::app()->clientScript->registerScriptFile($assetsUrl . '/js/payment-form.js');
        
        $customVars = sha1(StringHelper::uniqid());
        $view       = $this->extension->getPathAlias() . '.customer.views.payment-form';

        $csrfTokenName = Yii::app()->request->csrfTokenName;
        $csrfToken = Yii::app()->request->csrfToken;


        $this->controller->renderPartial($view, compact('model', 'order', 'cancelUrl', 'returnUrl', 'dopaymentUrl', 'notifyUrl', 'customVars', 'csrfTokenName', 'csrfToken'));
    }
    
    // mark the order as pending retry
    public function processOrder()
    {
        $request = Yii::app()->request;
        
        if (strlen($request->getPost('custom')) != 40) {
            return false;
        }
        
        $transaction = $this->controller->getData('transaction');
        $order       = $this->controller->getData('order');
        
        $order->status = PricePlanOrder::STATUS_PENDING;
        $order->save(false);
        
        $transaction->payment_gateway_name = 'Nextpay - www.nextpay.ir';
        $transaction->payment_gateway_transaction_id = $request->getPost('custom');
        $transaction->status = PricePlanOrderTransaction::STATUS_PENDING_RETRY;
        $transaction->save(false);
  
        $message = Yii::t('payment_gateway_ext_nextpay', 'Your order is in "{status}" status, it usually takes a few minutes to be processed and if everything is fine, your pricing plan will become active!', array(
            '{status}' => Yii::t('orders', $order->status),
        ));
        
        if ($request->isAjaxRequest) {
            return $this->controller->renderJson(array(
                'result'  => 'success', 
                'message' => $message,
            ));
        }
        
        Yii::app()->notify->addInfo($message);
        $this->controller->redirect(array('price_plans/index'));
    }
}
