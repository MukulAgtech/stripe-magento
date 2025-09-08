<?php

namespace StripeIntegration\Payments\Model\Stripe;

class Charge
{
    use StripeObjectTrait;

    private $objectSpace = 'charges';
    private $tokenHelper;
    private $paymentIntentHelper;

    public function __construct(
        \StripeIntegration\Payments\Model\Stripe\Service\StripeObjectServicePool $stripeObjectServicePool,
        \StripeIntegration\Payments\Helper\Token $tokenHelper,
        \StripeIntegration\Payments\Helper\PaymentIntent $paymentIntentHelper
    )
    {
        $stripeObjectService = $stripeObjectServicePool->getStripeObjectService($this->objectSpace);
        $this->setData($stripeObjectService);

        $this->tokenHelper = $tokenHelper;
        $this->paymentIntentHelper = $paymentIntentHelper;
    }

    public function fromChargeId($id, $expandParams = [])
    {
        $id = $this->tokenHelper->cleanToken($id);

        if (!empty($this->getStripeObject()->id) && $this->getStripeObject()->id == $id)
        {
            return $this;
        }

        if (!empty($expandParams))
        {
            $this->setExpandParams($expandParams);
        }

        $this->load($id);
        return $this;
    }

    public function fromObject(\Stripe\Charge $charge)
    {
        $this->setObject($charge);
        return $this;
    }

    public function setExpandParams($params)
    {
        $this->stripeObjectService->setExpandParams($params);

        return $this;
    }

    public function getRiskScore()
    {
        return $this->getStripeObject()->outcome->risk_score ?? null;
    }

    public function getRiskLevel()
    {
        return $this->getStripeObject()->outcome->risk_level ?? null;
    }
}