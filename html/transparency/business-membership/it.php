<?php
/**
 * Public Transparency: Organization Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses an Organization <-> Member relationship model,
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
    <span class="current">Appartenenza organizzativa e filosofia dei ruoli</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Questa pagina spiega il passaggio da una semantica di team debolmente accoppiata a un modello
      esplicito di relazione Organizzazione <strong>&lt;-&gt;</strong> Membro, la politica dei ruoli
      attuale e i principi che utilizziamo per mantenere i permessi verificabili e sicuri.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Perché esiste questo modello</h2>
      <p>
        La collaborazione sulle buste paga ha un impatto reale sulla sicurezza. Un modello di ruoli
        facile da leggere, testare e controllare è più sicuro di un modello costruito su verifiche
        casuali e sparse.
      </p>
      <p>
        La struttura Organizzazione <strong>&lt;-&gt;</strong> Membro dà a ogni attore una relazione
        esplicita con un'organizzazione, con comportamento di stato, ruolo e portata gestito dalla policy.
      </p>
    </section>

    <section class="doc-section">
      <h2>Modifiche alla relazione Organizzazione <strong>&lt;-&gt;</strong> Membro</h2>
      <ul class="doc-list">
        <li>L'appartenenza è rappresentata come una relazione esplicita piuttosto che uno stato UI implicito.</li>
        <li>Gli stati del ciclo di vita — richiesta di accesso, invito, approvazione, attivazione e revoca — sono applicati dalla policy backend.</li>
        <li>I pannelli organizzativi e le notifiche ora riflettono in modo più coerente le transizioni di relazione e i risultati dei ruoli.</li>
        <li>Il comportamento organizzativo condiviso è governato dallo stato di appartenenza prima che vengano elaborate le azioni privilegiate.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Cambiamenti di ruolo e filosofia dei ruoli attuale</h2>
      <p>
        I ruoli sono guidati dalle capacità, con restrizioni di portata applicate per operazione. La baseline attuale:
      </p>
      <ul class="doc-list">
        <li><strong>proprietario:</strong> controllo sovrano incluso il trasferimento di proprietà e le azioni di governance ad alta fiducia.</li>
        <li><strong>manager:</strong> controllo operativo quotidiano senza autorità di trasferimento della proprietà.</li>
        <li><strong>collaboratore:</strong> operatore di fiducia con autorità di scrittura vincolata dall'ambito assegnato.</li>
        <li><strong>membro:</strong> partecipazione limitata in self-service con diritti di modifica limitati.</li>
        <li><strong>spettatore:</strong> visibilità in sola lettura senza permessi di scrittura.</li>
      </ul>
      <p>
        Preferiamo la composizione esplicita di capacità e portata rispetto agli indicatori di ruolo sovraccarichi. Questo rende i risultati dei ruoli più facili da testare e da comprendere.
      </p>
    </section>

    <section class="doc-section">
      <h2>Filosofia della sicurezza e della crittografia</h2>
      <p>
        La collaborazione organizzativa si interseca con i controlli di crittografia e consenso.
        I controlli di appartenenza e ruolo governano il comportamento condiviso dell'envelope
        organizzativo in modo che le operazioni sensibili rimangano vincolate alla policy.
      </p>
      <ul class="doc-list">
        <li>Lo stato di appartenenza e consenso viene validato prima che procedano le operazioni sicure condivise.</li>
        <li>I cambiamenti di ruolo e le transizioni di appartenenza sono trattati come eventi rilevanti per la sicurezza, non solo eventi UX.</li>
        <li>I percorsi di negazione dell'accesso sono un comportamento atteso in caso di mancata corrispondenza della policy e vengono esposti per la verificabilità.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Filosofia operativa per il futuro</h2>
      <ul class="doc-list">
        <li><strong>Fonte di policy unica:</strong> le decisioni su ruoli e portata dovrebbero provenire da mappe di policy backend condivise.</li>
        <li><strong>UI come proiezione:</strong> le interfacce dovrebbero visualizzare i risultati della policy piuttosto che duplicare la logica di autorizzazione.</li>
        <li><strong>Transizioni tracciabili:</strong> approvazioni, cambiamenti di ruolo e revoche devono rimanere osservabili e verificabili.</li>
        <li><strong>Trasparenza delle release:</strong> i cambiamenti di comportamento nell'appartenenza e nei ruoli sono documentati nei changelog e nelle pagine di trasparenza.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
