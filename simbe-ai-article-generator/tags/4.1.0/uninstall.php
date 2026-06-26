<?php
/**
 * Simbe AI Article Generator Uninstall
 * 
 * Removes all plugin data when uninstalled via WordPress admin.
 * This file runs when user deletes the plugin from Plugins page.
 */

if (!defined('WP_UNINSTALL_PLUGIN') || !defined('ABSPATH')) {
    exit;
}

if (!current_user_can('delete_plugins')) {
    wp_die();
}

global $wpdb;

$table_name = $wpdb->prefix . 'simbe1_article_tracking';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

delete_option('simbe1_articles_options');

$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_simbe1_%'");