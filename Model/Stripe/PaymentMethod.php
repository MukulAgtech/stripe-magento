<?php

namespace StripeIntegration\Payments\Model\Stripe;

class PaymentMethod
{
    use StripeObjectTrait;

    private $objectSpace = 'paymentMethods';

    public function __construct(
        \StripeIntegration\Payments\Model\Stripe\Service\StripeObjectServicePool $stripeObjectServicePool
    )
    {
        $stripeObjectService = $stripeObjectServicePool->getStripeObjectService($this->objectSpace);
        $this->setData($stripeObjectService);
    }

    public function fromPaymentMethodId($id)
    {
        if (!empty($this->getStripeObject()->id) && $this->getStripeObject()->id == $id)
        {
            return $this;
        }

        $this->load($id);
        return $this;
    }

    public function getCustomerId()
    {
        if (empty($this->getStripeObject()->customer))
        {
            return null;
        }

        if (!empty($this->getStripeObject()->customer->id))
        {
            return $this->getStripeObject()->customer->id;
        }

        return $this->getStripeObject()->customer;
    }

    public function getPaymentMethodType()
    {
        return $this->getStripeObject()->type ?? null;
    }

    public function getCardData()
    {
        if (empty($this->getStripeObject()->card))
        {
            return null;
        }

        return [
            'brand' => $this->getStripeObject()->card->brand,
            'last4' => $this->getStripeObject()->card->last4,
            'wallet' => $this->getStripeObject()->card->wallet->type ?? null,
        ];
    }
}