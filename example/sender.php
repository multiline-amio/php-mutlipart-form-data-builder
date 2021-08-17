<?php

use MultilineAmio\MultipartFormDataBuilder\FormDataBuilder;
use MultilineAmio\MultipartFormDataBuilder\FormDataBuilderException;

require_once __DIR__ . '/../src/MultipartFormDataBuilder/FormDataBuilder.php';
require_once __DIR__ . '/../src/MultipartFormDataBuilder/FormDataBuilderException.php';

$formDataBuilder = new FormDataBuilder();

try {
    $data = $formDataBuilder->addData('abc', 'def')
        ->addData('fed[]', 'cba')
        ->addData('fed[]', 'def')
        ->addFile('file[]', __DIR__ . '/receiver.php')
        ->addFile('file[]', __DIR__ . '/sender.php')
        ->build();
} catch (FormDataBuilderException $e) {
    die($e->getMessage());
}

$host = "localhost";
$path = "/receiver.php";
$fp = fsockopen($host, 8888);
if ($fp) {
    $str = "POST " . $path . " HTTP/1.1\r\n";
    $str .= "Host: " . $host . "\r\n";
    $str .= "Content-Type: " . $formDataBuilder->getContentType() . "\r\n";
    $str .= "Content-Length: " . strlen($data) . "\r\n";
    $str .= "Connection: close\r\n\r\n";

    $str .= $data;

    fwrite($fp, $str);

    $result = '';
    while (!feof($fp)) {
        $result .= fgets($fp, 128);
    }
    fclose($fp);

    $response = explode("\r\n\r\n", $result);
    echo $response[1];
}
