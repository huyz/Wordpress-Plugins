<?php
/**
 * Plugin Name: Pending Posts Widget
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Un widget qui affiche la liste des articles en attente.
 * Version: 0.1
 * Author: Cédric Boverie
 * Author URI: http://www.boverie.eu
 */

/**
 * Pending_Posts widget class
 */
class WP_Widget_Pending_Posts extends WP_Widget {

	function WP_Widget_Pending_Posts() {
		$widget_ops = array('classname' => 'widget_Pending_entries', 'description' => __( "List scheduled/Pending posts", 'Pending_posts_widget') );
		$this->WP_Widget('Pending-posts', __('Pending Posts', 'Pending_posts_widget'), $widget_ops);

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	function widget($args, $instance) {
		$cache = wp_cache_get('widget_Pending_posts', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( isset($cache[$args['widget_id']]) )
			return $cache[$args['widget_id']];

		ob_start();
		extract($args);

		$title = empty($instance['title']) ? __('Pending Posts', 'Pending_posts_widget') : apply_filters('widget_title', $instance['title']);
		if ( !$number = (int) $instance['number'] )
			$number = 10;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 15 )
			$number = 15;

		$queryArgs = array(
			'showposts'			=> $number,
			'what_to_show'		=> 'posts',
			'nopaging'			=> 0,
			'post_status'		=> 'pending',
			'caller_get_posts'	=> 1,
			'order'				=> 'ASC'
		);

		$r = new WP_Query($queryArgs);
		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php echo $before_title . $title . $after_title; ?>
		<ul>
		<?php  while ($r->have_posts()) : $r->the_post(); ?>
		<li><?php if ( get_the_title() ) the_title(); else the_ID(); ?><?php edit_post_link('e',' (',')'); ?></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
			wp_reset_query();  // Restore global post data stomped by the_post().
		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_add('widget_Pending_posts', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_Pending_entries']) )
			delete_option('widget_Pending_entries');

		return $instance;
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_Pending_posts', 'widget');
	}

	function form( $instance ) {
		$title = esc_attr($instance['title']);
		if ( !$number = (int) $instance['number'] )
			$number = 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>">
		<?php _e('Number of posts to show:'); ?>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" /></label>
		<br /><small><?php _e('(at most 15)'); ?></small></p>
<?php
	}
}
function registerPendingPostsWidget() {
	register_widget('WP_Widget_Pending_Posts');
}
add_action('widgets_init', 'registerPendingPostsWidget');
