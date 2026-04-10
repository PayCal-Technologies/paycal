---
title: Transparantierapport applicatiebeveiliging
date: 2026-03-31
author: PayCal Beveiliging
tags: security, appsec, billing_hardening
---

## Rapportmetadata

◆ Datum: 2026-03-31
◆ Bereik: Verzoekafhandeling, omleidingen, API-beschermingen en vertrouwensgrenzen
◆ Referentie: Interne beveiligingsaudit (2026-03-31)

## Overzicht

We hebben onlangs een applicatiebeveiligingsreview afgerond gericht op aanvalsvectoren uit de echte wereld die moderne webapplicaties treffen. Deze inspanning gaf prioriteit aan **praktische risicoreductie** zonder het normale productgedrag te verstoren.

Dit document beschrijft wat er is geïdentificeerd, wat er is gewijzigd en hoe we omgaan met doorlopende beveiliging.

### Triggergebeurtenis en externe rapporten

Vandaag werden we gewaarschuwd door bevestigde rapporten over de compromittering van het npm Axios-pakket. Die waarschuwing triggerde direct deze volledige audit- en interne systeemsweep-cyclus.

Externe technische referenties:
◆ BleepingComputer: [Hackers compromitteren Axios npm-pakket om cross-platform malware te verspreiden](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Axios supply chain-aanval verspreidt cross-platform RAT via gecompromitteerd npm-account](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Supply chain-ontploffing: populair npm-pakket met backdoor om RAT te installeren](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Belangrijkste bevindingen

We hebben drie significante beveiligingsrisico's geïdentificeerd en verholpen:

◆ Omleidingsbeheer: open redirect-vector (opgelost)
◆ Header-vertrouwen: Host/header-vergiftiging (opgelost)
◆ API-bescherming: ontbrekende CSRF-controles (opgelost)

## Wat we hebben opgelost

### 1) Omleidingsbeveiliging (taalwijziging)

**Probleem**
Omleidingen vertrouwden op `HTTP_REFERER`, die afwezig of gemanipuleerd kan zijn. Dit creëert potentiële phishing-ketens met vertrouwde domeinen.

**Oplossing**
◆ Strikte hostvalidatie afgedwongen
◆ Alleen interne of same-origin omleidingen toegestaan
◆ Standaard terugval naar `/` wanneer validatie mislukt

**Resultaat**
Omleidingen zijn nu **expliciet beperkt tot vertrouwde origins**.

### 2) Header-vertrouwensgrenzen (factureringsstromen)

**Probleem**
Doorgestuurde headers (bijv. host/proto) beïnvloedden de origin-logica zonder de verzoekbron te verifiëren. Een onjuiste configuratie kon hostmanipulatie toestaan.

**Oplossing**
◆ **Vertrouwde proxy-controle** geïntroduceerd
◆ Doorgestuurde headers worden alleen geaccepteerd van bekende infrastructuur
◆ Alle andere gevallen vallen terug op de canonieke applicatie-origin

**Resultaat**
Origin-afhandeling is nu **deterministisch en bestand tegen header-spoofing**.

### 3) CSRF-bescherming (factureringsacties)

**Probleem**
Geauthenticeerde factureringseindpunten misten CSRF-validatie. Dit stelde mutatie-eindpunten bloot aan cross-site request forgery onder geldige sessies.

**Oplossing**
◆ CSRF-validatie toegepast op alle factueringsmutaties
◆ Gecentraliseerde tokenverificatielogica
◆ Frontend verzendt tokens consistent

**Resultaat**
Alle factuuroperaties die de status wijzigen vereisen nu **expliciet door de gebruiker geïnitieerde verzoeken**.

## Aanvullende review

### Command-uitvoeringsoppervlakken

We hebben codepaden met uitvoeringsprimitieven (bijv. shell/exec) gereviewed.

**Huidige status**
◆ Geen actieve blootstelling via controllers of publieke routes
◆ Geen bewijs van runtime-aanroep in verzoekpaden

**Standpunt**
◆ Behandelen als **uitsluitend niet-publieke interne tools**
◆ Kandidaat voor toekomstige verwijdering of isolatie

## Verificatie

Alle wijzigingen zijn gevalideerd via:

◆ PHP lint op gewijzigde bestanden
◆ Statische editor-diagnostiek
◆ Handmatige inspectie van verzoekstromen

Er zijn geen syntaxis- of runtime-problemen geïntroduceerd.

## Toegepaste beveiligingsprincipes

Deze versterking bevestigt enkele kernprincipes:

◆ **Standaard weigeren** versus impliciete vertrouwen
◆ **Expliciete vertrouwensgrenzen** (bijv. proxy's, origins)
◆ **Validatie op elk extern ingangspunt**
◆ **Gecentraliseerde beveiligingscontroles** versus verspreide checks

## Wat dit betekent voor gebruikers

◆ Verminderd phishing-risico door misbruik van omleidingen
◆ Sterkere garanties rond factureringsacties
◆ Verbeterde integriteit in verzoekafhandeling en origin-validatie

Er is geen actie vereist van gebruikers.

## Lopend werk

We behandelen beveiliging als een doorlopend proces. Volgende stappen omvatten:

◆ Integratietests: gedrag van omleidingsvalidatie
◆ Integratietests: CSRF-handhaving op eindpunten
◆ Integratietests: afhandeling van proxy-vertrouwensgrenzen
◆ Periodieke scans: omleidingssinkholes
◆ Periodieke scans: header-vertrouwensregressies
◆ Interne triage van hoog-risico routes

## Bijgewerkte bestanden

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Slotopmerking

Deze inspanning richtte zich op het elimineren van **realistische exploitatiepaden**, niet van theoretische randgevallen. We blijven prioriteit geven aan wijzigingen die de beveiliging significant verbeteren terwijl de productbetrouwbaarheid behouden blijft.
