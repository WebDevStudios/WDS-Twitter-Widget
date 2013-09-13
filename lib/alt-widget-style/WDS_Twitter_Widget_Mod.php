<?php
/**
 * Modifies output of WDS Twitter Widget
 * @since  0.1.1
 */
class WDS_Twitter_Widget_Mod {

    /**
     * Alternating tweet classname
     * @since  0.1.1
     */
    public static $class = 'odd';

    public function __construct() {
        add_filter( 'wds_twwi_tweet_content', array( $this, 'reformat_tweet' ), 10, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'style' )  );
    }

    /**
     * Enqueue stylesheet
     * @since  0.1.1
     */
    public function style() {
        wp_register_style( 'wds-twitter-alt', WDS_TWWI_URL.'lib/alt-widget-style/tweet-styling.css', null, '0.1.1' );
    }

    /**
     * Filters tweet output & enqueues the stylesheet
     * @since  0.1.1
     * @param  string $content Already created tweet
     * @param  object $tweet   Tweet object from Twitter's API
     * @return string          Reformatted tweet
     */
    public function reformat_tweet( $content, $tweet ) {
        wp_enqueue_style( 'wds-twitter-alt' );
        return self::render_tweet( $tweet, $content );
    }

    /**
     * Format a tweet object from Twitter's API
     * @since  0.1.1
     * @param  object $tweet   Tweet object from Twitter's api
     * @param  string $content Replace tweet text
     * @return string          Formatted tweet
     */
    public static function render_tweet( $tweet, $content = '' ) {
        $content = $content ? $content : $tweet->text;
        $result  = '
        <div class="tweet '. self::get_class() .'">
            <header class="tweet-head clear">
                <a target="_blank" href="https://twitter.com/' . $tweet->user->screen_name . '">
                    <img class="tweet-portrait" src="' . $tweet->user->profile_image_url . '">
                    <div class="tweet-name">' . $tweet->user->name . '</div>
                    <div class="tweet-username">@' . $tweet->user->screen_name . '</div>
                </a>
                <a target="_blank" href = "https://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id_str . '" class="tweet-time" data-createdtime="' . $tweet->created_at . '">'. self::time_ago( $tweet->created_at ) .'</a>
            </header>
            <div class="tweet-body">'. $content .'</div>
            <footer class="tweet-foot">
                <a class="tweet-reply" target="_blank" href="https://twitter.com/intent/tweet?in_reply_to=' . $tweet->id_str . '">Reply</a>
                <a class="tweet-retweet" target="_blank" href="https://twitter.com/intent/retweet?tweet_id=' . $tweet->id_str . '">Retweet</a>
                <a class="tweet-favorite" target="_blank" href="https://twitter.com/intent/favorite?tweet_id=' . $tweet->id_str . '">Favorite</a>
            </footer>
        </div>'."\n";
        return $result;
    }

    /**
     * Alternating tweet classname
     * @since  0.1.1
     * @return string Alternating tweet class
     */
    public static function get_class() {
        $class = self::$class;
        self::$class = $class == 'odd' ? 'even' : 'odd';
        return $class;
    }

    /**
     * Gets time relative to now, and formats with first letter
     * @since  0.1.1
     * @param  string $timestr Timestamp (or other time string)
     * @return string          Formatted relative time
     */
    public static function time_ago( $timestr ) {
        $time_ago = human_time_diff( strtotime( $timestr ) );
        $replacements = array(
            'h' => '/ hours?/i',
            'd' => '/ days?/i',
            'w' => '/ weeks?/i',
            'm' => '/ months?/i',
            'y' => '/ years?/i',
        );
        return preg_replace( $replacements, array_keys( $replacements ), $time_ago );
    }

}
new WDS_Twitter_Widget_Mod();