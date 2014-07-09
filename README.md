subscriptionSSO
===============

Subscription SSO (single-sign-on) plugin for OJS 2.x.

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
