{**
 * plugins/generic/articleMetricsBadges/templates/badges.tpl
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Renders one badge per enabled provider for the current article DOI.
 *
 * On the article page the badges are wrapped in a section.item, which is the
 * structure every other element of main_entry uses (authors, doi, abstract,
 * downloads chart). Themes indent .item, so without it the badges hang outside
 * the content column. In the sidebar block the wrapper is not used: the block
 * template provides its own.
 *}
{if !$metricsBadgesInBlock}<section class="item article-metrics-badges-item">{/if}
<div class="article-metrics-badges{if $metricsBadgesInBlock} article-metrics-badges--block{/if}">
	{foreach from=$metricsBadgesProviders item=metricsBadgesProvider}
		{if $metricsBadgesProvider == 'plumx'}
			<div class="article-metrics-badges__item article-metrics-badges__plumx">
				<a href="https://plu.mx/plum/a/?doi={$metricsBadgesDoi|escape:'url'}"
					class="{$plumxWidgetType|escape}"
					data-hide-when-empty="{if $plumxHideWhenEmpty}true{else}false{/if}"
					{if $plumxHidePrint}data-hide-print="true" {/if}
					{if $plumxOrientation}data-orientation="{$plumxOrientation|escape}" {/if}
					{if $plumxBorder}data-border="true" {/if}
					{if $plumxWidth}data-width="{$plumxWidth|escape}"{/if}></a>
			</div>
		{elseif $metricsBadgesProvider == 'dimensions'}
			<div class="article-metrics-badges__item article-metrics-badges__dimensions">
				<span class="__dimensions_badge_embed__"
					data-doi="{$metricsBadgesDoi|escape}"
					data-style="{$dimensionsStyle|escape}"
					data-legend="hover-right"
					{if $dimensionsHideZero}data-hide-zero-citations="true"{/if}></span>
			</div>
		{elseif $metricsBadgesProvider == 'altmetric'}
			<div class="article-metrics-badges__item article-metrics-badges__altmetric">
				<div class="altmetric-embed"
					data-badge-type="{$altmetricBadgeType|escape}"
					data-badge-popover="{$altmetricPopover|escape}"
					data-hide-no-mentions="true"
					data-doi="{$metricsBadgesDoi|escape}"></div>
			</div>
		{/if}
	{/foreach}
</div>
{if !$metricsBadgesInBlock}</section>{/if}
