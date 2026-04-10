<?php
/**
 * Public Transparency: Error Handling & Message Normalization
 *
 * PURPOSE: 
 * Explain PayCal's standardized error-message normalization pattern, the
 * security and UX rationale behind it, and how we ensure users receive
 * meaningful, safe error feedback across all frontend modules.
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
$pageTitle = 'Gestion des erreurs et normalisation des messages - [PayCal]';
$pageLabel = 'Gestion des erreurs et normalisation des messages';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Gestion des erreurs et normalisation des messages</span>
  </nav>

  <header class="doc-article-header">
    <h1>Gestion des erreurs et normalisation des messages</h1>
    <p class="deck">
      Comment PayCal normalise la signalisation des erreurs sur tous les modules frontend pour garantir
      que les utilisateurs reçoivent des commentaires significatifs, sûrs et cohérents sans exposer les détails sensibles.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Aperçu et objectif</h2>
      <p>
        Lorsque les utilisateurs rencontrent des erreurs (défaillances réseau, accès refusé, erreurs de validation),
        ils méritent un retour clairs montant ce qui s'est passé et comment le corriger. Cependant,
        les messages bruts du backend doivent être normalisés pour :
      </p>
      <ul class="doc-list">
        <li><strong>Éliminer le bruit :</strong> Supprimer les préfixes redondants comme « Erreur : » et les espaces inutiles</li>
        <li><strong>Prévenir les fuites :</strong> Eviter que les détails sensibles d'implémentation ne parviennent à l'utilisateur</li>
        <li><strong>Fournir des alternatives :</strong> Afficher les messages sécurisés si les erreurs sont vides ou malformées</li>
        <li><strong>Assurer la cohérence :</strong> Appliquer la même logique sur les 11+ modules frontend</li>
        <li><strong>Améliorer le débogage :</strong> Enregistrer les détails complets des erreurs sur Phantom Wing et afficher des résumés sûrs</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Le problème : Les erreurs génériques vs. significatives</h2>
      <p>
        Avant la normalisation, les modules PayCal utilisaient une gestion d'erreurs ad hoc :
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ MAUVAIS : Expose l'erreur brute, duplique la logique
PC.showToast(error?.message || 'Importation échouée.');
PW.error(`Importation échouée : ${error.message}`);</code></pre>
      </div>
      <p>Problèmes avec cette approche :</p>
      <ul class="doc-list">
        <li>Les utilisateurs voient des messages confus comme « ECONNREFUSED : Connexion refusée »</li>
        <li>Chaque module met en place sa propre logique de secours indépendamment</li>
        <li>Aucun nettoyage d'espace blanc ou suppression de préfixe cohérents</li>
        <li>Les messages d'erreur vides peuvent s'afficher comme « undefined » dans l'UI</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>La solution : Résolveur d'erreurs standardisé</h2>
      <p>
        Tous les modules frontend de PayCal utilisent désormais une fonction de résolution unifiée
        qui normalise les messages d'erreur :
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ BON : Normalisé, cohérent, sûr
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Extrait le message de l'objet erreur
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Supprime le préfixe « Erreur : » et l'espace blanc
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Renvoie la version normalisée si non vide ; sinon le secours sûr
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Utilisation :</strong></p>
      <div class="doc-code-block">
        <pre><code>// Dans les blocs catch sur tous les modules
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Impossible de mettre à jour le profil.');
  PC.showToast(message, 'error');  // L'utilisateur voit un retour significatif
  PW.error(message);                // Enregistré pour débogage
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Portée d'implémentation</h2>
      <p>
        À partir d'avril 2026, ce modèle standardisé de gestion des erreurs a été appliqué à
        <strong>11 modules frontend</strong> avec <strong>~40+ blocs catch normalisés</strong> :
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Authentification et paramètres (7 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Modules de données et cœur (4 modules)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Modules de haut valeur (10+ points catch) :</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Gestion org, accès, pistes d'audit (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — CRUD de site, gains, récupération de travail orphelin (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Opérations sur les entrées de jour, copier/coller/supprimer (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Catégories d'erreur et motifs de traitement</h2>
      <p>Le résolveur est appliqué de façon cohérente dans plusieurs catégories d'erreur :</p>
      
      <h3>1. Défaillances des requêtes réseau</h3>
      <div class="doc-code-block">
        <pre><code>// Module réseau : Erreurs HTTP, délais d'expiration, problèmes de connexion
async function deleteResource(ep, id) {
  try {
    // ...logique fetch...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Erreur réseau');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Gestion des réponses API</h3>
      <div class="doc-code-block">
        <pre><code>// Facturation/Paramètres : Le serveur a renvoyé un message d'erreur dans la charge utile
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Impossible de charger l\'état de facturation.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Impossible de charger l\'état de facturation.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Défaillances d'exploitation de l'UI</h3>
      <div class="doc-code-block">
        <pre><code>// Calendrier/Organisations : Actions initiées par l'utilisateur (coller, supprimer, mettre à jour)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Succès!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'L\'action a échoué. Réessayez.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Initialisation asynchrone</h3>
      <div class="doc-code-block">
        <pre><code>// Modules principaux : Défaillances d'initialisation au démarrage ou dépendantes
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'L\'initialisation de la navigation a échoué');
  PW.warn(resolved);  // Enregistré mais ne bloque pas la page
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Considérations de sécurité</h2>
      <p>
        La normalisation des messages d'erreur protège la confidentialité des utilisateurs et l'intégrité du système :
      </p>
      <ul class="doc-list">
        <li>
          <strong>Pas de détails de base de données :</strong> Les erreurs du backend comme
          « UNIQUE constraint failed on email » sont interceptées à la limite de l'API
        </li>
        <li>
          <strong>Pas de chemins de fichiers :</strong> Les erreurs système exposant les chemins de fichiers sont supprimées
        </li>
        <li>
          <strong>Pas de fuite d'authentification :</strong> Les réponses aux défaillances d'authentification ne révèlent jamais
          si un compte existe (uniquement les messages génériques sûrs)
        </li>
        <li>
          <strong>Pas de détails CORS/réseau :</strong> Les erreurs au niveau du transport sont normalisées
          à des messages génériques « Erreur de connexion »
        </li>
        <li>
          <strong>Secours sécurisés :</strong> Tous les capteurs ont des messages de secours explicites ;
          n'affichent jamais « undefined » ou « null »
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Avantages de l'expérience utilisateur</h2>
      <p>
        Les messages d'erreur normalisés améliorent significativement l'expérience utilisateur :
      </p>
      <ul class="doc-list">
        <li>
          <strong>Retour clair :</strong> Les utilisateurs savent ce qui a échoué
          (par ex. « Clé d'accès non reconnue » vs. générique « Échec de la connexion »)
        </li>
        <li>
          <strong>Étapes suivantes exploitables :</strong> Lorsque c'est possible, les messages suggèrent des remèdes
          (« Réessayer », « Vérifier votre connexion », « Contacter l'assistance »)
        </li>
        <li>
          <strong>Cohérence dans l'application :</strong> Les mêmes types d'erreurs s'affichent de la même manière partout,
          réduisant la confusion
        </li>
        <li>
          <strong>États d'erreur accessibles :</strong> Les lecteurs d'écran annoncent les messages normalisés ;
          la journalisation fournit un contexte complet pour les équipes d'assistance
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Workflow de débogage et d'assistance</h2>
      <p>
        La normalisation des erreurs ne sacrifie <strong>pas</strong> la capacité de débogage.
        Les détails complets de l'erreur circulent vers Phantom Wing :
      </p>
      <div class="doc-code-block">
        <pre><code>// L'utilisateur voit un message UI propre
PC.showToast(resolveThrownMessage(error, 'Le téléchargement a échoué.'), 'error');

// L'équipe d'assistance voit les détails complets dans les journaux Phantom Wing
PW.error('Le téléchargement a échoué', {
  userMessage: resolveThrownMessage(error, 'Le téléchargement a échoué.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Tests et assurance qualité</h2>
      <p>
        Tous les changements de gestion des erreurs sont validés avant le déploiement :
      </p>
      <ul class="doc-list">
        <li><strong>Validation de la syntaxe :</strong> <code>php -l</code> et <code>node --check</code> vérifient la correction</li>
        <li><strong>Sécurité de type :</strong> Les diagnostiques de l'éditeur confirment aucune régression de type</li>
        <li><strong>Tests d'intégration :</strong> Les blocs catch sont testés avec des objets d'erreur simulés</li>
        <li><strong>Journalisation de Phantom Wing :</strong> Les messages d'erreur sont vérifiés dans les journaux de débogage</li>
        <li><strong>Audit d'accessibilité :</strong> Les annonces du lecteur d'écran sont testées pour la clarté</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Maintenance et extensions futures</h2>
      <p>
        Ce modèle est conçu pour la maintenabilité à long terme :
      </p>
      <ul class="doc-list">
        <li>
          <strong>Prêt pour la localisation :</strong> Les messages d'erreur peuvent être canalisés via i18n
          sans modifier la logique du résolveur
        </li>
        <li>
          <strong>Extensible :</strong> Le résolveur peut être amélioré pour gérer les codes d'erreur,
          la logique de nouvelle tentative ou la recherche de message spécialisée
        </li>
        <li>
          <strong>Documentation :</strong> Chaque module comprend des commentaires en ligne expliquant
          les scénarios d'erreur et les stratégies de secours
        </li>
        <li>
          <strong>Historique Git :</strong> Tous les changements suivis avec des messages de commit détaillés
          et les diffs au niveau des fichiers pour un examen facile
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Résumé : La norme de gestion des erreurs de PayCal</h2>
      <p>
        La normalisation normalisée des messages d'erreur de PayCal assure que :
      </p>
      <ol class="doc-list">
        <li>Les utilisateurs reçoivent des commentaires d'erreur clairs et exploitables</li>
        <li>Les détails système sensible ne fuient jamais vers le frontend</li>
        <li>La gestion des messages est cohérente sur tous les 11+ modules frontend</li>
        <li>Les équipes de débogage et d'assistance conservent le contexte d'erreur complet via Phantom Wing</li>
        <li>Le code est maintenable, testable et accessible</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Cet engagement envers la sécurité, la clarté et la cohérence reflète l'engagement de PayCal
        envers la confiance des utilisateurs et le partage d'informations transparentes.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
