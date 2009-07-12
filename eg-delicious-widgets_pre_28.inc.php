<?php

if (!class_exists('EG_Delicious_BlogRoll_Widget')) {

	class EG_Delicious_BlogRoll_Widget extends EG_Widget_110 {

		function EG_Delicious_BlogRoll_Widget($id, $name, $description, $class_name, $multi_widget=FALSE) {
			$this->__construct($id, $name, $description, $class_name, $multi_widget);
		}

		function __construct($id, $name, $description, $class_name, $multi_widget=FALSE) {
			static $categories;

			parent::__construct($id, $name, $description, $class_name, $multi_widget);

			if (! isset($categories)) {
				$temp_cat = get_terms('link_category', array('hierarchical' => 0));
				foreach ($temp_cat as $cat) {
					$categories[$cat->term_id] = $cat->name;
				}
			}
			$fields = array(
						'presentation' => array(
							'type'		=> 'comment',
							'label'		=> 'General options'
						),
						'title' => array(
							'type'		=> 'ftext',
							'label'		=> 'Title'
						),
						'columns' => array(
							'type'		=> 'select',
							'label'		=> 'Columns',
							'list'		=> array( '1' => 1, '2' => 2)
						),
						'column_min'	=> array(
							'type'		=> 'numeric',
							'label'		=> 'Minimum number of links'
						),
						'end_pres'	=> array(
							'type' 		=> 'separator'
						),
						'categories' => array(
							'type'		=> 'comment',
							'label'		=> 'Categories options'
						),
						'category' => array(
							'type'		=> 'checkbox',
							'label'		=> 'Categories to display:',
							'list'		=> $categories
						),
						'categorize'  => array(
							'type'		=> 'checkbox',
							'label'		=> 'Group links by category',
						),
						'end_category' => array(
							'type'		=> 'separator'
						),
						'links' => array(
							'type'		=> 'comment',
							'label'		=> 'Links options'
						),
						'show_description' => array(
							'type'		=> 'checkbox',
							'label'		=> 'Show description',
						),
						'hide_invisible' => array(
							'type'    => 'checkbox',
							'label'   => 'Hide Invisible',
						),
						'orderby' => array(
							'type'    => 'select',
							'label'   => 'Order by',
							'list'    => array( 'none'   => '',
												'name'   => 'Name',
												'rating' => 'Rating',
												'rand'   => 'Random'
											)
						),
						'order' => array(
							'type'    => 'select',
							'label'   => 'Order',
							'list'    => array( 'none' => '',
												'ASC' => 'Ascending',
												'DESC' => 'Descending')
						),
						'limit' => array(
							'type'    => 'numeric',
							'label'   => 'Number of links to display'
						),
						'show_all' => array(
							'type'    => 'numeric',
							'label'   => 'Page/Post ID to see all links'
						),
				);

				$default_values = array(
						'title' 			=> 'Bookmarks',
						'columns' 			=> 1,
						'column_min'		=> -1,
						'category' 			=> array_keys($categories),
						'categorize'        => 1,
						'hide_invisible'	=> '1',
						'orderby' 			=> 'name',
						'order' 			=> 'ASC',
						'limit' 			=> '-1',
						'show_all' 			=> '',
						'show_description'  => 0
					);
			$this->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 0 );
			$this->set_form($fields, $default_values, TRUE );
		} // End of construct

		function list_bookmarks($bookmarks, $cat_id, $columns, $column_min, $show_all, $show_description) {

			$output = '';
			if ($show_all != '' && $show_all != 0) {
				$bookmarks[$bookmark_number]->link_name         = __('All favorites',$this->textdomain);
				$bookmarks[$bookmark_number]->link_url          = get_permalink($show_all);
				$bookmarks[$bookmark_number]->link_description  = '';
			}
			$bookmark_number = sizeof($bookmarks);
			if ($columns != 1 && $bookmark_number < $column_min) $columns = 1;
			if ($columns == 1) $height = $bookmark_number;
			else {
				$height = intval( $bookmark_number / $columns) + 1;
				$output .= '<div class="blogroll-block">';
			}
			$column_index = 1;
			$link_number  = 1;
			$output .= '<ul class="xoxo blogroll'.
						($columns!=1?' blogroll-col blogroll-col1':'').
						($cat_id>0? ' linkcat-'.$cat_id:'').
						'">';
			foreach ($bookmarks as $bookmark) {
				$name = $bookmark->link_name;
				$description = ($bookmark->link_description!='' && $show_description?' '.$bookmark->link_description:'');

				$output .= '<li><a href="'.$bookmark->link_url.'" title="'.$name.'">'.$name.'</a>'.$description.'</li>';
				if (! ($link_number % $height)) {
					$output .= '</ul>';
					$column_index++;
					if ($link_number != $bookmark_number) $output .= '<ul class="xoxo blogroll blogroll-col blogroll-col'.$column_index.($cat_id>0? ' linkcat-'.$cat_id:'').'">';
				}
				$link_number++;
			}
			$output .= ($columns == 1?'':'</div>');

			return ($output);
		}

		function display($args, $number=-1) {
			static $categories;

			if ($number<0) $number = 1;

			extract($args, EXTR_SKIP);
			$values = wp_parse_args( $this->options[$number], $this->default_values );

			$string = '';
			if ($this->is_visible($number) && isset($values['category']) && $values['category'] != '') {

				$params = array(
						'categorize' 		=> $values['categorize'],
						'title_li' 			=> '',
						'title_before' 		=> '',
						'title_after'  		=> '',
						'echo'				=> 0,
						'category'			=> implode(',', $values['category']),
						'limit'				=> ($values['limit'] == ''?-1:$values['limit']),
						'orderby'			=> ($values['orderby'] == 'none'?'name':$values['orderby']),
						'order'				=> ($values['order']   == 'none'?'ASC':$values['order']),
						'show_private'		=> $values['show_private'],
						'hide_invisible'	=> $values['hide_invisible']
					);

				if (! $params['categorize']) {
					$output     .= $before_widget.$before_title.$values['title'].$after_title;
					$bookmarks   = get_bookmarks($params);
					$output     .= $this->list_bookmarks($bookmarks, -1,
														$values['columns'],
														$values['column_min'],
														$values['show_all'],
														$values['show_description']);
					$output     .= $after_widget;

				} // Not categorize
				else {
					if (! isset($categories)) {
						$temp_cat = get_terms('link_category', array('hierarchical' => 0));
						foreach ($temp_cat as $cat) {
							$categories[$cat->term_id] = $cat->name;
						}
					}
					$output = '';
					foreach ($values['category'] as $cat_id) {
						$output            .= $before_widget.$before_title.$categories[$cat_id].$after_title;
						$params['category'] = $cat_id;
						$bookmarks          = get_bookmarks($params);
						$output            .= $this->list_bookmarks($bookmarks, $cat_id,
															$values['columns'],
															$values['column_min'],
															$values['show_all'],
															$values['show_description']);
						$output .= $after_widget;
					}
				} // Categorize

				// Display output
				if ($output != '') echo $output;

			} // End of is_visible

		} // End of display

	} // End of class

} // End of class_exists

$eg_delicious_blogroll_widget = new EG_Delicious_BlogRoll_Widget('egdel_blogroll', 'EG-Delicious Blogroll', 'Enhanced blogroll', 'widget_links', TRUE);
$eg_delicious_blogroll_widget->load();

?>