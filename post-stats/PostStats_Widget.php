<?php
// Sidebar widget
class PostStats_Widget extends WP_Widget {
	function PostStats_Widget() {
		$widget_ops = array('classname' => 'widget_poststats', 'description' => __('Posts Statistics',POSTSTATS_TEXTDOMAIN) );
		$this->WP_Widget(false, 'PostStats',$widget_ops);
	}

	function form($instance) {
            $title = esc_attr($instance['title']);
            ?>
                <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <?php
        }

        /** @see WP_Widget::update */
        function update($new_instance, $old_instance) {
            $instance = $old_instance;
            $instance['title'] = strip_tags($new_instance['title']);
            return $instance;
        }

	function widget($args, $instance) {
            // outputs the content of the widget
            extract( $args );
            $title = apply_filters('widget_title', $instance['title']);
            echo $before_widget;
			
            if(empty($title))
				$title = __('Posts Statistics',POSTSTATS_TEXTDOMAIN);
				
            echo $before_title.$title.$after_title;
            PostStats_widget_function();
            echo $after_widget;
	}
}
?>