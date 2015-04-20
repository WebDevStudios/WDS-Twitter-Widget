WDS Twitter Widget
======

A widget that lets you show a user's latest tweets. Built with developers in mind and has many filters throughout.

There is a function for disabling the Twitter App credential fields in the widget. This is handy if you don't want `consumer_key`, `consumer_secret`, `access_token`, and `access_token_secret` visibile to users of the WordPress dashboard, and allows you to keep it in version control.

To disable:
```php
wds_twwi_disable_widget_app_settings( array(
	'consumer_key'        => 'YOUR CONSUMER KEY',
	'consumer_secret'     => 'YOUR CONSUMER SECRET',
	'access_token'        => 'YOUR ACCESS TOKEN',
	'access_token_secret' => 'YOUR ACCESS TOKEN SECRET',
) );

// Also works if you want to secure your keys through obscurity
// wds_twwi_disable_widget_app_settings( '0=CONSUMER_KEY&1=CONSUMER_SECRET&2=ACCESS_TOKEN&3=ACCESS_TOKEN_SECRET' );

```

If there's an error with your app credentials, the error will be displayed in the WordPress dashboard.

Example usage if wanting to disable widget:
[https://gist.github.com/jtsternberg/ace26c317c4d4a070abd](https://gist.github.com/jtsternberg/ace26c317c4d4a070abd)

### Installation

1. Recursively clone this repo (`git clone --recursive https://github.com/WebDevStudios/WDS-Twitter-Widget.git`), or [download this zip](https://raw.githubusercontent.com/WebDevStudios/WDS-Twitter-Widget/master/wds-twitter-widget.zip).
1. Upload the entire `/wds-twitter-widget` directory to the `/wp-content/plugins/` directory.
2. Activate WDS Twitter Widget through the 'Plugins' menu in WordPress.

### Plugin Details

* Contributors:      [webdevstudios](github.com/webdevstudios), [jtsternberg](github.com/jtsternberg)
* Donate link:       [http://webdevstudios.com](http://webdevstudios.com)
* Tags:					Twitter, Twitter API, 1.1 API, widget, shortcode
* Requires at least: 3.5.0
* Tested up to:      4.2
* Stable tag:        0.1.3
* License:           GPLv2 or later
* License URI:       [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)

### Changelog

##### 0.1.3
* Update for xss vulnerability, https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage

##### 0.1.2
* New filter, `wds_twitter_fetch_tweets` for overriding the retrieval of tweets.

##### 0.1.1
* Refactor plugin so that methods for getting tweets are publicly accessible (so the widget can be bypassed)
* Additional filters for manipulating data
* Function to disable widget (if using methods programmatically)

##### 0.1.0
* First release
