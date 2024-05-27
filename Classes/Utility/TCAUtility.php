<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2020 Brain Appeal GmbH
 *
 * @copyright 2020 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Utility;

class TCAUtility
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getImportFieldConfiguration(): array
    {
        $versionInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        $isLegacyVersion = $versionInformation->getMajorVersion() < 12;
        $tcaColumns = [

            'ce_import_source' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.name',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'eval' => 'trim',
                    'readOnly' => 1,
                ],
            ],
            'ce_import_id' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.ce_import_id',
                'config' => $isLegacyVersion ? [
                    'type' => 'input',
                    'size' => 2,
                    'eval' => 'int',
                    'readOnly' => 1,
                    'default' => 0
                ] : [
                    'type' => 'number',
                    'size' => 2,
                    'readOnly' => true,
                    'default' => 0
                ],
            ],
            'ce_imported_at' => [
                'exclude' => true,
                'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang_db.xlf:tx_campuseventsconnector_domain_model_event.ce_imported_at',
                'config' => $isLegacyVersion ? [
                    'type' => 'input',
                    'renderType' => 'inputDateTime',
                    'eval' => 'datetime,int',
                    'readOnly' => 1,
                ] : [
                    'type' => 'datetime',
                    'readOnly' => true,
                ],
            ],
        ];
        return $tcaColumns;
    }
}
