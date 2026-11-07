<?php
/**
 * FILE:      .github/ci/live-send.php
 * CREATED:   2026-11-07 18:06 +03
 * TASK:      prestashop-ci-install
 * AUTHOR:    M Alhassoun
 * WHAT:      Inside the PrestaShop container: connects the module's gateway with the CI account, turns sending and test
 *            mode on, sends one SMS through KwtsmsSender::send() to KWTSMS_TEST_NUMBER, checks the log row is test mode.
 *            Prints no phone number and no credentials.
 * WHY:       Proves a fresh install works end to end before a release.
 * USAGE:     php /tmp/live-send.php   (run by .github/workflows/install.yml)
 * DEPENDS:   Installed kwtsms module; env KWTSMS_USERNAME, KWTSMS_PASSWORD, KWTSMS_TEST_NUMBER.
 */
require '/var/www/html/config/config.inc.php';
require_once _PS_MODULE_DIR_ . 'kwtsms/kwtsms.php';

Configuration::updateValue('KWTSMS_TEST_MODE', 1);
Configuration::updateValue('KWTSMS_GATEWAY_ENABLED', 1);
Configuration::updateValue('KWTSMS_SENDER_ID', 'KWT-SMS');
if ((int) Configuration::get('KWTSMS_TEST_MODE') !== 1) {
    fwrite(STDERR, "ABORT: test mode is off, nothing sent\n");
    exit(2);
}

$gateway = new KwtsmsGateway();
$login = $gateway->connect((string) getenv('KWTSMS_USERNAME'), (string) getenv('KWTSMS_PASSWORD'));
echo 'connect: ' . json_encode(array('success' => $login['success'] ?? null)) . "\n";
if (empty($login['success'])) {
    exit(1);
}

$result = KwtsmsSender::send((string) getenv('KWTSMS_TEST_NUMBER'), 'QA-prestashop-ci ' . gmdate('YmdHi'), 'gateway_test', array('recipient_type' => 'admin'));
echo 'result: ' . json_encode(array('success' => $result['success'] ?? null, 'error' => $result['error'] ?? null)) . "\n";

$row = Db::getInstance()->getRow('SELECT status, test_mode FROM ' . _DB_PREFIX_ . 'kwtsms_log ORDER BY id_kwtsms_log DESC');
echo 'log row: ' . json_encode($row) . "\n";
exit(!empty($result['success']) && (int) ($row['test_mode'] ?? 0) === 1 ? 0 : 1);
