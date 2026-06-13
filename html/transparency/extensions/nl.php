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
    <span class="current">Extensieparadigma</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_EXTENSIONS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal is zo ontworpen dat de centrale bedrijfslogica stabiel blijft terwijl extensielagen
      functies kunnen aanpassen voor verschillende implementaties en productstrategieën.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Core-first-architectuur</h2>
      <p>
        <strong>PayCal Core</strong> bevat de canonieke domein- en controllerlogica:
        berekeningen, validatie, machtigingen, levenscyclusbeleid en gedeelde API-contracten.
      </p>
      <p>
        Core blijft ontwerpsgewijs extensie-agnostisch. Integratiepunten worden geïsoleerd via
        bridgecontracten zodat Core-services onafhankelijk van runtime-specifieke pakketten
        getest kunnen worden.
      </p>
    </section>

    <section class="doc-section">
      <h2>Basisextensies in dit opslagplaats</h2>
      <p>
        Dit opslagplaats bevat <strong>basisextensie-implementaties</strong> die standaardgedrag
        bieden voor extensiepunten. Ze fungeren als publieke referentiepakketten en veilige
        standaarden voor zelfgehoste implementaties.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> basisfacturatie-capaciteitshooks en modusselectie</li>
        <li><strong>earnings-ytd:</strong> basis YTD-rendering en inkomsten-hookpunten</li>
        <li><strong>business-signals:</strong> basisbedrijfssignaal-hooks</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Extern extensiemodel</h2>
      <p>
        Externe partijen die dit opslagplaats gebruiken kunnen hun eigen extensiepakketten
        aanmaken en onderhouden. Het aanbevolen model is:
      </p>
      <ol class="doc-list">
        <li>Houd Core-logica waar mogelijk ongewijzigd</li>
        <li>Implementeer aangepast gedrag in extensiepakketten</li>
        <li>Koppel aangepaste pakketten via de gedocumenteerde extensie-bootstrap en hookpunten</li>
        <li>Bewaar Core-contracten zodat upstream-upgrades beheersbaar blijven</li>
      </ol>
      <p>
        Dit maakt concurrerende en verticaalspecifieke implementaties mogelijk zonder
        langdurige forks van de centrale domeincode te forceren.
      </p>
    </section>

    <section class="doc-section">
      <h2>Differentiatie van het canonieke paycal.app-platform</h2>
      <p>
        Het canonieke <code>https://paycal.app</code>-platform draait <strong>private
        extensievarianten</strong> bovenop dezelfde Core en het basisextensie-paradigma.
      </p>
      <p>
        Deze private varianten vormen een bewuste productdifferentiatielaag voor door PayCal
        beheerde omgevingen. Ze kunnen workflows, capaciteitsgedrag en UI-specifieke integraties
        aanpassen terwijl compatibiliteit met dezelfde kernarchitectuur behouden blijft.
      </p>
      <ul class="doc-list">
        <li>Core-logica blijft gedeeld en controleerbaar</li>
        <li>Publieke/basisextensies blijven beschikbaar in het opslagplaats</li>
        <li>Private extensies bieden de differentiatie van het canonieke platform</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Transparantieverbintenissen</h2>
      <ul class="doc-list">
        <li>Core-contracten zijn gedocumenteerd en getest bij extensiepunten</li>
        <li>Bridgegrenzen zijn expliciet om koppeling vindbaar te maken</li>
        <li>Extensiegedrag kan evolueren zonder Core-services te destabiliseren</li>
        <li>Zelfhosters zijn vrij om alternatieve extensiestrategieën te bouwen</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
