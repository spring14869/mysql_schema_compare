<?php
namespace Marleychang\MysqlSchemaCompare\Services;

class DbService
{
    public function getInstance($host, $username, $password, $database)
    {
        return new \MysqliDb($host, $username, $password, $database);
    }
}