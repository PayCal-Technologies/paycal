---
title: Stand der Plattform: PayCal Version 1.049.000
date: 2026-04-10
author: PayCal Team
tags: release, accessibility, privacy, security, premium
---

## Überblick

PayCal Version 1.049.000 markiert einen bedeutenden architektonischen Meilenstein. Die Plattform arbeitet jetzt als deny-safe Umgebung für professionelle Arbeitszeiterfassung, bei der Datenschutzsouveränität und radikale Barrierefreiheit im Kern des Produkts verankert sind.

Mit einer Codebasis von 945 mathematisch verifizierten Dateien steht diese Version für den Übergang von schneller Feature-Entwicklung zu belastbarer Plattformstabilität.

## Barrierefreiheit ist jetzt nachweisbar

Zum 10. April 2026 bestätigt die WCAG Theme Contrast Matrix eine vollständige Bestehensrate im gesamten visuellen System.

◆ 68 Themes über 2.040 Prüfpunkte gescannt
◆ Mindestkontrast von 4,75:1 über alle Theme-Tokens erzwungen
◆ Abdeckung aller auswählbaren Designs, einschließlich Matrix (15,56:1) und Akira (14,02:1)

Das Ergebnis ist eine konsistente Lesbarkeit unabhängig von der Theme-Auswahl.

## Datenschutzsouveränität: Drei Sicherheits-Säulen

### 1) Passkey-Only Anmeldung (Workstream G)

PayCal hat die Browser-Credential-Bridge vollständig entfernt und arbeitet jetzt ausschließlich mit Passkeys.

◆ Kein Risiko durch Passwortdatenbanken
◆ WebAuthn + HKDF leiten lokal einen Key Encryption Key (KEK) ab
◆ Der Server erhält nur verpacktes Schlüsselmaterial

### 2) Automatische Datenlöschung (Workstream D)

Sensibler Zustand wird bewusst kurzlebig gehalten.

◆ Tab-Verbergen und Seitenwechsel lösen DOM-Sensitivity-Scrub aus
◆ Sicherheitsschlüssel und sensible Arbeitsdaten werden aus dem Speicher entfernt
◆ Datenspeicherung folgt strikten Notwendigkeitsgrenzen

### 3) Privacy Guard Telemetrie (Workstream B)

Betriebsbeobachtung bleibt erhalten, ohne Identitätslecks zu erzeugen.

◆ Telemetrie ist anonymisiert
◆ Übermittlung erfolgt in Batches mit zufälligem Jitter
◆ Logs sind so ausgelegt, dass keine Korrelation zu Sitzungen oder Einnahmen möglich ist

## Professional Toolkit Highlights

### AriaEcho Narration

Barrierefreiheitsorientierte Sprachausgabe wandelt Rohdaten zu Zeit und Lohn in natürliche, professionelle Formulierungen für assistive Arbeitsabläufe um.

### Private Math (Lokale Steuer-Engine)

Steuerberechnungen laufen vollständig im Browser, damit sensible Einkommensberechnungen nicht auf entfernte Server gelangen.

### Professionelle Exporte

PDF-, CSV- und Text-Exporte stehen per Ein-Klick-Generierung bereit. Export Identity Inversion nutzt eine bereinigte temporäre Report-Identität, die unmittelbar nach dem Download gelöscht wird.

### Safety Net Recovery

Orphaned Work Recovery erkennt nicht mehr verknüpfte Arbeitseinträge nach Site-Löschungen und unterstützt beim Wiederverbinden zur Wahrung der Historie.

## Premium: Zusammenarbeit ohne Kompromisse

Premium-Organisationsfunktionen bieten stärkere operative Steuerung, ohne die Privatsphäre auf Nutzerebene aufzugeben.

◆ Organization Hub für Arbeitgeber- und Team-Workflows
◆ Verfeinertes Rollenmodell mit granularen Berechtigungen
◆ Delegierte Kalenderansichten für Management-Überblick
◆ DEK Auto-Bootstrap für sofortige Verschlüsselungsbereitschaft beim Seitenaufruf

## Schluss

PayCal v1.049.000 ist mehr als ein Versionssprung. Es ist ein Plattformversprechen für barrierefreies Design, Datenschutzsouveränität und nutzerkontrollierte Datenverarbeitung im großen Maßstab.

Sicher. Barrierefrei. Gehört dir. Das ist PayCal.
