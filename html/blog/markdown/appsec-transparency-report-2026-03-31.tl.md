---
title: Ulat ng Transparency sa Seguridad ng Aplikasyon
date: 2026-03-31
author: PayCal Seguridad
tags: security, appsec, billing_hardening
---

## Metadata ng Ulat

◆ Petsa: 2026-03-31
◆ Saklaw: Paghawak ng kahilingan, mga redirect, proteksyon ng API, at mga hangganan ng tiwala
◆ Sanggunian: Panloob na audit sa seguridad (2026-03-31)

## Pangkalahatang-ideya

Kamakailan naming natapos ang isang pagsusuri sa seguridad ng aplikasyon na nakatuon sa mga tunay na vector ng pag-atake na nakakaapekto sa mga modernong web application. Inuna ng pagsisikap na ito ang **praktikal na pagbabawas ng panganib** nang hindi nasiira ang normal na gawi ng produkto.

Inilalarawan ng dokumentong ito kung ano ang natukoy, kung ano ang binago, at kung paano namin haharapin ang patuloy na seguridad.

### Kaganapang Nag-trigger at Mga Panlabas na Ulat

Naabisuhan kami ngayon ng mga kumpirmadong ulat tungkol sa pagkakompromiso ng npm Axios package. Ang alertong iyon ay direktang nag-trigger ng kumpletong audit at panloob na system sweep cycle na ito.

Mga panlabas na teknikal na sanggunian:
◆ BleepingComputer: [Kinompromiso ng mga hacker ang Axios npm package para mag-drop ng cross-platform na malware](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Ang pag-atake sa supply chain ng Axios ay nagtutulak ng cross-platform RAT sa pamamagitan ng nakompromisong npm account](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Pagsabog ng supply chain: sikat na npm package na may backdoor para mag-install ng RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Mga Pangunahing Natuklasan

Natukoy at niremediyo namin ang tatlong makabuluhang panganib sa seguridad:

◆ Pamamahala ng redirect: vector ng bukas na redirect (naayos na)
◆ Tiwala sa header: Host/header poisoning (naayos na)
◆ Proteksyon ng API: mga nawawalang tsek ng CSRF (naayos na)

## Ano ang Aming Inayos

### 1) Seguridad ng Redirect (pagbabago ng wika)

**Problema**
Umasa ang mga redirect sa `HTTP_REFERER`, na maaaring wala o maaaring manipulahin. Gumagawa ito ng mga potensyal na chain ng phishing gamit ang mga pinagkakatiwalaang domain.

**Resolusyon**
◆ Mahigpit na pagpapatunay ng host na inilapat
◆ Mga panloob o same-origin na redirect lamang ang pinapayagan
◆ Default na fallback sa `/` kapag nabigo ang validation

**Resulta**
Ang mga redirect ay ngayon ay **malinaw na limitado sa mga pinagkakatiwalaang pinagmulan**.

### 2) Mga Hangganan ng Tiwala ng Header (mga daloy ng billing)

**Problema**
Ang mga ipinaabot na header (hal. host/proto) ay nakaimpluwensya sa lohika ng pinagmulan nang hindi bine-verify ang pinagmulan ng kahilingan. Ang maling configuration ay maaaring magpahintulot sa manipulasyon ng host.

**Resolusyon**
◆ Ipinakilala ang **kontrol ng pinagkakatiwalaang proxy**
◆ Ang mga ipinaabot na header ay tinatanggap lamang mula sa kilalang imprastraktura
◆ Ang lahat ng iba pang kaso ay bumabalik sa kanonikong pinagmulan ng aplikasyon

**Resulta**
Ang paghawak ng pinagmulan ay ngayon ay **deterministik at lumalaban sa header spoofing**.

### 3) Proteksyon ng CSRF (mga aksyon ng billing)

**Problema**
Ang mga authenticated na endpoint ng billing ay kulang sa CSRF validation. Inilantad nito ang mga mutation endpoint sa cross-site request forgery sa ilalim ng mga valid na session.

**Resolusyon**
◆ CSRF validation na inilapat sa lahat ng mga mutasyon ng billing
◆ Sentralisadong lohika ng pag-verify ng token
◆ Ang frontend ay nagpapadala ng mga token nang tuloy-tuloy

**Resulta**
Ang lahat ng operasyon ng billing na nagbabago ng estado ay nangangailangan na ngayon ng **mga kahilingang malinaw na sinimulan ng gumagamit**.

## Karagdagang Pagsusuri

### Mga Ibabaw ng Pagpapatakbo ng Utos

Sinuri namin ang mga landas ng code na naglalaman ng mga primitive ng pagpapatakbo (hal. shell/exec).

**Kasalukuyang Katayuan**
◆ Walang aktibong exposure sa pamamagitan ng mga controller o pampublikong route
◆ Walang katibayan ng runtime invocation sa mga landas ng kahilingan

**Posisyon**
◆ Tratuhin bilang **mga eksklusibong hindi pampublikong panloob na tool**
◆ Kandidato para sa hinaharap na pagtanggal o paghihiwalay

## Pag-verify

Lahat ng pagbabago ay na-validate sa pamamagitan ng:

◆ PHP lint sa mga binagong file
◆ Mga static na diagnostic ng editor
◆ Manu-manong inspeksyon ng mga daloy ng kahilingan

Walang mga isyu sa syntax o runtime ang naipasok.

## Mga Prinsipyong Panseguridad na Inilapat

Ang pagpapatibay na ito ay muling nagpapatunay ng ilang pangunahing prinsipyo:

◆ **Default na pagtanggi** kumpara sa implicit na tiwala
◆ **Malinaw na mga hangganan ng tiwala** (hal. mga proxy, pinagmulan)
◆ **Validation sa bawat panlabas na punto ng pagpasok**
◆ **Mga sentralisadong kontrol sa seguridad** kumpara sa mga nakakalat na tsek

## Ano ang Kahulugan Nito para sa mga Gumagamit

◆ Nabawasang panganib ng phishing sa pamamagitan ng pag-abuso sa redirect
◆ Mas matibay na garantiya sa paligid ng mga aksyon ng billing
◆ Pinahusay na integridad sa paghawak ng kahilingan at validation ng pinagmulan

Walang kinakailangang aksyon mula sa mga gumagamit.

## Patuloy na Trabaho

Tinatrato namin ang seguridad bilang isang patuloy na proseso. Kasama sa mga susunod na hakbang:

◆ Mga pagsubok sa integrasyon: gawi ng redirect validation
◆ Mga pagsubok sa integrasyon: CSRF enforcement sa mga endpoint
◆ Mga pagsubok sa integrasyon: paghawak ng hangganan ng tiwala ng proxy
◆ Mga pana-panahong pag-scan: mga sinkhole ng redirect
◆ Mga pana-panahong pag-scan: mga regression ng tiwala ng header
◆ Panloob na triage ng mga mataas na panganib na route

## Mga Na-update na File

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Pangwakas na Tala

Ang pagsisikap na ito ay nakatuon sa pag-aalis ng **mga makatotohanang landas ng pagsasamantala**, hindi mga teoryang edge case. Patuloy naming uunahin ang mga pagbabago na makabuluhang nagpapabuti ng seguridad habang pinapanatili ang pagiging maaasahan ng produkto.
