<?php

namespace StripeIntegration\Payments\Helper;

/**
 * MINIMAL DEPENDENCIES HELPER
 * No dependencies on other helper classes.
 * This class can be injected into installation scripts, cron jobs, predispatch observers etc.
 */
class Data
{
    private $storeManager;
    private $scopeConfig;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfigData($field)
    {
        $storeId = $this->storeManager->getStore()->getId();

        return $this->scopeConfig->getValue($field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function convertToSetupIntentConfirmParams($paymentIntentConfirmParams)
    {
        $confirmParams = $paymentIntentConfirmParams;

        if (!empty($confirmParams['payment_method_options']))
        {
            foreach ($confirmParams['payment_method_options'] as $key => $value)
            {
                if (isset($confirmParams['payment_method_options'][$key]['setup_future_usage']))
                    unset($confirmParams['payment_method_options'][$key]['setup_future_usage']);

                if (isset($confirmParams['payment_method_options'][$key]['moto']))
                    unset($confirmParams['payment_method_options'][$key]['moto']);

                if (!in_array($key, \StripeIntegration\Payments\Helper\PaymentMethod::SETUP_INTENT_PAYMENT_METHOD_OPTIONS))
                    unset($confirmParams['payment_method_options'][$key]);

                if (empty($confirmParams['payment_method_options'][$key]))
                    unset($confirmParams['payment_method_options'][$key]);
            }

            if (empty($confirmParams['payment_method_options']))
                unset($confirmParams['payment_method_options']);
        }

        if (isset($confirmParams['off_session']))
            unset($confirmParams['off_session']);

        return $confirmParams;
    }

    public function getBuyRequest($orderItem)
    {
        if (!$orderItem || !$orderItem->getId())
            return null;

        $productOptions = $orderItem->getProductOptions();
        if (!$productOptions)
            return null;

        if (empty($productOptions['info_buyRequest']))
            return null;

        return new \Magento\Framework\DataObject($productOptions['info_buyRequest']);
    }

    public function getConfigurableProductBuyRequest($orderItem)
    {
        if (!$orderItem || !$orderItem->getId())
            return null;

        $productOptions = $orderItem->getProductOptions();
        if (!$productOptions)
            return null;

        $buyRequest = isset($productOptions['info_buyRequest']) ? $productOptions['info_buyRequest'] : null;

        if (!$buyRequest)
            return null;

        $buyRequest['qty'] = $orderItem->getQtyOrdered();

        // Extract the configurable item options
        $configurableItemOptions = isset($productOptions['attributes_info']) ? $productOptions['attributes_info'] : null;

        if (!$configurableItemOptions)
            return $buyRequest;

        // Add the configurable item options to buyRequest
        $superAttribute = [];
        foreach ($configurableItemOptions as $option) {
            if (isset($option['attribute_id']) && isset($option['value'])) {
                $superAttribute[$option['attribute_id']] = $option['value'];
            }
        }

        if (!empty($superAttribute)) {
            $buyRequest['super_attribute'] = $superAttribute;
        }

        return $buyRequest;
    }

    public function areArrayValuesTheSame(array $array1, array $array2)
    {
        $combined = array_merge($array1, $array2);
        $unique = array_unique($combined);

        if (count($unique) != count($array1))
            return false;

        if (count($unique) != count($array2))
            return false;

        return true;
    }
}
