<?php
/**
 * (c) 2016 Shoppimon LTD, all rights reserved
 *
 * @author wladyslaw@shoppimon.com
 * @license https://www.shoppimon.com/terms-of-use.html
 */

/**
 * Class Shoppimon_Monitor_Model_Observer
 */
class Shoppimon_Monitor_Model_Observer
{

    public function analyzeData(Varien_Event_Observer $observer)
    {
        $pushUrl = Mage::getStoreConfig('shoppimon/settings/push_url');
        if (strlen($pushUrl) > 0) {
            $resultData = [];
            $analyzeModel = Mage::getModel('Shoppimon_Monitor/Analyzer');

            $analyzeModel->checkWebsiteId();

            $resultData['metadata'] = $analyzeModel->getStoreMetadata();
            $resultData['instrumentation'] = $analyzeModel->getInstrumentation();
            $resultData['bi'] = $analyzeModel->getBIInfo();
            $resultData['techs'] = $analyzeModel->getTechnicalData();

            $websiteId = Mage::getStoreConfig('shoppimon/settings/website_id'); //default config option chosen
            $data = [
                'website_id' => $websiteId,
                'base_url' => Mage::getBaseUrl(),
                'data' => $resultData
            ];

            $ch = curl_init();
            curl_setopt(
                $ch,
                CURLOPT_URL,
                Shoppimon_Monitor_Model_Analyzer::SHOPPIMON_BASE_URL . $pushUrl
            );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt(
                $ch,
                CURLOPT_HEADER,
                'Content-type: application/shoppimon-magento-ver' . $resultData['techs']['shoppimon_ext_version'] . '+json'
            );
            $result = curl_exec($ch);

            if (!$result) {
                $e = new Exception('We were unable to push data to Shoppimon. Please contact Shoppimon support');
                Mage::logException($e);
            }
        }
    }
}