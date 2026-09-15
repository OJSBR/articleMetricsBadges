<?php

/**
 * @file plugins/generic/articleMetricsBadges/tests/ArticleMetricsBadgesTest.php
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleMetricsBadgesTest
 *
 * @brief Providers, the DOI of the displayed version, the inline position, the
 *        sidebar block and the settings form.
 */

namespace APP\plugins\generic\articleMetricsBadges\tests;

use APP\plugins\generic\articleMetricsBadges\ArticleMetricsBadgesBlockPlugin;
use APP\plugins\generic\articleMetricsBadges\ArticleMetricsBadgesPlugin;
use APP\plugins\generic\articleMetricsBadges\ArticleMetricsBadgesSettingsForm;
use APP\publication\Publication;
use PHPUnit\Framework\Attributes\CoversClass;
use PKP\tests\PKPTestCase;

#[CoversClass(ArticleMetricsBadgesPlugin::class)]
#[CoversClass(ArticleMetricsBadgesBlockPlugin::class)]
#[CoversClass(ArticleMetricsBadgesSettingsForm::class)]
class ArticleMetricsBadgesTest extends PKPTestCase
{
    /**
     * A plugin with the given settings, on the article page or not.
     */
    protected function plugin(array $settings, bool $articlePage = true): ArticleMetricsBadgesPlugin
    {
        return new class ($settings, $articlePage) extends ArticleMetricsBadgesPlugin {
            public function __construct(private array $settings, private bool $articlePage)
            {
                parent::__construct();
            }

            public function getSetting($contextId, $name)
            {
                return $this->settings[$name] ?? null;
            }

            public function getEnabled($contextId = null)
            {
                return (bool) ($this->settings['enabled'] ?? false);
            }

            protected function isArticlePage(): bool
            {
                return $this->articlePage;
            }

            public function getTemplateResource($template = null, $inCore = false)
            {
                return (string) $template;
            }
        };
    }

    /**
     * A template manager holding the given template variables.
     */
    protected function templateManager(array $vars): object
    {
        return new class ($vars) {
            public function __construct(public array $vars)
            {
            }

            public function getTemplateVars($name)
            {
                return $this->vars[$name] ?? null;
            }
        };
    }

    protected function publication(string $doi): Publication
    {
        $publication = new class () extends Publication {
            public string $doi = '';

            public function getDoi(): ?string
            {
                return $this->doi;
            }
        };
        $publication->doi = $doi;
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
        $this->assertSame(ArticleMetricsBadgesPlugin::PROVIDERS, array_keys(ArticleMetricsBadgesPlugin::PROVIDER_SCRIPTS));
        foreach (ArticleMetricsBadgesPlugin::PROVIDER_SCRIPTS as $url) {
            $this->assertStringStartsWith('https://', $url);
        }
    }

    public function testTheBlockFollowsThePluginAndItsSetting(): void
    {
        $block = new ArticleMetricsBadgesBlockPlugin($this->plugin(['enabled' => true]));
        $this->assertTrue($block->getEnabled(1));
        $this->assertFalse($block->getCurrentVersion());
        $this->assertTrue($block->getHideManagement());
        $this->assertSame('ArticleMetricsBadgesBlockPlugin', $block->getName());
        $this->assertFalse((new ArticleMetricsBadgesBlockPlugin($this->plugin(['enabled' => false])))->getEnabled(1));
    }

    public function testTheFormOnlyAcceptsTheChoicesItOffers(): void
    {
        $form = new ArticleMetricsBadgesSettingsForm($this->plugin([]), 1);
        $valid = ['plumxEnabled' => true, 'showInline' => true, 'inlineHook' => 'details', 'plumxWidgetType' => 'plumx-details', 'plumxWidth' => '350px', 'dimensionsStyle' => 'bar'];
        foreach ($valid as $name => $value) {
            $form->setData($name, $value);
        }
        $this->assertTrue($this->checksPass($form), 'A valid form was refused.');

        foreach (['inlineHook' => 'Templates::Article::Main" onload="x', 'plumxWidgetType' => 'plumx-summary x', 'altmetricPopover' => 'middle', 'plumxWidth' => '350px;color:red'] as $name => $value) {
            $form = new ArticleMetricsBadgesSettingsForm($this->plugin([]), 1);
            foreach ($valid + [$name => $value] as $field => $fieldValue) {
                $form->setData($field, $fieldValue);
            }
            $form->setData($name, $value);
            $this->assertFalse($this->checksPass($form), "{$name} = {$value} was accepted.");
        }
    }

    public function testAtLeastOneProviderAndOnePositionAreRequired(): void
    {
        $form = new ArticleMetricsBadgesSettingsForm($this->plugin([]), 1);
        $form->setData('showInline', true);
        $form->validate(false);
        $this->assertArrayHasKey('plumxEnabled', $form->getErrorsArray());

        $form = new ArticleMetricsBadgesSettingsForm($this->plugin([]), 1);
        $form->setData('dimensionsEnabled', true);
        $form->validate(false);
        $this->assertArrayHasKey('showInline', $form->getErrorsArray());
    }

    public function testTheBadgesGoOnlyWhereTheManagerChose(): void
    {
        $this->assertSame('Templates::Article::Details', ArticleMetricsBadgesPlugin::INLINE_HOOKS['details']);
        $source = (string) file_get_contents(dirname(__DIR__) . '/ArticleMetricsBadgesPlugin.php');
        $this->assertStringContainsString("(self::INLINE_HOOKS[\$chosenHook] ?? null) !== \$hookName", $source);
        $this->assertSame(3, count(ArticleMetricsBadgesPlugin::INLINE_HOOKS));
    }

    /**
     * Whether the field checks of the form pass (POST and CSRF left out: there is no request).
     */
    protected function checksPass(ArticleMetricsBadgesSettingsForm $form): bool
    {
        foreach ($form->_checks as $check) {
            if (in_array((new \ReflectionClass($check))->getShortName(), ['FormValidatorPost', 'FormValidatorCSRF'], true)) {
                continue;
            }
            if (!$check->isValid()) {
                return false;
            }
        }
        return true;
    }
}
