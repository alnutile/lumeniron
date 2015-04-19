<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Encryption\Encrypter;

function setRequestForConsoleEnvironment(&$app)
{
    $url = 'http://localhost';

    $parameters = array($url, 'GET', array(), array(), array(), $_SERVER);

    $app->refreshRequest(static::onRequest('create', $parameters));
}

setRequestForConsoleEnvironment($app);
$app->boot();

function decryptPayload($payload)
{
    $crypt = new Encrypter(getenv('IRON_ENCRYPTION_KEY'));
    $payload = $crypt->decrypt($payload);
    return json_decode(json_encode($payload), FALSE);
}
