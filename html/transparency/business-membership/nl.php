<?php
/**
 * Public Transparency: Business Connections and Role Philosophy
 *
 * PURPOSE:
 * Explain how PayCal separates business connections, active membership,
 * role changes, consent, and explicit access grants.
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
    <span class="current">Zakelijke verbindingen en rolfilosofie</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Deze pagina legt de overgang uit van los gekoppelde groepssemantiek naar expliciete
      verbindingen. Een verbinding zegt wie met wie verbonden is. Lidmaatschap, rol,
      toestemming en toegang tot beschermde gegevens blijven afzonderlijke beleidsbeslissingen.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
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
        De Business- <strong>&lt;-&gt;</strong>-ledenverbinding geeft elke actor een expliciete
        identiteitslink met een business. Actief lidmaatschap, rolbevoegdheid, toestemming
        voor beschermde gegevens en toekomstige persoon-tot-persoon grants blijven daarvan gescheiden.
      </p>
    </section>

    <section class="doc-section">
      <h2>Wijzigingen in de Business- <strong>&lt;-&gt;</strong>-ledenverbinding</h2>
      <ul class="doc-list">
        <li>Verbindingen worden expliciet weergegeven in plaats van afgeleid uit UI-status.</li>
        <li>Levenscyclusstatussen — toegangsverzoek, uitnodiging, goedkeuring, activering en intrekking — worden afgedwongen door backend-beleid.</li>
        <li>Businesspanelen en meldingen weerspiegelen nu verbindingstransities en roluitkomsten consistenter.</li>
        <li>Gedeeld Businessgedrag wordt geregeld door actief lidmaatschap en rolbeleid voordat bevoorrechte acties worden verwerkt.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Verbinding, lidmaatschap, toestemming en grants</h2>
      <p>
        PayCal behandelt deze concepten nu als gescheiden:
      </p>
      <ul class="doc-list">
        <li><strong>Verbinding:</strong> een identiteitslink tussen een persoon en een business, of tussen twee personen.</li>
        <li><strong>Lidmaatschap:</strong> de actieve Business-deelname voor samenwerking in de workspace.</li>
        <li><strong>Toestemming:</strong> de goedkeuring van het lid om beschermde werkgegevens te delen.</li>
        <li><strong>Grant:</strong> een expliciete toestemming, zoals gedelegeerde kalenderweergave of een toekomstige vertrouwde herstelfunctie.</li>
      </ul>
      <p>
        Een verbinding alleen geeft geen beschermde rapporten, exports, payroll-zichtbaarheid,
        herstelbevoegdheid of mogelijkheid om namens een andere persoon te handelen.
      </p>
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
        Businesssamenwerking kruist met versleutelings- en toestemmingscontroles. Actief lidmaatschap,
        rolcontroles en toestemmingsstatus bewaken gedeeld Business-envelop-gedrag zodat gevoelige
        operaties beleidsgebonden blijven.
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
