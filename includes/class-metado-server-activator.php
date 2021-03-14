<?php

/**
 * Fired during plugin activation
 *
 * @link       https://rockiger.com
 * @since      1.0.0
 *
 * @package    Metado_Server
 * @subpackage Metado_Server/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Metado_Server
 * @subpackage Metado_Server/includes
 * @author     Marco Laspe <marco@rockiger.com>
 */
class Metado_Server_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$db_prefix = $wpdb->prefix . 'metado_';

		$boards_table_name = $db_prefix . 'boards';
		$boards_table = "CREATE TABLE $boards_table_name (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    columns json NOT NULL,
		isDeleted boolean NOT NULL DEFAULT false,
		showBacklog boolean NOT NULL DEFAULT true,
		title varchar(255) NOT NULL,
		createdBy bigint(20) unsigned NOT NULL default 0,
		updatedBy bigint(20) unsigned NOT NULL default 0,
		createdAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updatedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
		KEY title (title)
  ) $charset_collate;";

		$projects_table_name = $db_prefix . 'projects';
		$projects_table = "CREATE TABLE $projects_table_name (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL,
		project_type varchar(255) NOT NULL,
		project_meta json,
		createdBy bigint(20) unsigned NOT NULL default 0,
		updatedBy bigint(20) unsigned NOT NULL default 0,
		createdAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updatedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
		KEY title (title)
  ) $charset_collate;";

		$boards_2_projects_table_name = $db_prefix . 'boards_2_projects';
		$boards_2_projects_table = "CREATE TABLE $boards_2_projects_table_name (
    board_id bigint(20) unsigned NOT NULL DEFAULT 0,
    project_id bigint(20) unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY  (board_id, project_id),
		KEY project_id (project_id)
  ) $charset_collate;";

		$tasks_table_name = $db_prefix . 'tasks';
		$tasks_table = "CREATE TABLE $tasks_table_name (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		task_description longtext,
		finishedAt datetime,
		project_id bigint(20) unsigned NOT NULL default 0,
		task_status varchar(128) NOT NULL,
		title varchar(255) NOT NULL,
		createdBy bigint(20) unsigned NOT NULL default 0,
		updatedBy bigint(20) unsigned NOT NULL default 0,
		createdAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updatedAt datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
		KEY title (title)
  ) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$sql_statements = [$boards_table, $projects_table, $boards_2_projects_table, $tasks_table];

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_statements);
	}
}
