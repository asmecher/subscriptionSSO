<?php

/**
 * @file plugins/generic/subscriptionSSO/SubscriptionSSOPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2014-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file COPYING.
 *
 * @package plugins_generic_subscriptionSSO
 *
 * Plugin to defer subscription checks to an external system.
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class SubscriptionSSOPlugin extends GenericPlugin {
	/**
	 * @copydoc GenericPlugin::register
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			HookRegistry::register('LoadHandler',array(&$this, 'loadHandlerCallback'));
			HookRegistry::register('IssueAction::subscribedUser', array(&$this, 'subscribedUserCallback'));
			return true;
		}
		return $success;
	}

	/**
	 * Callback when a handler is loaded. Used to check for the presence
	 * of an incoming authentication, which needs to be verified.
	 * @param $hookName string Hook name
	 * @param $args array Hook arguments
	 * @return boolean Hook return status
	 */
	function loadHandlerCallback($hookName, $args) {
		$request = Application::getRequest();
		$journal = $request->getJournal();
		$incomingParameterName = $this->getSetting($journal->getId(), 'incomingParameterName');
		// Using $_GET rather than Request because this may be case
		// sensitive (e.g. differentiating myid from myId)
		if ($incomingParameterName != '' && isset($_GET[$incomingParameterName])) {
			$incomingKey = $_GET[$incomingParameterName];

			// This is an incoming authorization. Contact the remote service.
			$verificationUrl = $this->getSetting($journal->getId(), 'verificationUrl');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $verificationUrl . urlencode($incomingKey));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1) ;
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$result = curl_exec($ch);
			curl_close($ch);

			// Verify the result.
			$resultRegexp = $this->getSetting($journal->getId(), 'resultRegexp');
			if (preg_match($resultRegexp, $result)) {
				// Successfully validated.
				$_SESSION['subscriptionSSOTimestamp'] = time();
			} else {
				// Failed to validate.
				unset($_SESSION['subscriptionSSOTimestamp']);
				$request->redirectURL($this->getSetting($journal->getId(), 'redirectUrl'));
			}
		}
		return false;
	}

	/**
	 * Callback when a handler is loaded. Used to check for the presence
	 * of an incoming authentication, which needs to be verified.
	 * @param $hookName string Hook name
	 * @param $args array Hook arguments
	 * @return boolean Hook return status
	 */
	function subscribedUserCallback($hookName, $args) {
		// Exclude the index and issue pages.
		$request = Application::getRequest();
		if (in_array($request->getRequestedPage(), array('', 'index', 'issue', 'search'))) return false;

		// Permit an abstract view.
		if ($request->getRequestedPage() == 'article' && $request->getRequestedOp() == 'view' && count($request->getRequestedArgs())==1) return false;

		$journal = $args[1];
		$result =& $args[4]; // Reference required
		$result = isset($_SESSION['subscriptionSSOTimestamp']) && $_SESSION['subscriptionSSOTimestamp'] + ($this->getSetting($journal->getId(), 'hoursValid') * 3600) > time();
		if (!$result) {
			// If we're not subscribed, redirect.
			$request->redirectURL($this->getSetting($journal->getId(), 'redirectUrl') . '?redirectUrl=' . urlencode($request->getRequestUrl()));
		}
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 * @param $params array
	 * @param $smarty Smarty
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array_merge($actionArgs, array('verb' => 'settings'))),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * @copydoc PKPPlugin::manage()
	 */
	function manage($args, $request) {
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);

		switch ($request->getUserVar('verb')) {
			case 'settings':
				$this->import('SubscriptionSSOSettingsForm');
				$form = new SubscriptionSSOSettingsForm($this, $context->getId());
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage();
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc Plugin::getDisplayName
	 */
	function getDisplayName() {
		return __('plugins.generic.subscriptionSSO.name');
	}

	/**
	 * @copydoc Plugin::getDescription
	 */
	function getDescription() {
		return __('plugins.generic.subscriptionSSO.description');
	}
}

?>
