<?php

require_once(ABSPATH . WPINC . '/rss.php');

if (!class_exists('EG_Delicious_TagRolls_Widget')) {

	class EG_Delicious_TagRolls_Widget extends EG_Widget_110 {

		function EG_Delicious_TagRolls_Widget($id, $name, $description, $class_name, $multi_widget=FALSE) {
			$this->__construct($id, $name, $description, $class_name, $multi_widget);
		} // End of constructor

		function __construct($id, $name, $description, $class_name, $multi_widget=FALSE) {

			parent::__construct($id, $name, $description, $class_name, $multi_widget);

			$fields = array(
					'title'		=> array(
						'type'		=> 'text',
						'label'		=> 'Title'
					),
					'username'	=> array(
						'type'		=> 'text',
						'label'		=> 'Delicious username'
					),
					'count'		=> array(
						'type'		=> 'numeric',
						'label'		=> 'Quantity'
					),
					'font' 		=> array(
						'type'		=> 'comment',
						'label'		=> 'Font parameters'
					),
					'min_size'	=> array(
						'type'		=> 'numeric',
						'label'		=> 'Smallest size'
					),
					'max_size'	=> array(
						'type'		=> 'numeric',
						'label'		=> 'Largest size'
					),
					'orderby' 		=> array(
						'type'    	=> 'radio',
						'label'  	=> 'Order by',
						'list'	  	=> array ( 'alpha' => 'alphabetical', 'freq' => 'Frequency')
					),
					'order' 		=> array(
						'type'    	=> 'radio',
						'label'  	=> 'Order',
						'list'	  	=> array ( 'ASC' => 'Ascending', 'DESC' => 'Descending')
					),
					'flow' 		=> array(
						'type'    	=> 'radio',
						'label'   	=> 'Flow',
						'list'	  	=> array ( 'flat' => 'Flat', 'list' => 'List (UL/LI)')
					),
					'showitems' => array(
						'type'		=> 'comment',
						'label'		=> 'Show'
					),
					'name' 		=> array(
						'type'    	=> 'checkbox',
						'label'   	=> 'Show Delicious name'
					),
					'showadd'	=> array(
						'type'    	=> 'checkbox',
						'label'   	=> 'Show "Add me to your network"'
					),
					'showcount'	=> array(
						'type'    	=> 'checkbox',
						'label'   	=> 'Show Tags count'
					)
			);

			$plugin_options = get_option(EG_DELICIOUS_OPTIONS_ENTRY);
			if ($plugin_options !== FALSE && isset($plugin_options['username'])) $username = $plugin_options['username'];
			else $username = '';
			
			$default_values = array(
					'title' 		=> 'Delicious TagRolls',
					'username' 		=> $username,
					'count'			=> -1,
					'min_size'		=> 10,
					'max_size'		=> 22,
					'orderby'		=> 'freq',
					'order'			=> 'ASC',
					'flow'			=> 'flat',
					'showadd' 		=> 1,
					'showcount'		=> 1,
					'name' 			=> 1
				);

			$this->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 0 );
			$this->set_form($fields, $default_values, TRUE );

		} // End of constructor


		function display($args, $number=-1) {

			if ($number<0) $number = 1;

			extract($args, EXTR_SKIP);
			$values = wp_parse_args( $this->options[$number], $this->default_values );

			$output = '';
			if ($this->is_visible($number) && $values['username'] != '') {

				if (EG_DELICIOUS_USE_LOCAL_DATA)
					$query = get_bloginfo('home').'/wp-content/plugins/eg-delicious-sync/tmp/debug/tagrolls.txt';
				else
					$query = 'http://feeds.delicious.com/v2/fancy/tags/'.attribute_escape($values['username']);

				$rss = fetch_rss($query);
				if ( !is_object($rss) ) {
					$output .= '<p>'.__('Error: could not find an RSS or ATOM feed at that URL.').'</p>';
				} else {

					$tags_list = array();
					foreach ( (array) $rss->items as $item ) {
						$tags_list[$item['title']] = $item['description'];
					}

					if ($values['orderby'] == 'freq') {
						if ($values['order'] == 'ASC') asort($tags_list);
						else arsort($tags_list);
					}
					else {
						if ($values['order'] == 'ASC') ksort($tags_list);
						else krsort($tags_list);
					}
					
					$max_number = ($values['count']>0?$values['count']:sizeof($tags_list));
					$max_count = max($tags_list);
					$min_count = min(1, $tags_list);
					$max_size  = ($values['max_size']<0?-$values['max_size']:$values['max_size']);
					$min_size  = ($values['min_size']<0?-$values['min_size']:$values['min_size']);
					if ($max_size < $min_size) {
						$tempo = $max_size;
						$max_size = $min_size;
						$min_size = $tempo;
					}
					if ($max_size == $min_size) {
						$max_size = $min_size + 10;
					}
					$a = ($max_size - $min_size) / ($max_count - $min_count);
					$b = $min_size - $a*$min_count;

					$url_base = 'http://delicious.com/'.$values['username'].'/';
					if ($values['flow'] == 'flat') {
						$before_list = '<div class="widget_delicious_tagrolls">';
						$after_list  = '</div>';
						$before_tag  = '';
						$after_tag   = ' ';
					}
					else {
						$before_list = '<ul class="widget_delicious_tagrolls">';
						$after_list  = '</ul>';
						$before_tag = '<li>';
						$after_tag  = '</li>';
					}

					$num = 0;
					$output .= $before_list;
					foreach ($tags_list as $tag => $count) {
						$font_size = intval($a * $count + $b);
						$output .= $before_tag.'<a href="'.$url_base.$tag.'" style="font-size:'.$font_size.'pt;">'.$tag.'</a>'.($values['showcount']?'('.$count.')':'').$after_tag;
						$num++;
						if ($num > $max_number) break;
					}
					$output .= $after_list;

					$temp_output = '';
					if ($values['name']) {
						$temp_output .= '<li class="icon-s">'.
								sprintf(__('I am <a href="%1s">%2s</a> on <a href="%3s">Delicious</a>', $this->textdomain),'http://delicious.com/'.$values['username'], $values['username'], 'http://delicious.com/').
								'</li>';
					}
					if ($values['showadd']) {
						$temp_output .= '<li class="showadd">'.
								sprintf(__('<a href="%s">Add me to your network</a>',$this->textdomain), 'http://delicious.com/network?add='.$values['username']).
								'</li>';
					}
					if ($temp_output != '') $output .= '<ul class="widget_delicious_tagrolls">'.$temp_output.'</ul>';
				} // End fetch_rss ok.
			} // End of non empty username

			if ($output != '') {
				echo $before_widget.
					($values['title']!= ''?$before_title.__($values['title'], $this->textdomain).$after_title:'').
					$output.
					$after_widget;
			}
		} // End of display
	} // End of class

} // End of class_exists

if (!class_exists('EG_Delicious_Badge_Widget')) {

	class EG_Delicious_Badge_Widget extends EG_Widget_110 {

		function EG_Delicious_Badge_Widget($id, $name, $description, $class_name, $multi_widget=FALSE) {
			$this->__construct($id, $name, $description, $class_name, $multi_widget);
		} // End of constructor

		function __construct($id, $name, $description, $class_name, $multi_widget=FALSE) {

			parent::__construct($id, $name, $description, $class_name, $multi_widget);

			$fields = array(
					'title' => array(
						'type'    => 'text',
						'label'   => 'Title'
					),
					'username'	=> array(
						'type'    => 'text',
						'label'   => 'Delicious username'
					),
					'icon'		=> array(
						'type'    => 'select',
						'label'   => 'Icon',
						'list'    => array('icon-none' => 'None', 'icon-s' => 'Small', 'icon-m' => 'Medium')
					),
					'showadd'	=> array(
						'type'    => 'checkbox',
						'label'   => 'Show "Add me to your network"'
					),
					'name' 		=> array(
						'type'    => 'checkbox',
						'label'   => 'Show Delicious name'
					),
					'itemcount' => array(
						'type'    => 'checkbox',
						'label'   => 'Show bookmark count'
					),
					'nwcount'	=> array(
						'type'    => 'checkbox',
						'label'   => 'Show network count'
					),
					'fancount' => array(
						'type'    => 'checkbox',
						'label'   => 'Show fan count'
					)
				);

			$plugin_options = get_option(EG_DELICIOUS_OPTIONS_ENTRY);
			if ($plugin_options !== FALSE && isset($plugin_options['username'])) $username = $plugin_options['username'];
			else $username = '';

			$default_values = array(
					'title' 		=> 'Delicious Network Badge',
					'username' 		=> $username,
					'icon' 			=> 'icon=m',
					'showadd' 		=> 1,
					'name' 			=> 1,
					'itemcount' 	=> 1,
					'nwcount' 		=> 1,
					'fancount' 		=> 1
				);

			$this->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 0 );
			$this->set_form($fields, $default_values, TRUE );

		} // End of constructor


		function display($args, $number=-1) {

			if ($number<0) $number = 1;

			extract($args, EXTR_SKIP);
			$values = wp_parse_args( $this->options[$number], $this->default_values );

			$output = '';
			if ($this->is_visible($number) && $values['username'] != '') {

				if (EG_DELICIOUS_USE_LOCAL_DATA)
					$query = get_bloginfo('home').'/wp-content/plugins/eg-delicious-sync/tmp/debug/badge.txt';
				else
					$query = 'http://feeds.delicious.com/v2/fancy/userinfo/'.attribute_escape($values['username']);

				$rss = fetch_rss($query);
				if ( !is_object($rss) ) {
					$output .= '<p>'.__('Error: could not find an RSS or ATOM feed at that URL.').'</p>';
				} else {

					$class = ' class="'.$values['icon'].'"';
					if ($values['name']) {
						$output .= '<li'.$class.'>'.
								sprintf(__('I am <a href="%1s">%2s</a> on <a href="%3s">Delicious</a>', $this->textdomain),'http://delicious.com/'.$values['username'], $values['username'], 'http://delicious.com/').
								'</li>';
					}
					foreach ( (array) $rss->items as $item ) {

						$field_name  = $item['guid'];
						$field_value = $item['description'];

						if ($values['itemcount'] && $field_name == 'items' ) {
							$output .= '<li'.($output==''?$class:'').'>'.
									sprintf(__('I have <strong>%1s</strong> <a href="%2s">bookmarks</a>',$this->textdomain), $field_value, 'http://delicious.com/'.$values['username']).
									'</li>';
						}
						if ($values['nwcount'] && $field_name == 'networkmembers' ) {
							$output .= '<li'.($output==''?$class:'').'>'.
									sprintf(__('I have <strong>%1s</strong> people in <a href="%2s">my network</a>',$this->textdomain), $field_value, 'http://delicious.com/network/'.$values['username'] ).
									'</li>';
						}
						if ($values['fancount'] && $field_name == 'networkfans' ) {
								$output .= '<li'.($output==''?$class:'').'>'.
										   sprintf(__('I have <strong>%1s</strong> fan',$this->textdomain), $field_value).
										   '</li>';
						}
					} // End of foreach item

					if ($values['showadd']) {
						$output .= '<li class="showadd">'.
								sprintf(__('<a href="%s">Add me to your network</a>',$this->textdomain), 'http://delicious.com/network?add='.$values['username']).'</li>';
					}

				} // End fetch_rss ok.
			} // End of non empty username

			if ($output != '') {
				echo $before_widget.
					($values['title']!= ''?$before_title.__($values['title'], $this->textdomain).$after_title:'').
					'<ul class="widget_delicious_badge">'.$output.'</ul>'.
					$after_widget;
			}
		} // End of display
	} // End of class

} // End of class_exists


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
						'presentation'     => array( 'type'	=> 'comment',  'label' => 'General options'),
						'title'            => array( 'type'	=> 'ftext',    'label' => 'Title'),
						'end_title'	       => array( 'type' => 'separator'),
						'col'	           => array( 'type'	=> 'comment',  'label' => 'Columns'),
						'columns'          => array( 'type' => 'select',   'label' => 'Columns',
							        'list' => array( '1' => 1, '2' => 2, '3' => 3)),
						'column_min'       => array( 'type' => 'numeric',  'label' => 'Minimum number of links per column'),
						'end_col'	       => array( 'type' => 'separator'),
						'categories'       => array( 'type'	=> 'comment',  'label' => 'Categories options'),
						'category'         => array( 'type'	=> 'checkbox', 'label' => 'Categories to display:',
							'list'		   => $categories),
						'categorize'       => array( 'type' => 'checkbox', 'label' => 'Group links by category'),
						'end_category'     => array( 'type'	=> 'separator'),
						'links'            => array( 'type'	=> 'comment',  'label' => 'Links options'),
						'show_description' => array( 'type' => 'checkbox', 'label' => 'Show description'),
						'hide_invisible'   => array( 'type' => 'checkbox', 'label' => 'Hide Invisible'),
						'orderby'          => array( 'type' => 'select',   'label' => 'Order by',
							     'list'    => array( 'none' => '', 'name' => 'Name', 'rating' => 'Rating', 'rand' => 'Random')),
						'order' 		   => array( 'type' => 'select',   'label' => 'Order',
								    'list' => array( 'none' => '', 'ASC' => 'Ascending',	'DESC' => 'Descending')),
						'limit' 		   => array( 'type' => 'numeric',  'label' => 'Number of links to display'),
						'show_all'         => array( 'type' => 'numeric',  'label' => 'Page/Post ID to see all links')
				);

				$default_values = array(
						'title' 			=> 'Blogroll',
						'columns' 			=> 1,
						'column_min'		=> -1,
						'category' 			=> array_keys($categories),
						'categorize'        => 0,
						'show_description'  => 0,
						'hide_invisible'	=> '1',
						'orderby' 			=> 'none',
						'order' 			=> 'none',
						'limit' 			=> '-1',
						'show_all' 			=> ''
					);

			$this->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 0 );
			$this->set_form($fields, $default_values, TRUE );
		} // End of construct

		function list_bookmarks($bookmarks, $cat_id, $columns, $column_min, $show_all, $show_description) {

			$output = '';
			if ($show_all != '' && $show_all != 0) {
				$bookmarks[$bookmark_number]->link_name         = __('List of the links',$this->textdomain);
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
// $eg_delicious_blogroll_widget->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 900);
$eg_delicious_blogroll_widget->load();

$eg_delicious_badge_widget = new EG_Delicious_Badge_Widget('egdel_badge', 'EG-Delicious Network Badge', 'RSS feeds to display Delicious Network Badge', 'widget_delicious_badge', FALSE);
// $eg_delicious_badge_widget->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 900);
$eg_delicious_badge_widget->load();

$eg_delicious_tagrolls_widget = new EG_Delicious_TagRolls_Widget('egdel_tagrolls', 'EG-Delicious TagRolls', 'RSS feeds to display Delicious Tags', 'widget_delicious_tagrolls', FALSE);
// $eg_delicious_tagrolls_widget->set_options(EG_DELICIOUS_TEXTDOMAIN, EG_DELICIOUS_COREFILE, 900);
$eg_delicious_tagrolls_widget->load();

?>