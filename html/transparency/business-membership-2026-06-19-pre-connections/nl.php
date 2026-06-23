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
    <span class="current">Organisatielidmaatschap en rolfilosofie</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Deze pagina legt de overgang uit van los gekoppelde groepssemantiek naar een expliciet
      Organisatie- <strong>&lt;-&gt;</strong>-Leden-relatiemodel, het huidige rolbeleid en de
      principes die we hanteren om rechten controleerbaar en veilig te houden.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Waarom dit model bestaat</h2>
      <p>
        Salarisadministratiesamenwerking heeft een reëel beveiligingseffect. Een rolmodel dat
        gemakkelijk te lezen, testen en controleren is, is veiliger dan een model gebouwd uit
        verspreide incidentele controles.
      </p>
      <p>
        De Organisatie- <strong>&lt;-&gt;</strong>-Leden-structuur geeft elke actor een expliciete
        relatie met een organisatie, met beleidsgestuurd status-, rol- en bereikgedrag.
      </p>
    </section>

    <section class="doc-section">
      <h2>Wijzigingen in de Organisatie- <strong>&lt;-&gt;</strong>-Leden-relatie</h2>
      <ul class="doc-list">
        <li>Lidmaatschap wordt weergegeven als een expliciete relatie in plaats van een impliciete UI-status.</li>
        <li>Levenscyclusstatussen — toegangsverzoek, uitnodiging, goedkeuring, activering en intrekking — worden afgedwongen door backend-beleid.</li>
        <li>Organisatiepanelen en meldingen weerspiegelen nu relatietransities en roluitkomsten consistenter.</li>
        <li>Gedeeld organisatiegedrag wordt geregeld door de lidmaatschapsstatus voordat bevoorrechte acties worden verwerkt.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Rolwijzigingen en huidige rolfilosofie</h2>
      <p>
        Rollen zijn capaciteitsgedreven, met bereikbeperkingen per operatie toegepast. De huidige basislijn:
      </p>
      <ul class="doc-list">
        <li><strong>eigenaar:</strong> soevereine controle inclusief eigendomsoverdracht en bestuursacties met hoog vertrouwen.</li>
        <li><strong>manager:</strong> dagelijkse operationele controle zonder eigendomsoverdrachtsbevoegdheid.</li>
        <li><strong>bijdrager:</strong> vertrouwde operator met schrijfbevoegdheid beperkt door het toegewezen bereik.</li>
        <li><strong>lid:</strong> beperkte zelfbediening met beperkte mutatierechten.</li>
        <li><strong>waarnemer:</strong> alleen-lezenzichtbaarheid zonder schrijfrechten.</li>
      </ul>
      <p>
        We geven de voorkeur aan expliciete capaciteits- en bereiksamenstellingen boven overladen rolmarkeringen. Dit maakt roluitkomsten gemakkelijker te testen en te redeneren.
      </p>
    </section>

    <section class="doc-section">
      <h2>Beveiligings- en versleutelingsfilosofie</h2>
      <p>
        Organisatiesamenwerking kruist met versleutelings- en toestemmingscontroles. Lidmaatschaps-
        en rolcontroles bewaken gedeeld organisatie-envelop-gedrag zodat gevoelige operaties
        beleidsgebonden blijven.
      </p>
      <ul class="doc-list">
        <li>Lidmaatschaps- en toestemmingsstatus worden gevalideerd voordat gedeelde beveiligde operaties doorgaan.</li>
        <li>Rolwijzigingen en lidmaatschapstransities worden behandeld als beveiligingsrelevante gebeurtenissen, niet alleen UX-gebeurtenissen.</li>
        <li>Toegangsweigerpaden zijn verwacht gedrag bij beleidsmismatch en worden zichtbaar gemaakt voor controleerbaarheid.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Operationele filosofie voor de toekomst</h2>
      <ul class="doc-list">
        <li><strong>Één beleidsbron:</strong> rol- en bereikbeslissingen moeten afkomstig zijn van gedeelde backend-beleidskaarten.</li>
        <li><strong>UI als projectie:</strong> interfaces moeten beleidsuitkomsten weergeven in plaats van autorisatielogica dupliceren.</li>
        <li><strong>Traceerbare overgangen:</strong> goedkeuringen, rolwijzigingen en intrekkingen moeten observeerbaar en herzienbaar blijven.</li>
        <li><strong>Versietransparantie:</strong> gedragswijzigingen in lidmaatschap en rollen worden gedocumenteerd in changelogs en transparantiepagina's.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
