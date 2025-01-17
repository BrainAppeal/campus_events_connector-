<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer\DBAL;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Repository\AbstractImportedRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class DBAL implements \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface, \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var AbstractImportedRepository[]
     */
    private $repositories = [];

    /**
     * @var string[]
     */
    private $classTableMapping = [];

    /**
     * @param string $modelClass
     * @return AbstractImportedRepository|null
     */
    private function getRepository(string $modelClass): ?AbstractImportedRepository
    {
        if (!isset($this->repositories[$modelClass])) {

            $repository = null;
            $repositoryClass = str_replace('\\Model\\', '\\Repository\\', $modelClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                /** @var AbstractImportedRepository $repository */
                $repository = GeneralUtility::makeInstance($repositoryClass);
            }
            $this->repositories[$modelClass] = $repository;
        }

        return $this->repositories[$modelClass];
    }

    /**
     * @param string $modelClass
     * @param string $importSource
     * @param int $importId
     * @param null|int|int[] $pid
     * @return ImportedModelInterface|null
     */
    public function findByImport(string $modelClass, string $importSource, int $importId, $pid): ?ImportedModelInterface
    {
        $repository = $this->getRepository($modelClass);
        if (null === $repository) {
            return null;
        }

        return $repository->findByImport($importSource, $importId, $pid);
    }

    /**
     * @param ImportedModelInterface[] $objects
     */
    public function updateObjects($objects)
    {
        foreach ($objects as $object) {
            $repository = $this->getRepository($object::class);
            if ($object instanceof ImportedModelInterface && null !== $repository) {
                $object->setCeImportedAt(time());
                if ($object->getUid() > 0) {
                    $repository->update($object);
                } else {
                    $repository->add($object);
                }
            }
        }
        if (isset($repository)) {
            $repository->persistAll();
        }
    }

    private function deleteRawFromTable($tableName, $importSource, $pid, $importTimestamp, $excludeUids)
    {
        $pid = (int)$pid;
        $importSource = preg_replace("/['\"]/", "", (string) $importSource);
        $importTimestamp = (int)$importTimestamp;

        /** @noinspection SqlResolve */
        $deleteSql = "DELETE FROM $tableName WHERE pid = ? AND ce_import_source = ? AND ce_imported_at < ?";

        $excludeUidsList = implode(',', array_filter($excludeUids,  'is_numeric'));
        if ($excludeUidsList !== '') {
            $deleteSql .= " AND uid NOT IN ($excludeUidsList)";
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($tableName);
        $connection->executeStatement($deleteSql, [$pid, $importSource, $importTimestamp]);
    }

    /**
     * @inheritDoc
     */
    public function processImportedItems($tableName, $importIdList, $importSource, $tstamp)
    {
        $uidListCsv = implode(',', array_filter($importIdList,  'is_numeric'));
        $connection = $this->getConnectionForTable($tableName);
        if (!empty($uidListCsv)) {
            // Update timestamp for all items from the api list result + mark as not deleted
            $sql = "UPDATE $tableName SET ce_imported_at = ?, deleted = 0 WHERE ce_import_source = ? AND ce_import_id IN ($uidListCsv)";
            $connection->executeStatement($sql, [$tstamp, $importSource]);
        }
        // Mark all items as deleted that were not included in the api list result
        $sql = "UPDATE $tableName SET tstamp = ?, deleted = 1 WHERE ce_import_source = ?";
        if (!empty($uidListCsv)) {
            $sql .= " AND ce_import_id NOT IN ($uidListCsv)";
        }
        $connection->executeStatement($sql, [$tstamp, $importSource]);
    }

    protected function getConnectionForTable($tableName)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($tableName);
    }

    public function removeNotUpdatedObjects(string $modelClass, string $importSource, int $pid, int $importTimestamp, array $excludeUids = [])
    {
        if (is_a($modelClass, FileReference::class, true)) {
            $this->deleteRawFromTable('sys_file_reference', $importSource, $pid, $importTimestamp, $excludeUids);
        } else {
            $repository = $this->getRepository($modelClass);

            if (null !== $repository) {
                $results = $repository->findByNotImportedSince($importTimestamp, $importSource, $pid);
                foreach ($results as $result) {
                    $repository->remove($result);
                }

                $repository->persistAll();
            }
        }
    }

    private function getTableForModelClass($modelClass)
    {
        if (!isset($this->classTableMapping[$modelClass])) {
            $dataMapper = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class);
            $this->classTableMapping[$modelClass] = $dataMapper->buildDataMap($modelClass)->getTableName();
        }

        return $this->classTableMapping[$modelClass];
    }

    /**
     * @param FileReference $sysFileReference
     * @param array $attribs
     */
    public function updateSysFileReference(FileReference $sysFileReference, $attribs = [])
    {
        $data['sys_file_reference'][$sysFileReference->getUid()] = $attribs;

        // Get an instance of the DataHandler and process the data
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $sysFile
     * @param ImportedModelInterface $target
     * @param string $property
     * @param array $attribs
     * @return int|null
     */
    public function addSysFileReference($sysFile, $target, $property, $attribs = [])
    {
        $uidLocal = $sysFile->getUid();
        $uidForeign = $target->getUid();
        $table = $this->getTableForModelClass($target::class);
        $storagePid = $target->getPid();


        $newId = 'NEW'.$uidForeign.'-'.$uidLocal;

        $attribs = array_replace($attribs,[
            'uid_local'   => $uidLocal,
            'table_local' => 'sys_file',
            'uid_foreign' => $uidForeign,
            'tablenames'  => $table,
            'fieldname'   => $property,
            'pid'         => $storagePid,
        ]);
        $data = [
            'sys_file_reference' => [$newId => $attribs],
            $table               => [$uidForeign => [$property => $newId]],
        ];

        // Get an instance of the DataHandler and process the data
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        if (!empty($dataHandler->substNEWwithIDs[$newId])) {
            return $dataHandler->substNEWwithIDs[$newId];
        }
        return null;
    }


    /**
     * @param int $pid
     * @return bool
     */
    public function checkIfPidIsValid($pid)
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->resetRestrictions();
        $pageRowOrNull = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', (int) $pid))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (!empty($pageRowOrNull) && (int) $pageRowOrNull['uid'] == $pid) {
            return true;
        }
        return false;
    }


}
