<?php
/**
 * Provides the view functionality for the plugin 
 */

if (!class_exists('ClickToDonateView')):
    class ClickToDonateView {
        
        // Properties names
        private static $requireLogin = '_require_login';
        private static $enableCoolOff = '_enable_cool_off';
        private static $coolOff = '_cool_off_time';
        private static $coolOffUnit = '_cool_off_time_unit';
        private static $coolOffUnitSeconds = 'seconds';
        private static $coolOffUnitMinutes = 'minutes';
        private static $coolOffUnitHours = 'hours';
        private static $coolOffUnitDays = 'days';
        private static $restrictByCookie = '_restrict_by_cookie';
        private static $restrictByLogin = '_restrict_by_login';
        private static $enableClickLimits = '_enable_click_limits';
        private static $maxClicks = '_maxClicks';
        private static $enableStartDate = '_enable_startDate';
        private static $startDate = '_startDate';
        private static $enableEndDate = '_enable_endDate';
        private static $endDate = '_endDate';
        private static $internalDebugEnabled = false;
        
        public static function init(){
            
            if (is_admin()):
                // Register the addMetaBox method to the Wordpress backoffice administration initialization action hook
                add_action('admin_init', array(__CLASS__, 'addMetaBox'));

                // Register the adminEnqueueScripts method to the Wordpress admin_enqueue_scripts action hook
                add_action('admin_enqueue_scripts', array(__CLASS__, 'adminEnqueueScripts'));

                // Register the adminPrintStyles method to the Wordpress admin_print_styles action hook
                add_action('admin_print_styles', array(__CLASS__, 'adminPrintStyles'));
                
                // Integrate our plugin with the TinyMce hooks
                add_filter('mce_external_plugins', array(__CLASS__, 'filterMceExternalPlugins'));
                add_filter('mce_buttons_2', array(__CLASS__, 'filterMceButtons'));
                add_filter('mce_external_languages', array(__CLASS__, 'filterMceExternalLanguages'));
                add_filter('tiny_mce_before_init', array(__CLASS__, 'filterMceBeforeInit'));
                add_action('after_wp_tiny_mce', array(__CLASS__, 'afterWpTinyMce'));

                // Add hook for ajax posts query
                add_action('wp_ajax_' . 'ctd_get_links', array(__CLASS__, 'getBannersList'));
            else:
                // Add thePosts method to filter the_posts
                add_filter('the_posts', array(__CLASS__, 'thePosts'), 10, 2);
                
                // Add the_content filter to filter the post content
                add_filter('the_content', array(__CLASS__, 'theContent'));
                
                // Add widget_text filter to filter the text widget content
                add_filter('widget_text', array(__CLASS__, 'theContent'));
                
                
            endif;

            // Register the savePost method to the Wordpress save_post action hook
            add_action('save_post', array(__CLASS__, 'savePost'));
            
        }
        
        /**
         * Register the scripts to be loaded on the backoffice, on our custom post type
         */
        public static function adminEnqueueScripts() {
            if (is_admin()):
                $suffix = self::debugSufix();
            
                if(($current_screen = get_current_screen()) && $current_screen->post_type == ClickToDonateController::POST_TYPE):
                    
                    if($current_screen->base=="post"):
                        // Register the scripts
                        wp_enqueue_script(ClickToDonate::CLASS_NAME.'_common', plugins_url("js/common$suffix.js", ClickToDonate::FILE), array('jquery', 'jquery-ui-datepicker'), '1.0');
                        // Admin script
                        wp_enqueue_script(ClickToDonate::CLASS_NAME.'_admin', plugins_url("js/admin$suffix.js", ClickToDonate::FILE), array('jquery-ui-datepicker', 'jquery-ui-spinner', ClickToDonate::CLASS_NAME.'_common'), '1.0');
                        // Localize the script
                        wp_localize_script(ClickToDonate::CLASS_NAME.'_admin', 'ctdAdminL10n', array(
                            'closeText' => __('Done', 'ClickToDonate'),
                            'currentText' => __('Today', 'ClickToDonate'),
                            'dateFormat' => __('mm/dd/yy', 'ClickToDonate'),
                            'dayNamesSunday' => __('Sunday', 'ClickToDonate'),
                            'dayNamesMonday' => __('Monday', 'ClickToDonate'),
                            'dayNamesTuesday' => __('Tuesday', 'ClickToDonate'),
                            'dayNamesWednesday' => __('Wednesday', 'ClickToDonate'),
                            'dayNamesThursday' => __('Thursday', 'ClickToDonate'),
                            'dayNamesFriday' => __('Friday', 'ClickToDonate'),
                            'dayNamesSaturday' => __('Saturday', 'ClickToDonate'),
                            'dayNamesMinSu' => __('Su', 'ClickToDonate'),
                            'dayNamesMinMo' => __('Mo', 'ClickToDonate'),
                            'dayNamesMinTu' => __('Tu', 'ClickToDonate'),
                            'dayNamesMinWe' => __('We', 'ClickToDonate'),
                            'dayNamesMinTh' => __('Th', 'ClickToDonate'),
                            'dayNamesMinFr' => __('Fr', 'ClickToDonate'),
                            'dayNamesMinSa' => __('Sa', 'ClickToDonate'),
                            'dayNamesShortSun' => __('Sun', 'ClickToDonate'),
                            'dayNamesShortMon' => __('Mon', 'ClickToDonate'),
                            'dayNamesShortTue' => __('Tue', 'ClickToDonate'),
                            'dayNamesShortWed' => __('Wed', 'ClickToDonate'),
                            'dayNamesShortThu' => __('Thu', 'ClickToDonate'),
                            'dayNamesShortFri' => __('Fri', 'ClickToDonate'),
                            'dayNamesShortSat' => __('Sat', 'ClickToDonate'),
                            'monthNamesJanuary' => __('January', 'ClickToDonate'),
                            'monthNamesFebruary' => __('February', 'ClickToDonate'),
                            'monthNamesMarch' => __('March', 'ClickToDonate'),
                            'monthNamesApril' => __('April', 'ClickToDonate'),
                            'monthNamesMay' => __('May', 'ClickToDonate'),
                            'monthNamesJune' => __('June', 'ClickToDonate'),
                            'monthNamesJuly' => __('July', 'ClickToDonate'),
                            'monthNamesAugust' => __('August', 'ClickToDonate'),
                            'monthNamesSeptember' => __('September', 'ClickToDonate'),
                            'monthNamesOctober' => __('October', 'ClickToDonate'),
                            'monthNamesNovember' => __('November', 'ClickToDonate'),
                            'monthNamesDecember' => __('December', 'ClickToDonate'),
                            'monthNamesShortJan' => __('Jan', 'ClickToDonate'),
                            'monthNamesShortFeb' => __('Feb', 'ClickToDonate'),
                            'monthNamesShortMar' => __('Mar', 'ClickToDonate'),
                            'monthNamesShortApr' => __('Apr', 'ClickToDonate'),
                            'monthNamesShortMay' => __('May', 'ClickToDonate'),
                            'monthNamesShortJun' => __('Jun', 'ClickToDonate'),
                            'monthNamesShortJul' => __('Jul', 'ClickToDonate'),
                            'monthNamesShortAug' => __('Aug', 'ClickToDonate'),
                            'monthNamesShortSep' => __('Sep', 'ClickToDonate'),
                            'monthNamesShortOct' => __('Oct', 'ClickToDonate'),
                            'monthNamesShortNov' => __('Nov', 'ClickToDonate'),
                            'monthNamesShortDec' => __('Dec', 'ClickToDonate'),
                            'nextText' => __('Next', 'ClickToDonate'),
                            'prevText' => __('Prev', 'ClickToDonate'),
                            'weekHeader' => __('Wk', 'ClickToDonate')
                        ));
                        
                        
                        $current_screen->add_help_tab( array(
                            'id'       => __CLASS__.'campaigns'
                            ,'title'    => __( 'Click-to-donate campaigns', 'ClickToDonate' )
                            ,'callback' => array(__CLASS__, 'contextualHelpForCampaigns')
                        ) );
                        
                        $current_screen->add_help_tab( array(
                            'id'       => __CLASS__.'submitbox'
                            ,'title'    => __( 'Campaign configuration', 'ClickToDonate' )
                            ,'callback' => array(__CLASS__, 'contextualHelpForConfigurationOptions')
                        ) );
                        
                    else:
                        $current_screen->add_help_tab( array(
                            'id'       => __CLASS__.'campaigns'
                            ,'title'    => __( 'Click-to-donate campaigns', 'ClickToDonate' )
                            ,'callback' => array(__CLASS__, 'contextualHelpForCampaigns')
                        ) );
                    endif;
                endif;
                
                if($current_screen && $current_screen->base=="post"):
                    $current_screen->add_help_tab( array(
                        'id'       => __CLASS__.'campaigns_associations'
                        ,'title'    => __( 'Campaigns association', 'ClickToDonate' )
                        ,'callback' => array(__CLASS__, 'contextualHelpForCampaignsAssociations')
                    ) );
                endif;

                // Link list script
                wp_enqueue_script(ClickToDonate::CLASS_NAME . '_links-list', plugins_url("js/tinymce/links-list$suffix.js", ClickToDonate::FILE), array('jquery', 'wpdialogs'), '1.0');
                wp_localize_script(ClickToDonate::CLASS_NAME . '_links-list', 'ctdLinksListL10n', array(
                    'closeText' => __('Done', 'ClickToDonate'),
                    'title' => __('Insert/edit link', 'ClickToDonate'),
                    'update' => __('Update', 'ClickToDonate'),
                    'save' => __('Add Link', 'ClickToDonate'),
                    'noTitle' => __('(no title)', 'ClickToDonate'),
                    'noMatchesFound' => __('No matches found.', 'ClickToDonate'),
                    'loadingCampaign' => __('Loading campaign {0} title...', 'ClickToDonate')
                ));
            endif;
        }
        
        /**
         * Register the styles to be loaded on the backoffice on our custom post type
         */
        public static function adminPrintStyles() {
            if (is_admin()):
                $suffix = self::debugSufix();
                if(($current_screen = get_current_screen()) && $current_screen->post_type == ClickToDonateController::POST_TYPE):
                    wp_enqueue_style(ClickToDonate::CLASS_NAME . '_jquery-ui-theme', plugins_url("css/jquery-ui/jquery-ui-1.10.3.custom{$suffix}.css", ClickToDonate::FILE), array(), '1.10.3');
                    wp_enqueue_style(ClickToDonate::CLASS_NAME . '_common', plugins_url("css/common$suffix.css", ClickToDonate::FILE), array(ClickToDonate::CLASS_NAME . '_jquery-ui-theme'), '1.0');
                    wp_enqueue_style(ClickToDonate::CLASS_NAME . '_admin', plugins_url("css/admin$suffix.css", ClickToDonate::FILE), array(ClickToDonate::CLASS_NAME . '_jquery-ui-theme', ClickToDonate::CLASS_NAME . '_common'), '1.0');
                endif;
                
                wp_enqueue_style('ctd-tinymce', plugins_url("css/tinymce/tinymce$suffix.css", ClickToDonate::FILE), array(), '1.0');
            endif;
        }
        
        public static function contextualHelpForCampaigns($screen, $tab) {
            _e("
                <p>Campaigns <strong>Click-to-donate</strong> are a special type of content that allow the accounting of visits by site visitors.</p>
                <p>These campaigns can be based on advanced rules that allow complete control over what content is displayed to users.</p>", 'ClickToDonate'
            );
            _e("
                <p>Each campaign can have one of four possible states:
                    <ul>
                        <li><strong>Online</strong> - for the campaign that are active and can be viewed by visitors</li>
                        <li><strong>Completed</strong> - for campaigns that are completed</li>
                        <li><strong>Scheduled</strong> - for campaigns that have not yet started</li>
                        <li><strong>Unavailable</strong> - for campaigns that are not yet available (eg drafts)</li>
                    </ul>
                </p>", 'ClickToDonate'
            );
            _e("<p>On this screen you can search, edit, delete and create new campaigns.</p>", 'ClickToDonate');
        }
        
        public static function contextualHelpForCampaignsAssociations($screen, $tab) {
            printf(__('
                <p>The button %1$s on the WYSIWYG editor can be used to create special links for access to the campaigns.</p>
                <p>Start by selecting the object (eg text or image) to which you want the link, and click the button %1$s in the editor.</p>
                <p>In the <strong>Click to Donate Campaign link</strong> select the campaign for which the link should point and click <strong>Add link</strong>.</p>
                <p>The system will actively monitor the content that links to campaigns, hiding or displaying the content according to the status of the respective campaigns.</p>
                <p>With this system you can create advanced scenarios with campaigns associated with other campaigns, with the necessary individual restrictions.</p>
                ', 'ClickToDonate'
            ), '<span style="display: inline-block; width: 20px; height: 20px; background: url(\''.plugins_url("images/icon.gif", ClickToDonate::FILE).'\') center top no-repeat;">&nbsp;</span>');
        }
        
        public static function contextualHelpForConfigurationOptions($screen, $tab) {
            _e("
                <p>The <strong>Campaign Setup</strong> panel can used to set the properties and constraints associated with a campaign:
                    <ul>
                        <li><strong>Require visitor authentication</strong> - specifies that visitors must authenticate on the system so they can visit a given campaign.</li>
                        <li><strong>Cooling-off period</strong> - specifies the minimum time interval between visits from a visitor to a given campaign, for access and accounting purposes. 
                        Control of this minimum time can be done using two different mechanisms:
                            <ul>
                                <li><strong>Restrict by cookie</strong> - the imposition of the time interval between access is controlled via a cookie (a small file stored on the client browser, but is unreliable for advanced users).</li>
                                <li><strong>Restrict by login</strong> - the user must be authenticate on the system to visit the campaign, and the control of the time interval is made ​​using authentication, where the visit is associated with the user (more reliable, but requires that the user is logged into the site so that their visit is registered).</li>
                            </ul>
                        </li>
                        <li><strong>Limit the number of clicks</strong> - sets the maximum number of visits until campaign completion (useful for removal of a campaign that reaches the ceiling set by the sponsors).</li>
                        <li><strong>Campaign start date</strong> - set the date (and time) for the beginning of the campaign, at which time it will be automatically available to visitors.</li>
                        <li><strong>Campaign end date</strong> - set the date (and time) for the end of the campaign, at which time it will no longer be available to visitors.</li>
                        <li><strong>Status</strong> - the status of the campaign:
                            <ul>
                                <li><strong>Online</strong> - for the campaign that are active and can be viewed by visitors</li>
                                <li><strong>Completed</strong> - for campaigns that are completed</li>
                                <li><strong>Scheduled</strong> - for campaigns that have not yet started</li>
                                <li><strong>Unavailable</strong> - for campaigns that are not yet available (eg drafts)</li>
                            </ul>
                        </li>
                    </ul>
                </p>", 'ClickToDonate'
            );
        }

        /**
         * Add our TinyMCE plugin to the plugin list
         * @param array $plugins with the URLs for the javascript files
         * @return array with our plugin added
         */
        public static function filterMceExternalPlugins($plugins) {
            if (self::hasPermission()):
                $suffix = self::debugSufix();
                $plugins[ClickToDonate::CLASS_NAME] = plugin_dir_url(ClickToDonate::FILE) . "js/tinymce/tinymce$suffix.js";
            endif;

            return $plugins;
        }

        /**
         * Add our button to the TinyMCE toolbar
         * @param array $buttons with the buttons list
         * @return array with our button added
         */
        public static function filterMceButtons($buttons) {
            if (self::hasPermission())
                array_push($buttons, 'separator', ClickToDonate::CLASS_NAME);

            return $buttons;
        }

        /**
         * Send the localized strings to the TinyMCE
         * @param array $files with the list of locatization files
         * @return array
         */
        public static function filterMceExternalLanguages($files) {
            if (self::hasPermission())
                $files[] = plugin_dir_path(ClickToDonate::FILE) . 'php/langs/wp-langs.php';

            return $files;
        }

        /**
         * Send the plugin information to the TinyMCE
         * 
         * @param array $params to add our parameter
         * @return array with out parameter
         */
        public static function filterMceBeforeInit($params) {
            if (self::hasPermission()):

                $info = get_plugin_data(ClickToDonate::FILE, false, true);
                $params[ClickToDonate::CLASS_NAME] = "{
                    'longname':'" . esc_js($info['Name']) . "',
                    'author':'" . esc_js($info['Author']) . "',
                    'authorurl':'" . esc_js($info['AuthorURI']) . "',
                    'infourl':'" . esc_js($info['PluginURI']) . "',
                    'version':'" . esc_js($info['Version']) . "'
                }";
            endif;

            return $params;
        }

        /**
         * Write the code for the campaigns links tinymce window
         * 
         * @param type $settings 
         */
        public static function afterWpTinyMce($settings) {
            ?>
            <div style="display:none;">
                <form id="ClickToDonateLinks" tabindex="-1">
                    <?php wp_nonce_field('ctd-links-list', '_ajax_ctd_links_nonce', false); ?>
                    <div id="link-selector">
                        <div id="link-options">
                            <div>
                                <input id="ctd-url-field" type="hidden" tabindex="10" name="href" />
                            </div>
                            <div id="ctd-campaign-name-field-wrapper">
                                <label><span><?php _e('Campaign:', 'ClickToDonate'); ?></span></label> <label id="ctd-campaign-name-field"/></label>
                            </div>
                            <div>
                                <label><span><?php _e('Title', 'ClickToDonate'); ?></span><input id="ctd-link-title-field" type="text" tabindex="20" name="linktitle" /></label>
                            </div>
                            <div class="link-target">
                                <label><input type="checkbox" id="ctd-link-target-checkbox" tabindex="30" /> <?php _e('Open link in a new window/tab', 'ClickToDonate'); ?></label>
                            </div>
                        </div>
                        <?php $show_internal = '1' == get_user_setting('ctd-links', '0'); ?>
                        <p class="howto toggle-arrow <?php if ($show_internal) echo 'ctd-toggle-arrow-active'; ?>" id="ctd-internal-toggle"><?php _e('Link to existing content', 'ClickToDonate'); ?></p>
                        <div id="ctd-search-panel"<?php if (!$show_internal) echo ' style="display:none"'; ?>>
                            <div class="link-search-wrapper">
                                <label>
                                    <span><?php _e('Filter', 'ClickToDonate'); ?></span>
                                    <input type="text" id="ctd-search-field" class="link-ctd-search-field" tabindex="60" autocomplete="off" />
                                    <img class="waiting" src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt="" />
                                </label>
                            </div>
                            <div id="ctd-search-results" class="ctd-query-results">
                                <ul></ul>
                                <div class="ctd-river-waiting">
                                    <img class="waiting" src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt="" />
                                </div>
                            </div>
                            <div id="ctd-most-recent-results" class="ctd-query-results">
                                <div class="query-notice"><em><?php _e('No search term specified. Showing recent items.', 'ClickToDonate'); ?></em></div>
                                <ul></ul>
                                <div class="ctd-river-waiting">
                                    <img class="waiting" src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" alt="" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submitbox">
                        <div id="ctd-links-cancel">
                            <a class="submitdelete deletion" href="#"><?php _e('Cancel', 'ClickToDonate'); ?></a>
                        </div>
                        <div id="ClickToDonateLinks-update">
                            <input type="submit" tabindex="100" value="<?php esc_attr_e('Add Link', 'ClickToDonate'); ?>" class="button-primary" id="ClickToDonateLinks-submit" name="ClickToDonateLinks-submit">
                        </div>
                    </div>
                </form>
            </div>
            <?php
        }
        
        /**
         * Send the campaigns list as a response of an ajax request 
         */
        public static function getBannersList() {
            check_ajax_referer('ctd-links-list', '_ajax_ctd_links_nonce');

            $args = array();

            if (isset($_POST['search']))
                $args['s'] = stripslashes($_POST['search']);
            
            $match = array();
            if(preg_match('/^\#ctd\-(\d+)$/siU', $args['s'], $match)>0):
                unset($args['s']);
                $args['query'] = array(
                    'p'=>$match[1]
                );
            endif;
            
            $args['pagenum'] = !empty($_POST['page']) ? absint($_POST['page']) : 1;
            
            $args['post_status'] = array(
                'publish',
                ClickToDonateController::STATUS_online,
                ClickToDonateController::STATUS_scheduled
            );

            $results = ClickToDonateController::getPosts($args);

            if (!isset($results))
                die('0');

            echo json_encode($results);
            echo "\n";

            exit;
        }
        
        /**
         * Add a metabox to the campaign post type
         */
        public static function addMetaBox() {
            // Replace the submit core metabox by ours
            add_meta_box('submitdiv', __('Campaign configuration', 'ClickToDonate'), array(__CLASS__, 'writeSubmitMetaBox'), ClickToDonateController::POST_TYPE, 'side', 'core');
        }

        /**
         * Output a custom metabox for saving the post
         * @param Object $post 
         */
        public static function writeSubmitMetaBox($post) {
            $post_type = $post->post_type;
            $post_type_object = get_post_type_object($post_type);
            $can_publish = current_user_can($post_type_object->cap->publish_posts);
            ?>
            <div class="submitbox" id="submitpost">
                <div id="minor-publishing">

                    <?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key  ?>
                    <div style="display:none;">
                        <?php submit_button(__('Save', 'ClickToDonate'), 'button', 'save'); ?>
                    </div>

                    <div id="minor-publishing-actions">
                        <div id="save-action">
                            <?php if (in_array($post->post_status, array(ClickToDonateController::STATUS_unavailable, 'auto-draft')) || 0 == ClickToDonateController::getPostID($post)): ?>
                                <input type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save', 'ClickToDonate'); ?>" tabindex="4" class="button button-highlighted" />
                            <?php endif; ?>
                            <img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-loading" id="draft-ajax-loading" alt="" />
                        </div>
                        <div id="preview-action">
                            <?php
                            if (in_array($post->post_status, array(ClickToDonateController::STATUS_online, ClickToDonateController::STATUS_finished, ClickToDonateController::STATUS_scheduled))):
                                $preview_link = esc_url(get_permalink(ClickToDonateController::getPostID($post)));
                                $preview_button = __('Preview', 'ClickToDonate');
                            else:
                                $preview_link = get_permalink(ClickToDonateController::getPostID($post));
                                if (is_ssl())
                                    $preview_link = str_replace('http://', 'https://', $preview_link);
                                $preview_link = esc_url(apply_filters('preview_post_link', add_query_arg('preview', 'true', $preview_link)));
                                $preview_button = __('Preview', 'ClickToDonate');
                            endif;
                            ?>
                            <a class="preview button" href="<?php echo($preview_link); ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo($preview_button); ?></a>
                            <input type="hidden" name="wp-preview" id="wp-preview" value="" />
                        </div>

                        <div class="clear"></div>
                    </div><?php // /minor-publishing-actions    ?>

                    <?php
                    // Retrieve the campaign date and time interval (and convert them back to the localtime)
                    $startDate = ClickToDonateController::getStartDate($post) - (current_time('timestamp', true) - current_time('timestamp', false));
                    $endDate = ClickToDonateController::getEndDate($post) - (current_time('timestamp', true) - current_time('timestamp', false));

                    // Extract the hours from the timestamp
                    if (!ClickToDonateController::hasStartDate($post)):
                        $startHours = array('0');
                        $startMinutes = array('00');
                    else:
                        $startHours = array(date('G', $startDate));
                        $startMinutes = array(date('i', $startDate));
                    endif;

                    // Extract the minutes from the timestamp
                    if (!ClickToDonateController::hasEndDate($post)):
                        $endHours = array('0');
                        $endMinutes = array('00');
                    else:
                        $endHours = array(date('G', $endDate));
                        $endMinutes = array(date('i', $endDate));
                    endif;
                    ?>
                    <div id="ctd-campaign-admin" class="hide-if-no-js misc-pub-section">
                        <div class="ctd-enable-container">
                            <input id="ctd-require-login" name="<?php echo(__CLASS__ . self::$requireLogin); ?>" value="require_login"<?php checked(ClickToDonateController::isLoginRequired($post)); ?> type="checkbox"/><label class="selectit" for="ctd-require-login"><?php _e('Require visitor authentication', 'ClickToDonate'); ?></label>
                        </div>
                        <fieldset id="ctd-enable-cool-off-container" class="ctd-enable-container ctd-jquery-ui">
                            <legend><input id="ctd-enable-cool-off" name="<?php echo(__CLASS__ . self::$enableCoolOff); ?>" value="enable_cool_off"<?php checked(ClickToDonateController::hasCoolOffLimit($post)); ?> type="checkbox"/><label class="selectit" for="ctd-enable-cool-off"><?php _e('Cooling-off period', 'ClickToDonate'); ?></label></legend>
                            <div id="ctd-cool-off-container" class="start-hidden">
                                <?php
                                // Compute the time unit and their value
                                $time = ClickToDonateController::getCoolOffLimit($post);

                                $seconds = $time % 60;
                                $time /= 60;
                                $minutes = $time % 60;
                                $time /= 60;
                                $hours = $time % 24;
                                $time /= 24;
                                $days = floor($time);

                                $timeUnit = self::$coolOffUnitSeconds;
                                if ($seconds > 0) {
                                    $seconds+=$days * 86400 + $hours * 3600 + $minutes * 60;
                                    $minutes = 0;
                                    $hours = 0;
                                    $days = 0;
                                    $timeUnit = self::$coolOffUnitSeconds;
                                } elseif ($minutes > 0) {
                                    $minutes+=$days * 1440 + $hours * 60;
                                    $hours = 0;
                                    $days = 0;
                                    $timeUnit = self::$coolOffUnitMinutes;
                                } elseif ($hours > 0) {
                                    $hours+=$days * 24;
                                    $days = 0;
                                    $timeUnit = self::$coolOffUnitHours;
                                } elseif ($days > 0) {
                                    $timeUnit = self::$coolOffUnitDays;
                                }
                                $time = $days + $hours + $minutes + $seconds;
                                ?>
                                <div><label for="ctd-cool-off-period" class="selectit"><?php _e('Cooling-off period:', 'ClickToDonate'); ?></label>
                                    <div>
                                        <input title="<?php esc_attr_e('Specify the number of seconds between visits on the same campaign', 'ClickToDonate') ?>" id="ctd-cool-off-period" type="text" size="8" style="width: 70px;" name="<?php echo(__CLASS__ . self::$coolOff); ?>" value="<?php echo($time); ?>" />
                                        <select name="<?php echo(__CLASS__ . self::$coolOffUnit); ?>" id="ctd-cool-off-time-unit">
                                            <option<?php selected($timeUnit, self::$coolOffUnitSeconds); ?> value='1'><?php _e('Second(s)', 'ClickToDonate') ?></option>
                                            <option<?php selected($timeUnit, self::$coolOffUnitMinutes); ?> value='60'><?php _e('Minute(s)', 'ClickToDonate') ?></option>
                                            <option<?php selected($timeUnit, self::$coolOffUnitHours); ?> value='3600'><?php _e('Hour(s)', 'ClickToDonate') ?></option>
                                            <option<?php selected($timeUnit, self::$coolOffUnitDays); ?> value='86400'><?php _e('Day(s)', 'ClickToDonate') ?></option>
                                        </select>
                                    </div>
                                    <span id="ctd-readable-cool-off-period"></span></div>
                                <div><input id="ctd-restrict-by-cookie" name="<?php echo(__CLASS__ . self::$restrictByCookie); ?>" value="restrict_by_cookie"<?php checked(ClickToDonateController::isToRestrictByCookie($post)); ?> type="checkbox"/><label class="selectit" for="ctd-restrict-by-cookie"><?php _e('Restrict by cookie', 'ClickToDonate'); ?></label></div>
                                <div><input id="ctd-restrict-by-login" name="<?php echo(__CLASS__ . self::$restrictByLogin); ?>" value="restrict_by_login"<?php checked(ClickToDonateController::isToRestrictByLogin($post)); ?> type="checkbox"/><label class="selectit" for="ctd-restrict-by-login"><?php _e('Restrict by login', 'ClickToDonate'); ?></label></div>
                            </div>

                        </fieldset>

                        <fieldset id="ctd-enable-maxclicks-container" class="ctd-enable-container ctd-jquery-ui">
                            <legend><input id="ctd-enable-maxclicks" name="<?php echo(__CLASS__ . self::$enableClickLimits); ?>" value="enable_click_limits"<?php checked(ClickToDonateController::hasClicksLimit($post)); ?> type="checkbox"/><label class="selectit" for="ctd-enable-maxclicks"><?php _e('Limit the number of clicks', 'ClickToDonate'); ?></label></legend>
                            <div id="ctd-maxclicks-container" class="start-hidden">
                                <label class="selectit"><?php _e('Clicks limit:', 'ClickToDonate'); ?> <input title="<?php esc_attr_e('Specify the number of clicks allowed before disabling the campaign', 'ClickToDonate') ?>" id="ctd-maximum-clicks-limit" type="text" name="<?php echo(__CLASS__ . self::$maxClicks); ?>" value="<?php echo(ClickToDonateController::getClicksLimit($post)); ?>" /></label>
                            </div>

                        </fieldset>

                        <fieldset id="ctd-enable-startdate-container" class="ctd-enable-container ctd-jquery-ui">
                            <legend>
                                <input id="ctd-enable-startdate" name="<?php echo(__CLASS__ . self::$enableStartDate); ?>" value="enable_startDate"<?php checked(ClickToDonateController::hasStartDate($post)); ?> type="checkbox"/><label class="selectit" for="ctd-enable-startdate"><?php _e('Set the campaign start date', 'ClickToDonate'); ?></label>
                            </legend>
                            <div id="ctd-startdate-container" class="start-hidden">
                                <label class="selectit"><?php _e('Start date:', 'ClickToDonate'); ?> <input size="8" maxlength="10" title="<?php esc_attr_e('Specify the start date when the campaign is supposed to start', 'ClickToDonate') ?>" id="ctd-startdate" type="text" /></label>
                                <input id="ctd-hidden-startdate" type="hidden" name="<?php echo(__CLASS__ . self::$startDate); ?>" value="<?php echo(date('Y-n-j', $startDate)); ?>" />
                                <div class="ctd-timer-container">@<input title="<?php esc_attr_e('Specify the campaign starting hours', 'ClickToDonate') ?>" size="2" maxlength="2" id="ctd-starthours" name="<?php echo(__CLASS__ . '_startHours'); ?>" type="text" value="<?php echo($startHours[0]); ?>" /><span class="ctd-time-separator"> : </span><input title="<?php esc_attr_e('Specify the campaign starting minutes', 'ClickToDonate') ?>" size="2" maxlength="2" id="ctd-startminutes" name="<?php echo(__CLASS__ . '_startMinutes'); ?>" type="text" value="<?php echo($startMinutes[0]); ?>" /></div>
                            </div>
                        </fieldset>

                        <fieldset id="ctd-enable-enddate-container" class="ctd-enable-container ctd-jquery-ui">
                            <legend>
                                <input id="ctd-enable-enddate" name="<?php echo(__CLASS__ . self::$enableEndDate); ?>" value="enable_endDate"<?php checked(ClickToDonateController::hasEndDate($post)); ?> type="checkbox"/><label class="selectit" for="ctd-enable-enddate"><?php _e('Set the campaign end date', 'ClickToDonate'); ?></label>
                            </legend>
                            <div id="ctd-enddate-container" class="start-hidden">
                                <label class="selectit"><?php _e('End date:', 'ClickToDonate'); ?> <input size="8" maxlength="10" title="<?php esc_attr_e('Specify the end date when the campaign is supposed to end', 'ClickToDonate') ?>" id="ctd-enddate" type="text" name="<?php echo(__CLASS__ . self::$endDate); ?>" /></label>
                                <input id="ctd-hidden-enddate" type="hidden" name="<?php echo(__CLASS__ . self::$endDate); ?>" value="<?php echo(date('Y-n-j', $endDate)); ?>" />
                                <div class="ctd-timer-container">@<input title="<?php esc_attr_e('Specify the campaign ending hours', 'ClickToDonate') ?>" size="2" maxlength="2" id="ctd-endhours" name="<?php echo(__CLASS__ . '_endHours'); ?>" type="text" value="<?php echo($endHours[0]); ?>" /><span class="ctd-time-separator"> : </span><input title="<?php esc_attr_e('Specify the campaign ending minutes', 'ClickToDonate') ?>" size="2" maxlength="2" id="ctd-endminutes" name="<?php echo(__CLASS__ . '_endMinutes'); ?>" type="text" value="<?php echo($endMinutes[0]); ?>" /></div>
                            </div>
                        </fieldset>
                    </div>

                    <div id="misc-publishing-actions">

                        <div class="misc-pub-section<?php
                    if (!$can_publish) {
                        echo ' misc-pub-section-last';
                    }
                    ?>">
                            <label for="post_status"><?php _e('Status:', 'ClickToDonate') ?></label>
                            <input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo(esc_attr(('auto-draft' == $post->post_status ) ? ClickToDonateController::STATUS_unavailable : $post->post_status)); ?>" />
                            <select name='post_status' id='post_status' tabindex='4'>
                                <option<?php selected($post->post_status, ClickToDonateController::STATUS_online); ?> value='<?php echo(ClickToDonateController::STATUS_online); ?>'><?php _e('Online', 'ClickToDonate') ?></option>
                                <option<?php selected($post->post_status, ClickToDonateController::STATUS_finished); ?> value='<?php echo(ClickToDonateController::STATUS_finished); ?>'><?php _e('Completed', 'ClickToDonate') ?></option>
                                <option<?php selected($post->post_status, ClickToDonateController::STATUS_scheduled); ?> value='<?php echo(ClickToDonateController::STATUS_scheduled); ?>'><?php _e('Scheduled', 'ClickToDonate') ?></option>
                                <?php if ('auto-draft' == $post->post_status) : ?>
                                    <option<?php selected($post->post_status, 'auto-draft'); ?> value='<?php echo(ClickToDonateController::STATUS_unavailable); ?>'><?php _e('Unavailable', 'ClickToDonate') ?></option>
                                <?php else : ?>
                                    <option<?php selected($post->post_status, ClickToDonateController::STATUS_unavailable); ?> value='<?php echo(ClickToDonateController::STATUS_unavailable); ?>'><?php _e('Unavailable', 'ClickToDonate') ?></option>
                        <?php endif; ?>
                            </select>
                        </div><?php // /misc-pub-section   ?>
            <?php do_action('post_submitbox_misc_actions'); ?>
                    </div>
                    <div class="clear"></div>
                </div>

                <div id="major-publishing-actions">
                        <?php do_action('post_submitbox_start'); ?>
                    <div id="delete-action">
                        <?php
                        if (current_user_can("delete_post", ClickToDonateController::getPostID($post))) {
                            if (!EMPTY_TRASH_DAYS)
                                $delete_text = __('Delete Permanently', 'ClickToDonate');
                            else
                                $delete_text = __('Move to Trash', 'ClickToDonate');
                            ?><a class="submitdelete deletion" href="<?php echo get_delete_post_link(ClickToDonateController::getPostID($post)); ?>"><?php echo($delete_text); ?></a><?php } ?>
                    </div>

                    <div id="publishing-action">
                        <img src="<?php echo esc_url(admin_url('images/wpspin_light.gif')); ?>" class="ajax-loading" id="ajax-loading" alt="" />
                        <?php
                        if (in_array($post->post_status, array(ClickToDonateController::STATUS_unavailable, 'auto-draft')) || 0 == ClickToDonateController::getPostID($post)):
                            if ($can_publish) :
                                if (!empty($startDate) && current_time('timestamp', false) < $startDate &&  (0 != ClickToDonateController::getPostID($post) && !in_array($post->post_status, array('auto-draft')))) :
                                    ?>
                                    <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule', 'ClickToDonate') ?>" />
                                    <?php submit_button(__('Schedule', 'ClickToDonate'), 'primary', 'publish', false, array('tabindex' => '5', 'accesskey' => 'p')); ?>
                                <?php else : ?>
                                    <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish', 'ClickToDonate') ?>" />
                                    <?php submit_button(__('Publish', 'ClickToDonate'), 'primary', 'publish', false, array('tabindex' => '5', 'accesskey' => 'p')); ?>
                                <?php
                                endif;
                            endif;
                        else:
                            ?>
                            <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update', 'ClickToDonate') ?>" />
                            <input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php esc_attr_e('Update', 'ClickToDonate') ?>" />
                        <?php
                        endif;
                        ?>
                    </div>
                    <div class="clear"></div>
                </div>
            </div>

            <?php
        }
        
        
        /**
         * Save the custom data from the metaboxes with the custom post type
         * 
         * @param int $postId
         * @return int with the post id
         */
        public static function savePost($postId) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE):
                return $postId;
            endif;
            switch (get_post_type($postId)):
                case ClickToDonateController::POST_TYPE:
                    // Get the submited data
                    
                    if (isset($_POST[__CLASS__.self::$requireLogin])):
                        $requireLogin = true;
                    else:
                        $requireLogin = false;
                    endif;
                    
                    if (isset($_POST[__CLASS__ . self::$enableCoolOff])):
                        $enableCoolOff = true;
                        $coolOff = isset($_POST[__CLASS__ . self::$coolOff]) ? $_POST[__CLASS__ . self::$coolOff] : -1;

                        // Multiply by the time unit
                        if (isset($_POST[__CLASS__ . self::$coolOffUnit]) && is_numeric($_POST[__CLASS__ . self::$coolOffUnit])):
                            $coolOff*=(int) $_POST[__CLASS__ . self::$coolOffUnit];
                        endif;

                        $restrictByCookie = isset($_POST[__CLASS__ . self::$restrictByCookie]) ? true : false;
                        $restrictByLogin = isset($_POST[__CLASS__ . self::$restrictByLogin]) ? true : false;
                    else:
                        $enableCoolOff = false;
                        $coolOff = -1;
                        $restrictByCookie = false;
                        $restrictByLogin = false;
                    endif;

                    if (isset($_POST[__CLASS__ . self::$enableClickLimits])):
                        $enableClickLimits = true;
                        $maxClicks = isset($_POST[__CLASS__ . self::$maxClicks]) ? $_POST[__CLASS__ . self::$maxClicks] : -1;
                    else:
                        $enableClickLimits = false;
                        $maxClicks = -1;
                    endif;

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

                    // The start date cannot be greater than end date
                    if (is_numeric($startDate) && is_numeric($endDate) && $startDate > $endDate):
                        $t = $startDate;
                        $startDate = $endDate;
                        $endDate = $t;
                    endif;

                    // Save the metadata to the database
                    ClickToDonateController::requireLogin($postId, $requireLogin);
                    ClickToDonateController::enableCoolOffLimit($postId, $enableCoolOff);
                    ClickToDonateController::setCoolOffLimit($postId, $coolOff);
                    ClickToDonateController::enableCookieRestrition($postId, $restrictByCookie);
                    ClickToDonateController::enableLoginRestrition($postId, $restrictByLogin);
                    ClickToDonateController::enableClicksLimit($postId, $enableClickLimits);
                    ClickToDonateController::setMaximumNumberOfClicks($postId, $maxClicks);
                    ClickToDonateController::enableStartDate($postId, $enableStartDate);
                    ClickToDonateController::setStartDate($postId, $startDate);
                    ClickToDonateController::enableEndDate($postId, $enableEndDate);
                    ClickToDonateController::setEndDate($postId, $endDate);

                    // Change the post status based on the metadata
                    ClickToDonateController::updatePostStatus($postId);

                    break;
            endswitch;
            return $postId;
        }

        /**
         * Filter the posts content if they are campaigns, or count the visualizations
         * 
         * @param array $posts
         * @param WP_Query $query
         * @return array with the (possible) filtered posts 
         */
        public static function thePosts($posts, $query) {
            if (empty($posts))
                return $posts;

            if($query->is_single()):
                foreach ($posts as $index => $post):
                    // If is a countable post type, is a single post and we are getting the post from the front office, verify and count the visit
                    if(get_post_type($post) == ClickToDonateController::POST_TYPE && !is_admin()):
                        $status = ClickToDonateController::bannerCanBeShown($post, true);
                        switch($status):
                            // The campaign was finished
                            case ClickToDonateController::MSG_URL_ERROR:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".__('Sorry, but the URL for this campaign is invalid.', 'ClickToDonate')."</span>";
                                break;
                            // The campaign was finished
                            case ClickToDonateController::MSG_AUTHENTICATION_ERROR:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".sprintf(__('You must %s in the site to validate your visit.', 'ClickToDonate'), '<a href="'.wp_login_url(home_url()).'" title="'.__('Follow the link to login in the site', 'ClickToDonate').'">'.__('login', 'ClickToDonate').'</a>')."</span>";
                                break;
                            // The campaign was finished
                            case ClickToDonateController::MSG_CAMPAIGN_FINISHED:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".__('Thanks for visiting, but this campaign has been completed.', 'ClickToDonate')."</span>";
                                break;
                            // The campaign is scheduled to start
                            case ClickToDonateController::MSG_CAMPAIGN_SCHEDULED:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".__('Thanks for visiting, but this campaign has not started.', 'ClickToDonate')."</span>";
                                break;
                            case ClickToDonateController::MSG_CAMPAIGN_UNAVAILABLE:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".__('Thanks for visiting, but this campaign is not available.', 'ClickToDonate')."</span>";
                                break;
                            case ClickToDonateController::MSG_RESTRITED_BY_COOKIE:
                            case ClickToDonateController::MSG_RESTRITED_BY_LOGIN:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".sprintf(__('Thanks for visiting, but this campaign has already been visited by you recently. Please try again in %s.', 'ClickToDonate'), self::stringfyTime(ClickToDonateController::getCoolOffLimitRemainingForAuthenticatedUser($post)))."</span>";
                                break;
                            // Everything ok, let's try to register the visit
                            case ClickToDonateController::MSG_OK:
                                if (ClickToDonateController::registerVisit($post)):
                                    $posts[$index]->post_content.="<span class=\"ctd-visit-registered\"><hr/>".__('<br/>Visit registered.', 'ClickToDonate')."</span>";
                                    break;
                                endif;
                            // Houston, we have problems
                            case ClickToDonateController::MSG_UNKNOWN_ERROR:
                            case ClickToDonateController::MSG_UNKNOWN_POST_STATUS:
                            case ClickToDonateController::MSG_UNKNOWN_POST_TYPE:
                            default:
                                    $posts[$index]->post_content="<span class=\"ctd-visit-error\">".__('Unable to register the visit. Please try again later.', 'ClickToDonate')."</span>";
                                break;
                        endswitch;
                    endif;
                endforeach;
            endif;
            
            return $posts;
        }
        
        /**
         * Filter the content and remove the links for inaccessible campaigns
         * 
         * @param string $content 
         */
        public static function theContent($content){
            $matches = array();
            $patterns = array();
            $replacements = array();
            
            if(preg_match_all('/<a\s*[^>]*href\s*=\s*([\"\']??)\#ctd\-(\d+)\\1[^>]*>(.*)<\/a>/siU', $content, $matches, PREG_SET_ORDER)>0):
                foreach($matches as $match):
                    // $match[0] - full match
                    // $match[2] - id of the campaign
                    // $match[3] - content of the link tag
                    $postId = $match[2];
                    $status = ClickToDonateController::bannerCanBeShown($postId, false, true);
                    switch ($status):
                        case ClickToDonateController::MSG_OK:
                            $patterns[$postId]='/<a\s*([^>]*)href\s*=\s*([\"\']??)\#ctd\-'.preg_quote($postId).'\\2([^>]*)>(.*)<\/a>/siU';
                            $replacements[$postId]= '<a $1href="'.get_permalink($postId).'"$3>$4</a>';

                            break;
                        
                        case ClickToDonateController::MSG_AUTHENTICATION_ERROR:
                            $patterns[$postId]='/<a\s*([^>]*)href\s*=\s*([\"\']??)\#ctd\-'.preg_quote($postId).'\\2([^>]*)>(.*)<\/a>/siU';
                            $replacements[$postId]= '<a $1href="'.wp_login_url(get_permalink()).'"$3>$4</a>';
                            
                            break;

                        default:
                            $patterns[$postId]='/<a\s*[^>]*href\s*=\s*([\"\']??)\#ctd\-'.preg_quote($postId).'\\1[^>]*>(.*)<\/a>/siU';
                            $replacements[$postId]= '';
                    endswitch;
                endforeach;
            endif;
            
            return preg_replace($patterns, $replacements, $content);
        }

        /**
         * @return the prefix to append to the scripts and CSS files when the debug mode is enabled
         */
        public static function debugSufix() {
            return ((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) || self::$internalDebugEnabled ? '.dev' : '');
        }
        
        /**
         * Convert a seconds time stamp representation into a pretty string
         * @param type $timestamp
         * @return type 
         */
        public static function stringfyTime($timestamp){
            $seconds = $timestamp % 60;
            $timestamp /= 60;
            $minutes = $timestamp % 60;
            $timestamp /= 60;
            $hours = $timestamp % 24;
            $timestamp /= 24;
            $days = floor($timestamp);
            $time = array();
            if($days>0){
                $time[]=sprintf(__('%s days', 'ClickToDonate'), $days);
            }
            if($hours>0){
                $time[]=sprintf(__('%s hours', 'ClickToDonate'), $hours);
            }
            if($minutes>0){
                $time[]=sprintf(__('%s minutes', 'ClickToDonate'), $minutes);
            }
            if($seconds>0){
                $time[]=sprintf(__('%s seconds', 'ClickToDonate'), $seconds);
            }
            return implode(' ', $time);
        }


        /**
         * Verify if the user has the necessary permission to access this resource
         * @return type 
         */
        private static function hasPermission() {
            return ((current_user_can('edit_posts') || current_user_can('edit_pages')) && user_can_richedit());
        }
    }
endif;
ClickToDonateView::init();