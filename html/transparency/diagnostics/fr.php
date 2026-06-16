<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Diagnostics optionnels &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal comprend une couche de diagnostics optionnelle que vous contrôlez. Voici exactement
      ce qu'elle collecte, ce qui reste sur votre appareil et comment elle est utilisée.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Vue d'ensemble</h2>
      <p>
        PayCal est livré avec une couche de diagnostics intégrée appelée <strong>Phantom Wing</strong>.
        Par défaut, elle est presque entièrement silencieuse — elle ne capture que les erreurs
        graves et non gérées et n'envoie jamais rien sans votre activation explicite.
      </p>
      <p>
        Si vous rencontrez un problème et souhaitez partager davantage de contexte avec le support,
        vous pouvez activer des diagnostics supplémentaires dans
        <a href="/settings/diagnostics/">Paramètres → Débogage (Optionnel)</a>.
        Chaque paramètre est indépendant ; vous pouvez activer uniquement celui qui est pertinent.
        Les trois sont <strong>Désactivés</strong> par défaut.
      </p>
    </section>

    <section class="doc-section">
      <h2>Les trois contrôles d'activation volontaire</h2>
      <p>
        Chaque contrôle se trouve dans le panneau <strong>Débogage (Optionnel)</strong> en bas de
        votre page Paramètres. Ils sont conçus uniquement pour le dépannage — les activer peut
        légèrement ralentir les interactions de page car un travail supplémentaire s'effectue dans
        le navigateur.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Paramètre</th>
            <th>Ce qu'il active</th>
            <th>Qui le voit</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Messages de console</strong></td>
            <td>
              Émet des avertissements, des journaux d'information et des marqueurs de performance
              dans la console développeur de votre navigateur. Utile pour l'auto-diagnostic —
              ouvrez les DevTools et recherchez les messages préfixés par <code>[PayCal]</code>
              ou des marqueurs emoji.
            </td>
            <td>Vous uniquement (votre console navigateur, jamais transmis)</td>
          </tr>
          <tr>
            <td><strong>Diagnostics détaillés</strong></td>
            <td>
              Active la journalisation interne étape par étape des événements. Phantom Wing capture
              le cycle de vie complet des opérations (chargements de calendrier, soumissions de
              formulaires, événements de session) dans un journal en mémoire inclus dans tout
              rapport de support que vous choisissez de partager.
            </td>
            <td>Vous uniquement, sauf si vous partagez un rapport de support</td>
          </tr>
          <tr>
            <td><strong>Aperçus réseau</strong></td>
            <td>
              Journalise les délais des requêtes API — la durée de chaque aller-retour serveur,
              les tailles de réponse et si le regroupement ou la mise en cache a été appliqué.
              Aide à diagnostiquer la lenteur sur des opérations spécifiques.
            </td>
            <td>Vous uniquement (votre console navigateur, jamais transmis)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Ce que Phantom Wing fait par défaut</h2>
      <p>
        Même avec les trois contrôles désactivés, Phantom Wing exécute un moniteur de base
        léger qui ne capture que les défaillances graves :
      </p>
      <ul class="doc-list">
        <li>Exceptions JavaScript non interceptées (<code>window.onerror</code>)</li>
        <li>Rejets de promesse non gérés</li>
        <li>Appels Fetch qui échouent avec une erreur réseau (pas les erreurs HTTP — celles-ci sont gérées par fonctionnalité)</li>
      </ul>
      <p>
        Ces données de base restent entièrement en mémoire et ne sont jamais transmises nulle part.
        Elles sont affichées dans un résumé d'une seconde dans la console du navigateur au chargement
        de la page pour vous permettre de voir rapidement si quelque chose a mal tourné, puis elles
        sont supprimées.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Télémétrie</h2>
      <p>
        Phantom Wing dispose d'un canal de télémétrie léger utilisé pour mesurer la fiabilité
        des fonctionnalités de manière agrégée — par exemple, détecter si une opération
        particulière échoue à un taux inhabituel sur la plateforme.
      </p>
      <h3>Ce que la télémétrie envoie</h3>
      <ul class="doc-list">
        <li>Comptages d'événements anonymisés par heure (ex. : <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Catégorie et type d'erreur — jamais le message d'erreur complet ni la trace de pile</li>
        <li>Aucun identifiant utilisateur, aucun jeton de session, aucune adresse IP</li>
      </ul>
      <h3>Ce que la télémétrie n'envoie jamais</h3>
      <ul class="doc-list">
        <li>Votre nom, e-mail ou tout détail de compte</li>
        <li>Revenus, période de paie ou données financières</li>
        <li>Messages d'erreur complets ou traces de pile</li>
        <li>Chemins d'URL ou chaînes de requête</li>
        <li>Frappes au clavier ou valeurs de champs de formulaire</li>
      </ul>
      <h3>Limitation de débit &amp; repli</h3>
      <p>
        Les soumissions de télémétrie sont limitées côté serveur par utilisateur par minute. Si
        votre client dépasse le seuil, le serveur acquitte silencieusement et supprime l'excédent
        — rien n'est stocké. Le client applique également un repli exponentiel : après deux échecs
        consécutifs côté serveur, il désactive automatiquement la soumission de télémétrie pendant
        dix minutes.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Expurgation des données</h2>
      <p>
        Avant qu'une valeur soit stockée en mémoire ou transmise via la télémétrie, Phantom Wing
        applique un passage d'expurgation automatique. Les valeurs correspondant à des modèles
        sensibles connus sont remplacées par <code>[REDACTED]</code> :
      </p>
      <ul class="doc-list">
        <li>Adresses e-mail</li>
        <li>Jetons Bearer et valeurs d'en-tête d'autorisation</li>
        <li>Jetons CSRF</li>
        <li>Chaînes qui ressemblent à des clés cryptographiques ou des blobs encodés en base64 dépassant une longueur minimale</li>
      </ul>
      <p>
        L'expurgation s'applique à tous les arguments passés aux méthodes console interceptées et
        à toutes les valeurs de champs de télémétrie avant la mise en file d'attente. Elle ne peut
        pas être contournée par l'activation des paramètres de diagnostic.
      </p>
    </section>

    <section class="doc-section">
      <h2>Gardes de portée : pages où les diagnostics sont supprimés</h2>
      <p>
        La soumission de télémétrie est complètement supprimée sur les pages d'authentification
        (<code>/auth/</code>). Cela signifie que même si les Aperçus réseau sont activés, aucune
        télémétrie n'est transmise pendant que vous vous trouvez sur les flux de connexion,
        d'inscription ou de récupération. Il s'agit d'une mesure de défense en profondeur pour
        empêcher toute possibilité que des données adjacentes aux identifiants apparaissent dans
        les canaux de diagnostic.
      </p>
    </section>

    <section class="doc-section">
      <h2>Votre contrôle</h2>
      <p>
        Les trois paramètres de diagnostic sont stockés comme préférences de compte, pas comme
        cookies de navigateur. Ils suivent votre compte sur tous les appareils et sessions et
        sont <strong>Désactivés</strong> par défaut pour chaque compte — y compris les nouveaux.
        Vous pouvez les modifier à tout moment dans
        <a href="/settings/diagnostics/">Paramètres → Débogage (Optionnel)</a>.
      </p>
      <p>
        La désactivation d'un paramètre prend effet immédiatement au prochain chargement de page.
        Aucune donnée de diagnostic n'est conservée entre les sessions : le journal en mémoire
        de Phantom Wing est effacé lorsque vous naviguez ailleurs ou fermez l'onglet.
      </p>
    </section>

    <section class="doc-section">
      <h2>Résumé</h2>
      <ol class="doc-list">
        <li>Les trois contrôles de débogage sont <strong>Désactivés</strong> par défaut et doivent être explicitement activés par vous</li>
        <li>Les Messages de console et les Aperçus réseau ne quittent jamais votre appareil</li>
        <li>Les Diagnostics détaillés restent en mémoire et ne sont partagés que si vous choisissez de partager un rapport de support</li>
        <li>La télémétrie n'envoie que des comptages d'événements anonymisés et agrégés — zéro donnée personnelle</li>
        <li>Toutes les valeurs sont expurgées avant stockage ou transmission, quels que soient les paramètres de diagnostic</li>
        <li>La télémétrie est entièrement supprimée sur toutes les pages d'authentification</li>
        <li>La limitation de débit et le repli automatique du client empêchent tout sur-signalement accidentel</li>
      </ol>
      <p class="doc-section-footer-note">
        Phantom Wing est conçu pour que vous puissiez laisser tous les diagnostics désactivés
        indéfiniment. Les contrôles d'activation existent pour donner à vous et à l'équipe de
        support un langage commun lorsque quelque chose tourne mal — pas pour collecter des
        données par défaut.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
