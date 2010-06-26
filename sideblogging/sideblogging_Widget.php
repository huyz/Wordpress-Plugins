<?php
// Sidebar widget
class SideBlogging_Widget extends WP_Widget {

	function SideBlogging_Widget() {
		$widget_ops = array(
			'classname' => 'widget_sideblogging',
			'description' => __('Display asides in a widget',Sideblogging::domain)
		);
		$this->WP_Widget(false, 'SideBlogging',$widget_ops);
	}

	function form($instance) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? intval($instance['number']) : '';
			
		if($number <= 0)
			$number = 5;
			
		echo '<p>';
		echo '<label for="'.$this->get_field_id('title').'">';
		echo __('Title:').' <input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" type="text" value="'.$title.'" />';
		echo '</label><br />';
		
		echo '<label for="'.$this->get_field_id('number').'">';
		_e('Number of asides to display:',Sideblogging::domain);
		echo ' <select name="'.$this->get_field_name('number').'" id="'.$this->get_field_id('number').'" class="widefat">';
		foreach(range(1,20) as $n)
			echo '<option value="'.$n.'" '.selected($number,$n).'>'.$n.'</option>';
		echo '</select></label>';
		echo '</p>';
	}

        /** @see WP_Widget::update */
        function update($new_instance, $old_instance) {
            $instance = $old_instance;
            $instance['title'] = strip_tags($new_instance['title']);
            $instance['number'] = intval($new_instance['number']);
            return $instance;
        }

	function widget($args, $instance) {
		// outputs the content of the widget
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$number = intval($instance['number']);
		
		if(empty($title))
			$title = __('Asides',Sideblogging::domain);
		if($number <= 0)
			$number = 5;
				
		echo $before_widget;
		echo $before_title.$title.$after_title;
		global $query_string;
		query_posts('post_type=asides&posts_per_page='.$number.'&orderby=date&order=DESC');

		//The Loop
		if (have_posts())
		{
			echo '<ul>';
			while ( have_posts() )
			{
				the_post();
				$content = get_the_content();
				
				echo '<li>'.get_the_title();

				if(strlen($content) > 0)
				{
					if(preg_match('#youtube.com|dailymotion.com|wat.tv|.flv|&lt;video#',$content))
					{
						$image = 'video.gif';
						$alt = 'Lien vers la vidéo';
					}
					else if(preg_match('#.mp3|.ogg|&lt;audio#',$content))
					{
						$image = 'music.gif';
						$alt = 'Lien vers le son';
					}
					else if(preg_match('#&lt;embed|&lt;object#',$content))
					{
						$image = 'video.gif';
						$alt = 'Lien vers la vidéo';
					}
					else if(preg_match('#&lt;img#',$content))
					{
						$image = 'image.gif';
						$alt = 'Lien vers l\'image';
					}
					else
					{
						$image = 'other.gif';
						$alt = 'Lien vers la brève';
					}
					
					echo ' <a href="'.get_permalink().'">
					<img src="'.SIDEBLOGGING_URL.'/images/'.$image.'" alt="'.$alt.'" title="'.$alt.'" />
					</a>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		wp_reset_query();
		echo $after_widget;
		}
	}
?>