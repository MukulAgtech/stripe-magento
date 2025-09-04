<?php

namespace StripeIntegration\Payments\Model\Stripe\Service;

use StripeIntegration\Payments\Model\Stripe\Service\StripeObjectServiceFactory;

class StripeObjectServicePool
{
    private $stripeObjectServiceFactory;
    private $stripeObjectServices;

    public function __construct(
        StripeObjectServiceFactory $stripeObjectServiceFactory
    )
    {
        $this->stripeObjectServiceFactory = $stripeObjectServiceFactory;
    }

    public function getStripeObjectService($objectSpace)
    {
        if (!isset($this->stripeObjectServices[$objectSpace])) {
            $this->stripeObjectServices[$objectSpace] = $this->stripeObjectServiceFactory
                ->create()
                ->setObjectSpace($objectSpace);
        }
        return $this->stripeObjectServices[$objectSpace];
    }
}