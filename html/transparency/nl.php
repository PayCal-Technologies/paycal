<?php
/**
 * Public Transparency Hub
 *
 * PURPOSE: High-level philosophy and links to detailed transparency pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_link.php';

if (!function_exists('transparency_href')) {
  function transparency_href(string $path): string
  {
    return $path;
  }
}

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_HOME',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$readMoreLabel = 'Lees meer';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Transparantiehub - [PayCal]';
$pageLabel = 'Transparantiehub';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Transparantiehub</span>
    </nav>

    <header class="doc-article-header">
      <h1>Transparantiehub</h1>
      <p class="deck">Wij publiceren hoe PayCal werkt zodat gebruikers beslissingen kunnen verifieren in plaats van alleen op verklaringen te vertrouwen.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Overzicht van platformfilosofie</h2>
        <p>PayCal is gebouwd rond inspecteerbare processen: formules zijn gedocumenteerd, telemetriegrenzen zijn expliciet en retentie is standaard eindig.</p>
        <p>Ons principe is eenvoudig: als een systeem invloed heeft op loon of privacy, moeten gebruikers kunnen begrijpen hoe het werkt en hoe het wordt bestuurd.</p>
        <p>Abonnementsfacturatie wordt verwerkt door Stripe. Stripe-ondersteuning is beschikbaar via <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Recente productvormende updates, inclusief facturatie en profielgovernance-flows, worden bijgehouden op onze pagina's voor framework/backend en testgovernance.</p>
      </section>

      <div class="doc-panel-grid" aria-label="Transparantie detailpanelen">
        <section class="doc-section">
          <h2>Status beveiligingsaudit</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Deze pagina publiceert de huidige auditstatus, afgesloten scope, bewijsreferenties en releaseblokkende toezeggingen die de beveiligingshouding behouden.</p>
          <ul class="doc-fact-list">
            <li>De status van de huidige cyclus wordt gepubliceerd met verificatiedatum en reviewcadans.</li>
            <li>Dekkingsgebieden omvatten runtime-lifecyclecontroles, telemetrie-isolatie, correlatiegovernance en hardening van geprivilegieerde rollen.</li>
            <li>De validatiesnapshot bevat Playwright, JS, PHPStan niveau 9 en backendtestresultaten.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Platformmetrieken en privacy</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>De metriekenpagina legt operationele telemetrie uit die wordt verzameld voor betrouwbaarheid en capaciteitsplanning.</p>
          <ul class="doc-fact-list">
            <li>Telemetriesleutels en voorbeelden zijn gepubliceerd zodat claims controleerbaar zijn.</li>
            <li>De verzamelingsscope is alleen geaggregeerd met harde limieten en zonder persoonlijke identifiers in sleutels.</li>
            <li>Retentie volgt een gedefinieerde lifecycle: ruwe data, rollups en automatische verwijdering.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Toegankelijkheid en WCAG-naleving</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Wij gebruiken WCAG 2.1 niveau AA als onze praktische toegankelijkheidsstandaard en publiceren recent toegankelijkheidswerk in duidelijke taal.</p>
          <ul class="doc-fact-list">
            <li>De kernnavigatie ondersteunt toetsenbordgebruik, skip-links en gedocumenteerde sneltoetsen met een enkele toets voor primaire bestemmingen.</li>
            <li>Afhandeling van sneltoetsen is beveiligd en wordt niet geactiveerd tijdens typen in bewerkbare velden of wanneer dialoogvensters open zijn.</li>
            <li>Recente regressiedekking controleert koppen, reflow/tekstafstand, navigatiepaden en de overdracht van toegankelijkheidsfeedback.</li>
            <li>Strikte route-niveau contrastblokkades op kern publieke pagina's zijn opgelost, terwijl breder contrastwerk over het hele thema doorgaat.</li>
            <li>Gebruikers kunnen een toegankelijkheidsrapport starten vanaf de toegankelijkheidspagina en dit vervolgen via de beveiligde contactflow.</li>
            <li>De toegankelijkheids-transparantiepagina publiceert nu datum van laatste verificatie, verificatiebereik, bekende beperkingen en volgende revisiedatum.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verificatie en governance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Deze pagina documenteert hoe PayCal beleid afdwingt via tests, hooks, runtime-limieten en beveiligingscontroles.</p>
          <ul class="doc-fact-list">
            <li>Pre-commit- en pre-push-hooks dwingen PHPStan niveau 9 af en wijzen baseline-omzeilingen af.</li>
            <li>CI voert gefaseerde validatie uit over unit-, integratie-, contract-, random-order- en dekkingsjobs.</li>
            <li>Runtime-controles passen rate-limits, TTL-vensters en anti-misbruikblokkades toe voor gevoelige flows.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Netwerkcapaciteiten</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Dit artikel publiceert transportprotocollen en response-headercontroles die worden gebruikt om browser- en netwerkgedrag te beveiligen.</p>
          <ul class="doc-fact-list">
            <li>Documenteert HTTPS-handhaving, HSTS-preload en HTTP/3 (QUIC)-aankondiging.</li>
            <li>Somt de huidige baseline van beveiligingsheaders op, inclusief CSP, COOP, COEP, CORP en browser-hardeningheaders.</li>
            <li>Legt protocolonderhandeling en fallback-gedrag uit voor moderne clients.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governance van testen en validatie</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Dit artikel documenteert hoe wij backend-, frontend- en toegankelijkheidsvalidatie uitvoeren en welke gates als releaseblokkerend worden behandeld.</p>
          <ul class="doc-fact-list">
            <li>Toont de actieve PHPUnit-suite-inventaris en categorieverdeling.</li>
            <li>Documenteert releaseblokkerende validatiecommando's die worden gebruikt in <code>/mis</code>-sweeps.</li>
            <li>Legt uit hoe testbewijs wordt gesynchroniseerd naar changelogs en source-of-truth-notities.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governance van afhankelijkheden en CI/CD</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Dit artikel publiceert hoe npm-afhankelijkheden worden beheerd en hoe CI-gates voor release worden afgedwongen.</p>
          <ul class="doc-fact-list">
            <li>Documenteert het lockfile-first npm-beleid en de automatiseringseisen voor <code>npm ci</code>.</li>
            <li>Koppelt JavaScript-kwaliteitsgates en backend-pipelinefasen aan workflowcontroles.</li>
            <li>Somt bekende documentatiebeperkingen en geplande governanceverbeteringen op.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Wijzigingslogboek framework en backend</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Deze pagina volgt backendarchitectuur en wijzigingen op frameworkniveau met publieke uitleg over wat er veranderde en waarom.</p>
          <ul class="doc-fact-list">
            <li>Vat service/controller-wijzigingen samen die het gedrag wezenlijk beinvloeden.</li>
            <li>Koppelt releasewijzigingen aan beveiligings- en governancecontroles.</li>
            <li>Bevat verwijzingen naar gedetailleerde changelog- en auditartefacten.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Productervaring en facturatiewijzigingen</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Grote updates rond account, facturatie en profielstromen worden toegelicht naast backend- en testgovernance, zodat gebruikers zowel UX- als gedragswijzigingen kunnen auditen.</p>
          <ul class="doc-fact-list">
            <li>Volgt afhandeling van facturatiestatus en wijzigingen in contracten rond abonnementsstatus.</li>
            <li>Legt beschermingen voor destructieve acties vast, zoals expliciete bevestigingszinnen voor accountverwijdering.</li>
            <li>Verbindt productgerichte updates met bewijs voor verificatie en releasegovernance.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Belastingmethodologie</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>De belastingpagina documenteert onze CRA-uitgelijnde formules, drempels en voorbeelden die voor schattingen worden gebruikt.</p>
          <ul class="doc-fact-list">
            <li>CPP-, OAS-, EI-, federale/provinciale belasting- en nettoloonformules zijn gedocumenteerd met uitgewerkte voorbeelden.</li>
            <li>Huidige drempels en tarieven van het belastingjaar worden gepubliceerd en gekoppeld aan CRA-referenties.</li>
            <li>Rekenkwaliteit wordt gevalideerd met een geautomatiseerde testsuite en jaarlijkse tariefupdates.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>E-mailarchitectuur</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>De e-mailpagina legt uit welke transactionele e-mails PayCal verzendt, hoe templates worden gerenderd en hoe leveringsbetrouwbaarheid wordt geverifieerd.</p>
          <ul class="doc-fact-list">
            <li>Flowspecifieke templatefamilies zijn gedocumenteerd over verificatie-, herstel-, e-mailwijzigings- en contactondersteuningspaden.</li>
            <li>Leveringsverantwoordelijkheden zijn gescheiden tussen EmailGarum-orkestratie en EmailTransport SMTP-protocolafhandeling.</li>
            <li>Opt-in livetests voor templatesweeps en DKIM/DMARC-gezondheidsverificatie zijn gedocumenteerd.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Earnings load-testing</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Dit artikel publiceert reproduceerbare A/B-benchmarkresultaten voor eager rendering versus lazy section loading op <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Bevat een matrix met 10 runs voor echte en synthetische datasets van 2025/2026.</li>
            <li>Rapporteert DOMContentLoaded, timing wanneer secties gereed zijn en afwegingen rond API-calls.</li>
            <li>Documenteert testmethode en interpretatie voor publieke beoordeling.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Superheroes-kaart</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>De Superheroes-pagina documenteert PayCal's thematische, domeinoverstijgende componenten en het specifieke operationele probleem dat elk component oplost.</p>
          <ul class="doc-fact-list">
            <li>Bevat ShadowTalon, Guardian, Phantom Wing, Lens en EmailGarum.</li>
            <li>Legt uit waar elk component wordt gebruikt en welke risicogrens het beschermt.</li>
            <li>Biedt verificatie-ankers zodat implementatieclaims direct in code en tests kunnen worden gecontroleerd.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
