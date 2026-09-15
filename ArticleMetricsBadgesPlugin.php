<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesPlugin.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesPlugin
 *
 * @brief Displays PlumX, Dimensions and Altmetric badges on the article page.
 */

namespace APP\plugins\generic\articleMetricsBadges;

use APP\core\Application;
use APP\template\TemplateManager;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use PKP\core\JSONMessage;
use PKP\core\PKPPageRouter;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class ArticleMetricsBadgesPlugin extends GenericPlugin
{
    /**
     * The providers supported by this plugin. The key is used both as the
     * settings prefix (e.g. plumxEnabled) and as the template variable prefix.
     */
    public const PROVIDERS = ['plumx', 'dimensions', 'altmetric'];

    /**
     * Template hooks available for the inline badges, keyed by the value
     * stored in the inlineHook setting.
     */
    public const INLINE_HOOKS = [
        'main' => 'Templates::Article::Main',
        'details' => 'Templates::Article::Details',
        'footer' => 'Templates::Article::Footer::PageFooter',
    ];

    /** The script of each provider, loaded by the reader's browser from the provider. */
    public const PROVIDER_SCRIPTS = [
        'plumx' => 'https://cdn.plu.mx/widget-all.js',
        'dimensions' => 'https://badge.dimensions.ai/badge.js',
        'altmetric' => 'https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js',
    ];

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && !Application::isUnderMaintenance() && $this->getEnabled($mainContextId)) {
            // The provider scripts are loaded from here so that the plugin does not
            // depend on the active theme calling the page footer hook.
            Hook::add('TemplateManager::display', [$this, 'loadProviderScripts']);

            foreach (self::INLINE_HOOKS as $hookName) {
                Hook::add($hookName, [$this, 'insertInlineBadges']);
            }

            PluginRegistry::register('blocks', new ArticleMetricsBadgesBlockPlugin($this), $this->getPluginPath());
        }

        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.articleMetricsBadges.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.articleMetricsBadges.description');
    }

    /**
     * Get the list of providers enabled for a context.
     */
    public function getEnabledProviders(?int $contextId): array
    {
        $enabled = [];
        foreach (self::PROVIDERS as $provider) {
            if ($this->getSetting($contextId, $provider . 'Enabled')) {
                $enabled[] = $provider;
            }
        }
        return $enabled;
    }

    /**
     * Get the DOI of the article being displayed, if the badges should be shown.
     *
     * Returns null whenever the badges must not be rendered: outside the article
     * page, without a submission in the template context, without a DOI or
     * without any provider enabled.
     */
    public function getArticleDoi($templateMgr, ?int $contextId): ?string
    {
        if (!$this->isArticlePage() || !$this->getEnabledProviders($contextId)) {
            return null;
        }

        // The version of the article being displayed, which may be an older one.
        $publication = $templateMgr->getTemplateVars('publication') ?: $templateMgr->getTemplateVars('article')?->getCurrentPublication();
        if (!$publication) {
            return null;
        }

        $doi = $publication->getDoi();
        return $doi ?: null;
    }

    /**
     * Whether the request is for the article page. getRequestedPage() only exists on the
     * page router: every backend AJAX request runs through the component router.
     */
    protected function isArticlePage(): bool
    {
        $request = Application::get()->getRequest();
        $router = $request->getRouter();
        return $router instanceof PKPPageRouter && $router->getRequestedPage($request) === 'article';
    }

    /**
     * Enqueue the JavaScript of each enabled provider on the article page.
     *
     * @param array $args [TemplateManager, string $template, string $output]
     */
    public function loadProviderScripts(string $hookName, array $args): bool
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !$this->getArticleDoi($templateMgr, $context->getId())) {
            return Hook::CONTINUE;
        }

        foreach ($this->getEnabledProviders($context->getId()) as $provider) {
            $templateMgr->addJavaScript('articleMetricsBadges-' . $provider, self::PROVIDER_SCRIPTS[$provider], ['contexts' => 'frontend']);
        }

        $templateMgr->addStyleSheet(
            'articleMetricsBadges',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/badges.css',
            ['contexts' => 'frontend']
        );

        return Hook::CONTINUE;
    }

    /**
     * Output the badges in the position chosen by the journal manager.
     *
     * @param array $params [array $smartyParams, Smarty $smarty, string $output]
     */
    public function insertInlineBadges(string $hookName, array $params): bool
    {
        $output = &$params[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }

        $contextId = $context->getId();
        if (!$this->getSetting($contextId, 'showInline')) {
            return Hook::CONTINUE;
        }

        $chosenHook = $this->getSetting($contextId, 'inlineHook');
        if ((self::INLINE_HOOKS[$chosenHook] ?? null) !== $hookName) {
            return Hook::CONTINUE;
        }

        $templateMgr = TemplateManager::getManager($request);
        $doi = $this->getArticleDoi($templateMgr, $contextId);
        if (!$doi) {
            return Hook::CONTINUE;
        }

        $this->assignBadgeVariables($templateMgr, $contextId, $doi);
        $templateMgr->assign('metricsBadgesInBlock', false);
        $output .= $templateMgr->fetch($this->getTemplateResource('badges.tpl'));

        return Hook::CONTINUE;
    }

    /**
     * Assign every template variable needed to render the badges.
     */
    public function assignBadgeVariables($templateMgr, ?int $contextId, string $doi): void
    {
        $templateMgr->assign([
            'metricsBadgesDoi' => $doi,
            'metricsBadgesProviders' => $this->getEnabledProviders($contextId),
            'metricsBadgesTemplatePath' => $this->getTemplateResource('badges.tpl'),
            // PlumX
            'plumxWidgetType' => $this->getSetting($contextId, 'plumxWidgetType') ?: 'plumx-summary',
            'plumxOrientation' => $this->getSetting($contextId, 'plumxOrientation'),
            'plumxHideWhenEmpty' => $this->getSetting($contextId, 'plumxHideWhenEmpty'),
            'plumxHidePrint' => $this->getSetting($contextId, 'plumxHidePrint'),
            'plumxBorder' => $this->getSetting($contextId, 'plumxBorder'),
            'plumxWidth' => $this->getSetting($contextId, 'plumxWidth'),
            // Dimensions
            'dimensionsStyle' => $this->getSetting($contextId, 'dimensionsStyle') ?: 'small_circle',
            'dimensionsHideZero' => $this->getSetting($contextId, 'dimensionsHideZero'),
            // Altmetric
            'altmetricBadgeType' => $this->getSetting($contextId, 'altmetricBadgeType') ?: 'donut',
            'altmetricPopover' => $this->getSetting($contextId, 'altmetricPopover') ?: 'right',
        ]);
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $router = $request->getRouter();
        array_unshift($actions, new LinkAction(
            'settings',
            new AjaxModal(
                $router->url($request, null, null, 'manage', null, [
                    'verb' => 'settings',
                    'plugin' => $this->getName(),
                    'category' => 'generic',
                ]),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        ));

        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $form = new ArticleMetricsBadgesSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        (new NotificationManager())->createTrivialNotification(
                            $request->getUser()->getId(),
                            Notification::NOTIFICATION_TYPE_SUCCESS,
                            ['contents' => __('plugins.generic.articleMetricsBadges.settings.saved')]
                        );
                        return new JSONMessage(true);
                    }
                    return new JSONMessage(true, $form->fetch($request));
                }

                $form->initData();
                return new JSONMessage(true, $form->fetch($request));
        }

        return parent::manage($args, $request);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\articleMetricsBadges\ArticleMetricsBadgesPlugin', '\ArticleMetricsBadgesPlugin');
}
