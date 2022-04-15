<?php

namespace App\Helpers\Api;

use Exception;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Dataset;
use Google\Cloud\Core\ExponentialBackoff;

class BigQuery
{

    private string $projectId = 'peak-age-279206';
    private string $datasetId = 'dashboard';
    private string $keyPath;

    private BigQueryClient $BigQuery;
    private Dataset $dashboard;

    public function __construct()
    {
        $this->keyPath = base_path() . "/credentials/google/credentials.json";

        $this->BigQuery = new BigQueryClient([
            'projectId' => $this->projectId,
            'keyFilePath' => $this->keyPath,
        ]);

        $this->dashboard = $this->BigQuery->dataset( $this->datasetId );
    }

    /**
     * @throws Exception
     */
    public function getYM_UidByRoistat_Id( $CLIENT_NAME ): array
    {
        $sql = "SELECT DISTINCT YM_UID FROM `peak-age-279206.googleDataStuidio.FL-visitors` WHERE CLIENT_NAME IN (" . $CLIENT_NAME . ")";
        return $this->getQueryResults($sql);
    }

    public function getQueryResults( string $query ): array
    {
        $options = [
            "configuration" => [
                "query" => [
                    "defaultDataset" => [
                        "datasetId" => $this->datasetId,
                    ],
                ],
            ],
        ];

        $query = $this->BigQuery->query($query, $options);
        $queryResults = $this->BigQuery->runQuery($query);

        $rows = [];
        foreach ($queryResults as $i => $row) {
            foreach ($row as $column => $value)
                $rows[$i][$column] = trim(json_encode($value), '"');
        }
        return $rows;
    }

}
