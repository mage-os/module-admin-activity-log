# Mage-OS Admin Activity Log

[![Latest Stable Version](https://poser.pugx.org/mage-os/module-admin-activity-log/v/stable)](https://packagist.org/packages/mage-os/module-admin-activity-log)
[![License](https://poser.pugx.org/mage-os/module-admin-activity-log/license)](https://packagist.org/packages/mage-os/module-admin-activity-log)
[![Total Downloads](https://poser.pugx.org/mage-os/module-admin-activity-log/downloads)](https://packagist.org/packages/mage-os/module-admin-activity-log)

A comprehensive admin activity logging extension for Mage-OS and Magento 2 that provides complete audit trails for
administrative actions, login attempts, and page visits.

Huge thanks to KiwiCommerce for publishing this extension, and for their support and contributions. This extension is a 
fork of the original [Magento Admin Activity Log](https://github.com/kiwicommerce/magento2-admin-activity) by KiwiCommerce.

## ✨ Key Features

- **Admin Action Logging**: Track all admin activities including add, edit, delete, print, view, and mass update operations
- **Login Monitoring**: Record successful and failed login attempts with detailed information
- **Field-Level Changes**: Track specific field modifications with before/after values
- **IP Address Logging**: Capture IP addresses and user agent information for security analysis
- **Extensible Configuration**: Customize tracked entities and skip fields via XML configuration

## 📋 Requirements

| Component | Version |
|-----------|---------|
| **Magento Open Source** | 2.4.x |
| **Mage-OS** | 1.0+ |
| **PHP** | 8.1+ |

## 🚀 Installation

```bash
composer require mage-os/module-admin-activity-log
php bin/magento setup:upgrade
```

## ⚙️ Configuration

### Basic Configuration

Navigate to **Stores → Configuration → Advanced → Admin → Admin Activity**

#### General Settings
- **Enable Admin Activity Logs**: Enable/disable the extension
- **Record Login Activity**: Track login and logout activities
- **Record Page Visits**: Monitor admin page navigation
- **Clear Admin Activity Logs After**: Set how many days to keep logs (default: 90 days)

## 📖 Usage Guide

### Viewing Activity Logs

Navigate to **System → Admin Activity → Admin Activity Logs**

Click View on any log entry to see detailed info.

### Login Activity Monitoring

Go to **System → Admin Activity → Admin Login Logs**

## Architecture

The module is built around interface-driven services for clean extensibility:

| Interface | Description |
|-----------|-------------|
| `ActivityConfigInterface` | Configuration settings (enabled state, log retention) |
| `FieldTrackerInterface` | Tracks field-level changes with before/after values |
| `ModelResolverInterface` | Resolves and loads models for activity logging |
| `ActivityRepositoryInterface` | CRUD operations for activity log entries |
| `LoginRepositoryInterface` | CRUD operations for login log entries |

All interfaces are marked `@api` and can be customized via DI preferences.

## Extensibility

### Customizing Tracked Entities

The module uses `adminactivity.xml` to define which entities are tracked and which fields are skipped during logging.
Third-party modules can extend this configuration by creating their own `etc/adminactivity.xml` file.

Example configuration to add a custom entity:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:MageOS_AdminActivityLog:etc/adminactivity.xsd">
    <modules>
        <module name="customer_group">
            <label translate="true">Customer Group</label>
            <models>
                <class name="Magento\Customer\Model\Group" />
            </models>
            <events>
                <event controller_action="customer_group_save" action_alias="save" />
                <event controller_action="customer_group_delete" action_alias="delete" />
            </events>
            <config>
                <skip_fields>
                    <field>customer_group_id</field>
                    <field>check_if_is_new</field>
                </skip_fields>
                <configpath constant="MODULE_CUSTOMER" />
                <editurl url="{{module}}/{{controller}}/edit/id/{{id}}" />
                <itemfield field="customer_group_code" />
            </config>
        </module>
    </modules>
</config>
```

### Skip Fields

To exclude specific fields from being logged (e.g., timestamps, internal IDs), add them to the `skip_fields` node for the relevant module in your `adminactivity.xml`.

### Protected Fields

Sensitive fields (passwords, tokens, payment data) are automatically excluded from logging. To add custom protected fields:

```xml
<type name="MageOS\AdminActivityLog\Model\FieldChecker">
    <arguments>
        <argument name="protectedFields" xsi:type="array">
            <item name="my_secret_field" xsi:type="string">my_secret_field</item>
        </argument>
    </arguments>
</type>
```

## Security

- **Model Allowlist**: Only explicitly allowed model classes can be loaded during activity logging, preventing arbitrary class instantiation.
- **Protected Fields**: Sensitive data (passwords, API keys, tokens, payment info) is never logged, configured via DI.
- **ACL Permissions**: Control access to activity logs via permission rules.
- **CSRF Protection**: All admin actions are protected with form keys.

## Performance Notes

- **Page Visit Logging**: Disabled by default. Enable only if needed, as it creates a log entry for every admin page view.
- **Database Indexes**: The module includes indexes on frequently queried columns for optimal filtering performance.
- **Bulk Operations**: Log cleanup and activity logging use bulk database operations to minimize overhead.
- **Field Truncation**: Large values are truncated at ~64KB to prevent database bloat.

## Contributing

Issues and pull requests welcome on GitHub.

## License

This module is licensed under the [Open Software License 3.0](https://opensource.org/licenses/OSL-3.0).

## Support

For bugs or feature requests, please open an issue in the GitHub repository.
