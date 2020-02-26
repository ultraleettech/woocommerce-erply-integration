=== Ultraleet Woocommerce Erply Integration ===
Contributors: ultraleet
Tags: woocommerce, erply, integration, e-commerce, ecommerce, POS
Requires at least: 4.9
Tested up to: 5.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enables integration between your Woocommerce shop and Erply POS account.

== Description ==

Use this plugin to import products from your Erply store to your Woocommerce site.

Automatic synchronization of products is supported via the WordPress Cron feature.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ultraleet-wc-erply-integration` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Erply Integration->Settings screen to configure the plugin
4. Fill in your Erply API credentials in the General tab to fully activate the plugin
5. Use the Scheduling tab to enable automatic synchronization
6. Enable active components in the Components tab
7. Configure component settings in other tabs after activation

== Synchronization ==

In most cases, it is advisable to configure the plugin to periodically synchronize your product list with your Erply account via the Scheduling tab in plugin settings. However, you can also manually trigger product synchronization by going to the Erply Integration > Synchronization page via admin menu and clicking "Synchronize now".

Note, that using this feature doesn't immediately trigger synchronization, but will instead add the synchronization tasks to background schedule.

In any case, all synchronization tasks are executed in the background via the Wordpress Cron system. By default, WP-Cron executes scheduled tasks when people are visiting your site. If, however, the traffic to your site is low, this can result in quite a bit of a delay in synchronization of your products, especially if you have a large catalog.

In such cases, it is advisable to hook WP-Cron into your system task scheduler. You can read how to do that [here](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/).

You can read more about WP-Cron [in the WordPress plugin handbook](https://developer.wordpress.org/plugins/cron/).

== Advanced Features ==

This plugin only synchronizes your product catalog in a single language. We are planning to release more advanced functionality, such as WPML support, product stock synchronization, price lists, orders/invoices, and more, as add-on plugins in the near future. This documentation will be updated as soon as those products become available.

Our website is currently under development. In the mean time, if you are interested in hearing more about the advanced features, or even would consider hiring us to develop custom solutions for your shop, you can e-mail us directly at the following address: ultraleet [at] gmail.com.

== Frequently Asked Questions ==

= I have discovered a bug in the plugin. What should I do? =

Please submit a report in [our Github repository](https://github.com/ultraleettech/woocommerce-erply-integration/issues).

= I have other questions. How can I contact you? =

Please email us directly at ultraleet [at] gmail.com and we will get back to you shortly.

== Changelog ==

= 1.0.0 (2020-02-26) =
* Initial release.
