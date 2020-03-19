<?php

/**
 * @file SubscriptionSSOSettingsForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2014-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class SubscriptionSSOSettingsForm
 * @brief Form for journal managers to modify subscription SSO plugin settings
 */

import('lib.pkp.classes.form.Form');

class SubscriptionSSOSettingsForm extends Form {

	/** @var int */
	var $_journalId;

	/** @var GenericPlugin */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function __construct($plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorRegExp($this, 'incomingParameterName', 'required', 'plugins.generic.subscriptionSSO.settings.incomingParameterName.required', '/^[a-zA-Z0-9\/._-]+$/'));
		$this->addCheck(new FormValidatorURL($this, 'verificationUrl', 'required', 'plugins.generic.subscriptionSSO.settings.verificationUrl.required'));
		$this->addCheck(new FormValidator($this, 'resultRegexp', 'required', 'plugins.generic.subscriptionSSO.settings.resultRegexp.required'));
		$this->addCheck(new FormValidatorURL($this, 'redirectUrl', 'required', 'plugins.generic.subscriptionSSO.settings.redirectUrl.required'));
		$this->addCheck(new FormValidatorURL($this, 'redirectUrl', 'required', 'plugins.generic.subscriptionSSO.settings.redirectUrl.required'));
		$this->addCheck(new FormValidatorRegExp($this, 'hoursValid', 'required', 'plugins.generic.subscriptionSSO.settings.hoursValid.required', '/^[0-9]+$/'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->_journalId;
		$plugin = $this->_plugin;

		$this->_data = array(
			'incomingParameterName' => $plugin->getSetting($journalId, 'incomingParameterName'),
			'verificationUrl' => $plugin->getSetting($journalId, 'verificationUrl'),
			'resultRegexp' => $plugin->getSetting($journalId, 'resultRegexp'),
			'redirectUrl' => $plugin->getSetting($journalId, 'redirectUrl'),
			'hoursValid' => $plugin->getSetting($journalId, 'hoursValid')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('incomingParameterName', 'verificationUrl', 'resultRegexp', 'redirectUrl', 'hoursValid'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = $this->_plugin;
		$journalId = $this->_journalId;

		$plugin->updateSetting($journalId, 'incomingParameterName', $this->getData('incomingParameterName'), 'string');
		$plugin->updateSetting($journalId, 'verificationUrl', $this->getData('verificationUrl'), 'string');
		$plugin->updateSetting($journalId, 'resultRegexp', $this->getData('resultRegexp'), 'string');
		$plugin->updateSetting($journalId, 'redirectUrl', $this->getData('redirectUrl'), 'string');
		$plugin->updateSetting($journalId, 'hoursValid', $this->getData('hoursValid'), 'string');
	}
}

