<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesBlockPlugin.inc.php
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ArticleMetricsBadgesBlockPlugin
 * @ingroup plugins_generic_articleMetricsBadges
 *
 * @brief Sidebar block for the Article Metrics Badges plugin.
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class ArticleMetricsBadgesBlockPlugin extends BlockPlugin {

	/** @var string Name of the generic plugin that registers this block */
	var $parentPluginName;

	/** @var string Path of the generic plugin that registers this block */
	var $pluginPath;

	/**
	 * Constructor
	 * @param $parentPluginName string
	 * @param $pluginPath string
	 */
	function __construct($parentPluginName, $pluginPath) {
		parent::__construct();
		$this->parentPluginName = $parentPluginName;
		$this->pluginPath = $pluginPath;
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
		if (!Config::getVar('general', 'installed')) return true;
		$plugin = $this->getParentPlugin();
		return $plugin ? $plugin->getEnabled($contextId) : false;
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
	 * Get the generic plugin that owns the settings.
	 * @return ArticleMetricsBadgesPlugin|null
	 */
	function getParentPlugin() {
		return PluginRegistry::getPlugin('generic', $this->parentPluginName);
	}

	/**
	 * @copydoc Plugin::getPluginPath()
	 */
	function getPluginPath() {
		return $this->pluginPath;
	}

	/**
	 * @copydoc BlockPlugin::getBlockTemplateFilename()
	 */
	function getBlockTemplateFilename() {
		return 'block.tpl';
	}

	/**
	 * @copydoc BlockPlugin::getContents()
	 */
	function getContents($templateMgr, $request = null) {
		$plugin = $this->getParentPlugin();
		if (!$plugin) return '';

		if (is_null($request)) $request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context) return '';

		$contextId = $context->getId();
		if (!$plugin->getSetting($contextId, 'showBlock')) return '';

		$doi = $plugin->getArticleDoi($templateMgr, $contextId);
		if (!$doi) return '';

		$blockTitle = $plugin->getSetting($contextId, 'blockTitle');
		$templateMgr->assign(array(
			'metricsBadgesBlockTitle' => $blockTitle ? $blockTitle : __('plugins.generic.articleMetricsBadges.block.defaultTitle'),
			'metricsBadgesInBlock' => true,
		));
		$plugin->assignBadgeVariables($templateMgr, $contextId, $doi);

		return $templateMgr->fetch($this->getTemplateResource($this->getBlockTemplateFilename()));
	}
}
