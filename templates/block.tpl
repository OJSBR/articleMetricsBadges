{**
 * plugins/generic/articleMetricsBadges/templates/block.tpl
 *
 * Copyright (c) 2026 OJSBR - STNT Tecnologia da Informacao LTDA
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Sidebar block wrapper for the metrics badges.
 *}
<div class="pkp_block block_article_metrics_badges">
	<span class="title">{$metricsBadgesBlockTitle|escape}</span>
	<div class="content">
		{include file=$metricsBadgesTemplatePath}
	</div>
</div>
