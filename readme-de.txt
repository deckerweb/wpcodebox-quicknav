=== WPCodeBox QuickNav ===
Contributors: daveshine
Tags: toolbar, snippets, wpcodebox, quicknav
Requires at least: 6.9
Requires PHP: 8.3
Stable tag: 1.0.0
Tested up to: 7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Favoriten, Snippet-Suche, Editor-Fokus und Functionality-Plugin-Übersicht für WPCodeBox 2.

== Description ==

WPCodeBox QuickNav ist ein unabhängiges Add-on von David Decker – DECKERWEB für Administratoren und Snippet-Autoren. Es entstand aus dem Wunsch nach denselben kurzen Wegen wie in Advanced Scripts QuickNav: Snippets über die Toolbar erreichen, Favoriten persönlich verwalten und im Editor Platz gewinnen. Dazu kommt eine verständliche Betriebsübersicht für das separat erzeugte Functionality-Plugin. WPCodeBox selbst wird weder ersetzt noch mitgeliefert.

* Persönliche Favoriten: Bis zu 100 Snippets als Favoriten auswählen oder den Stern eines Suchtreffers verwenden. Die Auswahl gehört zum Benutzerkonto auf der aktuellen Website. Vorschaukarten öffnen den jeweiligen Editor.
* Snippets in allen Ordnern finden: Die kompakte QuickNav-Suche durchsucht Titel und Ordnerpfade, kombiniert Typ und Aktivierungsstatus und zeigt bis zu 50 Treffer samt Gesamtzahl. Sie ist standardmäßig aktiv und in den QuickNav-Einstellungen abschaltbar. Die native WPCodeBox-Suche und der Manager bleiben verfügbar.
* Toolbar und Menü: Direktzugriff auf Favoriten, aktive und inaktive Snippets, Ordner, Einstellungen und den Functionality-Plugin-Status. Das erste Untermenü von WPCodeBox heißt „Snippets“. QuickNav-Einstellungen und Functionality-Plugin-Übersicht liegen darunter; ohne WPCodeBox-Hauptmenü unter WordPress-Einstellungen. Der Name des Toolbar-Haupteintrags ist persönlich pro Website einstellbar. Der Admin-Hauptmenüname ist optional für die Website einstellbar. Typ und gespeicherter Status haben eigene lesbare Labels in der Toolbar.
* Vollbild und Editor-Fokus: „Arbeitsbereich im Vollbild“ blendet WordPress-Menü und Toolbar aus. „Editor-Fokus“ blendet zusätzlich die Snippet-Liste, die Snippet-Einstellungen und die QuickNav-Suche aus. Die native Speicherleiste bleibt sichtbar. Zurück-Schalter und Escape beenden die Ansicht, ohne den Editor neu zu laden. Titel und Typ des geöffneten Snippets bleiben oben sichtbar. Ein Stern speichert das lokale Snippet direkt als persönlichen Favoriten. Vollbild und Editor-Fokus lassen sich mit persönlichen Tastenkürzeln umschalten. Eine kompakte Hilfe im Editor zeigt QuickNav- und native Kürzel. Optional wird die letzte Ansicht persönlich pro Website gemerkt. Die Hilfe bietet direkte Ansichtsaktionen; gespeicherte lokale Snippets erhalten einen kopierbaren Editor-Link. Die Bereiche passen sich an die verbleibende Fensterhöhe an.
* Functionality-Plugin-Übersicht: Die eigenständige Übersicht zeigt erkannte verwaltete Plugins mit echtem WordPress-Aktivierungsstatus, konfigurierten Snippet-Einbindungen und Dateistatus. Bei verfügbarem WPCodeBox werden Titel, Typ und Ordner zugeordnet und datiert gespeichert. Bei deaktiviertem WPCodeBox bleiben Dateiliste und gespeicherte Zusatzinformationen erreichbar. Betriebsstatus und Zähler fassen die Lage zusammen. Suche und Filter grenzen die Liste ein; fehlende Dateien stehen zuerst. Dateipfade lassen sich kopieren und Informationen gezielt aktualisieren.
* Befehle, abgesicherter Modus und Ressourcen: Die native WordPress-Befehlspalette erhält dort, wo sie verfügbar ist, QuickNav-Verknüpfungen. Die Toolbar bietet den WPCodeBox-Safe-Mode-Zugang, optionale Ressourcen und passende Entwicklerwerkzeuge. Library 1.2.0 und Updater 2.3.0 sind enthalten. Ein eigenständiger roter Warnstreifen mit Flamme rechts bleibt bei aktivem Safe Mode sichtbar, auch im Frontend.

== Installation ==

Voraussetzungen: WordPress 6.9 oder neuer und PHP 8.3 oder neuer. Für die Snippet-Navigation und die Editoransichten muss WPCodeBox 2 ab Version 1.4.1 aktiv sein. Die frühere WPCodeBox-Version V1 wird nicht unterstützt. WPCodeBox wird separat erworben und ist nicht enthalten. Das Add-on bleibt ohne WPCodeBox aktivierbar; Einstellungen und die eigenständige Functionality-Plugin-Übersicht bleiben verfügbar. Das ZIP unter Plugins → Plugin hinzufügen → Plugin hochladen installieren. Bei einem Update das Ersetzen der vorhandenen QuickNav-Version bestätigen. Favoriten und Anzeigeoptionen bleiben erhalten.

== Frequently Asked Questions ==

= Bedeutet „aktiv“ auch ausgeführt? =
Nein. Der Plugin-Status kommt aus WordPress. „Einbindung freigegeben“ bedeutet, dass ein direkter include_once-Eintrag in der erzeugten Hauptdatei vorhanden ist. Bedingungen, Hooks, Ausführungstyp, Fehler und der abgesicherte Modus beeinflussen die Ausführung. QuickNav protokolliert keine tatsächlichen Ausführungen.

= Welche WPCodeBox-Version wird unterstützt? =
Die Anbindung unterstützt ausschließlich WPCodeBox 2 ab Version 1.4.1. WPCodeBox V1 wird nicht unterstützt. Bei deaktiviertem WPCodeBox 2 bleiben Einstellungen und Functionality-Plugin-Übersicht verfügbar. Bei erkanntem Functionality-Plugin bleibt auch die QuickNav-Toolbar erreichbar. Einzelne Snippets werden weiterhin in WPCodeBox verwaltet; direkte Editorlinks erscheinen nur bei verfügbarer Anbindung.

= Verändert QuickNav erzeugte Plugins? =
Nein. QuickNav liest die Konfiguration und führt dafür keine Plugin-Datei aus. Zum Pausieren des gesamten Functionality-Plugins führt „Plugins verwalten“ zur nativen WordPress-Verwaltung. Die WPCodeBox-interne Abschaltfunktion kann das erzeugte Plugin entfernen; für ein einfaches Pausieren die normale WordPress-Deaktivierung verwenden.

= Wie beende ich Vollbild und Fokus? =
Oben „Zurück zur normalen Ansicht“ wählen oder Escape drücken. Wenn ein Dialog oder eine Editor-Vervollständigung geöffnet ist, zuerst diesen schließen. Die Ansichten sind vorübergehend und starten nach einem Seitenaufruf wieder normal. Browser-Vollbild wird nicht eingeschaltet.

= Was wird gespeichert? =
Favoriten und Anzeigeoptionen pro Benutzer und Website sowie datierte Functionality-Plugin-Metadaten pro Website. Kein Snippet-Quellcode und keine Ausführungsprotokolle. Deaktivierung und Deinstallation erhalten diese Daten und sämtliche WPCodeBox-Daten. Temporäre Updatedaten werden bereinigt; die Library berücksichtigt andere installierte Hosts.

= Welche externen Verbindungen gibt es? =
Der Updater kann öffentliche stabile GitHub-Releases prüfen. Automatische Updates werden nicht eingeschaltet. Der optionale Online-Katalog der Library ist standardmäßig aus. Übertragen werden übliche HTTP-/WordPress-/PHP-Metadaten, kein Snippet-Code und keine Favoriten. Ressourcenlinks kontaktieren ihr Ziel beim Öffnen.

= Was gilt für Multisite? =
Favoriten, Anzeigeoptionen und Metadatenstände bleiben je Website getrennt. Die Übersicht unterscheidet Website- und Netzwerkaktivierung. Sie ist in der Website-Verwaltung erreichbar; es gibt keine netzwerkweite Snippet-Sammlung. Netzwerkaktivierte Plugins können nur in der Netzwerkverwaltung deaktiviert werden.

== Changelog ==

= 1.0.0 · 2026-10-30 =

- Neu: Schnellzugriff auf WPCodeBox-2-Snippets über Favoriten, Statuslisten, Ordner und ordnerübergreifende Suche.
- Neu: Vollbild-Arbeitsbereich, Editor-Fokus, anpassbare Tastenkürzel und kompakte Tastenkürzelhilfe.
- Neu: Eigenständige Functionality-Plugin-Übersicht mit verständlichen Einbindungszuständen und gespeicherten Metadaten.
- Neu: Dauerhafter roter Safe-Mode-Warnstreifen mit Flamme in der Toolbar im Admin und Frontend.
- Verbessert: An die Fensterhöhe angepasste Editorbereiche, persönliche Beschriftung und optional gemerkte Editoransicht.
