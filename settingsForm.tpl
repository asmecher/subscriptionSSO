{**
 * plugins/generic/subscriptionSSO/settingsForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file COPYING.
 *
 * Subscription SSO plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.subscriptionSSO.subscriptionSSOSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="subscriptionSSOSettings">
<div id="description">{translate key="plugins.generic.subscriptionSSO.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="40%" class="label">{fieldLabel name="incomingParameterName" required="true" key="plugins.generic.subscriptionSSO.settings.incomingParameterName"}</td>
		<td width="60%" class="value"><input type="text" name="incomingParameterName" id="incomingParameterName" value="{$incomingParameterName|escape}" size="15" maxlength="25" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="verificationUrl" required="true" key="plugins.generic.subscriptionSSO.settings.verificationUrl"}</td>
		<td class="value"><input type="text" name="verificationUrl" id="verificationUrl" value="{$verificationUrl|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="resultRegexp" required="true" key="plugins.generic.subscriptionSSO.settings.resultRegexp"}</td>
		<td class="value"><input type="text" name="resultRegexp" id="resultRegexp" value="{$resultRegexp|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="redirectUrl" required="true" key="plugins.generic.subscriptionSSO.settings.redirectUrl"}</td>
		<td class="value"><input type="text" name="redirectUrl" id="redirectUrl" value="{$redirectUrl|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="hoursValid" required="true" key="plugins.generic.subscriptionSSO.settings.hoursValid"}</td>
		<td class="value"><input type="text" name="hoursValid" id="hoursValid" value="{$hoursValid|escape}" size="15" maxlength="25" class="textField" /></td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
