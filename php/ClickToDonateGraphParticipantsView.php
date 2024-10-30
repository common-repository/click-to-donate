<?php
/**
 * Provides the view functionality for the plugin 
 */

if (!class_exists('ClickToDonateGraphParticipantsView')):
    class ClickToDonateGraphParticipantsView {
        
        public static function init(){
            
            if (is_admin()):
                // Register the addMetaBox method to the Wordpress backoffice administration initialization action hook
                add_action('admin_init', array(__CLASS__, 'addMetaBox'));
            
                // Register the wpDashboardSetup method to the wp_dashboard_setup action hook
                add_action('wp_dashboard_setup', array(__CLASS__, 'wpDashboardSetup'));

                // Register the adminEnqueueScripts method to the Wordpress admin_enqueue_scripts action hook
                add_action('admin_enqueue_scripts', array(__CLASS__, 'adminEnqueueScripts'));

                // Register the adminPrintStyles method to the Wordpress admin_print_styles action hook
                add_action('admin_print_styles', array(__CLASS__, 'adminPrintStyles'));
                
                add_action('wp_ajax_' . 'ctd_get_rankings', array(__CLASS__, 'getRankings'));
            endif;
        }
        
        /**
         * Register the scripts to be loaded on the backoffice, on our custom post type
         */
        public static function adminEnqueueScripts() {
            if (is_admin()):
                $suffix = ClickToDonateView::debugSufix();
            
                $current_screen = get_current_screen();
                if(current_user_can('read') && ($current_screen->id=='dashboard' || $current_screen->post_type == ClickToDonateController::POST_TYPE)):
                    // Register the scripts
                    wp_enqueue_script('google-jsapi', 'https://www.google.com/jsapi');
                    
                    // Admin script
                    wp_enqueue_script(ClickToDonate::CLASS_NAME.'_common', plugins_url("js/common{$suffix}.js", ClickToDonate::FILE), array('jquery', 'jquery-ui-datepicker'), '1.0');
                    wp_enqueue_script(ClickToDonate::CLASS_NAME.'_graph_visitors', plugins_url("js/ctd-graph-participants{$suffix}.js", ClickToDonate::FILE), array(ClickToDonate::CLASS_NAME.'_common', 'jquery', 'google-jsapi', 'jquery-ui-datepicker'), '1.0');
                    wp_localize_script(ClickToDonate::CLASS_NAME . '_graph_visitors', 'ctdGraphParticipantsL10n', array(
                        'language' => esc_js(esc_js(get_bloginfo('language'))),
                        'loading' => esc_js(__( 'Loading...', 'ClickToDonate' )),
                        'withoutdata' => esc_js(__( 'Without data to show', 'ClickToDonate' )),
                        'nogoogle' => esc_js(__( 'Sorry, but the Google Chart API was not found. Probably there is no Internet connection available.', 'ClickToDonate' )),
                        'day' => esc_js(__( 'Day', 'ClickToDonate' )),
                        'days' => esc_js(__( 'Days', 'ClickToDonate' )),
                        'totalVisits' => esc_js(__('Total visits', 'ClickToDonate')),
                        'privateMethodDoesNotExist' => __('Private method {0} does not exist', 'ClickToDonate'),
                        'methodDoesNotExist' => __('Method {0} does not exist', 'ClickToDonate'),
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
                endif;
            endif;
        }
        
        /**
         * Register the styles to be loaded on the backoffice on our custom post type
         */
        public static function adminPrintStyles() {
            if (is_admin()):
                $suffix = ClickToDonateView::debugSufix();
            
                $current_screen = get_current_screen();
                if(current_user_can('read') && ($current_screen->id=='dashboard' || $current_screen->post_type == ClickToDonateController::POST_TYPE)):
                    wp_enqueue_style(ClickToDonate::CLASS_NAME . '_jquery-ui-theme', plugins_url("css/jquery-ui/jquery-ui-1.10.3.custom{$suffix}.css", ClickToDonate::FILE), array(), '1.10.3');
                    wp_enqueue_style(ClickToDonate::CLASS_NAME . '_common', plugins_url("css/common$suffix.css", ClickToDonate::FILE), array(ClickToDonate::CLASS_NAME . '_jquery-ui-theme'), '1.0');
                endif;
            endif;
        }

        /**
         * Add a metabox to dashboard
         */
        public static function wpDashboardSetup() {
            // Add our metabox with the graphics to the dashboard
            if(current_user_can('read')):
                wp_add_dashboard_widget(__CLASS__, __('Campaigns user rankings', 'ClickToDonate'), array(__CLASS__, 'writeMetaBox'));
            endif;
        }

        /**
         * Add a metabox to the campaign post type
         */
        public static function addMetaBox() {
            // Add our metabox with the graphics to our custom post type
            if(current_user_can('list_users')):
                add_meta_box(__CLASS__, __('Campaign user rankings', 'ClickToDonate'), array(__CLASS__, 'writeMetaBox'), ClickToDonateController::POST_TYPE);
            endif;
        }

        /**
         * Output a custom metabox for saving the post
         * @param Object $post 
         */
        public static function writeMetaBox($post) {
            
            if(current_user_can('read')):
                ?>
                    <div style="margin: 10px 0 20px;">
                        <label class="selectit"><?php _e('Period start date:', 'ClickToDonate'); ?> <input style="text-align: center;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the period start date', 'ClickToDonate') ?>" id="ctd-graphparticipants-startdate" type="text" /></label>
                        <input id="ctd-hidden-graphparticipants-startdate" type="hidden" value="<?php printf("%.0f", ((current_time('timestamp')-3600*24*7)*1000)); ?>" />
                        <label class="selectit"><?php _e('Period end date:', 'ClickToDonate'); ?> <input style="text-align: center;" size="8" maxlength="10" title="<?php esc_attr_e('Specify the period end date', 'ClickToDonate') ?>" id="ctd-graphparticipants-enddate" type="text" /></label>
                        <input id="ctd-hidden-graphparticipants-enddate" type="hidden" value="<?php printf("%.0f", (current_time('timestamp')*1000)); ?>" />

                        <a class="button" id="ctd-load-graphparticipants"><?php _e('Load', 'ClickToDonate'); ?></a>
                    </div>
                    <div id="ctd-graphparticipants-container" style='width: 100%;'></div>
                    <script>
                        google.load("visualization", "1.1", {
                            packages:["table"], 
                            'language': ctdGraphParticipantsL10n.language
                        });

                        $j('#ctd-load-graphparticipants').click(function(){
                            var $j = jQuery.noConflict();
                            $j('#ctd-graphparticipants-container').ctdGraphVisitors(
                                'loadData',{
                                    'action' : 'ctd_get_rankings',
                                    '_ajax_ctd_get_rankings_nonce' : '<?php echo(esc_attr(wp_create_nonce('ctd-get-rankings'))); ?>',
                                    'startDate': ($j("#ctd-hidden-graphparticipants-startdate").val()/1000),
                                    'endDate': ($j("#ctd-hidden-graphparticipants-enddate").val()/1000+3600*24-1)
                                    <?php if(isset($post)): echo(", 'postId' : '".esc_js(ClickToDonateController::getPostID($post))."'"); endif; ?>
                                }
                            );
                            return false;
                        });
                    </script>
                <?php
            endif;
        }
        
        /**
         * Send the campaigns list as a response of an ajax request 
         */
        public static function getRankings() {
            
            if(current_user_can('read')):
                check_ajax_referer('ctd-get-rankings', '_ajax_ctd_get_rankings_nonce');

                $postId = !empty($_POST['postId']) ? absint($_POST['postId']) : 0;

                $startDate = !empty($_POST['startDate']) ? absint($_POST['startDate']) : 0;
                $endDate = !empty($_POST['endDate']) ? absint($_POST['endDate']) : 0;

                $results = ClickToDonateController::getBannerParticipantsClicks($postId, 0, $startDate, $endDate);

                if (!isset($results) || empty($results))
                    die('0');

                $resultsArray = array();
                $resultsArray[] = array(__('Position', 'ClickToDonate'), __('User', 'ClickToDonate'), __('Participations', 'ClickToDonate'));
                $position = 1;
                $canListUsers = current_user_can('list_users');
                $currentUserId = get_current_user_id();
                $currentUserAdded = false;
                foreach ($results as $result):
                    $values = array();
                    foreach ($result as $value):
                        if(empty($values)):
                            if($position++>3 && !$canListUsers && $value!=$currentUserId):
                                break;
                            endif;
                            $values[] = $position-1;
                            if(!$canListUsers && $value!=$currentUserId):
                                $values[] = '-';
                            else:
                                $user = get_userdata($value);
                                $values[] = $user->display_name;
                                if($value==$currentUserId):
                                    $currentUserAdded=true;
                                endif;
                            endif;
                        else:
                            $values[] = (int)$value;
                        endif;
                    endforeach;
                    if(!empty($values)):
                        $resultsArray[] = $values;
                    endif;
                endforeach;
                
                // Add the current user statistics
                if(!empty($results) && !$currentUserAdded && $user = get_userdata(get_current_user_id())):
                    $resultsArray[] = array($position++, $user->display_name, 0);
                endif;

                echo json_encode($resultsArray);
                echo "\n";

                exit;
            endif;
        }
    }
endif;
ClickToDonateGraphParticipantsView::init();