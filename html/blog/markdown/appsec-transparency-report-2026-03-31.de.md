---
title: Bericht zur Anwendungssicherheit
date: 2026-03-31
author: PayCal Sicherheit
tags: security, appsec, billing_hardening
---

## Berichtsmetadaten

◆ Datum: 2026-03-31
◆ Umfang: Anfragebehandlung, Weiterleitungen, API-Schutz und Vertrauensgrenzen
◆ Referenz: Internes Sicherheits-Audit (2026-03-31)

## Übersicht

Wir haben kürzlich eine gezielte Überprüfung der Anwendungssicherheit durchgeführt, die auf reale Angriffsvektoren moderner Webanwendungen abzielte. Diese Initiative priorisierte die **praktische Risikominderung** ohne Beeinträchtigung des regulären Produktverhaltens.

Dieses Dokument beschreibt, was identifiziert wurde, was geändert wurde und wie wir laufende Sicherheit angehen.

### Auslösendes Ereignis und externer Bericht

Wir wurden heute durch bestätigte Berichte über die Kompromittierung des npm-Pakets Axios alarmiert. Diese Meldung hat diesen vollständigen internen Systemsweep und Audit-Zyklus direkt ausgelöst.

Externe technische Referenzen:
◆ BleepingComputer: [Hacker kompromittieren Axios-npm-Paket, um plattformübergreifende Malware zu verbreiten](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Axios-Supply-Chain-Angriff verbreitet plattformübergreifenden RAT über kompromittierten npm-Account](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Supply-Chain-Explosion: Beliebtes npm-Paket mit Backdoor zum Einschleusen eines RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Wichtigste Ergebnisse

Wir haben drei bedeutende Sicherheitsrisiken identifiziert und behoben:

◆ Weiterleitungsbehandlung: Offener Weiterleitungsvektor (behoben)
◆ Header-Vertrauen: Host-/Header-Vergiftung (behoben)
◆ API-Schutz: Fehlende CSRF-Prüfungen (behoben)

## Was wir behoben haben

### 1) Weiterleitungssicherheit (Sprachwechsel)

**Problem**
Weiterleitungen stützten sich auf `HTTP_REFERER`, der fehlen oder manipuliert sein kann. Dies schafft potenzielle Phishing-Ketten über vertrauenswürdige Domains.

**Lösung**
◆ Strikte Host-Validierung erzwungen
◆ Nur interne oder gleichherkunftsorientierte Weiterleitungen erlaubt
◆ Standard-Fallback auf `/` bei fehlgeschlagener Validierung

**Ergebnis**
Weiterleitungen sind nun **explizit auf vertrauenswürdige Ursprünge begrenzt**.

### 2) Header-Vertrauensgrenzen (Abrechnungsflüsse)

**Problem**
Weitergeleitete Header (z. B. Host/Proto) beeinflussten die Ursprungslogik ohne Überprüfung der Anfragequelle. Fehlkonfigurationen könnten die Host-Manipulation ermöglichen.

**Lösung**
◆ **Vertrauenswürdiges Proxy-Gating** eingeführt
◆ Weitergeleitete Header werden nur von bekannter Infrastruktur akzeptiert
◆ Alle anderen Fälle fallen auf den kanonischen Anwendungsursprung zurück

**Ergebnis**
Die Ursprungsbehandlung ist nun **deterministisch und resistent gegen Header-Spoofing**.

### 3) CSRF-Schutz (Abrechnungsaktionen)

**Problem**
Authentifizierte Abrechnungs-Endpunkte fehlten CSRF-Validierung. Dies setzte Mutations-Endpunkte unter gültigen Sitzungen Cross-Site-Request-Forgery aus.

**Lösung**
◆ CSRF-Validierung für alle Abrechnungsmutationen erzwungen
◆ Token-Überprüfungslogik zentralisiert
◆ Frontend sendet konsistent Token

**Ergebnis**
Alle zustandsändernden Abrechnungsoperationen erfordern nun **explizit benutzerinitiierte Anfragen**.

## Zusätzliche Überprüfung

### Befehlsausführungsoberflächen

Wir haben Code-Pfade mit Ausführungsprimitiven (z. B. Shell/Exec) überprüft.

**Aktueller Status**
◆ Keine aktive Exponierung über Controller oder öffentliche Routen
◆ Kein Nachweis von Laufzeitaufrufen in Anfrage-Pfaden

**Position**
◆ Als **nicht-öffentliches internes Tooling** behandeln
◆ Kandidat für zukünftige Entfernung oder Isolierung

## Verifizierung

Alle Änderungen wurden validiert durch:

◆ PHP-Syntax-Prüfung der geänderten Dateien
◆ Statische Editor-Diagnosen
◆ Manuelle Überprüfung der Anfrage-Flüsse

Keine Syntax- oder Laufzeitprobleme wurden eingeführt.

## Angewandte Sicherheitsprinzipien

Dieser Härtungsdurchgang bekräftigt einige Kernprinzipien:

◆ **Standardmäßige Ablehnung** statt implizitem Vertrauen
◆ **Explizite Vertrauensgrenzen** (z. B. Proxies, Ursprünge)
◆ **Validierung an jedem externen Eingabepunkt**
◆ **Zentrale Sicherheitskontrollen** statt verstreuter Prüfungen

## Was das für Nutzer bedeutet

◆ Reduziertes Phishing-Risiko durch Missbrauch von Weiterleitungen
◆ Stärkere Garantien bei Abrechnungsaktionen
◆ Verbesserte Integrität der Anfragebehandlung und Ursprungsvalidierung

Von Nutzern ist keine Aktion erforderlich.

## Laufende Arbeiten

Wir behandeln Sicherheit als kontinuierlichen Prozess. Nächste Schritte umfassen:

◆ Integrationstests: Weiterleitungsvalidierungsverhalten
◆ Integrationstests: CSRF-Durchsetzung über Endpunkte
◆ Integrationstests: Proxy-Vertrauensgrenzenverwaltung
◆ Periodische Scans: Weiterleitungssenken
◆ Periodische Scans: Header-Vertrauensregressionen
◆ Interne Klassifizierung von Hochrisikorouten

## Aktualisierte Dateien

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Abschlussbemerkung

Dieser Aufwand konzentrierte sich auf die Beseitigung **realistischer Ausnutzungspfade**, nicht theoretischer Grenzfälle. Wir werden weiterhin Änderungen priorisieren, die die Sicherheit sinnvoll verbessern und gleichzeitig die Produktzuverlässigkeit erhalten.
