<?php

namespace WP_VGWORT;

/**
 * Template for the Dashboard view
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 */
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Dashboard', 'vgw-metis' ); ?></h1>
	<?php esc_html_e( 'VG WORT METIS', 'vgw-metis' ); ?> <?php esc_html_e( $this->plugin::VERSION ) ?>
    <?php $this->dev_info(); ?>
    <hr/>


    <div class="card" style="max-width: unset;">
        <h2 class="title"><?php esc_html_e( 'Übersicht', 'vgw-metis' ); ?></h2>
        <p><?php esc_html_e( 'Verfügbare Zählmarken: ', 'vgw-metis' ) ?><?php esc_html_e( DB_Pixels::get_available_pixel_count() ) ?></p>
        <p><?php esc_html_e( 'Zugewiesene Zählmarken: ', 'vgw-metis' ) ?><?php esc_html_e( DB_Pixels::get_assigned_pixel_count() ) ?></p>
    </div>


    <div class="card" style="max-width: unset;">
        <h2 class="title">Erste Schritte</h2>
        <p>Willkommen zu VG WORT METIS - das offizielle Plugin der VG WORT zur Verwaltung von Zählmarken für Ihre WordPress Seiten und Beiträge!</p>
        <p>Damit Sie gleich loslegen können, erfolgt hier eine Kurzanleitung, die den ersten Umgang mit dem Plugin vereinfachen soll und welche Dinge Sie dabei beachten müssen:</p>
        <ol>
            <li>
                <p><strong>API-Key im Portal T.O.M. generieren</strong></p>
                <p>Falls nicht bereits erfolgt, müssen Sie sich zunächst im Portal T.O.M. (<a href="https://tom.vgwort.de/portal/index" target="_blank">https://tom.vgwort.de/portal/index</a>) unter dem Bereich METIS einen API-Key generieren lassen. Bitte halten Sie diesen API-Key unbedingt geheim und geben Sie diesen nicht an Dritte weiter! Sie können auch jederzeit einen neuen API-Key generieren, der alte wird damit aber sofort ungültig. Dementsprechend muss der neue API-Key dann auch in den Einstellungen aktualisiert werden.</p>
            </li>
            <li>
                <p><strong>API-Key in den Einstellungen hinterlegen</strong></p>
                <p>Den zuvor generierten API-Key können Sie in den Einstellungen einfügen. Bitte speichern Sie die Einstellungen daraufhin, sodass der API-Key auch tatsächlich im Plugin hinterlegt ist.</p>
            </li>
            <li>
                <p><strong>Zählmarken bestellen oder importieren</strong></p>
                <p>In den Einstellungen können Sie manuell neue Zählmarken bestellen oder exportierte Zählmarken aus T.O.M. über eine CSV-Datei ins Plugin importieren. Beachten Sie dabei, dass über den CSV-Import auch nur die eigenen Zählmarken importiert werden können, also Zählmarken, die vom Benutzer mit dem hinterlegten API-Key bestellt worden sind.</p>
            </li>
            <li>
                <p><strong>Namen des API-Key-Besitzers hinterlegen</strong></p>
                <p>Bitte geben Sie beim WordPress-Administrator über die Beteiligtenverwaltung des Plugins oder über die Beteiligtenverwaltung von WordPress Ihren Namen ein. Dieser Schritt ist erforderlich, damit bei der Meldungserstellung der Melder des Textes korrekt angezeigt wird.</p>
            </li>
        </ol>
    </div>

    <div class="card" style="max-width: unset;">
        <h2 class="title">Zuweisung von Zählmarken</h2>
        <p>Damit die Aufrufe auf Ihren Beiträgen und Seiten gezählt werden, ist der Einbau einer Zählmarke im HTML-Quelltext erforderlich. Vom Plugin aus erfolgt dieser Einbau automatisch mit der Zuweisung einer Zählmarke, falls im Quelltext keine Zählmarke aufgefunden wurde. Wenn im Quelltext bereits eine Zählmarke eingebaut ist, nimmt das Plugin lediglich die Zuweisung vor, sodass die Zählmarke dem jeweiligen Beitrag oder Seite zugeordnet und dort angezeigt wird. Die Zuweisung von Zählmarken kann auf folgende Art und Weise erfolgen:</p>
        <ul>
            <li>
                <p><strong>Bei der Erstellung von neuen Beiträgen und Seiten</strong></p>
                <p>Bei der Erstellung von neuen Seiten und Beiträgen gibt es im Gutenberg-Editor und im Classic-Editor die Option „Zählmarke automatisch zuweisen“. Wenn diese ausgewählt ist, wird bei der Erstellung des Eintrags automatisch eine beliebige, im Plugin verfügbare und nicht zugewiesene Zählmarke zugewiesen. Wenn keine Zählmarken verfügbar sind, werden automatisch neue Zählmarken nachbestellt.</p>
            </li>
            <li>
                <p><strong>Bei der Bearbeitung von Seiten und Beiträgen</strong></p>
                <p>Bei der Bearbeitung von Seiten und Beiträgen kann im Gutenberg-Editor und im Classic-Editor über die Schaltfläche „Zählmarke automatisch zuweisen“ eine beliebige, im Plugin verfügbare und nicht zugewiesene Zählmarke zugewiesen und eingebaut werden. Wenn keine Zählmarken verfügbar sind, werden automatisch neue Zählmarken nachbestellt. Diese Methode prüft vorab auch den HTML-Quelltext auf eingebaute Zählmarken. Wird hier eine gültige Zählmarke gefunden, dann wird lediglich die Zuweisung dieser Zählmarke vorgenommen.</p>
                <p>Alternativ kann über die Schaltfläche „Zählmarke manuell zuweisen“ eine beliebige Zählmarke eingegeben und zugewiesen werden. Wenn in diesem Fall bereits eine Zählmarke zugewiesen war, kann über diese Methode die bereits vorhandene Zählmarke durch eine neue ersetzt werden. Die manuelle Zuweisung von Zählmarken unterstützt auch die Zuweisung von fremden Zählmarken, d.h. Zählmarken, die nicht vom Benutzer mit dem hinterlegten API-Key bestellt worden sind.</p>
            </li>
            <li>
                <p><strong>Über die Mehrfachaktionen auf der Beitrags- und Seitenübersicht</strong></p>
                <p>In der Beitrags- oder Seitenübersicht von WordPress können mehrere Einträge ausgewählt und über die Mehrfachaktion „Zählmarken zuweisen“ mit Zählmarken versehen werden. Bereits eingebaute Zählmarken im HTML-Quelltext werden ebenfalls berücksichtigt.</p>
            </li>
            <li>
                <p><strong>Über die Scan-Funktion</strong></p>
                <p>Die Scan-Funktion in den Einstellungen durchsucht den Quelltext aller Beiträge und Seiten Ihrer WordPress-Instanz nach bereits eingebauten Zählmarken. Wenn Zählmarken direkt in den HTML-Quelltext oder über ein anderes Plugin eingebaut worden sind, zeigt das Plugin initial noch keine zugewiesene Zählmarke an. Damit die Zuordnung der Zählmarke zu einem Beitrag oder einer Seite im Plugin korrekt ausgewiesen wird, kann die Scan-Funktion Abhilfe schaffen. Je nach Anzahl der Einträge, der verwendeten Themes und Plugins kann es vereinzelt zu einem Timeout bei der Überprüfung des Quelltexts kommen. In diesem Fall können Sie als Alternative auf die Mehrfachaktionen auf der Beitrags- und Seitenübersicht zurückgreifen, dort die gewünschten Beiträge oder Seiten auswählen und die Zuweisung (schrittweise) vornehmen. Ansonsten ist die Zuweisung der richtigen Zählmarke auch über die manuelle Zuweisung möglich.</p>
            </li>
        </ul>
        <p><strong>Hinweis:</strong></p>
        <p>Um Zählmarken bei der Erstellung und bei der Bearbeitung von Beiträgen und Seiten hinzuzufügen, kann sowohl der Gutenberg-Editor als auch der Classic-Editor verwendet werden. Seit WordPress 5.0 ist der Gutenberg-Editor als Standard-Editor festgelegt. Möchten Sie lieber den Classic-Editor verwenden, dann lässt sich dieser wie alle anderen Plugins gleichermaßen installieren.</p>
    </div>

    <div class="card" style="max-width: unset;">
        <h2 class="title">Zählmarkenübersicht</h2>

        <p>Die Zählmarkenübersicht zeigt die Details zu allen zugewiesenen und nicht zugewiesenen Zählmarken an. Beim Mindestzugriff werden alle meldefähigen Jahre der Zählmarke mit farblicher Kennzeichnung angezeigt. Der Status einer Zählmarke kann folgende Werte annehmen:</p>
        <ul>
            <li><p><strong>Nicht zugewiesen</strong></p>
                <p>Die Zählmarke ist noch mit keinem Eintrag verlinkt (zugewiesen oder reserviert).</p></li>
            <li><p><strong>Zugewiesen</strong></p>
                <p>Die Zählmarke ist bei einem Eintrag zugewiesen.</p></li>
            <li><p><strong>Nicht zugewiesen (reserviert)</strong></p>
                <p>Die Zählmarke war zuvor einem Eintrag zugewiesen, ist aber nachträglich wieder entfernt worden. Sie ist somit aktuell keinem Eintrag zugewiesen und die Zählung wurde ausgesetzt. Die noch vorhandene Verknüpfung mit dem Eintrag bewirkt, dass die Zählmarke bei erneuter, automatischer Zuweisung dem jeweiligen Eintrag wieder korrekt zugeordnet werden kann.</p></li>
            <li><p><strong>Mehrfach zugewiesen</strong></p>
                <p>Die Zählmarke ist bei anderen Einträgen ebenfalls verlinkt (zugewiesen oder reserviert).</p></li>
            <li><p><strong>Ungültig</strong></p>
                <p>Die Zählmarke war zuvor einem Eintrag zugewiesen, ist aber durch eine neue Zuweisung mit einer anderen Zählmarke ungültig geworden.</p></li>
        </ul>
    </div>

    <div class="card" style="max-width: unset;">
        <h2 class="title">Meldungsübersicht</h2>

        <p>In der Meldungsübersicht werden alle Einträge angezeigt, die mit einer Zählmarke versehen sind. Es werden hier somit sowohl gemeldete, meldefähige und nicht meldefähige Einträge angezeigt:</p>
        <ul>
            <li><p><strong>Gemeldet</strong></p>
                <p>Zu diesem Eintrag wurde bereits eine Meldung erstellt.</p></li>
            <li><p><strong>Meldefähig</strong></p>
                <p>Ein Eintrag ist meldefähig, wenn eine Zählmarke zugewiesen ist und der Mindestzugriff in einem der meldefähigen Jahre erreicht wurde. Dies gilt allerdings nur für eigene Zählmarken, fremde Zählmarken hingegen sind nicht meldefähig. Für die Meldefähigkeit ist ausschlaggebend, ob in einem der letzten 3 Jahre der Mindestzugriff erreicht wurde. Handelt es sich bei einem Eintrag um die Textart „Lyrik“, so ist der Text bei Erfüllung dieses Kriteriums meldefähig, unabhängig von der Anzahl der Zeichen. Für Einträge der Textart „anderer Text“ hängt die Meldefähigkeit zusätzlich von der Länge der Zeichen ab. Texte mit weniger als 1800 Zeichen sind nicht meldefähig. Für Texte mit mindestens 10 000 Zeichen reicht der anteilige Mindestzugriff (750 Zugriffe). In den anderen Fällen muss der volle Mindestzugriff (1500 Zugriffe) erreicht worden sein.</p></li>
            <li><p><strong>Nicht meldefähig</strong></p>
                <p>Der Eintrag ist nicht meldefähig, da entweder der Mindestzugriff nicht erreicht wurde oder die zugewiesene Zählmarke eine fremde Zählmarke ist.</p></li>
        </ul>
    </div>

    <div class="card" style="max-width: unset;">
        <h2 class="title">Meldungserstellung</h2>

        <p>Ist ein Eintrag meldefähig, so erscheint in der Meldungsübersicht die Schaltfläche „Meldung erstellen“. Auf der darauffolgenden Seite können Sie für die abzusendende Meldung zusätzliche URLs sowie die Beteiligten am Text eingeben.</p>
        <p><strong>Beteiligte</strong></p>
        <p>Unter dem Abschnitt „Beteiligte“ finden Sie die folgenden Tabellen vor:</p>
        <ul>
            <li><p><strong>Verfügbare Beteiligte laut Beteiligtenverwaltung</strong></p>
                <p>In dieser Tabelle werden die Beteiligten zur Auswahl angeboten, die zuvor in der Beteiligtenverwaltung angelegt worden sind. Diese Beteiligte können mittels den Aktionspfeilen „>“ sowie „<“ für die Meldung hinzugefügt bzw. wieder entfernt werden.</p></li>
            <li><p><strong>Beteiligte am gemeldeten Text</strong></p>
                <p>In dieser Tabelle werden die Beteiligten aufgeführt, die am zu meldenden Text mitgewirkt haben und diese Beteiligte werden beim Absenden der Meldung an T.O.M. übergeben. Beteiligte können aus der Beteiligtenverwaltung oder manuell hinzugefügt werden.</p></li>
        </ul>
        <p><strong>Optionale Zusatzfunktionen</strong></p>
        <p>Verfügen Sie in T.O.M. über die optionalen Zusatzfunktionen, so können Sie auch Texte melden, bei denen Sie selbst nicht beteiligt sind. Wenn die optionalen Zusatzfunktionen in T.O.M. aktiviert sind, können Sie bei der Meldungserstellung in WordPress über die Checkbox „Ich bin am gemeldeten Text nicht als Autor oder Übersetzer beteiligt“ sich selbst als Beteiligten entfernen.</p>
    </div>

    <div class="card" style="max-width: unset;">
        <h2 class="title">Beteiligtenverwaltung</h2>

        <p>In der Beteiligtenverwaltung können neue Beteiligte angelegt werden, die im Zuge der Meldungserstellung in der Tabelle „Verfügbare Beteiligte laut Beteiligtenverwaltung“ zur Auswahl stehen sollen. Auch bereits angelegte und neu erstellte WordPress-Benutzer (mit Ausnahme jener der Rolle "Abonnent") werden automatisch in die Beteiligtenverwaltung aufgenommen. In diesem Fall wird zusätzlich der Benutzername angezeigt. Da der Nachname bei der Meldung immer ein Pflichtfeld ist, muss zu jedem angelegten Beteiligten der Nachname angegeben werden.</p>

        <p><strong>Hinweis:</strong></p>
        <p>Bei der Meldung ist es wie in T.O.M. nicht erforderlich, sich selbst in die Liste der ausgewählten Beteiligten anzugeben. Der Benutzer, dem der API-Key gehört, ist automatisch der Melder und muss deswegen nicht zusätzlich als Beteiligter angegeben werden. Wenn aber andere Autoren, Übersetzer oder ein Verlag beim Text beteiligt waren, müssen Sie diese beim Erstellen der Meldung explizit als Beteiligten hinzufügen.</p>

    </div>

</div>
