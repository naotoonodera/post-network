<?php


if (!defined('ABSPATH')) {
	exit;
}

class PostNetwork
{

	private $options;
	protected $option_name;
	protected $option_name_sub;


	public function __construct() {
		add_action('admin_menu', array($this, 'pn_add_option_setting_page'));
		add_action('admin_init', array($this, 'pn_page_init'));
		add_filter('plugin_action_links', array($this, 'pn_action_links'), 10, 2);
		$this->option_name = 'post_network';
		$this->option_name_sub = 'post_network_settings';
	}

	public function pn_add_option_setting_page() {
		add_menu_page('Post Network', 'Post Network', 'manage_options', $this->option_name, array($this, 'pn_create_main_page'), 'dashicons-networking', 99);
		add_submenu_page($this->option_name, 'Visualize', 'Visualize', 'manage_options', $this->option_name, array($this, 'pn_create_main_page'));
		add_submenu_page($this->option_name, 'Settings', 'Settings', 'manage_options', $this->option_name_sub, array($this, 'pn_create_settings_page'));
	}


	/*
	==================================
	Plugin page
	==================================
	*/

	public function pn_action_links($links, $file) {
		$plugin_file = 'post-network/post-network.php';

		if (is_plugin_active($plugin_file) && $plugin_file == $file) {
			$settings_link = '<a href="' . site_url() . '/wp-admin/admin.php?page=post_network_settings">' . __('Settings', 'post-network') . '</a>';
			array_unshift($links, $settings_link);
		}

		return $links;
	}

	/*
	==================================
	settings page
	==================================
	*/

	public function pn_page_init() {
		$this->options = get_option($this->option_name);

		if (isset($_GET['page']) && $_GET['page'] === $this->option_name_sub) {
			add_settings_section('graph', __('Graph settings', 'post-network'), '', $this->option_name);
		}

		$settings = $this->pn_get_fields();

		foreach ($settings as $key => $value) {
			if (!isset($value['id'], $value['title'], $value['callback'], $value['section_id'])) {
				continue;
			}

			add_settings_field($value['id'], $value['title'], array($this, $value['callback']), $this->option_name, $value['section_id'], $value);
		}

		register_setting($this->option_name, $this->option_name, array($this, 'pn_sanitize'));
	}


	/*
	==================================
	settings page view
	==================================
	*/

	public function pn_create_settings_page() {
		?>
		<div class="wrap pn-option-setting">
			<?php
			global $parent_file;
			if ('options-general.php' !== $parent_file) {
				require ABSPATH . 'wp-admin/options-head.php';
			}
			?>
			<h2>Post Network settings</h2>
			<form method="post" action="options.php">
			<?php
				settings_fields($this->option_name);
				do_settings_sections($this->option_name);
				submit_button(__('Save', 'post-network'));
			?>
			</form>
		</div>
		<?php
	}

	/*
	==================================
	main page view
	==================================
	*/

	public function pn_create_main_page() {
		?>
		<div class="pn-option-setting">
			<div id="pn-loader"></div>

			<div id="pn"></div>
			<table>
				<tr>
					<th><?php esc_attr_e('Post Title', 'post-network');?></th>
					<th><?php esc_attr_e('Permalink', 'post-network');?></th>
					<th><?php esc_attr_e('ID', 'post-network');?></th>
					<th><?php esc_attr_e('Edit', 'post-network');?></th>
				</tr>
				<?php

					$array_post_type = array('post');




					// WP＿Query args
					$args = array(
						'post_type' => $array_post_type,
						'posts_per_page' => -1,
					);

					if ($this->options['graph_post_status']) {
						$args = $args + array('post_status' => 'publish');
					}



					$query = new WP_Query($args);

					if ($query->have_posts()) {
						while ($query->have_posts()) {
							$query->the_post();
							$post = get_post();
							$permalink = get_permalink($post->ID);
							$nodes[] = $this->pn_create_node($post->ID, get_the_category($post->ID)[0]->term_id);
							$links = $this->pn_get_all_links(do_shortcode($post->post_content));
							$ids = $this->pn_urls_to_post_ids($links);

							if ($ids) {
								foreach ($ids as $key => $post_id) {
									if (in_array(get_post_type($post_id), $array_post_type, true)) {
										$edges[] = $this->pn_create_edge($post->ID, $post_id);
										$to_post_ids[] = $post_id;
									}
								}
							}

							$row_data[] = array(
								'id' => $post->ID,
								'title' => $post->post_title,
								'link' => $permalink,
							);
						}

						wp_reset_postdata();

						foreach ($to_post_ids as $to_post_id) {
							$key_index = array_search((int) $to_post_id, array_column($nodes, 'id'), true);

							if ($key_index) {
								$value = $nodes[$key_index]['value'] + 1;
								$nodes[$key_index]['value'] = $value;
							}
						}
					}
					$nodes = apply_filters('post_network_nodes', $nodes);
					$edges = apply_filters('post_network_edges', $edges);
				?>

				<?php foreach ($row_data as $row => $item): ?>
					<tr>
						<td class="title"><?php esc_attr_e($item['title']);?></td>
						<td class="permalink"><a href="<?php esc_attr_e($item['link']);?>"><?php esc_attr_e($item['link']);?></a></td>
						<td id="<?php esc_attr_e($item['id']);?>" class="id"><?php esc_attr_e($item['id']);?></td>
						<td class="edit"><a href="<?php esc_attr_e(home_url() . '/wp-admin/post.php?post=' . $item['id'] . '&action=edit');?>"><?php _e('Edit', 'post-network');?></a></td>
					</tr>
				<?php endforeach; ?>
			</table>


			<script type="text/javascript">
				var pnOptions = <?php echo wp_json_encode($this->options);?>;
				var nodes = new vis.DataSet(<?php echo wp_json_encode($nodes); ?>);
				var edges = new vis.DataSet(<?php echo wp_json_encode($edges); ?>);
				var optionsMain =  <?php echo wp_json_encode($this->pn_set_options_main()); ?>;
				var optionsConfigure =  <?php echo wp_json_encode($this->pn_set_options_configure()); ?>;
				var optionsEdges =  <?php echo wp_json_encode($this->pn_set_options_edges()); ?>;
				var optionsGroups =  <?php echo wp_json_encode($this->pn_set_options_groups()); ?>;
				var optionsInteraction =  <?php echo wp_json_encode($this->pn_set_options_interaction()); ?>;
				var optionsLayout =  <?php echo wp_json_encode($this->pn_set_options_layout()); ?>;
				var optionsManipulation =  <?php echo wp_json_encode($this->pn_set_options_manipulation()); ?>;
				var optionsNodes =  <?php echo wp_json_encode($this->pn_set_options_nodes()); ?>;
				var optionsPhysics =  <?php echo wp_json_encode($this->pn_set_options_physics()); ?>;

				pn_create(pnOptions, optionsMain, optionsConfigure, optionsEdges, optionsGroups, optionsInteraction, optionsLayout, optionsManipulation, optionsNodes, optionsPhysics);
			</script>
		</div>
		<?php
}

	public function pn_set_options_main() {
		//https://visjs.github.io/vis-network/docs/network/#options
		$optionsMain = array(
			'autoResize' => true,
		);

		return apply_filters('post_network_options_main', $optionsMain);
	}

	public function pn_set_options_configure() {
		//https://visjs.github.io/vis-network/docs/network/configure.html#
		$optionsConfigure = array(
			'configure' => array(
				'enabled' => false,
				'filter' => 'physics, edges',				
				'showButton' => true,
			),
		);
		return apply_filters('post_network_options_configure', $optionsConfigure);
	}

	public function pn_set_options_edges() {
		//https://visjs.github.io/vis-network/docs/network/edges.html
		$optionsEdges = array(
			'edges' => array(
				'arrows' => array(
					'to' => array(
						'enabled' => true,
					),
				),
			),
		);
		return apply_filters('post_network_options_edges', $optionsEdges);
	}

	public function pn_set_options_groups() {
		//https://visjs.github.io/vis-network/docs/network/groups.html        
			$optionsGroups = array(
				'groups' => array(
					'useDefaultGroups' => true,
				),			
		);
		return apply_filters('post_network_options_groups', $optionsGroups);
	}

	public function pn_set_options_interaction() {
		//https://visjs.github.io/vis-network/docs/network/interaction.html
		$optionsInteraction = array(
			'interaction' => array(
				'dragNodes' => true,
			),
		);
		return apply_filters('post_network_options_interaction', $optionsInteraction);

	}

	public function pn_set_options_layout() {
		//https://visjs.github.io/vis-network/docs/network/layout.html
		$optionsLayout = array(
			'layout' => array(
				'improvedLayout' => true,
			),
		);
		return apply_filters('post_network_options_layout', $optionsLayout);
	}

	public function pn_set_options_manipulation() {
		//https://visjs.github.io/vis-network/docs/network/manipulation.html
		$optionsManipulation = array(
			'manipulation' => array(
				'enabled' => false,
			),
		);
		return apply_filters('post_network_options_manipulation', $optionsManipulation);
	}

	public function pn_set_options_nodes() {
		//https://visjs.github.io/vis-network/docs/network/nodes.html
		$optionsNodes = array(
			'nodes' => array(
				'shape' => "dot",
			),
		);
		return apply_filters('post_network_options_nodes', $optionsNodes);
	}

	public function pn_set_options_physics() {
		//https://visjs.github.io/vis-network/docs/network/physics.html
		$optionsPhysics = array(
			'physics' => array(
				'enabled' => true,
			),
		);

		if ($this->options['graph_disable_physics']) {
			$optionsPhysics['physics'] = false;
		}
		return apply_filters('post_network_options_physics', $optionsPhysics);
	}

	/*
	==================================
	functions
	==================================
	*/

	/**
	 * Create edge
	 *
	 * @param  mixed $from Post id.
	 * @param  mixed $to Post id.
	 * @return array
	 */
	public function pn_create_edge($from, $to) {
		$array = array(
			'from' => (int) $from,
			'to' => (int) $to,
		);

		return $array;
	}
	/**
	 * Create node.
	 *
	 * @param  int    $post_id Post id.
	 * @param  string $group Post type.
	 * @return array
	 */
	public function pn_create_node($post_id, $group) {
		$array = array(
			'id' => (int) $post_id,
			'group' => (int) $group,
			'value' => (int) 0,

		);

		if ('post_title' === $this->options['graph_label']) {
			$array = $array + array('label' => (string) get_the_title($post_id));
		} elseif ('post_id' === $this->options['graph_label']) {
			$array = $array + array('label' => (string) $post_id);
			$array = $array + array('title' => get_the_title($post_id));
		} else { // none
			$array = $array + array('label' => '');
			$array = $array + array('title' => get_the_title($post_id));
		}

		return $array;
	}

	/**
	 * Get post ids from urls.
	 *
	 * @param  array $urls Post urls.
	 * @return array $post_ids Post ids.
	 */
	public function pn_urls_to_post_ids($urls) {
		$post_ids = array();
		if (!is_array($urls)) {
			return $post_ids;
		}
		$urls = array_unique($urls);
		foreach ($urls as $key => $url) {
			if (url_to_postid($url)) {
				$post_ids[] = url_to_postid($url);
			}
		}
		return $post_ids;
	}

	/**
	 * Get all the links in content.
	 *
	 * @param  mixed $content Post content.
	 * @return array Links found in content.
	 */
	public function pn_get_all_links($content) {
		$pattern = '(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)';
		preg_match_all($pattern, $content, $match);

		if (empty($match[0])) {
			return false;
		}
		return $match[0];
	}

	public function pn_sanitize($input) {
		$existing = get_option($this->option_name);

		if (!$existing) {
			return $input;
		}

		$return = array_merge($existing, $input);

		return $return;
	}

	/*
	==================================
	settings fields
	==================================
	*/

	public function pn_get_fields() {
		$array = array(
			array(
				'id' => 'graph_label',
				'title' => __('Graph label', 'post-network'),
				'callback' => 'pn_select_callback',
				'section_id' => 'graph',
				'value' => array(
					'post_id' => __('Post ID', 'post-network'),
					'post_title' => __('Post title', 'post-network'),
					'none' => __('None', 'post-network'),
				),
			),
			array(
				'id' => 'graph_disable_physics',
				'title' => __('Disable physics simulation', 'post-network'),
				'callback' => 'pn_boolean_callback',
				'section_id' => 'graph',
			),
			array(
				'id' => 'graph_post_status',
				'title' => __('Include published post only', 'post-network'),
				'callback' => 'pn_boolean_callback',
				'section_id' => 'graph',
			),
		);

		return $array;
	}

	/*
	==================================
	callbacks
	==================================
	*/

	public function pn_select_callback($args) {
		$option_value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : '';
		$args_value = $args['value'];
		$cnt = 0;
		?>
		<select name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($args['id']); ?>]" id="<?php echo esc_attr($args['id']); ?>">
			<?php foreach ($args_value as $key => $value): ?>
				<?php $checked_flag = (empty($option_value) && $cnt == 0 || $key === $option_value) ? true : false;?>
				<option value="<?php echo esc_attr($key); ?>"
				<?php
					if ($checked_flag) {
						echo 'selected';
					}
				?>
				><?php echo esc_attr($value); ?></option>
				<?php $cnt++;?>
			<?php endforeach;?>
		</select>
		<?php if (!empty($args['description'])): ?>
			<p><?php echo esc_attr($args['description']); ?></p>
		<?php endif;
	}

	public function pn_boolean_callback($args) {
		$option_value = isset($this->options[$args['id']]) ? esc_attr($this->options[$args['id']]) : '';
		?>
		<input type="hidden" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($args['id']); ?>]" value="0">
		<input type="checkbox" id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($args['id']); ?>]" value="1"<?php checked($this->options[$args['id']], 1);?>>
		<?php if (!empty($args['description'])): ?>
		<p><?php echo esc_attr($args['description']); ?></p>
		<?php endif;?>
		<?php
	}

}

/*
==================================
load
==================================
 */

if (is_admin()) {
	$option_settings_page = new PostNetwork();
}
