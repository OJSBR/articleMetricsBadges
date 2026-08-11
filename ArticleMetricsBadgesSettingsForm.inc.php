<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesSettingsForm.inc.php
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ArticleMetricsBadgesSettingsForm
 * @ingroup plugins_generic_articleMetricsBadges
 *
 * @brief Settings form for the Article Metrics Badges plugin.
 */

import('lib.pkp.classes.form.Form');

class ArticleMetricsBadgesSettingsForm extends Form {

	/** @var int */
	var $_contextId;

	/** @var ArticleMetricsBadgesPlugin */
	var $_plugin;

	/** @var array Settings saved as booleans */
	static $boolSettings = array(
		'plumxEnabled', 'dimensionsEnabled', 'altmetricEnabled',
		'showInline', 'showBlock',
		'plumxHideWhenEmpty', 'plumxHidePrint', 'plumxBorder',
		'dimensionsHideZero',
	);

	/** @var array Settings saved as strings */
	static $stringSettings = array(
		'inlineHook', 'blockTitle',
		'plumxWidgetType', 'plumxOrientation', 'plumxWidth',
		'dimensionsStyle',
		'altmetricBadgeType', 'altmetricPopover',
	);

	/**
	 * Constructor
	 * @param $plugin ArticleMetricsBadgesPlugin
	 * @param $contextId int
	 */
	function __construct($plugin, $contextId) {
		$this->_plugin = $plugin;
		$this->_contextId = $contextId;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * The cross-field rules (at least one provider, at least one position) are
	 * checked here because a FormValidator is bound to a single field and both
	 * rules are satisfied by any one of several checkboxes.
	 * @copydoc Form::validate()
	 */
	function validate($callHooks = true) {
		$valid = parent::validate($callHooks);

		if (!$this->getData('plumxEnabled') && !$this->getData('dimensionsEnabled') && !$this->getData('altmetricEnabled')) {
			$this->addError('plumxEnabled', __('plugins.generic.articleMetricsBadges.settings.providerRequired'));
			$this->addErrorField('plumxEnabled');
			$valid = false;
		}

		if (!$this->getData('showInline') && !$this->getData('showBlock')) {
			$this->addError('showInline', __('plugins.generic.articleMetricsBadges.settings.positionRequired'));
			$this->addErrorField('showInline');
			$valid = false;
		}

		return $valid;
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$plugin = $this->_plugin;
		$contextId = $this->_contextId;

		foreach (array_merge(self::$boolSettings, self::$stringSettings) as $name) {
			$this->setData($name, $plugin->getSetting($contextId, $name));
		}

		// Defaults on first open. No provider is enabled here on purpose: each one
		// has its own terms of use, so turning it on must be a deliberate act by
		// someone who knows the journal is licensed for it.
		if (is_null($plugin->getSetting($contextId, 'showInline'))) {
			$this->setData('showInline', true);
			$this->setData('inlineHook', 'main');
			$this->setData('plumxWidgetType', 'plumx-summary');
			$this->setData('plumxOrientation', 'horizontal');
			$this->setData('plumxHideWhenEmpty', true);
			$this->setData('dimensionsStyle', 'small_circle');
			$this->setData('dimensionsHideZero', true);
			$this->setData('altmetricBadgeType', 'donut');
			$this->setData('altmetricPopover', 'right');
		}

		parent::initData();
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_merge(self::$boolSettings, self::$stringSettings));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'pluginName' => $this->_plugin->getName(),
			'inlineHookOptions' => array(
				'main' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.main',
				'details' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.details',
				'footer' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.footer',
			),
			'plumxWidgetTypeOptions' => array(
				'plumx-summary' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.summary',
				'plumx-details' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.details',
				'plumx-plum-print-popup' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.popup',
			),
			'plumxOrientationOptions' => array(
				'horizontal' => 'plugins.generic.articleMetricsBadges.settings.plumx.orientation.horizontal',
				'vertical' => 'plugins.generic.articleMetricsBadges.settings.plumx.orientation.vertical',
			),
			'dimensionsStyleOptions' => array(
				'small_circle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.smallCircle',
				'small_rectangle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.smallRectangle',
				'large_rectangle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.largeRectangle',
				'bar' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.bar',
			),
			'altmetricBadgeTypeOptions' => array(
				'donut' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.donut',
				'medium-donut' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.mediumDonut',
				'bar' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.bar',
			),
			'altmetricPopoverOptions' => array(
				'right' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.right',
				'left' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.left',
				'top' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.top',
				'bottom' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.bottom',
			),
		));

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$plugin = $this->_plugin;
		$contextId = $this->_contextId;

		foreach (self::$boolSettings as $name) {
			$plugin->updateSetting($contextId, $name, (bool) $this->getData($name), 'bool');
		}
		foreach (self::$stringSettings as $name) {
			$plugin->updateSetting($contextId, $name, (string) $this->getData($name), 'string');
		}

		return parent::execute(...$functionArgs);
	}
}
