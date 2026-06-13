<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_EXTENSIONS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_EXTENSIONS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_EXTENSIONS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Paradigme des extensions</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_EXTENSIONS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal est conçu de manière à ce que la logique métier fondamentale reste stable pendant
      que les couches d'extension peuvent adapter les fonctionnalités pour différents déploiements
      et stratégies de produit.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Architecture « Core en premier »</h2>
      <p>
        <strong>PayCal Core</strong> contient la logique de domaine et de contrôleur canonique :
        calculs, validation, permissions, politique de cycle de vie et contrats d'API partagés.
      </p>
      <p>
        Le Core reste indépendant des extensions par conception. Les points d'intégration sont isolés
        par des contrats de pont afin que les services de base puissent être testés indépendamment
        des packages spécifiques à l'exécution.
      </p>
    </section>

    <section class="doc-section">
      <h2>Extensions de base incluses dans ce dépôt</h2>
      <p>
        Ce dépôt fournit des <strong>implémentations d'extensions de base</strong> qui offrent
        un comportement par défaut pour les points d'extension. Elles servent de packages de
        référence publics et de valeurs sûres par défaut pour les déploiements auto-hébergés.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider :</strong> hooks de capacité de facturation de base et sélection de mode</li>
        <li><strong>earnings-ytd :</strong> rendu YTD de base et points de hook de gains</li>
        <li><strong>business-signals :</strong> hooks de signal d'entreprise de base</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Modèle d'extension tiers</h2>
      <p>
        Les tiers utilisant ce dépôt peuvent créer et maintenir leurs propres packages d'extension.
        Le modèle recommandé est :
      </p>
      <ol class="doc-list">
        <li>Conserver la logique du Core non modifiée autant que possible</li>
        <li>Implémenter un comportement personnalisé dans les packages d'extension</li>
        <li>Lier les packages personnalisés via le bootstrap d'extension et les points de hook documentés</li>
        <li>Préserver les contrats du Core afin que les mises à niveau en amont restent gérables</li>
      </ol>
      <p>
        Cela permet des déploiements compétitifs et spécifiques à une verticale sans forcer
        des forks à long terme du code de domaine central.
      </p>
    </section>

    <section class="doc-section">
      <h2>Différenciation de la plateforme canonique paycal.app</h2>
      <p>
        La plateforme canonique <code>https://paycal.app</code> exécute des <strong>variantes
        d'extension privées</strong> au-dessus du même Core et paradigme d'extensions de base.
      </p>
      <p>
        Ces variantes privées constituent une couche de différenciation de produit délibérée
        pour les environnements opérés par PayCal. Elles peuvent ajuster les flux de travail,
        le comportement des capacités et les intégrations spécifiques à l'UI tout en préservant
        la compatibilité avec la même architecture centrale.
      </p>
      <ul class="doc-list">
        <li>La logique du Core reste partagée et auditable</li>
        <li>Les extensions publiques/de base restent disponibles dans le dépôt</li>
        <li>Les extensions privées assurent la différenciation de la plateforme canonique</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Engagements de transparence</h2>
      <ul class="doc-list">
        <li>Les contrats du Core sont documentés et testés aux points d'extension</li>
        <li>Les limites de pont sont explicites pour rendre le couplage découvrable</li>
        <li>Le comportement des extensions peut évoluer sans déstabiliser les services de base</li>
        <li>Les adoptants auto-hébergés sont libres de construire des stratégies d'extension alternatives</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
