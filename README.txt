=== Plugin Name ===
Contributors: randyjensen,randyhoyt
Tags: openphoto,media
Requires at least: 3.2
Tested up to: 3.5
Stable tag: 0.9.5%VERSION_NUMBER%

Insert photos from your OpenPhoto installation into your WordPress content through the media manager.

== Description ==

Insert photos from your OpenPhoto installation into your WordPress content through the media manager.

Learn more about [OpenPhoto](http://theopenphotoproject.org/ "OpenPhoto")

Find the project on [GitHub](https://github.com/openphoto/openphoto-wordpress "OpenPhoto Github")

== Installation ==

1. Install plugin like any other WordPress plugin
2. Browse to Settings > OpenPhoto.
3. Specify the web address of your OpenPhoto installation. Save the settings to authenticate with your OpenPhoto installation's API.
4. Now when you add an image through WordPress, you'll see a new tab called "OpenPhoto".

== Frequently Asked Questions ==

== Changelog ==

= 0.9.5 =
* Fix Insert Into Post button in WP 3.5

= 0.9.4.2 =
* Reverting parameter name to "generate" 

= 0.9.4.1 =
* Change values for File Name and File Type

= 0.9.4 =
* Including openphoto-php as a Git submodule
* Use title from OpenPhoto instead of filename
* Handle change in OpenPhoto API related to value of pathOriginal

= 0.9.3 =
* Force image sizes to be generated so image src will persist.
* Add option for image size referenced by the File URL button.     

= 0.9.2 =
* Minor fixes to address header redirects, the OAuth workflow, and non-unique ids

= 0.9.1 =
* Initial release