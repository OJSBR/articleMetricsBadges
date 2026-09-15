<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesBlockPlugin.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesBlockPlugin
 *
 * @brief Sidebar block for the Article Metrics Badges plugin, registered by the
 *        generic plugin (the way the core webFeed plugin registers its block).
 */

namespace APP\plugins\generic\articleMetricsBadges;

use APP\core\Application;
use PKP\plugins\BlockPlugin;

class ArticleMetricsBadgesBlockPlugin extends BlockPlugin
{
    public function __construct(protected ArticleMetricsBadgesPlugin $parentPlugin)
    {
        parent::__construct();
    }

    /**
     * @copydoc Plugin::getName()
     */
    public function getName(): string
    {
        return substr(static::class, strlen(__NAMESPACE__) + 1);
    }

    /**
     * The block is installed and upgraded together with the generic plugin.
     */
    public function getCurrentVersion()
    {
        return false;
    }

    /**
     * The block follows the generic plugin: whenever the plugin is enabled the block is
     * offered in Appearance > Sidebar. Whether it renders anything is decided by the
     * showBlock setting, in getContents().
     *
     * @copydoc BlockPlugin::getEnabled()
     */
    public function getEnabled($contextId = null)
    {
        return $this->parentPlugin->getEnabled($contextId);
    }

    /**
     * @copydoc Plugin::getHideManagement()
     */
    public function getHideManagement(): bool
    {
        return true;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.articleMetricsBadges.block.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.articleMetricsBadges.block.description');
    }

    /**
     * @copydoc Plugin::getPluginPath()
     */
    public function getPluginPath(): string
    {
        return $this->parentPlugin->getPluginPath();
    }

    /**
     * @copydoc Plugin::getTemplatePath()
     */
    public function getTemplatePath($inCore = false): string
    {
        return $this->parentPlugin->getTemplatePath($inCore);
    }

    /**
     * @copydoc BlockPlugin::getContents()
     *
     * @param null|mixed $request
     */
    public function getContents($templateMgr, $request = null)
    {
        $request ??= Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !$this->parentPlugin->getSetting($context->getId(), 'showBlock')) {
            return '';
        }

        $contextId = (int) $context->getId();
        $doi = $this->parentPlugin->getArticleDoi($templateMgr, $contextId);
        if (!$doi) {
            return '';
        }

        $templateMgr->assign([
            'metricsBadgesBlockTitle' => $this->parentPlugin->getSetting($contextId, 'blockTitle') ?: __('plugins.generic.articleMetricsBadges.block.defaultTitle'),
            'metricsBadgesInBlock' => true,
        ]);
        $this->parentPlugin->assignBadgeVariables($templateMgr, $contextId, $doi);

        return $templateMgr->fetch($this->parentPlugin->getTemplateResource('block.tpl'));
    }
}
