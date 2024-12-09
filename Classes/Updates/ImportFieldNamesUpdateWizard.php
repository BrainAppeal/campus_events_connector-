<?php

namespace BrainAppeal\CampusEventsConnector\Updates;

use BrainAppeal\CampusEventsConnector\Service\UpdateService;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('campusEventsConnector')]
class ImportFieldNamesUpdateWizard implements UpgradeWizardInterface
{
    /** @var UpdateService */
    protected $updateService;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @return UpdateService
     */
    protected function getUpdateService(): UpdateService
    {
        if ($this->updateService === null) {
            $this->updateService = GeneralUtility::makeInstance(UpdateService::class);
        }
        return $this->updateService;
    }

    /**
     * Returns the title attribute
     *
     * @return string The title of this update wizard
     */
    public function getTitle(): string
    {
        return 'Campus Events: Migrate import fields';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Campus Events: Migrates the old import fields to the new named ones.';
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        return $this->getUpdateService()->performUpdates();
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $description = '';
        $result = $this->checkForUpdate();
        if ($this->output !== null) {
            $this->output->write($description);
        }
        return $result;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Setter injection for output into upgrade wizards
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(): bool
    {
        return $this->getUpdateService()->checkIfUpdateIsNeeded();
    }

}
