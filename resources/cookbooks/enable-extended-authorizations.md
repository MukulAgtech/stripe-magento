---
title: Enable extended authorizations
subtitle: Hold card authorizations for up to 30 days with Stripe's extended authorization feature.
route: /connectors/adobe-commerce/cookbooks/enable-extended-authorizations

stripe_products: []
---

# Enable Extended Authorizations

Stripe's extended authorization feature allows you to hold customer funds for up to 30 days (depending on the card network) compared to standard authorization validity periods of 7 days for online payments. This guide instructs you how to enable extended authorizations in your Magento store using the Stripe module.

## Availability

Extended authorizations are available to users on **IC+ pricing**. If you're on blended Stripe pricing and want access to this feature, you need to contact Stripe at [support.stripe.com](https://support.stripe.com/).

### Create a new module

Create a new module with the following directory structure. Replace `Vendor` with your preferred vendor name.

```
app/code/Vendor/StripeCustomizations/
├── etc/
│   ├── module.xml
│   └── config.xml
├── registration.php
```

Inside `registration.php`, register your module with Magento:

```php
<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Vendor_StripeCustomizations',
    __DIR__
);
```

Inside `etc/module.xml`, define the module and set up dependencies:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Vendor_StripeCustomizations" setup_version="1.0.0">
        <sequence>
            <module name="StripeIntegration_Payments"/>
        </sequence>
    </module>
</config>
```

Inside `etc/config.xml`, override the following settings from the Stripe module:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <stripe_settings>
            <extended_authorizations_enabled>1</extended_authorizations_enabled>
        </stripe_settings>
    </default>
</config>
```

### Enable the module

Once you've created all the necessary files, enable your custom module:

```sh
php bin/magento module:enable Vendor_StripeCustomizations
php bin/magento setup:upgrade
php bin/magento cache:clean
php bin/magento cache:flush
```

## Usage and Testing

When extended authorizations are enabled and your Stripe account is eligible (on IC+ pricing), the feature will automatically be applied to qualifying card payments.

To verify if an authorization has the extended window:
1. Place an order using test card **5555 5555 5555 4444**.
2. Open the order page from the Magento admin.
3. Look for the *Authorization expires* entry under the *Payment Information* section of the order.

## Additional Considerations

1. **Authorize Only mode is required**: Extended authorizations must be used with Authorize Only mode. Make sure your Payment Action in the Stripe module configuration section is set to "Authorize Only".
2. **Compliance**: You're responsible for compliance with all card network rules when using extended authorizations. Some networks allow extended authorizations only in specific cases.
3. **Customer Experience**: Inform customers that their funds may be held for an extended period before capturing the payment.
4. **Merchant Category Codes**: Extended authorizations availability depends on your Merchant Category Code (MCC) for some card networks.

For more details on card network validity windows and other terms, please refer to the [extended authorizations documentation](https://docs.stripe.com/payments/extended-authorization).