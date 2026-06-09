=== CoPAI Meetup Registration ===
Contributors: pgrundner
Tags: meetup, events, registration, custom post type, email
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: MIT

Meetup-Verwaltung mit Anmeldeformular, E-Mail-Bestätigung und Admin-Übersicht für WordPress.

== Beschreibung ==

Custom Post Type „Meetup" mit vollständiger Teilnehmerverwaltung.

**Features:**
* Custom Post Type „Meetup" mit Meta-Feldern: Datum, Uhrzeit, Ort, Videolink, Min/Max Teilnehmer, Anmeldezeitraum
* Anmeldeformular automatisch auf Meetup-Seiten eingeblendet (+ Shortcode `[meetup_anmeldung]`)
* Videolink wird NUR per E-Mail an angemeldete Teilnehmer verschickt – nicht öffentlich sichtbar
* E-Mail-Bestätigung an Teilnehmer nach erfolgreicher Anmeldung
* Admin-Benachrichtigung bei jeder neuen Anmeldung
* Anmeldezeitraum konfigurierbar (von/bis Datum+Uhrzeit)
* Max. Teilnehmer – Formular wird bei Vollbelegung automatisch deaktiviert
* Admin-Übersicht aller Anmeldungen pro Meetup
* CSV-Export der Teilnehmerliste
* Anmeldezahl-Spalte in der Meetup-Übersicht

== Installation ==

1. Plugin-ZIP nach `/wp-content/plugins/copai-meetup-registration/` hochladen
2. Plugin in WordPress aktivieren
3. Unter „Meetups" neue Veranstaltungen anlegen

== Shortcode ==

`[meetup_anmeldung]` – Anmeldeformular manuell einbetten (wird auf Meetup-Seiten automatisch angezeigt)

== Anmeldefelder ==

* Name (Pflicht)
* Unternehmen / Organisation (optional)
* Postleitzahl (optional)
* E-Mail (Pflicht)

== Förderhinweis ==

Community of Practice AI 2023-2-AT01-KA210-VET-000169864 wird von der Europäischen Union finanziert.

Von der Europäischen Union finanziert. Die geäußerten Ansichten und Meinungen entsprechen jedoch 
ausschließlich denen des Autors bzw. der Autoren und spiegeln nicht zwingend die der Europäischen 
Union oder der OeAD-GmbH wider. 
Weder die Europäische Union noch die OeAD-GmbH können dafür verantwortlich gemacht werden.
 
== Changelog ==

= 1.0.0 =
* Erste Version
