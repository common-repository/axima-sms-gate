<?php

use Pays\SmsGate\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client(new \GuzzleHttp\Client(), 'greeny', '');

echo '<pre>' . htmlspecialchars($client->sendSms('Hello World! (-.-(-.-)-.-)', '602500016')) . '</pre>';
