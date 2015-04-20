<?php
/**
 * Plugin Name: WDS Twitter Widget
 * Plugin URI:  http://webdevstudios.com
 * Description: WordPress Twitter widget
 * Version:     0.1.3
 * Author:      WebDevStudios
 * Author URI:  http://webdevstudios.com
 * Donate link: http://webdevstudios.com
 * License:     GPLv2+
 * Text Domain: wds_twwi
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 WebDevStudios (email : contact@webdevstudios.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'WDS_TWWI_VERSION', '0.1.3' );
define( 'WDS_TWWI_URL',     plugin_dir_url( __FILE__ ) );
define( 'WDS_TWWI_PATH',    dirname( __FILE__ ) . '/' );

class WDS_Twitter {

	// A single instance of this class.
	private static $settings = null;
	// Default settings
	protected static $defaults = array(
		'title'                => '',
		'twitter_id'           => '',
		'twitter_num'          => 1,
		'twitter_duration'     => 60,
		'twitter_hide_replies' => 0,
		'show_time_stamp'      => 0,
		'follow_link_show'     => 0,
		'follow_link_text'     => '',
		'consumer_key'         => '',
		'consumer_secret'      => '',
		'access_token'         => '',
		'access_token_secret'  => '',
		// Conditionally apply filters based on context
		'context'              => 'widget',
	);
	// Generic Twitter API error
	public static $error;
	// Set to true to programatically set app creds (recommended)
	public static $hide_app_fields = false;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return WDS_Twitter A single instance of this class.
	 */
	public static function init() {
		if ( null == self::$settings ) {
			self::$settings = new self();
		}

		return self::$settings;
	}

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'hooks' ) );
		add_action( 'widgets_init', array( $this, 'widget' ) );
		add_shortcode( 'wds_tweets', array( $this, 'get_tweets_list' ) );
	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 */
	public function hooks() {
		// Generic Twitter API error
		self::$error = __( 'There was an error while attempting to contact the Twitter API. Please try again.', 'wds_twwi' );
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wds_twwi' );
		load_plugin_textdomain( 'wds_twwi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Registers widget
	 * @since  0.1.0
	 */
	public function widget() {
		// Filter to turn off widget registration
		if ( ! apply_filters( 'wds_twwi_do_widget', true ) ) {
			return;
		}
		// Include widget
		require_once( WDS_TWWI_PATH .'lib/latest-tweets-widget.php' );
		// Register widget
		register_widget( 'WDS_Latest_Tweets_Widget' );
		// easter egg
		if ( ! apply_filters( 'wds_twwi_alt_widget_style', false ) ) {
			return;
		}
		// Modify styling of widget
		require_once( WDS_TWWI_PATH .'lib/alt-widget-style/WDS_Twitter_Widget_Mod.php' );
	}

	/**
	 * Gets and displays an html list of formatted tweets
	 * @since  0.1.1
	 * @param  array $settings Settings for grabbing tweets
	 */
	public static function tweets_list( $settings ) {
		echo self::get_tweets_list( $settings );
	}

	/**
	 * Shortcode that returns an html list of formatted tweets
	 * @since  0.1.1
	 * @param  array $settings Settings for grabbing tweets
	 */
	public static function tweets_shortcode( $settings ) {
		$settings['context'] = 'shortcode';
		return self::get_tweets_list( $settings );
	}

	/**
	 * Gets an html list of formatted tweets
	 * @since  0.1.1
	 * @param  array  $settings Settings for grabbing tweets
	 * @return string           Html list of formatted tweets
	 */
	public static function get_tweets_list( $settings ) {

		$list_format  = apply_filters( 'wds_twwi_tweet_list_format', "<ul class=\"wds-latest-tweets\">\n%s</ul>\n", $settings );
		$tweet_format = apply_filters( 'wds_twwi_tweet_format', "\t<li>%s</li>\n", $settings );

		$list = '';
		$tweets = self::get_tweets( $settings );

		foreach ( (array) $tweets as $tweet ) {
			$list .= sprintf( $tweet_format, $tweet );
		}

		return sprintf( $list_format, $list );

	}

	/**
	 * Gets array of formatted tweets (cached in a transient)
	 * @since  0.1.1
	 * @param  array  $settings Settings for grabbing tweets
	 * @return array            Array of formatted tweets
	 */
	public static function get_tweets( $settings ) {

		// Merge with defaults
		$settings = wp_parse_args( (array) $settings, self::defaults() );

		$twitter_id = sanitize_text_field( apply_filters( 'wds_twwi_twitter_id', $settings['twitter_id'], $settings ) );
		if ( ! trim( $twitter_id ) ) {
			return self::do_error( __( 'Please provide a Twitter Username.', 'wds_twwi' ) );
		}

		$twitter_num = (int) $settings['twitter_num'];
		$twitter_duration = absint( $settings['twitter_duration'] ) < 1 ? 60 : absint( $settings['twitter_duration'] );

		// create our transient ID
		$trans_id = $twitter_id .'-'. $twitter_num .'-'. $twitter_duration;
		// Should we reset our data?
		$reset_trans = isset( $_GET['delete-trans'] ) && $_GET['delete-trans'] == true;

		// If we're resetting the transient, or our transient is expired
		if ( $reset_trans || ! ( $tweets = get_transient( $trans_id ) ) ) {
			$hide_replies = $settings['twitter_hide_replies'];
			$show_time = $settings['show_time_stamp'];
			$number = $hide_replies ? $twitter_num + 80 : $twitter_num;
			$tweets = array();

			// Make sure we have our Twitter class
			if ( ! class_exists( 'TwitterWP' ) ) {
				require_once( WDS_TWWI_PATH .'lib/TwitterWP/lib/TwitterWP.php' );
			}

			// Initiate our Twitter app
			$tw = TwitterWP::start( array(
				$settings['consumer_key'],
				$settings['consumer_secret'],
				$settings['access_token'],
				$settings['access_token_secret'],
			) );
			if ( is_wp_error( $tw ) ) {
				return self::do_error( is_user_logged_in() ? $tw->show_wp_error( $tw, false ) : '' );
			}

			// Retrieve tweets from the api
			$_tweets = self::fetch_tweets( $tw, compact( 'twitter_id', 'number' ) );

			if ( ! $_tweets ) {
				return array( __( 'The Twitter API is taking too long to respond. Please try again later.', 'wds_twwi' ) );

			} elseif ( is_wp_error( $_tweets ) ) {
				return self::do_error( is_user_logged_in() ? $tw->show_wp_error( $_tweets, false ) : '' );
			}

			$count = 1;
			// Build the tweets array
			foreach ( (array) $_tweets as $tweet ) {
				// Don't include @ replies (if applicable)
				if ( $hide_replies && $tweet->in_reply_to_user_id ) {
					continue;
				}

				// Format tweet (hashtags, links, etc)
				$content = self::twitter_linkify( $tweet->text );

				if ( $show_time ) {
					// Calculate time difference
					$timeago = sprintf( __( 'about %s ago', 'wds_twwi' ), human_time_diff( strtotime( $tweet->created_at ) ) );
					$timeago_link = sprintf( '<a href="%s" rel="nofollow">%s</a>', esc_url( sprintf( 'http://twitter.com/%s/status/%s', $twitter_id, $tweet->id_str ) ), esc_html( $timeago ) );
					// Include timestamp
					$content .= '<span class="time-ago">'. $timeago_link .'</span>'."\n";
				}

				// Add tweet to array
				$tweets[] = apply_filters( 'wds_twwi_tweet_content', $content, $tweet, $settings );

				// Stop the loop if we've got enough tweets
				if ( $hide_replies && $count >= $twitter_num ) {
					break;
				}

				$count++;
			}

			// Just in case
			$tweets = array_slice( (array) $tweets, 0, $twitter_num );

			if ( $settings['follow_link_show'] && $settings['follow_link_text'] ) {
				$tweets[] = '<a href="' . esc_url( 'http://twitter.com/'.$twitter_id ).'" target="_blank">'. esc_html( $settings['follow_link_text'] ) .'</a>';
			}

			$time = ( $twitter_duration * 60 );
			// Save tweets to a transient
			set_transient( $trans_id, $tweets, $time );
		}

		return $tweets;
	}

	/**
	 * Retrieve tweets from the Twitter API
	 * Checks for tweets returned from the 'wds_twitter_fetch_tweets' filter
	 * before returning the default set of tweets for the user.
	 *
	 * @since  0.1.2
	 *
	 * @param  string $twitter_id Twitter user ID
	 * @param  int    $number     Number of tweets to retrieve
	 *
	 * @return mixed              Array of tweets or WP_Error
	 */
	public static function fetch_tweets( $tw, $args = array() ) {

		$tweets = apply_filters( 'wds_twitter_fetch_tweets', null, $tw, $args );

		if ( is_null( $tweets ) ) {
			$tweets = $tw->get_tweets( $args['twitter_id'], $args['number'] );
		}

		return $tweets;
	}

	/**
	 * Parses tweets and generates HTML anchor tags around URLs, usernames,
	 * username/list pairs and hashtags.
	 *
	 * @link https://github.com/mzsanford/twitter-text-php
	 *
	 * @since  0.1.0
	 * @param  string $content Tweet content
	 * @return string          Modified tweet content
	 */
	public static function twitter_linkify( $content ) {

		// Include the Twitter-Text-PHP library
		if ( ! class_exists( 'Twitter_Regex' ) ) {
			require_once( WDS_TWWI_PATH .'lib/TwitterText/lib/Twitter/Autolink.php' );
		}

		return Twitter_Autolink::create( $content, true )
			->setNoFollow( false )->setExternal( true )->setTarget( '_blank' )
			->setUsernameClass( 'tweet-url username' )
			->setListClass( 'tweet-url list-slug' )
			->setHashtagClass( 'tweet-url hashtag' )
			->setURLClass( 'tweet-url tweek-link' )
			->addLinks();
	}

	/**
	 * Return error message in an array
	 * @since  0.1.1
	 * @param  string $msg Error message (optional)
	 * @return array       Error message in an array
	 */
	public static function do_error( $msg = '' ) {
		$msg = $msg ? $msg : self::$error;
		return array( apply_filters( 'wds_twwi_twitter_error', $msg ) );
	}

	/**
	 * Return setting defaults
	 * @since  0.1.1
	 * @return array WDS_Twitter $defaults array
	 */
	public static function defaults() {
		return apply_filters( 'wds_twwi_twitter_defaults', self::$defaults );
	}

	/**
	 * Disables widget app fields by adding them programmatically
	 * @since 0.1.1
	 * @param array $app App credentials
	 */
	public static function disable_widget_app_settings( $app = array() ) {

		// Make sure we have our Twitter class
		if ( ! class_exists( 'TwitterWP' ) ) {
			require_once( WDS_TWWI_PATH .'lib/TwitterWP/lib/TwitterWP.php' );
		}

		// initiate your app
		$tw = TwitterWP::start( $app );
		if ( is_wp_error( $tw ) ) {
			self::$error = $tw->show_wp_error( $tw, false );
			add_action( 'all_admin_notices', array( $this, 'bad_app' ) );
			return;
		}

		// Ok, app's good, hide the widget fields
		WDS_Twitter::$hide_app_fields = true;
	}

	/**
	 * Displays any errors output from WDS_Twitter::disable_widget_app_settings in the admin dashboard
	 * @since  0.1.1
	 */
	public static function bad_app() {
		printf( '<div id="message" class="updated">%s</div>', self::$error );
	}

}

// init our class
WDS_Twitter::init();

/**
 * Disables widget app fields by adding them programmatically
 *
 * Will displays any app errors in the admin dashboard
 *
 * @since 0.1.1
 * @param array $app App credentials
 */
function wds_twwi_disable_widget_app_settings( $app = array() ) {
	WDS_Twitter::disable_widget_app_settings( $app );
}
