<?php
/**
 * (c) 2016 Shoppimon LTD, all rights reserved
 *
 * @author wladyslaw@shoppimon.com
 * @license https://www.shoppimon.com/terms-of-use.html
 */

/**
 * Class Shoppimon_Monitor_Model_Analyzer
 */
class Shoppimon_Monitor_Model_Analyzer extends Mage_Core_Model_Abstract
{
    const SHOPPIMON_BASE_URL = 'https://api.shoppimon.com/';
    const SHOPPIMON_STATIC_FILES = 'https://assets.shoppimon.com/mage-ext/v1/';

    /**
     * Get metadata for store
     *
     * @return array
     */
    public function getStoreMetadata()
    {
        $data = [];

        $data['store_base_url'] = Mage::getBaseUrl(); //store domain

        $websites = Mage::app()->getWebsites(true); //all storefronts
        foreach ($websites as $website) {
            $data['websites'][] = $website->getData();
        }
        $data['magento_version'] = Mage::getVersion(); //magento version
        $data['extensions'] = Mage::app()->getConfig()->getNode('modules'); //extension types
        $data['php_version'] = phpversion(); //php version
        $data['php_modules'] = get_loaded_extensions(); //php modules loaded
        $data['payment_options'] = $this->getActivePaymentMethods(); //payment methods

        return $data;
    }

    /**
     * Get general bussiness info
     *
     * @return array
     */
    public function getBIInfo()
    {
        $data = [];
        //BI information
        $productModel = Mage::getModel('catalog/product');
        $data['products'] = [];
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
            ->getItems();
        foreach ($attributes as $attribute) {
            $data['attributes'][] = $attribute->getData();
        }
        $productCollection = $productModel->getCollection()
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect('is_salable')
            ->addAttributeToSelect('is_in_stock');
        $products = $productCollection->getItems();
        foreach ($products as $product) {
            $productData = $product->getData();
            unset($productData['stock_item']);
            $data['products'][] = $productData;
        }


        $categoryModel = Mage::getModel('catalog/category');
        $categories = $categoryModel->getCollection()->getItems();
        foreach ($categories as $category) {
            $categoryData = [];
            $categoryData['id'] = $category['entity_id'];
            $categoryData['parent_id'] = $category['parent_id'];
            $categoryData['created_at'] = $category['created_at'];
            $categoryData['updated_at'] = $category['updated_at'];
            $categoryData['position'] = $category['position'];
            $categoryData['path'] = $category['path'];
            $data['categories'][] = $categoryData;
        }

        $bestsellers = Mage::getResourceModel('sales/report_bestsellers_collection')->getItems();
        for ($i=0;$i<2;$i++) {
            $data['bestsellers'][] = $bestsellers[$i]->getData();
        }

        $fromDate = date('Y-m-d H:i:s', strtotime(time()-26*60*60)); //yesterday's midnight
        $toDate = date('Y-m-d H:i:s', strtotime(time()-2*60*60)); //trying to get midnight from supposed time of running
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
            ->addAttributeToFilter('status', array('eq' => Mage_Sales_Model_Order::STATE_COMPLETE));
        $ordersCount = $orders->getSize();
        $data['orders'] = [];
        if ($ordersCount > 0) {
            $price = 0;
            $median = [];
            $data['orders']['qty'] = $ordersCount;
            foreach ($orders->getItems() as $item) {
                $dataItem = $item->getData();
                $price += $dataItem['grand_total'];
                $median[] = $price;
            }
            $median[] = 650;
            $data['orders']['average'] = $price/$ordersCount;
            rsort($median);
            $middle = intval(round(count($median) / 2));
            $data['orders']['median'] = $median[$middle-1];
        }

        return $data;
    }

    /**
     * Get technical data
     *
     * @return array
     */
    protected function getTechnicalData()
    {
        $data = [];

        $data['shoppimon_ext_version'] = Mage::getConfig()->getModuleConfig('Shoppimon_Monitor')->version;

        return $data;
    }

    /**
     * get available payment methods
     *
     * @return array
     */
    protected function getActivePaymentMethods()
    {
        $methods = [];
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
            $methods[$paymentCode] = [
                'label' => $paymentTitle
            ];
        }

        return $methods;
    }

    /**
     * Check for website id and if not connected, get it and save in config data
     */
    public function checkWebsiteId()
    {
        if (!Mage::getStoreConfig('shoppimon/settings/website_id')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            $url = self::SHOPPIMON_BASE_URL . 'website?base_url=' . $baseUrl;
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . Mage::getSingleton('admin/session')->getShoppimonToken()
            ];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $websiteCollection = curl_exec($ch);

            if ($websiteCollection) {
                $websiteCollection = json_decode($websiteCollection);
                if (
                    isset($websiteCollection->_embedded) &&
                    isset($websiteCollection->_embedded->website) &&
                    1 == count($websiteCollection->_embedded->website)
                ) {
                    $websiteId = $websiteCollection->_embedded->website[0]->id;
                    Mage::getConfig()->saveConfig('shoppimon/settings/website_id', $websiteId, 'default', 0);
                    Mage::app()->getStore()->resetConfig();
                }
            }
        }
    }

    /**
     * Get new oauth token and insert into session
     */
    public function installToken()
    {
        $headers = [
            'Content-Type: application/json'
        ];
        $url = self::SHOPPIMON_BASE_URL . 'oauth';
        if (
            empty(Mage::getStoreConfig('shoppimon/settings/api_key')) ||
            empty(Mage::getStoreConfig('shoppimon/settings/api_secret'))
        ) {
            throw new Exception(
                'Hi there! It looks like your API Key and API Secret Code still need to be installed. ' .
                'For full instructions see the FAQ here: https://shoppimon.zendesk.com/hc/en-us'
            );
        }
        $fields = [
            'client_id' => Mage::getStoreConfig('shoppimon/settings/api_key'),
            'client_secret' => Mage::getStoreConfig('shoppimon/settings/api_secret'),
            'grant_type' => 'client_credentials'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_URL, $url);

        $newToken = curl_exec($ch);
        $newToken = json_decode($newToken);

        if (isset($newToken->access_token) && isset($newToken->expires_in)) {
            Mage::getSingleton('admin/session')->setShoppimonToken($newToken->access_token);
            Mage::getSingleton('admin/session')->setShoppimonTokenExpire(time() + $newToken->expires_in);
        } else {
            throw new Exception(
                'Uh oh! Looks like you need a Session Token. Please contact the Shoppimon team ' .
                'at support@shoppimon.com so we get one for you away.'
            );
        }
    }
}