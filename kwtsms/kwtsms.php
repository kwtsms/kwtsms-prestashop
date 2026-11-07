<?php
/**
 * kwtSMS - SMS Notifications & Alerts
 *
 * Main module file. Handles installation, uninstallation, hook callbacks,
 * and admin configuration page routing. Business logic is delegated to
 * classes in the classes/ directory.
 *
 * Related files:
 * - classes/KwtsmsInstaller.php: install/uninstall logic
 * - classes/KwtsmsGateway.php: API wrapper
 * - classes/KwtsmsSender.php: SMS send pipeline
 * - classes/KwtsmsTemplateManager.php: template rendering
 * - classes/KwtsmsLogger.php: logging
 * - classes/KwtsmsCron.php: daily sync
 * - controllers/admin/AdminKwtsmsController.php: admin UI
 *
 * @author    kwtSMS <support@kwtsms.com>
 * @copyright kwtSMS
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/KwtsmsInstaller.php';
require_once dirname(__FILE__) . '/classes/KwtsmsGateway.php';
require_once dirname(__FILE__) . '/classes/KwtsmsSender.php';
require_once dirname(__FILE__) . '/classes/KwtsmsTemplateManager.php';
require_once dirname(__FILE__) . '/classes/KwtsmsLogger.php';
require_once dirname(__FILE__) . '/classes/KwtsmsCron.php';

class Kwtsms extends Module
{
    public function __construct()
    {
        $this->name = 'kwtsms';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.1';
        $this->author = 'kwtSMS';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => '9.99.99');
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('kwtSMS - SMS Notifications & Alerts');
        $this->description = $this->l('Send SMS notifications to customers and admins via kwtSMS gateway. Order confirmations, status updates, new customer alerts, payment confirmations, and low stock alerts.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall kwtSMS? All SMS logs and settings will be deleted.');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $installer = new KwtsmsInstaller($this);

        return parent::install()
            && $installer->install()
            && $installer->registerHooks();
    }

    public function uninstall()
    {
        $installer = new KwtsmsInstaller($this);

        return $installer->uninstall()
            && parent::uninstall();
    }

    /**
     * Redirect to admin controller when clicking "Configure" in module list.
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminKwtsms'));
    }

    // -------------------------------------------------------------------------
    // Hook: New order placed
    // -------------------------------------------------------------------------

    public function hookActionValidateOrder($params)
    {
        $this->handleIntegration('order_placed', $params, function ($params) {
            $order = isset($params['order']) ? $params['order'] : null;
            $customer = isset($params['customer']) ? $params['customer'] : null;

            if (!Validate::isLoadedObject($order) || !Validate::isLoadedObject($customer)) {
                return null;
            }

            $currency = new Currency($order->id_currency);

            return array(
                'placeholders' => array(
                    '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
                    '{order_ref}' => $order->reference,
                    '{order_total}' => number_format($order->total_paid, 2),
                    '{currency}' => $currency->iso_code,
                    '{customer_email}' => $customer->email,
                ),
                'id_customer' => (int) $customer->id,
                'id_order' => (int) $order->id,
                'id_lang' => (int) $customer->id_lang,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Hook: Order status changed
    // -------------------------------------------------------------------------

    public function hookActionOrderStatusPostUpdate($params)
    {
        $this->handleIntegration('order_status_changed', $params, function ($params) {
            $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;
            $newOrderStatus = isset($params['newOrderStatus']) ? $params['newOrderStatus'] : null;

            if (!$idOrder || !$newOrderStatus) {
                return null;
            }

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                return null;
            }

            $customer = new Customer($order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                return null;
            }

            return array(
                'placeholders' => array(
                    '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
                    '{order_ref}' => $order->reference,
                    '{order_status}' => $newOrderStatus->name[$customer->id_lang] ?? $newOrderStatus->name[Configuration::get('PS_LANG_DEFAULT')],
                ),
                'id_customer' => (int) $customer->id,
                'id_order' => (int) $order->id,
                'id_lang' => (int) $customer->id_lang,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Hook: New customer registered
    // -------------------------------------------------------------------------

    public function hookActionCustomerAccountAdd($params)
    {
        $this->handleIntegration('new_customer', $params, function ($params) {
            $customer = isset($params['newCustomer']) ? $params['newCustomer'] : null;

            if (!Validate::isLoadedObject($customer)) {
                return null;
            }

            return array(
                'placeholders' => array(
                    '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
                    '{customer_email}' => $customer->email,
                ),
                'id_customer' => (int) $customer->id,
                'id_order' => null,
                'id_lang' => (int) $customer->id_lang,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Hook: Payment confirmed
    // -------------------------------------------------------------------------

    public function hookActionPaymentConfirmation($params)
    {
        $this->handleIntegration('payment_confirmed', $params, function ($params) {
            $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;
            if (!$idOrder) {
                return null;
            }

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                return null;
            }

            $customer = new Customer($order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                return null;
            }

            $currency = new Currency($order->id_currency);

            return array(
                'placeholders' => array(
                    '{customer_name}' => $customer->firstname . ' ' . $customer->lastname,
                    '{order_ref}' => $order->reference,
                    '{order_total}' => number_format($order->total_paid, 2),
                    '{currency}' => $currency->iso_code,
                ),
                'id_customer' => (int) $customer->id,
                'id_order' => (int) $order->id,
                'id_lang' => (int) $customer->id_lang,
            );
        });
    }

    // -------------------------------------------------------------------------
    // Hook: Stock quantity updated (low stock alert)
    // -------------------------------------------------------------------------

    public function hookActionUpdateQuantity($params)
    {
        $idProduct = isset($params['id_product']) ? (int) $params['id_product'] : 0;
        $quantity = isset($params['quantity']) ? (int) $params['quantity'] : 0;

        if (!$idProduct) {
            return;
        }

        // Get integration settings
        $integration = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'kwtsms_integration`
             WHERE `integration_key` = "low_stock" AND `active` = 1'
        );

        if (!$integration) {
            return;
        }

        $settings = json_decode($integration['settings'], true);
        $threshold = isset($settings['threshold']) ? (int) $settings['threshold'] : 5;

        if ($quantity > $threshold) {
            // Stock is above threshold, clear cooldown if any
            KwtsmsGateway::setCache('low_stock_alerted_' . $idProduct, '');
            return;
        }

        // Check 24h cooldown
        $lastAlerted = KwtsmsGateway::getCache('low_stock_alerted_' . $idProduct);
        if (!empty($lastAlerted)) {
            $lastTime = strtotime($lastAlerted);
            if ($lastTime && (time() - $lastTime) < 86400) {
                return;
            }
        }

        $product = new Product($idProduct, false, Configuration::get('PS_LANG_DEFAULT'));
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $adminPhones = Configuration::get('KWTSMS_ADMIN_PHONES');
        if (empty($adminPhones)) {
            return;
        }

        // Send admin alert
        $adminTemplateId = (int) $integration['id_kwtsms_template_admin'];
        if ($adminTemplateId) {
            $templateRow = Db::getInstance()->getRow(
                'SELECT `template_key` FROM `' . _DB_PREFIX_ . 'kwtsms_template`
                 WHERE `id_kwtsms_template` = ' . $adminTemplateId
            );

            if ($templateRow) {
                $message = KwtsmsTemplateManager::render($templateRow['template_key'], array(
                    '{product_name}' => $product->name,
                    '{product_ref}' => $product->reference,
                    '{stock_quantity}' => (string) $quantity,
                ));

                if ($message) {
                    $phones = array_map('trim', explode(',', $adminPhones));
                    KwtsmsSender::send($phones, $message, 'low_stock', array(
                        'recipient_type' => 'admin',
                    ));

                    // Set cooldown
                    KwtsmsGateway::setCache('low_stock_alerted_' . $idProduct, date('Y-m-d H:i:s'));
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Hook: CronJobs module
    // -------------------------------------------------------------------------

    public function hookActionCronJob()
    {
        KwtsmsCron::run();
    }

    /**
     * Required by CronJobs module to set frequency.
     *
     * @return array
     */
    public function getCronFrequency()
    {
        return array(
            'hour' => 3,
            'day' => -1,
            'month' => -1,
            'day_of_week' => -1,
        );
    }

    // -------------------------------------------------------------------------
    // Shared integration handler
    // -------------------------------------------------------------------------

    /**
     * Generic handler for hook-based integrations.
     * Checks if integration is active, resolves phone/template, sends SMS.
     *
     * @param string   $integrationKey e.g. 'order_placed'
     * @param array    $params         Hook params from PrestaShop
     * @param callable $extractor      Returns array with 'placeholders', 'id_customer',
     *                                 'id_order', 'id_lang' or null to abort
     */
    private function handleIntegration($integrationKey, $params, callable $extractor)
    {
        $integration = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'kwtsms_integration`
             WHERE `integration_key` = "' . pSQL($integrationKey) . '" AND `active` = 1'
        );

        if (!$integration) {
            return;
        }

        $data = $extractor($params);
        if (!$data) {
            return;
        }

        $placeholders = $data['placeholders'];
        $idCustomer = $data['id_customer'];
        $idOrder = isset($data['id_order']) ? $data['id_order'] : null;
        $idLang = isset($data['id_lang']) ? $data['id_lang'] : null;
        $recipientType = $integration['recipient_type'];

        // Send to customer
        if (in_array($recipientType, array('customer', 'both')) && $idCustomer) {
            $phone = $this->getCustomerPhone($idCustomer);
            if ($phone) {
                $customerTemplateId = (int) $integration['id_kwtsms_template'];
                $templateRow = Db::getInstance()->getRow(
                    'SELECT `template_key` FROM `' . _DB_PREFIX_ . 'kwtsms_template`
                     WHERE `id_kwtsms_template` = ' . $customerTemplateId
                );

                if ($templateRow) {
                    $message = KwtsmsTemplateManager::render($templateRow['template_key'], $placeholders, $idLang);
                    if ($message) {
                        KwtsmsSender::send($phone, $message, $integrationKey, array(
                            'recipient_type' => 'customer',
                            'id_customer' => $idCustomer,
                            'id_order' => $idOrder,
                        ));
                    }
                }
            } else {
                KwtsmsLogger::debug('hook_' . $integrationKey, 'No phone for customer ' . $idCustomer);
            }
        }

        // Send to admin
        if (in_array($recipientType, array('admin', 'both'))) {
            $adminPhones = Configuration::get('KWTSMS_ADMIN_PHONES');
            if (!empty($adminPhones)) {
                $adminTemplateId = $integration['id_kwtsms_template_admin'];
                if ($adminTemplateId) {
                    $templateRow = Db::getInstance()->getRow(
                        'SELECT `template_key` FROM `' . _DB_PREFIX_ . 'kwtsms_template`
                         WHERE `id_kwtsms_template` = ' . (int) $adminTemplateId
                    );

                    if ($templateRow) {
                        $message = KwtsmsTemplateManager::render($templateRow['template_key'], $placeholders);
                        if ($message) {
                            $phones = array_map('trim', explode(',', $adminPhones));
                            KwtsmsSender::send($phones, $message, $integrationKey . '_admin', array(
                                'recipient_type' => 'admin',
                                'id_customer' => $idCustomer,
                                'id_order' => $idOrder,
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * Get customer phone from their default delivery address.
     * Prefers phone_mobile, falls back to phone.
     *
     * @param int $idCustomer
     *
     * @return string|null Phone number or null
     */
    private function getCustomerPhone($idCustomer)
    {
        $idAddress = (int) Address::getFirstCustomerAddressId($idCustomer);
        if (!$idAddress) {
            return null;
        }

        $address = new Address($idAddress);
        if (!Validate::isLoadedObject($address)) {
            return null;
        }

        if (!empty($address->phone_mobile)) {
            return $address->phone_mobile;
        }

        if (!empty($address->phone)) {
            return $address->phone;
        }

        return null;
    }
}
