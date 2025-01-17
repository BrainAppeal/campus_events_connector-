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

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Http\Client;
use GuzzleHttp\Promise\PromiseInterface;

class FileImporter extends AbstractFileImporter implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    /**
     * @param int $storageId
     * @param string $storageFolder
     */
    public function initialize(int $storageId, string $storageFolder): void
    {
        $this->storageId = $storageId;
        $this->storageFolder = $storageFolder;
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @param string $tempFilenameAndPath
     * @param \BrainAppeal\CampusEventsConnector\Http\PromiseInterface|PromiseInterface $promise
     */
    private function addToQueue($object, $property, $data, $tempFilenameAndPath, $promise, $targetFileName): void
    {
        $importId = (int) (!empty($data['id']) ? $data['id'] : $object->getUid());
        $this->newReferenceQueue[] = [
            'object' => $object,
            'property' => $property,
            'data' => $data,
            'download' => [
                'file' => $tempFilenameAndPath,
                'promise' => $promise,
            ],
            'import_id' => $importId,
            'target_file_name' => $targetFileName,
        ];
        $this->mappingOfUsedFileNamesToReferenceUid[$targetFileName] = null;
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     */
    public function enqueueFileMapping($object, $property, $data): void
    {
        if (empty($data['hash']) || empty($data['url'])) {
            return;
        }

        $importId = (int) (!empty($data['id']) ? $data['id'] : $object->getUid());
        $fileBaseName = basename((string) $data['url']);
        $targetFileName = $this->getImportFileName($importId, $fileBaseName);
        if (!$targetFileName) {
            return;
        }
        $existingReference = $this->getFileReferenceIfExists($object, $property, $targetFileName);

        if (null !== $existingReference && $this->originalResourceIsValid($existingReference)) {
            $fileReferenceUid = $existingReference->getOriginalResource()->getUid();
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
            $this->mappingOfUsedFileNamesToReferenceUid[$targetFileName] = $fileReferenceUid;
        } else {
            $tempFilenameAndPath = $this->getTempFilePath();

            try {
                $promise = $this->client->getAsync($data['url'], ['sink' => $tempFilenameAndPath]);
            } catch (\BrainAppeal\CampusEventsConnector\Http\HttpException $e) {
                unset($e);
                return;
            }

            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $promise, $targetFileName);
        }
    }

    /**
     * @param array<string, mixed> $queueEntry
     * @return string|null
     */
    protected function getDownloadFromQueueEntry(array $queueEntry): ?string
    {
        /**
         * @var \BrainAppeal\CampusEventsConnector\Http\PromiseInterface $downloadPromise
         */
        $downloadPromise = $queueEntry['download']['promise'];
        $downloadFile = $queueEntry['download']['file'];

        try {
            $downloadPromise->wait();
        } catch (\BrainAppeal\CampusEventsConnector\Http\HttpException $e) {
            unset($e);
        }
        if ('fulfilled' === $downloadPromise->getState()) {
            return $downloadFile;
        }

        return null;
    }

}
