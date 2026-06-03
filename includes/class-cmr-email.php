<?php
/**
 * @author  Peter Grundner <peter.grundner@murbit.at>
 * @date    September 2025
 * @project Community of Practice AI KA210 – VET 4603C73C
 *
 * Förderhinweis:
 * 
 * Von der Europäischen Union finanziert. Die geäußerten Ansichten und Meinungen entsprechen jedoch 
 * ausschließlich denen des Autors bzw. der Autoren und spiegeln nicht zwingend die der Europäischen 
 * Union oder der OeAD-GmbH wider. 
 * Weder die Europäische Union noch die OeAD-GmbH können dafür verantwortlich gemacht werden.
  */
defined('ABSPATH') || exit;

class CMR_Email {

    /**
     * Bestätigungs-E-Mail an Teilnehmer senden.
     */
    public static function send_confirmation(array $reg, int $meetup_id): void {
        $fields    = CMR_CPT::get_fields($meetup_id);
        $meetup    = get_post($meetup_id);
        $title     = $meetup ? get_the_title($meetup) : 'Meetup';
        $site_name = get_bloginfo('name');

        $date     = $fields['event_date'] ? date_i18n('d.m.Y', strtotime($fields['event_date'])) : '–';
        $time     = $fields['event_time'] ?: '–';
        $location = $fields['event_location'] ?: 'Online';
        $video    = $fields['video_url'];

        $subject = "✅ Anmeldebestätigung: {$title}";

        $body  = "Hallo {$reg['name']},\n\n";
        $body .= "deine Anmeldung für das folgende Meetup wurde erfolgreich registriert:\n\n";
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $body .= "📅 {$title}\n";
        $body .= "Datum:  {$date} um {$time} Uhr\n";
        $body .= "Ort:    {$location}\n";
        if ($video) {
            $body .= "🔗 Video-Link: {$video}\n";
            $body .= "(Bitte halte diesen Link vertraulich)\n";
        }
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $body .= "Wir freuen uns auf deine Teilnahme!\n\n";
        $body .= "– {$site_name}";

        wp_mail(
            $reg['email'],
            $subject,
            $body,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    /**
     * Benachrichtigung an Admin bei neuer Anmeldung.
     */
    public static function notify_admin(array $reg, int $meetup_id): void {
        $title     = get_the_title($meetup_id);
        $admin     = get_option('admin_email');
        $count     = CMR_DB::count($meetup_id);
        $max       = CMR_CPT::get_fields($meetup_id)['max_participants'];

        $subject = "Neue Anmeldung: {$title}";
        $body    = "Neue Meetup-Anmeldung:\n\n"
                 . "Meetup:     {$title}\n"
                 . "Name:       {$reg['name']}\n"
                 . "Unternehmen:{$reg['company']}\n"
                 . "PLZ:        {$reg['zip']}\n"
                 . "E-Mail:     {$reg['email']}\n"
                 . "Anmeldungen: {$count}" . ($max ? " / {$max}" : '') . "\n\n"
                 . "Admin: " . admin_url("edit.php?post_type=meetup&page=cmr-registrations&meetup_id={$meetup_id}");

        wp_mail($admin, $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);
    }
}
