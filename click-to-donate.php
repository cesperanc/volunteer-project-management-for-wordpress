<?php
/*
Plugin Name: Click-to-donate
Description: This extension provides a system for managing image advertising campaigns based on visits of visitors
Version: 0.1
Author: Cláudio Esperança, Diogo Serra
Author URI: http://dei.estg.ipleiria.pt/
*/

//namespace pt\ipleiria\estg\dei\pi\ClickToDonate;

if(!class_exists('ClickToDonate')):
    class ClickToDonate{
        // Constants
            /**
             * The query param to which this plugin will respond to 
             */
            const URL_QUERY_PARAM = 'donate-to';
            
            /**
             * The post type for the campaigns 
             */
            const POST_TYPE = 'ctd-campaign';
            
            
            const DB_VERSION_FIELD_NAME = 'ClickToDonate_Database_version';

        // Methods
            /**
             * Class constructor 
             */
            public function __construct(){

            }
            
            /**
             * Load the plugin language pack, and register the post type for the campaigns
             */
            public function _init(){
                load_plugin_textdomain(__CLASS__, false, dirname(plugin_basename(__FILE__)).'/langs');
                
                register_post_type( self::POST_TYPE,
                    array(
                        'hierarchical' => true,
                        'labels' => array(
                            'name' => __('Campaigns', __CLASS__),
                            'singular_name' => __('Campaign', __CLASS__),
                            'add_new' => __('Add new', __CLASS__),
                            'add_new_item' => __('Add new campaign', __CLASS__),
                            'edit_item' => __('Edit campaign', __CLASS__),
                            'new_item' => __('New campaign', __CLASS__),
                            'view_item' => __('View campaign', __CLASS__),
                            'search_items' => __('Search campaigns', __CLASS__),
                            'not_found' => __('No campaign found', __CLASS__),
                            'not_found_in_trash' => __('No campaigns were found on the recycle bin', __CLASS__)
                        ),
                        'description' => __('Click to donate campaigns', __CLASS__),
                        'has_archive' => false,
                        'public' => true,
                        'publicly_queryable' => true,
                        'exclude_from_search' => true,
                        'show_ui' => true,
                        'show_in_menu' => true,
                        'show_in_nav_menus'=>true,
                        'supports'=>array('title', 'editor', 'thumbnail', 'revisions'),
                        'rewrite' => array(
                            'slug' => self::URL_QUERY_PARAM,
                            'with_front'=>'false'
                        ),
                        'query_var' => true,
                        'capability_type' => 'page',
                    )
                );
            }
            
            /**
             * Filter the posts content if they are campaigns, or count the visualizations
             * 
             * @param array $posts
             * @param WP_Query $query
             * @return array with the (possible) filtered posts 
             */
            function thePosts( $posts, $query ) {
                if ( empty( $posts ))
                    return $posts;
                
                foreach($posts as $index=>$post):
                    if(get_post_type($post)==self::POST_TYPE):
                        // @TODO Implement the code to verify if the banner can be shown and register the click. If the banner couldn't not be shown, replace the content by the error message
                        $views = get_post_custom_values(__CLASS__.'_views', $post->ID);
                        if(!empty($views) && isset($views[0])):
                            $views = $views[0];
                        endif;
                        update_post_meta($post->ID, __CLASS__.'_views', ((int)$views+1));
                        $posts[$index]->post_content.="<hr/>Clique contabilizado";
                    endif;
                endforeach;
                
                return $posts;
            }
            
            /**
             * Add a metabox to the campaign post type
             */
            public function addMetaBox(){
                add_meta_box(__CLASS__.'-meta', __('Campaign configuration', __CLASS__), function(){
                    $postId = get_the_ID();

                    $views = get_post_custom_values(__CLASS__.'_views', $postId);
                    if(!empty($views) && isset($views[0])):
                        $views = $views[0];
                    endif;

                    ?>
                        <div>
                            <?php printf(__( 'Views: %s', __CLASS__), $views); ?>
                        </div>
                    <?php
                }, self::POST_TYPE, 'advanced', 'high');
            }
            
            /**
             * Save the custom data from the metaboxes with the custom post type
             * 
             * @param int $postId
             * @return int with the post id
             */
            public function savePost($postId){
                if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ):
                    return $postId;
                endif;
                switch(get_post_type($postId)):
                    case self::POST_TYPE:
//                        // Get the posted data
//                        $field = isset($_POST[__CLASS__.'_fieldname'])?$_POST[__CLASS__.'_fieldname']:'';
//                        // Save the object in the database
//                        update_post_meta($postId, __CLASS__.'_fieldname', $field);

                        break;
                endswitch;
                return $postId;
            }
            
            /**
             * Install the database tables
             */
            public function install(){
                
                // Load the plugin version
                $plugin = get_plugin_data(__FILE__);
                $version = $plugin['Version'];
                
                // Compare the plugin version with the local version, and update the database tables accordingly
                if(version_compare(get_option(self::DB_VERSION_FIELD_NAME), $version, '<')):
                    
                    // Remove the previous version of the database (fine by now, but should be reconsidered in future versions)
                    //call_user_func(array(__CLASS__, 'uninstall'));
                    
                    // Get the WordPress database abstration layer instance
                    $wpdb = self::getWpDB();
                    
                    // Set the charset collate
                    $charset_collate = '';
                    if (!empty($wpdb->charset)):
                        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
                    endif;
                    if (!empty($wpdb->collate)):
                        $charset_collate .= " COLLATE {$wpdb->collate}";
                    endif;
                    
                    // Prepare the SQL queries
                    $queries = array();
                    
                    /*
                    $queries[] = "
                        CREATE TABLE `{$instance->TABLE_FILE_METADATA}` (
                            `{$instance->TABLE_FILE_METADATA_ID}` bigint(20) NOT NULL AUTO_INCREMENT,
                            `{$instance->TABLE_FILE_METADATA_PARENT}` bigint(20) DEFAULT NULL,
                            `{$instance->TABLE_FILE_METADATA_TIME}` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                            `{$instance->TABLE_FILE_METADATA_NAME}` text NOT NULL,
                            `{$instance->TABLE_FILE_METADATA_MIME_TYPE}` varchar(32),
                            `{$instance->TABLE_FILE_METADATA_ETAG}` tinytext,
                            `{$instance->TABLE_FILE_METADATA_SIZE}` bigint(10) DEFAULT '0',
                            `{$instance->TABLE_FILE_METADATA_USER}` bigint(20) DEFAULT '0',
                            PRIMARY KEY (`{$instance->TABLE_FILE_METADATA_ID}`),
                            KEY `{$instance->TABLE_FILE_METADATA_USER}` (`{$instance->TABLE_FILE_METADATA_USER}`),
                            KEY `{$instance->TABLE_FILE_METADATA_PARENT}` (`{$instance->TABLE_FILE_METADATA_PARENT}`)
                        ) ENGINE=InnoDB {$charset_collate} COMMENT='Database files metatable';
                    ";

                    $queries[] = "
                        ALTER TABLE `{$instance->TABLE_FILE_METADATA}`
                            ADD CONSTRAINT `{$instance->TABLE_FILE_METADATA}_{$instance->TABLE_FILE_METADATA_PARENT}` FOREIGN KEY (`{$instance->TABLE_FILE_METADATA_PARENT}`) REFERENCES `{$instance->TABLE_FILE_METADATA}` (`{$instance->TABLE_FILE_METADATA_ID}`) ON DELETE SET NULL ON UPDATE CASCADE
                        ;
                    ";

                    $queries[] = "
                        CREATE TABLE `{$instance->TABLE_FILE_DATA}` (
                            `{$instance->TABLE_FILE_DATA_ID}` bigint(20) NOT NULL AUTO_INCREMENT,
                            `{$instance->TABLE_FILE_DATA_METADATA}` bigint(20) NOT NULL,
                            `{$instance->TABLE_FILE_DATA_ORDER}` bigint(20) NOT NULL DEFAULT '0',
                            `{$instance->TABLE_FILE_DATA_DATA}` longblob NOT NULL,
                            `{$instance->TABLE_FILE_DATA_PREVIOUS}` bigint(20) DEFAULT NULL,
                            `{$instance->TABLE_FILE_DATA_NEXT}` bigint(20) DEFAULT NULL,
                            PRIMARY KEY  (`{$instance->TABLE_FILE_DATA_ID}`),
                            UNIQUE KEY `unique_order` (`{$instance->TABLE_FILE_DATA_METADATA}`,`{$instance->TABLE_FILE_DATA_ORDER}`),
                            UNIQUE KEY `{$instance->TABLE_FILE_DATA_NEXT}` (`{$instance->TABLE_FILE_DATA_NEXT}`),
                            UNIQUE KEY `{$instance->TABLE_FILE_DATA_PREVIOUS}` (`{$instance->TABLE_FILE_DATA_PREVIOUS}`)
                        ) ENGINE=InnoDB {$charset_collate} COMMENT='Database files data table';
                    ";

                    $queries[] = "
                        ALTER TABLE `{$instance->TABLE_FILE_DATA}`
                            ADD CONSTRAINT `{$instance->TABLE_FILE_DATA}_meta` FOREIGN KEY (`file_metadata`) REFERENCES `{$instance->TABLE_FILE_METADATA}` (`{$instance->TABLE_FILE_METADATA_ID}`) ON DELETE CASCADE ON UPDATE CASCADE,
                            "."
                            ADD CONSTRAINT `{$instance->TABLE_FILE_DATA}_{$instance->TABLE_FILE_DATA_NEXT}` FOREIGN KEY (`{$instance->TABLE_FILE_DATA_NEXT}`) REFERENCES `{$instance->TABLE_FILE_DATA}` (`{$instance->TABLE_FILE_DATA_ID}`) ON DELETE CASCADE ON UPDATE CASCADE
                        ;
                    ";
                    */

                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    dbDelta($queries);

                    update_option(self::DB_VERSION_FIELD_NAME, $version);
                endif;
            }
            
            /**
             * Uninstall the plugin data
             */
            function uninstall(){
                // Get the WordPress database abstration layer instance
                $wpdb = self::getWpDB();
                    
//                //$wpdb->query("DROP TRIGGER IF EXISTS `{$instance->TABLE_FILE_DATA}_set_order`;");
//                $wpdb->query("ALTER TABLE `{$instance->TABLE_FILE_DATA}` DROP FOREIGN KEY `{$instance->TABLE_FILE_DATA}_meta`;");
//                $wpdb->query("ALTER TABLE `{$instance->TABLE_FILE_DATA}` DROP FOREIGN KEY `{$instance->TABLE_FILE_DATA}_{$instance->TABLE_FILE_DATA_NEXT}`;");
//                $wpdb->query("ALTER TABLE `{$instance->TABLE_FILE_METADATA}` DROP FOREIGN KEY `{$instance->TABLE_FILE_METADATA}_{$instance->TABLE_FILE_METADATA_PARENT}`;");
//                $wpdb->query("DROP TABLE IF EXISTS {$instance->TABLE_FILE_DATA};");
//                $wpdb->query("DROP TABLE IF EXISTS {$instance->TABLE_FILE_METADATA};");
                
                // Remove the plugin version information
                delete_option(self::DB_VERSION_FIELD_NAME);
                
                // Remove all the campaigns
                self::removePostType();
            }
            
            
        // Static methods
            /**
             * Remove the custom post type for this plugin
             * 
             * @global array $wp_post_types with all the custom post types
             * @return boolean true on success, false otherwise
             */
            private static function removePostType() {
                global $wp_post_types;
                
                $posts = get_posts( array(
                    'post_type' => self::POST_TYPE,
                    'posts_per_page' => -1,
                    'nopaging' => true
                ) );
                
                foreach ($posts as $post):
                    wp_delete_post($post->ID, true);
                endforeach;
                
                
                if ( isset( $wp_post_types[ self::POST_TYPE ] ) ):
                    unset( $wp_post_types[ self::POST_TYPE ] );
                    return true;
                endif;
                return false;
            }
            
            /**
             * Return the WordPress Database Access Abstraction Object 
             * 
             * @global wpdb $wpdb
             * @return wpdb 
             */
            public static function getWpDB(){
                global $wpdb;
                
                return $wpdb;
            }
        
            /**
             * Register the plugin functions with the Wordpress hooks
             */
            public static function init(){
                // Register the install database method to be executed when the plugin is activated
                register_activation_hook(__FILE__,array(__CLASS__, 'install'));

                // Register the remove database method when the plugin is removed
                register_uninstall_hook(__FILE__,array(__CLASS__, 'uninstall'));

                // Register the _init method to the Wordpress initialization action hook
                add_action('init', array(__CLASS__, '_init'));
                
                // Register the addMetaBox method to the Wordpress backoffice administration initialization action hook
                add_action('admin_init', array(__CLASS__, 'addMetaBox'));
                
                // Register the savePost method to the Wordpress save_post action hook
                add_action('save_post', array(__CLASS__, 'savePost'));
                
                // Add thePosts method to filter the_posts
                add_filter('the_posts', array(__CLASS__, 'thePosts'), 10, 2);
            }
        }
endif;

ClickToDonate::init();