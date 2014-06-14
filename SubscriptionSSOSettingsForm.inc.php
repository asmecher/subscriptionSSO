<?php

/**
 * @file plugins/generic/subscriptionSSO/SubscriptionSSOSettingsForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file COPYING.
 *
 * @class SubscriptionSSOSettingsForm
 * @ingroup plugins_generic_subscriptionSSO
 *
 * @brief Form for journal managers to modify subscription SSO plugin settings
 */


import('lib.pkp.classes.form.Form');

class SubscriptionSSOSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function SubscriptionSSOSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorAlphaNum($this, 'incomingParameterName', 'required', 'plugins.generic.subscriptionSSO.settings.incomingParameterName.required'));
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
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

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
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'incomingParameterName', $this->getData('incomingParameterName'), 'string');
		$plugin->updateSetting($journalId, 'verificationUrl', $this->getData('verificationUrl'), 'string');
		$plugin->updateSetting($journalId, 'resultRegexp', $this->getData('resultRegexp'), 'string');
		$plugin->updateSetting($journalId, 'redirectUrl', $this->getData('redirectUrl'), 'string');
		$plugin->updateSetting($journalId, 'hoursValid', $this->getData('hoursValid'), 'string');
	}
}

?>
