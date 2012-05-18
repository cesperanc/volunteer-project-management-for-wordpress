<?php
/*
Plugin Name: Volunteer Project Management
Description: This extension provides a system for managing volunteer projects that depend on download and upload files
Version: 0.1
Author: Cláudio Esperança, Diogo Serra
Author URI: http://dei.estg.ipleiria.pt/
*/

//namespace pt\ipleiria\estg\dei\pi\VolunteerProjectManagement;

if(!class_exists('VolunteerProjectManagement')):
    class VolunteerProjectManagement{
        // Constants
            /**
             * The query param to which this plugin will respond to 
             */
            const URL_QUERY_PARAM = 'volunteer-to';
            
            /**
             * The post type for the Volunteer Project 
             */
            const POST_TYPE = 'vpm-project';

            /**
            * The database variable name to store the plugin database version
            */
            const DB_VERSION_FIELD_NAME = 'VolunteerProjectManagement_Database_version';
            const STATUS_online = 'vpm-online';
            const STATUS_finished = 'vpm-finished';
            const STATUS_scheduled = 'vpm-scheduled';
            const STATUS_unavailable = 'vpm-unavailable';
            
            
            // Table variables
            private static $startDate = '_startDate';
            private static $endDate = '_endDate';

        // Methods
            /**
             * Class constructor 
             */
            public function __construct(){

            }
            
            /**
             * Load the plugin language pack, and register the post type for the Volunteer Projects
             */
            public function _init(){
                load_plugin_textdomain(__CLASS__, false, dirname(plugin_basename(__FILE__)).'/langs');
                
                register_post_type( self::POST_TYPE,
                    array(
                        'hierarchical' => true,
                        'labels' => array(
                            'name' => __('Vol. Projects', __CLASS__),
                            'singular_name' => __('Vol. Project', __CLASS__),
                            'add_new' => __('Add new', __CLASS__),
                            'add_new_item' => __('Add new Volunteer Project', __CLASS__),
                            'edit_item' => __('Edit Volunteer Project', __CLASS__),
                            'new_item' => __('New Volunteer Project', __CLASS__),
                            'view_item' => __('View Volunteer Project', __CLASS__),
                            'search_items' => __('Search Volunteer Projects', __CLASS__),
                            'not_found' => __('No Volunteer Project found', __CLASS__),
                            'not_found_in_trash' => __('No Volunteer Projects were found on the recycle bin', __CLASS__)
                        ),
                        'description' => __('Volunteer Projects', __CLASS__),
                        'has_archive' => false,
                        'public' => true,
                        'publicly_queryable' => true,
                        'exclude_from_search' => true,
                        'show_ui' => true,
                        'show_in_menu' => true,
                        'show_in_nav_menus'=>true,
                        'supports'=>array('title', 'revisions'),
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
            * Get the post from the parameter or the main loop
            * 
            * @param int|object $post to get the post from
            * @return object with the post
            */
            private static function getPost($post){
                if ( is_int($post) && absint( $post ))
                    $post =& get_post($post);
                if ( !is_object($post) )
                    $post =& get_post(@get_the_ID());

                return $post;
            }

            /**
            * Get the post ID from the parameter or the main loop
            * @param int|object $post to get the post from
            * @return int with the post ID 
            */
            private static function getPostID($post){
                if($post = self::getPost($post))
                    return $post->ID;
                return 0;
            }

            /**
            * Set a custom value associated with a post
            * 
            * @param string $key with the key name
            * @param int|object $post with the post
            * @param string value with the value to associate with the key in the post
            */
            private static function setPostCustomValues($key, $value='', $post=0){
                update_post_meta(self::getPostID($post), __CLASS__.$key, $value);
            }

            /**
            * Get a custom value associated with a post
            * 
            * @param string $key with the key name
            * @param int|object $post with the post
            * @return string value for the key or boolean false if the key was not found
            */
            private static function getPostCustomValues($key, $post=0){
                $value = get_post_custom_values(__CLASS__.$key, self::getPostID($post));
                return (!empty($value) && isset($value[0]))?$value[0]:false;
            }
            

            /**
            * Register the scripts to be loaded on the backoffice, on our custom post type
            */
            public function adminEnqueueScripts() {
                if (is_admin() && ($current_screen = get_current_screen()) && $current_screen->post_type == self::POST_TYPE /*&& $current_screen->base=='post'*/):
                    // Register the scripts
                    wp_enqueue_script('ui-spinner', plugins_url('js/jquery-ui/ui.spinner.min.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse'), '1.20');
                    wp_enqueue_script(__CLASS__ . '_admin', plugins_url('js/admin.js', __FILE__), array('jquery-ui-datepicker', 'ui-spinner'), '1.0');

                    // Localize the script
                    wp_localize_script(__CLASS__ . '_admin', 'vpmAdmin', array(
                        'closeText' => __('Done', __CLASS__),
                        'currentText' => __('Today', __CLASS__),
                        'dateFormat' => __('mm/dd/yy', __CLASS__),
                        'dayNamesSunday' => __('Sunday', __CLASS__),
                        'dayNamesMonday' => __('Monday', __CLASS__),
                        'dayNamesTuesday' => __('Tuesday', __CLASS__),
                        'dayNamesWednesday' => __('Wednesday', __CLASS__),
                        'dayNamesThursday' => __('Thursday', __CLASS__),
                        'dayNamesFriday' => __('Friday', __CLASS__),
                        'dayNamesSaturday' => __('Saturday', __CLASS__),
                        'dayNamesMinSu' => __('Su', __CLASS__),
                        'dayNamesMinMo' => __('Mo', __CLASS__),
                        'dayNamesMinTu' => __('Tu', __CLASS__),
                        'dayNamesMinWe' => __('We', __CLASS__),
                        'dayNamesMinTh' => __('Th', __CLASS__),
                        'dayNamesMinFr' => __('Fr', __CLASS__),
                        'dayNamesMinSa' => __('Sa', __CLASS__),
                        'dayNamesShortSun' => __('Sun', __CLASS__),
                        'dayNamesShortMon' => __('Mon', __CLASS__),
                        'dayNamesShortTue' => __('Tue', __CLASS__),
                        'dayNamesShortWed' => __('Wed', __CLASS__),
                        'dayNamesShortThu' => __('Thu', __CLASS__),
                        'dayNamesShortFri' => __('Fri', __CLASS__),
                        'dayNamesShortSat' => __('Sat', __CLASS__),
                        'monthNamesJanuary' => __('January', __CLASS__),
                        'monthNamesFebruary' => __('February', __CLASS__),
                        'monthNamesMarch' => __('March', __CLASS__),
                        'monthNamesApril' => __('April', __CLASS__),
                        'monthNamesMay' => __('May', __CLASS__),
                        'monthNamesJune' => __('June', __CLASS__),
                        'monthNamesJuly' => __('July', __CLASS__),
                        'monthNamesAugust' => __('August', __CLASS__),
                        'monthNamesSeptember' => __('September', __CLASS__),
                        'monthNamesOctober' => __('October', __CLASS__),
                        'monthNamesNovember' => __('November', __CLASS__),
                        'monthNamesDecember' => __('December', __CLASS__),
                        'monthNamesShortJan' => __('Jan', __CLASS__),
                        'monthNamesShortFeb' => __('Feb', __CLASS__),
                        'monthNamesShortMar' => __('Mar', __CLASS__),
                        'monthNamesShortApr' => __('Apr', __CLASS__),
                        'monthNamesShortMay' => __('May', __CLASS__),
                        'monthNamesShortJun' => __('Jun', __CLASS__),
                        'monthNamesShortJul' => __('Jul', __CLASS__),
                        'monthNamesShortAug' => __('Aug', __CLASS__),
                        'monthNamesShortSep' => __('Sep', __CLASS__),
                        'monthNamesShortOct' => __('Oct', __CLASS__),
                        'monthNamesShortNov' => __('Nov', __CLASS__),
                        'monthNamesShortDec' => __('Dec', __CLASS__),
                        'nextText' => __('Next', __CLASS__),
                        'prevText' => __('Prev', __CLASS__),
                        'weekHeader' => __('Wk', __CLASS__)
                    ));
                endif;
            }

            /**
            * Register the styles to be loaded on the backoffice on our custom post type
            */
            public function adminPrintStyles() {
                if (is_admin() && ($current_screen = get_current_screen()) && $current_screen->post_type == self::POST_TYPE):
                    wp_enqueue_style('jquery-ui-core', plugins_url('css/jquery-ui/smoothness/jquery.ui.core.css', __FILE__), array(), '1.8.20');
                    wp_enqueue_style('jquery-ui-datepicker', plugins_url('css/jquery-ui/smoothness/jquery.ui.datepicker.css', __FILE__), array('jquery-ui-core'), '1.8.20');
                    wp_enqueue_style('jquery-ui-theme', plugins_url('css/jquery-ui/smoothness/jquery.ui.theme.css', __FILE__), array('jquery-ui-core'), '1.8.20');
                    wp_enqueue_style('ui-spinner', plugins_url('css/jquery-ui/ui.spinner.css', __FILE__), array(), '1.20');
                endif;
            }

            /**
            * Add a metabox to the project post type
            */
            public function addMetaBox() {
                // Replace the submit core metabox by ours
                add_meta_box(__CLASS__.'-meta', __('Project Volunteer configuration'), array(__CLASS__, 'writeSettingsMetaBox'), self::POST_TYPE, 'advanced', 'core');
                
            }
            /**
            * Output a custom metabox for saving the post
            * @param Object $post 
            */
            public static function writeSettingsMetaBox($post) {
                $post_type = $post->post_type;
                $post_type_object = get_post_type_object($post_type);
                $can_publish = current_user_can($post_type_object->cap->publish_posts);
                    $postId = get_the_ID();
                    

                    // Retrieve the campaign date and time interval (and convert them back to the localtime)
                        $startDate = self::getStartDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                    
                    
                    
                        // Retrieve the campaign date and time interval (and convert them back to the localtime)
                        $startDate = self::getStartDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));

                        // Extract the hours from the timestamp
                        //if(!self::hasStartDate($post)):
                            $startHours = array('0');
                            $startMinutes = array('00');
                        /*else:
                            $startHours = array(date('G', $startDate));
                            $startMinutes = array(date('i', $startDate));
                        endif;*/

                        // Extract the minutes from the timestamp
                        //if(!self::hasEndDate($post)):
                            $endHours = array('0');
                            $endMinutes = array('00');
                        /*else:
                            $endHours = array(date('G', $endDate));
                            $endMinutes = array(date('i', $endDate));
                        endif;*/
                    
                        $upload_project_file_id    = get_post_meta($post->ID, '$upload_project_file_id', true);
                        
                        
                        
                        wp_nonce_field(plugin_basename(__FILE__), 'wp_custom_attachment_nonce');  
  
                        $html = ' 
                    <p class="description">';  
                            $html .= 'Upload your PDF here.';  
                        $html .= ' 

                    ';  
                        $html .= ' 
                    <input type="file" id="wp_custom_attachment" name="wp_custom_attachment" value="" size="25">';  

                        echo $html;  
                        
                        
                    ?>
                            


                            <div id="vpm-upload-container">
                                <label><?php _e('Upload the file project', __CLASS__) ?></label>
				<input id="upload_project_file" type="file" size="36" name="upload_project_file" value="" />
				<!--<input id="upload_project_file_button" type="button" value="<?php _e('Upload File', __CLASS__);?>" />-->
                                
                                <?php
                                if(!empty($upload_project_file_id) && $upload_project_file_id != '0') {
                                    echo '<p><a href="' . wp_get_attachment_url($upload_project_file_id) . '">View document</a></p>';
                                }
                                ?>
                            </div>
			
                        <fieldset id="vpm-enable-startdate-container" class="vpm-enable-container">
                            <div id="vpm-startdate-container">
                                <label class="selectit"><?php _e('Start date:', __CLASS__); ?> <input style="width: 6em;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the start date when the project is supposed to start', __CLASS__) ?>" id="vpm-startdate" type="text" /></label>
                                <input id="vpm-hidden-startdate" type="hidden" name="<?php echo(__CLASS__ . self::$startDate); ?>" value="<?php echo(date('Y-n-j', $startDate)); ?>" />
                                @<input title="<?php esc_attr_e('Specify the project starting hours', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-starthours" name="<?php echo(__CLASS__ . '_startHours'); ?>" type="text" value="<?php echo($startHours[0]); ?>" />:<input title="<?php esc_attr_e('Specify the volunteer starting minutes', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-startminutes" name="<?php echo(__CLASS__ . '_startMinutes'); ?>" type="text" value="<?php echo($startMinutes[0]); ?>" />
                            </div>
                        </fieldset>
                        <fieldset id="vpm-enable-enddate-container" class="vpm-enable-container">
                            <div id="vpm-enddate-container">
                                <label class="selectit"><?php _e('End date:', __CLASS__); ?> <input style="width: 6em;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the end date when the project is supposed to end', __CLASS__) ?>" id="vpm-enddate" type="text" name="<?php echo(__CLASS__ . self::$endDate); ?>" /></label>
                                <input id="vpm-hidden-enddate" type="hidden" name="<?php echo(__CLASS__ . self::$endDate); ?>" value="<?php echo(date('Y-n-j', $endDate)); ?>" />
                                @<input title="<?php esc_attr_e('Specify the volunterr ending hours', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-endhours" name="<?php echo(__CLASS__ . '_endHours'); ?>" type="text" value="<?php echo($endHours[0]); ?>" />:<input title="<?php esc_attr_e('Specify the project ending minutes', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-endminutes" name="<?php echo(__CLASS__ . '_endMinutes'); ?>" type="text" value="<?php echo($endMinutes[0]); ?>" />
                            </div>
                        </fieldset>			


                    <?php
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

                        
                        
                        // Make sure the file array isn't empty
                        if(!empty($_FILES['wp_custom_attachment']['name'])) {

                                // Setup the array of supported file types. In this case, it's just PDF.
                                // @todo  we want more?
                                $supported_types = array('application/pdf');

                                // Get the file type of the upload
                                $arr_file_type = wp_check_filetype(basename($_FILES['wp_custom_attachment']['name']));
                                $uploaded_type = $arr_file_type['type'];

                                // Check if the type is supported. If not, throw an error.
                                if(in_array($uploaded_type, $supported_types)) {

                                        // Use the WordPress API to upload the file
                                        $upload = wp_upload_bits($_FILES['wp_custom_attachment']['name'], null, file_get_contents($_FILES['wp_custom_attachment']['tmp_name']));
                                        if(isset($upload['error']) && $upload['error'] != 0) {
                                                wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                                        } else {
                                                add_post_meta($postId, 'wp_custom_attachment', $upload);
                                                update_post_meta($postId, 'wp_custom_attachment', $upload);
                                        } // end if/else
                                } else {
                                        wp_die("The file type that you've uploaded is not a PDF.");
                                } // end if/else

                        } // end if             
                        
                        
                        
                        
                        break;
                endswitch;
                return $postId;
            }

            /**
            * Get the start date of a specific project
            * 
            * @param int|object $post
            * @return int with timestamp of the start date
            */
            public static function getStartDate($post=0){
                $date = self::getPostCustomValues(self::$startDate, $post);
                return (int)($date===false?current_time('timestamp', false):$date);
            }

            /**
            * Get the end date of a specific campaign
            * 
            * @param int|object $post
            * @return int with timestamp of the end date
            */
            public static function getEndDate($post=0){
                $date = self::getPostCustomValues(self::$endDate, $post);
                // Default is set to current date plus a day
                return (int)($date===false?current_time('timestamp', false)+3600*24:$date);
            }
            
            /**
             * Install the database tables
             */
            public function install(){

                // Load the libraries
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                
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
            
            /*
             * Change form encode type
             */

            function post_edit_form_tag( ) {
                echo ' enctype="multipart/form-data"';
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
        function edit_columns($columns)
		{
			$columns = array(
				"cb" => "<input type=\"checkbox\" />",
				"title" => "Vol. Projects",
				"date" => "Data",
				"vpm_project_file" => "File",
			);
			return $columns;
		}

function custom_columns($column)
	{
		global $post;
		switch ($column)
		{
			case "vpm_project_file":
				$custom = get_post_custom();
				echo $custom["upload_project_file"];
		}
	}

            /**
             * Register the plugin functions with the Wordpress hooks
             */
            public static function init(){
                
$prefix = self::getWpDB()->prefix;
                // Register the install database method to be executed when the plugin is activated
                register_activation_hook(__FILE__, array(__CLASS__, 'install'));

                // Register the install database method to be executed when the plugin is updated
                add_action('plugins_loaded', array(__CLASS__, 'install'));

                // Register the remove database method when the plugin is removed
                register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall'));

                // Register the _init method to the Wordpress initialization action hook
                add_action('init', array(__CLASS__, '_init'));

                // Register the addMetaBox method to the Wordpress backoffice administration initialization action hook
                add_action('admin_init', array(__CLASS__, 'addMetaBox'));

                // Register the savePost method to the Wordpress save_post action hook
                add_action('save_post', array(__CLASS__, 'savePost'));

                // Add thePosts method to filter the_posts
                //add_filter('the_posts', array(__CLASS__, 'thePosts'), 10, 2);

                // Add mapMetaCapabilities method to filter map_meta_cap  // Maybe we can use this in the module 2
                //add_filter('map_meta_cap', array(__CLASS__, 'mapMetaCapabilities'), 10, 4);

                // Register the adminEnqueueScripts method to the Wordpress admin_enqueue_scripts action hook
                add_action('admin_enqueue_scripts', array(__CLASS__, 'adminEnqueueScripts'));

                // Register the adminPrintStyles method to the Wordpress admin_print_styles action hook
                add_action('admin_print_styles', array(__CLASS__, 'adminPrintStyles'));
                
                // Register the form tag so we can upload files
                add_action( 'post_edit_form_tag' , array(__CLASS__, 'post_edit_form_tag') );
            }


		
        }
endif;

VolunteerProjectManagement::init();
