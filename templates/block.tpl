{**
 * templates/block.tpl
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Subscription SSO sidebar block.
 *}
<div class="pkp_block block_subscription_sso">
	<h2 class="title">{translate key="plugins.generic.subscriptionSSO.block.displayName"}</h2>
	<div class="content">
		<p>
			<a href="{$subscriptionSSOLoginUrl|escape}">{translate key="plugins.generic.subscriptionSSO.block.login"}</a>
		</p>
	</div>
</div>
