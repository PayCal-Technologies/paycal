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
    <span class="current">Connessioni aziendali e filosofia dei ruoli</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Questa pagina spiega il passaggio da una semantica di gruppo debolmente accoppiata a
      Connessioni esplicite. Una connessione dice chi è collegato a chi. Appartenenza, ruolo,
      consenso e accesso ai dati protetti restano decisioni di policy separate.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
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
        La connessione Business <strong>&lt;-&gt;</strong> Membro dà a ogni attore un link di identità
        esplicito con un'azienda. Appartenenza attiva, autorità di ruolo, consenso sui dati
        protetti e future concessioni persona-a-persona restano separati da quel link.
      </p>
    </section>

    <section class="doc-section">
      <h2>Modifiche alla connessione Business <strong>&lt;-&gt;</strong> Membro</h2>
      <ul class="doc-list">
        <li>Le connessioni sono rappresentate esplicitamente invece di essere dedotte dallo stato UI.</li>
        <li>Gli stati del ciclo di vita — richiesta di accesso, invito, approvazione, attivazione e revoca — sono applicati dalla policy backend.</li>
        <li>I pannelli Business e le notifiche ora riflettono in modo più coerente le transizioni di connessione e i risultati dei ruoli.</li>
        <li>Il comportamento Business condiviso è governato da appartenenza attiva e policy di ruolo prima che vengano elaborate le azioni privilegiate.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Connessione, appartenenza, consenso e concessioni</h2>
      <p>
        PayCal ora tratta questi concetti come separati:
      </p>
      <ul class="doc-list">
        <li><strong>Connessione:</strong> un link di identità tra una persona e un'azienda, o tra due persone.</li>
        <li><strong>Appartenenza:</strong> lo stato attivo di partecipazione Business usato per la collaborazione nel workspace.</li>
        <li><strong>Consenso:</strong> l'approvazione del membro alla condivisione di dati di lavoro protetti.</li>
        <li><strong>Concessione:</strong> un permesso esplicito, come la vista calendario delegata o una futura capacità di recupero fidato.</li>
      </ul>
      <p>
        Una connessione da sola non concede report protetti, esportazioni, visibilità payroll,
        autorità di recupero o capacità di agire per un'altra persona.
      </p>
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
        La collaborazione Business si interseca con i controlli di crittografia e consenso.
        Appartenenza attiva, controlli di ruolo e stato del consenso governano il comportamento
        condiviso dell'envelope Business in modo che le operazioni sensibili rimangano vincolate alla policy.
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
