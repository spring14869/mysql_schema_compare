<?php

require '../vendor/autoload.php';

use Marleychang\MysqlSchemaCompare\Services\CompareIssues;
use Marleychang\MysqlSchemaCompare\Services\CompareService;

$compareService = new CompareService();

$standard = [
    'host' => 'ip1',
    'username' => 'root',
    'password' => '',
    'database' => 'database1'
];

$compared = [
    'host' => 'ip2',
    'username' => 'root',
    'password' => '',
    'database' => 'database2'
];

// hidden useless compare
$hiddenType = [
    CompareIssues::NEW_TABLE
];

$diffHtml = $compareService->setConfigs($standard, $compared)
    ->doCompare()
    ->visualize($hiddenType);


// display html
echo $diffHtml;
