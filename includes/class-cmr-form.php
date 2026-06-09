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

class CMR_Form {

    public static function init(): void {
        add_shortcode('meetup_anmeldung', [self::class, 'render_shortcode']);
        add_filter('the_content',         [self::class, 'append_to_content']);
        add_action('wp_ajax_nopriv_cmr_register', [self::class, 'handle_ajax']);
        add_action('wp_ajax_cmr_register',        [self::class, 'handle_ajax']);
    }

    /** Automatisch ans Ende jedes Meetup-Beitrags anhängen */
    public static function append_to_content(string $content): string {
        if (is_singular('meetup') && in_the_loop() && is_main_query()) {
            $content .= self::render_shortcode([]);
        }
        return $content;
    }

    public static function render_shortcode(array $atts): string {
        $meetup_id = get_the_ID();
        if (!$meetup_id || get_post_type($meetup_id) !== 'meetup') return '';

        $fields = CMR_CPT::get_fields($meetup_id);
        $now    = current_time('Y-m-d\TH:i');

        // Registrierungszeitraum prüfen
        $reg_from  = $fields['reg_from'];
        $reg_until = $fields['reg_until'];

        if ($reg_from && $now < $reg_from) {
            $from_fmt = date_i18n('d.m.Y H:i', strtotime($reg_from));
            return '<div class="cmr-notice cmr-notice--info">Die Anmeldung öffnet am <strong>' . esc_html($from_fmt) . ' Uhr</strong>.</div>';
        }
        if ($reg_until && $now > $reg_until) {
            return '<div class="cmr-notice cmr-notice--warning">Die Anmeldung für dieses Meetup ist geschlossen.</div>';
        }

        // Max. Teilnehmer prüfen
        $max   = (int) $fields['max_participants'];
        $count = CMR_DB::count($meetup_id);
        if ($max > 0 && $count >= $max) {
            return '<div class="cmr-notice cmr-notice--warning">Dieses Meetup ist ausgebucht (<strong>' . $count . '/' . $max . '</strong> Plätze belegt).</div>';
        }

        $spots = ($max > 0) ? '<p class="cmr-spots">Noch <strong>' . ($max - $count) . '</strong> von <strong>' . $max . '</strong> Plätzen frei.</p>' : '';

        ob_start();
        ?>
        <div class="cmr-form-wrap" id="cmr-form-wrap-<?= $meetup_id ?>">
            <h3 class="cmr-form-title">🎟 Jetzt anmelden</h3>
            <?= $spots ?>
            <form class="cmr-form" data-meetup="<?= $meetup_id ?>">
                <div class="cmr-field">
                    <label for="cmr_name_<?= $meetup_id ?>">Name <span class="cmr-required">*</span></label>
                    <input type="text" id="cmr_name_<?= $meetup_id ?>" name="cmr_name" required placeholder="Vor- und Nachname" />
                </div>
                <div class="cmr-field">
                    <label for="cmr_company_<?= $meetup_id ?>">Unternehmen / Organisation</label>
                    <input type="text" id="cmr_company_<?= $meetup_id ?>" name="cmr_company" placeholder="optional" />
                </div>
                <div class="cmr-field cmr-field--half">
                    <label for="cmr_zip_<?= $meetup_id ?>">Postleitzahl</label>
                    <input type="text" id="cmr_zip_<?= $meetup_id ?>" name="cmr_zip" placeholder="z.B. 1010" maxlength="10" />
                </div>
                <div class="cmr-field">
                    <label for="cmr_email_<?= $meetup_id ?>">E-Mail <span class="cmr-required">*</span></label>
                    <input type="email" id="cmr_email_<?= $meetup_id ?>" name="cmr_email" required placeholder="deine@email.at" />
                </div>
                <div class="cmr-message" id="cmr-msg-<?= $meetup_id ?>" style="display:none"></div>
                <button type="submit" class="cmr-btn">Verbindlich anmelden</button>
                <p class="cmr-hint">Nach der Anmeldung erhältst du eine Bestätigungs-E-Mail<?= $fields['video_url'] ? ' mit dem Videolink' : '' ?>.</p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /** AJAX-Handler */
    public static function handle_ajax(): void {
        check_ajax_referer('cmr_register', 'nonce');

        $meetup_id = (int) ($_POST['meetup_id'] ?? 0);
        $name      = sanitize_text_field($_POST['name']    ?? '');
        $company   = sanitize_text_field($_POST['company'] ?? '');
        $zip       = sanitize_text_field($_POST['zip']     ?? '');
        $email     = sanitize_email($_POST['email']        ?? '');

        if (!$meetup_id || !$name || !$email || !is_email($email)) {
            wp_send_json_error(['message' => 'Bitte fülle alle Pflichtfelder korrekt aus.']);
        }

        if (get_post_type($meetup_id) !== 'meetup') {
            wp_send_json_error(['message' => 'Ungültiges Meetup.']);
        }

        $fields = CMR_CPT::get_fields($meetup_id);
        $now    = current_time('Y-m-d\TH:i');

        if ($fields['reg_from']  && $now < $fields['reg_from'])  wp_send_json_error(['message' => 'Anmeldung noch nicht geöffnet.']);
        if ($fields['reg_until'] && $now > $fields['reg_until']) wp_send_json_error(['message' => 'Anmeldezeitraum abgelaufen.']);

        $max   = (int) $fields['max_participants'];
        $count = CMR_DB::count($meetup_id);
        if ($max > 0 && $count >= $max) wp_send_json_error(['message' => 'Leider ist dieses Meetup ausgebucht.']);

        if (CMR_DB::already_registered($meetup_id, $email)) {
            wp_send_json_error(['message' => 'Diese E-Mail-Adresse ist bereits angemeldet.']);
        }

        $reg = compact('name', 'company', 'zip', 'email');
        $id  = CMR_DB::insert(array_merge($reg, ['meetup_id' => $meetup_id]));

        if (!$id) {
            wp_send_json_error(['message' => 'Fehler beim Speichern. Bitte versuche es erneut.']);
        }

        CMR_Email::send_confirmation($reg, $meetup_id);
        CMR_Email::notify_admin($reg, $meetup_id);

        wp_send_json_success(['message' => '🎉 Anmeldung erfolgreich! Wir haben dir eine Bestätigungs-E-Mail geschickt.']);
    }
}
