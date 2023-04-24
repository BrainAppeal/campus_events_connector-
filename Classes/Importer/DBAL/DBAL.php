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
     * @return AbstractImportedRepository
     */
    private function getRepository($modelClass)
    {
        if (!isset($this->repositories[$modelClass])) {

            $repository = null;
            $repositoryClass = str_replace('\\Model\\', '\\Repository\\', $modelClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
                /** @var AbstractImportedRepository $repository */
                $repository = $objectManager->get($repositoryClass);
            }
            $this->repositories[$modelClass] = $repository;
        }

        return $this->repositories[$modelClass];
    }

    public function findByImport($modelClass, $importSource, $importId, $pid)
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
            $repository = $this->getRepository(get_class($object));
            $object->setCeImportedAt(time());
            if ($object->getUid() > 0) {
                $repository->update($object);
            } else {
                $repository->add($object);
            }
        }
        if (isset($repository)) {
            $repository->persistAll();
        }
    }

    private function deleteRawFromTable($tableName, $importSource, $pid, $importTimestamp, $excludeUids)
    {
        $pid = intval($pid);
        $importSource = preg_replace("/['\"]/", "", $importSource);
        $importTimestamp = intval($importTimestamp);

        /** @noinspection SqlResolve */
        $deleteSql = "DELETE FROM $tableName WHERE pid = ? AND ce_import_source = ? AND ce_imported_at < ?";

        $excludeUidsList = implode(',', array_filter($excludeUids,  'is_numeric'));
        if (strlen($excludeUidsList) > 0) {
            $deleteSql .= " AND uid NOT IN ($excludeUidsList)";
        }

        /** @var ConnectionPool $connectionPool */
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($tableName);
        $statement = $connection->prepare($deleteSql);
        $statement->execute([$pid, $importSource, $importTimestamp]);
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

            $statement = $connection->prepare($sql);
            $statement->execute([$tstamp, $importSource]);
        }
        // Mark all items as deleted that were not included in the api list result
        $sql = "UPDATE $tableName SET tstamp = ?, deleted = 1 WHERE ce_import_source = ?";
        if (!empty($uidListCsv)) {
            $sql .= " AND ce_import_id NOT IN ($uidListCsv)";
        }
        $statement = $connection->prepare($sql);
        $statement->execute([$tstamp, $importSource]);
    }

    protected function getConnectionForTable($tableName)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool->getConnectionForTable($tableName);
    }

    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp, $excludeUids = [])
    {
        if ($modelClass == FileReference::class) {
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
     * @param FileReference $fileReference
     * @param array $attribs
     */
    public function updateSysFileReference($fileReference, $attribs = [])
    {
        $data['sys_file_reference'][$fileReference->getUid()] = $attribs;

        // Get an instance of the DataHandler and process the data
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->start($data, array());
        $dataHandler->process_datamap();
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\File $sysFile
     * @param ImportedModelInterface $target
     * @param string $field
     * @param array $attribs
     * @return int|null
     */
    public function addSysFileReference($sysFile, $target, $field, $attribs = [])
    {
        $uidLocal = $sysFile->getUid();
        $uidForeign = $target->getUid();
        $table = $this->getTableForModelClass(get_class($target));
        $storagePid = $target->getPid();


        $newId = 'NEW'.$uidForeign.'-'.$uidLocal;

        $attribs = array_replace($attribs,[
            'uid_local'   => $uidLocal,
            'table_local' => 'sys_file',
            'uid_foreign' => $uidForeign,
            'tablenames'  => $table,
            'fieldname'   => $field,
            'pid'         => $storagePid,
        ]);
        $data = [
            'sys_file_reference' => [$newId => $attribs],
            $table               => [$uidForeign => [$field => $newId]],
        ];

        // Get an instance of the DataHandler and process the data
        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $dataHandler->start($data, array());
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
        $statement = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', intval($pid)))
            ->execute();
        while ($checkPid = $statement->fetchColumn(0)) {
            if ($checkPid == $pid) {
                return true;
            }
        }
        return false;
    }


}
