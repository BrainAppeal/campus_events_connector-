# TYPO3 Extension campus_events_connector

[![Latest Stable Version](https://poser.pugx.org/brainappeal/campus_events_connector/v/stable)](https://packagist.org/packages/brainappeal/campus_events_connector)
[![License](https://poser.pugx.org/brainappeal/campus_events_connector/license)](https://packagist.org/packages/brainappeal/campus_events_connector)
[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-green.svg)](https://get.typo3.org/version/12)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-green.svg)](https://get.typo3.org/version/13)

## Campus Events
Campus Events ist ein Veranstaltungstool, das für den Einsatz bei Hochschulen und Universitäten bzw. für den Bildungssektor optimiert ist. 
Es kann jedoch auch in anderen Bereichen zum Einsatz kommen. Campus Events vereinfacht insbesondere Aufgaben, die immer wieder vorkommen, viel Zeit beanspruchen und sich deshalb gut automatisieren lassen.

Kernfunktionen:
* Verwaltung von Veranstaltungen
* Anmeldung der Teilnehmer als Self-Service
* Teilnehmer Management mit Wartelistenfunktion
* 1-Klick Teilnehmerlisten und Unterschriftenlisten 
* 1-Klick Namensschilder und Tischaufsteller mit Namen
* Massen-E-Mail Funktion an Teilnehmer und/oder die Warteliste
* Für interne und öffentliche Events

Mehr auf [https://www.campus-events.com](https://www.campus-events.com) und [https://hilfe.campus-events.com](https://hilfe.campus-events.com). 

## TYPO3 Extension

### Übersicht
Mit dieser kostenlosen TYPO3 Extension werden Veranstaltungen und Termine von Campus Events in die TYPO3 CMS Webseite übertragen.
Die Extension verwendet die Campus Events API (Schnittstelle), um alle Veranstaltungen an TYPO3 zu übergeben und dort als Campus Events-Objekte anzulegen. 
Aus diesen Objekten lassen sich durch die unten aufgeführten Erweiterungen z.B. news Objekte erzeugen. Hierdurch kann Campus Events schnell in laufende System integriert werden, da die Veranstaltungen in TYPO3 aus Campus Events befüllt werden.

### Kompatibilität
TYPO3 12.4 LTS - 13.4 LTS

### Erweiterbarkeit / Kombinationen

* [campus_events_connector](https://github.com/BrainAppeal/campus_events_connector)                  – Datenaustausch von Campus Events zu TYPO3
* [campus_events_frontend](https://github.com/BrainAppeal/campus_events_frontend)                    – Darstellung von Veranstaltungen und Termin auf TYPO3 Webseiten
* [campus_events_convert2news](https://github.com/BrainAppeal/campus_events_convert2news)            – wandelt Campus Events-Objekte in EXT:news Objekte 

## Hinweis für TYPO3 Agenturen
Setzen Sie Campus Events für Ihre Kunden ein und profitieren Sie von einer durchdachten Lösung und interessanten 
Provisionen. Bitte kontaktieren Sie uns und fragen Sie nach dem Partnerprogramm.

## Allgemeine Einstellungen

Für die korrekte Anzeige der Uhrzeiten muss die Zeitzone auf dem Server richtig konfiguriert sein.
Alternativ kann die Zeitzone auch in den TYPO3-Einstellungen festgelegt werden:

```
$GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'] = 'Europe/Berlin';
```

## Änderungen

Siehe: [CHANGELOG.md](CHANGELOG.md)
