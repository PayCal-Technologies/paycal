---
title: Rapporto sulla trasparenza della sicurezza applicativa
date: 2026-03-31
author: Sicurezza PayCal
tags: security, appsec, billing_hardening
---

## Metadati del rapporto

◆ Data: 2026-03-31
◆ Ambito: Gestione delle richieste, reindirizzamenti, protezioni API e limiti di fiducia
◆ Riferimento: Audit di sicurezza interno (2026-03-31)

## Panoramica

Abbiamo recentemente completato una revisione della sicurezza applicativa focalizzata su vettori di attacco reali che colpiscono le moderne applicazioni web. Questo sforzo ha dato priorità alla **riduzione pratica del rischio** senza interrompere il normale comportamento del prodotto.

Questo documento descrive cosa è stato identificato, cosa è stato modificato e come affrontiamo la sicurezza continua.

### Evento scatenante e report esterni

Siamo stati allertati oggi da report confermati sulla compromissione del pacchetto npm Axios. Quell'alert ha direttamente innescato questo ciclo completo di audit e scansione interna del sistema.

Riferimenti tecnici esterni:
◆ BleepingComputer: [Gli hacker compromettono il pacchetto npm Axios per distribuire malware multipiattaforma](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [L'attacco alla supply chain di Axios distribuisce RAT multipiattaforma tramite account npm compromesso](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Esplosione della supply chain: pacchetto npm popolare con backdoor per installare un RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Risultati chiave

Abbiamo identificato e rimediato tre significativi rischi di sicurezza:

◆ Gestione dei reindirizzamenti: vettore di open redirect (corretto)
◆ Fiducia negli header: avvelenamento Host/header (corretto)
◆ Protezione API: controlli CSRF mancanti (corretti)

## Cosa abbiamo corretto

### 1) Sicurezza nei reindirizzamenti (cambio lingua)

**Problema**
I reindirizzamenti dipendevano da `HTTP_REFERER`, che può essere assente o manipolato. Ciò crea potenziali catene di phishing utilizzando domini fidati.

**Risoluzione**
◆ Validazione rigorosa dell'host applicata
◆ Sono consentiti solo reindirizzamenti interni o della stessa origine
◆ Ritorno predefinito a `/` quando la validazione fallisce

**Risultato**
I reindirizzamenti sono ora **esplicitamente limitati a origini fidate**.

### 2) Limiti di fiducia degli header (flussi di fatturazione)

**Problema**
Gli header inoltrati (es. host/proto) influenzavano la logica dell'origine senza verificare la fonte della richiesta. Una configurazione errata potrebbe consentire la manipolazione dell'host.

**Risoluzione**
◆ Introdotto il **controllo dei proxy fidati**
◆ Gli header inoltrati vengono accettati solo dall'infrastruttura nota
◆ Tutti gli altri casi tornano all'origine canonica dell'applicazione

**Risultato**
La gestione dell'origine è ora **deterministica e resistente allo spoofing degli header**.

### 3) Protezione CSRF (azioni di fatturazione)

**Problema**
Gli endpoint di fatturazione autenticati mancavano della validazione CSRF. Ciò esponeva gli endpoint di mutazione alla falsificazione di richieste cross-site in sessioni valide.

**Risoluzione**
◆ Validazione CSRF applicata a tutte le mutazioni di fatturazione
◆ Logica di verifica dei token centralizzata
◆ Il frontend invia i token in modo coerente

**Risultato**
Tutte le operazioni di fatturazione che modificano lo stato richiedono ora **richieste esplicitamente avviate dall'utente**.

## Revisione aggiuntiva

### Superfici di esecuzione dei comandi

Abbiamo revisionato i percorsi di codice contenenti primitive di esecuzione (es. shell/exec).

**Stato attuale**
◆ Nessuna esposizione attiva tramite controller o route pubbliche
◆ Nessuna evidenza di invocazione a runtime nei percorsi di richiesta

**Posizione**
◆ Trattare come **strumenti interni non pubblici esclusivamente**
◆ Candidato per rimozione o isolamento futuro

## Verifica

Tutte le modifiche sono state validate tramite:

◆ Lint PHP sui file modificati
◆ Diagnostica statica dell'editor
◆ Ispezione manuale dei flussi di richiesta

Non sono stati introdotti problemi di sintassi o runtime.

## Principi di sicurezza applicati

Questo rafforzamento riafferma alcuni principi fondamentali:

◆ **Negazione predefinita** rispetto alla fiducia implicita
◆ **Limiti di fiducia espliciti** (es. proxy, origini)
◆ **Validazione ad ogni punto di ingresso esterno**
◆ **Controlli di sicurezza centralizzati** rispetto a controlli dispersi

## Cosa significa per gli utenti

◆ Rischio di phishing ridotto tramite abuso dei reindirizzamenti
◆ Garanzie più solide sulle azioni di fatturazione
◆ Integrità migliorata nella gestione delle richieste e nella validazione dell'origine

Non è richiesta alcuna azione da parte degli utenti.

## Lavoro in corso

Trattiamo la sicurezza come un processo continuo. I prossimi passi includono:

◆ Test di integrazione: comportamento della validazione dei reindirizzamenti
◆ Test di integrazione: applicazione CSRF sugli endpoint
◆ Test di integrazione: gestione dei limiti di fiducia dei proxy
◆ Scansioni periodiche: sink dei reindirizzamenti
◆ Scansioni periodiche: regressioni di fiducia degli header
◆ Triage interno delle route ad alto rischio

## File aggiornati

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Nota conclusiva

Questo sforzo si è concentrato sull'eliminazione di **percorsi di sfruttamento realistici**, non di casi limite teorici. Continueremo a dare priorità ai cambiamenti che migliorano significativamente la sicurezza preservando l'affidabilità del prodotto.
