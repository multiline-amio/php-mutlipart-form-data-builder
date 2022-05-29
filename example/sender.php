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
        ->send("http://localhost/receiver.php");
} catch (FormDataBuilderException $e) {
    die($e->getMessage());
}
