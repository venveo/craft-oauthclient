# OAuth 2.0 Client Changelog

## 4.0.0-beta.1 - Unreleased

### Changed
- Now requires Craft 4 & PHP 8.0.2
- Apps no longer keep track of who created them

## Fixed
- Fixed saving an app with no scopes producing an error

### Removed
- Removed route `oauthclient/authorize/refresh/` use `oauth/authorize/refresh/` instead
- Removed route `oauthclient/authorize/` use `oauth/authorize/` instead
- Removed `Plugin::$plugin` static property - use `Plugin::getInstance()` instead
- Removed `userId` property from Apps

## 2.1.9 - 2021-03-30
### Added
- It's now possible to specify a redirect URL in `App::getRedirectUrl`

### Fixed
- The `renderConnector` no longer uses a form to submit, making it possible to use within forms (e.g. field layout templates)

## 2.1.8 - 2020-10-27
### Fixed
- Fixed Composer 2 compatibility (#32)

## 2.1.7 - 2020-04-06
### Fixed
- Error when creating Facebook provider due to lack of Graph API version

### Changed
- The Authorization process will now keep track of a context in the session

### Added
- Added `AuthorizationEvent` to modify the flow of app authorization, such as the return URI for non-POST authorizations.
- Added `venveo\oauthclient\controllers\AuthorizeController::EVENT_BEFORE_AUTHENTICATE`
- Added `venveo\oauthclient\controllers\AuthorizeController::EVENT_AFTER_AUTHENTICATE`

## 2.1.6 - 2020-02-18
### Fixed
- Fixed a permissions error that could occur when a non-admin user tries to authenticate an app

### Changed
- Changed `oauthclient/authorize` and `oauthclient/authorize/refresh` routes to `oauth/authorize` and `oauth/authorize/refresh` respectively. THe original routes will continue to function as expected.

## 2.1.5 - 2020-02-17
### Fixed
- Incorrect response from getUrlAuthorize (Thanks, @kennethormandy)

## 2.1.4 - 2020-02-07
### Fixed
- Error when invoking createTokenModelResponse (Thanks, @joshangell)

## 2.1.3 - 2020-01-13
### Added
- Added a setting to override a provider's authorization URL
- Added `EVENT_CREATE_TOKEN_MODEL_FROM_RESPONSE` to base Provider
- Added a connect button to the app edit page

### Changed
- Allow providers to define their own logic for token creation

### Fixed
- Improved compatibility with older versions of PHP (7.0+)

## 2.1.2 - 2019-11-09
### Added
- Added permissions for authorizing with apps

### Changed
- The Connect button on apps in the CP now includes a `plugin.cp` context

## 2.1.1 - 2019-10-08
### Added
- Added `AuthorizationUrlEvent` event type
- Added `EVENT_GET_URL_OPTIONS` event to `Apps` service to allow modification of options
- Added `context` parameter to getRedirectUrl($context) to track the EVENT_GET_URL_OPTIONS
- Added `context` parameter to renderConnector($context) to track the EVENT_GET_URL_OPTIONS

## 2.1.0 - 2019-10-08
### Added
- Added project config support
- Added events `EVENT_BEFORE_APP_DELETED` and `EVENT_AFTER_APP_DELETED`

### Changed
- Minimum Craft version require is now 3.1.34.3
- Events now extend `ModelEvent`
- Optimized event triggers

### Fixed
- Fixed deleting apps

## 2.0.4 - 2019-10-03
### Added
- Added renderConnector() to app model
- Added support for redirectUri in connector controller

### Fixed
- Fixed bug where tokens for the wrong app could be retrieved

## 2.0.3 - 2019-10-03
### Added
- Added `craft.oauth` Twig variable
- Added `craft.oauth.getAppByHandle()` Twig helper
- Added CLI for refreshing app tokens `oauthclient/apps/refresh-tokens <app handle>`
- Added events for token refresh before, after, and error
- Added `getValidTokensForUser()` to App model
- Added `checkTokenWithProvider()` to Credentials service
- Added `ValidatesToken` interface for providers to implement

### Fixed
- Fixed a potential bug getting tokens by app & user

### Changed
- refreshToken service method no longer accepts an $app parameter

## 2.0.2 - 2019-10-02
### Fixed
- Fixed install migration on MySQL

## 2.0.1 - 2019-10-02
### Changed
- Make sure only admins can access the OAuth settings

## 2.0.0 - 2019-10-02
### Added
- Initial release

## 1.0.0 - 2018-12-04
### Added
- Initial release
