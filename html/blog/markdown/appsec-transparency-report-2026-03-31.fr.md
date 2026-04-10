---
title: Rapport de transparence sur la sécurité applicative
date: 2026-03-31
author: PayCal Sécurité
tags: security, appsec, billing_hardening
---

## Métadonnées du rapport

◆ Date : 2026-03-31
◆ Périmètre : Gestion des redirections, protections API et périmètres de confiance
◆ Référence : Audit de sécurité interne (2026-03-31)

## Vue d'ensemble

Nous avons récemment effectué un audit approfondi de la sécurité applicative ciblant des vecteurs d'attaque réels affectant les applications web modernes. Cet effort a priorisé la **réduction pratique des risques** sans perturber le comportement normal du produit.

Ce document décrit ce qui a été identifié, ce qui a été corrigé, et notre approche de la sécurité en continu.

### Élément déclencheur et signalement externe

Nous avons été alertés aujourd'hui par des signalements confirmés concernant la compromission du paquet npm Axios. Cette alerte a directement déclenché ce cycle complet d'audit et de balayage interne.

Références techniques externes :
◆ BleepingComputer : [Des pirates compromettent le paquet npm Axios pour déployer un malware multiplateforme](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News : [L'attaque sur la chaîne d'approvisionnement Axios propage un RAT multiplateforme via un compte npm compromis](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register : [Explosion dans la chaîne d'approvisionnement : un paquet npm populaire backdooré pour déposer un RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Résultats clés

Nous avons identifié et remédié trois risques de sécurité significatifs :

◆ Gestion des redirections : vecteur de redirection ouverte (corrigé)
◆ Confiance dans les en-têtes : empoisonnement Host/header (corrigé)
◆ Protection API : contrôles CSRF manquants (corrigé)

## Ce que nous avons corrigé

### 1) Sécurité des redirections (changement de langue)

**Problème**
Les redirections reposaient sur `HTTP_REFERER`, qui peut être absent ou manipulé. Cela crée des chaînes de phishing potentielles via des domaines de confiance.

**Résolution**
◆ Validation stricte du domaine d'origine imposée
◆ Seules les redirections internes ou vers la même origine sont autorisées
◆ Repli par défaut sur `/` en cas d'échec de validation

**Résultat**
Les redirections sont désormais **explicitement limitées aux origines de confiance**.

### 2) Périmètres de confiance des en-têtes (flux de facturation)

**Problème**
Les en-têtes transmis (ex. host/proto) influençaient la logique d'origine sans vérification de la source de la requête. Une mauvaise configuration pourrait permettre la manipulation du host.

**Résolution**
◆ Introduction d'un **contrôle de mandataire de confiance**
◆ Les en-têtes transférés ne sont acceptés que depuis l'infrastructure connue
◆ Tous les autres cas utilisent l'origine canonique de l'application

**Résultat**
La gestion de l'origine est désormais **déterministe et résistante à l'usurpation d'en-tête**.

### 3) Protection CSRF (actions de facturation)

**Problème**
Les endpoints de facturation authentifiés manquaient de validation CSRF. Cela exposait les endpoints de mutation à la falsification de requête inter-sites sous des sessions valides.

**Résolution**
◆ Validation CSRF appliquée à toutes les mutations de facturation
◆ Logique de vérification de token centralisée
◆ Le frontend envoie systématiquement les tokens

**Résultat**
Toutes les opérations de facturation modifiant un état nécessitent désormais des **requêtes explicitement initiées par l'utilisateur**.

## Revue complémentaire

### Surfaces d'exécution de commandes

Nous avons examiné les chemins de code contenant des primitives d'exécution (ex. shell/exec).

**État actuel**
◆ Aucune exposition active via un contrôleur ou une route publique
◆ Aucune preuve d'invocation à l'exécution dans les chemins de requête

**Position**
◆ Considérer comme **outillage interne non public uniquement**
◆ Candidat à une future suppression ou isolation

## Vérification

Toutes les modifications ont été validées via :

◆ Lint PHP sur les fichiers modifiés
◆ Diagnostics statiques de l'éditeur
◆ Inspection manuelle des flux de requêtes

Aucun problème de syntaxe ou d'exécution n'a été introduit.

## Principes de sécurité appliqués

Ce renforcement réaffirme quelques principes fondamentaux :

◆ **Refus par défaut** plutôt que confiance implicite
◆ **Périmètres de confiance explicites** (ex. mandataires, origines)
◆ **Validation à chaque point d'entrée externe**
◆ **Contrôles de sécurité centralisés** plutôt que dispersés

## Ce que cela signifie pour les utilisateurs

◆ Risque réduit de phishing par abus de redirections
◆ Garanties renforcées concernant les actions de facturation
◆ Intégrité améliorée du traitement des requêtes et de la validation d'origine

Aucune action n'est requise de la part des utilisateurs.

## Travaux en cours

Nous traitons la sécurité comme un processus continu. Les prochaines étapes comprennent :

◆ Tests d'intégration : comportement de validation des redirections
◆ Tests d'intégration : application du CSRF sur les endpoints
◆ Tests d'intégration : gestion des périmètres de confiance des mandataires
◆ Analyses périodiques : points de sortie des redirections
◆ Analyses périodiques : régressions dans la confiance des en-têtes
◆ Classification interne des routes à haut risque

## Fichiers mis à jour

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Note de clôture

Cet effort s'est concentré sur l'élimination des **chemins d'exploitation réalistes**, et non des cas limites théoriques. Nous continuerons à prioriser les changements qui améliorent meaningfully la sécurité tout en préservant la fiabilité du produit.
