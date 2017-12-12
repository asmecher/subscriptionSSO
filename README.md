subscriptionSSO
===============

Subscription SSO (single-sign-on) plugin for OJS 3.x. (The 2.x version is in
the `ojs2` branch.)

This plugin permits delegation of OJS subscription checks to a third-party
web service. The plugin requires the following configuration parameters:

- Incoming parameter name (should not be already used anywhere by OJS!)
- Verification URL
- Verification regular expression
- Redirect URL
- Hours valid

When OJS receives a request with the incoming parameter name set, the plugin
will consider it to be a session initiation arising from a login to some
external service. (That service should redirect the user to OJS, supplying
a session ID in the incoming parameter.)

When this happens, OJS will append the value of that parameter to the
configured verification URL and fetch that document behind the scenes using
cURL. It will test the response using the Verification Regular Expression.
If the regular expression matches, the session will be considered verified;
if not, it will be considered failed and the user will be sent to the Redirect
URL.

When a subscription check is performed, OJS will (instead of its usual check)
see if the user has a valid session by the above criteria, and whether it is
fresh enough (see Hours valid). If so, the subscription will be considered
granted; if not, the user will be directed to the Redirect URL.

Example Exchange
================
Scenario: A user hits the OJS website for a particular article (`www.mysite.com/ojs/articles/view/1`) without having logged into the subscription portal (`www.mysite.com/membership`). They log into the portal and are returned to OJS to read the journal content.

Configuration: The journal has the subscriptionSSO plugin installed and configured as follows:
- Incoming parameter name: ssoUserHash
- Verification URL: `http://www.mysite.com/membership/verify.php?sessionId=`
- Verification regular expression: `/^1$/`
- Redirect URL: `http://www.mysite.com/membership/subscriptionRequired.php`
- Hours valid: 4

1. The user hits the OJS URL for an article, such as `http://www.mysite.com/ojs/articles/view/1`. Since this is subscription content, the subscription SSO plugin checks to see if this user has already been validated as recently as the "Hours valid" setting permits. It doesn't know anything about this user, so it redirects their browser to the "Redirect URL", `http://www.mysite.com/membership/subscriptionRequired.php`, adding the url-encoded current URL as a URL parameter called "redirectUrl" so that subscriptionRequired.php knows where to send the user back to afterwards. In this case, the user is redirected to `http://www.mysite.com/membership/subscriptionRequired.php?redirectUrl=http%3A%2F%2Fwww.mysite.com%2Fojs%2Farticles%2Fview%2F1` (the "redirectUrl" parameter is "`http://www.mysite.com/ojs/articles/view/1`", url-encoded.) 
2. The user enters their login information into a form there (or purchases a new subscription). That part of the site decides that the user should now be granted access to subscription content via OJS. To do so, it redirects the user back to the URL it was given via the redirectUrl parameter in step 1, but also adds a parameter that OJS can use to verify the login with. This parameter is named ssoUserHash in this example. The redirectUrl was `http://www.mysite.com/ojs/articles/view/1` from step 1, and the subscription portal generates a unique identifier of `123456` for this user, so it would redirect the user's browser back to OJS via the following URL: `http://www.mysite.com/ojs/articles/view/1?ssoUserHash=123456`
3. OJS's subscriptionSSO plugin sees the ssoUserHash variable and intervenes to verify the session before OJS handles the request as usual. It does this by making a request behind the scenes using the "verification URL" setting (`http://www.mysite.com/membership/verify.php?sessionId=`) and appending the value of the ssoUserHash variable to get `http://www.mysite.com/membership/verify.php?sessionId=123456`. That request returns some data that indicates success or failure of the session verification. The subscriptionSSO plugin checks that result against the configured verification regular expression (`/^1$/` in this case). If it matches, then the session has been successfully verified and OJS will permit access to subscription content for the number of configured valid hours (4 in this case).
4. Since the user has been returned to the page for the same article that was originally requested, they will see the contents of that article.
