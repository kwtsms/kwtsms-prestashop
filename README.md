<p align="center">
  <img src="https://www.kwtsms.com/images/kwtsms_logo_60.png" alt="kwtSMS" height="60">
</p>

<h1 align="center">kwtSMS for PrestaShop</h1>

<p align="center">
  SMS notifications and alerts for your PrestaShop store, powered by the
  <a href="https://www.kwtsms.com">kwtSMS</a> gateway.
</p>

<p align="center">
  <a href="https://github.com/kwtsms/kwtsms-prestashop/releases/latest"><img src="https://img.shields.io/github/v/release/kwtsms/kwtsms-prestashop?style=flat-square&label=version&color=FFA200" alt="Version"></a>
  <img src="https://img.shields.io/badge/PrestaShop-8.0--9.x-251B5B?style=flat-square&logo=prestashop&logoColor=white" alt="PrestaShop 8.0 - 9.x">
  <img src="https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP >= 7.4">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-AFL--3.0-blue?style=flat-square" alt="License"></a>
  <a href="https://www.kwtsms.com"><img src="https://img.shields.io/badge/kwtsms.com-FFA200?style=flat-square" alt="kwtsms.com"></a>
</p>

---

## About kwtSMS

[kwtSMS](https://www.kwtsms.com) is a Kuwait-based SMS gateway providing reliable message delivery to Kuwaiti mobile numbers. It supports Arabic and English content, approved Sender IDs, OTP flows, and a simple HTTP API. This module connects your PrestaShop store to the kwtSMS platform so you can send automated SMS notifications without writing any code.

## Features

| Notification | Recipient | Trigger |
|---|---|---|
| Order placed | Customer + Admin | New order validated |
| Order status changed | Customer | Shipping, delivery, cancellation, refund |
| New customer | Customer + Admin | Account registration |
| Payment confirmed | Customer + Admin | Payment received |
| Low stock alert | Admin | Stock falls below threshold |

Additional capabilities:

- **Bilingual templates** with automatic language selection (English / Arabic)
- **Custom placeholders** per notification type
- **SMS log** with full send history in the back office
- **Cron endpoint** for daily automated sync tasks
- **Connection test** to verify API credentials from the admin panel

## Requirements

| Dependency | Version |
|---|---|
| PrestaShop | 8.0.0 - 9.x |
| PHP | >= 7.4 |
| kwtSMS account | [Sign up at kwtsms.com](https://www.kwtsms.com) |

## Installation

1. Download `kwtsms.zip` from the [latest release](https://github.com/kwtsms/kwtsms-prestashop/releases/latest)
2. In your PrestaShop back office, go to **Modules > Module Manager**
3. Click **Upload a module** and select the `kwtsms.zip` file
4. After the upload completes, the module is installed automatically
5. Click **Configure** on the success message, or find **kwtSMS** under **Modules > Module Manager** and click **Configure**

## Setup Guide

The module has six tabs: **Dashboard**, **Gateway**, **Settings**, **Templates**, **Logs**, and **Help**. Follow these steps in order.

### Step 1: Connect your kwtSMS account (Gateway tab)

1. Open the **Gateway** tab
2. Enter your **kwtSMS username** and **API password** (the same credentials you use to log in at [kwtsms.com](https://www.kwtsms.com))
3. Click **Connect**
4. If the connection succeeds, your available balance and Sender IDs are loaded automatically
5. Select your **Sender ID** from the dropdown (this is the name that appears on the recipient's phone, must be pre-approved by kwtSMS)
6. Select your **default country code** (965 for Kuwait)
7. Optionally enable **Test mode** to log SMS without actually sending them
8. Click **Save**

To verify the connection works, scroll down to the **Test SMS** section, enter a phone number, and click **Send Test**.

### Step 2: Set admin phone numbers (Settings tab)

1. Open the **Settings** tab
2. In the **Admin Phone Numbers** field, enter one or more phone numbers separated by commas (include the country code, e.g. `96598765432`)
3. These numbers receive admin notifications: new orders, new customers, payment confirmations, and low stock alerts

### Step 3: Enable notifications (Settings tab)

Below the admin phones, you will see the list of available integrations:

- **Order Placed**: SMS to customer and admin when a new order is placed
- **Order Status Changed**: SMS to customer when order status changes (shipped, delivered, cancelled, refunded)
- **New Customer Registered**: SMS to customer (welcome) and admin (alert)
- **Payment Confirmed**: SMS to customer and admin when payment is received
- **Low Stock Alert**: SMS to admin when a product's stock drops below the threshold (default: 5 units, configurable)

Toggle each one on or off, then click **Save**.

### Step 4: Customize message templates (Templates tab)

1. Open the **Templates** tab
2. You will see all 8 message templates (one per notification type and recipient)
3. Click **Edit** on any template to modify it
4. Each template supports **English** and **Arabic** content. The module automatically picks the language matching the customer's PrestaShop language preference
5. Use placeholders to insert dynamic data into your messages:

| Placeholder | Available in | Description |
|---|---|---|
| `{customer_name}` | All templates | Customer full name |
| `{order_ref}` | Order placed, status changed, payment | Order reference number |
| `{order_total}` | Order placed, payment | Order total amount |
| `{currency}` | Order placed, payment | Currency code |
| `{order_status}` | Status changed | New status name |
| `{customer_email}` | New customer (admin) | Customer email |
| `{product_name}` | Low stock | Product name |
| `{product_ref}` | Low stock | Product reference / SKU |
| `{stock_quantity}` | Low stock | Current stock level |
| `{shop_name}` | All templates | Inserted automatically |

Example template: `Hi {customer_name}, your order {order_ref} for {order_total} {currency} has been confirmed. Thank you for shopping at {shop_name}!`

### Step 5: Set up the daily cron job (optional)

The module includes a cron endpoint that syncs your balance and Sender IDs daily. To enable it:

1. Open the **Gateway** tab
2. Copy the **Cron URL** shown at the bottom of the page
3. Add it to your server's crontab to run once daily:

```
0 3 * * * curl -s "https://yourshop.com/module/kwtsms/cron?token=YOUR_TOKEN" > /dev/null
```

Replace the URL with the one from your Gateway tab.

## Monitoring

### Dashboard tab
Shows a quick overview: connection status, SMS sending mode, balance, Sender ID, default country, and counts of sent/failed messages for today and this month.

### Logs tab
Full history of every SMS the module has sent or attempted. You can filter by status (sent, failed), event type, date range, or phone number. Use **Clear All Logs** to reset the log table.

## License

[Academic Free License 3.0 (AFL-3.0)](https://opensource.org/licenses/AFL-3.0)
