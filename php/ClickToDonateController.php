<?php
/**
 * Provides the controller functionality for the plugin 
 */

if (!class_exists('ClickToDonateController')):
    class ClickToDonateController {
        // Constants
        /**
         * Query param to which this plugin will respond to 
         */
        const URL_QUERY_PARAM = 'donate-to';

        /**
         * Custom post type for the campaigns 
         */
        const POST_TYPE = 'ctd-campaign';

        /**
         * Custom post status 
         */
        const STATUS_online = 'ctd-online';
        const STATUS_finished = 'ctd-finished';
        const STATUS_scheduled = 'ctd-scheduled';
        const STATUS_unavailable = 'ctd-unavailable';
        
        // Generic message codes
        const MSG_OK = 1;
        const MSG_UNKNOWN_ERROR = -1;
        const MSG_UNKNOWN_POST_TYPE = -2;
        const MSG_UNKNOWN_POST_STATUS = -3;
        const MSG_RESTRITED_BY_COOKIE = -4;
        const MSG_RESTRITED_BY_LOGIN = -5;
        const MSG_CAMPAIGN_UNAVAILABLE = -6;
        const MSG_CAMPAIGN_SCHEDULED = -7;
        const MSG_CAMPAIGN_FINISHED = -8;
        const MSG_URL_ERROR = -9;
        const MSG_AUTHENTICATION_ERROR = -10;
        
        // Class variables
        private static $requireLogin = '_require_login';
        private static $enableCoolOff = '_enable_cool_off';
        private static $coolOff = '_cool_off_time';
        private static $restrictByCookie = '_restrict_by_cookie';
        private static $restrictByLogin = '_restrict_by_login';
        private static $enableClickLimits = '_enable_click_limits';
        private static $maxClicks = '_maxClicks';
        private static $enableStartDate = '_enable_startDate';
        private static $startDate = '_startDate';
        private static $enableEndDate = '_enable_endDate';
        private static $endDate = '_endDate';
        private static $cookieName = 'CTD-banner';
        private static $viewBannerOnce = 'ctd_view_banner';
        
        public static function init(){
            
            // Register the WpInit method to the Wordpress initialization action hook
            add_action('init', array(__CLASS__, 'WpInit'));
            
            // Register the install database method to be executed when the plugin is activated
            register_activation_hook(ClickToDonate::FILE, array(__CLASS__, 'activation'));

            // Register the install database method to be executed when the plugin is updated
            add_action('plugins_loaded', array(__CLASS__, 'install'));

            // Register the remove database method when the plugin is removed
            register_uninstall_hook(ClickToDonate::FILE, array(__CLASS__, 'uninstall'));

            // Register the transitionPostStatus method to the Wordpress transition_post_status action hook
            add_action('transition_post_status', array(__CLASS__, 'transitionPostStatus'), 10, 3);
            
            // Add the post_type_link filter to filter the post URL
            add_filter('post_type_link', array(__CLASS__, 'postTypeLink'), 10, 4);
        }
        
        /**
         * Register the post type for the campaigns
         */
        public static function WpInit() {
            load_plugin_textdomain('ClickToDonate', false, dirname(plugin_basename(ClickToDonate::FILE)) . '/langs');
            
            // Register the post types and statuses
            self::registerPostType();
        }
        
        /**
         * Plugin activation hook
         * @global $wp_rewrite to flush the rewrite rules
         */
        public static function activation() {
            // Install the plugin requirements
            self::install();
            
            // Flag to flush the rewrite rules on the next init
            update_option(__CLASS__.'_flush_rules', true);
        }
        
        /**
         * Install the plugin requirements
         */
        public static function install() {
            ClickToDonateModel::install();
        }
        
        /**
         * Uninstall the plugin data
         * 
         * @global $wp_rewrite to flush the rewrite rules
         */
        public static function uninstall() {
            global $wp_rewrite;
            
            //Uninstall the data
            ClickToDonateModel::uninstall();
            
            // Remove all the campaigns
            self::removePostType();
            
            if(method_exists($wp_rewrite, 'flush_rules'))
                $wp_rewrite->flush_rules();
        }
        
        /**
         * Register the post type and linked states
         * 
         * @uses post_type_exists
         * @uses register_post_type
         * @uses post_type_exists
         * @uses register_post_status
         * @uses get_option
         * @uses delete_option
         * @uses flush_rewrite_rules
         */
        private static function registerPostType() {
            // Create the post status
            if(function_exists('register_post_type') && !post_type_exists(self::POST_TYPE)):
                register_post_type(self::POST_TYPE, array(
                    'hierarchical' => false,
                    'labels' => array(
                        'name' => __('Campaigns', 'ClickToDonate'),
                        'singular_name' => __('Campaign', 'ClickToDonate'),
                        'add_new' => __('Add new', 'ClickToDonate'),
                        'add_new_item' => __('Add new campaign', 'ClickToDonate'),
                        'edit_item' => __('Edit campaign', 'ClickToDonate'),
                        'new_item' => __('New campaign', 'ClickToDonate'),
                        'view_item' => __('View campaign', 'ClickToDonate'),
                        'search_items' => __('Search campaigns', 'ClickToDonate'),
                        'not_found' => __('No campaign found', 'ClickToDonate'),
                        'not_found_in_trash' => __('No campaigns were found on the recycle bin', 'ClickToDonate')
                    ),
                    'description' => __('Click to donate campaigns', 'ClickToDonate'),
                    'has_archive' => false,
                    'public' => true,
                    'publicly_queryable' => true,
                    'exclude_from_search' => true,
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'show_in_nav_menus' => false,
                    'capability_type' => 'page',
                    'supports' => array('title', 'editor', 'revisions', 'thumbnail'),
                    'rewrite' => array(
                        'slug' => self::URL_QUERY_PARAM,
                        'with_front' => 'false'
                    ),
                    'query_var' => true
                ));
                
                // Force the flush of the rewrite rules to assume the new post type slug
                if(get_option(__CLASS__.'_flush_rules', false) && function_exists('flush_rewrite_rules')):
                    delete_option(__CLASS__.'_flush_rules');
                    flush_rewrite_rules();
                endif;
            endif;
            
            if(function_exists('register_post_status')):
                if (!post_type_exists(self::STATUS_online)):
                    register_post_status(self::STATUS_online, array(
                        'label' => __('Online', 'ClickToDonate'),
                        'public' => true,
                        'internal' => false,
                        'private' => false,
                        'exclude_from_search' => true,
                        'show_in_admin_all_list' => true,
                        'show_in_admin_status_list' => true,
                        'label_count' => _n_noop('Online <span class="count">(%s)</span>', 'Online <span class="count">(%s)</span>'),
                    ));
                endif;

                if (!post_type_exists(self::STATUS_scheduled)):
                    register_post_status(self::STATUS_scheduled, array(
                        'label' => __('Scheduled', 'ClickToDonate'),
                        'public' => false,
                        'internal' => false,
                        'private' => true,
                        'exclude_from_search' => true,
                        'show_in_admin_all_list' => true,
                        'show_in_admin_status_list' => true,
                        'label_count' => _n_noop('Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>'),
                    ));
                endif;

                if (!post_type_exists(self::STATUS_finished)):
                    register_post_status(self::STATUS_finished, array(
                        'label' => __('Finished', 'ClickToDonate'),
                        'public' => false,
                        'internal' => false,
                        'private' => true,
                        'exclude_from_search' => true,
                        'show_in_admin_all_list' => true,
                        'show_in_admin_status_list' => true,
                        'label_count' => _n_noop('Finished <span class="count">(%s)</span>', 'Finished <span class="count">(%s)</span>'),
                    ));
                endif;

                if (!post_type_exists(self::STATUS_unavailable)):
                    register_post_status(self::STATUS_unavailable, array(
                        'label' => __('Unavailable', 'ClickToDonate'),
                        'public' => false,
                        'internal' => false,
                        'private' => true,
                        'exclude_from_search' => true,
                        'show_in_admin_all_list' => true,
                        'show_in_admin_status_list' => true,
                        'label_count' => _n_noop('Unavailable <span class="count">(%s)</span>', 'Unavailable <span class="count">(%s)</span>'),
                    ));
                endif;
            endif;
        }
        
        /**
         * Remove the custom post type for this plugin
         * 
         * @global array $wp_post_types with all the custom post types
         * @return boolean true on success, false otherwise
         */
        private static function removePostType() {
            global $wp_post_types;

            $posts = get_posts(array(
                'post_type' => self::POST_TYPE,
                'posts_per_page' => -1,
                'nopaging' => true
            ));

            foreach ($posts as $post):
                wp_delete_post(self::getPostID($post), true);
            endforeach;


            if (isset($wp_post_types[self::POST_TYPE])):
                unset($wp_post_types[self::POST_TYPE]);
                return true;
            endif;
            return false;
        }
        
        
        /**
         * Get the post list
         * @param array $args
         * @return boolean 
         */
        public static function getPosts($args) {
            if(!isset($args['query'])):
                $args['query'] = array();
            endif;
            $defaults = array(
                'post_type' => self::POST_TYPE,
                'suppress_filters' => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'post_status' => array(
                    'publish',
                    self::STATUS_online,
                    self::STATUS_scheduled
                ),
                'order' => 'DESC',
                'orderby' => 'post_date',
                'posts_per_page' => 20
            );
            
            $query = wp_parse_args( $args['query'], $defaults );

            $args['pagenum'] = isset($args['pagenum']) ? absint($args['pagenum']) : 1;

            if (isset($args['s']))
                $query['s'] = $args['s'];

            $query['offset'] = $args['pagenum'] > 1 ? $query['posts_per_page'] * ( $args['pagenum'] - 1 ) : 0;

            // Do main query.
            $get_posts = new WP_Query;
            $posts = $get_posts->query($query);
            // Check if any posts were found.
            if (!$get_posts->post_count)
                return false;

            // Build results.
            $results = array();
            foreach ($posts as $post) {
                $postType = get_post_type_object($post->post_type);
                $info = $postType->labels->singular_name;

                $results[] = array(
                    'ID' => $post->ID,
                    'title' => trim(esc_html(strip_tags(get_the_title($post)))),
                    'permalink' => get_permalink($post->ID),
                    'info' => $info,
                    'post_status' => get_post_status($post->ID),
                );
            }

            return $results;
        }
        
        
        /**
         * If the post status was updated, update it with our rules
         * 
         * @param string $newStatus with the new post status
         * @param string $oldStatus with the old post status
         * @param object $post with the post object
         */
        public static function transitionPostStatus($newStatus, $oldStatus, $post) {
            self::updatePostStatus(self::getPostID($post));
        }

        /**
         * Based on the status and the configurations of the post, set the post_status accordingly
         * 
         * @param int|object $post
         * @return string with the post status
         */
        public static function updatePostStatus($post = 0) {
            $post = get_post($post);
            if(get_post_type($post)==self::POST_TYPE):
                // Compute the new status
                $newStatus = false;
                switch (get_post_status($post)):
                    case 'publish':
                    case self::STATUS_online:
                    case self::STATUS_scheduled:
                        $numberOfClicks = self::countBannerVisits($post);
                        if (self::hasClicksLimit($post) && self::getClicksLimit($post) <= $numberOfClicks || self::hasEndDate($post) && self::getEndDate($post) <= current_time('timestamp', true)):
                            $newStatus = self::STATUS_finished;
                        elseif (self::hasStartDate($post) && self::getStartDate($post) >= current_time('timestamp', true)):
                            $newStatus = self::STATUS_scheduled;
                        else:
                            $newStatus = self::STATUS_online;
                        endif;
                        break;
                endswitch;

                // Persist the new status
                if ($newStatus):
                    $oldStatus = get_post_status($post);
                    $post->post_status = $newStatus;
                    if ($oldStatus != $newStatus):
                        wp_update_post($post);
                        wp_transition_post_status($newStatus, $oldStatus, $post);
                    endif;
                endif;
            endif;
            
            return get_post_status($post);
        }

        /**
         * Verify if a banner can be shown
         * 
         * @param int|object $post
         * @param boolean $checkUrl to also verify the URL
         * @param boolean $checkAuthentication to also check the authentication requirement
         * @return true if the banner can be shown, false otherwise 
         */
        public static function bannerCanBeShown($post = 0, $checkUrl=false, $checkAuthentication=true) {
            $post = get_post($post);

            if(get_post_type($post) == self::POST_TYPE):
                self::updatePostStatus($post);
            
                if($checkUrl && !self::verifyPostLink()):
                    return self::MSG_URL_ERROR;
                endif;
                
                
                // If the post is online
                switch (get_post_status($post)):
                    case 'publish':
                    case self::STATUS_online:
                        // Impose the authentication restritions
                        if($checkAuthentication && self::isLoginRequired($post) && !is_user_logged_in()):
                            return self::MSG_AUTHENTICATION_ERROR;
                        endif;
                        
                        // Impose the cookie restritions
                        if (self::isToRestrictByCookie($post) && isset($_COOKIE[self::$cookieName . self::getPostID($post)]) && is_numeric($_COOKIE[self::$cookieName . self::getPostID($post)]) && $_COOKIE[self::$cookieName . self::getPostID($post)] > current_time('timestamp', true)):
                            return self::MSG_RESTRITED_BY_COOKIE;
                        endif;

                        // Impose the authenticated user time limit restritions
                        if (self::isToRestrictByLogin($post) && (self::getLastBannerVisitByAuthenticatedUser($post) + self::getCoolOffLimit($post) > current_time('timestamp', true))):
                            return self::MSG_RESTRITED_BY_LOGIN;
                        endif;
                        
                        return self::MSG_OK;
                    case self::STATUS_scheduled:
                        return self::MSG_CAMPAIGN_SCHEDULED;
                    case self::STATUS_unavailable:
                        return self::MSG_CAMPAIGN_UNAVAILABLE;
                    case self::STATUS_finished:
                        return self::MSG_CAMPAIGN_FINISHED;
                endswitch;
                return self::MSG_UNKNOWN_POST_STATUS;
            else:
                return self::MSG_UNKNOWN_POST_TYPE;
            endif;
            return self::MSG_UNKNOWN_ERROR;
        }
        
        /**
         * Filter the permalink URL adding the nonce field to allow the post view
         * 
         * @param string $post_link
         * @param int|object $post
         * @param boolean $leavename
         * @param string $sample
         * @return string with the filtered URL 
         */
        public static function postTypeLink($post_link, $post, $leavename = false, $sample = false){
            return /*(ClickToDonateController::bannerCanBeShown($post, false, false)==ClickToDonateController::MSG_OK)?*/self::createPostLink($post_link)/*:$post_link*/;
        }
        
        /**
         * Retrieve URL with nonce added to URL query.
         *
         * @param string $actionurl URL to add nonce action
         * @return string URL with nonce action added.
         * @see wp_nonce_url
         */
        public static function createPostLink($actionurl) {
            $actionurl = str_replace( '&amp;', '&', $actionurl );
            return esc_html( add_query_arg( 'ctd-nonce', wp_create_nonce( self::$viewBannerOnce ), $actionurl ) );
        }
        
        /**
         * Verify the nonce parameter of an URL
         * 
         * @return boolean true if the nonce field checks out, false otherwise
         */
        public static function verifyPostLink(){
            $query_arg = 'ctd-nonce';
            return isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], self::$viewBannerOnce) : false;
        }

        /**
         * Get the post ID from the parameter or the main loop
         * @param int|object $post to get the post from
         * @return int with the post ID 
         */
        public static function getPostID($post) {
            if ($post = get_post($post))
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
        private static function setPostCustomValues($key, $value = '', $post = 0) {
            update_post_meta(self::getPostID($post), ClickToDonate::CLASS_NAME . $key, $value);
        }

        /**
         * Get a custom value associated with a post
         * 
         * @param string $key with the key name
         * @param int|object $post with the post
         * @return string value for the key or boolean false if the key was not found
         */
        private static function getPostCustomValues($key, $post = 0) {
            $value = get_post_custom_values(ClickToDonate::CLASS_NAME . $key, self::getPostID($post));
            return (!empty($value) && isset($value[0])) ? $value[0] : false;
        }

        /**
         * Verify if the campaign requires user authentication
         * 
         * @param int|object $post
         * @return boolean
         */
        public static function isLoginRequired($post = 0) {
            return (boolean) self::getPostCustomValues(self::$requireLogin, $post);
        }

        /**
         * Enable or disable the login requirement
         * 
         * @param int|object $post with the post
         * @param boolean $enable 
         */
        public static function requireLogin($post = 0, $require=false) {
            self::setPostCustomValues(self::$requireLogin, $require, $post);
        }

        /**
         * Verify if the campaign has a cooling-off time limit
         * 
         * @param int|object $post
         * @return boolean
         */
        public static function hasCoolOffLimit($post = 0) {
            return (boolean) self::getPostCustomValues(self::$enableCoolOff, $post);
        }

        /**
         * Enable or disable the cool off limit
         * 
         * @param int|object $post with the post
         * @param boolean $enable 
         */
        public static function enableCoolOffLimit($post = 0, $enable=false) {
            self::setPostCustomValues(self::$enableCoolOff, $enable, $post);
        }

        /**
         * Get the cooling-off time between clicks on a specific campaign
         * 
         * @param int|object $post
         * @return int with the cooling-off time
         */
        public static function getCoolOffLimit($post = 0) {
            $limit = self::getPostCustomValues(self::$coolOff, $post);
            return (int) (!self::hasCoolOffLimit($post) || $limit === false ? 0 : $limit);
        }

        /**
         * Get the cooling-off time remaining between clicks on a specific campaign
         * 
         * @param int|object $post
         * @param int|object $user
         * @return int with the cooling-off time
         */
        public static function getCoolOffLimitRemaining($post = 0, $user = 0) {
            $cookieCoolOffTime = 0;
            if (self::isToRestrictByCookie($post) && isset($_COOKIE[self::$cookieName . self::getPostID($post)]) && is_numeric($_COOKIE[self::$cookieName . self::getPostID($post)]) && $_COOKIE[self::$cookieName . self::getPostID($post)] > current_time('timestamp', true)):
                $cookieCoolOffTime = $_COOKIE[self::$cookieName . self::getPostID($post)] - current_time('timestamp', true);
            endif;

            
            $loginCoolOffTime = 0;
            $tmp = (self::getLastBannerVisit($post, $user) + self::getCoolOffLimit($post)-current_time('timestamp', true));
            if (self::isToRestrictByLogin($post) && $tmp>0):
                $loginCoolOffTime = $tmp;
            endif;
            
            return ($cookieCoolOffTime>$loginCoolOffTime?$cookieCoolOffTime:$loginCoolOffTime);
        }

        /**
         * Get the cooling-off time remaining between clicks on a specific campaign for the authenticated user
         * 
         * @param int|object $post
         * @return int with the cooling-off time
         */
        public static function getCoolOffLimitRemainingForAuthenticatedUser($post = 0) {
            if ($userId = get_current_user_id()):
                return self::getCoolOffLimitRemaining($post, $userId);
            endif;
            return 0;
        }

        /**
         * Set the cool off limit value
         * 
         * @param int|object $post with the post
         * @param int $limit 
         */
        public static function setCoolOffLimit($post = 0, $limit=0) {
            self::setPostCustomValues(self::$coolOff, $limit, $post);
        }

        /**
         * Verify if the campaign is to be restricted by a cookie
         * 
         * @param int|object $post
         * @return boolean
         */
        public static function isToRestrictByCookie($post = 0) {
            return (boolean) (self::hasCoolOffLimit($post) && self::getPostCustomValues(self::$restrictByCookie, $post));
        }

        /**
         * Enable or disable the cookie restrition
         * 
         * @param int|object $post with the post
         * @param boolean $enable 
         */
        public static function enableCookieRestrition($post = 0, $enable=false) {
            self::setPostCustomValues(self::$restrictByCookie, $enable, $post);
        }

        /**
         * Verify if the campaign is to be restricted by login (the user must be authenticated
         * 
         * @param int|object $post
         * @return boolean
         */
        public static function isToRestrictByLogin($post = 0) {
            return (boolean) (self::hasCoolOffLimit($post) && self::getPostCustomValues(self::$restrictByLogin, $post));
        }

        /**
         * Enable or disable the login restrition
         * 
         * @param int|object $post with the post
         * @param boolean $enable 
         */
        public static function enableLoginRestrition($post = 0, $enable=false) {
            self::setPostCustomValues(self::$restrictByLogin, $enable, $post);
        }

        /**
         * Verify if the post has the click limits enforced
         * 
         * @param int|object $post
         * @return boolean
         */
        public static function hasClicksLimit($post = 0) {
            return (boolean) self::getPostCustomValues(self::$enableClickLimits, $post);
        }

        /**
         * Enable or disable the click limits restrition
         * 
         * @param int|object $post with the post
         * @param boolean $enable 
         */
        public static function enableClicksLimit($post = 0, $enable=false) {
            self::setPostCustomValues(self::$enableClickLimits, $enable, $post);
        }

        /**
         * Get the maximum number of clicks allowed in a specific campaign
         * 
         * @param int|object $post
         * @return int with the maximum number of clicks 
         */
        public static function getClicksLimit($post = 0) {
            $limit = self::getPostCustomValues(self::$maxClicks, $post);
            return (int) (!self::hasClicksLimit($post) || $limit === false ? -1 : $limit);
        }

        /**
         * Set the maximum number of visits associated with a banner
         * 
         * @param int|object $post with the post
         * @param int $limit 
         */
        public static function setMaximumNumberOfClicks($post = 0, $limit=0) {
            self::setPostCustomValues(self::$maxClicks, $limit, $post);
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
         * Get the start date of a specific campaign
         * 
         * @param int|object $post
         * @return int with timestamp of the start date
         */
        public static function getStartDate($post = 0) {
            $date = self::getPostCustomValues(self::$startDate, $post);
            return (int) (!self::hasStartDate($post) || $date === false ? current_time('timestamp', false) : $date);
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
        public static function getEndDate($post = 0) {
            $date = self::getPostCustomValues(self::$endDate, $post);
            // Default is set to current date plus a day
            return (int) (!self::hasEndDate($post) || $date === false ? current_time('timestamp', false) + 3600 * 24 : $date);
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
         * Register a visit in the system
         * 
         * @param int|object $post
         * @return boolean true if the visit was successfuly registered, false otherwise
         */
        public static function registerVisit($post) {
            if(self::bannerCanBeShown($post)==self::MSG_OK):
                if(ClickToDonateModel::registerVisit(self::getPostID($post))):
                    $currentTime = current_time('timestamp', true);
                    if (self::isToRestrictByCookie($post)):
                        return setcookie(self::$cookieName.self::getPostID($post), $currentTime + self::getCoolOffLimit($post), $currentTime + 86400 + self::getCoolOffLimit($post), SITECOOKIEPATH, '', is_ssl(), true);
                    endif;

                    return true;
                endif;
            endif;
            return false;
        }
        
        /**
         * Count the visits on a banner
         * 
         * @param int|object $post
         * @param int $user to filter the visits by a specific user
         * @return int with the number of visits
         */
        public static function countBannerVisits($post, $user = 0) {
            return ClickToDonateModel::countBannerVisits(self::getPostID($post), $user);
        }
        
        /**
         * Count the visits to a specific banner by the authenticated user
         * @param int|object $post
         * @return int with the number of visits
         */
        public static function countBannerVisitsByAuthenticatedUser($post) {
            if ($userId = get_current_user_id()):
                return self::countBannerVisits($post, $userId);
            endif;
            return 0;
        }
        
        /**
         * Get the timestamp of the last visit to the banner
         * @param int|object $post
         * @param int $user
         * @return int 
         */
        public static function getLastBannerVisit($post, $user = 0) {
            return ClickToDonateModel::getLastBannerVisit(self::getPostID($post), $user);
        }

        /**
         * Get the timestamp of the last visit of the authenticated user to the banner
         * @param int|object $post
         * @return int 
         */
        public static function getLastBannerVisitByAuthenticatedUser($post) {
            if ($userId = get_current_user_id()):
                return self::getLastBannerVisit($post, $userId);
            endif;
            return 0;
        }
        
        /**
         * Get the banner(s) visits
         * @param int|object $post
         * @param int $user
         * @param int $startDate
         * @param int $endDate
         * @param string $dateGranularity
         * @return ClickToDonateVisitsData with the visits data 
         */
        public static function getBannerVisits($post=0, $user = 0, $startDate=0, $endDate=0, $dateGranularity=ClickToDonateModel::DATE_GRANULARITY_DAYS) {
            return ClickToDonateModel::getBannerVisits(self::getPostID($post), $user, $startDate, $endDate, $dateGranularity);
        }

        /**
         * Get the banner(s) visits for the authenticated user
         * @param int|object $post
         * @param int $startDate
         * @param int $endDate
         * @param string $dateGranularity
         * @return ClickToDonateVisitsData with the visits data 
         */
        public static function getBannerVisitsByAuthenticatedUser($post=0, $startDate=0, $endDate=0, $dateGranularity=ClickToDonateModel::DATE_GRANULARITY_DAYS) {
            if ($userId = get_current_user_id()):
                return self::getBannerVisits($post, $userId, $startDate, $endDate, $dateGranularity);
            endif;
            return 0;
        }
        
        /**
         * Get the banner(s) visits rankings
         * 
         * @param int|object $post
         * @param int $user
         * @param int $startDate
         * @param int $endDate
         * @return ClickToDonateVisitsData with the visits data 
         */
        public static function getBannerParticipantsClicks($post=0, $user = 0, $startDate=0, $endDate=0) {
            return ClickToDonateModel::getBannerParticipantsClicks(self::getPostID($post), $user, $startDate, $endDate);
        }

        /**
         * Get the banner(s) visits for the authenticated user
         * @param int|object $post
         * @param int $startDate
         * @param int $endDate
         * @return ClickToDonateVisitsData with the visits data 
         */
        public static function getBannerAuthenticatedUserClicks($post=0, $startDate=0, $endDate=0) {
            if ($userId = get_current_user_id()):
                return self::getBannerParticipantsClicks($post, $userId, $startDate, $endDate);
            endif;
            return 0;
        }
    }
endif;
ClickToDonateController::init();