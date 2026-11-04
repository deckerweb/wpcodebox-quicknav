# Fragen nach Themen

[English](https://github.com/deckerweb/wpcodebox-quicknav/wiki/FAQ-by-topic)

## Welche WPCodeBox-Version wird unterstützt?

Die Anbindung unterstützt ausschließlich WPCodeBox 2 ab Version 1.4.1. WPCodeBox V1 wird nicht unterstützt. Bei deaktiviertem WPCodeBox 2 bleiben Einstellungen und Functionality-Plugin-Übersicht verfügbar. Bei erkanntem Functionality-Plugin bleibt auch die QuickNav-Toolbar erreichbar. Einzelne Snippets werden weiterhin in WPCodeBox verwaltet; direkte Editorlinks erscheinen nur bei verfügbarer Anbindung.

## Wie beende ich Vollbild und Fokus?

Oben „Zurück zur normalen Ansicht“ wählen oder Escape drücken. Wenn ein Dialog oder eine Editor-Vervollständigung geöffnet ist, zuerst diesen schließen. Die Ansichten sind vorübergehend und starten nach einem Seitenaufruf wieder normal. Browser-Vollbild wird nicht eingeschaltet.

## Was wird gespeichert?

Favoriten und Anzeigeoptionen pro Benutzer und Website sowie datierte Functionality-Plugin-Metadaten pro Website. Kein Snippet-Quellcode und keine Ausführungsprotokolle. Deaktivierung und Deinstallation erhalten diese Daten und sämtliche WPCodeBox-Daten. Temporäre Updatedaten werden bereinigt; die Library berücksichtigt andere installierte Hosts.

## Welche externen Verbindungen gibt es?

Der Updater kann öffentliche stabile GitHub-Releases prüfen. Automatische Updates werden nicht eingeschaltet. Der optionale Online-Katalog der Library ist standardmäßig aus. Übertragen werden übliche HTTP-/WordPress-/PHP-Metadaten, kein Snippet-Code und keine Favoriten. Ressourcenlinks kontaktieren ihr Ziel beim Öffnen.

## Was gilt für Multisite?

Favoriten, Anzeigeoptionen und Metadatenstände bleiben je Website getrennt. Die Übersicht unterscheidet Website- und Netzwerkaktivierung. Sie ist in der Website-Verwaltung erreichbar; es gibt keine netzwerkweite Snippet-Sammlung. Netzwerkaktivierte Plugins können nur in der Netzwerkverwaltung deaktiviert werden.

## Verändert QuickNav erzeugte Plugins?

Nein. QuickNav liest die Konfiguration und führt dafür keine Plugin-Datei aus. Zum Pausieren des gesamten Functionality-Plugins führt „Plugins verwalten“ zur nativen WordPress-Verwaltung. Die WPCodeBox-interne Abschaltfunktion kann das erzeugte Plugin entfernen; für ein einfaches Pausieren die normale WordPress-Deaktivierung verwenden.

## Functionality-Plugin: Was bedeuten „aktiv“ und „Einbindung freigegeben“?

Diese Angaben gehören zur QuickNav-Übersicht des von WPCodeBox erzeugten Functionality-Plugins. Sie zeigen die Konfiguration, nicht die tatsächlich ausgeführten Snippets. „Plugin aktiv“ bedeutet: Das Functionality-Plugin ist in WordPress aktiviert. „Einbindung freigegeben“ bedeutet: Das Snippet ist in der erzeugten Hauptdatei zum Laden vorgesehen; sein Ladeeintrag ist nicht auskommentiert. Ob der Snippet-Code tatsächlich ausgeführt wird, hängt zusätzlich zum Beispiel von Bedingungen, Hooks und dem abgesicherten Modus (Safe Mode) ab. Auch Fehler können die Ausführung verhindern. Beispiel: Ein Snippet, das nur im Frontend laufen soll, muss bei einem Aufruf im Admin nicht ausgeführt werden, obwohl seine Einbindung freigegeben ist. QuickNav zeichnet keine tatsächlichen Ausführungen auf.

[Anleitung mit weiteren Einzelheiten](https://github.com/deckerweb/wpcodebox-quicknav/wiki/Anleitung)
