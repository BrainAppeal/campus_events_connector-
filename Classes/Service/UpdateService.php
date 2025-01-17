<?php

namespace BrainAppeal\CampusEventsConnector\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdateService
{
    /** @var array $tables */
    protected $tables = [
        'tx_campuseventsconnector_domain_model_event',
        'tx_campuseventsconnector_domain_model_location',
        'tx_campuseventsconnector_domain_model_speaker',
        'tx_campuseventsconnector_domain_model_organizer',
        'tx_campuseventsconnector_domain_model_timerange',
        'tx_campuseventsconnector_domain_model_category',
        'tx_campuseventsconnector_domain_model_targetgroup',
        'tx_campuseventsconnector_domain_model_filtercategory',
        'tx_campuseventsconnector_domain_model_convertconfiguration',
        'tx_campuseventsconnector_domain_model_viewlist',
        'sys_file_reference',
    ];
    protected $fields = [
        'import_source',
        'import_id',
        'imported_at',
    ];

    /**
     * @return bool
     */
    public function checkIfUpdateIsNeeded(): bool
    {
        $updateNeeded = false;
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($this->tables as $table) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $tableContents = $queryBuilder->select('*')->from($table)
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();
            if (!empty($tableContents)
                && ((array_key_exists('zzz_deleted_import_source', $tableContents)
                || array_key_exists('import_source', $tableContents))
                && ((string)$tableContents['ce_import_source'] === ''))) {
                $updateNeeded = true;
            }
        }
        return $updateNeeded;
    }

    /**
     * @return bool
     */
    public function performUpdates(): bool
    {
        foreach ($this->tables as $table) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class);
            $queryBuilder = $connection->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $tableContents = $queryBuilder->select('*')->from($table)->executeQuery()->fetchAssociative();
            if (!empty($tableContents)) {
                foreach ($this->fields as $field) {
                    $fieldPrefixes = ['zzz_deleted_', ''];
                    foreach ($fieldPrefixes as $fieldPrefix) {
                        if (array_key_exists($fieldPrefix . $field, $tableContents)
                            && ($tableContents['ce_' . $field] === '' ||  $tableContents['ce_' . $field] === null)) {
                            $queryBuilder = $connection->getQueryBuilderForTable($table);
                            $queryBuilder->update($table)
                                ->where(
                                    $queryBuilder->expr()->eq(
                                        'uid',
                                        $queryBuilder->createNamedParameter($tableContents['uid'], Connection::PARAM_INT)
                                    )
                                )
                                ->set('ce_' . $field, $tableContents[$fieldPrefix . $field]);
                            $queryBuilder->executeStatement();
                            break;
                        }
                    }
                }
            }
        }
        return true;
    }
}
