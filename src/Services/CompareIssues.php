<?php


namespace Marleychang\MysqlSchemaCompare\Services;


class CompareIssues
{
    const NEW_TABLE = 'new_table';
    const TABLE_ENGINE = 'engine';
    const TABLE_COLLATION = 'collation';
    const NEW_COLUMN = 'new_column';
    const COLUMN_KEY = 'column_key';
    const COLUMN_DATA_TYPE = 'column_data_type';
    const COLUMN_NULLABLE = 'column_nullable';
}