<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesBlockPlugin.inc.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesBlockPlugin
 * @ingroup plugins_generic_articleMetricsBadges
 *
 * @brief Sidebar block for the Article Metrics Badges plugin.
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class ArticleMetricsBadgesBlockPlugin extends BlockPlugin {

	/** @var ArticleMetricsBadgesPlugin The generic plugin that registers this block */
	protected $_parentPlugin;

	/**
	 * Constructor
	 * @param $parentPlugin ArticleMetricsBadgesPlugin
	 */
	function __construct($parentPlugin) {
		parent::__construct();
		$this->_parentPlugin = $parentPlugin;
	}

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'ArticleMetricsBadgesBlockPlugin';
	}

	/**
	 * This block is installed and upgraded together with the generic plugin.
	 * @return boolean
	 */
	function getCurrentVersion() {
		return false;
	}

	/**
	 * The block follows the generic plugin: whenever the plugin is enabled the
	 * block is offered in Appearance > Sidebar. Whether it actually renders
	 * anything is decided by the showBlock setting, in getContents().
	 * @copydoc LazyLoadPlugin::getEnabled()
	 */
	function getEnabled($contextId = null) {
		return $this->_parentPlugin->getEnabled($contextId);
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.articleMetricsBadges.block.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.articleMetricsBadges.block.description');
	}

	/**
	 * The block is managed from the generic plugin's settings.
	 * @return boolean
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * @copydoc Plugin::getPluginPath()
	 */
	function getPluginPath() {
		return $this->_parentPlugin->getPluginPath();
	}

	/**
	 * @copydoc Plugin::getTemplatePath()
	 */
	function getTemplatePath($inCore = false) {
		return $this->_parentPlugin->getTemplatePath($inCore);
	}

	/**
	 * @copydoc BlockPlugin::getContents()
	 */
	function getContents($templateMgr, $request = null) {
		if (is_null($request)) $request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context || !$this->_parentPlugin->getSetting($context->getId(), 'showBlock')) return '';

		$contextId = $context->getId();
		$doi = $this->_parentPlugin->getArticleDoi($templateMgr, $contextId);
		if (!$doi) return '';

		$blockTitle = $this->_parentPlugin->getSetting($contextId, 'blockTitle');
		$templateMgr->assign(array(
			'metricsBadgesBlockTitle' => $blockTitle ? $blockTitle : __('plugins.generic.articleMetricsBadges.block.defaultTitle'),
			'metricsBadgesInBlock' => true,
		));
		$this->_parentPlugin->assignBadgeVariables($templateMgr, $contextId, $doi);

		return $templateMgr->fetch($this->_parentPlugin->getTemplateResource('block.tpl'));
	}
}
