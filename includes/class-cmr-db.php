<?php
/**
 * @author  Peter Grundner <peter.grundner@murbit.at>
 * @date    September 2025
 * @license MIT License – https://opensource.org/licenses/MIT
 *
 * Gefördert durch: Community of Practice AI KA210 – VET 4603C73C
 * Finanziert von der Europäischen Union. Die geäußerten Ansichten und Meinungen
 * entsprechen ausschließlich denen des Autors und spiegeln nicht zwingend die der
 * Europäischen Union oder der OeAD-GmbH wider.
 */
defined('ABSPATH') || exit;

class CMR_DB {

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'meetup_registrations';
    }

    public static function install(): void {
        global $wpdb;
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            meetup_id       BIGINT UNSIGNED NOT NULL,
            name            VARCHAR(200)    NOT NULL,
            company         VARCHAR(200)    NOT NULL DEFAULT '',
            zip             VARCHAR(20)     NOT NULL DEFAULT '',
            email           VARCHAR(200)    NOT NULL,
            registered_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY meetup_id (meetup_id),
            UNIQUE KEY meetup_email (meetup_id, email)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert(array $data): int|false {
        global $wpdb;
        $result = $wpdb->insert(self::table(), [
            'meetup_id' => (int) $data['meetup_id'],
            'name'      => sanitize_text_field($data['name']),
            'company'   => sanitize_text_field($data['company'] ?? ''),
            'zip'       => sanitize_text_field($data['zip']     ?? ''),
            'email'     => sanitize_email($data['email']),
        ], ['%d', '%s', '%s', '%s', '%s']);

        return $result ? $wpdb->insert_id : false;
    }

    public static function already_registered(int $meetup_id, string $email): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . self::table() . " WHERE meetup_id = %d AND email = %s LIMIT 1",
            $meetup_id, sanitize_email($email)
        ));
    }

    public static function count(int $meetup_id): int {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::table() . " WHERE meetup_id = %d",
            $meetup_id
        ));
    }

    public static function get_all(int $meetup_id): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE meetup_id = %d ORDER BY registered_at ASC",
            $meetup_id
        ));
    }
}
