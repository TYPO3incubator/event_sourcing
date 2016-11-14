<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\EventSourcing\Common::overrideConfiguration();
\TYPO3\CMS\EventSourcing\Common::registerEventSources();
