<?php

/**
 * @file plugins/generic/articleMetricsBadges/tests/bootstrap.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Bootstrap for the test suite.
 *
 * OJS 3.3 has no autoloader for plugin classes: the plugin classes and PKP's
 * test base class are loaded here, so they are compiled against the real PKP
 * classes of the installation.
 */

if (!class_exists('PKPApplication')) {
    $root = dirname(__DIR__, 4);
    if (!is_file($root . '/lib/pkp/includes/bootstrap.inc.php')) {
        fwrite(STDERR, "The plugin must be installed in plugins/generic/articleMetricsBadges of an OJS 3.3 installation to run the suite.\n");
        exit(2);
    }
    chdir($root);
    define('INDEX_FILE_LOCATION', $root . '/index.php');
    require_once $root . '/lib/pkp/includes/bootstrap.inc.php';
}

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.plugins.BlockPlugin');
import('lib.pkp.classes.form.Form');
require_once dirname(__DIR__) . '/ArticleMetricsBadgesBlockPlugin.inc.php';
require_once dirname(__DIR__) . '/ArticleMetricsBadgesPlugin.inc.php';
require_once dirname(__DIR__) . '/ArticleMetricsBadgesSettingsForm.inc.php';
require_once dirname(__DIR__, 4) . '/lib/pkp/tests/PKPTestCase.inc.php';
require_once __DIR__ . '/PoFile.php';
