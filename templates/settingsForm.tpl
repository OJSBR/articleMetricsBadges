{**
 * plugins/generic/articleMetricsBadges/templates/settingsForm.tpl
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Article Metrics Badges plugin settings.
 *}
<script>
	$(function() {ldelim}
		$('#articleMetricsBadgesSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<script src="{$settingsScriptUrl|escape}"></script>

<form class="pkp_form" id="articleMetricsBadgesSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="articleMetricsBadgesSettingsFormNotification"}
	{include file="common/formErrors.tpl"}

	<div id="description">{translate key="plugins.generic.articleMetricsBadges.settings.description"}</div>

	{fbvFormArea id="articleMetricsBadgesProviders" title="plugins.generic.articleMetricsBadges.settings.providers"}
		{fbvFormSection list="true" description="plugins.generic.articleMetricsBadges.settings.providers.description"}
			{fbvElement type="checkbox" id="plumxEnabled" label="plugins.generic.articleMetricsBadges.settings.provider.plumx" checked=$plumxEnabled|compare:true}
			{fbvElement type="checkbox" id="dimensionsEnabled" label="plugins.generic.articleMetricsBadges.settings.provider.dimensions" checked=$dimensionsEnabled|compare:true}
			{fbvElement type="checkbox" id="altmetricEnabled" label="plugins.generic.articleMetricsBadges.settings.provider.altmetric" checked=$altmetricEnabled|compare:true}
		{/fbvFormSection}
		{fbvFormSection}
			<div class="pkp_help">
				<p>{translate key="plugins.generic.articleMetricsBadges.settings.providers.licensing"}</p>
				<ul>
					<li><strong>PlumX</strong> &mdash; {translate key="plugins.generic.articleMetricsBadges.settings.provider.plumx.terms"}
						<a href="https://plu.mx/plum/developers/widgets" target="_blank" rel="noopener noreferrer">plu.mx/plum/developers/widgets</a></li>
					<li><strong>Dimensions</strong> &mdash; {translate key="plugins.generic.articleMetricsBadges.settings.provider.dimensions.terms"}
						<a href="https://www.dimensions.ai/policies-terms-metrics/" target="_blank" rel="noopener noreferrer">dimensions.ai/policies-terms-metrics</a></li>
					<li><strong>Altmetric</strong> &mdash; {translate key="plugins.generic.articleMetricsBadges.settings.provider.altmetric.terms"}
						<a href="https://www.altmetric.com/solutions/free-tools/free-badges-for-individual-researchers/" target="_blank" rel="noopener noreferrer">altmetric.com</a></li>
				</ul>
				<p>{translate key="plugins.generic.articleMetricsBadges.settings.providers.privacy"}</p>
			</div>
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="articleMetricsBadgesPositions" title="plugins.generic.articleMetricsBadges.settings.positions"}
		{fbvFormSection list="true" description="plugins.generic.articleMetricsBadges.settings.positions.description"}
			{fbvElement type="checkbox" id="showInline" label="plugins.generic.articleMetricsBadges.settings.showInline" checked=$showInline|compare:true}
			{fbvElement type="checkbox" id="showBlock" label="plugins.generic.articleMetricsBadges.settings.showBlock" checked=$showBlock|compare:true}
		{/fbvFormSection}
		<div id="articleMetricsBadgesInlineOptions">
			{fbvFormSection for="inlineHook" description="plugins.generic.articleMetricsBadges.settings.inlineHook"}
				{fbvElement type="select" id="inlineHook" from=$inlineHookOptions selected=$inlineHook translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
		</div>
		<div id="articleMetricsBadgesBlockOptions">
			{fbvFormSection for="blockTitle" description="plugins.generic.articleMetricsBadges.settings.blockTitle"}
				{fbvElement type="text" id="blockTitle" value=$blockTitle inline=true size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}
		</div>
	{/fbvFormArea}

	<div id="articleMetricsBadgesPlumxOptions">
		{fbvFormArea id="articleMetricsBadgesPlumx" title="plugins.generic.articleMetricsBadges.settings.plumx"}
			{fbvFormSection for="plumxWidgetType" description="plugins.generic.articleMetricsBadges.settings.plumx.widgetType"}
				{fbvElement type="select" id="plumxWidgetType" from=$plumxWidgetTypeOptions selected=$plumxWidgetType translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection for="plumxOrientation" description="plugins.generic.articleMetricsBadges.settings.plumx.orientation"}
				{fbvElement type="select" id="plumxOrientation" from=$plumxOrientationOptions selected=$plumxOrientation translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection for="plumxWidth" description="plugins.generic.articleMetricsBadges.settings.plumx.width"}
				{fbvElement type="text" id="plumxWidth" value=$plumxWidth inline=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection list="true"}
				{fbvElement type="checkbox" id="plumxHideWhenEmpty" label="plugins.generic.articleMetricsBadges.settings.plumx.hideWhenEmpty" checked=$plumxHideWhenEmpty|compare:true}
				{fbvElement type="checkbox" id="plumxBorder" label="plugins.generic.articleMetricsBadges.settings.plumx.border" checked=$plumxBorder|compare:true}
				{fbvElement type="checkbox" id="plumxHidePrint" label="plugins.generic.articleMetricsBadges.settings.plumx.hidePrint" checked=$plumxHidePrint|compare:true}
			{/fbvFormSection}
		{/fbvFormArea}
	</div>

	<div id="articleMetricsBadgesDimensionsOptions">
		{fbvFormArea id="articleMetricsBadgesDimensions" title="plugins.generic.articleMetricsBadges.settings.dimensions"}
			{fbvFormSection for="dimensionsStyle" description="plugins.generic.articleMetricsBadges.settings.dimensions.style"}
				{fbvElement type="select" id="dimensionsStyle" from=$dimensionsStyleOptions selected=$dimensionsStyle translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection list="true"}
				{fbvElement type="checkbox" id="dimensionsHideZero" label="plugins.generic.articleMetricsBadges.settings.dimensions.hideZero" checked=$dimensionsHideZero|compare:true}
			{/fbvFormSection}
		{/fbvFormArea}
	</div>

	<div id="articleMetricsBadgesAltmetricOptions">
		{fbvFormArea id="articleMetricsBadgesAltmetric" title="plugins.generic.articleMetricsBadges.settings.altmetric"}
			{fbvFormSection}
				<div class="pkp_help">{translate key="plugins.generic.articleMetricsBadges.settings.altmetric.coverageWarning"}</div>
			{/fbvFormSection}
			{fbvFormSection for="altmetricBadgeType" description="plugins.generic.articleMetricsBadges.settings.altmetric.badgeType"}
				{fbvElement type="select" id="altmetricBadgeType" from=$altmetricBadgeTypeOptions selected=$altmetricBadgeType translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection for="altmetricPopover" description="plugins.generic.articleMetricsBadges.settings.altmetric.popover"}
				{fbvElement type="select" id="altmetricPopover" from=$altmetricPopoverOptions selected=$altmetricPopover translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
		{/fbvFormArea}
	</div>

	{fbvFormButtons submitText="common.save" hideCancel=true}

	<p><span class="pkp_help">{translate key="plugins.generic.articleMetricsBadges.settings.doiNotice"}</span></p>
</form>
