<?php
namespace Marleychang\MysqlSchemaCompare\Services;

class TableSchemaService
{
    public function getInfos($connection, $databaseName)
    {
        $db = $databaseName;

        $tables = $this->getTableList($connection, $db);

        // 重整資料表陣列
        $tableFormat = [];
        foreach ($tables as $idx => $table) {
            $tableFormat[$table['TABLE_NAME']] = $table;
        }

        foreach ($tableFormat as $tableName => $table) {
            $columns = $this->getColumnList($connection, $db, $table['TABLE_NAME']);

            $columnKeys = $this->getTableKeys($connection, $db, $table['TABLE_NAME']);

            // 欄位有多少索引
            $keyFormat = [];
            foreach ($columnKeys as $keyInfo) {
                $keyFormat[$keyInfo['COLUMN_NAME']][] = $keyInfo['INDEX_NAME'];
            }

            // 重整資料欄位陣列
            $columnFormat = [];
            foreach ($columns as $col) {
                // 欄位有索引
                $col['INDEXS'] = isset($keyFormat[$col['COLUMN_NAME']]) ? $keyFormat[$col['COLUMN_NAME']] : [];

                $columnFormat[$col['COLUMN_NAME']] = $col;
            }
            $tableFormat[$tableName]['COLUMNS'] = $columnFormat;
        }

        return $tableFormat;
    }


    /**
     * 取得資料表清單
     * @param $conn
     * @param $database
     * @return mixed
     */
    protected function getTableList($conn, $database)
    {
        $sql = 'SELECT 
                TABLE_SCHEMA,
                TABLE_NAME,
                ENGINE,
                TABLE_COLLATION,
                CREATE_TIME
            FROM information_schema.tables 
            WHERE TABLE_SCHEMA = "' . $database . '"
            ORDER BY TABLE_NAME ASC;';

        return $conn->query($sql);
    }


    /**
     * 取得資料表索引
     * @param $conn
     * @param $database
     * @param $table
     * @return mixed
     */
    protected function getTableKeys($conn, $database, $table)
    {
        $sql = 'SELECT NON_UNIQUE, INDEX_NAME, COLUMN_NAME, INDEX_TYPE
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = "' . $database . '" AND TABLE_NAME = "' .  $table . '";';

        return $conn->query($sql);
    }


    /**
     * 取得欄位清單
     * @param $conn
     * @param $database
     * @param $table
     * @return mixed
     */
    protected function getColumnList($conn, $database, $table)
    {
        $sql = 'SELECT
                    * 
                FROM information_schema.columns
                WHERE TABLE_SCHEMA = "' . $database . '"
                AND TABLE_NAME = "' . $table . '"
                ORDER BY ORDINAL_POSITION ASC';

        return $conn->query($sql);
    }
}