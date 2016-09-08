=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, sergey.r, mihaimihai, EduardMaghakyan
Donate link: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
License: GPLv2
Requires at least: 3.9
Tested up to: 4.6
Stable tag: 3.8.6

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using [WooCommerce](https://wordpress.org/plugins/woocommerce/) and [WPML](http://wpml.org). It makes products and store pages translatable, lets visitors switch languages and order products in their language.

= Features =

* Lets you translate different kinds of WooCommerce product types
* Central management for translating product categories, tags and custom attributes
* Automatically synchronizes product variations and images
* Keeps the same language through the checkout process
* Sends emails to clients and admins in their selected language
* Allows inventory tracking without breaking products into languages
* Enables running a single WooCommerce store with multiple currencies

= Usage Instructions =

For step by step instructions on setting up a multilingual shop, please go to [WooCommerce Multilingual Manual](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) page.

After installing, follow the steps of the setup wizard to translate the store pages, configure what attributes should be translated, enable the multi-currency mode and other settings.

Then, continue to the 'Products' and any categories, tags and attributes that you use.

When you need help, go to [WooCommerce Multilingual support forum](http://wpml.org/forums/topic-tag/woocommerce/).

= Downloads =

This version of WooCommerce Multilingual works with WooCommerce > 2.1

You will also need [WPML](http://wpml.org), together with the String Translation and the Translation Management modules, which are part of the [Multilingual CMS](http://wpml.org/purchase/) package.

= Minimum versions for WPML and modules =

WooCommerce Multilingual checks that the following versions of WPML and their components are active:

* WPML Multilingual CMS       - 3.4
* WPML String Translation     - 2.0
* WPML Translation Management - 2.2
* WPML Media                  - 2.1

Without having all these running, WooCommerce Multilingual will not be able to run.

== Installation ==

= Minimum Requirements =

* WordPress 3.9 or later
* PHP version 5.6 or later
* MySQL version 5.6 or later

* WooCommerce 2.1 or later
* WPML Multilingual CMS 3.4 or later
* WPML String Translation 2.0 or later
* WPML Translation Management 2.2 or later
* WPML Media 2.1 or later

= WordPress automatic installation =
In your WordPress dashboard, go to the Plugins section and click 'Add new'.

= WPML Installer =
If you're already using WPML on your site, in your WordPress dashboard, go to the Plugins section, click 'Add new' and go to the 'Commercial' tab.

= Manual Installation =
1. Upload 'woocommerce-multilingual' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= Setup =
After installing the plugin either automatically or manually:

1. Follow the steps of the setup wizard for the basic required configuration
2. Translate existing content: products, attributes, permalink bases
3. Optionally, add secondary currencies

= Updating =
Once you installer WooCommerce Multilingual, the built in Installer works together with the WordPress automatic update built in logic to make the updating process as easy as it can be.

== Frequently Asked Questions ==

= Does this work with other e-commerce plugins? =

No. This plugin is tailored for WooCommerce.

= What do I need to do in my theme? =

Make sure that your theme is not hard-coding any URL. Always use API calls to receive URLs to pages and you'll be fine.

= My checkout page displays in the same language =

In order for the checkout and store pages to appear translated, you need to create several WordPress pages and insert the WooCommerce shortcodes into them. You'll have to go over the [documentation](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) and see that you performed all steps on the way.

= Can I have different urls for the store in the different languages =

Yes. You can translate the product permalink base, product category base, product tag base and the product attribute base on the Store URLs section.

= Why do my product category pages return a 404 error? =

In this case, you may need to translate the product category base. You can do that on the Store URLs section.

= Can I set the prices in the secondary currencies? =

By default, the prices in the secondary currencies are determined using the exchange rates that you fill in when you add or edit a currency. On individual products, however, you can override this and set prices manually for the secondary currencies.

= Can I have separate currencies for each language? =

Yes. By default, each currency will be available for all languages, but you can customize this and disable certain currencies on certain languages.

= Is this plugin compatible with other WooCommerce extensions? =

WooCommerce Multilingual is compatible with all major WooCommerce extensions. We're continuously work on checking and maintaining compatibility and collaborate closely with the authors of these extensions.



== Screenshots ==

1. Products translation screen
2. Product translation editor
3. Global attributes translation
4. Multiple currencies
5. Status Page
6. Shop URLs translation screen

== Changelog ==

= 3.8.6 =
* Fix shipping cost conversion issue specific to PHP 5.6
* Bug fix: an incorrect shipping cost was displayed on the backend when the order was placed in a secondary currency
* Bug fix: users with the Shop Manager role were not able to translate products
* Bug fix: changing an order language in the backend did not change the language for attributes in the order
* Bug fix: for every e-mail action took when editing an order a new order e-mail was sent to the admin

= 3.8.5 =
* Fixed more problems related to converting shipping costs in secondary currencies
* Fixed one compatibility problem with WooCommerce Show Single Variations
* Bug fix: product translations were not synchronized correctly when marking an existing product as a translation of another one
* Bug fix: variation names not displayed in tooltips on the orders screen in the backend
* Updated the wpml-config.xml configuration file: copy prices to product translations also when multi-currency is not on
* Other small fixes for the admin interface

= 3.8.4 =
* Bug fix: minimum required amount was not calculated correctly for secondary currencies (not included in the previous version)

= 3.8.3 =
* Added improvements to the Translation Editor for translating custom fields for products and variations
* Added access for translator subscribers to translate content
* Fixed compatibility issues with WooCommerce Visual Products Configurator (wrong amount in cart)
* Fixed a compatibility issue with WooCommerce Product Addons (untranslated labels)
* Fixed compatibility issues with WooCommerce Composite products
* Fixed some new compatibility issues with WooCommerce Bookings
* Bug fix: when using language as parameter and the 'dropdown' option was used for the product categories widget, translated urls were not working
* Bug fix: shipping costs were not showing on the secondary languages in some cases
* Bug fix: the shipping costs were not calculated correctly for currencies using less decimals than the default currency
* Bug fix: adding a product to the cart and then adding its translation too could lead to a fatal error
* Bug fix: switching the language on the cart page when using different domains for different languages was emptying the cart
* Bug fix: minimum required amount was not calculated correctly for secondary currencies
* Bug fix: incorrect currency symbol was displayed on the 'Filter by Price' widget

= 3.8.2 =
* Bug fix: cart strings not displaying in the correct language in some conditions
* Bug fix: prices in secondary currencies were not updated on the front end after changing the price (the cache was not invalidated)
* Bug fix: shipping classes were not synchronized for translated products in some circumstances
* Bug fix: translated endpoints were missing from teh rewrite rules after updating the permalinkst pull
* Bug fix: stock status was sometimes not synchronized correctly when changing the stock manually
* Bug fix: when using the default category base the language switcher did not show translated urls on the front end
* Updated the cart cache hashes logic according to new WooCommerce logic
* Added a new filter: 'wcml_product_custom_prices'
* Added separate section for translatable fields for external products in the translations editor
* Fixed compatibility issues with WooCommerce Table Rate Shipping 3.0+
* Fixed one compatibility issue with WooCommerce Dynamic Pricing: the discount was not shown on the mini-cart
* Fixed compatibility with Product Add-ons: strings were not translated

= 3.8.1 =
* Fixed one compatibility issue with WooCommerce Ajax Cart: cart quantities were not updating
* Fixed one compatibility issue with WooCommerce Bookings: incorrect bookings were shown in the backend when toggling between admin languages
* Fixed one compatibility issue with the Adventure Tours theme
* Fixed one compatibility issue with the Aurum theme
* Fixed compatibility issues with the Composite Products plugin
* Bug fix: auto-generated slugs on the products translation editor were not made unique
* Bug fix: sometimes prices with decimals were subtracted 0.01
* Made translation controls on the WooCommerce products page disabled by default
* Optimized autoloading of PHP classes for better performance

= 3.8 =
* A new design, a new look and feel complementing the new WPML 3.4
* A new translation editor for the products
* New options for translating product attributes
* An enhanced and dedicated configuration screen for multi-currency
* Easier translation of URLs
* Immediate attention to configuration issues on the Status page
* Improved support for the WooCommerce REST API
* Straightforward setup wizard to run WooCommerce Multilingual
* Bundled Installer makes it effortless to add in the required plugins
* Option for downloadable products to share files under each product
* Other price types can be set custom values for secondary currencies
* Translating WooCommerce email strings also got simpler
* Numerous bug fixes and enhancements

= 3.7.16 =
* Compatibility with WooCommerce 2.6 (woocommerce_term_meta tables removed)
* Fixed a compatibility issue with WooCommerce Table Rate Shipping (shipping class not showing on secondary language)
* Bug fix: Translated shipping classes were sometimes not displayed for secondary languages
* Bug fix: WooCommerce Booking & Appointments causes Fatal Error when Translation Management
* Bug fix: Yoast custom fields were not shown in the translation editor

= 3.7.15 =
* Fixed a problem with BACS payment gateway strings not being translated in order confirmation page
* Fixed some compatibility issues with WooCommerce Tab Manager

= 3.7.14 =
* Fixed a problem introduced in the previous version: Mollie payment methods not working when using the 'Mollie Payments for WooCommerce' plugin

= 3.7.13 =
* Bug fix: When adding a global attribute inline while creating a product in a secondary language, the term was created in the wrong language
* Fixed a compatibility issues with WooCommerce Tab Manager: fatal error when trying to translate a product
* Fixed another compatibility issues with WooCommerce Tab Manager: when a product had only a global tab, the translated tab didn't show up on the translated product
* Improvements for how the gateways strings are registered for translation
* Updated logic for registering and translating Shipping zones and methods according to changes in WooCommerce 2.6

= 3.7.12 =
* Fixed a bug that made the shop pages return 404 errors on WordPress 4.5
* Fixed warnings caused by terms translated before the WooCommerce Multilingual activation
* Bug fix: WooCommerce Multilingual locales for secondary languages were not loaded correctly

= 3.7.11 =
* Bug fix: the downloadable products were not synced properly with their translations
* Bug fix: the confirmation for installing WooCommerce translations for the secondary languages was not saved
* Bug fix: The option to "Show only products with custom prices in secondary currencies" was not working well for variable products
* Bug fix: saving custom prices when creating a new product didn't work
* Removed backward compatibility with WooCommerce versions older than 2.1
* Small compatibility fixes for the upcoming WordPress 4.5
* Fixed a problem with the pagination on the products list page under the WooCommerce Multilingual section

= 3.7.10 =
* Fixed a small issue with the product translations editor (additional toolbar showing)
* Fixed a compatibility issue with Memcached on Siteground: product category archive pages were returning 404
* Bug fix: the price widget was not using the correct values with multi-currency mode on
* Bug fix: in some cases the costs for International Shipping were not calculated correctly in the secondary languages
* Bug fix: When using comma for a decimal separator, for custom prices, the rounded values were not determined correctly
* Bug fix: In some cases, translated product variations were displayed as 'out of stock' on the front end.
* Fixed a fatal error occurring when selecting the WPML admin language to 'All languages' on the WooCommerce settings page
* Compatibility with WooThemes Mix and Match Products
* Fixed a bug preventing a shipping to be set to a variation when the default language of the product was not English
* Fixed a bug that was sometimes preventing the 'incl. vat' suffix to be displayed on prices
* Fixed a compatibility issue with Gravity Forms Product Add-Ons

= 3.7.9 =
* Fixed an issue prevent the correct plugin activation in some cases
* Fixed an issue potentially causing  uncatched errors when using some specific payment gateways

= 3.7.8 =
* Updated the logic for downloading WooCommerce translations from translate.wordpress.org
* Compatibility with WooCommerce Bookings 1.9 (and fixed othe small compatibility issues with older versions)
* Fixed a compatibility issue with WooCommerce Subscriptions: the sign-up fee was not correct in the 2nd currency
* Fixed a compatibility issue with WooCommerce Subscriptions: a fatal error was triggered during the checkout process in some circumstances
* Fixed other compatibility issues with WooCommerce Subscriptions: endpoints, incorrect signup fee in secondary currency
* Fixed a compatibility issue with WooCommerce Payment Gateways: some strings were registered/changed when on checkout
* Bug fix: variations created with Any were not showing the user selected attribute when added to the cart
* Bug fix: it was not possible to changeor or set the "Set prices in other currencies manually" option for a duplicate product
* Fixed a compatibiilty issue with WooCommerce Bulk Stock Management (the 'out of stock' flag was not synced)
* Bug fix: private products were visible to all users on grouped products
* Bug fix: the tax label could register in the wrong language sometimes and then it was not possible to translate it correctly
* Bug fix: partial the subject and heading for the refund emails were not translated when sent to users who had placed orders in secondary languages

= 3.7.7 =
* Fixed an issue that was causing a fatal error for sites using the Flatsome theme
* Fixed an issue with translating standard tax rate name
* Fixed an issue with product categories widget
* Fixed issue with variable products in cart ( local attributes not translated after switching language )
* Added filter for _load_filters function in multi-currency class
* Set variations as translatable post type

= 3.7.6 =
* Fixed several problems with the permalinks when using the slash character in the bases
* Fixed an issue with coupons: the coupons were not applied according to the minimum amount of the cart in the current currency
* Fixed one compatibility issue with the Flatsome theme
* Fixed a bug preventing the shop page link to be translated correctly to the other languages (when using WPML 3.3.1+)

= 3.7.5 =
* Fixed a backward compatibility with WPML versions prior 3.2 (causing fatal error)
* Bug fix: in some specific cases variations were not created correctly - 'Any %name%' instead of term value
* Bug fix: updating a product for which attached media had been deleted caused a warning (WooCommerce issue: 9681)
* Fixed an issue with completing PayPal payments when using the default permalinks and the language as a parameter in the urls
* Fixed an issue with order notes in the WP admin: 'array' was displayed instead of the actual note

= 3.7.4 =
* Fixed a problem with the previous version that caused a fatal error when upgrading

= 3.7.3 =
* Added support for translating custom attributes (for variations) via the professional translation
* Added support for translating products tab information (WooCommerce Tab Manager) via the professional translation
* Added support for translating persons and resources (WooCommerce Bookings) via the professional translation
* Added support for translating products bundle data (WooCommerce Product Bundles) via the professional translation
* Added extended compatibility and support for professional translation for WooCommerce Composite Products
* Bug fix: it was not possible to set a product translation as draft when the original was published
* Bug fix: in some cases the product categories hierarchy (and count) was not sycned across translations
* Bug fix: the custom title and description of a bundle of a translated product was removed after updating the original product
* Bug fix: custom fields that did not have any translation preference were wrongfully copied across translations
* Bug fix: multi-currency was not working properly for product variations when the "Show only products with custom prices in secondary currencies" option was on
* Fixed an important compatibility issue with Yoast SEO (fatal error when using Yoast SEO 3.0+)
* Bug fix: wcml_check_on_duplicate_products_in_cart was incorrectly duplicated specific items in the cart

= 3.7.2 =
* Added synchronization for the 'featured' flag (star) for products across translations
* Fixed one compatibility problem with WooCommerce Bookings: bookings were not filtered by language on the front end
* Fixed one compatibility problem with WooCommerce Composite Products (causing a fatal error when viewing a composite product)
* Bug fix: in some cases the cart total in a secondary currency was wrongfully rounded instead of showing the decimals
* Bug fix: translated products were not published on the same schedule when using the future publishing
* Bug fix: in some situations variations could not be created for a variable product with global attributes that contained special characters
* Bug fix: wrong currency was used in an order when the currency was changed while placing the order and checkingout with Paypal
* Bug fix: the relationship of a duplicate product with the original was lost when the original was updated.

= 3.7.1 =
* Compatibility fixes for WooCommerce Bookings and WooCommerce Composite Products
* Fixed a typo in a function that caused a fatal error

= 3.7 =
* Added support for strings in different languages. Translated strings are not required to be in English (Requires WPML 3.3+)
* Fixed a compatibility issue with WooCommerce Bookings: bookings in all languages showing on calendar (requires WooCommerce Bookings 1.8+)
* Fixed a compatibility issue with WooCommerce Bookings: deleting a reservation did not delete translations too (requires WooCommerce Bookings 1.8+)
* Fixed a PayPal checkout issue when multi-currency was enabled and the decimal separator was set to comma and thousands separator was set to dot
* Fixed a compatibility issue with WooCommerce Product Addons: adding a second item for the same product added the first product again too
* Bug fix: a slash character was missing the in product breadcrumb when the translated page slug was identical to the one in the default language
* Bug fix: incorrect cost for the flat rate shipping was displayed in certain circumstances
* Improved compatibility with Gravity Forms Product Addons for translating the cart data
* Removed a deprecated hook used for the compatibility with WooCommerce Subscriptions
* Bug fix: cart_widget.js code was loaded in places that it wasn't needed
* Bug fix: in a specific case, the price in a secondary currency was not displayed correctly (the amount in the original currency was displayed)
* Bug fix: content was disappearing when switching between the visual and text editors on the product translation editor
* Bug fix: when using attributes that were numeric values a catchable fatal error was triggered

= 3.6.11 =
* Fixed one issue that was causing a fatal error when an older version WPML was used (3.1.9.7)

= 3.6.10 =
* Bug fix: Custom prices for variations were not saved when clicking the 'Save changes' button
* Bug fix: Pagination was not working for a category having the term id equal to the id of the account page
* Bug fix: 'Shop' was appearing two times in the breadcrumbs when using the shop base + category for a product url base
* Bug fix: Fixed one issue with WooCommerce Bookings - adding two separate bookings to the cart showed as one item instead of two
* Bug fix: The products menu order was not synced in some situations
* Fixed a compatibility issue with the Peddlar theme
* Fixed a styling issue on the custom prices section for product variations
* Updates for the compatibility with WooCommerce Product Bundles from the plugin author
* Bug fix: Sometimes it was not possible to enable the slug translation for a custom post when WooCommerce Multilingual was active

= 3.6.9 =
* Bug fix: Prices for variable products were not converted correctly when using multiple currencies after the WooCommerce 2.4 update
* Bug fix: Variations translations were not created when using custom attributes with space characters in them
* Bug fix: When the option to show only products with custom prices in the secondary currencies was on, no products were displayed
* Changed the order in which the products are displayed on the WooCommerce Multilingual products editor: chronological DESC

= 3.6.8 =
* Added a series of compatibility fixes for WooCommerce 2.4.x (custom attributes, endpoints)
* Bug fix: Incorrect prices were calculated for Table Rate Shipping (bug originally fixed in version 3.6.5)
* Bug fix: WooCommerce Bookings - when you deleted a booking from the backend, the calendar on the front end did not update
* Bug fix: WooCommerce Bookings - translations of a booking post were not deleted when the original post was deleted
* Bug fix: WooCommerce Bookings - booking product appeared multiple times in the cart in some cases
* Bug fix: WooCommerce Bookings - when a booking product was created from the backend, multiple posts were created in some cases
* Bug fix: 'Stock Qty' field was not locked in the translated variations section

= 3.6.7 =
* Bug fix: Converted prices in secondary currencies were incorrect in some situations. e.g. For VND with an VND:EUR exchange rate of 30,000:1
* Bug fix: Wrong urls were displayed in the  language switcher for product category or product tag urls

= 3.6.6 =
* Fixed a bug that was causing a PHP warning when using a WPML version prior 3.2

= 3.6.5 =
* Enabled the WooCommerce Bookings compatibility support
* Bug fix: Fixed a bug that caused a wrong price to be displayed when adding a product in the cart from two different languages
* Bug fix: After a product translation was edited in the standard product editor, the WooCommerce custom attribute translations were lost
* Bug fix: The product variations failed to sync when the term_id was different than the term_taxonomy_id for the terms used to create the variations
* Bug fix: Some product translations were showing non existing discounted prices
* Fixed a couple of compatibility issues with WooCommerce Product Bundles (e.g. with using the Flatsome theme) 
* Fixed a small usability issue related to Sensei
* Bug fix: Stock quantity not synchronized when items were used in orders created in the backend
* Bug fix: Payment gateways strings were not registered for string translation
* Bug fix: Global Attributes were not translated in the WooCommerce Mail
* Bug fix: In some cases the WooCommerce endpoints were not translated correctly
* Bug fix: An extra 'a' tag was added to products in the mini-cart
* Bug fix: A 404 error was returned on the translated product category archive page
* Bug fix: Some shipping methods were displayed incorrectly on the cart page when using Table Rate Shipping
* Bug fix: In some cases prices showing the Paypal order summary included decimals even if the prices were supposed to be rounded to integers
* Bug fix: When adding different variations of a product, a single variation was added more times
* Bug fix: Urls in the secondary languages were not working properly when using the deafault translations (from teh mo files) instead of translating tehm with string translation
* Bug fix: In some cases some email notification strings were not registered
* Fixed a compatibility problem with Dynamic Pricing: in a specific context, based on a price rule, the end price was multiplied by a factor with each page reload

= 3.6.4 =
* Bug fix: Parse error: syntax error, unexpected T_PAAMAYIM_NEKUDOTAYIM (introduced in 3.6.1)
* Bug fix: In some conditions it was not possible to load product pages in other languages than the default.
* Bug fix: Fixed some compatibility issues with Product Bundles

= 3.6.3 =
* Fixed a bug causing a PHP warning when using an older version of WPML String Translation

= 3.6.2 =
* Bug fix: A product could appear multiple times in the cart when added in different languages
* Bug fix: Product attribute labels translations were not showing on the frontend in some circumstances
* Bug fix: Attributes labels translations not showing on the 'Add product' admin panel
* Bug fix: The flags for custom languages were not showing correctly on the products editor screen
* Bug fix: The currency switcher was missing from the WooCommerce Status dashboard widget (in version 3.6.1)
* Bug fix: The auto-adjust ids functionality from WPML was not working with wc_get_product_terms
* Bug fix: The 'shop' link was stripped out of the breadcrumb in the Woocommerce product page.
* Bug fix: The product category template was not working correctly in secondary languages
* Bug fix: Fixed a problem with sanitize_title for variations in Danish and German
* Moved the Tab manager settings to separate file from the WCML config
* Duplicates for media are now being created, if missing, when product translations are created. 
* Bug fix: Fixed a fatal error that was occurring when the WPML was not updated to version 3.2 while the WPML addons were updated to the latest versions.

= 3.6.1 =
* Updated the taxonomy translation synchronization to be compatible with WPML 3.2
* Bug fix: the notice that shows up on the general settings page when the default language is not English did not hide when it was dismissed.
* Bug fix: after adding a new currency and reloading the page, the new currency was gone. Also the exchange rate was wrong after re-adding teh currency.
* Bug fix: an incorrect currency was being passed to the payment gateway when paying for an order created in the backend.
* Bug fix: the prices in the custom currencies were not saved when a product was published.
* Bug fix: extra backslashes were added when translating custom attribute name in products.
* Bug fix: custom product categories template was not working as expected
* Bug fix: updating WordPress language packs was not working when using custom locale codes in WPML
* Bug fix: Variable products returned error in secondary language "This product is currently out of stock and unavailable."
* Bug fix: The publishing date was not updating on translation when changed on the product in the original language
* Bug fix: Attributes with the value "0" value were not displayed on the front end
* Bug fix: Modified Free shipping label could not be translated
* Bug fix: When editing product translations it was possible to save an empty slug.

= 3.6 =
* Added the ability to edit the slugs of the translated products in the products editor
* Added the option to show only products with custom prices on the front end
* Performance improvements: fewer db queries, caching. Up to 40% faster on large sites.
* Support for the 'lang' parameter in WooCommerce REST API calls
* Option to hide the default currency selector on the product page
* Bug fix: Fixed a design issue on the 'connect with translation' pop-up on products.
* Bug fix: Accessing the source content in the WooCommerce Multilingual product translation content editor was not possible sometimes.
* Bug fix: 'Invisible' products were showing as links in the cart instead of just names.
* Bug fix: The cart_widget.js code was always loaded.
* Bug fix: Screen Options & Check All not working on WooCommerce Orders page
* Bug fix: Sometimes the IPN Url sent to Paypal was wrong causing a 404 error after the payment was complete
* Bug fix: Translated endpoint pages were sometimes returning 404
* Bug fix: When using a default language different than English, the product permalink base was not in English.

= 3.5.5 =
* Bug fixed: Custom attributes were disappearing after updating a product in the WooCommerce native product editor
* Tested compatibility with WordPress 4.2
* Security review and fixes
* Made the key "woocommerce_cancelled_order_settings" translatable
* Email heading and subject sent after placing an order were not translated when using ‘Complete’ button on orders page
* Bug fixed: warning about minimum order requirement always showing in some conditions when using a child theme

= 3.5.4 =
* Bug fixed: Can't access source content in WCML product translation table
* Bug fixed: Custom Post Types leads to 404 error

= 3.5.3 =
* Bug fixed: Redirection issues with "Your latest posts" as a front page
* Bug fixed: Yoast fileds not saved in WooCommerce Multilingual products table
* Bug fixed: Translated endpoints returns page not found
* Bug fixed: Custom fields are locked in variation section

= 3.5.2 =
* Compatibility with WooCommerce 2.3.x
* Bug fixed: Redirection issues with "Shop" page as front page
* Bug fixed: Language column was missing from the products list page
* Bug fixed: Product tags disappeared after updating the product attribute 'size'
* Bug fixed: Featured image title and text were not editable in the WooCommerce Multilingual Translation Table
* Bug fixed: Only first three attributes were available for translation
* Bug fixed: The shipping fee was not converted correctly when using the multi-currency mode
* Bug fixed: The default currency configuration (decimal & thousand separator) was ignored when the multi-currency was active
* Bug fixed: Subsequent request to product preview page lead to a 404 page.
* Bug fixed: 'Insert link' button on the visual editor of the products translations screen was not working.
* Bug fixed: Fixed another compatibility problem with WooCommerce Product Tabs
* Bug fixed: A variable product was showing an incorrect price in the cart
* Bug fixed: The flat rate shipping was showing the wrong price on the checkout page in certain conditions

= 3.5.1 =
* Bug fixed: Performance issue with queries number

= 3.5 =
* Added support for creating products in secondary languages only.
* Added enhancements for the Woocommerce Multilingual products table (filter by original language, display language flag).
* Added option to synchronize the products and product taxonomies order.
* Bug fixed: The cart was not updating quantities for variable product (when have more than one variable in the cart).
* Bug fixed: The cart total was not updating when using get_cart_total() and get_cart_subtotal() functions in other plugins or themes.
* Bug fixed: Wrong price format and order total were displayed on the new order page in the WP admin
* Bug fixed: The featured image and the gallery images were overridden when updating translations
* Bug fixed: Fixed the 'Keep' option that allows keeping the same currency on teh front end, when switching the language.
* Bug fixed: Fixed a javascript error that was showing when changing currencies order
* Bug fixed: The decimal number was not working correctly for the default currency
* Bug fixed: Fixed a compatibility problem with WooCommerce Product Tabs
* Bug fixed: A coupon was applied incorrectly to all products in the cart when they were defined for specific product variations.
* Bug fixed: WooCommerce note email language was not correct
* Bug fixed: WooCommerce reports were showing duplicate products
* Bug fixed: When using WordPress in a folder, the checkout showed an 'expired session' error message.
* Added support currency argument in raw_price_filter

= 3.4.3 =
* Bug fixed: Incorrect decimal separator for prices on WordPress admin
* Bug fixed: ‘Insert link’ button not working on products translator interface.
* Bug fixed: Switching currency after adding to cart was adding an additional item
* Bug fixed: Review setting not preserved on translation of variable product
* Bug fixed: “Visible on the products page” option for product attributes was still selectable for product translation.
* Bug fixed: Translation status icon not updated on products translator page
* Bug fixed: Shipping rate was lost when WPML is activated
* Bug fixed: WooCommerce ‘sort by’ links going to blog not products
* Bug fixed: Option to select currency position was missing immediately after a new currency was added

= 3.4.2 =
* Accommodated taxonomy translation changes in WPML

= 3.4.1 =
* Bug fix: A variable product was somtimes breaking the shopping cart
* Fixes added for translating custom fields that are textareas

= 3.4 =
* Additional support for updating the WooCommerce translations.
* Added currency switcher for the WooCommerce status widget on the WordPress admin dashboard.
* Usability fixes for the translation of custom attributes in the WooCommerce native editor.
* Added validation for the sale amount when using custom prices with multi-currency.
* Bug fixed: Incorrect currency symbol position on edit order page.
* Bug fixed: Incorrect currency displayed for order when editing an order in the backend.
* Bug fixed: Coupon option 'Exclude sale items' was not being applied correctly. Sale items were not excluded.
* Bug fixed: Currency switcher widget was not showing under the available widgets list in the backend.
* Bug fixed: The breadcrumbs structure dropped the shop page when WooCommerce Multilingual was activated.
* Bug fixed: Manually adding a product to an order is not taking a custom price (secondary currency) if set.
* Bug fixed: Error when trying to add a category when “All languages” was selected in the admin language switcher.

= 3.3.4 =
* Fixed bug related to back-compatibility with WooCommerce versions < 2.2.*

= 3.3.3 =
* Compatibility with WooCommerce 2.2.x
* Auto-download WooCommerce translations for active and new languages
* Page titles translations for WooCommerce pages taken from WooCommerce Multilingual .mo files
* Product base, product category slug, product tag slug and product attribute bases will always have to be translated via String Translation (not using WooCommerce translations from the mo files)
* Added warning message on settings page when product base not translated to all languages
* Fixed: Base currency format ignored after adding additional currency
* Fixed: Shipping class names were displayed wrong on the WooCommerce settings page when switching the admin language
* Fixed: WooCommerce pages were not working correctly after changing the default language
* Fixed: WooCommerce native interface doesn't copy the variations prices

= 3.3.2 =
* Fixed: 'Language warning' appears when editing product translations using the native WooCommerce editor
* Fixed: Variation cannot be added to an existing order
* Fixed: Media Attachment controls for products missing
* Prevented disabling of option to use slugs in different language for products
* Fixed: Slashes not stripped correctly in product translation editor
* Fixed: 'Copy content' button not working on product translations
* Disable admin language switcher on the Product => Attributes screen
* Allow 'woocommerce_price_display_suffix' to be translated with String Translation
* Allow 'woocommerce_email_from_name' and 'woocommerce_email_from_address' to be translated with String Translation
* Fixed: Menu order is not synced when using "drag and drop" in Products => Sort Products
* Fixed: One WooCommerce attribute field won't translate
* Fixed: Variations not showing in the correct language in some circumstances
* Optimizations for the WooCommerce Multilingual products admin page - faster when a large number of products exist
* Duplicate translations too when duplicating a WooCommerce product
* Fixed: WC Price Filter showing the wrong currency
* Ability to use any currency when creating an order in the backend.

= 3.3.1 =
* Some strings were showing in the wrong language on the cart and checkout page.
* Product category urls - in some cases the product category urls didn�t work on sites with the default language different than English.
* Products gallery images synchronization - sometimes, when synchronizing products "gallery images" and categories, the result was not  updated correctly on the Troubleshooting page
* Fixed issues related to WooCOmmerce Dynamic Pricing
* Supoprt for translating WooCommerce 2.1+ endpoints
* 'Continue Shopping' button pointing to the wrong url
* Problem with short links
* Fixed some issues with Table Rate Shipping

= 3.3 =
* Performance improvements: optimized database queries
* Support rounding rules for converted prices
* More advanced GUI for Multi-currency options
* GUI for currency switchers (including widget)
* Added option to synchronize product category display type & thumbnail
* Performance improvement for WCML_Terms::translate_category_base (avoid switching locales)
* Send admin notifications to admin default language
* Dependencies update: WooCommerce Multilingual requires WPML 3.1.5
* Set language information for existing products when installing WCML the first time.
* Do not allow disabling all currencies for a language
* Removed �clean up test content� and �send to translation� dropdown on products editor page
* Message about overwritten settings in wpml-config made more explicit
* Lock �Default variation� select field in product translations
* After change shipping method on cart page we will see not translated strings
* Fixed bug related to shipping cost calculation in multi-currency mode
* With php magic quotes on, products translations with quotes have backslashes
* Bug related to translation of grouped products � simple product not showing up on front end
* Stock actions on the order page don�t work correct with translated products
* For Orders save attributes in default language and display them on order page in admin language
* Attribute Label appearing untranslated in backend order
* Memory issues on the Products tab when we have a large number of products
* �product-category� not translated in the default language.
* �WCML_Products� does not have a method �translated_cart_item_name�
* Order completed emails sent in default currency
* Language suffix (e.g. @en) not hidden for product attributes on the front end
* Quick edit functionality issues fixed
* Fixed �Call to undefined method WC_Session_Handler::get()�
* Fatal error when updating the order status to �complete�
* Currency is not converted when you switch language until you refresh the page.
* �Super Admin� not able to see the WCML menu
* Checkout validation errors in default language instead of user language
* Fixes for compatibility with Tab manager: Can�t translate �Additional Information� tab title
* Bug: SEO title & meta description changed to original
* Bug: 404 on �view my order� on secondary language using �language name added as a parameter�
* Bug: Permalink placeholders appear translated when using default language different than English
* Fixes for compatibility with Table Rate shipping: shipping classes not decoded correctly in multi-currency mode
* Bug: �show all products� link on WCML products page points to the wrong page � no products
* Bug fix: product page redirecting to homepage when the product post type slug was identical in different languages and �language added as a parameter� was set
* Bug fixes related to File paths functionality (WooComemrce 2.1.x)
* Bug: Product parents not synced between translations (grouped products)
* Bug: Grouped products title incomplete
* Bug: Db Error when saving translation of variable products with custom attributes
* Bug: WooCommerce translated product attributes with spaces not showing
* Bug: Deactivated currency still appears if you maintain the default currency for that language to �Keep�.
* Bug: Incorrect shipping value on translated page
* Bug: Reports for products including only products in the current language (WooCommerce 2.1.x)
* Bug: WooCommerce translated product attributes with spaces not showing
* Bug: Problems creating translations for shop pages when existing pages were trashed
* Bug fix: Fatal error when Multi-currency is not enabled and �Table Rate Shipping� plugin is active
* Fixed bug in compatibility with Tab Manager
* Bug fix: Cart strings falling to default language after updating chosen shipping method
* Bug fix: Reports not including selected product/category translations


= 3.2.1 =
* Fixed bug related to product category urls translaiton
* Fixed bug related to back-compatibility with WooCommerce 2.0.20

= 3.2 =
* Compatibility with upcoming WooCommerce 2.1
* Multi-currency support: configure currencies per languages
* Multi-currency support: custom prices for different currencies
* Support translation for the attribute base (permalinks)
* Bug: Emails not sent in the correct language when uses bulk action on orders list page
* Bug: Order notes email in wrong language in certain circumstances
* Bug: Shipping method names are being registered in the wrong language
* Bug: WooCommerce Multilingual menu doesn't display for translators 
* Bug: Using 'category' for products cat slug conflicts with posts 'category'
* Bug: Paypal rejects payments with decimals on certain currencies

= 3.1 =
* Support for multi-currency (independent of language) BETA
* Support for translating products via ICanLocalize (professional translation)
* Option to synchronize product translation dates
* Compatibility with Table Rate Shipping and other extensions
* Better handling for couponse
* Fixed bug: product attributes not saved on orders
* Fixed bug: Can't get to the cart & checkout pages if they are set as child pages
* Fixed bug: Style conflicts in Dashboard for Arabic
* Fixed various issues with notification emails
* Fixed bug: Variable products default selection is not copied to translations.
* Fixed bug: Product Table is not showing Product Draft count

= 3.0.1 =
* Replaced deprecated jQuery function live()
* Fixed bug: language names not localized on products editor page
* Fixed bug: Can't set "Custom post type" to translate
* Fixed bug: Translation fields not visible - In certain circumstances (e.g. search) the translation fields corresponding to the translated languages were missing
* Fixed alignment for �Update/Save� button in the products translation editor
* Fixed bug: Default selection not copied to duplicate products
* Fixed bug: Price doesn't change when change language on the cart page when set "I will manage the pricing in each currency myself"
* Resolved one compatibility issue with Woosidebars
* Direct translators to the products translation editor automatically (instead of the standard post translation editor)
* Fixed bug: In some situations (different child categories with the same name) the wrong categories were set to a duplicated product.
* Enhancement: Add icons for products in the products translation editor
* Register WooCommerce strings (defined as admin texts in the wpml config file) automatically on plugin activation
* WPML (+addons) - new versions required.
* lcfirst is only available since php 5.3
* Identify fields on known plugins and show their human name in our product translation table (support for WordPress SEO for now)

= 3.0 =
* Brand new GUI and workflow
* Support for easy taxonomy translation 
* Bariations synchronization
* Product images synchronization


= 2.3.3 =
* Fix logout link not working in secondary language
* Fix accepting orders in backend leading to 404
* Set email headings & subjects as translatable
* Set order language when sending order emails from admin
* Sync product tags the same way as categories
* Fix bug in ajax product search filter
* Support for WooCommerce Brands extension (http://www.woothemes.com/products/brands/)
* Initial support for Translation Editor
* Fix bug with cart currency updates and variations
* Fix language in new customer note notifications

= 2.3.2 =
* Sync also default options for custom attributes.
* Global resync (done only once) of the orderings of product attribute values and categories across all languages.
* Fixed a bug and a corner case in variation synchronization.

= 2.3.1 =
* Fixed incompatibility with PHP 5.2

= 2.3 =
* Refactor translation and currency conversion of products & variations in cart
* A problem we had with shipping selection was resolved in WooCommerce itself
* Improved synchronization of global product attributes, whether used for variations or not
* Custom product attributes registered as strings when defined in the backend
* Don't adjust the currency symbol in WooCommerce settings page
* Term and product category order is synchronized among languages
* Additional filters for WooCommerce emails
* Fixed layered nav widgets in translated shop page
* Synchronize Product Categories

= 2.2 =
* Price in mini-cart refreshed when changing language
* Fix bug in multilingual currency setting that slipped in 2.1

= 2.1 =
* Add admin notices for required plugins
* Add support for 'Review Order' and 'Lost Password' pages
* Fix rounding issues in currency conversion
* Variations: pick translated terms using 'trid' gives better results
* Variations: sync to all languages when there are more than 2 languages
* Improvement: load JS/CSS only when needed

= 2.0 =
* Fix variation sync to more than one language
* Fix custom field sync for new variations
* Fix rounding of amounts in PayPal
* Adjust product stock sync to WC 2.x
* Add automatic id translation of logout page
* Adjust permalink warnings to WC 2.x
* Clean up code

= 1.5 =
* Fixed manually setting prices in translated products.
* Take advantage of WPML's new slug translation feature.
* Added the possibility of translating custom attributes.
* Improvements to product variation synchronization.
* Fixed product stock sync for variable products .
* Fix and improve checks made to incompatible permalink configurations.
* Fix tax label translation when there is more than one of them.
* Send order notifications in the language the order was made.
* Removed several warnings and updated deprecated code.
* Cleanup language configuration file and add missing strings.

= 1.4 =
* Allow translating the 'Terms & Conditions' page.
* Register shipping methods strings for translation.
* Register several tax-related strings for translation.
* Fix registration of payment gateway titles and descriptions.
* Synchronize the default attribute of a variable product across its translations.
* Allow saving WooCommerce/Settings while using a non-default language.
* Fix problems when the shop page is at the home page.
* Allow using Wordpress default permalink structure aswell.
* Fix amount sent to payment gateway when using multiple currencies.
* Fix for language switcher in shop pages (fixed in WPML)
* Fix for subscriptions module price not showing (fixed in WPML)
* Rewrite product variation sync: each variation is related to its translations, sync becomes easier
* Remove several PHP warnings and notices.
* Send order status update emails in the language the order was made.

= 1.3 =
* Fixed all custom fields synchronization between translations
* Fixed the stock issue for translations
* Fixed the price filter widget for multiple currencies feature
* Fixed product duplication to a second language 
* Payment gateways texts now are translatable
* Custom variables translations now will be shown in the correct language

= 1.2 =
* Added helpful documentation buttons
* Added makes new attributes translatable automatically
* Added payment gateways translations
* Fixed order statuses disappeared in the orders page
* Fixed attributes translations in duplicated variations
* Fixed PHP warning when adding variations is in question

= 1.1 =
* Added multi-currency feature
* Fixed synchronization of attributes and variations 
* Fixed translation of attributes
* Fixed JS error in the checkout page
* Fixed enable guest checkout (no account required) issue
* Fixed Up-sells/Cross-sells search (showed all translated products)
* Fixed 'Show post translation link' repeating issue

= 1.0 =
* Fixed 'Return to store' URL
* Fixed language selector for the translated shop base pages
* Fixed the product remove URL in the translated language
* Fixed the checkout URL in the translated language
* Fix to prevent incorrect product URL in the shop base page when the permalink is not 'shop'

= 0.9 =
* First release

== Upgrade Notice ==

= 2.0 =
More variation fixes and compatibility with WooCommerce 2.x

= 1.5 =
Variation translation works a lot better now. This version runs best with WooCommerce 1.6.6.

= 1.4 =
This version runs with WooCommerce 1.6.5.x and 1.7.x. Recommeded WPML version is 2.6.2 and above.

= 1.3 =
Fixed compatibility between WooCommerce 1.5.8 and WPML 2.5.2

= 1.2 =
Added a few improvements and fixed bugs.

= 1.1 =
Fixed a few bugs. Added multi-currency mode.

= 1.0 =
Recommended update! Fixed a few bugs;

= 0.9 =
* First release