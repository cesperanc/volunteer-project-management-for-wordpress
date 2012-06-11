<?php
/*
Plugin Name: Volunteer Project Management
Description: This extension provides a system for managing volunteer projects that depend on download and upload files
Version: 0.9
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
             * The default user role that can contribute
             * http://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table
             * We will use the author as default because is the lower role that can upload files 
             */
            const DEFAULT_CAP = 'upload_files';
            const CONTRIBUTION_CAP = 'vpm-CanContribute';

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
            private static $downloadCounter = '_downloadCounter';
            private static $projectFile = '_projectFile';
            private static $num = 0;
            private static $enableStartDate = '_enable_startDate';
            private static $enableEndDate = '_enable_endDate';

        // Methods
            /**
             * Class constructor 
             */
            public function __construct(){

            }
            
            /**
             * Working role for the plugin
             * 
             */
            public function getAllowedCap(){
                $options = get_option('vpmOptions');
                if(isset($options['cap_check']) && $options['cap_check']!=''){
                    return self::CONTRIBUTION_CAP;
                }
                return self::DEFAULT_CAP;
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
                        'publicly_queryable' => false,
                        'exclude_from_search' => true,
                        'show_ui' => true,
                        'show_in_menu' => true,
                        'show_in_nav_menus'=>true,
                        'supports'=>array('title', 'editor', 'page-attributes'),
                        'rewrite' => array(
                            'slug' => self::URL_QUERY_PARAM,
                            'with_front'=>'false'
                        ),
                        'query_var' => true/*,
                        'capability_type' => 'page'*/
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
            public static function setPostCustomValues($key, $value='', $post=0){
                update_post_meta(self::getPostID($post), __CLASS__.$key, $value);
            }

            /**
            * Get a custom value associated with a post
            * 
            * @param string $key with the key name
            * @param int|object $post with the post
            * @return string value for the key or boolean false if the key was not found
            */
            public static function getPostCustomValues($key, $post=0){
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
                    wp_localize_script(__CLASS__ . '_admin', 'vpmAdminL10n', array(
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
                wp_enqueue_script(__CLASS__ . '_plugin_options', plugins_url('js/options.js', __FILE__));
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
                wp_enqueue_style('admin', plugins_url('css/admin.css', __FILE__), array(), '1.0');
            }

            /**
            * Add a metabox to the project post type
            */
            public function addMetaBox() {
                add_meta_box(__CLASS__.'-meta', __('Project Volunteer configuration'), array(__CLASS__, 'writeSettingsMetaBox'), self::POST_TYPE, 'advanced', 'core');
                //self::list_hooked_functions();
            }
            /**
            * Output a custom metabox for saving the post
            * @param Object $post 
            */
            public static function writeSettingsMetaBox($post) {
                $post_type = $post->post_type;
                $post_type_object = get_post_type_object($post_type);
                    $postId = get_the_ID();                  
                    
                        // Retrieve the campaign date and time interval (and convert them back to the localtime)
                        $startDate = self::getStartDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        $downloadCounter = self::getPostCustomValues(self::$downloadCounter, $post);
                        // Extract the hours from the timestamp
                        if(!self::hasStartDate($post)):
                            $startHours = array('0');
                            $startMinutes = array('00');
                        else:
                            $startHours = array(date('G', $startDate));
                            $startMinutes = array(date('i', $startDate));
                        endif;

                        // Extract the minutes from the timestamp
                        if(!self::hasEndDate($post)):
                            $endHours = array('0');
                            $endMinutes = array('00');
                        else:
                            $endHours = array(date('G', $endDate));
                            $endMinutes = array(date('i', $endDate));
                        endif;
                    ?>
                        
                        <div id="vpm-upload-container">
                            <label><?php _e('Project file', __CLASS__) ?></label>
                            <?php echo self::projectFile($postId); ?>
                        </div>

                        <fieldset id="vpm-enable-startdate-container" class="vpm-enable-container">
                            <legend>
                                <input id="vpm-enable-startdate" name="<?php echo(__CLASS__ . self::$enableStartDate); ?>" value="enable_startDate"<?php checked(self::hasStartDate($post)); ?> type="checkbox"/><label class="selectit" for="vpm-enable-startdate"><?php _e('Set the project start date', __CLASS__); ?></label>
                            </legend>
                            <div id="vpm-startdate-container" class="start-hidden">
                                <label class="selectit"><?php _e('Start date:', __CLASS__); ?> <input style="width: 6em;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the start date when the project is supposed to start', __CLASS__) ?>" id="vpm-startdate" type="text" /></label>
                                <input id="vpm-hidden-startdate" type="hidden" name="<?php echo(__CLASS__ . self::$startDate); ?>" value="<?php echo(date('Y-n-j', $startDate)); ?>" />
                                @<input title="<?php esc_attr_e('Specify the project starting hours', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-starthours" name="<?php echo(__CLASS__ . '_startHours'); ?>" type="text" value="<?php echo($startHours[0]); ?>" />:<input title="<?php esc_attr_e('Specify the volunteer starting minutes', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-startminutes" name="<?php echo(__CLASS__ . '_startMinutes'); ?>" type="text" value="<?php echo($startMinutes[0]); ?>" />
                            </div>
                        </fieldset>

                        <fieldset id="vpm-enable-enddate-container" class="vpm-enable-container">
                            <legend>
                                <input id="vpm-enable-enddate" name="<?php echo(__CLASS__ . self::$enableEndDate); ?>" value="enable_endDate"<?php checked(self::hasEndDate($post)); ?> type="checkbox"/><label class="selectit" for="vpm-enable-enddate"><?php _e('Set the project end date', __CLASS__); ?></label>
                            </legend>
                            <div id="vpm-enddate-container" class="start-hidden">
                                <label class="selectit"><?php _e('End date:', __CLASS__); ?> <input style="width: 6em;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the end date when the project is supposed to end', __CLASS__) ?>" id="vpm-enddate" type="text" name="<?php echo(__CLASS__ . self::$endDate); ?>" /></label>
                                <input id="vpm-hidden-enddate" type="hidden" name="<?php echo(__CLASS__ . self::$endDate); ?>" value="<?php echo(date('Y-n-j', $endDate)); ?>" />
                                @<input title="<?php esc_attr_e('Specify the volunteer ending hours', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-endhours" name="<?php echo(__CLASS__ . '_endHours'); ?>" type="text" value="<?php echo($endHours[0]); ?>" />:<input title="<?php esc_attr_e('Specify the project ending minutes', __CLASS__) ?>" style="width: 2em;" size="2" maxlength="2" id="vpm-endminutes" name="<?php echo(__CLASS__ . '_endMinutes'); ?>" type="text" value="<?php echo($endMinutes[0]); ?>" />
                            </div>
                        </fieldset>
                        <div id="vpm-downloadCounter-container">
                            <label><?php _e('Number of downloads: ', __CLASS__) ?></label>
                            <?php echo($downloadCounter); ?>
                        </div>

                    <?php
            }
            /**
             * Manage the upload file for the project
             * 
             * @param int $postId
             * @return html
             */
            function projectFile($postId) {

                wp_nonce_field(plugin_basename(__FILE__), __CLASS__ . '_projectFile_nonce');
                

                // Grab the array of file information currently associated with the post
                //$file = self::getPostCustomValues(self::$projectFile, $postId);
                $file = get_post_meta($postId, __CLASS__ . '_projectFile', true);

                $html = '';
                // Display the 'View' and 'Delete' option if a URL to a file exists else upload option
                if(@strlen(trim($file['url'])) > 0) {
                    // Create the input box and set the file's URL as the text element's value
                    $html .= '<input type="hidden" id="'.self::$projectFile . '_url" name="'.__CLASS__ . self::$projectFile.'_url" value=" ' . $file['url'] . '" size="30" />';
                    $html .= '<a href=" ' . $file['url'] . '" id="vpm_projectFile_view" target="_blank">' . __('View') . '</a> ';
                    $html .= '<input type="checkbox" id="vpm_projectFile_delete" name="'.__CLASS__ . self::$projectFile.'_delete" value="deleteFile"/>' . __('Check if you want delete the file');
                }else{
                    $html .= '<input type="file" id="'.self::$projectFile . '" name="'.__CLASS__ . self::$projectFile.'" value="" size="25" />';
                }

                return $html;

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
                        if (isset($_POST[__CLASS__ . self::$enableStartDate])):
                            $enableStartDate = true;

                            $startDate = isset($_POST[__CLASS__ . self::$startDate]) ? ($_POST[__CLASS__ . self::$startDate]) : '';
                            list($year, $month, $day) = explode('-', $startDate);
                            $hours = isset($_POST[__CLASS__ . '_startHours']) ? (int) $_POST[__CLASS__ . '_startHours'] : 0;
                            $minutes = isset($_POST[__CLASS__ . '_startMinutes']) ? (int) $_POST[__CLASS__ . '_startMinutes'] : 0;

                            // Set the timestamp, converting it from local time to UTC
                            $startDate = mktime($hours, $minutes, 0, $month, $day, $year) + (current_time('timestamp', true) - current_time('timestamp', false));
                        else:
                            $enableStartDate = false;
                            $startDate = '';
                        endif;

                        if (isset($_POST[__CLASS__ . self::$enableEndDate])):
                            $enableEndDate = true;
                            $endDate = isset($_POST[__CLASS__ . self::$endDate]) ? ($_POST[__CLASS__ . self::$endDate]) : '';
                            list($year, $month, $day) = explode('-', $endDate);
                            $hours = isset($_POST[__CLASS__ . '_endHours']) ? (int) $_POST[__CLASS__ . '_endHours'] : 0;
                            $minutes = isset($_POST[__CLASS__ . '_endMinutes']) ? (int) $_POST[__CLASS__ . '_endMinutes'] : 0;

                            // Set the timestamp, converting it from local time to UTC
                            $endDate = mktime($hours, $minutes, 0, $month, $day, $year) + (current_time('timestamp', true) - current_time('timestamp', false));
                        else:
                            $enableEndDate = false;
                            $endDate = '';
                        endif;
                        
                        self::enableStartDate($postId, $enableStartDate);
                        self::setStartDate($postId, $startDate);
                        self::enableEndDate($postId, $enableEndDate);
                        self::setEndDate($postId, $endDate);

                        
                        // Make sure the file array isn't empty
                        if(!empty($_FILES[__CLASS__ . '_projectFile']['name'])) {

                                // Setup the array of supported file types. In this case, it's just PDF.
                                // @todo  we want more?
                                $supported_types = array('application/pdf');

                                // Get the file type of the upload
                                $arr_file_type = wp_check_filetype(basename($_FILES[__CLASS__ . self::$projectFile]['name']));
                                $uploaded_type = $arr_file_type['type'];

                                // Check if the type is supported. If not, throw an error.
                                if(in_array($uploaded_type, $supported_types)) {
                                        // Use the WordPress API to upload the file
                                        $upload = wp_upload_bits($_FILES[__CLASS__ . self::$projectFile]['name'], null, file_get_contents($_FILES[__CLASS__ . '_projectFile']['tmp_name']));
                                        __CLASS__ . self::$num++;
                                        if(isset($upload['error']) && $upload['error'] != 0) {
                                                wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                                        } else {
                                                add_post_meta($postId, __CLASS__ . self::$projectFile, $upload);
                                                update_post_meta($postId, __CLASS__ . self::$projectFile, $upload);
                                                // When we update the file we reset the counter
                                                self::setPostCustomValues(self::$downloadCounter, 0);
                                                
                                        } // end if/else
                                } else {
                                        wp_die("The file type that you've uploaded is not a PDF.");
                                } // end if/else
                                //echo "<pre>".print_r($_FILES,true)."</pre>";
                                //wp_die("fez upload...");//$_FILES = null;
                                //error_log("------ ".__CLASS__ . self::$num++." ------------");
                        }else{  
                            
                            if($_POST[__CLASS__ . self::$projectFile.'_delete']=="deleteFile"){

                                // Grab a reference to the file associated with this post  
                                $doc = get_post_meta($postId, __CLASS__ . self::$projectFile, true); 

                                // Grab the value for the URL to the file stored in the text element 
                                $delete_flag = get_post_meta($postId, __CLASS__ . self::$projectFile.'_url', true); 

                                // Determine if a file is associated with this post and if the delete flag has been set (by clearing out the input box) 
                                if(strlen(trim($doc['url'])) > 0 && strlen(trim($delete_flag)) == 0) { 

                                    // Attempt to remove the file. If deleting it fails, print a WordPress error. 
                                    if(unlink($doc['file'])) { 

                                        // Delete succeeded so reset the WordPress meta data 
                                        update_post_meta($postId, __CLASS__ . self::$projectFile, null); 
                                        update_post_meta($postId, __CLASS__ . self::$projectFile.'_url', ''); 

                                    } else { 
                                        wp_die('There was an error trying to delete your file.'); 
                                    } // end if/el;se 

                                } // end if 
                            }

                        } // end if/else             
                        
                        
                        
                        
                        break;
                endswitch;
                return $postId;
            }
            /**
            * Verify if the post has a start date setting enabled
            * 
            * @param int|object $post
            * @return boolean
            */
            public static function hasStartDate($post = 0) {
                return (boolean) self::getPostCustomValues(self::$enableStartDate, $post);
            }

            /**
            * Enable or disable the start date scheduling
            * 
            * @param int|object $post with the post
            * @param boolean $enable 
            */
            public static function enableStartDate($post = 0, $enable=false) {
                self::setPostCustomValues(self::$enableStartDate, $enable, $post);
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
            * Set the start date for a campaign
            * 
            * @param int|object $post with the post
            * @param int $date 
            */
            public static function setStartDate($post = 0, $date=0) {
                self::setPostCustomValues(self::$startDate, $date, $post);
            }
            /**
            * Verify if the post has a end date setting enabled
            * 
            * @param int|object $post
            * @return boolean
            */
            public static function hasEndDate($post = 0) {
                return (boolean) self::getPostCustomValues(self::$enableEndDate, $post);
            }

            /**
            * Enable or disable the end date scheduling
            * 
            * @param int|object $post with the post
            * @param boolean $enable 
            */
            public static function enableEndDate($post = 0, $enable=false) {
                self::setPostCustomValues(self::$enableEndDate, $enable, $post);
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
            * Set the end date for the campaign
            * 
            * @param int|object $post with the post
            * @param int $date 
            */
            public static function setEndDate($post = 0, $date=0) {
                self::setPostCustomValues(self::$endDate, $date, $post);
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
            
            function vpm_columns($column){
                $columns = array(
                    'cb' => '<input type="checkbox" />',
                    'title' => __( 'Vol. Projects' ),
                    //'vpm_project_file' => __( 'Vol. Projects files'),
                    'vpm_startDate' => __( 'Start Date' ),
                    'vpm_endDate' => __( 'End Date'),
                    'vpm_downloads' => __('Number of downloads')
                );

                return $columns;
            }
function list_hooked_functions($tag=false){
 global $wp_filter;
 if ($tag) {
  $hook[$tag]=$wp_filter[$tag];
  if (!is_array($hook[$tag])) {
  trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);
  return;
  }
 }
 else {
  $hook=$wp_filter;
  ksort($hook);
 }
 echo '<pre>';
 foreach($hook as $tag => $priority){
  echo "<br />&gt;&gt;&gt;&gt;&gt;\t<strong>$tag</strong><br />";
  ksort($priority);
  foreach($priority as $priority => $function){
  echo $priority;
  foreach($function as $name => $properties) echo "\t$name<br />";
  }
 }
 echo '</pre>';
 return;
}
            function vpm_manage_columns( $column, $postId ) {
                global $post;
                switch( $column ) {

                        /* If displaying the 'project file' column. */
                        case 'vpm_project_file' :
                                /* Get the file for the post. */
                                $file = get_post_meta($postId, __CLASS__ . '_projectFile');
                                /* If the file exist. */
                                if ( !empty( $file ) && isset($file[0]['url']) ) {
                                    
                    
                                    echo '<a href="' . add_query_arg( array( 'post_type' => self::POST_TYPE, 'post' => $post->ID, 'action' => 'edit', 'option'=>'downloadFile'  ), admin_url( 'post.php') ) . '" id="vpm_projectFile_download">' ;
                                            _e('Download', __CLASS__);
                                        echo '</a> ';
                                }
                                /* If no file were found, output a default message. */
                                else {
                                        _e( 'No file uploaded' );
                                }
                                break;
                         case 'vpm_startDate':
                            // Retrieve the campaign date and time interval (and convert them back to the localtime)
                            $startDate = self::getStartDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));

                            // Extract the hours from the timestamp
                            if(!$startDate):
                                echo _e("No start date");
                            else:
                                echo date("m-d-Y@H:i:s", $startDate);
                            endif;

                            break;
                        
                        case 'vpm_endDate':
                            
                            // Retrieve the campaign date and time interval (and convert them back to the localtime)
                            $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                            // Extract the minutes from the timestamp
                            if(!$endDate):
                                echo _e("No end date");
                            else:
                                echo date("m-d-Y@H:i:s", $endDate);
                            endif;
                        
                            break;
                        
                        case 'vpm_downloads':
                            echo self::getPostCustomValues(self::$downloadCounter);
                            break;
                                 
                                

                        /* Just break out of the switch statement for everything else. */
                        default :
                                break;
                }
            }
            
            function downloadFile() {
                if(isset($_REQUEST['action']) && $_REQUEST['action']=="downloadFile" && isset($_REQUEST['post'])){
                    if(isset($_REQUEST['post_type']) && $_REQUEST['post_type']== self::POST_TYPE && isset($_REQUEST['post'])){
                        $_REQUEST['post_type'] = '';
                        $post = get_post($_REQUEST['post']);
                        $currentValue = self::getPostCustomValues(self::$downloadCounter,$post);
                        //echo("$$$$$$".self::$projectFile."$$$------------");
                        $file = get_post_meta($post->ID, __CLASS__ . self::$projectFile, true);
                        //echo $file;
                        $urlFile = $file['url'];
                        self::setPostCustomValues(self::$downloadCounter, $currentValue+1,$post);                        
                        
                        //
                        // TODO: make open in new page
                        //Download the file
                        header('Pragma: public');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Cache-Control: private',false);
                        header('Content-Disposition: attachment; filename="'.basename($urlFile).'"');
                        header('Content-Transfer-Encoding: binary');
                        header('Connection: close');
                        readfile($urlFile);

                    }
                }
            } 
            
            function vpm_sortable_columns( $columns ) {

                $columns['vpm_startDate'] = 'vpm_startDate';
                $columns['vpm_endDate'] = 'vpm_endDate';
                $columns['vpm_downloads'] = 'vpm_downloads';

                return $columns;
            }
            
            
            function vpm_columns_load() {
                add_filter( 'request', array(__CLASS__, 'vpm_sort_dates'),10,1 );
            }
            
            function vpm_sort_dates( $vars ) {
                /* Check if we're viewing our post type. */
                if ( isset( $vars['post_type'] ) && $vars['post_type'] == self::POST_TYPE ) {
                    if ( isset( $vars['orderby']) ){
                        switch ($vars['orderby']) {
                            case 'vpm_startDate':
                                /* Merge the query vars with our custom variables. */
                                $vars = array_merge(
                                    $vars,
                                        array(
                                            'meta_key' => __CLASS__ . self::$startDate,
                                            'orderby' => 'meta_value_num'
                                        )
                                );
                                break;
                            case 'vpm_endDate':
                                /* Merge the query vars with our custom variables. */
                                $vars = array_merge(
                                    $vars,
                                        array(
                                            'meta_key' => __CLASS__ .  self::$endDate,
                                            'orderby' => 'meta_value_num'
                                        )
                                );
                                break;
                            case 'vpm_downloads':
                                /* Merge the query vars with our custom variables. */
                                $vars = array_merge(
                                    $vars,
                                        array(
                                            'meta_key' => __CLASS__ .  self::$downloadCounter,
                                            'orderby' => 'meta_value_num'
                                        )
                                );
                                break;
                            default:
                                break;
                        }
                    }
                }

                return $vars;
            }
            
            function vpm_settings_page() {
                if ( function_exists('add_submenu_page') )
                        add_submenu_page('plugins.php', __('Volunteer project configuration'), __('Volunteer project configuration', __CLASS__), 'manage_options', 'vpm-settings', array(__CLASS__, 'vpm_conf'));
            }

            function plugin_settings_link( $links, $file ) {
                if ( $file == plugin_basename( dirname(__FILE__).'/VolunteerProjectManagement.php' ) ) {
                    $links[] = '<a href="plugins.php?page=vpm-settings">'.__('Settings', __CLASS__).'</a>';
                }

                return $links;
                
            }
            
            function optionsSettings() {
                //register our settings
                add_settings_section('vpm_main', 'Main Settings', array(__CLASS__,'vpm_section_text'), 'vpm_plugin');
                //add_settings_field('plugin_text_string', 'Plugin Text Input', array(__CLASS__,'vpm_setting_string'), 'vpm_plugin', 'vpm_main');
                add_settings_field('plugin_cap_check', 'Users who can contribute', array(__CLASS__,'vpm_setting_check'), 'vpm_plugin', 'vpm_main');
                register_setting( 'vpmOptions', 'vpmOptions', array(__CLASS__,'setOurCaps') );

            }
            
            function vpm_section_text() {
                echo '<p>Main description of this section here.</p>';
            } 
            function vpm_setting_string() {
                $options = get_option('vpmOptions');
                echo "<input id='plugin_text_string' name='vpmOptions[text_string]' size='40' type='text' value='{$options['text_string']}' />";
            } 
            function vpm_setting_check() {
                $options = get_option('vpmOptions');
            ?>
                <fieldset id="vpm-enable-cap-container" class="vpm-enable-container">
                    <legend>
                        <?php if(isset($options['cap_check']) && $options['cap_check']=='true'){$checked = "checked='cheked'";}else{$checked = "";} ?>
                        <input id="plugin_cap_check" name="vpmOptions[cap_check]" value="true" <?php echo $checked?>  type="checkbox"/>
                        <label for="plugin_cap_check"><?php _e("If this option isn't used only users who have capability to upload files can contribute",__CLASS__);?></label>
                    </legend>
                    <div id="vpm-cap-container" class="start-hidden">
                    <?php
                        $wproles = get_option('wp_user_roles');
                        foreach ($wproles as $role_name => $role_specification)
                        {
                            if(in_array(self::CONTRIBUTION_CAP, $role_specification['capabilities'])){
                                if(isset($role_specification['capabilities'][self::CONTRIBUTION_CAP]) && $role_specification['capabilities'][self::CONTRIBUTION_CAP]==1){
                                    $checked = "checked='checked'";
                                }else{
                                    $checked = "";
                                }
                            echo "<input name=\"wpu_roles_${role_name}\" type=\"checkbox\" value=\"yes\"" . $checked. "/> " . __($role_specification['name']) . "<br/>";
                            }
                        }
                    ?>
                    </div>
                </fieldset>


            <?php
            } 
            // validate our options
            function setOurCaps($options) {
                $wproles = get_option('wp_user_roles');
                foreach ($wproles as $role_name => $role_specification){
                    $role = get_role( $role_name ); // get role
                    if($options['cap_check']=='true'){
                        if($_POST['wpu_roles_'.$role_name]=='yes'){
                            $role->add_cap( self::CONTRIBUTION_CAP );
                        }else{
                            $role->remove_cap( self::CONTRIBUTION_CAP );
                        }
                    }else{
                        $role->remove_cap( self::CONTRIBUTION_CAP );
                    }
                }
                return $options;
            }
            
            function vpm_conf() {
                if ( isset($_POST['Submit']) ) {
                    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
                        die(__('Cheatin&#8217; uh?'));
                }
                ?>
                <?php if ( !empty($_POST['submit'] ) ) : ?>
                <div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
                <?php endif; ?>
                <div class="wrap">
                <h2><?php _e('Volunteer project management configuration'); ?></h2>
                <?php echo _e('Options relating to the Volunteer project plugin.');?>
                
                <div class="narrow">
                    <form action="options.php" method="post" id="vpm-conf"> 
                        <?php settings_fields('vpmOptions'); ?>
                        <?php do_settings_sections('vpm_plugin'); ?>
                        <input name="Submit" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
                    </form>
                </div>
                <?php
            }
            
            /**
             *
             * @param string $actions
             * @param type $post
             * @return string 
             */
            function vpm_row_actions($actions, $post){
                // only to our post type
                if ($post->post_type == self::POST_TYPE){
                    $options = get_option('vpmOptions');
                    if(isset($options['cap_check'])){
                        $allowedRole = self::CONTRIBUTION_CAP;
                    }else{
                        $allowedRole = self::DEFAULT_CAP;
                    }
                    echo("capability ->".$allowedRole);
                    // If current user can upload files he can contribute
                    if(current_user_can($allowedRole)){
                        $actions['add-contribution'] = '<a href="' . add_query_arg( array( 'action'=>'uploadContribution', 'post_type' => self::POST_TYPE, 'post' => $post->ID ), admin_url( 'post-new.php') ) . '" title="'.esc_attr('Add a contribution').'" rel="permalink">'.__("Add a contribution").'</a>';
                    }
                    //$actions["add-contribution"] = '<a href="' . add_query_arg( array( 'action'=>'uploadContribution', 'post_type' => self::POST_TYPE, 'post' => $post->ID ), admin_url( 'post-new.php') ) . '" title="Create a new page with this page as its parent">Create child</a>';  
                }
                return $actions;
            }

            function uploadContribution($post) {
                // only to our post type
                if ($post->post_type == self::POST_TYPE && isset($_GET['post'])){
                    if($_GET['action']== 'uploadContribution'){
                        ?>
                    <div class="wrap">
                    <h2><?php _e('Volunteer project management configuration'); ?></h2>
                    <?php echo _e('Options relating to the Volunteer project plugin.');?>

                    <div class="narrow">
                        <form action="options.php" method="post" id="vpm-conf"> 
                            <?php //settings_fields('vpmOptions'); ?>
                            <?php //do_settings_sections('vpm_plugin'); ?>
                            <input name="Submit" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
                        </form>
                    </div>
                    <?php
                    }
                }
                
                //wp_redirect( admin_url( 'edit.php?post_type=vpm-project') );
            } 
            
            function register_ContributionPage(){
                if(current_user_can(self::getAllowedCap())){
                    add_submenu_page( 'edit.php?post_type='.self::POST_TYPE, __('Projects list'), __('Projects list'), self::getAllowedCap(), 'contributionPage', array(__CLASS__,'contributionPage_build') );
                }
            }
            
            function contributionPage_build(){
                $messages = array(
                    'noFile' => array('color' => '#888', 'text' => __('You must set a file to upload.')),
                    'noPdf' => array('color' => '#888', 'text' => __('You must set a pdf file to upload.')),
                    'uploadError' => array('color' => '#888', 'text' => __('Upload error, try again please.')),
                    'success' => array('color' => '#AA0', 'text' => __('Your contribution is saved.')),
                );
                if(isset($_GET['action']) && $_GET['action']=="savePost"){
                    // if image don't exist we don't save the contribution
                    if(!empty($_FILES[__CLASS__ . '_projectFile']['name'])) {
                        
                            // Setup the array of supported file types. In this case, it's just PDF.
                            // @todo  we want more?
                            $supported_types = array('application/pdf');

                            // Get the file type of the upload
                            $arr_file_type = wp_check_filetype(basename($_FILES[__CLASS__ . self::$projectFile]['name']));
                            $uploaded_type = $arr_file_type['type'];

                            // Check if the type is supported. If not, throw an error.
                            if(in_array($uploaded_type, $supported_types)) {
                                    // Use the WordPress API to upload the file
                                    $upload = wp_upload_bits($_FILES[__CLASS__ . self::$projectFile]['name'], null, file_get_contents($_FILES[__CLASS__ . '_projectFile']['tmp_name']));
                                    __CLASS__ . self::$num++;
                                    if(isset($upload['error']) && $upload['error'] != 0) {
                                        $message = 'uploadError';
                                        wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                                        
                                    } else {
                                        // Before we link the file to the post we must inser the post
                                        $contributionPost = array(
                                            'post_title' => wp_strip_all_tags( $_POST['post_title'] ),
                                            'post_content' => $_POST['content'],
                                            'post_status' => 'draft',
                                            'post_type' => self::POST_TYPE,
                                            'post_author' => $_POST['post_author'],
                                            'post_parent' => $_POST['parent_id'],
                                            'comment_status' => 'closed'
                                        );


                                        $postId = wp_insert_post( $contributionPost );
                                        
                                        
                                            add_post_meta($postId, __CLASS__ . self::$projectFile, $upload);
                                            update_post_meta($postId, __CLASS__ . self::$projectFile, $upload);
                                            // When we update the file we reset the counter
                                            self::setPostCustomValues(self::$downloadCounter, 0);
                                            $message = 'success';
                                    } // end if/else
                            } else {
                                $message = 'noPdf';
                                wp_die("The file type that you've uploaded is not a PDF.");
                            } // end if/else
                    }
                    $message = 'noFile';
                }
                
                if(isset($_GET['post']) && $_GET['post']>0){
                    $post = get_post($_GET['post']);
                    switch ($_GET['action']) {
                        case 'view':
                            //echo $_GET['post']."<pre>".print_r($post,true)."</pre>";
                            if($post->post_status=='publish' && $post->post_type==self::POST_TYPE){
                                $file = get_post_meta($post->ID, __CLASS__.self::$projectFile, true);
                                $html = '<a href="' . add_query_arg( array( 'post_type' => self::POST_TYPE, 'post' => $post->ID, 'page' => 'contributionPage','action' => 'downloadFile'  ), admin_url( 'edit.php') ) . '" id="vpm_projectFile_download">'.__('Download', __CLASS__).'</a>';
                                $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                                if(!$endDate):
                                    $endDate = _e("No end date");
                                else:
                                    $endDate = date("m-d-Y@H:i:s", $endDate);
                                endif;
                            ?>
                                <div class="wrap">
                                    <div id="icon-edit" class="icon32 icon32-posts-<?php echo self::POST_TYPE?>"><br/></div>
                                        <h2><?php _e('Project: ',__CLASS__). $post->post_title;?></h2>
                                        <div class="vpm-contribution-content"><?php echo apply_filters('the_content', $post->post_content); ?></div>
                                        <div class="vpm-contribution-content">
                                            <b><?php echo __('End Date').":</b> ". $endDate;?><br/>
                                            <b><?php echo __('Number of downloads ').":</b> ".self::getPostCustomValues(self::$downloadCounter, $post);?><br/>
                                            <b><?php echo __('Project file').":</b> ".$html;?><br/>    
                                        </div>
                                </div>
                            <?php
                            }else{
                                die(__('Cheatin&#8217; uh?'));
                            }
                            break;
                        case 'contribute':
                            ?>
                                <div class="wrap">
                                    <div id="icon-users" class="icon32"><br/></div>
                                    <h2><?php _e('Contribute to Project: ', __CLASS__); echo $post->post_title;?></h2>
                                    <form id="contribution" id="post" name="post" method="post" enctype="multipart/form-data" action="edit.php?post_type=vpm-project&page=contributionPage&action=savePost">
                                        <input type="hidden" name="page" id="page" value="<?php echo $_REQUEST['page'] ?>" />
                                        <input type="hidden" name="post_author" id="post_author" value="<?php echo get_current_user_id() ?>" />
                                        <input type="hidden" name="parent_id" id="parent_id" value="<?php echo $post->ID?>" />
                                        <div id="titlediv">
                                            <div id="titlewrap">
                                                <input type="text" autocomplete="off" id="title" size="30" name="post_title">
                                            </div>
                                        </div>
                                        <div class="postarea" id="postdivrich">
                                            <?php the_editor('','content'); ?>
                                        </div>
                                        <div id="vpm-upload-container">
                                            <label><?php _e('Contribution file', __CLASS__) ?></label>
                                            <?php echo self::projectFile(null); ?>
                                        </div>
                                        <input name="Submit" class="button-primary" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
                                    </form>
                                </div>
                    
                            <?php
                            break;
                        default:
                            die(__('Cheatin&#8217; uh?'));
                            break;
                    }
                }else{
                
                
                    global $wp_post_types;
                    $poststoList = array();

                    $posts = get_posts( array(
                        'post_type' => self::POST_TYPE,
                        'posts_per_page' => -1,
                        'nopaging' => true
                    ) );
                    //echo mktime();//-(current_time('timestamp', true)."---".current_time('timestamp', false));
                    foreach ($posts as $post) {
                        $startDate = self::getStartDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        $endDate = self::getEndDate($post)-(current_time('timestamp', true)-current_time('timestamp', false));
                        
                        $currentDate = mktime();
                        //echo $startDate."----".$currentDate."----".$endDate."------".$post->ID."<br/>";
                        //if($post->post_status=='publish' && $startDate<$currentDate && $currentDate<$endDate){
                        if($post->post_status=='publish'){
                            $notShow=0;
                            //if($startDate<$currentDate && $currentDate<$endDate){
                            if(self::hasStartDate($post) && $startDate<$currentDate){ 
                                $startDate = date("m-d-Y@H:i:s", $startDate);
                            }elseif(!self::hasStartDate($post)){
                                $startDate = __("No start date",__CLASS__);
                            }else{
                                $notShow=1;
                            }
                            if(self::hasEndDate($post) && $currentDate<$endDate){
                                $endDate = date("m-d-Y@H:i:s", $endDate);
                            }elseif(!self::hasEndDate($post)){
                                $endDate = __("No end date",__CLASS__);
                            }else{
                                $notShow=1;
                            }
                            if($notShow!=1 ){
                                $put = array(            
                                    'ID'        => $post->ID,
                                    'title'     => $post->post_title,
                                    'vpm_excerpt' => substr($post->post_content, 0, 100),
                                    'vpm_endDate' => $endDate,
                                    'vpm_downloads' => self::getPostCustomValues(self::$downloadCounter, $post)
                                );
                                array_push ($poststoList, $put);
                            }
                        }

                    }
                    //echo "<pre>".print_r($poststoList,true)."</pre>";
                    if(!class_exists('contribute_Table')){
                        require_once( WP_PLUGIN_DIR."/".__CLASS__.'/contributeList.php' );
                    }

                    $contribute_Table = new contribute_Table($poststoList);
                    //Fetch, prepare, sort, and filter our data...
                    $contribute_Table->prepare_items();
                    ?>
                    
                    <?php if ( !empty($_POST['Submit'] ) ) : ?>
                    <div id="message" class="updated fade">
                        <p style="padding: .5em; background-color: <?php echo $messages[$message]['color']; ?>; color: #fff; font-weight: bold;"><?php _e($messages[$message]['text'], __CLASS__); ?></p>
                    </div>
                    <?php endif; ?>
                        <div class="wrap">

                            <div id="icon-users" class="icon32"><br/></div>
                            <h2><?php _e('Projects that need your contribution', __CLASS__)?></h2>

                            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                            <form id="contributions-filter" method="get">
                                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                                <!-- Now we can render the completed list table -->
                                <?php $contribute_Table->display() ?>
                            </form>

                        </div>


                    <?php
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
                add_action('admin_init', array(__CLASS__, 'optionsSettings'));
                

                // Register the savePost method to the Wordpress save_post action hook
                add_action('save_post', array(__CLASS__, 'savePost'));
                
                // Add custom columns to our post
                add_filter('manage_edit-'.self::POST_TYPE.'_columns', array(__CLASS__ , 'vpm_columns'), 10, 2);
                // Add custom columns to our post
                add_action('manage_'.self::POST_TYPE.'_posts_custom_column', array(__CLASS__ , 'vpm_manage_columns'),10,2);
                // Add a sortable column
                add_filter( 'manage_edit-'.self::POST_TYPE.'_sortable_columns', array(__CLASS__, 'vpm_sortable_columns'),10,1);
                // Only run our customization on the 'edit.php' page in the admin. */
                add_action( 'load-edit.php', array(__CLASS__, 'vpm_columns_load'),10,1);
                // Add action so we can count number of downloads
                add_action('admin_init', array(__CLASS__ , 'downloadFile'),10,2);
                // TODO is this the best way?
                add_filter('post_type_link', array(__CLASS__, 'downloadFile'), 10, 1);
                
                // Add submenu page
                add_action('admin_menu', array(__CLASS__, 'register_ContributionPage'));

                
                
                
                
                // Add a custom link action
                //add_filter('page_row_actions', array(__CLASS__ , 'vpm_row_actions' ), 10, 2);
                // Add action so users can contribute
                add_action('admin_init', array(__CLASS__ , 'uploadContribution'), 10, 1);
                
                
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
                
                // Add a link option so we can define plugin settings                
                add_filter( 'plugin_action_links', array(__CLASS__ , 'plugin_settings_link'), 10, 2 );
                // Add a link option in links menu so we can define plugin settings
                add_action( 'admin_menu', array(__CLASS__ , 'vpm_settings_page' ));
                
                
                

            }

		
        }
endif;

VolunteerProjectManagement::init();
