=== WooCommerce Multilingual - run WooCommerce with WPML ===
Contributors: AmirHelzer, sergey.r, mihaimihai, EduardMaghakyan, andrewp-2, kaggdesign
Donate link: http://wpml.org/documentation/related-projects/woocommerce-multilingual/
Tags: CMS, woocommerce, commerce, ecommerce, e-commerce, products, WPML, multilingual, e-shop, shop
License: GPLv2
Requires at least: 4.7
Tested up to: 5.3
Stable tag: 4.7.9
Requires PHP: 5.6

Allows running fully multilingual e-commerce sites using WooCommerce and WPML.

== Description ==

This 'glue' plugin makes it possible to run fully multilingual e-commerce sites using [WooCommerce](https://wordpress.org/plugins/woocommerce/) and [WPML](http://wpml.org).

= Key Features =

* Translate all WooCommerce products (simple, variable, grouped, external)
* Easy translation management for products, categories and attributes
* Keeps the same language through the checkout process
* Sends emails to clients and admins in their language
* Allows inventory tracking without breaking products into languages
* Enables running a single WooCommerce store with multiple currencies

= Compatibility with WooCommerce Extensions =

Almost every WooCommerce store uses some extensions. WooCommerce Multilingual is fully compatible with popular extensions, including:

* [WooCommerce Bookings](https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-bookings-woocommerce-multilingual/)
* [WooCommerce Table Rate Shipping](https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-table-rate-shipping-woocommerce-multilingual/)
* [WooCommerce Subscriptions](https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-subscriptions-woocommerce-multilingual/)
* [WooCommerce Product Add-ons](https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-product-add-ons-woocommerce-multilingual/)
* [WooCommerce Tab Manager](https://wpml.org/documentation/woocommerce-extensions-compatibility/translating-woocommerce-tab-manager-woocommerce-multilingual/)

Looking for other extensions that are tested and compatible with WPML? See the complete [list of WooCommerce extensions that are compatible with WPML](https://wpml.org/documentation/woocommerce-extensions-compatibility/).

= Usage Instructions =

For step by step instructions on setting up a multilingual shop, please go to [WooCommerce Multilingual Manual](http://wpml.org/documentation/related-projects/woocommerce-multilingual/) page.

After installing, follow the steps of the *setup wizard* to translate the store pages, configure what attributes should be translated, enable the multi-currency mode and other settings.

Then, continue to the 'Products' and any categories, tags and attributes that you use.

When you need help, go to [WooCommerce Multilingual support forum](http://wpml.org/forums/topic-tag/woocommerce/).

= Downloads =

This version of WooCommerce Multilingual works with WooCommerce > 3.3.0

You will also need [WPML](http://wpml.org), together with the String Translation and the Translation Management modules, which are part of the [Multilingual CMS](http://wpml.org/purchase/) package.

= Minimum versions for WPML and modules =

WooCommerce Multilingual checks that the required components are active and up to date.

If the checks fail, WooCommerce Multilingual will not be able to run.

== Installation ==

= Minimum Requirements =

* WordPress 4.7 or later
* PHP version 5.6 or later
* MySQL version 5.6 or later

* WooCommerce 3.3.0 or later
* WPML Multilingual CMS 4.3.5 or later
* WPML String Translation 3.0.5 or later
* WPML Translation Management 2.9.1 or later

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

= Can I have different urls for the store in the different languages? =

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

= 4.7.8 =
* Make `Additional content` field translatable for Emails.
* Fixed stock synchronization issue for some extra plugins.
* Fixed cart item not deleted from cart page in some cases.
* Fixed Average Rating Widget Filter in all languages.
* Fixed a fatal error when applying a translation job on a product with tabs on PHP 7.1+.
* Fixed admin order note language after order status change.
* Fixed not showing products when shop page is a child page of the front/home page.
* Fixed display glitch of displaying current currency while adding new one.
* Fixed compatibility plugins additional content appears not translated when using ATE.
* Fixed inability to edit 'before discount' field on edit order page.
* Fixed products in all languages displayed on new booking admin page.
* Fixed language icon not updated in real-time when using Advanced Translation Editor.
* Fixed warning message displayed at the wrong moment.
* Fixed wrong language of custom attributes on cart page with display as translated mode enabled for products.
* Fixed multiple ajax calls on the front page if few tabs opened in different languages for non-logged users.
* Fixed Subscriptions early renewal price if not subscription price selected in the shop.
* Fixed Top Rated product widget displaying wrong products on the second language.
* Fixed Variable subscription "From" from price display auto converted price instead of custom one.
* Fixed the dynamic WooCommerce blocks which were not converted in the current language.
* Fixed product in wrong language selected on new order admin page.
* WP Super Cache enable cache for switching currency.
* Lock attributes select on second language native edit screen.
* Fixed price not shown issue with WooCommerce Bookings.
* Removed limitation of decimals in multi-currency settings.

= 4.7.0 =
* Replaced some Twig templates with pure PHP templates as the first step towards the removal of Twig dependencies.
* added comp. class to cover price update when products are edited with WOOBE plugin
* Added compatibility class for WooCommerce order status Manager  plugin
* Fixed an issue where the strings for the default payment methods were not properly translated on the Checkout page.
* Fixed an issue with the cache flush during language switching.
* Fixed in the original ticket.
* Fixed an issue where the gateway strings would always register in English instead of the site's default language.
* Fixed languages column width on products table.
* Fixed PHP Notice for WC Variations Swatches And Photos compatibility.
* WooCommerce Bookings compatibility : Fixed notice when trying to cancel booking.
* Fixed an issue where the total price on the Composite product page was not rounded.
* Fixed an issue causing wrong rewrite rules after saving the settings and visiting a page in a language other than the default.
* Fixed an issue with incorrect price converting for the Product add-ons.
* Fixed an issue with the WooCommerce Subscriptions availability in the secondary language after purchasing the subscription in the original language.
* Fixed an issue with the currency reverting to the default one during checkout.
* Fixed removed meta from original product not synchronized to translation.
* Fixed an issue where the BACS gateway instructions were not translated when re-sending the customer notification email from the admin.
* Fixed an issue with missing language information for attribute terms that happened after changing the attribute slug.
* Removed the Twig Composer dependency as it now relies on Twig from the WPML core plugin.
* Fixed an issue where customers would not receive notifications in the correct language.
* Fixed an issue where the Products shortcode was not working in the secondary language.
* Fixed error while sending WooCoomerce Bookings email for bookings which didn't have orders assigned.
* Added compatibility for free version of YIKES Custom Product Tabs.
* Updated compatibility class for WC Checkout Addons
* Fixed the images that were wrongly inserted in the translation job when attachments are not translatable.
* Significantly improved the site performance on when updating the page, post, or a WooCommerce product page in the admin.
* Added the "wp_" prefix to all cookies so that hosting and caching layers can properly handle them.
* Fixed a JavaScript error on the Store URLs tab.
* Fixed an issue where the "Fix translated variations relationships" troubleshooting option was removing translated variations.
* Fixed an issue where product names were not translated in the admin emails.
* Fixed an issue with the price filter widget not showing results in a secondary language.
* Fixed an issue where the shipping classes in secondary languages were not calculated during checkout.
* Display larger images when hovering thumbnails in the WooCommerce Multilingual Products admin page.
* Added the "wcml_new_order_admin_email_language" filter to allow setting the language of emails sent to admins for new or updated orders.

= 4.6.0 =
* Fix wrong currency code after removing item from manually created order
* Replace *_woocommerce_term_meta functions on *_term_meta
* Fix gallery images not showing up on translated product page
* Fix double calculating order item price while manually adding it from admin to order with WooCommerce 3.6.0
* Fix performance issues on checkout with manage stock products
* Fix performance issue on shop page with WooCommerce 3.6
* Fix loading scripts on admin pages
* Fix coupon discount when editing order from admin
* Fix wrong product price after adding another product to existing order from admin
* Fix my-account page endpoints in secondary language with pages set to "Display as translated"

= 4.5.0 =
* Add "get_post_metadata" hook to filter Woocommerce product data
* Added function in troubleshooting page to fix broken variations
* Fixed DB error when saving a variation with specific steps
* Fix refreshing of status icon when ATE Job of updated content is synced
* Fix few notices when removing a Elementor widget and refresh page
* Fetch ATE translations from WCML Product Translation Tab
* Fix warning when adding comment to product
* Fixed wrong price calculation when adding product to new order on backend
* Fixed bookings counter on admin bookings listing page
* Fixed stock quantity not synchronized to translation when creating it
* Fixed notice when saving translation
* Fixed translated attributes via ATE/Translation service not connected to translated product
* Fix not translated "On Hold" email subject after returning order from "Processing"
* Remove unneded $_SESSION variables on checkout page
* Fix PHP notice `Notice: Only variables should be passed by reference`
* Implemented dependency check for minimum compatible versions of required WPML plugins
* Fixed default variation not pre-selected on front-end for translated product with non latin attribute in default language
* Fix cannot change currency with "wcml_client_currency" filter
* Fixed not valid API key when trying manually update exchange rates
* WooCommerce Variation Swatches and Photos compatibility to translate attributes
* Fix related product displays in all languages
* Added compatibility with Yikes Custom Product Tabs

= 4.4.0 =
* Added the ability to associate BACS accounts with currencies
* Hide reviews in other languages link, if there are no reviews in product
* Update WCML Logo
* Removed Product Type Column from WCML backend and added compatibility with the WC Product Type Column plugin
* Fix low_stock_amount not synchronized to translations
* Fix custom attribute with number in name not appears to translation in Translation editor
* Fix not applied price rule for WooCommerce Table Rate Shipping in second currency
* Fix translated custom field wrongly saved to translation if contains array of strings
* Endless loop when using troubleshooting action to duplicate terms
* Fixed an issue with Elementor PRO products block showing all categories in the translated page.
* Fixed Xliff doesn't contains variation descriptions for WooCommerce Subscriptions
* Fixed compatibility issue with Flatsome theme
* Fix issue with custom product attribute title when trying to upload translation with XLIFF file
* Fixed cart validate for specific situations
* Added filter for translated package rates
* Added WPML switcher buttons library for Multi Currency in backend
* Fix loading Jquery to any place in code and in header
* Added fix for variation product "become" out-of-stock when translating using native screen
* Removed backward compatibility filters for terms synchronization
* Fixed attribute slug language always set to English
* Wrong path in Bookings compatibility class
* Fixed a fatal error occuring with older versions of WooCommerce (3.3.5)
* Fixed confirming order as complete from the order edit screen, does not decrement the second language stock qty
* Product category data always synchronizes on save of the translation and does not respect WPML option to sync taxonomies
* Fixed call to undefined method WPML_URL_Filters::remove_global_hooks with WPML < 3.6.0
* Fixed compatibility class name for wc product addons
* Fixed manual order creation does not respect manual prices
* Fix email language for the order as complete emails
* Fixed Composite Products compatibility - Price not rounding to the nearest integer
* Fixed missing custom attribute in XLIFF file / Pro Translation
* Fix Endpoint error to prevent 404 in some cases
* Fixed accepted arguments for terms_clause
* Resolved an exception causing an error message in the cart in some setups
* Fixed missed synchronization of 'outofstock' visibility term between product translations
* Fix broken logic with Table Rate Shipping when product uses class with "break and abort" rule
* Custom attributes terms not copied to diplicated translation after update values in original
* Added support for wpml endpoints
* WP Fastest Cache compatibility - fixed currency switcher problem
* Added ability to set custom prices for secondary currencies in WC Product Add-Ons
* Update minimum requirements
* Added ability to add custom payment methods for each currency

= 4.3.0 =
* Added ability to filtering comments by language
* Use display-as-translated for product images and product galleries
* Fixed issue when deleting a currency in Safari
* Fixed issue causing fatal error when activating WCML and WPML String Translation
* Changes in the Fixer.io API
* Added a fix where in some situation the product slug URL is not translated correctly
* Variable product removed from cart when switching language on the cart page
* Multicurrency in defaults not calculated correctly when creating manual order
* Product Bundles - search products returned wrong values
* Translating custom product category base leads to products returning error 404 when both bases contains the same string
* Table Rate Shipping - products with different classes produce no shipping method on cart page
* New order admin email subject and heading were overwrites with wrong data
* Fix small issue in product stock sync
* Refund and restock - not working properly when refunding the variation in second language
* WooCommerce Product Bundles -> original overwrites translation (visible when using title/description override)
