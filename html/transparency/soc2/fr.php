<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SOC2_PAGE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'];


require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Centre de transparence</a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">Une vue technique de la façon dont PayCal associe les contrôles SOC 2 aux comportements système appliqués et aux preuves générées en continu.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Vue d'ensemble</h2>
      <p>PayCal exploite un programme de sécurité orienté SOC 2 axé sur l'application vérifiable et les preuves traçables, soumis à la validation indépendante d'un auditeur.</p>
      <ul class="doc-fact-list">
        <li><strong>Contrôles dans le périmètre :</strong> CC1-CC9</li>
        <li><strong>Artefacts dans le bundle actuel :</strong> 37</li>
        <li><strong>Mappages contrôle-artefact :</strong> 26</li>
        <li><strong>Fenêtre de fraîcheur des preuves :</strong> 35 jours</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Couverture des contrôles (CC1-CC9)</h2>
      <p>Tous les contrôles SOC 2 Common Criteria dans le périmètre (CC1 à CC9) sont associés aux preuves conservées dans le bundle mensuel.</p>
      <p>Ce mappage permet une traçabilité directe de l'objectif de contrôle aux artefacts concrets utilisés pour la révision.</p>
    </section>

    <section class="doc-section">
      <h2>3. Comment les contrôles sont appliqués</h2>
      <p>PayCal traite l'application comme une propriété système. Les contrôles sont appliqués de manière programmatique, pas seulement documentés.</p>
      <ul class="doc-fact-list">
        <li><strong>Authentification :</strong> Flux d'authentification avec prise en charge des clés d'accès pour renforcer la résistance au phishing.</li>
        <li><strong>Intégrité en exécution :</strong> Surveillance de l'intégrité en exécution avec gestion de l'état opérationnel.</li>
        <li><strong>Durcissement des sorties :</strong> Contrôles de désinfection Guardian pour les chemins DOM/sortie sensibles.</li>
        <li><strong>Barrière qualité :</strong> Barrière PHPUnit de suite complète automatisée avant que les preuves du bundle ne soient acceptées.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Gestion des changements &amp; Tests</h2>
      <p>La gouvernance des changements est alignée sur CC8 avec des changements suivis, des approbations et des preuves de test.</p>
      <ul class="doc-fact-list">
        <li><strong>Enregistrements de changements :</strong> 12</li>
        <li><strong>Enregistrements d'approbation :</strong> 10</li>
        <li><strong>Résultats des tests :</strong> 1528 tests, 8351 assertions (réussis)</li>
        <li><strong>Traçabilité test-contrôle :</strong> 5 suites, 5 réussies, 8 fichiers de test liés</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Piste d'audit &amp; Intégrité des preuves</h2>
      <p>Les événements d'exécution administratifs et liés à la sécurité sont exportés avec une validation par registre immuable pour les contrôles d'intégrité.</p>
      <p><strong>Statut actuel d'intégrité du registre :</strong> RÉUSSI.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Surveillance continue &amp; Fraîcheur</h2>
      <p>Les exports de preuves fonctionnent en continu et sont validés par rapport à une politique de fraîcheur déterministe.</p>
      <p><strong>Résultat actuel de fraîcheur :</strong> tous les artefacts mappés sont dans la fenêtre d'audit de 35 jours.</p>
    </section>

    <section class="doc-section">
      <h2>7. Statut actuel</h2>
      <p><strong>Statut :</strong> Préparation SOC 2 en cours, avec durcissement continu des contrôles et mises à jour déterministes des preuves.</p>
      <p>PayCal ne revendique pas de certification SOC 2 ni d'avis d'auditeur sur cette page. L'accès au rapport formel reste conditionné par un NDA.</p>
    </section>

    <section class="doc-section">
      <h2>Extraits de conformité réutilisables</h2>
      <p><strong>Badge de pied de page :</strong> Préparation en cours • Contrôles mappés • Surveillance continue des preuves</p>
      <p><strong>Bloc de résumé :</strong> CC1-CC9 mappés, 37 artefacts, 26 liens de contrôle, intégrité du registre réussie et preuves de test de suite complète automatisée.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Références</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Résumé de contrôle public épuré, narratifs déterministes et chemin de contact sécurité.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">Résumé SOC 2 PayCal</a>
          <span class="doc-ref-desc">Statut, métriques et accès NDA pour ce rapport.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Demander le rapport SOC 2 (NDA)</a>
          <span class="doc-ref-desc">Accès conditionné pour les revues de due diligence fournisseurs et sécurité.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Norme officielle</a>
          <span class="doc-ref-desc">Le cadre faisant autorité définissant les critères SOC 2.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Aperçu de l'historique et du périmètre des contrôles système et organisationnels.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Communauté Reddit</a>
          <span class="doc-ref-desc">Discussion de praticiens sur les audits SOC 2 et la préparation.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
