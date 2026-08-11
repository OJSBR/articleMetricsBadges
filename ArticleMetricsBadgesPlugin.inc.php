<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesPlugin.inc.php
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ArticleMetricsBadgesPlugin
 * @ingroup plugins_generic_articleMetricsBadges
 *
 * @brief Displays PlumX, Dimensions and Altmetric badges on the article page.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ArticleMetricsBadgesPlugin extends GenericPlugin {

	/** Appended to the stylesheet URL so that a released change reaches browsers that cached the old file. */
	const STYLE_VERSION = '1.1.0';

	/**
	 * The providers supported by this plugin. The key is used both as the
	 * settings prefix (e.g. plumxEnabled) and as the template variable prefix.
	 * @var array
	 */
	public static $providers = array('plumx', 'dimensions', 'altmetric');

	/**
	 * Template hooks available for the inline badges, keyed by the value
	 * stored in the inlineHook setting.
	 * @var array
	 */
	public static $inlineHooks = array(
		'main' => 'Templates::Article::Main',
		'details' => 'Templates::Article::Details',
		'footer' => 'Templates::Article::Footer::PageFooter',
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

			foreach (self::$inlineHooks as $hookName) {
				HookRegistry::register($hookName, array($this, 'insertInlineBadges'));
			}

			$this->import('ArticleMetricsBadgesBlockPlugin');
			PluginRegistry::register(
				'blocks',
				new ArticleMetricsBadgesBlockPlugin($this->getName(), $this->getPluginPath()),
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
		foreach (self::$providers as $provider) {
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
		$request = Application::get()->getRequest();
		$router = $request->getRouter();
		// getRequestedPage() only exists on the page router: every backend AJAX
		// request runs through the component router and must be ignored here.
		if (!($router instanceof PKPPageRouter) || $router->getRequestedPage($request) != 'article') return null;
		if (!$this->getEnabledProviders($contextId)) return null;

		$submission = $templateMgr->getTemplateVars('article');
		if (!$submission) return null;

		$doi = $submission->getStoredPubId('doi');
		return $doi ? $doi : null;
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

		$scripts = array(
			'plumx' => 'https://cdn.plu.mx/widget-all.js',
			'dimensions' => 'https://badge.dimensions.ai/badge.js',
			'altmetric' => 'https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js',
		);

		foreach ($this->getEnabledProviders($context->getId()) as $provider) {
			$templateMgr->addJavaScript(
				'articleMetricsBadges-' . $provider,
				$scripts[$provider],
				array('contexts' => 'frontend')
			);
		}

		$templateMgr->addStyleSheet(
			'articleMetricsBadges',
			$request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/badges.css?v=' . self::STYLE_VERSION,
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
		if (!isset(self::$inlineHooks[$chosenHook]) || self::$inlineHooks[$chosenHook] != $hookName) return false;

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
