<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');


$GLOBALS['TBE_MODULES_EXT']["xMOD_db_new_content_el"]['addElClasses'][\Fab\RssDisplay\Backend\Wizard::class] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rss_display') . 'Classes/Backend/Wizard.php';

call_user_func(function () {
    /**
     * Register icons
     */
    $identifier = 'plugins_tx_rssdisplay_pi1_wizard';

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
    );
    $iconRegistry->registerIcon(
        $identifier, // Icon-Identifier, z.B. tx-myext-action-preview
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:rss_display/Resources/Public/Images/RssDisplay.png']
    );
});
