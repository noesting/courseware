<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Setup tables for the mooc.ip courseware-plugin and adds editable display name of the plugin.
 *
 * @author <andre@gundk.it>
 */


class SetupCourseware extends DBMigration {

    public function description () {
        return 'Setup tables for the mooc.ip courseware-plugin and adds editable display name of the plugin.';
    }

    public function up () {
      
        $db = DBManager::get();

        // check if Mooc.IP is already installed and the schema-version of Mooc.IP
        // If it is the most recent one, do nothing,
        // if the schema-version is to old, do an exit rescue with an error message
        $version = $db->fetchColumn("SELECT version FROM schema_version WHERE domain = 'Mooc.IP'");

        if ($version && $version >= 20) {
            // tables are there, no further action needed
            return;
        } else if ($version && $version < 20) {
            throw new Exception('Please upgrade your Mooc.IP-Plugin to at least Version 1.0.4 or deinstall it completely if you do not need it!');
        }

        // if no Mooc.IP-installion is found, create the courseware tables
        
        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_blocks` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `type` varchar(64) NOT NULL,
          `sub_type` varchar(64) DEFAULT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `seminar_id` varchar(32) DEFAULT NULL,
          `title` varchar(255) DEFAULT NULL,
          `position` int(11) DEFAULT '0',
          `publication_date` int(11) DEFAULT NULL,
          `chdate` int(11) DEFAULT NULL,
          `mkdate` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_fields` (
          `block_id` int(11) NOT NULL,
          `user_id` varchar(32) NOT NULL,
          `name` varchar(255) NOT NULL,
          `json_data` mediumtext,
          PRIMARY KEY (`block_id`,`user_id`,`name`)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_userprogress` (
          `block_id` int(11) NOT NULL,
          `user_id` varchar(32) NOT NULL DEFAULT '',
          `grade` double DEFAULT NULL,
          `max_grade` double NOT NULL DEFAULT '1',
          PRIMARY KEY (`block_id`,`user_id`)
        )");


        if (is_null(Config::get()->getValue(\Mooc\PLUGIN_DISPLAY_NAME_ID))) {
            Config::get()->create(\Mooc\PLUGIN_DISPLAY_NAME_ID, array(
                'value'       => 'Mooc.IP',
                'is_default'  => 1,
                'type'        => 'string',
                'range'       => 'global',
                'section'     => 'global',
                'description' => 'Angezeigter Name des Plugins'
            ));
        }

        SimpleORMap::expireTableScheme();
    }

    public function down ()
    {
        // To avoid data loss, nothing is deleted by default
        // remove the following "return;"-statement to clean tables on uninstall
        return;

        DBManager::get()->exec("DROP TABLE mooc_blocks");
        DBManager::get()->exec("DROP TABLE mooc_userprogress");
        DBManager::get()->exec("DROP TABLE mooc_fields");
        
        Config::get()->delete(\Mooc\PLUGIN_DISPLAY_NAME_ID);
        
        SimpleORMap::expireTableScheme();
    }
}
