/**
 * @file cypress/tests/functional/ArticleMetricsBadges.cy.js
 *
 * Copyright (c) 2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests: the settings and the badges a reader sees on an article.
 *
 * Parameters (--env): contextPath, adminUser, adminPassword (captcha on login
 * must be off for the run). The defaults match the data set of PKP's continuous
 * integration; the first test enables the plugin when it is off. The reader test
 * looks for a published article with a DOI through the API and is skipped when
 * there is none (the CI data set has no DOIs). The settings are put back as they
 * were after the run; a journal that never saved them keeps those of the run, since
 * the form refuses to save no provider.
 */

describe('Article Metrics Badges plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';

	const row = 'articlemetricsbadgesplugin';
	const form = '#articleMetricsBadgesSettingsForm';
	const settingsUrl = () => pageUrl('$$$call$$$/grid/settings/plugins/settings-plugin-grid/manage') + '?verb=settings&plugin=' + row + '&category=generic&save=1';
	let originalSettings = null;

	// ---- OJSBR spec helpers (padrão v2): work on OJS/OMP 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	// Saves the settings with a request to the form's own URL and the page's token.
	const postSettings = (fields) => cy.window({timeout: 60000}).its('pkp.currentUser.csrfToken').then((token) => request({
		method: 'POST',
		url: settingsUrl(),
		form: true,
		body: Object.assign({}, fields, {csrfToken: token}),
	}));

	// A published article of the journal with a DOI: {id, doi}, or null.
	const findArticleWithDoi = () => cy.window().then((win) => cy.wrap((async() => {
		const get = (path) => win.fetch(path, {credentials: 'same-origin'}).then((response) => response.json());
		const list = await get(pageUrl('api/v1/submissions') + '?status=3&count=50');
		for (const submission of list.items || []) {
			const publication = await get(pageUrl('api/v1/submissions/' + submission.id + '/publications/' + submission.currentPublicationId));
			if (publication.doiObject && publication.doiObject.doi) {
				return {id: submission.id, doi: publication.doiObject.doi};
			}
		}
		return null;
	})(), {timeout: 60000}));

	it('Enables the plugin', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin(row);
	});

	it('Refuses a position or a style the form does not offer', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		openPluginSettings(row, form);
		cy.window().then((win) => {
			if (originalSettings === null) {
				originalSettings = {};
				win.jQuery(form).serializeArray().filter((field) => field.name !== 'csrfToken').forEach((field) => {
					originalSettings[field.name] = field.value;
				});
			}
		});
		postSettings({dimensionsEnabled: '1', showInline: '1', inlineHook: 'Templates::Article::Main" onmouseover="x', dimensionsStyle: 'small_circle'}).then((response) => {
			expect(response.body.status).to.eq(true);
			// The form comes back with its errors instead of closing.
			expect(response.body.content).to.contain('formErrors');
		});
	});

	it('Shows only the enabled badge, with the article DOI, where the manager chose', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		findArticleWithDoi().then((article) => {
			if (!article) {
				cy.log('No published article with a DOI: reader check skipped');
				return;
			}
			postSettings({dimensionsEnabled: '1', showInline: '1', inlineHook: 'main', dimensionsStyle: 'small_circle', dimensionsHideZero: '1'}).its('body.status').should('eq', true);

			cy.clearCookies();
			cy.visit(pageUrl('article/view/' + article.id) + '?reload=' + Date.now(), {headers: {Cookie: 'OJSSID=cypress' + Date.now()}});
			cy.get('script[src^="https://badge.dimensions.ai/badge.js"]').should('have.length', 1);
			cy.get('script[src^="https://cdn.plu.mx/widget-all.js"]').should('have.length', 0);
			cy.get('script[src*="altmetric"], script[src*="d1bxh8uas1mnw7"]').should('have.length', 0);
			cy.get('.article-metrics-badges-item .article-metrics-badges__dimensions [data-doi]').should('have.length', 1)
				.and('have.attr', 'data-doi', article.doi);
			cy.get('link[href*="/articleMetricsBadges/styles/badges.css"]').should('have.length', 1);
			cy.get('.block_article_metrics_badges').should('have.length', 0);
		});
	});

	// Puts the settings back as they were, also when a test failed.
	after(function() {
		if (originalSettings === null) {
			return;
		}
		login(adminUser, adminPassword);
		openPluginsTab();
		postSettings(originalSettings);
	});
});
