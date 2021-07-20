<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../paymes/paymes-api/vendor/autoload.php';

App::load_function('gateway');
App::load_function('invoice');

$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (! $gatewayParams['type']) {
    die("Module Not Activated");
}

$response = new \IsaEken\Paymes\Callback();
$response->fill([
    'paymesOrderId' => (string) $_POST['paymesOrderId'],
    'orderId'       => (string) $_POST['orderId'],
    'type'          => (string) $_POST['type'],
    'message'       => (string) $_POST['message'],
    'price'         => (string) $_POST['price'],
    'currency'      => (string) $_POST['currency'],
    'hash'          => (string) $_POST['hash'],
]);

if ($response->status()) {
    addInvoicePayment(
        intval($_POST['orderId']),
        $_POST['paymesOrderId'],
        floatval($_POST['price']),
        floatval(0),
        $gatewayModuleName
    );
    callback3DSecureRedirect(intval($_POST['orderId']), true);
}
