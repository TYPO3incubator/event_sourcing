<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\EventSourcing\Common::overrideConfiguration();
\TYPO3\CMS\EventSourcing\Common::registerEventSources();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['eventSourcing'] = [
    'listenAllEvents' => false,
    'recordAllEvents' => false,
    'projectAllEvents' => false,
];
