<?php

/**
 * @file SubscriptionSSOPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2014-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Plugin to defer subscription checks to an external system.
 */

namespace APP\plugins\generic\subscriptionSSO;

use PKP\linkAction\LinkAction;
use PKP\plugins\GenericPlugin;
use PKP\linkAction\request\AjaxModal;
use PKP\config\Config;
use PKP\plugins\Hook;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use APP\core\Application;

class SubscriptionSSOPlugin extends GenericPlugin {
	/**
	 * @copydoc GenericPlugin::register
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			$this->addLocaleData();
			Hook::add('LoadHandler', [&$this, 'loadHandlerCallback']);
			Hook::add('IssueAction::subscribedUser', [&$this, 'subscribedUserCallback']);
			return true;
		}
		return $success;
	}

	/**
	 * Callback when a handler is loaded. Used to check for the presence
	 * of an incoming authentication, which needs to be verified.
	 * @param string $hookName Hook name
	 * @param array $args Hook arguments
	 * @return boolean Hook return status
	 */
	function loadHandlerCallback($hookName, $args) {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();
		if (!$journal) return false;

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
				$request->redirectUrl($this->getSetting($journal->getId(), 'redirectUrl'));
			}
		}
		return false;
	}

	/**
	 * Callback when a handler is loaded. Used to check for the presence
	 * of an incoming authentication, which needs to be verified.
	 * @param string $hookName Hook name
	 * @param array $args Hook arguments
	 * @return boolean Hook return status
	 */
	function subscribedUserCallback($hookName, $args) {
		// Exclude the index and issue pages.
		$request = Application::get()->getRequest();
		if (in_array($request->getRequestedPage(), ['', 'index', 'search'])) return false;
		// Capture issue galley requests, but not e.g. issue archive
		if ($request->getRequestedPage() == 'issue' && count($request->getRequestedArgs()) != 2) return false;

		// Permit an abstract view.
		if ($request->getRequestedPage() == 'article' && $request->getRequestedOp() == 'view' && count($request->getRequestedArgs())==1) return false;

		$journal = $args[1];

		$result =& $args[4]; // Reference required
		if ($result) return false; // If a subscription has already been established, respect that

		$result = isset($_SESSION['subscriptionSSOTimestamp']) && $_SESSION['subscriptionSSOTimestamp'] + ($this->getSetting($journal->getId(), 'hoursValid') * 3600) + 1 >= time();
		if (!$result) {
			// If we're not subscribed, redirect.
			$request->redirectUrl($this->getSetting($journal->getId(), 'redirectUrl') . '?redirectUrl=' . urlencode($request->getRequestUrl()));
		}
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$router = $request->getRouter();
		return array_merge(
			$this->getEnabled()?[
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array_merge($actionArgs, array('verb' => 'settings'))),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			]:[],
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

