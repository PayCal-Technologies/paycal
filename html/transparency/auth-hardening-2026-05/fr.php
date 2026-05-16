<?php
/**
 * Public Transparency: Auth, Passkey, and Redis Hardening — May 2026
 *
 * PURPOSE: Disclose all findings from the May 12, 2026 internal security audit of
 * authentication, passkey, and Redis infrastructure. Describes each flaw, its
 * risk, and exactly how it was fixed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Renforcement Auth, Passkey et Redis — Mai 2026 - [PayCal]';
$pageLabel = 'Renforcement Auth, Passkey & Redis — Mai 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Renforcement Auth, Passkey &amp; Redis — Mai 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Renforcement Auth, Passkey &amp; Redis — Mai 2026</h1>
    <p class="deck">
      Le 12 mai 2026, nous avons conduit un audit interne de notre infrastructure d'authentification,
      de clés d'accès et de Redis. Nous avons trouvé onze problèmes — tous dans du code que nous
      avons écrit nous-mêmes. Cet article documente ce que nous avons trouvé, pourquoi c'était
      important, et exactement ce que nous avons modifié.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Résumé exécutif</h2>
      <table class="doc-table" aria-label="Résumé exécutif des résultats d'audit">
        <tbody>
          <tr>
            <td><strong>Date d'audit</strong></td>
            <td>12 mai 2026</td>
          </tr>
          <tr>
            <td><strong>Périmètre</strong></td>
            <td>Authentification, clés d'accès (WebAuthn) et infrastructure Redis</td>
          </tr>
          <tr>
            <td><strong>Total des constats</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Répartition par gravité</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Statut de remédiation</strong></td>
            <td>Tous les constats résolus dans le commit <code>493d5e44</code>. Suite de tests complète validée. Aucune régression.</td>
          </tr>
          <tr>
            <td><strong>Preuves d'exploitation</strong></td>
            <td>Aucune</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Pourquoi nous publions ceci</h2>
      <p>
        Nous avons trouvé ces problèmes dans notre propre code applicatif et nos couches
        d'infrastructure — pas dans des dépendances tierces ou des services externes. Du code
        que nous avons revu, commis et livré.
      </p>
      <p>
        Nous publions ceci parce que la transparence en matière de sécurité exige plus que la
        divulgation de CVE externes ou la réussite d'audits. Cela signifie être publiquement
        responsable lorsque votre propre équipe livre du code qui ne répond pas au niveau que
        vous vous êtes fixé.
      </p>
      <p>
        Nous n'en sommes pas embarrassés. L'échec plus grave aurait été de découvrir ces
        problèmes et de choisir de ne pas les divulguer.
      </p>
    </section>

    <section class="doc-section">
      <h2>Méthodologie d'audit</h2>
      <p>
        Cet audit a été conduit en interne par l'équipe d'ingénierie le 12 mai 2026. La revue
        a couvert tous les chemins de code liés à la gestion de l'état d'authentification,
        au cycle de vie des credentials WebAuthn et à la gestion des clés Redis.
      </p>
      <ul class="doc-list">
        <li><strong>Revue manuelle du code</strong> de tous les fichiers controller, domain et infrastructure impliqués dans la création de session, l'enregistrement de clé d'accès, la connexion par clé d'accès et les flux de récupération de compte.</li>
        <li><strong>Analyse statique</strong> via PHPStan au Niveau 9 — tolérance zéro pour les chemins de code non sécurisés ou inaccessibles.</li>
        <li><strong>Modélisation des menaces</strong> contre la spécification WebAuthn Niveau 2 (§6.1 données d'authentificateur, §7.1 cérémonie d'enregistrement, §7.2 cérémonie d'authentification).</li>
        <li><strong>Tests de régression</strong> avec la suite de régression PHPUnit complète après remédiation. Tous les tests ont réussi.</li>
      </ul>
      <p>Aucun auditeur externe, rapport de bug bounty ou incident de sécurité n'a précédé cette revue. Ces problèmes ont été identifiés lors d'un processus interne de routine.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Notre philosophie d'ingénierie</h2>
      <p>Cet audit a révélé des défaillances dans trois principes que nous considérons comme fondamentaux :</p>
      <ul class="doc-list">
        <li>
          <strong>L'atomicité avant la correction.</strong> Si deux opérations doivent se produire
          ensemble, traitez-les comme une seule opération ou n'essayez pas du tout la conception.
          Un système &laquo;&nbsp;correct la plupart du temps&nbsp;&raquo; n'est pas correct.
        </li>
        <li>
          <strong>Défense en couches.</strong> Aucun contrôle unique ne devrait être la seule
          barrière à une frontière de sécurité. Si la base de données marque un credential comme
          révoqué, le chemin d'enregistrement doit aussi l'appliquer. La défense ne doit pas avoir
          de lacunes entre les couches.
        </li>
        <li>
          <strong>L'asymétrie d'information comme objectif de conception.</strong> Un attaquant qui
          sonde le système devrait en apprendre le moins possible sur ce qui se passe à l'intérieur.
          Les messages d'erreur, les entrées de journal et le timing des réponses sont tous des
          surfaces d'exposition.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Constat 1 &mdash; <code>hset + expire</code> non atomique (condition de course Redis) <span class="doc-badge high">High</span></h2>
      <p><strong>Catégorie : Redis / Atomicité</strong></p>
      <p>
        Sur huit sites d'appel, un hash Redis était écrit avec <code>HSET</code> puis immédiatement
        associé à un TTL avec une commande <code>EXPIRE</code> séparée :
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Ce sont deux allers-retours séparés vers Redis. Si le processus PHP se termine, est interrompu,
        dépasse un délai d'expiration, ou si Redis subit une défaillance momentanée entre les deux,
        le hash est écrit sans expiration — et vit indéfiniment dans Redis.
      </p>
      <p>Les sites d'appel concernés et leurs implications en matière de sécurité :</p>
      <table class="doc-table" aria-label="Sites d'appel concernés pour hset+expire non atomique">
        <thead>
          <tr>
            <th scope="col">Site d'appel</th>
            <th scope="col">Type de clé</th>
            <th scope="col">Conséquence du TTL manquant</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Enregistrement de session</td>
            <td>La session n'expire jamais — compte accessible au-delà de sa durée de vie prévue</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge inscription)</td>
            <td>Challenge WebAuthn</td>
            <td>Les données de challenge périmées persistent au-delà de leur durée de vie prévue, augmentant le risque de rejeu</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge enregistrement)</td>
            <td>Challenge WebAuthn</td>
            <td>Idem</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge connexion)</td>
            <td>Challenge WebAuthn</td>
            <td>Idem</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Challenge clé d'accès de récupération</td>
            <td>Les données de session de récupération n'expirent jamais</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (émission du code)</td>
            <td>Code e-mail de récupération</td>
            <td>Les codes à usage unique survivent au-delà de leur fenêtre d'expiration prévue</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (renvoi du code)</td>
            <td>Code e-mail de récupération</td>
            <td>Idem</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Tokens admin à usage unique</td>
            <td>Les tokens conçus pour expirer en 5 minutes peuvent survivre indéfiniment</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Enregistrement de transaction de récupération</td>
            <td>L'état de transaction de récupération n'est jamais nettoyé</td>
          </tr>
        </tbody>
      </table>
      <p>
        Pour les sessions, c'est une violation directe de la durée de vie d'accès. Une session devrait
        avoir un plafond strict. Si le TTL n'est jamais défini, ce plafond n'existe pas.
      </p>
      <p>
        Pour les tokens de capacité à usage unique, un token conçu pour être valide exactement 300 secondes
        peut encore être valide des jours plus tard.
      </p>
      <p><strong>Le correctif :</strong> Nous avons introduit <code>Database::hsetex()</code> — un wrapper qui
      exécute les deux opérations à l'intérieur d'une transaction Redis <code>MULTI/EXEC</code>, les rendant atomiques.
      Les opérations sont exécutées dans la même unité d'exécution, de sorte que la clé ne peut pas exister sans que
      son TTL soit appliqué. La clé a soit des données et un TTL, soit rien.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Chaque site d'appel qui émettait un <code>hset</code> suivi d'un <code>expire</code> sur la même clé a été converti.</p>
    </section>

    <section class="doc-section">
      <h2>Constat 2 &mdash; La déconnexion et l'invalidation CSRF pouvaient échouer silencieusement <span class="doc-badge high">High</span></h2>
      <p><strong>Catégorie : Redis / Déconnexion, CSRF</strong></p>
      <p>
        La méthode <code>Database::del()</code> — responsable de la suppression des clés Redis par
        modèle — énumérait les clés en utilisant le <em>réplica de lecture</em> puis émettait des
        commandes <code>DEL</code> vers le <em>primaire</em> :
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        La réplication Redis est asynchrone. Si le réplica est en retard — même de quelques
        millisecondes — il peut ne pas encore contenir la clé qui vient d'être écrite. Dans ce
        cas, <code>keys()</code> retourne une liste vide et aucun <code>DEL</code> n'est émis vers
        le primaire. La clé survit.
      </p>
      <p>Les deux appelants les plus critiques de <code>del()</code> :</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — déconnexion :</strong> Lorsqu'un utilisateur se
          déconnecte, nous supprimons sa clé de session. Si le réplica est en retard, la liste des
          clés de session retourne vide, la suppression ne se déclenche jamais, et la session continue
          d'exister sur le primaire. L'utilisateur croit être déconnecté. Il ne l'est pas.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — invalidation du nonce :</strong> Les tokens CSRF
          sont des nonces à usage unique. Après la première utilisation, ils doivent être supprimés.
          Si la suppression ne se déclenche jamais, le token peut être réutilisé dans une seconde
          requête. À usage unique devient réutilisable.
        </li>
      </ul>
      <p>
        Ce bug est subtil car il ne se manifeste que sous charge ou lors d'un décalage temporaire du
        réplica. En développement contre une instance Redis unique, il ne se déclenche jamais.
      </p>
      <p><strong>Le correctif :</strong> L'énumération et la suppression des clés doivent cibler la même instance.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 3 &mdash; Contournement de la vérification utilisateur WebAuthn <span class="doc-badge high">High</span></h2>
      <p><strong>Catégorie : Authentification</strong></p>
      <p>
        Dans <code>AccountRecoveryController</code>, lorsqu'une clé d'accès était enregistrée dans le
        cadre de la récupération de compte, l'appel <code>processCreate()</code> passait <code>false</code>
        pour <code>requireUserVerification</code> :
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    false,  // requireUserVerification — should be true
    true
);</code></pre>
      </div>
      <p>
        Le challenge émis au client spécifiait <code>userVerification: 'required'</code> — on
        demandait à l'authentificateur que l'utilisateur complète une vérification biométrique ou un
        PIN. Mais lors de la vérification de la réponse, nous disions à la bibliothèque de ne pas
        appliquer que le flag UV était défini.
      </p>
      <p>
        Un client modifié pourrait soumettre une réponse d'authentificateur avec le bit UV effacé.
        Notre serveur l'accepterait sans exiger que la vérification biométrique ait réellement eu lieu.
      </p>
      <p>
        Le flux de récupération de compte est le chemin qu'emprunte un utilisateur lorsqu'il a perdu
        l'accès à ses autres credentials. C'est la surface d'authentification la plus risquée que nous
        opérons. Affaiblir l'application biométrique ici est exactement le mauvais compromis.
      </p>
      <p><strong>Le correctif :</strong> UV est maintenant appliqué. Une réponse où les données d'authentificateur
      ne portent pas le flag UV défini est rejetée.</p>
      <div class="doc-code-block">
        <pre><code>// After — UV enforced
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    true,   // requireUserVerification — enforced
    true
);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 4 &mdash; La détection de clonage par compteur de signature ratait les attaques par rejeu <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Catégorie : Authentification</strong></p>
      <p>Notre détection de clonage de clé d'accès vérifiait :</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        La spécification WebAuthn Niveau 2 (§6.1) stipule : si le compteur de signature stocké est
        non nul et que le nouveau compteur de signature n'est pas <em>strictement supérieur</em> à
        la valeur stockée, le credential doit être considéré comme potentiellement cloné. Notre
        condition exigeait <code>&lt;</code>, pas <code>&lt;=</code>, donc un compteur égal — comme
        dans une attaque par rejeu — passait sans déclencher le flag de clonage.
      </p>
      <p><strong>Le correctif :</strong> Aligné sur la spécification.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 5 &mdash; Le compteur de signature n'était pas toujours persisté <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Catégorie : Authentification</strong></p>
      <p>Après une connexion réussie par clé d'accès, la mise à jour du compteur de signature était conditionnée à ce qu'il soit non nul :</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Certains authentificateurs retournent <code>0</code> comme sentinelle signifiant &laquo; cet
        appareil n'implémente pas de compteur. &raquo; Si un appareil commence plus tard à retourner
        un vrai compteur (mise à jour du firmware, ou si l'utilisateur enregistre le même credential
        sur une plateforme supportant les compteurs), nous n'aurions jamais persisté le premier vrai
        compteur car nous avions stocké <code>0</code> pour toujours.
      </p>
      <p>
        La détection de clonage (Constat 4) exige que le compteur stocké soit non nul — un
        authentificateur que nous tagons en permanence comme <code>0</code> est définitivement exclu
        de la protection basée sur les compteurs.
      </p>
      <p><strong>Le correctif :</strong> Le compteur de signature est toujours écrit. Le seuil de détection de clonage gère l'interprétation.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 6 &mdash; Une clé d'accès révoquée pouvait être ré-enregistrée <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Catégorie : Authentification</strong></p>
      <p>
        Lorsqu'un credential était marqué révoqué (détection de clonage déclenchée), il n'y avait
        pas de vérification dans le chemin d'enregistrement empêchant le ré-enregistrement du même
        <code>credential_id</code>. Un adversaire avec le credential de clé d'accès brut et un accès
        au compte pourrait ré-enregistrer le credential révoqué, effaçant son historique compromis.
      </p>
      <p>
        La révocation n'est significative que si elle est permanente. Si elle peut être écrasée par un
        ré-enregistrement utilisant le même credential, la détection de clonage ne fournit aucune
        protection durable.
      </p>
      <p><strong>Le correctif :</strong> Si <code>revoked_at</code> est non vide sur un enregistrement de credential existant,
      le ré-enregistrement est bloqué avec HTTP 403 et une entrée de journal de sécurité est écrite.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 7 &mdash; Énumération de comptes via des réponses d'erreur différentes <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Catégorie : Divulgation d'information</strong></p>
      <p>
        Lorsqu'une connexion par clé d'accès était tentée avec un e-mail non reconnu, le corps de
        la réponse d'erreur prenait une forme différente des autres cas d'échec — un payload de données
        vide <code>[]</code> contre le corps <code>{'error': 'passkey_invalid'}</code> retourné ailleurs.
        Un client sondant l'API pouvait distinguer &laquo; cet e-mail n'a pas de compte &raquo; de
        &laquo; cet e-mail existe mais le challenge a échoué &raquo; en inspectant le corps de la réponse.
      </p>
      <p>
        De plus, l'adresse e-mail brute était écrite dans le journal d'observabilité. Les pipelines
        d'agrégation de journaux ne devraient jamais contenir d'adresses e-mail d'utilisateurs brutes —
        si le système de journaux est compromis, chaque tentative d'énumération devient une liste
        d'adresses e-mail.
      </p>
      <p><strong>Le correctif :</strong> &laquo; E-mail introuvable &raquo; et &laquo; pas de credentials enregistrés &raquo;
      retournent maintenant le même corps d'erreur. Le journal d'observabilité enregistre uniquement un
      hash SHA-256 de l'e-mail — suffisant pour la corrélation d'incidents, insuffisant pour
      reconstruire l'adresse.</p>
      <div class="doc-code-block">
        <pre><code>// Before
Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

// After
Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 8 &mdash; État de la clé de récupération écrit avant confirmation de la livraison de l'e-mail <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Catégorie : Intégrité des données</strong></p>
      <p>
        Lors de la génération de clé de récupération de compte, le serveur écrivait
        <code>recovery_key_generated = 1</code> et <code>recovery_proof_key</code> dans l'enregistrement
        utilisateur <em>avant</em> d'envoyer l'e-mail de clé de récupération :
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — DB written first, email second
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
]);
$sent = EmailGarum::sendRecoveryKeyEmail(...);</code></pre>
      </div>
      <p>
        Si l'e-mail échouait à être envoyé, la base de données afficherait <code>recovery_key_generated = 1</code>
        — le système croit qu'une clé a été émise. L'utilisateur ne l'a jamais reçue.
      </p>
      <p>
        Il n'y a pas de chemin de régénération pour un utilisateur dans cet état. La récupération de
        compte est définitivement cassée pour ce compte jusqu'à une intervention manuelle.
      </p>
      <p><strong>Le correctif :</strong> La livraison de l'e-mail est confirmée en premier. L'état de la base de données reflète ce qui s'est réellement passé.</p>
      <div class="doc-code-block">
        <pre><code>// After — email first, then persist
$sent = EmailGarum::sendRecoveryKeyEmail(...);
if ($sent) {
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
    ]);
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Constat 9 &mdash; Le chemin d'inscription désactivé collectait encore les champs de mot de passe <span class="doc-badge low">Low</span></h2>
      <p><strong>Catégorie : Surface d'attaque</strong></p>
      <p>
        <code>RegistrationController</code> lisait encore <code>password</code> et
        <code>confirm_password</code> depuis POST même si l'inscription par mot de passe a été
        désactivée. L'inscription PayCal est par clé d'accès uniquement.
      </p>
      <p>
        Collecter des champs qui ne servent à rien n'est pas anodin. Chaque valeur lue depuis
        l'entrée utilisateur est une surface : elle peut être journalisée, auditée, transmise
        accidentellement à d'autres fonctions, ou incluse dans des payloads d'erreur. Le principe
        de surface minimale exige que nous ne collections pas ce que nous n'utilisons pas.
      </p>
      <p><strong>Le correctif :</strong> Les deux champs ont été supprimés de la carte de collecte d'entrée.</p>
    </section>

    <section class="doc-section">
      <h2>Constat 10 &mdash; E-mail utilisateur dans la réponse 403 de vérification d'e-mail <span class="doc-badge low">Low</span></h2>
      <p><strong>Catégorie : Divulgation d'information</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — le middleware appliquant la vérification d'e-mail avant
        d'accorder l'accès aux ressources protégées — incluait <code>user_email</code> dans le corps
        de la réponse 403 :
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Si un attaquant obtient un token de session valide mais non vérifié (par fixation de session
        ou un lien temporaire compromis), il peut apprendre l'adresse e-mail associée au compte depuis
        le corps de la réponse 403 — sans avoir fourni l'e-mail lui-même. La seule partie qui bénéficie
        de l'e-mail dans ce payload d'erreur est quelqu'un qui possède le token de session mais pas l'e-mail.
      </p>
      <p><strong>Le correctif :</strong> Le champ e-mail a été supprimé du payload d'erreur.</p>
    </section>

    <section class="doc-section">
      <h2>Constat 11 &mdash; Code mort dans <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Catégorie : Code mort / Surface d'attaque</strong></p>
      <p>
        <code>EmailGarum</code> contenait une méthode de 90 lignes, <code>verifyNewUserEmail()</code>,
        gérant un flux de changement d'e-mail par mot de passe. Ce flux a été remplacé lorsque la
        plateforme est passée à l'authentification par clé d'accès uniquement. La méthode n'était
        appelée nulle part dans la codebase.
      </p>
      <p>
        Le code mort n'est pas neutre. Il occupe de l'espace dans la surface de revue de sécurité,
        dans l'analyse statique, et dans la charge cognitive de quiconque lit le fichier. Il représente
        aussi un risque qu'un futur développeur, ignorant qu'il était intentionnellement abandonné,
        pourrait le relier à un nouveau flux sans contexte complet.
      </p>
      <p><strong>Le correctif :</strong> Supprimé. Tous les sites d'appel ont été confirmés vides avant la suppression.</p>
    </section>

    <section class="doc-section">
      <h2>Récapitulatif de tous les constats</h2>
      <table class="doc-table" aria-label="Récapitulatif de tous les constats">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Constat</th>
            <th scope="col">Gravité</th>
            <th scope="col">Catégorie</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td><code>hset + expire</code> non atomique sur 9 sites d'appel</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicité</td></tr>
          <tr><td>2</td><td><code>del()</code> utilisant le réplica de lecture pour l'énumération des clés</td><td><span class="doc-badge high">High</span></td><td>Redis / Déconnexion, CSRF</td></tr>
          <tr><td>3</td><td>Contournement UV WebAuthn dans l'enregistrement de récupération de compte</td><td><span class="doc-badge high">High</span></td><td>Authentification</td></tr>
          <tr><td>4</td><td>La détection de clonage par compteur de signature ratait les attaques par rejeu</td><td><span class="doc-badge medium">Medium</span></td><td>Authentification</td></tr>
          <tr><td>5</td><td>Compteur de signature non persisté quand l'authentificateur retourne zéro</td><td><span class="doc-badge medium">Medium</span></td><td>Authentification</td></tr>
          <tr><td>6</td><td>Une clé d'accès révoquée pouvait être ré-enregistrée</td><td><span class="doc-badge medium">Medium</span></td><td>Authentification</td></tr>
          <tr><td>7</td><td>Énumération de comptes via corps d'erreur + e-mail brut dans les journaux</td><td><span class="doc-badge medium">Medium</span></td><td>Divulgation d'information</td></tr>
          <tr><td>8</td><td>État DB de clé de récupération écrit avant confirmation de l'e-mail</td><td><span class="doc-badge medium">Medium</span></td><td>Intégrité des données</td></tr>
          <tr><td>9</td><td>Inscription désactivée collectant encore les champs de mot de passe</td><td><span class="doc-badge low">Low</span></td><td>Surface d'attaque</td></tr>
          <tr><td>10</td><td>E-mail utilisateur dans la réponse 403 de vérification d'e-mail</td><td><span class="doc-badge low">Low</span></td><td>Divulgation d'information</td></tr>
          <tr><td>11</td><td>Méthode morte <code>verifyNewUserEmail()</code> dans EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Code mort</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Ce que nous avons bien fait</h2>
      <p>Dans l'intérêt d'un tableau complet, les fondations déjà en place :</p>
      <ul class="doc-list">
        <li>
          <strong>Authentification par clé d'accès en premier.</strong> La plateforme fonctionne sur
          WebAuthn sans fallback par mot de passe pour les utilisateurs de clés d'accès. Le contournement
          UV et les problèmes de détection de clonage étaient des défauts au sein d'une architecture
          fondamentalement saine.
        </li>
        <li>
          <strong>Tokens de capacité à usage unique.</strong> Les mutations au niveau admin nécessitaient
          déjà des tokens frais et limités dans le temps. Le correctif d'atomicité a renforcé une
          protection existante plutôt qu'en ajouté une manquante.
        </li>
        <li>
          <strong>Journal de sécurité signé.</strong> Chaque événement de sécurité — y compris les nouveaux
          événements <code>passkey_revoked_reregistration_blocked</code> ajoutés dans ce commit — est
          écrit dans un journal signé, en ajout seulement, avec des champs structurés.
        </li>
        <li>
          <strong>PHPStan au Niveau 9.</strong> Les 11 fichiers modifiés ont été validés à la rigueur
          maximale d'analyse statique. La suite de régression complète a réussi sans régression.
        </li>
        <li>
          <strong>La détection de clonage existait.</strong> La logique était présente et partiellement
          correcte. Le Constat 4 était une erreur de condition aux limites, pas une fonctionnalité manquante.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Impact sur les clients</h2>
      <ul class="doc-list">
        <li><strong>Aucune preuve d'exploitation.</strong> Tous les constats ont été identifiés en interne lors d'une revue de code de routine. Aucun rapport externe, CVE ou incident n'a précédé cette divulgation.</li>
        <li><strong>Aucune exposition de credential en clair.</strong> Aucun mot de passe ou clé de récupération n'a été exposé. Les données de credential au repos restent chiffrées. Les données biométriques ne quittent jamais l'appareil authentificateur et ne sont jamais transmises à ni stockées par PayCal.</li>
        <li><strong>Aucune preuve d'accès non autorisé.</strong> Les journaux de sécurité ne montrent aucun schéma anormal cohérent avec l'exploitation de ces vecteurs.</li>
        <li><strong>Tous les constats remédiés avant la divulgation.</strong> Chaque problème décrit dans cet article a été corrigé, commis et testé avant la publication de cette page.</li>
        <li><strong>Suite de régression complète validée.</strong> Suite PHPUnit complète et analyse statique PHPStan Niveau 9 terminées proprement après remédiation.</li>
        <li><strong>Surveillance étendue.</strong> De nouveaux événements de journal de sécurité ont été ajoutés pour l'application de la révocation de clé d'accès (Constat 6) afin de détecter les anomalies futures plus tôt.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Prévention et contrôles de récidive</h2>
      <p>Deux règles d'ingénierie adoptées comme politique permanente à partir de cet audit :</p>
      <div class="subject-example-cutout" role="note" aria-label="Nouvelle règle d'ingénierie : hsetex comme modèle d'écriture Redis par défaut">
        <h3><code>hsetex</code> est le modèle d'écriture Redis par défaut</h3>
        <p>
          Tout futur code qui doit écrire un hash avec un TTL doit utiliser
          <code>Database::hsetex()</code>. L'ancien modèle en deux étapes n'est plus autorisé.
          Des règles PHPStan seront écrites pour signaler les nouvelles occurrences.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Nouvelle règle d'ingénierie : primauté de l'instance d'écriture pour toutes les opérations sur les clés">
        <h3>Primauté de l'instance d'écriture pour toutes les opérations sur les clés</h3>
        <p>
          Toute opération Redis dont la correction dépend de la relecture de ce qui vient d'être écrit
          doit utiliser l'instance d'écriture. Les réplicas de lecture sont réservés aux requêtes
          non critiques à forte lecture.
        </p>
      </div>
      <p>
        Les auto-audits à ce niveau de spécificité sont un engagement permanent. Nous continuerons
        à publier ce que nous trouvons. Les futurs rapports seront publiés sur le
        <a href="<?php echo transparency_href('/transparency/'); ?>">Centre de transparence</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Chronologie de la divulgation</h2>
      <table class="doc-table" aria-label="Chronologie de la divulgation">
        <thead>
          <tr>
            <th scope="col">Date</th>
            <th scope="col">Événement</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 mai 2026</time></td>
            <td>Constats identifiés lors d'une session d'audit interne de routine</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mai 2026</time></td>
            <td>Tous les correctifs implémentés et commis (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mai 2026</time></td>
            <td>Suite de régression PHPUnit complète validée, PHPStan Niveau 9 propre</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mai 2026</time></td>
            <td>Poussé vers origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 mai 2026</time></td>
            <td>Cet article de transparence publié</td>
          </tr>
        </tbody>
      </table>
      <p>
        Tous les constats ont été identifiés en interne. Aucun rapport externe, CVE ou violation n'a
        précédé cette divulgation. Il n'existe aucune preuve que l'un des constats ait été exploité.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
