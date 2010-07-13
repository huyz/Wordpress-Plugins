=== Sideblogging ===
Contributors: cedbv
Donate link: http://www.boverie.eu/
Tags: asides,facebook,twitter
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 0.2.1

Display asides in a widget. They can automatically be published to Twitter and Facebook. Require Wordpress 3 and PHP 5.

== Description ==

Manage and write aside posts (using the new custom post types feature).    
They are displayed in a sidebar widget and don't interfere with other posts.   
A dashboard widget is provided to allow fast aside blogging.

Require **Wordpress 3** and **PHP 5**.

Aside content must be write in post title.
If you write something in post content (like a video embed), a link to this content will be displayed after the aside.

Asides can be automatically posted on Twitter and/or Facebook.        
A Twitter app is preconfigured.           
For Facebook, you need to create your own application. Video tutorial included in contextual help on settings page.

When asides with additional content are published to Twitter a shortlink to the full content is added.          

Supported shortlink providers :

* Native (blogurl?p=post_ID)
* is.gd
* bit.ly (need api key)
* goo.gl
* tinyurl.com
* su.pr
* cli.gs
* twurl.nl
* fon.gs

== Installation ==

1. Upload `sideblogging` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Regenerate Wordpress permalink (why? See FAQ)
3. Configure the plugin's settings.

== Frequently Asked Questions ==

= What are the requirements ? =
* PHP 5
* Wordpress 3.0
* a widget compatible theme

= I have 404 error on aside's permalink =
Try to regenerate permalink in *Settings/Permalinks*.

= Publication to Facebook doesn't work =
Click on change Facebook account on settings page and try to Connect Facebook again.

== Screenshots ==

1. Settings
2. Dashboard widget
3. Menu
4. Public widget

== Changelog ==

= 0.2.1 =
* Minor change

= 0.2 =
* New option : comments in asides.
* Bugfix : Errors when WordPress address was not the same that blog address.

= 0.1.1 =
* Fix a problem that occurred in unexpected situations
* Plugin tested with PHP 5.2
* Fix a security issue in dashboard widget
* More check about compatibility

= 0.1 =
* First public version.

== Upgrade Notice ==

= 0.2 =
Comments in asides : If you want you can manually allow comments on previous asides (after activate the option).
On new aside that will be automatically.

= 0.1.1 =
Fix a random error and a security issue.

= 0.1 =
Is it possible to upgrade to the first version ?