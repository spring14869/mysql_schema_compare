<?php


namespace Marleychang\MysqlSchemaCompare\Services;


class CompareService
{
    protected $db;

    protected $schemaService;

    protected $standardConn;

    protected $standardConfig;

    protected $comparedConn;

    protected $comparedConfig;

    protected $compareInfo;

    public function __construct()
    {
        $this->db = new DbService();
        $this->schemaService = new TableSchemaService();
    }


    /**
     * 設定連線資訊
     * @param $standard
     * @param $compared
     * @return $this
     */
    public function setConfigs($standard, $compared)
    {
        $this->standardConfig = $standard;
        $this->comparedConfig = $compared;

        if ($this->standardConn) {
            $this->standardConn->disconnect();
        }

        if ($this->standardConn) {
            $this->comparedConn->disconnect();
        }

        $this->standardConn = $this->db->getInstance($standard['host'], $standard['username'], $standard['password'], $standard['database']);
        $this->comparedConn = $this->db->getInstance($compared['host'], $compared['username'], $compared['password'], $compared['database']);

        return $this;
    }


    public function doCompare()
    {
        $standardInfos = $this->schemaService->getInfos($this->standardConn, $this->standardConfig['database']);
        $compareInfos = $this->schemaService->getInfos($this->comparedConn, $this->comparedConfig['database']);

        $this->compareInfo = $this->compareInfos($standardInfos, $compareInfos);

        return $this;
    }


    /**
     * 取得比較結果
     * @return mixed
     */
    public function getCompareInfo()
    {
        return $this->compareInfo;
    }


    /**
     * 比對程序
     * @param $standardData
     * @param $compareData
     * @return array
     */
    protected function compareInfos($standardData, $compareData)
    {
        // 基準資料庫
        $standardTables = array_keys($standardData);
        $standardDatabaseName = $standardData[$standardTables[0]]['TABLE_SCHEMA'];

        $result = [];
        foreach ($compareData as $tableName => $tableInfo) {

            $info = [
                'std_database' => $standardDatabaseName,
                'database' => $tableInfo['TABLE_SCHEMA'],
                'table' => $tableName
            ];

            if (!isset($standardData[$tableName])) {
                $info['issue']['table'][] = [
                    'type' => CompareIssues::NEW_TABLE,
                    'column' => '',
                    'std' => '',
                    'compare' => $tableName,
                    'desc' => 'is new Table.'
                ];

                $result[] = $info;
                continue;
            }

            $standardTable = $standardData[$tableName];
            $standardColumn = $standardData[$tableName]['COLUMNS'];

            if ($tableInfo['ENGINE'] != $standardTable['ENGINE']) {
                $info['issue']['table'][] = [
                    'type' => CompareIssues::TABLE_ENGINE,
                    'column' => '',
                    'std' => $standardTable['ENGINE'],
                    'compare' => $tableInfo['ENGINE'],
                    'desc' => '2010年MySQL5.5版之後預設InnoDB'
                ];
            }


            if ($tableInfo['TABLE_COLLATION'] != $standardTable['TABLE_COLLATION']) {
                $info['issue']['table'][] = [
                    'type' => CompareIssues::TABLE_COLLATION,
                    'column' => '',
                    'std' => $standardTable['TABLE_COLLATION'],
                    'compare' => $tableInfo['TABLE_COLLATION'],
                    'desc' => "資料排序機制"
                ];
            }


            foreach ($tableInfo['COLUMNS'] as $columnName => $columnInfo) {
                if (!isset($standardColumn[$columnName])) {
                    $info['issue']['column'][] = [
                        'type' => CompareIssues::NEW_COLUMN,
                        'column' => $columnName,
                        'std' => '',
                        'compare' => $columnName,
                        'desc' => ''
                    ];
                    continue;
                }

                if ($standardColumn[$columnName]['DATA_TYPE'] != $columnInfo['DATA_TYPE']) {
                    $info['issue']['column'][] = [
                        'type' => CompareIssues::COLUMN_DATA_TYPE,
                        'column' => $columnName,
                        'std' => $standardColumn[$columnName]['DATA_TYPE'],
                        'compare' => $columnInfo['DATA_TYPE'],
                        'desc' => "資料型態差異"
                    ];
                } elseif ($standardColumn[$columnName]['COLUMN_TYPE'] != $columnInfo['COLUMN_TYPE']) {
                    $info['issue']['column'][] = [
                        'type' => CompareIssues::COLUMN_DATA_TYPE,
                        'column' => $columnName,
                        'std' => $standardColumn[$columnName]['COLUMN_TYPE'],
                        'compare' => $columnInfo['COLUMN_TYPE'],
                        'desc' => "資料型態{$columnInfo['DATA_TYPE']}差異"
                    ];
                }

                $diff = array_diff($standardColumn[$columnName]['INDEXS'], $columnInfo['INDEXS']);
                $diff2 = array_diff($columnInfo['INDEXS'], $standardColumn[$columnName]['INDEXS']);
                if ($diff || $diff2) {
                    $diffNames = array_merge($diff, $diff2);

                    $info['issue']['column'][] = [
                        'type' => CompareIssues::COLUMN_KEY,
                        'column' => $columnName,
                        'std' => implode(', ', $standardColumn[$columnName]['INDEXS']),
                        'compare' => implode(', ', $columnInfo['INDEXS']),
                        'desc' => '索引差異' . implode(',', $diffNames)
                    ];
                }


                if ($standardColumn[$columnName]['IS_NULLABLE'] != $columnInfo['IS_NULLABLE']) {
                    $info['issue']['column'][] = [
                        'type' => CompareIssues::COLUMN_NULLABLE,
                        'column' => $columnName,
                        'std' => $standardColumn[$columnName]['IS_NULLABLE'],
                        'compare' => $columnInfo['IS_NULLABLE'],
                        'desc' => ''
                    ];
                }

            }

            $result[] = $info;
        }


        return $result;
    }

    /**
     * 視覺化報表
     * @param array $hidden
     * @return string
     */
    public function visualize($hidden = [])
    {
        $data = $this->getCompareInfo();

        $html = '<style>
        table {
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid;
        }
        th, td {
            padding: 0 6px;
            line-height: 150%;
        }
        th {
            background-color: #ccc;
        }
        </style>';

        $html .= '<table>';
        $html .= '<tr>';
        $html .= '<th>standard DB</th>';
        $html .= '<th>compare DB</th>';
        $html .= '<th>Table Name</th>';
        $html .= '<th>Column Name</th>';
        $html .= '<th>Issue Level</th>';
        $html .= '<th>Issue Type</th>';
        $html .= '<th>standard info</th>';
        $html .= '<th>compare info</th>';
        $html .= '<th>description</th>';
        $html .= '</tr>';

        foreach ($data as $table) {
            if (!isset($table['issue'])) {
                continue;
            }

            // Issues
            foreach ($table['issue'] as $issueLevel => $issues) {

                // Issue types
                foreach ($issues as $issue) {
                    if (!empty($hidden) && in_array($issue['type'], $hidden)) {
                        continue;
                    }

                    $html .= '<tr>';
                    $html .= "<td align='center'>{$table['std_database']}</td>";
                    $html .= "<td align='center'>{$table['database']}</td>";
                    $html .= "<td>{$table['table']}</td>";
                    $html .= "<td>{$issue['column']}</td>";
                    $html .= "<td align='center'>{$issueLevel}</td>";
                    $html .= "<td align='center'>" . $this->issueTypeDisplay($issue['type']) . "</td>";
                    $html .= "<td>{$issue['std']}</td>";
                    $html .= "<td>{$issue['compare']}</td>";
                    $html .= "<td>{$issue['desc']}</td>";
                    $html .= '</tr>';
                }
            }
        }
        $html .= '</table>';

        return $html;
    }


    public function issueTypeDisplay($issueType)
    {
        switch ($issueType) {
            case CompareIssues::NEW_TABLE:
            case CompareIssues::NEW_COLUMN:
                return 'NEW';
                break;
            case CompareIssues::TABLE_ENGINE:
                return 'ENGINE';
                break;
            case CompareIssues::TABLE_COLLATION:
                return 'COLLATION';
                break;

            case CompareIssues::COLUMN_KEY:
                return 'KEY';
                break;
            case CompareIssues::COLUMN_DATA_TYPE:
                return 'DATA TYPE';
                break;
            case CompareIssues::COLUMN_NULLABLE:
                return 'NULLABLE';
                break;
        }

        return $issueType;
    }


    /**
     * 取得資料表異常類別
     * @return array
     */
    public function getIssueValuesByTable()
    {
        return [
            CompareIssues::NEW_TABLE,
            CompareIssues::TABLE_ENGINE,
            CompareIssues::TABLE_COLLATION
        ];
    }


    public function __destruct()
    {
        $this->standardConn->disconnect();
        $this->comparedConn->disconnect();
    }
}