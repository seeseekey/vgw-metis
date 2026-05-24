=== VG WORT METIS ===
Contributors: vgwort, 2ndkauboy, seeseekey
Tags: vgwort, metis, tom, zählmarke, urheber, autor, verlag, meldung, ausschüttung, pixel
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Das offizielle VG-WORT-Plugin zur Verwaltung von METIS-Zählmarken, Meldungen und Beteiligten direkt in WordPress.

== Description ==

VG WORT METIS verbindet WordPress direkt mit dem Meldeportal T.O.M. der VG WORT. Damit lassen sich Zählmarken bestellen, importieren, zu Beiträgen und Seiten zuweisen und für Meldungen vorbereiten, ohne zwischen WordPress und T.O.M. hin und her wechseln zu müssen.

Das Plugin unterstützt Classic Editor und Gutenberg, bietet eine zentrale Zählmarkenübersicht, erkennt bereits eingebundene Zählmarken im HTML-Quelltext und erleichtert die Erstellung von Meldungen inklusive Beteiligtenverwaltung.

= Für wen ist das Plugin gedacht? =

VG WORT METIS richtet sich aktuell an Autorinnen und Autoren, die ihre METIS-Zählmarken direkt in WordPress verwalten möchten. Die Unterstützung für Verlage ist für zukünftige Versionen geplant.

= Highlights =

* Offiziell unterstütztes Plugin der VG WORT
* Direkte und sichere Kommunikation mit T.O.M.
* Automatische und manuelle Bestellung von Zählmarken
* Import von Zählmarken aus T.O.M. per CSV-Datei
* Scan-Funktion für bereits eingebundene Zählmarken im HTML-Quelltext
* Zuweisung von Zählmarken in Classic Editor und Gutenberg
* Zentrale Übersicht über zugewiesene und nicht zugewiesene Zählmarken
* Zusatzinformationen in Beitrags- und Seitenübersichten
* Beteiligtenverwaltung für wiederkehrende Meldungen
* Meldungsübersicht für meldefähige und bereits gemeldete Inhalte
* Erstellung und Versand von Meldungen direkt aus WordPress

= Funktionsumfang im Detail =

= Direkte Verbindung mit T.O.M. =

Die Authentifizierung erfolgt über einen API-Key, den Sie im [T.O.M. Portal](https://tom.vgwort.de/portal/index) im Bereich METIS generieren können. Bewahren Sie den API-Key sicher auf und geben Sie ihn nicht an Dritte weiter. Bei Bedarf können Sie jederzeit einen neuen API-Key erzeugen; der bisherige Schlüssel wird dadurch ungültig.

= Zählmarken automatisch bestellen =

Vor der automatischen Zuweisung prüft VG WORT METIS, ob ausreichend freie Zählmarken vorhanden sind. Wird ein definierter Schwellenwert erreicht, bestellt das Plugin automatisch neue Zählmarken nach.

= Zählmarken manuell bestellen =

Zusätzlich zur automatischen Bestellung können Sie jederzeit manuell weitere Zählmarken anfordern und anschließend in WordPress verwenden.

= Zählmarken importieren =

Bereits in T.O.M. vorhandene Zählmarken lassen sich über eine CSV-Datei in das Plugin importieren. Der Import wird direkt in den Plugin-Einstellungen durchgeführt.

= Vorhandene Zählmarken erkennen =

Wenn in Ihren Beiträgen oder Seiten bereits Zählmarken eingebunden sind, kann die Scan-Funktion diese im HTML-Quelltext erkennen und den passenden Inhalten zuordnen. Das ist besonders hilfreich beim Wechsel von anderen Plugins wie Prosodia oder Worthy.

= Zählmarken zuweisen =

Bei neuen Beiträgen und Seiten können Sie festlegen, ob automatisch eine freie Zählmarke zugewiesen werden soll. Auch beim nachträglichen Bearbeiten lassen sich Zählmarken hinzufügen, entfernen oder manuell zuweisen, zum Beispiel wenn ein Übersetzungstext dieselbe Zählmarke nutzen soll.

= Zählmarken im Blick behalten =

Die Zählmarkenübersicht zeigt zugewiesene und nicht zugewiesene Zählmarken inklusive Statusinformationen. Dort erkennen Sie unter anderem, welche Texte den erforderlichen Mindestzugriff erreicht haben.

= Beitrags- und Seitenübersichten erweitern =

VG WORT METIS ergänzt die WordPress-Übersichten um relevante Informationen, zum Beispiel Zeichenzahl und Textart. Über Mehrfachaktionen können Zählmarken direkt zugewiesen oder entfernt werden.

= Beteiligte verwalten =

In der Beteiligtenverwaltung können häufig verwendete Beteiligte gepflegt werden. Außerdem werden WordPress-Benutzerinnen und -Benutzer automatisch aufgenommen, sofern sie nicht die Rolle "Abonnent" haben.

= Meldungen erstellen =

Die Meldungsübersicht zeigt meldefähige und bereits gemeldete Beiträge und Seiten. Beim Erstellen einer Meldung wählen Sie die Beteiligten aus oder fügen sie manuell hinzu. Wenn die optionalen Zusatzfunktionen in T.O.M. aktiviert sind, können auch Texte gemeldet werden, an denen Sie selbst nicht beteiligt sind.

= Hilfe und Anleitung =

Eine Kurzanleitung sowie weitere Informationen zur Funktionsweise finden Sie direkt im Dashboard des Plugins.

== Frequently Asked Questions ==

= Wie unterscheidet sich VG WORT METIS von anderen Plugins? =

VG WORT METIS ist das offiziell unterstützte WordPress-Plugin der VG WORT. Der zentrale Unterschied ist die direkte und sichere Kommunikation mit dem T.O.M. Portal.

= Ist die Nutzung des Plugins kostenpflichtig? =

Nein. Das Plugin steht kostenfrei zur Verfügung, inklusive aller Funktionen und Inhalte.

= Warum kann ich keine Zählmarken zu Beiträgen oder Seiten zuweisen? =

Für die Nutzung ist eine Authentifizierung über einen API-Key erforderlich. Den API-Key können Sie im [T.O.M. Portal](https://tom.vgwort.de/portal/index) im Bereich METIS generieren. Bitte halten Sie den Schlüssel geheim und geben Sie ihn nicht an Dritte weiter.

= Bei der Scan-Funktion kommt es zu einem Timeout. Was kann ich tun? =

Je nach Anzahl der Inhalte sowie eingesetztem Theme und Plugins kann die Überprüfung des Quelltexts vereinzelt zu lange dauern. Seit Version 2.0.0 berücksichtigt die automatische Zählmarkenzuweisung ebenfalls Zählmarken im Quelltext. Nutzen Sie in diesem Fall alternativ die Mehrfachaktionen in der Beitrags- und Seitenübersicht und weisen Sie Zählmarken schrittweise zu. Außerdem ist die manuelle Zuweisung einer bestimmten Zählmarke möglich.

= Ich habe einen Fehler gefunden oder einen Verbesserungsvorschlag. Wohin kann ich mich wenden? =

Bitte wenden Sie sich an den Support unter [metis.support@vgwort.de](mailto:metis.support@vgwort.de). Geben Sie bei Fehlerberichten nach Möglichkeit auch Ihre PHP-Version und WordPress-Version an.

== Screenshots ==

1. Plugin-Einstellungen
2. Zählmarkenübersicht
3. Informationen zu Zählmarken in der Seitenübersicht
4. Zuweisung von Zählmarken bei der Erstellung neuer Seiten
5. Zuweisung von Zählmarken beim nachträglichen Bearbeiten von Seiten
6. Beteiligtenverwaltung
7. Meldungsübersicht
8. Meldungserstellung

== Changelog ==

= 2.1.0 =
* Import von Zählmarken aus Prosodia VGW OS per CSV-Datei
* Verbesserte Scan-Funktion zur Erkennung und Zuordnung von Prosodia-Zählmarken
* Filter für Zählmarken-Zuordnungen in Beitrags- und Seitenübersichten
* Sortierbare Spalten in der Zählmarken- und Meldungsübersicht
* Erweiterte Nonce-, Rollen- und Berechtigungsprüfungen für Admin- und AJAX-Aktionen
* Verbesserte Absicherung von Ausgaben und Behebung von CVE-2025-50039
* Korrektur der Cron-Planung für den täglichen Zählmarkenabgleich
* Admin-CSS wird nur noch im Backend geladen
* Zählpixel werden gegen Lazy Loading geschützt
* Allgemeine Verbesserungen und Fehlerbehebungen

= 2.0.1 =
* Allgemeine Verbesserungen und Fehlerbehebungen
* Security-Fixes

= 2.0.0 =
* Unterstützung des Gutenberg-Editors
* Verbesserung der Scan-Funktion und der automatischen Zählmarkenzuweisung
* Neuer Status für mehrfach zugewiesene Zählmarken
* Manuelles Hinzufügen von Beteiligten bei der Meldungserstellung
* Unterstützung optionaler Zusatzfunktionen bei der Meldungserstellung
* Entfernung der Hinweismeldung bei vorhandenen WordPress-Benutzern ohne Nachnamen
* Allgemeine Verbesserungen und Fehlerbehebungen

= 1.2.0 =
* Allgemeine Verbesserungen und Fehlerbehebungen

= 1.1.1 =
* Kurzanleitung sowie Erklärungstexte im Dashboard
* Behebung der Weiterleitung auf eine leere Seite bei fehlerhaftem CSV-Import
* Behebung des Überschreibens von bereits vorhandenen Mehrfachaktionen
* Behebung fehlerhaften Verhaltens beim Ändern von Beteiligten in der Beteiligtenverwaltung
* Sonstige Verbesserungen

= 1.1.0 =
* Meldungsübersicht
* Erstellung und Absenden von Meldungen

= 1.0.1 =
* Allgemeine Verbesserungen und Fehlerbehebungen

= 1.0.0 =
* Erstversion
