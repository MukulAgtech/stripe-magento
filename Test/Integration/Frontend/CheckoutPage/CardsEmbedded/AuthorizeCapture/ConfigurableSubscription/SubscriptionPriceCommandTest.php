<?php

namespace StripeIntegration\Payments\Test\Integration\Frontend\CheckoutPage\CardsEmbedded\AuthorizeCapture\ConfigurableSubscription;

class SubscriptionPriceCommandTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->helper = $this->objectManager->get(\StripeIntegration\Payments\Helper\Generic::class);
        $this->tests = new \StripeIntegration\Payments\Test\Integration\Helper\Tests($this);
        $this->quote = new \StripeIntegration\Payments\Test\Integration\Helper\Quote();

        $this->stockRegistry = $this->objectManager->get(\Magento\CatalogInventory\Model\StockRegistry::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->compare = new \StripeIntegration\Payments\Test\Integration\Helper\Compare($this);
        $this->subscriptionPriceCommand = $this->objectManager->get(\StripeIntegration\Payments\Setup\Migrate\SubscriptionPriceCommand::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture current_store payment/stripe_payments/active 1
     * @magentoConfigFixture current_store payment/stripe_payments/payment_flow 0
     * @magentoConfigFixture current_store payment/stripe_payments_basic/stripe_mode test
     * @magentoConfigFixture current_store payment/stripe_payments/payment_action authorize_capture
     *
     * @magentoDataFixture ../../../../app/code/StripeIntegration/Payments/Test/Integration/_files/Data/ApiKeys.php
     * @magentoDataFixture ../../../../app/code/StripeIntegration/Payments/Test/Integration/_files/Data/Taxes.php
     * @magentoDataFixture ../../../../app/code/StripeIntegration/Payments/Test/Integration/_files/Data/Addresses.php
     * @magentoDataFixture ../../../../app/code/StripeIntegration/Payments/Test/Integration/_files/Data/Products.php
     */
    public function testSubscriptionMigration()
    {
        $this->quote->create()
            ->setCustomer('Guest')
            ->setCart("ConfigurableSubscription")
            ->setShippingAddress("California")
            ->setShippingMethod("FlatRate")
            ->setBillingAddress("California")
            ->setPaymentMethod("SuccessCard");

        $order = $this->quote->placeOrder();

        // Stripe checks
        $customerId = $order->getPayment()->getAdditionalInformation("customer_stripe_id");
        $customer = $this->tests->stripe()->customers->retrieve($customerId);
        $this->assertCount(1, $customer->subscriptions->data);
        $this->compare->object($customer->subscriptions->data[0], [
            "default_tax_rates" => [
                0 => [
                    "description" => "8.25% VAT",
                    "percentage" => "8.25",
                    "inclusive" => false
                ]
            ],
            "items" => [
                "data" => [
                    0 => [
                        "plan" => [
                            "amount" => "1000",
                            "currency" => "usd",
                            "interval" => "month",
                            "interval_count" => 1
                        ],
                        "price" => [
                            "recurring" => [
                                "interval" => "month",
                                "interval_count" => 1
                            ],
                            "unit_amount" => "1000"
                        ],
                        "quantity" => 1
                    ]
                ]
            ],
            "metadata" => [
                "Order #" => $order->getIncrementId()
            ],
            "status" => "active",
            "tax_percent" => "8.25"
        ]);
        $invoice = $this->tests->stripe()->invoices->retrieve($customer->subscriptions->data[0]->latest_invoice);
        $this->compare->object($invoice, [
            "amount_due" => 1583,
            "amount_paid" => 1583,
            "amount_remaining" => 0,
            "tax" => 83,
            "total" => 1583
        ]);

        // Trigger webhooks
        $subscription = $customer->subscriptions->data[0];
        $this->tests->event()->triggerSubscriptionEvents($subscription, $this);

        // Reset
        $this->helper->clearCache();

        // Change the subscription price
        $this->assertNotEmpty($customer->subscriptions->data[0]->metadata->{"Product ID"});
        $productId = $customer->subscriptions->data[0]->metadata->{"Product ID"};
        $product = $this->helper->loadProductById($productId);
        $productId = $product->getEntityId();
        $product->setPrice(15);
        $product = $this->tests->saveProduct($product);

        // Migrate the existing subscription to the new price
        $inputFactory = $this->objectManager->get(\Symfony\Component\Console\Input\ArgvInputFactory::class);
        $input = $inputFactory->create([
            "argv" => [
                null,
                $productId,
                $productId,
                $order->getId(),
                $order->getId()
            ]
        ]);
        $output = $this->objectManager->get(\Symfony\Component\Console\Output\ConsoleOutput::class);

        $this->subscriptionPriceCommand->run($input, $output);

        // Stripe checks
        $customer = $this->tests->stripe()->customers->retrieve($customerId);
        $this->assertCount(1, $customer->subscriptions->data);

        // Trigger webhooks
        $subscription = $customer->subscriptions->data[0];
        $this->tests->event()->triggerSubscriptionEvents($subscription, $this);

        // Stripe checks
        $this->assertNotEmpty($customer->subscriptions->data[0]->latest_invoice);
        $invoice = $this->tests->stripe()->invoices->retrieve($customer->subscriptions->data[0]->latest_invoice);
        $this->compare->object($customer->subscriptions->data[0], [
            "default_tax_rates" => [
                0 => [
                    "description" => "8.25% VAT",
                    "percentage" => "8.25",
                    "inclusive" => false
                ]
            ],
            "items" => [
                "data" => [
                    0 => [
                        "plan" => [
                            "amount" => "1500",
                            "currency" => "usd",
                            "interval" => "month",
                            "interval_count" => 1
                        ],
                        "price" => [
                            "recurring" => [
                                "interval" => "month",
                                "interval_count" => 1
                            ],
                            "unit_amount" => "1500"
                        ],
                        "quantity" => 1
                    ]
                ]
            ],
            "metadata" => [
                "Product ID" => $productId
                // "Order #" => $order->getIncrementId()
            ],
            "status" => "trialing",
            "tax_percent" => "8.25"
        ]);
        // All should be zero because it is a trial subscription
        $this->compare->object($invoice, [
            "amount_due" => 0,
            "amount_paid" => 0,
            "amount_remaining" => 0,
            "tax" => 0,
            "total" => 0
        ]);

        $upcomingInvoice = $this->tests->stripe()->invoices->upcoming(['customer' => $customer->id]);
        $this->assertCount(2, $upcomingInvoice->lines->data);
        $this->compare->object($upcomingInvoice, [
            "tax" => 124,
            "total" => 2124
        ]);
    }
}
