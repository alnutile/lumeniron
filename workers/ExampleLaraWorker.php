<?php
use Illuminate\Encryption\Encrypter;

require_once __DIR__ . '/libs/worker_boot.php';

$payload = getPayload(true);

fire($payload);

function fire($payload)
{
    var_dump($payload);
}

