<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesPlugin.inc.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesPlugin
 * @ingroup plugins_generic_articleMetricsBadges
 *
 * @brief Displays PlumX, Dimensions and Altmetric badges on the article page.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ArticleMetricsBadgesPlugin extends GenericPlugin {

	/**
	 * The providers supported by this plugin. The key is used both as the
	 * settings prefix (e.g. plumxEnabled) and as the template variable prefix.
	 */
	const PROVIDERS = array('plumx', 'dimensions', 'altmetric');

	/**
	 * Template hooks available for the inline badges, keyed by the value
	 * stored in the inlineHook setting.
	 */
	const INLINE_HOOKS = array(
		'main' => 'Templates::Article::Main',
		'details' => 'Templates::Article::Details',
		'footer' => 'Templates::Article::Footer::PageFooter',
	);

	/** The script of each provider, loaded by the reader's browser from the provider. */
	const PROVIDER_SCRIPTS = array(
		'plumx' => 'https://cdn.plu.mx/widget-all.js',
		'dimensions' => 'https://badge.dimensions.ai/badge.js',
		'altmetric' => 'https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js',
	);

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;

		if ($success && $this->getEnabled($mainContextId)) {
			// The provider scripts are loaded from here so that the plugin does not
			// depend on the active theme calling the page footer hook.
			HookRegistry::register('TemplateManager::display', array($this, 'loadProviderScripts'));

			foreach (self::INLINE_HOOKS as $hookName) {
				HookRegistry::register($hookName, array($this, 'insertInlineBadges'));
			}

			$this->import('ArticleMetricsBadgesBlockPlugin');
			PluginRegistry::register(
				'blocks',
				new ArticleMetricsBadgesBlockPlugin($this),
				$this->getPluginPath()
			);
		}

		return $success;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.articleMetricsBadges.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.articleMetricsBadges.description');
	}

	/**
	 * Get the list of providers enabled for a context.
	 * @param $contextId int
	 * @return array Provider keys
	 */
	function getEnabledProviders($contextId) {
		$enabled = array();
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
	 *
	 * @param $templateMgr TemplateManager
	 * @param $contextId int
	 * @return string|null
	 */
	function getArticleDoi($templateMgr, $contextId) {
		if (!$this->isArticlePage() || !$this->getEnabledProviders($contextId)) return null;

		// The version of the article being displayed, which may be an older one.
		$publication = $templateMgr->getTemplateVars('publication');
		if (!$publication) {
			$submission = $templateMgr->getTemplateVars('article');
			$publication = $submission ? $submission->getCurrentPublication() : null;
		}
		if (!$publication) return null;

		$doi = $publication->getStoredPubId('doi');
		return $doi ? $doi : null;
	}

	/**
	 * Whether the request is for the article page. getRequestedPage() only exists on the
	 * page router: every backend AJAX request runs through the component router.
	 * @return boolean
	 */
	function isArticlePage() {
		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		return $router instanceof PKPPageRouter && $router->getRequestedPage($request) === 'article';
	}

	/**
	 * Enqueue the JavaScript of each enabled provider on the article page.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function loadProviderScripts($hookName, $args) {
		$templateMgr = $args[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context) return false;

		if (!$this->getArticleDoi($templateMgr, $context->getId())) return false;

		foreach ($this->getEnabledProviders($context->getId()) as $provider) {
			$templateMgr->addJavaScript(
				'articleMetricsBadges-' . $provider,
				self::PROVIDER_SCRIPTS[$provider],
				array('contexts' => 'frontend')
			);
		}

		$templateMgr->addStyleSheet(
			'articleMetricsBadges',
			$request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/badges.css',
			array('contexts' => 'frontend')
		);

		return false;
	}

	/**
	 * Output the badges in the position chosen by the journal manager.
	 * @param $hookName string
	 * @param $params array
	 * @return boolean
	 */
	function insertInlineBadges($hookName, $params) {
		$output =& $params[2];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		if (!$context) return false;

		$contextId = $context->getId();
		if (!$this->getSetting($contextId, 'showInline')) return false;

		$chosenHook = $this->getSetting($contextId, 'inlineHook');
		if (!isset(self::INLINE_HOOKS[$chosenHook]) || self::INLINE_HOOKS[$chosenHook] !== $hookName) return false;

		$templateMgr = TemplateManager::getManager($request);
		$doi = $this->getArticleDoi($templateMgr, $contextId);
		if (!$doi) return false;

		$this->assignBadgeVariables($templateMgr, $contextId, $doi);
		$templateMgr->assign('metricsBadgesInBlock', false);
		$output .= $templateMgr->fetch($this->getTemplateResource('badges.tpl'));

		return false;
	}

	/**
	 * Assign every template variable needed to render the badges.
	 * @param $templateMgr TemplateManager
	 * @param $contextId int
	 * @param $doi string
	 */
	function assignBadgeVariables($templateMgr, $contextId, $doi) {
		$templateMgr->assign(array(
			'metricsBadgesDoi' => $doi,
			'metricsBadgesProviders' => $this->getEnabledProviders($contextId),
			'metricsBadgesTemplatePath' => $this->getTemplateResource('badges.tpl'),
			// PlumX
			'plumxWidgetType' => $this->getSetting($contextId, 'plumxWidgetType') ? $this->getSetting($contextId, 'plumxWidgetType') : 'plumx-summary',
			'plumxOrientation' => $this->getSetting($contextId, 'plumxOrientation'),
			'plumxHideWhenEmpty' => $this->getSetting($contextId, 'plumxHideWhenEmpty'),
			'plumxHidePrint' => $this->getSetting($contextId, 'plumxHidePrint'),
			'plumxBorder' => $this->getSetting($contextId, 'plumxBorder'),
			'plumxWidth' => $this->getSetting($contextId, 'plumxWidth'),
			// Dimensions
			'dimensionsStyle' => $this->getSetting($contextId, 'dimensionsStyle') ? $this->getSetting($contextId, 'dimensionsStyle') : 'small_circle',
			'dimensionsHideZero' => $this->getSetting($contextId, 'dimensionsHideZero'),
			// Altmetric
			'altmetricBadgeType' => $this->getSetting($contextId, 'altmetricBadgeType') ? $this->getSetting($contextId, 'altmetricBadgeType') : 'donut',
			'altmetricPopover' => $this->getSetting($contextId, 'altmetricPopover') ? $this->getSetting($contextId, 'altmetricPopover') : 'right',
		));
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$actions = parent::getActions($request, $actionArgs);
		if (!$this->getEnabled()) return $actions;

		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		array_unshift($actions, new LinkAction(
			'settings',
			new AjaxModal(
				$router->url($request, null, null, 'manage', null, array(
					'verb' => 'settings',
					'plugin' => $this->getName(),
					'category' => 'generic',
				)),
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
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();
				$this->import('ArticleMetricsBadgesSettingsForm');
				$form = new ArticleMetricsBadgesSettingsForm($this, $context->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						import('classes.notification.NotificationManager');
						$notificationManager = new NotificationManager();
						$notificationManager->createTrivialNotification(
							$request->getUser()->getId(),
							NOTIFICATION_TYPE_SUCCESS,
							array('contents' => __('plugins.generic.articleMetricsBadges.settings.saved'))
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
