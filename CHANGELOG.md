# OAuth 2.0 Client Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
