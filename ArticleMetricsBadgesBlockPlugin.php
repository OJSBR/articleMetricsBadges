<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesBlockPlugin.php
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ArticleMetricsBadgesBlockPlugin
 *
 * @brief Sidebar block for the Article Metrics Badges plugin.
 */

namespace APP\plugins\generic\articleMetricsBadges;

use APP\core\Application;
use PKP\config\Config;
use PKP\plugins\BlockPlugin;
use PKP\plugins\PluginRegistry;

class ArticleMetricsBadgesBlockPlugin extends BlockPlugin
{
    /**
     * Name of the generic plugin that registers this block.
     *
     * Untyped on purpose: $pluginPath is already declared without a type in
     * PKP\plugins\Plugin, and redeclaring it with a type is a fatal error.
     */
    public $parentPluginName;

    public function __construct(string $parentPluginName, string $pluginPath)
    {
        parent::__construct();
        $this->parentPluginName = $parentPluginName;
        $this->pluginPath = $pluginPath;
    }

    /**
     * This block is installed and upgraded together with the generic plugin.
     */
    public function getCurrentVersion()
    {
        return false;
    }

    /**
     * The block follows the generic plugin: whenever the plugin is enabled the
     * block is offered in Appearance > Sidebar. Whether it actually renders
     * anything is decided by the showBlock setting, in getContents().
     *
     * @copydoc LazyLoadPlugin::getEnabled()
     */
    public function getEnabled($contextId = null)
    {
        if (!Config::getVar('general', 'installed')) {
            return true;
        }
        $plugin = $this->getParentPlugin();
        return $plugin ? $plugin->getEnabled($contextId) : false;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.articleMetricsBadges.block.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.articleMetricsBadges.block.description');
    }

    /**
     * The block is managed from the generic plugin's settings.
     */
    public function getHideManagement()
    {
        return true;
    }

    /**
     * Get the generic plugin that owns the settings.
     */
    public function getParentPlugin(): ?ArticleMetricsBadgesPlugin
    {
        $plugin = PluginRegistry::getPlugin('generic', $this->parentPluginName);
        return $plugin instanceof ArticleMetricsBadgesPlugin ? $plugin : null;
    }

    /**
     * @copydoc Plugin::getPluginPath()
     */
    public function getPluginPath()
    {
        return $this->pluginPath;
    }

    /**
     * @copydoc BlockPlugin::getBlockTemplateFilename()
     */
    public function getBlockTemplateFilename(): string
    {
        return 'block.tpl';
    }

    /**
     * @copydoc BlockPlugin::getContents()
     */
    public function getContents($templateMgr, $request = null)
    {
        $plugin = $this->getParentPlugin();
        if (!$plugin) {
            return '';
        }

        $request ??= Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return '';
        }

        $contextId = $context->getId();
        if (!$plugin->getSetting($contextId, 'showBlock')) {
            return '';
        }

        $doi = $plugin->getArticleDoi($templateMgr, $contextId);
        if (!$doi) {
            return '';
        }

        $blockTitle = $plugin->getSetting($contextId, 'blockTitle');
        $templateMgr->assign([
            'metricsBadgesBlockTitle' => $blockTitle ?: __('plugins.generic.articleMetricsBadges.block.defaultTitle'),
            'metricsBadgesInBlock' => true,
        ]);
        $plugin->assignBadgeVariables($templateMgr, $contextId, $doi);

        return $templateMgr->fetch($this->getTemplateResource($this->getBlockTemplateFilename()));
    }
}
