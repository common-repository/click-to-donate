<?php
/**
 * Provides the model functionality for the plugin 
 */

if (!class_exists('ClickToDonateVisitsData')):
    /**
     * Class to represent the data from the visits
     */
    class ClickToDonateVisitsData {
        public $dateGranularity = ClickToDonateModel::DATE_GRANULARITY_DAYS;
        public $userId = false;
        public $startDate = 0;
        public $endDate = 0;
        public $postIds = array();
        public $data = array();
    }
endif;

if (!class_exists('ClickToDonateModel')):
    class ClickToDonateModel {
        
        /**
         * The database variable name to store the plugin database version
         */
        const DB_VERSION_FIELD_NAME = 'ClickToDonateDbVersion';
        const DATE_GRANULARITY_DAYS = 'DATE';
        const DATE_GRANULARITY_MONTHS = 'MONTH';
        const DATE_GRANULARITY_YEARS = 'YEAR';
        
        // Table variables name
        private static $tableClicks = 'clicks';
        private static $tableClicksID = 'ID';
        private static $tableClicksBannerID = 'bannerID';
        private static $tableClicksUserID = 'userID';
        private static $tableClicksTimestamp = 'timestamp';
        private static $tableSponsoredCampaigns = 'sponsoredCampaign';
        private static $tableSponsoredCampaignsCampaignID = 'campaignID';
        private static $tableSponsoredCampaignsUserID = 'userID';
        private static $prefixedTables = false;
        
        /**
         * Class constructor 
         */
        public function __construct() {
            
        }
        
        /**
         * Return the WordPress Database Access Abstraction Object 
         * 
         * @global wpdb $wpdb
         * @return wpdb 
         */
        public static function getWpDB() {
            global $wpdb;
            
            if(!self::$prefixedTables):
                $prefix = $wpdb->prefix;
                // Append the Wordpress table prefix to the table names (if the prefix isn't already added)
                self::$tableClicks = (stripos(self::$tableClicks, $prefix) === 0 ? '' : $prefix) . self::$tableClicks;
                self::$tableSponsoredCampaigns = (stripos(self::$tableSponsoredCampaigns, $prefix) === 0 ? '' : $prefix) . self::$tableSponsoredCampaigns;
            endif;
            return $wpdb;
        }
        
        /**
         * Install the database tables
         */
        public static function install() {

            // Load the libraries
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');

            // Load the plugin version
            $plugin = get_plugin_data(ClickToDonate::FILE);
            $version = $plugin['Version'];

            // Compare the plugin version with the local version, and update the database tables accordingly
            if (version_compare(get_option(self::DB_VERSION_FIELD_NAME), $version, '<')):

                // cache the errors
                ob_start();

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

                // Prepare the SQL queries for sponsored campaigns
                $queries = array();
                $queries[] = "
                        CREATE TABLE IF NOT EXISTS `".self::$tableSponsoredCampaigns."` (
                            `".self::$tableSponsoredCampaignsCampaignID."` bigint(20) unsigned NOT NULL COMMENT 'Foreign key for the campaign',
                            `".self::$tableSponsoredCampaignsUserID."` bigint(20) unsigned NOT NULL COMMENT 'Foreign key for the user',
                            KEY `".self::$tableSponsoredCampaignsUserID."` (`".self::$tableSponsoredCampaignsUserID."`),
                            KEY `".self::$tableSponsoredCampaignsCampaignID."` (`".self::$tableSponsoredCampaignsCampaignID."`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Implementation of the campaigns-sponsors relationship';
                    ";
                /* // Wordpress doesn't enforce the InnoDB MySQL engine to use on their tables, so we cannot enable the foreign key constrainsts until the Wordpress requires the MySQL 5.5 as the minimum requirement
                  $queries[] = "
                  ALTER TABLE `".self::$tableSponsoredCampaigns."`
                  ADD CONSTRAINT `sponsoredCampaign_ibfk_1` FOREIGN KEY (`".self::$tableSponsoredCampaignsCampaignID."`) REFERENCES `{$prefix}posts` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
                  ADD CONSTRAINT `sponsoredCampaign_ibfk_2` FOREIGN KEY (`".self::$tableSponsoredCampaignsUserID."`) REFERENCES `{$prefix}users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
                  ;
                  ";
                 */
                dbDelta($queries);

                // Prepare the SQL queries for clicks
                $queries = array();
                $queries[] = "
                        CREATE TABLE IF NOT EXISTS `".self::$tableClicks."` (
                            `".self::$tableClicksID."` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Key for the click',
                            `".self::$tableClicksBannerID."` bigint(20) unsigned DEFAULT NULL COMMENT 'Foreign key of the banner',
                            `".self::$tableClicksUserID."` bigint(20) unsigned DEFAULT NULL COMMENT 'Foreign key of the user',
                            `".self::$tableClicksTimestamp."` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Time stamp of the click',
                            PRIMARY KEY (`".self::$tableClicksID."`),
                            KEY `".self::$tableClicksBannerID."` (`".self::$tableClicksBannerID."`),
                            KEY `".self::$tableClicksUserID."` (`".self::$tableClicksUserID."`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table to store the site clicks on campaigns and/or banners' AUTO_INCREMENT=1 ;
                    ";
                /* // Wordpress doesn't enforce the InnoDB MySQL engine to use on their tables, so we cannot enable the foreign key constrainsts until the Wordpress requires the MySQL 5.5 as the minimum requirement
                  $queries[] = "
                  ALTER TABLE `".self::$tableClicks."`
                  ADD CONSTRAINT `clicks_ibfk_3` FOREIGN KEY (`".self::$tableClicksUserID."`) REFERENCES `{$prefix}users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
                  ADD CONSTRAINT `clicks_ibfk_2` FOREIGN KEY (`".self::$tableClicksBannerID."`) REFERENCES `{$prefix}posts` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE
                  ;
                  ";
                 */
                dbDelta($queries);

                // If errors were triggered, output them
                $contents = ob_get_contents();
                if (!empty($contents)):
                    trigger_error($contents, E_USER_ERROR);
                endif;

                // Update the plugin DB version
                update_option(self::DB_VERSION_FIELD_NAME, $version);
            endif;
        }

        /**
         * Uninstall the plugin data
         */
        public static function uninstall() {
            // Get the WordPress database abstration layer instance
            $wpdb = self::getWpDB();

            $wpdb->query("DROP TABLE IF EXISTS `".self::$tableClicks."`;");
            $wpdb->query("DROP TABLE IF EXISTS `".self::$tableSponsoredCampaigns."`;");

            // Remove the plugin version information
            delete_option(self::DB_VERSION_FIELD_NAME);

            // @TODO: remove all the metadata from the wordpress tables e.g., user table
        }
        
        
        /**
         * Register a visit in the system
         * 
         * @param int $post
         * @return boolean true if the visit was successfuly registered, false otherwise
         */
        public static function registerVisit($post) {
            $wpdb = self::getWpDB();
            
            if (is_int($post) && absint($post) && $post>0):

                $data = array(
                    self::$tableClicksBannerID => $post/* ,
                    self::$tableClicksTimestamp => current_time('mysql', true) */
                );
                $dataTypes = array(
                    '%d'/* ,
                    '%s' */
                );
                if ($userId = get_current_user_id()):
                    $data[self::$tableClicksUserID] = $userId;
                    $dataTypes[] = '%d';
                endif;

                if ($wpdb->insert(self::$tableClicks, $data, $dataTypes)):
                    return true;
                endif;
            endif;
            return false;
        }
        
        
        /**
         * Count the visits on a banner
         * 
         * @param int $post
         * @param int $user to filter the visits by a specific user
         * @return int with the number of visits
         */
        public static function countBannerVisits($post=0, $user = 0) {
            $wpdb = self::getWpDB();
            $extra = '';
            $params = array();
            if (is_int($post) && absint($post) && $post>0):
                $extra .= ' AND `' . self::$tableClicksBannerID . '`=%d';
                $params[] = $post;
            endif;
            if (is_int($user) && absint($user)):
                $extra .= ' AND `' . self::$tableClicksUserID . '`=%d';
                $params[] = $user;
            endif;
            if ($row = $wpdb->get_row($wpdb->prepare('
			    SELECT
				COUNT(*) AS `total`
			    FROM `' . self::$tableClicks . '` WHERE 1 ' . $extra . ';
			', $params), ARRAY_A)):
                return (int) $row['total'];
            endif;
            return 0;
        }
        
        /**
         * Get the timestamp of the last visit to the banner
         * @param int $post
         * @param int $user
         * @return int 
         */
        public static function getLastBannerVisit($post=0, $user = 0) {
            $wpdb = self::getWpDB();
            $extra = '';
            $params = array();
            if (is_int($post) && absint($post) && $post>0):
                $extra .= ' AND `' . self::$tableClicksBannerID . '`=%d';
                $params[] = $post;
            endif;
            if (is_int($user) && absint($user)):
                $extra .= ' AND `' . self::$tableClicksUserID . '`=%d';
                $params[] = $user;
            endif;
            if ($row = $wpdb->get_row($wpdb->prepare('
                    SELECT MAX(UNIX_TIMESTAMP(`' . self::$tableClicksTimestamp . '`)) AS `last`
                    FROM `' . self::$tableClicks . '` WHERE 1 ' . $extra . ';
                ', $params), ARRAY_A)):
                return (int) $row['last'];
            endif;
            return 0;
        }
        
        /**
         * Count the visits on a banner based on the given parameters
         * 
         * @param int $post
         * @param int $user to filter the visits by a specific user
         * @return ClickToDonateVisitsData with the visits data
         */
        public static function getBannerVisits($post=0, $user = 0, $startDate=0, $endDate=0, $dateGranularity=ClickToDonateModel::DATE_GRANULARITY_DAYS) {
            $wpdb = self::getWpDB();
            
            $visitsData = new ClickToDonateVisitsData();
            $select=array();
            $where=array();
            $groupBy=array();
            $orderBy=array();
            $dateField=esc_sql('date');
            
            // Based on the required date granularity, configure the query accordingly
            switch($dateGranularity):
                case ClickToDonateModel::DATE_GRANULARITY_YEARS:
                    $visitsData->dateGranularity=$dateGranularity;
                    
                    $select[] = 'YEAR(`'.self::$tableClicksTimestamp.'`) AS `'.$dateField.'`';
                    $groupBy[] = 'YEAR(`'.self::$tableClicksTimestamp.'`)';
                    $orderBy[]="`{$dateField}` ASC";
                    break;
                
                case ClickToDonateModel::DATE_GRANULARITY_MONTHS:
                    $visitsData->dateGranularity=$dateGranularity;
                    
                    $select[] = 'CONCAT(YEAR(`'.self::$tableClicksTimestamp.'`), \'-\', MONTH(`'.self::$tableClicksTimestamp.'`)) AS `'.$dateField.'`';
                    $groupBy[] = 'YEAR(`'.self::$tableClicksTimestamp.'`), MONTH(`'.self::$tableClicksTimestamp.'`)';
                    $orderBy[]="`{$dateField}` ASC";
                    break;
                case ClickToDonateModel::DATE_GRANULARITY_DAYS:
                default:
                    $visitsData->dateGranularity=ClickToDonateModel::DATE_GRANULARITY_DAYS;
                    
                    $select[] = 'DATE(`'.self::$tableClicksTimestamp.'`) AS `'.$dateField.'`';
                    $groupBy[] = 'DATE(`'.self::$tableClicksTimestamp.'`)';
                    $orderBy[]="`{$dateField}` ASC";
            endswitch;
            
            $params = array();
            
            // A specific user was request?
            if (is_int($user) && absint($user) && $user>0):
                $visitsData->userId = (int) $user;
                $where[] = '`' . self::$tableClicksUserID . '`=%d';
                $params[] = $user;
            endif;
            
            // Have we a start timestamp?
            if (is_int($startDate) && absint($startDate) && $startDate>0):
                $visitsData->startDate = (int) $startDate;
                $where[] = '`'.self::$tableClicksTimestamp.'`>=FROM_UNIXTIME(%d)';
                $params[] = $startDate;
            endif;
            
            // And a end timestamp?
            if (is_int($endDate) && absint($endDate) && $endDate>0):
                $visitsData->endDate = (int) $endDate;
                $where[] = '`'.self::$tableClicksTimestamp.'`<=FROM_UNIXTIME(%d)';
                $params[] = $endDate;
            endif;
            
            $totalField=esc_sql('total');
            
            // If a specific post was requested
            if (is_int($post) && absint($post) && $post>0):
                $visitsData->postIds[] = (int) $post;
                $where[] = '`' . self::$tableClicksBannerID . '`=%d';
                $params[] .= $post;
            
                $select[] = 'COUNT('.self::$tableClicksID.') AS `'.esc_sql($totalField).'`';
            else:
            // Or the query is for all the banners
                $select[] = '`' . self::$tableClicksBannerID . '` AS `bannerID`';
                $select[] = 'COUNT('.self::$tableClicksBannerID.') AS `'.esc_sql($totalField).'`';
                $groupBy[] = '`bannerID`';
                $visitsData->postIds = $wpdb->get_results($wpdb->prepare(
                    'SELECT DISTINCT(`' . self::$tableClicksBannerID . '`) AS `bannerID` '.
                    'FROM `' . self::$tableClicks . '` '.
                    (!empty($where)?' WHERE '.implode(' AND ', $where):'').
                    ';', $params), ARRAY_A);
            endif;
            
            // Load the data
            if (($rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT '.implode(', ', $select).' '.
                    'FROM `' . self::$tableClicks . '` '.
                    (!empty($where)?' WHERE '.implode(' AND ', $where):'').
                    (!empty($groupBy)?' GROUP BY '.implode(', ', $groupBy):'').
                    (!empty($orderBy)?' ORDER BY '.implode(', ', $orderBy):'').
                ';', $params), ARRAY_A)) && !empty($rows)):
                
                // If we have more than one banner, rearrange the data to a more suitable form
                if(count($visitsData->postIds)>=1 && $post<=0):
                    $tRows = array();
                    $bannerColumns = array();
                    
                    foreach ($visitsData->postIds as $banner):
                        $bannerColumns[$banner['bannerID']]=0;
                    endforeach;
                    
                    foreach ($rows as $row):
                        if(!isset($tRows[$row[$dateField]]) || !is_array($tRows[$row[$dateField]])):
                            $tRows[$row[$dateField]]=array($dateField=>$row[$dateField])+$bannerColumns;
                        endif;
                        
                        $tRows[$row[$dateField]][$row['bannerID']]=$row[$totalField];
                    endforeach;
                    $rows = $tRows;
                endif;
                
                $visitsData->data = $rows;
                return $visitsData;
            endif;
            return $visitsData;
        }
        
        /**
         * Count the visits on a banner
         * 
         * @param int $post
         * @param int $user to filter the visits by a specific user
         * @return int with the number of visits
         */
        public static function getBannerParticipantsClicks($post=0, $user = 0, $startDate=0, $endDate=0) {
            $wpdb = self::getWpDB();
            
            $totalField=esc_sql('total');
            $where=array();
            $select=array('`' . self::$tableClicksUserID . '` AS `userID`', 'COUNT('.self::$tableClicksUserID.') AS `'.$totalField.'`');
            $groupBy=array('`' . self::$tableClicksUserID . '`');
            $orderBy=array("`{$totalField}` DESC");
            
            $params = array();
            if (is_int($user) && absint($user) && $user>0):
                $where[] = '`' . self::$tableClicksUserID . '`=%d';
                $params[] = $user;
            endif;
            if (is_int($startDate) && absint($startDate) && $startDate>0):
                $where[] = '`'.self::$tableClicksTimestamp.'`>=FROM_UNIXTIME(%d)';
                $params[] = $startDate;
            endif;
            if (is_int($endDate) && absint($endDate) && $endDate>0):
                $where[] = '`'.self::$tableClicksTimestamp.'`<=FROM_UNIXTIME(%d)';
                $params[] = $endDate;
            endif;
            
            if (is_int($post) && absint($post) && $post>0):
                $where[] = '`' . self::$tableClicksBannerID . '`=%d';
                $params[] = $post;
            endif;
            
            if (($rows = $wpdb->get_results($wpdb->prepare(
                    'SELECT '.implode(', ', $select).' '.
                    'FROM `' . self::$tableClicks . '` '.
                    (!empty($where)?' WHERE '.implode(' AND ', $where):'').
                    (!empty($groupBy)?' GROUP BY '.implode(', ', $groupBy):'').
                    (!empty($orderBy)?' ORDER BY '.implode(', ', $orderBy):'').
                ';', $params), ARRAY_A)) && !empty($rows)):
                
                return $rows;
            endif;
            return array();
        }
    }
endif;