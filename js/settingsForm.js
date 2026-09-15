/**
 * @file plugins/generic/articleMetricsBadges/js/settingsForm.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings form: the options of a provider or a position are shown only while it is
 * checked. The form is loaded in a modal each time it is opened, so the handler is
 * delegated and bound once per page.
 */
(function($) {
	'use strict';

	if (!$ || window.articleMetricsBadgesSettingsBound) {
		return;
	}
	window.articleMetricsBadgesSettingsBound = true;

	var SECTIONS = {
		plumxEnabled: 'articleMetricsBadgesPlumxOptions',
		dimensionsEnabled: 'articleMetricsBadgesDimensionsOptions',
		altmetricEnabled: 'articleMetricsBadgesAltmetricOptions',
		showInline: 'articleMetricsBadgesInlineOptions',
		showBlock: 'articleMetricsBadgesBlockOptions'
	};

	function refresh(form) {
		$.each(SECTIONS, function(checkboxId, sectionId) {
			form.find('#' + sectionId).toggle(form.find('#' + checkboxId).is(':checked'));
		});
	}

	$(document).on('change', '#articleMetricsBadgesSettingsForm input[type="checkbox"]', function() {
		refresh($(this).closest('form'));
	});

	// The form may already be on the page, or arrive later in a modal.
	$(function() {
		refresh($('#articleMetricsBadgesSettingsForm'));
	});
	new MutationObserver(function() {
		var form = $('#articleMetricsBadgesSettingsForm:not([data-sections-ready])');
		if (form.length) {
			form.attr('data-sections-ready', '1');
			refresh(form);
		}
	}).observe(document.body, {childList: true, subtree: true});
})(window.jQuery);
