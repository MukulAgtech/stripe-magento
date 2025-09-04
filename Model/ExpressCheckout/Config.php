<?php

namespace StripeIntegration\Payments\Model\ExpressCheckout;

class Config
{
    // State
    private $storeId = null;
    private $isEnabled = null;
    private $activeLocations = [];
    private $allowGuestCheckout = null;
    private $sellerName = null;
    private $buttonHeight = null;
    private $sortOrderMethod = null;
    private $buttonTheme = [];
    private $buttonType = [];
    private $paymentMethodOrder = [];

    // Comstructor properties
    private $customerSession;
    private $storeHelper;
    private $configHelper;
    private $areaCodeHelper;
    private $checkoutSessionHelper;
    private $config;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \StripeIntegration\Payments\Helper\Store $storeHelper,
        \StripeIntegration\Payments\Helper\Config $configHelper,
        \StripeIntegration\Payments\Helper\AreaCode $areaCodeHelper,
        \StripeIntegration\Payments\Helper\CheckoutSession $checkoutSessionHelper,
        \StripeIntegration\Payments\Model\Config $config
    )
    {
        $this->customerSession = $customerSession;
        $this->storeHelper = $storeHelper;
        $this->configHelper = $configHelper;
        $this->areaCodeHelper = $areaCodeHelper;
        $this->checkoutSessionHelper = $checkoutSessionHelper;
        $this->config = $config;

        $this->isEnabled = $this->getConfigData('payment/stripe_payments_express/global_enabled');

        if (!$this->isEnabled)
            return;

        $this->activeLocations = explode(',', (string)$this->getConfigData("payment/stripe_payments_express/enabled"));
        $this->allowGuestCheckout = (bool)$this->getConfigData("checkout/options/guest_checkout");
        $this->sellerName = $this->_getSellerNameConfig();
        $this->buttonHeight = $this->_getButtonHeightConfig();
        $this->sortOrderMethod = $this->getConfigData("payment/stripe_payments_express/sort_order");
        $this->buttonTheme = $this->_getButtonThemeConfig();
        $this->buttonType = $this->_getButtonTypeConfig();
        $this->paymentMethodOrder = $this->_getPaymentMethodOrderConfig();
    }

    private function getConfigData($path)
    {
        if (empty($this->storeId))
            $this->storeId = $this->storeHelper->getStoreId();

        return $this->configHelper->getConfigData($path, $this->storeId);
    }

    private function canCheckout()
    {
        if ($this->customerSession->isLoggedIn())
            return true;

        return $this->allowGuestCheckout;
    }

    public function getActiveLocations()
    {
        return $this->activeLocations;
    }

    public function isEnabled($location)
    {
        if (!$this->config->initStripe())
            return false;

        if (!$this->isEnabled)
            return false;

        if (!in_array($location, $this->activeLocations))
            return false;

        if ($this->checkoutSessionHelper->isSubscriptionUpdate())
            return false;

        if ($this->areaCodeHelper->isAdmin())
            return false;

        if (!$this->storeHelper->isSecure())
            return false;

        if (!$this->canCheckout())
            return false;

        return true;
    }

    public function getButtonOptions()
    {
        $options = [
            'buttonHeight' => $this->buttonHeight,
            'buttonTheme' => $this->buttonTheme,
            'buttonType' => $this->buttonType
        ];

        if ($this->sortOrderMethod == "custom")
            $options['paymentMethodOrder'] = $this->paymentMethodOrder;

        return $options;
    }

    public function getSellerName()
    {
        return $this->sellerName;
    }

    private function _getSellerNameConfig()
    {
        $sellerName = $this->getConfigData('payment/stripe_payments_express/seller_name');
        if (empty($this->sellerName))
            return __("Order Total");

        return $sellerName;
    }

    private function _getButtonHeightConfig()
    {
        $buttonHeight = $this->getConfigData('payment/stripe_payments_express/button_height');
        if (!is_numeric($buttonHeight))
            return 50;
        else if ((int)$buttonHeight < 40)
            return 40;
        else if ((int)$buttonHeight > 55)
            return 55;

        return (int)$buttonHeight;
    }

    private function _getButtonThemeConfig()
    {
        $buttonTheme = [
            'applePay' => $this->getConfigData('payment/stripe_payments_express/apple_pay_button_theme'),
            'googlePay' => $this->getConfigData('payment/stripe_payments_express/google_pay_button_theme'),
            'paypal' => $this->getConfigData('payment/stripe_payments_express/paypal_button_theme')
        ];

        return $buttonTheme;
    }

    private function _getButtonTypeConfig()
    {
        $buttonType = [
            'applePay' => $this->getConfigData('payment/stripe_payments_express/apple_pay_button_type'),
            'googlePay' => $this->getConfigData('payment/stripe_payments_express/google_pay_button_type'),
            'paypal' => $this->getConfigData('payment/stripe_payments_express/paypal_button_type')
        ];

        return $buttonType;
    }

    private function _getPaymentMethodOrderConfig()
    {
        $paymentMethodOrder = [];

        $sortOrders = [
            'applePay' => $this->getConfigData('payment/stripe_payments_express/apple_pay_sort_order'),
            'googlePay' => $this->getConfigData('payment/stripe_payments_express/google_pay_sort_order'),
            'paypal' => $this->getConfigData('payment/stripe_payments_express/paypal_sort_order')
        ];

        foreach ($sortOrders as $key => $index)
        {
            if (empty($index))
                $index = 0;

            // Find the last occurence of $index, or the first occurence of a higher number, and insert the key there
            $insertIndex = count($paymentMethodOrder);
            for ($i = 0; $i < count($paymentMethodOrder); $i++)
            {
                if ($paymentMethodOrder[$i] > $index)
                {
                    $insertIndex = $i;
                    break;
                }
                else if ($paymentMethodOrder[$i] == $index)
                {
                    $insertIndex = $i + 1;
                }
            }

            array_splice($paymentMethodOrder, $insertIndex, 0, $key);
        }

        return $paymentMethodOrder;
    }
}