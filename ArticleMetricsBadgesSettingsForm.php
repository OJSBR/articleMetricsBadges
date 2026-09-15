<?php

/**
 * @file plugins/generic/articleMetricsBadges/ArticleMetricsBadgesSettingsForm.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesSettingsForm
 *
 * @brief Settings form for the Article Metrics Badges plugin.
 */

namespace APP\plugins\generic\articleMetricsBadges;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidator;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorInSet;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorRegExp;

class ArticleMetricsBadgesSettingsForm extends Form
{
    /** Settings saved as booleans */
    public const BOOL_SETTINGS = [
        'plumxEnabled', 'dimensionsEnabled', 'altmetricEnabled',
        'showInline', 'showBlock',
        'plumxHideWhenEmpty', 'plumxHidePrint', 'plumxBorder',
        'dimensionsHideZero',
    ];

    /** Settings saved as strings */
    public const STRING_SETTINGS = [
        'inlineHook', 'blockTitle',
        'plumxWidgetType', 'plumxOrientation', 'plumxWidth',
        'dimensionsStyle',
        'altmetricBadgeType', 'altmetricPopover',
    ];

    /** The choices of each select, as value => locale key. */
    public const OPTIONS = [
        'inlineHook' => [
            'main' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.main',
            'details' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.details',
            'footer' => 'plugins.generic.articleMetricsBadges.settings.inlineHook.footer',
        ],
        'plumxWidgetType' => [
            'plumx-summary' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.summary',
            'plumx-details' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.details',
            'plumx-plum-print-popup' => 'plugins.generic.articleMetricsBadges.settings.plumx.widgetType.popup',
        ],
        'plumxOrientation' => [
            'horizontal' => 'plugins.generic.articleMetricsBadges.settings.plumx.orientation.horizontal',
            'vertical' => 'plugins.generic.articleMetricsBadges.settings.plumx.orientation.vertical',
        ],
        'dimensionsStyle' => [
            'small_circle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.smallCircle',
            'small_rectangle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.smallRectangle',
            'large_rectangle' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.largeRectangle',
            'bar' => 'plugins.generic.articleMetricsBadges.settings.dimensions.style.bar',
        ],
        'altmetricBadgeType' => [
            'donut' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.donut',
            'medium-donut' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.mediumDonut',
            'bar' => 'plugins.generic.articleMetricsBadges.settings.altmetric.badgeType.bar',
        ],
        'altmetricPopover' => [
            'right' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.right',
            'left' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.left',
            'top' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.top',
            'bottom' => 'plugins.generic.articleMetricsBadges.settings.altmetric.popover.bottom',
        ],
    ];

    public function __construct(public ArticleMetricsBadgesPlugin $plugin, public ?int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
        foreach (self::OPTIONS as $field => $options) {
            // Only the choices the form offers reach the page markup.
            $this->addCheck(new FormValidatorInSet($this, $field, FormValidator::FORM_VALIDATOR_OPTIONAL_VALUE, 'validator.regex', array_keys($options)));
        }
        $this->addCheck(new FormValidatorRegExp($this, 'plumxWidth', FormValidator::FORM_VALIDATOR_OPTIONAL_VALUE, 'validator.regex', '/^\d{1,4}(px|%)?$/'));
    }

    /**
     * The cross-field rules (at least one provider, at least one position) are
     * checked here because a FormValidator is bound to a single field and both
     * rules are satisfied by any one of several checkboxes.
     *
     * @copydoc Form::validate()
     */
    public function validate($callHooks = true)
    {
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
    public function initData()
    {
        foreach (array_merge(self::BOOL_SETTINGS, self::STRING_SETTINGS) as $name) {
            $this->setData($name, $this->plugin->getSetting($this->contextId, $name));
        }

        // Defaults on first open. No provider is enabled here on purpose: each one
        // has its own terms of use, so turning it on must be a deliberate act by
        // someone who knows the journal is licensed for it.
        if (is_null($this->plugin->getSetting($this->contextId, 'showInline'))) {
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
    public function readInputData()
    {
        $this->readUserVars(array_merge(self::BOOL_SETTINGS, self::STRING_SETTINGS));
    }

    /**
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'settingsScriptUrl' => $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/settingsForm.js',
            'inlineHookOptions' => self::OPTIONS['inlineHook'],
            'plumxWidgetTypeOptions' => self::OPTIONS['plumxWidgetType'],
            'plumxOrientationOptions' => self::OPTIONS['plumxOrientation'],
            'dimensionsStyleOptions' => self::OPTIONS['dimensionsStyle'],
            'altmetricBadgeTypeOptions' => self::OPTIONS['altmetricBadgeType'],
            'altmetricPopoverOptions' => self::OPTIONS['altmetricPopover'],
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        foreach (self::BOOL_SETTINGS as $name) {
            $this->plugin->updateSetting($this->contextId, $name, (bool) $this->getData($name), 'bool');
        }
        foreach (self::STRING_SETTINGS as $name) {
            $this->plugin->updateSetting($this->contextId, $name, (string) $this->getData($name), 'string');
        }

        return parent::execute(...$functionArgs);
    }
}
