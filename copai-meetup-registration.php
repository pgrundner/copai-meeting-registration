<?php
/**
 * Plugin Name: CoPAI Meetup Registration
 * Description: Custom Post Type "Meetup" mit Anmeldeformular, E-Mail-Bestätigung und Admin-Übersicht.
 * Version:     1.0.0
 * Author:      Peter Grundner
 * License:     MIT
 * Project Community of Practice AI 2023-2-AT01-KA210-VET-000169864
 *
 * Förderhinweis:
 * 
 * Von der Europäischen Union finanziert. Die geäußerten Ansichten und Meinungen entsprechen jedoch 
 * ausschließlich denen des Autors bzw. der Autoren und spiegeln nicht zwingend die der Europäischen 
 * Union oder der OeAD-GmbH wider. 
 * Weder die Europäische Union noch die OeAD-GmbH können dafür verantwortlich gemacht werden.
 */
defined('ABSPATH') || exit;

define('CMR_VERSION',    '1.0.0');
define('CMR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CMR_PLUGIN_DIR . 'includes/class-cmr-cpt.php';
require_once CMR_PLUGIN_DIR . 'includes/class-cmr-db.php';
require_once CMR_PLUGIN_DIR . 'includes/class-cmr-email.php';
require_once CMR_PLUGIN_DIR . 'includes/class-cmr-form.php';
require_once CMR_PLUGIN_DIR . 'includes/class-cmr-admin-list.php';

function cmr_init(): void {
    CMR_CPT::init();
    CMR_Form::init();
    CMR_Admin_List::init();
}
add_action('plugins_loaded', 'cmr_init');

register_activation_hook(__FILE__, ['CMR_DB', 'install']);
