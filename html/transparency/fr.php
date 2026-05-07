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

$readMoreLabel = 'En savoir plus';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Centre de transparence - [PayCal]';
$pageLabel = 'Centre de transparence';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Centre de transparence</span>
    </nav>

    <header class="doc-article-header">
      <h1>Centre de transparence</h1>
      <p class="deck">Nous publions le fonctionnement de PayCal afin que les utilisateurs puissent verifier les decisions, pas seulement faire confiance aux declarations.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Vue d'ensemble de la philosophie de la plateforme</h2>
        <p>PayCal est concu autour d'operations inspectables: les formules sont documentees, les limites de telemetrie sont explicites et la retention est finie par defaut.</p>
        <p>Notre principe est simple: si un systeme affecte la paie ou la vie privee, les utilisateurs doivent pouvoir comprendre son fonctionnement et sa gouvernance.</p>
        <p>La facturation des abonnements est traitee par Stripe. Le support Stripe est disponible sur <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Les mises a jour recentes qui ont faconne le produit, y compris la facturation et les flux de gouvernance de profil, sont suivies dans nos pages framework/backend et gouvernance des tests.</p>
      </section>

      <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="Panneaux detail de transparence">
        <section class="doc-section">
          <h2>Statut d'audit de securite</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Cette page publie le statut d'audit actuel, le perimetre cloture, les references de preuves et les engagements de blocage release qui preservent la posture de securite.</p>
          <ul class="doc-fact-list">
            <li>Le statut du cycle en cours est publie avec date de verification et cadence de revue.</li>
            <li>La couverture des chantiers inclut les controles de cycle de vie runtime, l'isolation de telemetrie, la gouvernance de correlation et le durcissement des roles privilegies.</li>
            <li>Le snapshot de validation inclut Playwright, JS, PHPStan niveau 9 et les resultats de tests backend.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Metriques plateforme et vie privee</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La page metriques explique la telemetrie operationnelle collectee pour la fiabilite et la planification de capacite.</p>
          <ul class="doc-fact-list">
            <li>Les cles de telemetrie et des exemples sont publies afin que les affirmations soient verifiables.</li>
            <li>Le perimetre de collecte est uniquement agrege, avec limites strictes et sans identifiants personnels dans les cles.</li>
            <li>La retention suit un cycle de vie defini: donnees brutes, agregats et purge automatique.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Accessibilite et conformite WCAG</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Nous utilisons WCAG 2.1 niveau AA comme norme d'accessibilite de reference et nous publions les travaux recents en langage clair.</p>
          <ul class="doc-fact-list">
            <li>La navigation principale prend en charge le clavier, les liens d'evitement et des raccourcis a touche unique documentes pour les destinations principales.</li>
            <li>La gestion des raccourcis est securisee et ne se declenche pas pendant la saisie dans des champs modifiables ou lorsque des boites de dialogue sont ouvertes.</li>
            <li>La couverture de regression recente verifie les titres, le reflow/l'espacement du texte, les parcours de navigation et la transmission des retours d'accessibilite.</li>
            <li>Les blocages stricts de contraste au niveau des routes sur les pages publiques principales ont ete corriges, tandis que le travail de contraste a l'echelle du theme continue.</li>
            <li>Les utilisateurs peuvent demarrer un rapport d'accessibilite depuis la page accessibilite et le poursuivre via le flux de contact securise.</li>
            <li>La page de transparence accessibilite publie maintenant la date de derniere verification, le perimetre de verification, les limites connues et la date de prochaine revue.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verification et gouvernance</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Cette page documente comment PayCal applique les politiques via des tests, des hooks, des limites d'execution et des controles de securite.</p>
          <ul class="doc-fact-list">
            <li>Les hooks pre-commit et pre-push imposent PHPStan niveau 9 et rejettent les contournements de baseline.</li>
            <li>La CI execute une validation par etapes sur les jobs unitaires, d'integration, de contrat, d'ordre aleatoire et de couverture.</li>
            <li>Les controles d'execution appliquent des limites de debit, des fenetres TTL et des blocages anti-abus pour les flux sensibles.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Capacites reseau</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Cet article publie les protocoles de transport et les controles d'en-tetes de reponse utilises pour securiser le comportement navigateur et reseau.</p>
          <ul class="doc-fact-list">
            <li>Documente l'application de HTTPS, le preload HSTS et l'annonce HTTP/3 (QUIC).</li>
            <li>Liste la base actuelle des en-tetes de securite, incluant CSP, COOP, COEP, CORP et les en-tetes de durcissement navigateur.</li>
            <li>Explique la negotiation de protocole et le comportement de repli sur les clients modernes.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Gouvernance des tests et de la validation</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Cet article documente comment nous executons la validation backend, frontend et accessibilite, et quelles portes sont traitees comme bloquantes pour la release.</p>
          <ul class="doc-fact-list">
            <li>Presente l'inventaire actif des suites PHPUnit et la repartition par categorie.</li>
            <li>Documente les commandes de validation bloquantes utilisees dans les passes <code>/mis</code>.</li>
            <li>Explique comment les preuves de test sont synchronisees dans les changelogs et les notes source of truth.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Gouvernance des dependances et de la CI/CD</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Cet article publie comment les dependances npm sont controlees et comment les portes CI sont imposees avant release.</p>
          <ul class="doc-fact-list">
            <li>Documente la politique npm lockfile-first et les exigences d'automatisation <code>npm ci</code>.</li>
            <li>Relie les portes qualite JavaScript et les etapes du pipeline backend aux controles de workflow.</li>
            <li>Liste les limites de documentation connues et les ameliorations de gouvernance prevues.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Journal des changements framework et backend</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Cette page suit l'architecture backend et les changements au niveau framework avec des explications publiques sur ce qui a change et pourquoi.</p>
          <ul class="doc-fact-list">
            <li>Resume les changements service/controller qui affectent concretement le comportement.</li>
            <li>Relie les changements de release aux controles de securite et de gouvernance.</li>
            <li>Inclut des references vers le changelog detaille et les artefacts d'audit.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Experience produit et changements de facturation</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Les mises a jour majeures du compte, de la facturation et des flux profil sont expliquees avec la gouvernance backend et de test afin que les utilisateurs puissent auditer a la fois l'UX et les changements de comportement.</p>
          <ul class="doc-fact-list">
            <li>Suit la gestion des etats de facturation et les changements de contrat de statut d'abonnement.</li>
            <li>Capture les garde-fous des actions destructives, comme les phrases explicites de confirmation de suppression de compte.</li>
            <li>Lie les mises a jour visibles produit aux preuves de verification et de gouvernance release.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Methodologie fiscale</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La page taxes documente nos formules, seuils et exemples alignes sur la CRA utilises pour les estimations.</p>
          <ul class="doc-fact-list">
            <li>Les formules CPP, OAS, EI, impot federal/provincial et salaire net sont documentees avec des exemples detailles.</li>
            <li>Les seuils et taux de l'annee fiscale en cours sont publies et relies aux references CRA.</li>
            <li>La qualite de calcul est validee par une suite de tests automatisee et des mises a jour annuelles des taux.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Architecture email</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>La page email explique quels emails transactionnels PayCal envoie, comment les templates sont rendus et comment la fiabilite de livraison est verifiee.</p>
          <ul class="doc-fact-list">
            <li>Les familles de templates specifiques aux flux sont documentees pour les parcours verification, recuperation, changement d'email et support contact.</li>
            <li>Les responsabilites de livraison sont separees entre l'orchestration EmailGarum et la gestion du protocole SMTP par EmailTransport.</li>
            <li>Les tests live opt-in pour les balayages de templates et la verification de sante DKIM/DMARC sont documentes.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Tests de charge earnings</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Cet article publie des resultats de benchmarks A/B reproductibles pour le rendu anticipe versus le chargement paresseux des sections sur <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Inclut une matrice de 10 executions pour des jeux de donnees reels et synthetiques 2025/2026.</li>
            <li>Rapporte DOMContentLoaded, le temps de disponibilite des sections et les compromis sur les appels API.</li>
            <li>Documente la methode de test et l'interpretation pour revue publique.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Cartographie Superheroes</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>La page Superheroes documente les composants transverses thematiques de PayCal et le probleme operationnel precis que chacun resout.</p>
          <ul class="doc-fact-list">
            <li>Inclut ShadowTalon, Guardian, Phantom Wing, Lens et EmailGarum.</li>
            <li>Explique ou chaque composant est utilise et quelle frontiere de risque il protege.</li>
            <li>Fournit des ancres de verification afin que les affirmations d'implementation puissent etre inspectees directement dans le code et les tests.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
