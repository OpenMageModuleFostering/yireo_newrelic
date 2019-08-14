<?php
/**
 * NewRelic plugin for Magento 
 *
 * @package     Yireo_NewRelic
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (c) 2013 Yireo (http://www.yireo.com/)
 * @license     Open Source License
 */

class Yireo_NewRelic_Model_Observer
{
    /*
     * Listen to the event controller_action_predispatch
     * 
     * @access public
     * @parameter Varien_Event_Observer $observer
     * @return $this
     */
    public function controllerActionPredispatch($observer)
    {
        // Check whether NewRelic can be used
        if(Mage::helper('newrelic')->isEnabled() == true) {

            // Set the app-name
            $appname = trim(Mage::helper('newrelic')->getConfigValue('appname'));
            $license = trim(Mage::helper('newrelic')->getConfigValue('license'));
            $xmit = true; // @warning: This gives a slight performance overhead - check the NewRelic docs for details
            if(!empty($appname)) newrelic_set_appname($appname, $license, $xmit);

            // Common settings
            newrelic_capture_params(true);
        }
            
        return $this;
    }

    /*
     * Listen to the event core_block_abstract_to_html_after
     * 
     * @access public
     * @parameter Varien_Event_Observer $observer
     * @return $this
     */
    public function coreBlockAbstractToHtmlAfter($observer)
    {
        // Only for the frontend
        if(Mage::app()->getStore()->isAdmin() == true) {
            return $this;
        }

        // Check whether NewRelic can be used
        if(Mage::helper('newrelic')->isEnabled() == false) {
            return $this;
        }

        // Check whether NewRelic Real User Monitoring is active
        if(Mage::helper('newrelic')->getConfigValue('real_user_monitoring') == false) {
            return $this;
        }

        // Set generic data
        newrelic_add_custom_parameter('magento_controller', Mage::getModel('core/url')->getRequest()->getControllerModule());
        newrelic_add_custom_parameter('magento_request', Mage::getModel('core/url')->getRequest()->getRequestUri());
        newrelic_add_custom_parameter('magento_store_id', Mage::app()->getStore()->getId());

        // Get and set customer-data
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerName = trim($customer->getName());
        if(empty($customerName)) $customerName = 'guest';
        $customerEmail = trim($customer->getEmail());
        if(empty($customerEmail)) $customerEmail = 'guest';
        newrelic_add_custom_parameter('magento_customer_email', $customerEmail);
        newrelic_add_custom_parameter('magento_customer_name', $customerName);

        // Get and set product-data
        $product = Mage::registry('current_product');
        if(!empty($product)) {
            $productSku = $product->getSku();
            newrelic_add_custom_parameter('magento_product_name', $product->getName());
            newrelic_add_custom_parameter('magento_product_sku', $product->getSku());
            newrelic_add_custom_parameter('magento_product_id', $product->getId());
        } else {
            $productSku = null;
        }

        // Set user attributes
        newrelic_set_user_attributes($customerEmail, $customerName, $productSku);

        // Fetch objects from this event
        $transport = $observer->getEvent()->getTransport();
        $block = $observer->getEvent()->getBlock();

        // Add JavaScript to the header
        if($block->getNameInLayout() == 'head') {
            $extraHtml = newrelic_get_browser_timing_header();
            $html = $transport->getHtml()."\n".$extraHtml;
            $transport->setHtml($html);
        }

        // Add JavaScript to the footer
        if($block->getNameInLayout() == 'root') {
            $extraHtml = newrelic_get_browser_timing_footer();
            $html = str_replace('</body>', $extraHtml."\n".'</body>', $transport->getHtml());
            $transport->setHtml($html);
        }

        return $this;
    }

    /*
     * Listen to the event model_save_after
     * 
     * @access public
     * @parameter Varien_Event_Observer $observer
     * @return $this
     */
    public function modelSaveAfter($observer)
    {
        // Check whether NewRelic can be used
        if(Mage::helper('newrelic')->isEnabled() == false) {
            return $this;
        }

        $object = $observer->getEvent()->getObject();
        newrelic_custom_metric('Magento/'.get_class($object).'_Save', 1);

        return $this;
    }

    /*
     * Listen to the event model_delete_after
     * 
     * @access public
     * @parameter Varien_Event_Observer $observer
     * @return $this
     */
    public function modelDeleteAfter($observer)
    {
        // Check whether NewRelic can be used
        if(Mage::helper('newrelic')->isEnabled() == false) {
            return $this;
        }

        $object = $observer->getEvent()->getObject();
        newrelic_custom_metric('Magento/'.get_class($object).'_Delete', 1);

        return $this;
    }
}
