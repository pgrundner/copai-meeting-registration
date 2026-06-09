<?php
/**
 * @author  Peter Grundner <peter.grundner@murbit.at>
 * @date    September 2025
 * @license MIT License – https://opensource.org/licenses/MIT
 * @project Community of Practice AI 2023-2-AT01-KA210-VET-000169864
 *
 * Förderhinweis:
 * 
 * Von der Europäischen Union finanziert. Die geäußerten Ansichten und Meinungen entsprechen jedoch 
 * ausschließlich denen des Autors bzw. der Autoren und spiegeln nicht zwingend die der Europäischen 
 * Union oder der OeAD-GmbH wider. 
 * Weder die Europäische Union noch die OeAD-GmbH können dafür verantwortlich gemacht werden.
 */
defined('ABSPATH') || exit;

class CMR_CPT {

    public static function init(): void {
        add_action('init',            [self::class, 'register_post_type']);
        add_action('add_meta_boxes',  [self::class, 'add_meta_boxes']);
        add_action('save_post',       [self::class, 'save_meta'], 10, 2);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function register_post_type(): void {
        register_post_type('meetup', [
            'labels' => [
                'name'               => 'Meetups',
                'singular_name'      => 'Meetup',
                'add_new'            => 'Neues Meetup',
                'add_new_item'       => 'Neues Meetup anlegen',
                'edit_item'          => 'Meetup bearbeiten',
                'view_item'          => 'Meetup ansehen',
                'search_items'       => 'Meetups suchen',
                'not_found'          => 'Keine Meetups gefunden',
                'not_found_in_trash' => 'Keine Meetups im Papierkorb',
            ],
            'public'        => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-calendar-alt',
            'supports'      => ['title', 'editor', 'thumbnail'],
            'has_archive'   => true,
            'rewrite'       => ['slug' => 'meetups'],
            'show_in_rest'  => true,
        ]);
    }

    public static function add_meta_boxes(): void {
        add_meta_box(
            'cmr_meetup_details',
            'Meetup Details',
            [self::class, 'render_meta_box'],
            'meetup',
            'normal',
            'high'
        );
    }

    public static function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('cmr_save_meta', 'cmr_nonce');
        $f = self::get_fields($post->ID);
        ?>
        <style>
            .cmr-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            .cmr-meta-grid label { font-weight: 600; display: block; margin-bottom: 4px; }
            .cmr-meta-grid input, .cmr-meta-grid textarea { width: 100%; }
            .cmr-meta-full { grid-column: 1 / -1; }
            .cmr-meta-section { grid-column: 1 / -1; font-size: 13px; font-weight: 700;
                text-transform: uppercase; color: #888; border-bottom: 1px solid #ddd;
                padding-bottom: 4px; margin-top: 8px; }
        </style>
        <div class="cmr-meta-grid">
            <div class="cmr-meta-section">Veranstaltungsdetails</div>

            <div>
                <label>Datum</label>
                <input type="date" name="cmr_event_date" value="<?= esc_attr($f['event_date']) ?>" />
            </div>
            <div>
                <label>Uhrzeit</label>
                <input type="time" name="cmr_event_time" value="<?= esc_attr($f['event_time']) ?>" />
            </div>
            <div class="cmr-meta-full">
                <label>Ort (Adresse oder Beschreibung)</label>
                <input type="text" name="cmr_event_location" value="<?= esc_attr($f['event_location']) ?>"
                       placeholder="z.B. Wien, Musterstraße 1 – oder leer lassen für Online-Event" />
            </div>
            <div class="cmr-meta-full">
                <label>Videolink <small style="font-weight:normal">(nur in Bestätigungs-E-Mail, NICHT öffentlich sichtbar)</small></label>
                <input type="url" name="cmr_video_url" value="<?= esc_attr($f['video_url']) ?>"
                       placeholder="https://meet.jit.si/mein-meetup" />
            </div>

            <div class="cmr-meta-section">Teilnehmer</div>

            <div>
                <label>Mindest-Teilnehmer</label>
                <input type="number" name="cmr_min_participants" value="<?= esc_attr($f['min_participants']) ?>" min="0" />
            </div>
            <div>
                <label>Max. Teilnehmer <small style="font-weight:normal">(0 = unbegrenzt)</small></label>
                <input type="number" name="cmr_max_participants" value="<?= esc_attr($f['max_participants']) ?>" min="0" />
            </div>

            <div class="cmr-meta-section">Anmeldezeitraum</div>

            <div>
                <label>Anmeldung freischalten ab</label>
                <input type="datetime-local" name="cmr_reg_from" value="<?= esc_attr($f['reg_from']) ?>" />
            </div>
            <div>
                <label>Anmeldung freischalten bis</label>
                <input type="datetime-local" name="cmr_reg_until" value="<?= esc_attr($f['reg_until']) ?>" />
            </div>
        </div>
        <?php
    }

    public static function save_meta(int $post_id, \WP_Post $post): void {
        if (!isset($_POST['cmr_nonce']) || !wp_verify_nonce($_POST['cmr_nonce'], 'cmr_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'meetup') return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = [
            'cmr_event_date'      => 'sanitize_text_field',
            'cmr_event_time'      => 'sanitize_text_field',
            'cmr_event_location'  => 'sanitize_text_field',
            'cmr_video_url'       => 'esc_url_raw',
            'cmr_min_participants'=> 'absint',
            'cmr_max_participants'=> 'absint',
            'cmr_reg_from'        => 'sanitize_text_field',
            'cmr_reg_until'       => 'sanitize_text_field',
        ];

        foreach ($fields as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, $sanitizer($_POST[$key]));
            }
        }
    }

    public static function get_fields(int $post_id): array {
        return [
            'event_date'       => get_post_meta($post_id, 'cmr_event_date',       true),
            'event_time'       => get_post_meta($post_id, 'cmr_event_time',       true),
            'event_location'   => get_post_meta($post_id, 'cmr_event_location',   true),
            'video_url'        => get_post_meta($post_id, 'cmr_video_url',        true),
            'min_participants' => get_post_meta($post_id, 'cmr_min_participants', true) ?: 0,
            'max_participants' => get_post_meta($post_id, 'cmr_max_participants', true) ?: 0,
            'reg_from'         => get_post_meta($post_id, 'cmr_reg_from',         true),
            'reg_until'        => get_post_meta($post_id, 'cmr_reg_until',        true),
        ];
    }

    public static function enqueue_assets(): void {
        if (!is_singular('meetup')) return;
        wp_enqueue_style('cmr-style', CMR_PLUGIN_URL . 'assets/meetup.css', [], CMR_VERSION);
        wp_enqueue_script('cmr-script', CMR_PLUGIN_URL . 'assets/meetup.js', ['jquery'], CMR_VERSION, true);
        wp_localize_script('cmr-script', 'CMR', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('cmr_register'),
        ]);
    }

    public static function enqueue_admin_assets(string $hook): void {
        wp_enqueue_style('cmr-admin-style', CMR_PLUGIN_URL . 'assets/meetup.css', [], CMR_VERSION);
    }
}
