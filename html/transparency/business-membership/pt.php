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
    <span class="current">Conexões empresariais e filosofia de funções</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Esta página explica a transição de semântica de equipe fracamente acoplada para
      Conexões explícitas. Uma conexão diz quem está ligado a quem. Adesão, função,
      consentimento e acesso a dados protegidos são decisões de política separadas.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Por que este modelo existe</h2>
      <p>
        A colaboração em folha de pagamento tem impacto real na segurança. Um modelo de funções fácil
        de ler, testar e auditar é mais seguro do que um modelo construído a partir de verificações
        pontuais dispersas.
      </p>
      <p>
        A conexão Business <strong>&lt;-&gt;</strong> Membro dá a cada ator um vínculo de identidade
        explícito com um negócio. Adesão ativa, autoridade de função, consentimento sobre
        dados protegidos e futuras concessões pessoa-a-pessoa permanecem separados desse vínculo.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mudanças na conexão Business <strong>&lt;-&gt;</strong> Membro</h2>
      <ul class="doc-list">
        <li>As conexões são representadas explicitamente em vez de inferidas do estado da interface.</li>
        <li>Os estados do ciclo de vida — solicitação de acesso, convite, aprovação, ativação e revogação — são aplicados pela política de backend.</li>
        <li>Painéis Business e notificações agora refletem transições de conexão e resultados de funções de forma mais consistente.</li>
        <li>O comportamento Business compartilhado é regido por adesão ativa e política de função antes que ações privilegiadas sejam processadas.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Conexão, adesão, consentimento e concessões</h2>
      <p>
        O PayCal agora trata estes conceitos como separados:
      </p>
      <ul class="doc-list">
        <li><strong>Conexão:</strong> um vínculo de identidade entre uma pessoa e um negócio, ou entre duas pessoas.</li>
        <li><strong>Adesão:</strong> o estado ativo de participação Business usado para colaboração no workspace.</li>
        <li><strong>Consentimento:</strong> a aprovação do membro para compartilhar dados de trabalho protegidos.</li>
        <li><strong>Concessão:</strong> uma permissão explícita, como visualização delegada de calendário ou uma futura capacidade de recuperação confiável.</li>
      </ul>
      <p>
        Uma conexão sozinha não concede relatórios protegidos, exportações, visibilidade de folha,
        autoridade de recuperação nem capacidade de agir por outra pessoa.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mudanças de funções e filosofia de funções atual</h2>
      <p>
        As funções são orientadas por capacidade, com restrições de escopo aplicadas por operação. A linha de base atual:
      </p>
      <ul class="doc-list">
        <li><strong>proprietário:</strong> controle soberano, incluindo transferência de propriedade e ações de governança de alta confiança.</li>
        <li><strong>gerente:</strong> controle operacional diário sem autoridade de transferência de propriedade.</li>
        <li><strong>colaborador:</strong> operador confiável com autoridade de escrita restrita pelo escopo atribuído.</li>
        <li><strong>membro:</strong> participação limitada em autoatendimento com direitos de mutação restritos.</li>
        <li><strong>observador:</strong> visibilidade somente leitura sem permissões de escrita.</li>
      </ul>
      <p>
        Favorecemos a composição explícita de capacidades e escopos em detrimento de sinalizadores de função sobrecarregados. Isso torna os resultados das funções mais fáceis de testar e de raciocinar.
      </p>
    </section>

    <section class="doc-section">
      <h2>Filosofia de segurança e criptografia</h2>
      <p>
        A colaboração Business intersecta controles de criptografia e consentimento. Adesão ativa,
        verificações de função e estado de consentimento controlam o comportamento do envelope Business
        compartilhado para que operações sensíveis permaneçam vinculadas à política.
      </p>
      <ul class="doc-list">
        <li>O estado de adesão e consentimento é validado antes que operações seguras compartilhadas prossigam.</li>
        <li>Mudanças de função e transições de adesão são tratadas como eventos relevantes para a segurança, não apenas eventos de UX.</li>
        <li>Os caminhos de negação de acesso são comportamento esperado em caso de incompatibilidade de política e são expostos para auditabilidade.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Filosofia operacional futura</h2>
      <ul class="doc-list">
        <li><strong>Fonte de política única:</strong> decisões de função e escopo devem originar-se de mapas de política de backend compartilhados.</li>
        <li><strong>UI como projeção:</strong> interfaces devem exibir resultados de política em vez de duplicar lógica de autorização.</li>
        <li><strong>Transições rastreáveis:</strong> aprovações, mudanças de função e revogações devem permanecer observáveis e revisáveis.</li>
        <li><strong>Transparência de versões:</strong> mudanças de comportamento em adesão e funções são documentadas em changelogs e páginas de transparência.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
