<?php
/**
 * WDS Latest Tweets widget class.
 * @since 0.1.0
 */
class WDS_Latest_Tweets_Widget extends WP_Widget {

	/**
	 * Initiate widget
	 * @since 0.1.0
	 */
	public function __construct() {

		$widget_ops = array(
			'classname'   => 'latest-tweets',
			'description' => __( 'Display a list of a user\'s latest tweets.', 'wds_twwi' ),
		);

		$control_ops = array(
			'id_base' => 'latest-tweets',
			'width'   => 200,
			'height'  => 250,
		);

		parent::__construct( 'latest-tweets', __( 'WDS - Latest Tweets', 'wds_twwi' ), $widget_ops, $control_ops );

	}

	/**
	 * Echo the widget content.
	 * @since 0.1.0
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, WDS_Twitter::defaults() );

		echo $before_widget;

		if ( $instance['title'] ) {
			echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;
		}

		WDS_Twitter::tweets_list( $instance );

		echo $after_widget;

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that $new_instance is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 0.1.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via form()
	 * @param array $old_instance Old settings for this instance
	 * @return array Settings to save or bool false to cancel saving
	 */
	public function update( $new_instance, $old_instance ) {

		$defaults = WDS_Twitter::defaults();

		// Get Transient ID
		$twitter_id       = apply_filters( 'wds_twwi_twitter_id', ( isset( $old_instance['twitter_id'] ) ? $old_instance['twitter_id'] : '' ), $old_instance );
		$twitter_num      = isset( $old_instance['twitter_num'] ) ? $old_instance['twitter_num'] : $defaults['twitter_num'];
		$twitter_duration = isset( $old_instance['twitter_duration'] ) ? $old_instance['twitter_duration'] : $defaults['twitter_duration'];
		$trans_id         = $twitter_id .'-'. $twitter_num .'-'. $twitter_duration;

		// Force the transient to refresh
		delete_transient( $trans_id );

		$instance['twitter_id'] = str_replace( '@', '', strip_tags( $new_instance['twitter_id'] ) );

		foreach ( $defaults as $key => $value ) {
			$instance[$key] = strip_tags( ( isset( $new_instance[$key] ) ? $new_instance[$key] : $value ) );
		}

		return $instance;

	}

	/**
	 * Echo the settings update form.
	 * @since 0.1.0
	 * @param array $instance Current settings
	 */
	public function form( $instance ) {

		/** Merge with defaults */
		$instance = wp_parse_args( (array) $instance, WDS_Twitter::defaults() );

		if ( ! WDS_Twitter::$hide_app_fields ) {
			$this->twitter_app_fields( $instance );
		}
		?>
		<h3><?php _e( 'Widget Options', 'wds_twwi' ); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_id' ); ?>"><?php _e( 'Twitter Username', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'twitter_id' ); ?>" name="<?php echo $this->get_field_name( 'twitter_id' ); ?>" value="<?php echo esc_attr( $instance['twitter_id'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_num' ); ?>"><?php _e( 'Number of Tweets to Show', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'twitter_num' ); ?>" name="<?php echo $this->get_field_name( 'twitter_num' ); ?>" value="<?php echo esc_attr( $instance['twitter_num'] ); ?>" size="3" />
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'twitter_hide_replies' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'twitter_hide_replies' ); ?>" value="1" <?php checked( $instance['twitter_hide_replies'] ); ?>/>
			<label for="<?php echo $this->get_field_id( 'twitter_hide_replies' ); ?>"><?php _e( 'Hide @ Replies', 'wds_twwi' ); ?></label>
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'show_time_stamp' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_time_stamp' ); ?>" value="1" <?php checked( $instance['show_time_stamp'] ); ?>/>
			<label for="<?php echo $this->get_field_id( 'show_time_stamp' ); ?>"><?php _e( 'Show Tweet Timestamp', 'wds_twwi' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_duration' ); ?>"><?php _e( 'Load new Tweets every', 'wds_twwi' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'twitter_duration' ); ?>" id="<?php echo $this->get_field_id( 'twitter_duration' ); ?>">
				<option value="5" <?php selected( 5, $instance['twitter_duration'] ); ?>><?php _e( '5 Min.' , 'wds_twwi' ); ?></option>
				<option value="15" <?php selected( 15, $instance['twitter_duration'] ); ?>><?php _e( '15 Minutes' , 'wds_twwi' ); ?></option>
				<option value="30" <?php selected( 30, $instance['twitter_duration'] ); ?>><?php _e( '30 Minutes' , 'wds_twwi' ); ?></option>
				<option value="60" <?php selected( 60, $instance['twitter_duration'] ); ?>><?php _e( '1 Hour' , 'wds_twwi' ); ?></option>
				<option value="120" <?php selected( 120, $instance['twitter_duration'] ); ?>><?php _e( '2 Hours' , 'wds_twwi' ); ?></option>
				<option value="240" <?php selected( 240, $instance['twitter_duration'] ); ?>><?php _e( '4 Hours' , 'wds_twwi' ); ?></option>
				<option value="720" <?php selected( 720, $instance['twitter_duration'] ); ?>><?php _e( '12 Hours' , 'wds_twwi' ); ?></option>
				<option value="1440" <?php selected( 1440, $instance['twitter_duration'] ); ?>><?php _e( '24 Hours' , 'wds_twwi' ); ?></option>
			</select>
		</p>

		<p>
			<input id="<?php echo $this->get_field_id( 'follow_link_show' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'follow_link_show' ); ?>" value="1" <?php checked( $instance['follow_link_show'] ); ?>/>
			<label for="<?php echo $this->get_field_id( 'follow_link_show' ); ?>"><?php _e( 'Include link to twitter page?', 'wds_twwi' ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'follow_link_text' ); ?>"><?php _e( 'Link Text (required)', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'follow_link_text' ); ?>" name="<?php echo $this->get_field_name( 'follow_link_text' ); ?>" value="<?php echo esc_attr( $instance['follow_link_text'] ); ?>" class="widefat" />
		</p>
		<?php

	}

	/**
	 * Show Twitter App Credential fields
	 * @since  0.1.1
	 * @param array $instance Current settings
	 */
	public function twitter_app_fields( $instance ) {
		?>
		<h3><?php _e( 'Twitter App Credentials', 'wds_twwi' ); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_key' ); ?>"><?php _e( 'Consumer Key', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'consumer_key' ); ?>" name="<?php echo $this->get_field_name( 'consumer_key' ); ?>" value="<?php echo esc_attr( $instance['consumer_key'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'consumer_secret' ); ?>"><?php _e( 'Consumer Secret', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'consumer_secret' ); ?>" name="<?php echo $this->get_field_name( 'consumer_secret' ); ?>" value="<?php echo esc_attr( $instance['consumer_secret'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token' ); ?>"><?php _e( 'Access Token', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'access_token' ); ?>" name="<?php echo $this->get_field_name( 'access_token' ); ?>" value="<?php echo esc_attr( $instance['access_token'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'access_token_secret' ); ?>"><?php _e( 'Access Token Secret', 'wds_twwi' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'access_token_secret' ); ?>" name="<?php echo $this->get_field_name( 'access_token_secret' ); ?>" value="<?php echo esc_attr( $instance['access_token_secret'] ); ?>" class="widefat" />
		</p>
		<!-- <p class="description"><a href="http://webdevstudios.com/2013/08/29/how-to-create-a-twitter-app-to-help-alleviate-your-1-1-api-changeover-woes" target="_blank"><?php _e( 'How To Create a Twitter App', 'wds_twwi' ); ?></a></p> -->
		<?php
	}
}
