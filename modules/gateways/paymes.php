<?php

if (! defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/paymes/paymes-api/vendor/autoload.php';

/**
 * @return string[]
 */
function paymes_MetaData()
{
    return [
        'DisplayName' => 'Paymes Sanal POS',
        'APIVersion' => '1.0',
    ];
}

/**
 * @return string[][]
 */
function paymes_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Paymes Sanal POS',
        ],
        'publicKey' => [
            'FriendlyName' => 'Public Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'paym.es\'in verdiği Public Key\'i buraya girin',
        ],
        'secretKey' => [
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'paym.es\'in verdiği Secret Key\'i buraya girin.',
        ],
    ];
}

/**
 * @param $params
 */
function paymes_capture($params)
{
    // ...
}

/**
 * @param $params
 * @return string
 */
function paymes_link($params)
{
    $secretKey = $params['secretKey'];
    $publicKey = $params['publicKey'];

    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];
    $ip = json_decode($params['clientdetails']['model'], true)['ip'];
    $address = sprintf(
        '%s - %s - %s - %s - %s',
        $address1,
        $address2,
        $city,
        $country,
        $ip,
    );

    $companyName = $params['companyname'];
    $moduleName = $params['paymentmethod'];
    $langPayNow = $params['langpaynow'];

    $api = new \IsaEken\Paymes\Payment;
    $api
        ->setSecretKey($secretKey)
        ->setPublicKey($publicKey)
        ->setOrderId($invoiceId)
        ->setPrice($amount)
        ->setCurrency($currencyCode)
        ->setProductName($description)
        ->setBuyerName($firstname . ' ' .$lastname)
        ->setBuyerPhone($phone)
        ->setBuyerEmail($email)
        ->setBuyerAddress($address)
        ->makeHash();

    try {
        $response = $api->run();
        if ($response->getStatus() == 'error') {
            logTransaction($moduleName, $response->getMessage(), 'Failure');
            return 'declined';
        }

        logTransaction($moduleName, '', 'Pending');
        $url = $response->getReturnUrl();

        $htmlOutput = '<form method="post" action="' . $url . '">';
        $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
        $htmlOutput .= '</form>';

        return $htmlOutput;
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        logTransaction($moduleName, $e, 'Failure');
        return 'declined';
    }
}
