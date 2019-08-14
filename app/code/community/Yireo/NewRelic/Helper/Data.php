<?php
/**
 * NewRelic plugin for Magento 
 *
 * @package     Yireo_NewRelic
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (c) 2013 Yireo (http://www.yireo.com/)
 * @license     Open Source License
 */

class Yireo_NewRelic_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * Check whether this module can be used
     *
     * @access public
     * @param null
     * @return bool
     */
    public function isEnabled()
    {
        if(!extension_loaded('newrelic')) {
            return false;
        }

        return true;
    }

    public function getConfigValue($key = null, $default_value = null)
    {
        $value = Mage::getStoreConfig('newrelic/settings/'.$key);
        if(empty($value)) $value = $default_value;
        return $value;
    }
}
