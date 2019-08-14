<?php
/**
 * (c) 2016 Shoppimon LTD, all rights reserved
 *
 * @author wladyslaw@shoppimon.com
 * @license https://www.shoppimon.com/terms-of-use.html
 */

/**
 * Class Shoppimon_Monitor_MonitorController
 */
class Shoppimon_Monitor_MonitorController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $model = Mage::getModel('Shoppimon_Monitor/Analyzer');
        //token section
        $token = Mage::getSingleton('admin/session')->getShoppimonToken();
        $expireTime = Mage::getSingleton('admin/session')->getShoppimonTokenExpire();
        if ( !$token || !$expireTime || $expireTime < time()) {
            try {
                $model->installToken();
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addWarning(
                    $e->getMessage()
                );
            }
        }

        $this->loadLayout();
        if ( Mage::getSingleton('admin/session')->getShoppimonToken()){
            $model->checkWebsiteId();

            //load the dashboard
            $url = Shoppimon_Monitor_Model_Analyzer::SHOPPIMON_STATIC_FILES . 'shoppimon-magento-extension.html';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            $content = curl_exec($ch);

            if (!$content || strpos($content, 'shoppimon-extension-container') === false) {
                $e = new Exception(
                    'Uh oh! Looks like there are some missing files. Please contact the Shoppimon team ' .
                    'at support@shoppimon.com so we can get you up and running right away.'
                );
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addWarning(
                    $e->getMessage()
                );
            }
            $this->getLayout()->getBlock('shoppimon_monitor_dashboard')
                ->setData('content', $content);
        }
        $this->renderLayout();
    }
}
