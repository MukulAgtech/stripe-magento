<?php

namespace StripeIntegration\Payments\Test\Integration\Unit\Cron;

class WebhooksPingTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
    }

    /**
     * @magentoDataFixture ../../../../app/code/StripeIntegration/Payments/Test/Integration/_files/Data/ApiKeys.php
     */
    public function testCron()
    {
        $cron = $this->objectManager->create(\StripeIntegration\Payments\Cron\WebhooksPing::class);
        $cache = $this->objectManager->create(\Magento\Framework\App\CacheInterface::class);

        $cron->execute();

        $timeDifference = $cache->load("stripe_api_time_difference");

        $this->assertTrue(is_numeric($timeDifference));
    }
}
