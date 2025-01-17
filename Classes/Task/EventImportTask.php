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

namespace BrainAppeal\CampusEventsConnector\Task;

use BrainAppeal\CampusEventsConnector\Importer\PostImportHookInterface;
use BrainAppeal\CampusEventsConnector\Utility\CacheUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EventImportTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    public const API_VERSION_LEGACY = 'below-2-27-0';
    public const API_VERSION_ABOVE_227 = 'above-2-27-0';

    public const BASE_URI_DEFAULT = 'https://campusevents.example.com/';

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $apiVersion;

    /**
     * @var string
     */
    public $baseUri;

    /**
     * @var int|null
     */
    public $pid;

    /**
     * @var int
     */
    public $storageId;

    /**
     * @var string
     */
    public $storageFolder;

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\Importer
     */
    private function getImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\Importer $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\Importer::class);

        return $importer;
    }

    /**
     * @return \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter
     */
    private function getExtendedImporter()
    {
        /** @var \BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\BrainAppeal\CampusEventsConnector\Importer\ExtendedImporter::class);

        return $importer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->apiVersion === self::API_VERSION_ABOVE_227) {
            $importer = $this->getExtendedImporter();
            $success = $importer->import($this->baseUri, $this->apiKey, (int) $this->pid, (int) $this->storageId, $this->storageFolder);
            if (!$success) {
                $exceptions = $importer->getExceptions();
                if (!empty($exceptions)) {
                    if ($exceptions[0] instanceof \Exception) {
                        $this->logException($exceptions[0]);
                    } elseif ($exceptions[0] instanceof \Throwable) {
                        $logException = new Exception('Wrapped throwable: ' . $exceptions[0]->getMessage(), $exceptions[0]->getCode(), $exceptions[0]);
                        $this->logException($logException);
                    }

                }
            }
        } else {
            $importer = $this->getImporter();
            $success = $importer->import($this->baseUri, $this->apiKey, (int) $this->pid, (int) $this->storageId, $this->storageFolder);
        }


        $this->callHooks();

        if ($importer->hasChangedData()) {
            /** @var CacheUtility $cacheUtility */
            $cacheUtility = GeneralUtility::makeInstance(CacheUtility::class);
            $cacheUtility->clearCacheForPage($this->pid);
        }

        return $success;
    }

    private function callHooks()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_campuseventsconnector']['postImport'] as $classRef) {
                $hookObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classRef);
                if ($hookObj instanceof PostImportHookInterface || method_exists($hookObj, 'postImport')) {
                    $hookObj->postImport($this->pid);
                }
            }
        }
    }

    /**
     * @return ?string
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion ?: self::API_VERSION_ABOVE_227;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri ?: self::BASE_URI_DEFAULT;
    }

    /**
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getStorageId(): int
    {
        return $this->storageId;
    }

    /**
     * @return string
     */
    public function getStorageFolder(): string
    {
        return $this->storageFolder;
    }

}
