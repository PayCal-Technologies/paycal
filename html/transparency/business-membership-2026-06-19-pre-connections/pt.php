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
    <span class="current">Adesão organizacional e filosofia de funções</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Esta página explica a transição de semântica de equipe fracamente acoplada para um modelo
      explícito de relacionamento Organização <strong>&lt;-&gt;</strong> Membro, a política de
      funções atual e os princípios que usamos para manter as permissões auditáveis e seguras.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
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
        A estrutura Organização <strong>&lt;-&gt;</strong> Membro dá a cada ator um relacionamento
        explícito com uma organização, com comportamento de status, função e escopo orientado por política.
      </p>
    </section>

    <section class="doc-section">
      <h2>Mudanças no relacionamento Organização <strong>&lt;-&gt;</strong> Membro</h2>
      <ul class="doc-list">
        <li>A adesão é representada como um relacionamento explícito em vez de um estado de interface implícito.</li>
        <li>Os estados do ciclo de vida — solicitação de acesso, convite, aprovação, ativação e revogação — são aplicados pela política de backend.</li>
        <li>Painéis de organização e notificações agora refletem transições de relacionamento e resultados de funções de forma mais consistente.</li>
        <li>O comportamento organizacional compartilhado é regido pelo estado de adesão antes que ações privilegiadas sejam processadas.</li>
      </ul>
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
        A colaboração organizacional intersecta controles de criptografia e consentimento. As verificações
        de adesão e função controlam o comportamento do envelope organizacional compartilhado para que
        operações sensíveis permaneçam vinculadas à política.
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
