<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * PaymentGatewayNextpayExtModel
 * 
 * @package MailWizz EMA
 * @subpackage Payment Gateway Nextpay
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link http://www.mailwizz.com/
 * @copyright 2013-2014 MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */
 
class PaymentGatewayNextpayExtModel extends FormModel
{
    
    const STATUS_ENABLED = 'enabled';
    
    const STATUS_DISABLED = 'disabled';
    
    protected $_extensionInstance;
    
    public $api_key;
    
    
    public $status = 'disabled';
    
    public $sort_order = 1;
    
    public function rules()
    {
        $rules = array(
            array('api_key, status, sort_order', 'required'),
            array('api_key', 'safe'),
            array('status', 'in', 'range' => array_keys($this->getStatusesDropDown())),
            array('sort_order', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 999),
            array('sort_order', 'length', 'min' => 1, 'max' => 3),
        );
        
        return CMap::mergeArray($rules, parent::rules());    
    }
    
    public function save()
    {
        $extension  = $this->getExtensionInstance();
        $attributes = array('api_key', 'status', 'sort_order');
        foreach ($attributes as $name) {
            $extension->setOption($name, $this->$name);
        }
        return $this;
    }
    
    public function populate() 
    {
        $extension  = $this->getExtensionInstance();
        $attributes = array('api_key', 'status', 'sort_order');
        foreach ($attributes as $name) {
            $this->$name = $extension->getOption($name, $this->$name);
        }
        return $this;
    }
    
    public function attributeLabels()
    {
        $labels = array(
            'api_key'       => Yii::t('ext_payment_gateway_nextpay', 'Api Key'),
            'status'      => Yii::t('app', 'Status'),
            'sort_order'  => Yii::t('app', 'Sort order'),
        );
        
        return CMap::mergeArray($labels, parent::attributeLabels());    
    }
    
    public function attributePlaceholders()
    {
        $placeholders = array();
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
    
    public function attributeHelpTexts()
    {
        $texts = array(
            'api_key'       => Yii::t('ext_payment_gateway_nextpay', 'Your nextpay api key for auth you with nextpay'),
            'status'      => Yii::t('ext_payment_gateway_nextpay', 'Whether this gateway is enabled and can be used for payments processing'),
            'sort_order'  => Yii::t('ext_payment_gateway_nextpay', 'The sort order for this gateway'),
        );
        
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    public function getStatusesDropDown()
    {
        return array(
            self::STATUS_DISABLED   => Yii::t('app', 'Disabled'),
            self::STATUS_ENABLED    => Yii::t('app', 'Enabled'),
        );
    }
    
    public function getSortOrderDropDown()
    {
        $options = array();
        for ($i = 0; $i < 100; ++$i) {
            $options[$i] = $i;
        }
        return $options;
    }

    
    public function setExtensionInstance($instance)
    {
        $this->_extensionInstance = $instance;
        return $this;
    }
    
    public function getExtensionInstance()
    {
        if ($this->_extensionInstance !== null) {
            return $this->_extensionInstance;
        }
        return $this->_extensionInstance = Yii::app()->extensionsManager->getExtensionInstance('payment-gateway-nextpay');
    }
}
