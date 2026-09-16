<?php

/**
 * @file plugins/generic/articleMetricsBadges/tests/ArticleMetricsBadgesTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesTest
 *
 * @brief Providers, the DOI of the displayed version, the sidebar block and the
 *        settings form.
 */

namespace APP\plugins\generic\articleMetricsBadges\tests;

// OJS 3.3 has no autoloader for plugin classes.
require_once __DIR__ . '/bootstrap.php';

import('classes.publication.Publication');

class ArticleMetricsBadgesTest extends \PKPTestCase
{
    /**
     * A plugin with the given settings, on the article page or not.
     */
    protected function plugin(array $settings, bool $articlePage = true): \ArticleMetricsBadgesPlugin
    {
        return new class ($settings, $articlePage) extends \ArticleMetricsBadgesPlugin {
            private $settings;
            private $articlePage;

            public function __construct(array $settings, bool $articlePage)
            {
                $this->settings = $settings;
                $this->articlePage = $articlePage;
                parent::__construct();
            }

            function getSetting($contextId, $name)
            {
                return $this->settings[$name] ?? null;
            }

            function getEnabled($contextId = null)
            {
                return (bool) ($this->settings['enabled'] ?? false);
            }

            function isArticlePage()
            {
                return $this->articlePage;
            }

            function getTemplateResource($template = null, $inCore = false)
            {
                return (string) $template;
            }
        };
    }

    /**
     * A template manager holding the given template variables.
     */
    protected function templateManager(array $vars)
    {
        return new class ($vars) {
            private $vars;

            public function __construct(array $vars)
            {
                $this->vars = $vars;
            }

            public function getTemplateVars($name)
            {
                return $this->vars[$name] ?? null;
            }
        };
    }

    protected function publication(string $doi): \Publication
    {
        $publication = new \Publication();
        $publication->setData('pub-id::doi', $doi);
        return $publication;
    }

    public function testOnlyEnabledProvidersAreUsed(): void
    {
        $this->assertSame(['plumx', 'altmetric'], $this->plugin(['plumxEnabled' => true, 'dimensionsEnabled' => false, 'altmetricEnabled' => true])->getEnabledProviders(1));
        $this->assertSame([], $this->plugin([])->getEnabledProviders(1));
    }

    public function testTheBadgesUseTheDoiOfTheDisplayedVersion(): void
    {
        $settings = ['dimensionsEnabled' => true];
        $templateMgr = $this->templateManager(['publication' => $this->publication('10.1234/old-version')]);
        $this->assertSame('10.1234/old-version', $this->plugin($settings)->getArticleDoi($templateMgr, 1));

        // No badge outside the article page, without a provider, or without a DOI.
        $this->assertNull($this->plugin($settings, false)->getArticleDoi($templateMgr, 1));
        $this->assertNull($this->plugin([])->getArticleDoi($templateMgr, 1));
        $this->assertNull($this->plugin($settings)->getArticleDoi($this->templateManager(['publication' => $this->publication('')]), 1));
        $this->assertNull($this->plugin($settings)->getArticleDoi($this->templateManager([]), 1));
    }

    public function testEveryProviderHasItsOfficialScriptOverHttps(): void
    {
        $this->assertSame(\ArticleMetricsBadgesPlugin::PROVIDERS, array_keys(\ArticleMetricsBadgesPlugin::PROVIDER_SCRIPTS));
        foreach (\ArticleMetricsBadgesPlugin::PROVIDER_SCRIPTS as $url) {
            $this->assertStringStartsWith('https://', $url);
        }
    }

    public function testTheBlockFollowsThePlugin(): void
    {
        $block = new \ArticleMetricsBadgesBlockPlugin($this->plugin(['enabled' => true]));
        $this->assertTrue($block->getEnabled(1));
        $this->assertFalse($block->getCurrentVersion());
        $this->assertTrue($block->getHideManagement());
        $this->assertSame('ArticleMetricsBadgesBlockPlugin', $block->getName());
        $this->assertFalse((new \ArticleMetricsBadgesBlockPlugin($this->plugin(['enabled' => false])))->getEnabled(1));
    }

    public function testTheFormOnlyAcceptsTheChoicesItOffers(): void
    {
        $valid = ['plumxEnabled' => true, 'showInline' => true, 'inlineHook' => 'details', 'plumxWidgetType' => 'plumx-details', 'plumxWidth' => '350px', 'dimensionsStyle' => 'bar'];
        $this->assertTrue($this->checksPass($valid), 'A valid form was refused.');

        foreach (['inlineHook' => 'Templates::Article::Main" onload="x', 'plumxWidgetType' => 'plumx-summary x', 'altmetricPopover' => 'middle', 'plumxWidth' => '350px;color:red'] as $name => $value) {
            $this->assertFalse($this->checksPass(array_merge($valid, [$name => $value])), "{$name} = {$value} was accepted.");
        }
    }

    public function testAtLeastOneProviderAndOnePositionAreRequired(): void
    {
        $form = $this->formWithoutRequestChecks();
        $form->setData('showInline', true);
        $form->validate(false);
        $this->assertArrayHasKey('plumxEnabled', $form->getErrorsArray());

        $form = $this->formWithoutRequestChecks();
        $form->setData('dimensionsEnabled', true);
        $form->validate(false);
        $this->assertArrayHasKey('showInline', $form->getErrorsArray());
    }

    /**
     * The settings form without its POST and CSRF checks, which need a request and a session.
     */
    protected function formWithoutRequestChecks(): \ArticleMetricsBadgesSettingsForm
    {
        $form = new \ArticleMetricsBadgesSettingsForm($this->plugin([]), 1);
        $form->_checks = array_values(array_filter($form->_checks, function ($check) {
            return !in_array(get_class($check), ['FormValidatorPost', 'FormValidatorCSRF'], true);
        }));
        return $form;
    }

    /**
     * Whether the field checks pass for the given data.
     */
    protected function checksPass(array $data): bool
    {
        $form = $this->formWithoutRequestChecks();
        foreach ($data as $name => $value) {
            $form->setData($name, $value);
        }
        foreach ($form->_checks as $check) {
            if (!$check->isValid()) {
                return false;
            }
        }
        return true;
    }
}
