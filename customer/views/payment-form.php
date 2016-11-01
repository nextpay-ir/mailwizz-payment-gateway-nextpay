<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @subpackage Payment Gateway Nextpay
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2014 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

@session_start();
$_SESSION['custom'] 		= $customVars;
$_SESSION['returnUrl'] 		= $returnUrl;
$_SESSION['cancelUrl'] 		= $cancelUrl;

echo CHtml::form(Yii::app()->createUrl('payment_gateway_ext_nextpay/dop'), 'post', array(
    'id'         => 'nextpay-hidden-form',
    'data-order' => Yii::app()->createUrl('price_plans/order'),
));
echo CHtml::hiddenField('cmd', '_xclick');
echo CHtml::hiddenField('item_name', Yii::t('price_plans', 'Price plan').': '. $order->plan->name);
echo CHtml::hiddenField('item_number', $order->plan->uid);
echo CHtml::hiddenField('amount', round($order->total, 2));
echo CHtml::hiddenField('currency_code', $order->currency->code);
echo CHtml::hiddenField('no_shipping', 1);
echo CHtml::hiddenField('cancel_return', $cancelUrl);
echo CHtml::hiddenField('return', $returnUrl);
echo CHtml::hiddenField('notify_url', $notifyUrl);
echo CHtml::hiddenField('custom', $customVars);
// Nextpay Data
echo CHtml::hiddenField('amount', round($order->total, 2));
echo CHtml::hiddenField('order_id', $order->plan->uid);
echo CHtml::hiddenField('callback_uri', $notifyUrl);
echo CHtml::hiddenField($csrfTokenName, $csrfToken);
?>
<p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
    Nextpay - www.nextpay.ir <br />
    <?php echo Yii::t('ext_payment_gateway_nextpay', 'You will be redirected to pay securely on nextpay gateway!');?>
</p>
<p><button class="btn btn-success pull-right"><i class="fa fa-credit-card"></i> <?php echo Yii::t('price_plans', 'Submit payment')?></button></p>

<?php echo CHtml::endForm(); ?>
