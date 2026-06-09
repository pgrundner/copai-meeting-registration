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

class CMR_Admin_List {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_post_cmr_export_csv', [self::class, 'export_csv']);

        // Anmeldezahl-Spalte in der Meetup-Liste
        add_filter('manage_meetup_posts_columns',       [self::class, 'add_column']);
        add_action('manage_meetup_posts_custom_column', [self::class, 'render_column'], 10, 2);
    }

    public static function add_menu(): void {
        add_submenu_page(
            'edit.php?post_type=meetup',
            'Anmeldungen',
            'Anmeldungen',
            'edit_posts',
            'cmr-registrations',
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void {
        $meetup_id = (int) ($_GET['meetup_id'] ?? 0);

        // Alle Meetups für Dropdown
        $meetups = get_posts(['post_type' => 'meetup', 'numberposts' => -1, 'post_status' => 'any']);

        // Wenn kein Meetup gewählt, ersten nehmen
        if (!$meetup_id && $meetups) {
            $meetup_id = $meetups[0]->ID;
        }

        $registrations = $meetup_id ? CMR_DB::get_all($meetup_id) : [];
        $fields        = $meetup_id ? CMR_CPT::get_fields($meetup_id) : [];
        $max           = (int) ($fields['max_participants'] ?? 0);
        $count         = count($registrations);
        ?>
        <div class="wrap">
            <h1>Meetup-Anmeldungen</h1>

            <form method="get" style="margin-bottom:16px">
                <input type="hidden" name="post_type" value="meetup" />
                <input type="hidden" name="page" value="cmr-registrations" />
                <select name="meetup_id" onchange="this.form.submit()">
                    <option value="">– Meetup wählen –</option>
                    <?php foreach ($meetups as $m): ?>
                        <option value="<?= $m->ID ?>" <?= selected($meetup_id, $m->ID, false) ?>>
                            <?= esc_html(get_the_title($m)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($meetup_id): ?>
                <p>
                    <strong><?= $count ?></strong> Anmeldung(en)
                    <?= $max ? ' von <strong>' . $max . '</strong> Plätzen belegt' : '' ?>
                </p>

                <form method="post" action="<?= admin_url('admin-post.php') ?>">
                    <input type="hidden" name="action" value="cmr_export_csv" />
                    <input type="hidden" name="meetup_id" value="<?= $meetup_id ?>" />
                    <?php wp_nonce_field('cmr_export_csv'); ?>
                    <button class="button button-secondary" style="margin-bottom:12px">📥 CSV exportieren</button>
                </form>

                <?php if ($registrations): ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Unternehmen</th>
                            <th>PLZ</th>
                            <th>E-Mail</th>
                            <th>Angemeldet am</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $r): ?>
                        <tr>
                            <td><?= esc_html($r->name) ?></td>
                            <td><?= esc_html($r->company) ?></td>
                            <td><?= esc_html($r->zip) ?></td>
                            <td><a href="mailto:<?= esc_attr($r->email) ?>"><?= esc_html($r->email) ?></a></td>
                            <td><?= esc_html(date_i18n('d.m.Y H:i', strtotime($r->registered_at))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>Noch keine Anmeldungen für dieses Meetup.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function export_csv(): void {
        check_admin_referer('cmr_export_csv');
        if (!current_user_can('edit_posts')) wp_die('Kein Zugriff.');

        $meetup_id = (int) ($_POST['meetup_id'] ?? 0);
        if (!$meetup_id) wp_die('Kein Meetup gewählt.');

        $title = sanitize_file_name(get_the_title($meetup_id));
        $rows  = CMR_DB::get_all($meetup_id);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="anmeldungen-' . $title . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM für Excel
        fputcsv($out, ['Name', 'Unternehmen', 'PLZ', 'E-Mail', 'Angemeldet am'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r->name, $r->company, $r->zip, $r->email,
                date_i18n('d.m.Y H:i', strtotime($r->registered_at)),
            ], ';');
        }
        fclose($out);
        exit;
    }

    public static function add_column(array $columns): array {
        $columns['cmr_registrations'] = 'Anmeldungen';
        return $columns;
    }

    public static function render_column(string $column, int $post_id): void {
        if ($column !== 'cmr_registrations') return;
        $count  = CMR_DB::count($post_id);
        $max    = (int) CMR_CPT::get_fields($post_id)['max_participants'];
        $url    = admin_url("edit.php?post_type=meetup&page=cmr-registrations&meetup_id={$post_id}");
        $label  = $max ? "{$count} / {$max}" : "{$count}";
        echo '<a href="' . esc_url($url) . '">' . $label . '</a>';
    }
}
