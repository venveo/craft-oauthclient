# OAuth 2.0 Client plugin for Craft CMS 3.1

This plugin provides developers with an easy centralized approach to managing and storing OAuth 2.0
clients and tokens.

It exposes an easy to use API and frontend for authorizing tokens for internal business logic. What it does not do is
act as an authentication provider for users to login to the CMS.

## Example Use Cases
- Building a custom CRM integration
- Reading from and writing to Google Sheets
- Querying data on a business' Facebook page

## Requirements

This plugin should work on Craft CMS 3.2.0 or later, and likely earlier versions of Craft.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require venveo/craft-oauthclient

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for OAuth 2.0 Client.

Brought to you by [Venveo](https://venveo.com)
