<?php
/**
 * kwtSMS - Admin Controller
 *
 * Back-office controller with 6 tabs: Dashboard, Gateway, Settings,
 * Templates, Logs, Help. Uses Smarty templates for rendering.
 *
 * Related files:
 * - views/templates/admin/*.tpl: tab templates
 * - views/css/admin.css: styles
 * - views/js/admin.js: client-side logic
 * - classes/*: business logic classes
 *
 * @author    kwtSMS <support@kwtsms.com>
 * @copyright kwtSMS
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0
 */

require_once _PS_MODULE_DIR_ . 'kwtsms/classes/KwtsmsGateway.php';
require_once _PS_MODULE_DIR_ . 'kwtsms/classes/KwtsmsSender.php';
require_once _PS_MODULE_DIR_ . 'kwtsms/classes/KwtsmsTemplateManager.php';
require_once _PS_MODULE_DIR_ . 'kwtsms/classes/KwtsmsLogger.php';
require_once _PS_MODULE_DIR_ . 'kwtsms/classes/KwtsmsCron.php';

class AdminKwtsmsController extends ModuleAdminController
{
    /** @var array Tab definitions (populated in constructor with translations) */
    private $tabs = array();

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();

        $this->tabs = array(
            'dashboard' => $this->module->l('Dashboard'),
            'gateway'   => $this->module->l('Gateway'),
            'settings'  => $this->module->l('Settings'),
            'templates' => $this->module->l('Templates'),
            'logs'      => $this->module->l('Logs'),
            'help'      => $this->module->l('Help'),
        );
    }

    public function initToolbarTitle()
    {
        $this->toolbar_title = array('kwtSMS');
    }

    public function initPageHeaderToolbar()
    {
        $this->page_header_toolbar_title = 'kwtSMS';
        $this->page_header_toolbar_btn = array();
    }

    public function initContent()
    {
        parent::initContent();

        $currentTab = Tools::getValue('tab', 'dashboard');
        if (!array_key_exists($currentTab, $this->tabs)) {
            $currentTab = 'dashboard';
        }

        // Handle POST actions
        $this->processActions($currentTab);

        // Assign common variables BEFORE rendering tabs (templates need these)
        $this->context->smarty->assign(array(
            'kwtsms_tabs' => $this->tabs,
            'current_tab' => $currentTab,
            'tab_content' => '',
            'admin_link'  => $this->context->link->getAdminLink('AdminKwtsms'),
            'module_dir'  => _PS_MODULE_DIR_ . 'kwtsms/',
        ));

        // Build tab content (may use common variables)
        $tabContent = $this->renderTab($currentTab);
        $this->context->smarty->assign('tab_content', $tabContent);

        // Add CSS/JS
        $this->addCSS(_PS_MODULE_DIR_ . 'kwtsms/views/css/admin.css');
        $this->addJS(_PS_MODULE_DIR_ . 'kwtsms/views/js/admin.js');

        // Render the layout wrapper (dashboard.tpl contains tab nav + content area)
        $this->context->smarty->assign('content', $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/dashboard.tpl'
        ));
    }

    /**
     * Route to the correct tab renderer.
     *
     * @param string $tab
     *
     * @return string Rendered HTML
     */
    private function renderTab($tab)
    {
        switch ($tab) {
            case 'dashboard':
                return $this->renderDashboard();
            case 'gateway':
                return $this->renderGateway();
            case 'settings':
                return $this->renderSettings();
            case 'templates':
                return $this->renderTemplates();
            case 'logs':
                return $this->renderLogs();
            case 'help':
                return $this->renderHelp();
            default:
                return $this->renderDashboard();
        }
    }

    /**
     * Handle POST form submissions per tab.
     *
     * @param string $tab
     */
    private function processActions($tab)
    {
        if (!Tools::isSubmit('submitKwtsms')) {
            return;
        }

        switch ($tab) {
            case 'gateway':
                $this->processGateway();
                break;
            case 'settings':
                $this->processSettings();
                break;
            case 'templates':
                $this->processTemplates();
                break;
            case 'logs':
                $this->processLogs();
                break;
        }
    }

    // =========================================================================
    // Dashboard Tab
    // =========================================================================

    private function renderDashboard()
    {
        $gateway = new KwtsmsGateway();
        $stats = KwtsmsLogger::getStats();

        $this->context->smarty->assign(array(
            'balance'           => $gateway->getCachedBalance(),
            'balance_updated'   => KwtsmsGateway::getCacheUpdatedAt('balance'),
            'sender_id'         => Configuration::get('KWTSMS_SENDER_ID'),
            'default_country'   => Configuration::get('KWTSMS_DEFAULT_COUNTRY_CODE'),
            'gateway_connected' => (bool) Configuration::get('KWTSMS_GATEWAY_CONNECTED'),
            'gateway_enabled'   => (bool) Configuration::get('KWTSMS_GATEWAY_ENABLED'),
            'test_mode'         => (bool) Configuration::get('KWTSMS_TEST_MODE'),
            'sent_today'        => $stats['sent_today'],
            'sent_month'        => $stats['sent_month'],
            'failed_month'      => $stats['failed_month'],
        ));

        // Dashboard content is rendered inside the layout wrapper (dashboard.tpl)
        // via {if $current_tab == 'dashboard'} block, so no separate fetch needed.
        return '';
    }

    // =========================================================================
    // Gateway Tab
    // =========================================================================

    private function renderGateway()
    {
        $gateway = new KwtsmsGateway();

        $cronToken = Configuration::get('KWTSMS_CRON_TOKEN');
        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;
        $cronUrl = $shopUrl . 'module/kwtsms/cron?token=' . $cronToken;

        $this->context->smarty->assign(array(
            'gateway_connected'    => (bool) Configuration::get('KWTSMS_GATEWAY_CONNECTED'),
            'gateway_enabled'      => (bool) Configuration::get('KWTSMS_GATEWAY_ENABLED'),
            'test_mode'            => (bool) Configuration::get('KWTSMS_TEST_MODE'),
            'debug_mode'           => (bool) Configuration::get('KWTSMS_DEBUG_MODE'),
            'kwtsms_username'      => Configuration::get('KWTSMS_USERNAME'),
            // 2026-11-07 07:33 +03: never send the stored API password to the browser (it was in the field's value); only
            // say whether one is saved. Found in the PrestaShop 9.0.3 Docker QA, task int-rerelease-number.
            'kwtsms_password_saved' => (string) Configuration::get('KWTSMS_PASSWORD') !== '',
            'current_sender_id'    => Configuration::get('KWTSMS_SENDER_ID'),
            'current_country_code' => Configuration::get('KWTSMS_DEFAULT_COUNTRY_CODE'),
            'sender_ids'           => $gateway->getCachedSenderIds(),
            'coverage_codes'       => $gateway->getCachedCoverage(),
            'balance'              => $gateway->getCachedBalance(),
            'cron_url'             => $cronUrl,
            'default_test_message' => 'Test from ' . Configuration::get('PS_SHOP_NAME') . ' - ' . date('Y-m-d H:i:s'),
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/gateway.tpl'
        );
    }

    private function processGateway()
    {
        $action = Tools::getValue('action');

        switch ($action) {
            case 'connect':
                $username = Tools::getValue('kwtsms_username');
                $password = Tools::getValue('kwtsms_password');
                // 2026-11-07 07:33 +03: empty field = keep the saved password (the page no longer shows it).
                if ($password === '' || $password === false) {
                    $password = (string) Configuration::get('KWTSMS_PASSWORD');
                }

                if (empty($username) || empty($password)) {
                    $this->context->smarty->assign(array(
                        'gateway_message'      => $this->module->l('Please enter both username and password.'),
                        'gateway_message_type' => 'danger',
                    ));
                    return;
                }

                $gateway = new KwtsmsGateway();
                $result = $gateway->connect(pSQL($username), pSQL($password));

                if ($result['success']) {
                    $this->context->smarty->assign(array(
                        'gateway_message'      => sprintf($this->module->l('Connected successfully! Balance: %s credits.'), number_format($result['balance'], 2)),
                        'gateway_message_type' => 'success',
                    ));
                } else {
                    $this->context->smarty->assign(array(
                        'gateway_message'      => sprintf($this->module->l('Connection failed: %s'), $result['error']),
                        'gateway_message_type' => 'danger',
                    ));
                }
                break;

            case 'save_gateway':
                $senderId = Tools::getValue('kwtsms_sender_id');
                $countryCode = Tools::getValue('kwtsms_country_code');
                $enabled = (bool) Tools::getValue('kwtsms_gateway_enabled');
                $testMode = (bool) Tools::getValue('kwtsms_test_mode');
                $debugMode = (bool) Tools::getValue('kwtsms_debug_mode');

                Configuration::updateValue('KWTSMS_SENDER_ID', pSQL($senderId));
                Configuration::updateValue('KWTSMS_DEFAULT_COUNTRY_CODE', pSQL($countryCode));
                Configuration::updateValue('KWTSMS_GATEWAY_ENABLED', $enabled);
                Configuration::updateValue('KWTSMS_TEST_MODE', $testMode);
                Configuration::updateValue('KWTSMS_DEBUG_MODE', $debugMode);

                $this->context->smarty->assign(array(
                    'gateway_message'      => $this->module->l('Gateway configuration saved.'),
                    'gateway_message_type' => 'success',
                ));
                break;

            case 'test_sms':
                $phone = Tools::getValue('test_phone');
                $message = Tools::getValue('test_message');

                if (empty($phone)) {
                    $this->context->smarty->assign(array(
                        'gateway_message'      => $this->module->l('Please enter a phone number.'),
                        'gateway_message_type' => 'danger',
                    ));
                    return;
                }

                if (empty($message)) {
                    $message = 'Test from ' . Configuration::get('PS_SHOP_NAME') . ' - ' . date('Y-m-d H:i:s');
                }

                $result = KwtsmsSender::send(
                    pSQL($phone),
                    pSQL($message),
                    'gateway_test',
                    array('recipient_type' => 'admin')
                );

                $this->context->smarty->assign(array(
                    'test_result'  => $result,
                    'test_phone'   => $phone,
                    'test_message' => $message,
                ));
                break;

            case 'refresh_balance':
                $gateway = new KwtsmsGateway();
                $balanceResult = $gateway->syncBalance();

                if ($balanceResult && isset($balanceResult['result']) && $balanceResult['result'] === 'OK') {
                    $balance = isset($balanceResult['available']) ? (float) $balanceResult['available'] : 0;
                    $this->context->smarty->assign(array(
                        'gateway_message'      => sprintf($this->module->l('Balance refreshed: %s credits.'), number_format($balance, 2)),
                        'gateway_message_type' => 'success',
                    ));
                } else {
                    $this->context->smarty->assign(array(
                        'gateway_message'      => $this->module->l('Failed to refresh balance. Check your connection.'),
                        'gateway_message_type' => 'danger',
                    ));
                }
                break;
        }
    }

    // =========================================================================
    // Settings Tab
    // =========================================================================

    /**
     * Human-readable labels and descriptions for integrations.
     *
     * @return array key => [label, description]
     */
    private function getIntegrationMeta()
    {
        return array(
            'order_placed' => array(
                'label' => $this->module->l('Order Placed'),
                'description' => $this->module->l('Send SMS when a new order is placed.'),
            ),
            'order_status_changed' => array(
                'label' => $this->module->l('Order Status Changed'),
                'description' => $this->module->l('Send SMS when an order status is updated.'),
            ),
            'new_customer' => array(
                'label' => $this->module->l('New Customer Registered'),
                'description' => $this->module->l('Send SMS when a new customer signs up.'),
            ),
            'payment_confirmed' => array(
                'label' => $this->module->l('Payment Confirmed'),
                'description' => $this->module->l('Send SMS when payment for an order is confirmed.'),
            ),
            'low_stock' => array(
                'label' => $this->module->l('Low Stock Alert'),
                'description' => $this->module->l('Send SMS to admin when stock drops below threshold.'),
            ),
        );
    }

    private function renderSettings()
    {
        $meta = $this->getIntegrationMeta();

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'kwtsms_integration` ORDER BY `id_kwtsms_integration` ASC'
        );
        $integrations = array();
        $lowStockThreshold = 5;

        if ($rows) {
            foreach ($rows as $row) {
                $key = $row['integration_key'];
                $row['label'] = isset($meta[$key]) ? $meta[$key]['label'] : $key;
                $row['description'] = isset($meta[$key]) ? $meta[$key]['description'] : '';
                $row['active'] = (bool) $row['active'];
                $integrations[] = $row;

                if ($key === 'low_stock' && !empty($row['settings'])) {
                    $settings = json_decode($row['settings'], true);
                    if (isset($settings['threshold'])) {
                        $lowStockThreshold = (int) $settings['threshold'];
                    }
                }
            }
        }

        $this->context->smarty->assign(array(
            'admin_phones'        => Configuration::get('KWTSMS_ADMIN_PHONES'),
            'integrations'        => $integrations,
            'low_stock_threshold' => $lowStockThreshold,
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/settings.tpl'
        );
    }

    private function processSettings()
    {
        // Save admin phones
        $adminPhones = Tools::getValue('kwtsms_admin_phones', '');
        Configuration::updateValue('KWTSMS_ADMIN_PHONES', pSQL($adminPhones));

        // Save integration active states
        $activeMap = Tools::getValue('integration_active');
        if (!is_array($activeMap)) {
            $activeMap = array();
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `id_kwtsms_integration`, `integration_key` FROM `' . _DB_PREFIX_ . 'kwtsms_integration`'
        );

        if ($rows) {
            foreach ($rows as $row) {
                $id = (int) $row['id_kwtsms_integration'];
                $isActive = isset($activeMap[$id]) ? 1 : 0;

                $updateData = array(
                    'active' => $isActive,
                    'date_upd' => date('Y-m-d H:i:s'),
                );

                // Handle low stock threshold
                if ($row['integration_key'] === 'low_stock') {
                    $threshold = (int) Tools::getValue('low_stock_threshold', 5);
                    if ($threshold < 1) {
                        $threshold = 1;
                    }
                    $updateData['settings'] = pSQL(json_encode(array('threshold' => $threshold)));
                }

                Db::getInstance()->update(
                    'kwtsms_integration',
                    $updateData,
                    '`id_kwtsms_integration` = ' . $id
                );
            }
        }

        $this->context->smarty->assign(array(
            'settings_message'      => $this->module->l('Settings saved successfully.'),
            'settings_message_type' => 'success',
        ));
    }

    // =========================================================================
    // Templates Tab
    // =========================================================================

    /**
     * Human-readable label from a template key.
     *
     * @param string $key e.g. 'order_placed_customer'
     *
     * @return string
     */
    private function getTemplateLabel($key)
    {
        $labels = array(
            'order_placed_customer'          => $this->module->l('Order Placed (Customer)'),
            'order_placed_admin'             => $this->module->l('Order Placed (Admin)'),
            'order_status_changed_customer'  => $this->module->l('Order Status Changed (Customer)'),
            'new_customer_customer'          => $this->module->l('New Customer Welcome (Customer)'),
            'new_customer_admin'             => $this->module->l('New Customer Alert (Admin)'),
            'payment_confirmed_customer'     => $this->module->l('Payment Confirmed (Customer)'),
            'payment_confirmed_admin'        => $this->module->l('Payment Confirmed (Admin)'),
            'low_stock_admin'                => $this->module->l('Low Stock Alert (Admin)'),
        );

        return isset($labels[$key]) ? $labels[$key] : $key;
    }

    private function renderTemplates()
    {
        $editId = (int) Tools::getValue('edit_template', 0);

        if ($editId) {
            // Edit a single template
            $template = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'kwtsms_template`
                 WHERE `id_kwtsms_template` = ' . $editId
            );

            if ($template) {
                $template['label'] = $this->getTemplateLabel($template['template_key']);
                $template['placeholders'] = KwtsmsTemplateManager::getPlaceholders($template['template_key']);

                $this->context->smarty->assign(array(
                    'edit_template' => $template,
                ));
            }
        }

        // Always load list for the non-edit view
        $allTemplates = KwtsmsTemplateManager::getAllTemplates();
        $templatesList = array();
        foreach ($allTemplates as $tpl) {
            $tpl['label'] = $this->getTemplateLabel($tpl['template_key']);
            $templatesList[] = $tpl;
        }

        $this->context->smarty->assign(array(
            'templates_list' => $templatesList,
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/templates.tpl'
        );
    }

    private function processTemplates()
    {
        $idTemplate = (int) Tools::getValue('id_template', 0);
        if (!$idTemplate) {
            return;
        }

        $contentEn = Tools::getValue('content_en', '');
        $contentAr = Tools::getValue('content_ar', '');

        // Strip HTML tags for SMS safety
        $contentEn = strip_tags($contentEn);
        $contentAr = strip_tags($contentAr);

        $result = KwtsmsTemplateManager::updateTemplate(
            $idTemplate,
            $contentEn,
            !empty($contentAr) ? $contentAr : null
        );

        if ($result) {
            $this->context->smarty->assign(array(
                'templates_message'      => $this->module->l('Template saved successfully.'),
                'templates_message_type' => 'success',
            ));
        } else {
            $this->context->smarty->assign(array(
                'templates_message'      => $this->module->l('Failed to save template.'),
                'templates_message_type' => 'danger',
            ));
        }
    }

    // =========================================================================
    // Logs Tab
    // =========================================================================

    private function renderLogs()
    {
        $perPage = 50;
        $page = (int) Tools::getValue('logs_page', 1);
        if ($page < 1) {
            $page = 1;
        }

        // Collect filters
        $filters = array();
        $filterStatus = Tools::getValue('filter_status', '');
        $filterEventType = Tools::getValue('filter_event_type', '');
        $filterDateFrom = Tools::getValue('filter_date_from', '');
        $filterDateTo = Tools::getValue('filter_date_to', '');
        $filterSearch = Tools::getValue('filter_search', '');

        if (!empty($filterStatus)) {
            $filters['status'] = $filterStatus;
        }
        if (!empty($filterEventType)) {
            $filters['event_type'] = $filterEventType;
        }
        if (!empty($filterDateFrom) && Validate::isDate($filterDateFrom)) {
            $filters['date_from'] = $filterDateFrom;
        }
        if (!empty($filterDateTo) && Validate::isDate($filterDateTo)) {
            $filters['date_to'] = $filterDateTo;
        }
        if (!empty($filterSearch)) {
            $filters['recipient'] = $filterSearch;
        }

        $logsData = KwtsmsLogger::getLogs($filters, $page, $perPage);
        $totalPages = ($logsData['total'] > 0) ? (int) ceil($logsData['total'] / $perPage) : 1;

        // Distinct event types for the filter dropdown
        $eventTypeRows = Db::getInstance()->executeS(
            'SELECT DISTINCT `event_type` FROM `' . _DB_PREFIX_ . 'kwtsms_log`
             WHERE `status` != "debug" AND `event_type` != ""
             ORDER BY `event_type` ASC'
        );
        $eventTypes = array();
        if ($eventTypeRows) {
            foreach ($eventTypeRows as $row) {
                $eventTypes[] = $row['event_type'];
            }
        }

        // Build page URL preserving filters
        $adminLink = $this->context->link->getAdminLink('AdminKwtsms');
        $pageUrl = $adminLink . '&tab=logs';
        if (!empty($filterStatus)) {
            $pageUrl .= '&filter_status=' . urlencode($filterStatus);
        }
        if (!empty($filterEventType)) {
            $pageUrl .= '&filter_event_type=' . urlencode($filterEventType);
        }
        if (!empty($filterDateFrom)) {
            $pageUrl .= '&filter_date_from=' . urlencode($filterDateFrom);
        }
        if (!empty($filterDateTo)) {
            $pageUrl .= '&filter_date_to=' . urlencode($filterDateTo);
        }
        if (!empty($filterSearch)) {
            $pageUrl .= '&filter_search=' . urlencode($filterSearch);
        }

        $this->context->smarty->assign(array(
            'logs'               => $logsData['rows'],
            'logs_total'         => $logsData['total'],
            'logs_page'          => $page,
            'logs_pages'         => $totalPages,
            'logs_page_url'      => $pageUrl,
            'event_types'        => $eventTypes,
            'filter_status'      => $filterStatus,
            'filter_event_type'  => $filterEventType,
            'filter_date_from'   => $filterDateFrom,
            'filter_date_to'     => $filterDateTo,
            'filter_search'      => $filterSearch,
            'admin_link_raw'     => $this->context->link->getAdminLink('AdminKwtsms', false),
            'admin_token'        => Tools::getAdminTokenLite('AdminKwtsms'),
        ));

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/logs.tpl'
        );
    }

    private function processLogs()
    {
        $action = Tools::getValue('action');

        if ($action === 'clear_logs') {
            $result = KwtsmsLogger::clearAll();

            if ($result) {
                $this->context->smarty->assign(array(
                    'logs_message'      => $this->module->l('All logs have been cleared.'),
                    'logs_message_type' => 'success',
                ));
            } else {
                $this->context->smarty->assign(array(
                    'logs_message'      => $this->module->l('Failed to clear logs.'),
                    'logs_message_type' => 'danger',
                ));
            }
        }
    }

    // =========================================================================
    // Help Tab
    // =========================================================================

    private function renderHelp()
    {
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'kwtsms/views/templates/admin/help.tpl'
        );
    }
}
