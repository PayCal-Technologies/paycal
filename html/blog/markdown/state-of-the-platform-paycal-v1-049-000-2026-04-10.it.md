---
title: Stato della Piattaforma: PayCal Versione 1.049.000
date: 2026-04-10
author: Team PayCal
tags: release, accessibility, privacy, security, premium
---

## Panoramica

PayCal Versione 1.049.000 rappresenta un traguardo architetturale importante. La piattaforma ora opera come ambiente deny-safe per il tracciamento professionale del lavoro, con sovranità della privacy e accessibilità radicale integrate nel comportamento centrale del prodotto.

Con una codebase di 945 file verificati matematicamente, questa release segna il passaggio da uno sviluppo rapido di funzionalità a una stabilità di piattaforma più duratura.

## L'accessibilità è ora verificabile

Al 10 aprile 2026, la WCAG Theme Contrast Matrix conferma un tasso di superamento completo in tutto il sistema visivo.

◆ 68 temi analizzati su 2.040 checkpoint
◆ Soglia minima di contrasto 4,75:1 applicata a tutti i token del tema
◆ Copertura di tutti i design selezionabili, inclusi Matrix (15,56:1) e Akira (14,02:1)

Il risultato è una leggibilità coerente indipendentemente dal tema scelto.

## Sovranità della privacy: tre pilastri di sicurezza

### 1) Autenticazione solo passkey (Workstream G)

PayCal ha completato la rimozione del browser-credential bridge e ora opera esclusivamente con passkey.

◆ Nessun rischio legato a database password
◆ WebAuthn + HKDF derivano localmente una Key Encryption Key (KEK)
◆ Il server riceve solo materiale di chiave wrapped

### 2) Cancellazione automatica dei dati (Workstream D)

Lo stato sensibile viene mantenuto intenzionalmente a vita breve.

◆ Nascondere la scheda o uscire dalla pagina attiva un DOM Sensitivity Scrub
◆ Chiavi di sicurezza e stato sensibile vengono rimossi dalla memoria
◆ La conservazione dati rispetta limiti rigorosi di necessità

### 3) Telemetria Privacy Guard (Workstream B)

L'osservabilità operativa viene mantenuta senza perdita di identità.

◆ Telemetria anonimizzata
◆ Consegna a batch con jitter casuale
◆ Log progettati per impedire correlazioni con sessioni o eventi di guadagno

## Punti chiave del toolkit professionale

### AriaEcho Narration

La narrazione orientata all'accessibilità converte registri grezzi di tempo e retribuzione in linguaggio naturale e professionale per flussi assistivi.

### Private Math (motore fiscale locale)

I calcoli fiscali avvengono interamente nel browser, mantenendo i calcoli sensibili dei redditi fuori dai server remoti.

### Esportazioni professionali

Esportazioni PDF, CSV e testo disponibili con un clic. Export Identity Inversion usa un'identità temporanea sanificata per le intestazioni e la elimina immediatamente dopo il download.

### Safety Net Recovery

Orphaned Work Recovery rileva record scollegati dopo cancellazioni di siti e supporta il ricollegamento per preservare la continuità storica.

## Premium: collaborazione senza compromessi

Le funzionalità premium per organizzazioni offrono maggiore controllo operativo senza sacrificare la privacy individuale.

◆ Organization Hub per flussi di lavoro di datori di lavoro e team
◆ Modello di ruolo raffinato con permessi granulari
◆ Viste calendario delegate per supervisione manageriale
◆ DEK Auto-Bootstrap per prontezza di cifratura immediata alla visita pagina

## Chiusura

PayCal v1.049.000 è più di un incremento di versione. È un impegno di piattaforma verso design accessibile, sovranità della privacy e gestione dei dati controllata dall'utente su larga scala.

Sicuro. Accessibile. Tuo. Questo è PayCal.
