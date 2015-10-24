<?php

	/**
	 * Most Popular Posts
	 *
	 * @version 1.0
	 * @author Corneliu Cirlan (cornel@twocsoft.com)
	 * @link http://www.TwoCSoft.com/
	 */

	if (!class_exists("MostReadPosts")):

		class MostReadPosts
		{
			/**
			 * Post view key
			 *
			 * @var string
			 * @since 1.0
			 */
			private $countKey = "view-count";

			/**
			 * Post view slug
			 * 
			 * @var string
			 * @since 1.0
			 */
			private $countSlug = "view_count";

			/**
			 * Constructor
			 * 
			 * @since 1.0
			 */
			public function __construct()
			{
				// To keep the count accurate, lets get rid of prefetching
				remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

				// Hook into content to add data
				add_action('the_content', array($this, 'updateContent'));

				// insert ajax callback
				add_action('wp_head', array($this, 'ajaxCallback'));
					
				// update post view via AJAX only for non registered users
				add_action('wp_ajax_nopriv_'.$this->countKey, array($this, 'updateViewCount'));

				add_filter('manage_posts_columns', array($this, 'viewCountColumnHead'));
				add_action('manage_posts_custom_column', array($this, 'viewCountColumnContent'), 10, 2);

				// Customize the column
				add_action('admin_head', array($this, 'customizeColumn'));

				// sortable column
				add_action('manage_edit-post_sortable_columns', array($this, 'sortableViewCount'));
				add_action('pre_get_posts', array($this, 'sortMetaKey'));
			}


			/**
			 * AJAX CALLBACK
			 */
			public function ajaxCallback()
			{
				?>
				<script type="text/javascript">
					var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
					
					jQuery(document).ready(function($) {
						var postID = $('#<?php echo $this->countKey ?>').val();

						$.post(ajaxurl, {action: '<?php echo $this->countKey ?>', id: postID}, function(data, textStatus, xhr) {
							console.log(data);
						});
					});
				</script>
				<?php
			}


			/**
			 * UPDATE VIEW COUNT
			 */
			public function updateViewCount()
			{
				$this->setViewCount($_POST['id']);

				die($_POST['id']);
			}


			/**
			 * SET POST VIEW COUNT
			 */
			private function setViewCount($postID)
			{
				$count = get_post_meta($postID, $this->countKey, true);
				if ($count == ''):
						$count = 1;
						delete_post_meta($postID, $this->countKey);
						add_post_meta($postID, $this->countKey, $count);
					else:
						$count++;
						update_post_meta($postID, $this->countKey, $count);
				endif;
			}


			/**
			 * HOOK INTO POST CONTENT
			 */
			public function updateContent($content)
			{
				if (is_singular('post'))
					$content = '<input type="hidden" name="'.$this->countKey.'" id="'.$this->countKey.'" value="'.get_the_id().'" />'.$content;

				return $content;
			}


			// ADD NEW COLUMN
			public function viewCountColumnHead($defaults) {
				$defaults[$this->countSlug] = __('Views');
				return $defaults;
			}

			// SHOW THE FEATURED IMAGE
			public function viewCountColumnContent($column_name, $postID) {
				if ($column_name == $this->countSlug) {
					echo get_post_meta($postID, $this->countKey, true) != '' ? get_post_meta($postID, $this->countKey, true) : '0';
				}
			}


			/**
			 * CUSTOMIZE COLUMN
			 */
			public function customizeColumn()
			{
				?>
				<style type="text/css" media="screen">
					.column-<?php echo $this->countSlug; ?> {
						width: 5rem;
					}
				</style>
				<?php
			}


			/**
			 * SORTABLE VIEW COUNT
			 */
			public function sortableViewCount($columns)
			{
				$columns[$this->countSlug] = $this->countKey;

				return $columns;
			}


			/**
			 * SET SORT META DATA
			 */
			public function sortMetaKey($query)
			{
				// exit if not admin page
				if (!is_admin()) return;

				$orderby = $query->get('orderby');

				if ($this->countKey == $orderby):
					$query->set('meta_key', $this->countKey);
					$query->set('orderby', 'meta_value_num');
				endif;
			}
		}

		// NEW INSTANCE
		new MostReadPosts();

	endif;