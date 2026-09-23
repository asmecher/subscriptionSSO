<?php

/**
 * @file SubscriptionSSOPlugin.inc.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2014-2026 John Willinsky
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
use APP\facades\Repo;
use APP\issue\IssueAction;
use APP\submission\Submission;
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
            Hook::add('LoadHandler', $this->loadHandlerCallback(...));
            Hook::add('IssueAction::subscribedUser', $this->subscribedUserCallback(...));
            Hook::add('Templates::Common::Sidebar', $this->sidebarCallback(...));
            return true;
        }
        return $success;
    }

    /**
     * Callback when a handler is loaded. Used to check for the presence
     * of an incoming authentication, which needs to be verified.
     */
    function loadHandlerCallback(string $hookName, array $args) : bool
    {
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();
        if (!$journal) return Hook::CONTINUE;

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
                $request->getSession()->put('subscriptionSSOTimestamp', time());
            } else {
                // Failed to validate.
                $request->getSession()->remove('subscriptionSSOTimestamp');
                $request->redirectUrl($this->getSetting($journal->getId(), 'redirectUrl'));
            }
        }
        return Hook::CONTINUE;
    }

    /**
     * Callback when a handler is loaded. Used to check for the presence
     * of an incoming authentication, which needs to be verified.
     */
    function subscribedUserCallback(string $hookName, array $args) : bool
    {
        // Exclude the index and issue pages.
        $request = Application::get()->getRequest();
        if (in_array($request->getRequestedPage(), ['', 'index', 'search'])) return Hook::CONTINUE;
        // Capture issue galley requests, but not e.g. issue archive
        if ($request->getRequestedPage() == 'issue' && count($request->getRequestedArgs()) != 2) return Hook::CONTINUE;

        // Permit an abstract view.
        if ($request->getRequestedPage() == 'article' && $request->getRequestedOp() == 'view' && count($request->getRequestedArgs())==1) return Hook::CONTINUE;

        $articleId = $args[3];
        $submission = Repo::submission()->get($articleId);
        if ($submission && $submission->getCurrentPublication()->getData('accessStatus') == ARTICLE_ACCESS_OPEN) return Hook::CONTINUE;

        $result =& $args[4]; // Reference required
        if ($result) return Hook::CONTINUE; // If a subscription has already been established, respect that

        $journal = $args[1];
        $result = $request->getSession()->get('subscriptionSSOTimestamp', 0) + ($this->getSetting($journal->getId(), 'hoursValid') * 3600) + 1 >= time();
        if (!$result && !$this->allowIndividualPurchase($journal->getId())) {
            $request->redirectUrl($this->getLoginRedirectUrl($request, $journal->getId()));
        }
        return Hook::CONTINUE;
    }

    /**
     * Individual article and issue sales stay off until a journal turns them on.
     */
    function allowIndividualPurchase(int $journalId): bool
    {
        return (bool) $this->getSetting($journalId, 'allowIndividualPurchase');
    }

    /**
     * External login URL, including the URL to return to after authentication.
     */
    function getLoginRedirectUrl($request, int $journalId): string
    {
        return $this->getSetting($journalId, 'redirectUrl') . '?redirectUrl=' . urlencode($request->getRequestUrl());
    }

    /**
     * Prepend the subscription link. It is not a sidebar block the journal can reorder.
     */
    function sidebarCallback(string $hookName, array $args): bool
    {
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();

        if (!$journal || !$this->allowIndividualPurchase($journal->getId())) {
            return Hook::CONTINUE;
        }

        if (!$this->getSetting($journal->getId(), 'redirectUrl')) {
            return Hook::CONTINUE;
        }

        if (!$this->showsSubscriptionContent($request, $journal)) {
            return Hook::CONTINUE;
        }

        $templateMgr = $args[1];
        $templateMgr->assign('subscriptionSSOLoginUrl', $this->getLoginRedirectUrl($request, $journal->getId()));
        $args[2] = $templateMgr->fetch($this->getTemplateResource('block.tpl')) . ($args[2] ?? '');

        return Hook::CONTINUE;
    }

    /**
     * True when this request is showing an article or issue that requires a subscription.
     */
    function showsSubscriptionContent($request, $journal): bool
    {
        $page = $request->getRequestedPage();
        $op = $request->getRequestedOp();
        $args = $request->getRequestedArgs();
        $issueAction = new IssueAction();

        if (
            (in_array($page, ['', 'index']) && in_array($op, ['', 'index']))
            || ($page == 'issue' && $op == 'current')
        ) {
            $issue = Repo::issue()->getCurrent($journal->getId());
            return $issue && $issueAction->subscriptionRequired($issue, $journal);
        }

        if ($page == 'article' && $op == 'view' && isset($args[0])) {
            $submission = Repo::submission()->getByBestId((string) $args[0], $journal->getId());
            $publication = $submission ? $submission->getCurrentPublication() : null;

            if (!$publication || $publication->getData('accessStatus') == Submission::ARTICLE_ACCESS_OPEN) {
                return false;
            }

            $issueId = $publication->getData('issueId');
            $issue = $issueId ? Repo::issue()->get($issueId) : null;

            return $issue && $issueAction->subscriptionRequired($issue, $journal);
        }

        if ($page == 'issue' && $op == 'view' && isset($args[0])) {
            $issue = Repo::issue()->getByBestId((string) $args[0], $journal->getId());

            return $issue && $issueAction->subscriptionRequired($issue, $journal);
        }

        return false;
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
                        $router->url($request, null, null, 'manage', null, array_merge($actionArgs, ['verb' => 'settings'])),
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

