{**
 * plugins/generic/subscriptionSSO/settingsForm.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2014-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file COPYING.
 *
 * Subscription SSO plugin settings
 *
 *}
<div id="subscriptionSSOSettings">
<div id="description">{translate key="plugins.generic.subscriptionSSO.settings.description"}</div>

<div class="separator"></div>

<br />

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#subscriptionSSOSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form class="pkp_form" method="post" id="subscriptionSSOSettingsForm" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}

	{fbvFormArea id="ssoSettingsFormArea"}
		{fbvFormSection}
			{fbvElement type="text" id="incomingParameterName" name="incomingParameterName" value=$incomingParameterName label="plugins.generic.subscriptionSSO.settings.incomingParameterName" required=true}
			{fbvElement type="text" id="verificationUrl" name="verificationUrl" value=$verificationUrl label="plugins.generic.subscriptionSSO.settings.verificationUrl" required=true}
			{fbvElement type="text" id="resultRegexp" name="resultRegexp" value=$resultRegexp label="plugins.generic.subscriptionSSO.settings.resultRegexp" required=true}
			{fbvElement type="text" id="redirectUrl" name="redirectUrl" value=$redirectUrl label="plugins.generic.subscriptionSSO.settings.redirectUrl" required=true}
			{fbvElement type="text" id="hoursValid" name="hoursValid" value=$hoursValid label="plugins.generic.subscriptionSSO.settings.hoursValid" required=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
