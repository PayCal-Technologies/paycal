---
title: Staat van het Platform: PayCal Versie 1.049.000
date: 2026-04-10
author: PayCal Team
tags: release, accessibility, privacy, security, premium
---

## Overzicht

PayCal Versie 1.049.000 markeert een belangrijke architecturale mijlpaal. Het platform werkt nu als een deny-safe omgeving voor professionele arbeidsregistratie, met privacysoevereiniteit en radicale toegankelijkheid ingebouwd in de kern van het product.

Met een codebase van 945 wiskundig geverifieerde bestanden laat deze release een verschuiving zien van snelle featuregroei naar duurzame platformstabiliteit.

## Toegankelijkheid is nu aantoonbaar

Per 10 april 2026 bevestigt de WCAG Theme Contrast Matrix een volledige slagingsscore over het hele visuele systeem.

◆ 68 thema's gescand over 2.040 controlepunten
◆ Minimale contrastdrempel van 4,75:1 afgedwongen op alle thema-tokens
◆ Dekking van alle selecteerbare ontwerpen, inclusief Matrix (15,56:1) en Akira (14,02:1)

Het resultaat is consistente leesbaarheid, ongeacht het gekozen thema.

## Privacysoevereiniteit: drie beveiligingspijlers

### 1) Alleen passkey-authenticatie (Workstream G)

PayCal heeft de browser-credential bridge volledig verwijderd en werkt nu uitsluitend met passkeys.

◆ Geen risico van blootgestelde wachtwoorddatabases
◆ WebAuthn + HKDF leiden lokaal een Key Encryption Key (KEK) af
◆ De server ontvangt alleen ingepakt sleutelmateriaal

### 2) Automatisch opschonen van data (Workstream D)

Gevoelige status wordt bewust kortlevend gehouden.

◆ Tabblad verbergen en pagina verlaten triggeren een DOM Sensitivity Scrub
◆ Beveiligingssleutels en gevoelige werkstatus worden uit geheugen gewist
◆ Dataretentie volgt strikte noodzaakgrenzen

### 3) Privacy Guard-telemetrie (Workstream B)

Operationele observatie blijft mogelijk zonder identiteitslek.

◆ Telemetrie is geanonimiseerd
◆ Levering in batches met willekeurige jitter
◆ Logs zijn ontworpen om correlatie met sessies of inkomsten te voorkomen

## Professionele toolkit: highlights

### AriaEcho Narration

Toegankelijkheidsgerichte narratie zet ruwe tijd- en loonregistraties om in natuurlijke, professionele taal voor ondersteunende workflows.

### Private Math (lokale belastingengine)

Belastingberekeningen draaien volledig in de browser, zodat gevoelige inkomensberekeningen buiten externe servers blijven.

### Professionele exports

PDF-, CSV- en tekstdownloads met één klik. Export Identity Inversion gebruikt een opgeschoonde tijdelijke rapportidentiteit die direct na download wordt verwijderd.

### Safety Net Recovery

Orphaned Work Recovery detecteert ontkoppelde werkrecords na site-verwijderingen en helpt bij het opnieuw koppelen om historische continuïteit te behouden.

## Premium: samenwerking zonder compromis

Premium-organisatiefuncties bieden sterkere operationele controle zonder individuele privacy in te leveren.

◆ Organization Hub voor werkgevers- en teamworkflows
◆ Verfijnd rolmodel met granulaire rechten
◆ Gedelegeerde kalenderweergaven voor managementtoezicht
◆ DEK Auto-Bootstrap voor directe encryptiegereedheid bij paginabezoek

## Slot

PayCal v1.049.000 is meer dan een versieverhoging. Het is een platformbelofte voor toegankelijk ontwerp, privacysoevereiniteit en door gebruikers gecontroleerde gegevensverwerking op schaal.

Veilig. Toegankelijk. Van jou. Dit is PayCal.
