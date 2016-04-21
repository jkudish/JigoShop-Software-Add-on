JigoShop Software Add-On

This plugin extends the WordPress [JigoShop](http://jigoshop.com/) e-commerce plugin by offering the disabling and enabling certain functionality which transform the shop into a software selling hub, include a full API for license generation, license activation, license retrieval, activation e-mails, stats and more.

The code is well documented (inline) with more documentation to come. The project started off as a private client request, but is now public for anyone to collaborate on. That being said, the client continues to use this plugin and its updates, and thus there may be certain constraints before pushing an update (otherwise a branch may be required). We are open to any suggestions :)

The plugin requires JigoShop 1.1.1 or higher.

Changelog
===========
### 2.7
* Updates hook for post_paypal() trigger and enables subscribing and unsubscribing to activation notifications.

### 2.6
* Add upgrades page and related functionality

### 2.5
* Remove global stats, they were slow, had performance issues and not really used

### 2.4
* Lots of code cleanup for PHP notices/warnings, code standards and performance/security
* Spacing, indentation and code style fixes
* Update Github Updater class to latest version
* Fixes to the shop order search functionality (remove some code that is now native to Jigoshop and improve the display of search results)


### 2.3.2
* Fix saving of checkbox order meta fields

### 2.3.1
* Fix how the paypal transaction ID is recorded to work with the latest version(s) of JigoShop

### 2.3
* Prevent license keys from being used to upgrade more than once, along with necessary API adjustments.
* Fix a bug where upgrade orders were missing the product id.
* Properly register/enqueue css stylesheets.
* Other minor code cleanup/fixes.

### 2.2
* Major rewrite of how upgradable products work. If you are relying on the license key based upgrade system you may want to hold-off this update and/or [manually] convert your products to the new system
* Products can now be configured as an upgrade from and to another product
* Minor code cleanup

### 2.1.6
* Empty the cart whenever something is added to it (to make sure only 1 item is there at a time)

### 2.1.5
* Empty the cart when a sale is completed

### 2.1.4
* Empty cart when order is cancelled at paypal
* Minor adjustment to output buffer used to modify price sent to paypal
* Fix php notice in number_format function

### 2.1.3
* Save license keys with prefixes as lowercase

### 2.1.2
* Add license key field to order listings

### 2.1.1
* Fix several minor bugs with retrieving and saving product & order data

### 2.1
* Made some adjustments to the code, to adhere to WordPress coding standards
* Modified all &$this to $this (not required in PHP 5+)
* Internationalize plugin
* Complete phpDocs
* use $_REQUEST instead of $method in the API
* Stats page: visual bug when no activations yet
* Added a new Deactivation API
* Add an optional license key prefix per product
* Verify and ensure compatibility with JigoShop 1.1.1 (which is now required)

### 2.0
* activation reset method of the API now requires a valid license key (extra security measure)

### 1.9
* Remove all whitespace in all files
* Fix bug where number of possible activations would sometimes be reset to 0

### 1.8.9
* Remove some whitespace
* Fix compatibility for cart with JigoShop 0.9.9.1
* The plugin now requires JigoShop 0.9.9.1. If you need compatibility with an older version, please use version 1.8.8 or below of this plugin.

### 1.8.8
* Prevent timeout in updater class
* Only run the updater in the admin

### 1.8.7
* Fixed wp_error in updater class

### 1.8.6
* Ordering in json responses for activation API calls
* Removed old commented code
* Fixed wrong class_exists statement in updater, props @otto42

### 1.8.5
* Fixed headers for error API responses
* API nonce ordering in json responses

### 1.8.4
* Changed headers to resolve IE caching issues in the API
* Added the ability to pass a nonce in the API

### 1.8.3
* Fix timestamp output bug again

### 1.8.2
* Fix timestamp output bug

### 1.8.1
* Timestamp in correct order for sig in API requests

### 1.8
* Add timestamp to all API requests
* Fix PHP notice bug with the updater class from 1.7

### 1.7
* Ability to resend purchase e-mail to customers from backend
* Fix PHP memory bug with the updater class

### 1.6
* Store transaction ID for each order
* Process and send e-mail with Paypal IPN response, making it more bullet-proof

### 1.5.3
* fix a WP_Error bug with the updater class

### 1.5.2
* PHP < 5.3 backwards compatibly for removing the pragma header

### 1.5.1
* Remove pragma header

### 1.5
* Set proper headers for IE7 in the API

### 1.4
* disable email now button after click, fixes issue #15

### 1.3
* One-click plugin updates in the backend (a la typical WordPress), still only hosted on GitHub

### 1.2
* Initial Public Release
* Proper decimal formatting for prices on purchase pages (front-end)
* Allow searching for orders via the activation e-mail in the admin
* Removed direct references to client site/project in favour of more generic examples and strings

### 1.1
* Added an import page in the admin which allows to import orders
* See [this page](https://github.com/jkudish/JigoShop-Software-Add-on/wiki/Import-Instructions) for details on the import routine

### 1.0
* Initial Private Release

Current Version
===============

The line below is used for the updater API, please leave it untouched unless bumping the version up :)

~Current Version:2.6~
