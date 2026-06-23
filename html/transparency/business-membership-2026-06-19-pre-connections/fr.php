<?php
/**
 * Public Transparency: Business Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses a Business <-> Member relationship model,
 * how role changes are governed, and what architectural philosophy guides
 * capability, scope, and security decisions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Adhésion organisationnelle et philosophie des rôles</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Cette page explique le passage d'une sémantique d'équipe faiblement couplée à un modèle
      explicite de relation Organisation <strong>&lt;-&gt;</strong> Membre, la politique de rôle
      actuelle, et les principes que nous appliquons pour maintenir les permissions auditables et sécurisées.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Pourquoi ce modèle existe</h2>
      <p>
        La collaboration sur la paie a un impact réel sur la sécurité. Un modèle de rôle
        facile à lire, tester et auditer est plus sûr qu'un modèle construit à partir de
        vérifications ponctuelles dispersées.
      </p>
      <p>
        La structure Organisation <strong>&lt;-&gt;</strong> Membre donne à chaque acteur une relation
        explicite avec une organisation, avec un comportement d'état, de rôle et de portée
        pris en charge par la politique.
      </p>
    </section>

    <section class="doc-section">
      <h2>Changements dans la relation Organisation <strong>&lt;-&gt;</strong> Membre</h2>
      <ul class="doc-list">
        <li>L'adhésion est représentée comme une relation explicite plutôt qu'un état d'interface implicite.</li>
        <li>Les états du cycle de vie — demande d'accès, invitation, approbation, activation et révocation — sont appliqués par la politique backend.</li>
        <li>Les panneaux d'organisation et les notifications reflètent désormais les transitions de relation et les résultats de rôle de manière plus cohérente.</li>
        <li>Le comportement d'organisation partagé est régi par l'état de l'adhésion avant le traitement des actions privilégiées.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Changements de rôle et philosophie actuelle des rôles</h2>
      <p>
        Les rôles sont axés sur les capacités, avec des restrictions de portée appliquées par opération. Le référentiel actuel :
      </p>
      <ul class="doc-list">
        <li><strong>propriétaire :</strong> contrôle souverain incluant le transfert de propriété et les actions de gouvernance à haute confiance.</li>
        <li><strong>gestionnaire :</strong> contrôle opérationnel quotidien sans autorité de transfert de propriété.</li>
        <li><strong>contributeur :</strong> opérateur de confiance avec autorité d'écriture contrainte par la portée assignée.</li>
        <li><strong>membre :</strong> participation en libre-service limitée avec des droits de mutation restreints.</li>
        <li><strong>observateur :</strong> visibilité en lecture seule sans autorisations d'écriture.</li>
      </ul>
      <p>
        Nous privilégions la composition explicite de capacités et de portées plutôt que les indicateurs de rôle surchargés. Cela rend les résultats de rôle plus faciles à tester et à appréhender.
      </p>
    </section>

    <section class="doc-section">
      <h2>Philosophie de la sécurité et du chiffrement</h2>
      <p>
        La collaboration organisationnelle recoupule avec les contrôles de chiffrement et de consentement. Les vérifications
        d'adhésion et de rôle conditionnent le comportement de l'enveloppe organisationnelle partagée afin que les
        opérations sensibles restent liées à la politique.
      </p>
      <ul class="doc-list">
        <li>L'état de l'adhésion et du consentement est validé avant le traitement des opérations sécurisées partagées.</li>
        <li>Les changements de rôle et les transitions d'adhésion sont traités comme des événements pertinents pour la sécurité, pas seulement des événements UX.</li>
        <li>Les chemins de refus d'accès sont un comportement attendu en cas de non-correspondance de politique et sont exposés pour l'auditabilité.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Philosophie opérationnelle pour l'avenir</h2>
      <ul class="doc-list">
        <li><strong>Source de politique unique :</strong> les décisions de rôle et de portée doivent provenir de mappings de politique backend partagés.</li>
        <li><strong>L'IU comme projection :</strong> les interfaces doivent afficher les résultats de politique plutôt que dupliquer la logique d'autorisation.</li>
        <li><strong>Transitions traçables :</strong> les approbations, changements de rôle et révocations doivent rester observables et révisables.</li>
        <li><strong>Transparence des versions :</strong> les changements de comportement dans l'adhésion et les rôles sont documentés dans les journaux de modifications et les pages de transparence.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
