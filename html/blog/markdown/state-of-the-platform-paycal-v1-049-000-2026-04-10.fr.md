---
title: État de la Plateforme : PayCal Version 1.049.000
date: 2026-04-10
author: Équipe PayCal
tags: release, accessibility, privacy, security, premium
---

## Vue d'ensemble

PayCal Version 1.049.000 marque une étape architecturale majeure. La plateforme fonctionne désormais comme un environnement deny-safe pour le suivi professionnel du travail, avec souveraineté de la vie privée et accessibilité radicale intégrées au cœur du produit.

Avec une base de code de 945 fichiers mathématiquement vérifiés, cette version reflète le passage d'une évolution rapide des fonctionnalités à une stabilité durable de la plateforme.

## L'accessibilité est maintenant vérifiable

Au 10 avril 2026, la WCAG Theme Contrast Matrix confirme un taux de réussite complet sur l'ensemble du système visuel.

◆ 68 thèmes analysés sur 2 040 points de contrôle
◆ Seuil minimal de contraste de 4,75:1 imposé sur tous les tokens de thème
◆ Couverture de tous les designs sélectionnables, y compris Matrix (15,56:1) et Akira (14,02:1)

Résultat : une lisibilité cohérente quel que soit le thème choisi.

## Souveraineté de la vie privée : trois piliers de sécurité

### 1) Authentification uniquement par passkeys (Workstream G)

PayCal a finalisé la suppression du pont d'identifiants navigateur et fonctionne désormais exclusivement avec des passkeys.

◆ Aucun risque lié à une base de mots de passe
◆ WebAuthn + HKDF dérivent localement une Key Encryption Key (KEK)
◆ Le serveur ne reçoit que du matériel de clé encapsulé

### 2) Effacement automatique des données (Workstream D)

L'état sensible est maintenu comme strictement éphémère.

◆ Masquage d'onglet et sortie de page déclenchent un DOM Sensitivity Scrub
◆ Les clés de sécurité et l'état sensible sont effacés de la mémoire
◆ La rétention des données respecte des limites strictes de nécessité

### 3) Télémétrie Privacy Guard (Workstream B)

L'observabilité opérationnelle est conservée sans fuite d'identité.

◆ Télémétrie anonymisée
◆ Livraison par lots avec jitter aléatoire
◆ Journaux conçus pour empêcher la corrélation avec sessions ou revenus

## Points forts des outils professionnels

### AriaEcho Narration

La narration orientée accessibilité transforme les enregistrements bruts de temps et de rémunération en formulations naturelles et professionnelles pour les flux assistifs.

### Private Math (moteur fiscal local)

Les calculs fiscaux s'exécutent entièrement dans le navigateur, gardant les calculs sensibles de revenus hors des serveurs distants.

### Exportations professionnelles

Export PDF, CSV et texte en un clic. Export Identity Inversion utilise une identité temporaire assainie pour les en-têtes et la supprime immédiatement après téléchargement.

### Safety Net Recovery

Orphaned Work Recovery détecte les enregistrements non liés après suppression de sites et aide à les reconnecter pour préserver la continuité historique.

## Premium : collaboration sans compromis

Les fonctionnalités premium pour organisations apportent un contrôle opérationnel renforcé sans sacrifier la confidentialité individuelle.

◆ Organization Hub pour les flux employeurs et équipes
◆ Modèle de portée des rôles affiné avec autorisations granulaires
◆ Vues calendrier déléguées pour la supervision managériale
◆ DEK Auto-Bootstrap pour une préparation de chiffrement immédiate à l'ouverture de page

## Conclusion

PayCal v1.049.000 est plus qu'une incrémentation de version. C'est un engagement de plateforme envers un design accessible, la souveraineté de la vie privée et un traitement des données contrôlé par l'utilisateur à grande échelle.

Sécurisé. Accessible. À vous. C'est PayCal.
