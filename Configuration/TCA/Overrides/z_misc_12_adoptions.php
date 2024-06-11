<?php

defined('TYPO3') or die;

call_user_func(static function () {
    $ll = 'LLL:EXT:news/Resources/Private/Language/locallang_db.xlf:';
    $versionInformation = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
    if ($versionInformation->getMajorVersion() > 11) {
        $extTableNames = array_filter(array_keys($GLOBALS['TCA']), static function ($var) {
            return strpos($var, 'tx_campuseventsconnector_domain_model_') === 0;
        });
        $dateTimeFields = ['tstamp', 'crdate', 'starttime', 'endtime', 'start_tstamp', 'end_tstamp',];
        foreach ($extTableNames as $tableName) {
            // remove cruser_id
            unset($GLOBALS['TCA'][$tableName]['ctrl']['cruser_id']);
            // set datetime for various date fields
            foreach ($dateTimeFields as $dateField) {
                if (isset($GLOBALS['TCA'][$tableName]['columns'][$dateField])) {
                    $GLOBALS['TCA'][$tableName]['columns'][$dateField]['config'] = [
                        'type' => 'datetime',
                    ];
                }
            }
        }
        $tableName = 'tx_campuseventsconnector_domain_model_eventticketpricevariant';
        $GLOBALS['TCA'][$tableName]['columns']['bookable_from']['config'] = [
            'type' => 'datetime',
            'dbType' => 'datetime',
            'nullable' => true,
        ];
        $GLOBALS['TCA'][$tableName]['columns']['bookable_till']['config'] = [
            'type' => 'datetime',
            'dbType' => 'datetime',
            'nullable' => true,
        ];
        $tableName = 'tx_campuseventsconnector_domain_model_convertconfiguration';
        unset($GLOBALS['TCA'][$tableName]['columns']['target_pid']['config']['internal_type']);
        $GLOBALS['TCA'][$tableName]['columns']['template_path']['config']['type'] = 'folder';
        $tableName = 'tx_campuseventsconnector_domain_model_event';
        $numberFields = ['status', 'min_participants', 'max_participants', 'participants', 'order_type'];
        foreach ($numberFields as $numberField) {
            $GLOBALS['TCA'][$tableName]['columns'][$numberField]['config']['type'] = 'number';
            unset($GLOBALS['TCA'][$tableName]['columns'][$numberField]['config']['eval']);
        }
    }
});
