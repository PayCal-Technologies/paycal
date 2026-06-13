<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_SOC2_PAGE_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_SOC2_PAGE_TITLE'];


require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Navegação estrutural">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Centro de transparência</a>
    <span class="separator">/</span>
    <span class="current"><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_SOC2_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">Uma visão técnica de como o PayCal mapeia os controles SOC 2 para comportamentos de sistema aplicados e evidências geradas continuamente.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Visão geral</h2>
      <p>O PayCal opera um programa de segurança alinhado ao SOC 2, focado em aplicação verificável e evidências rastreáveis, não em meras afirmações documentais.</p>
      <ul class="doc-fact-list">
        <li><strong>Controles no escopo:</strong> CC1-CC9</li>
        <li><strong>Artefatos no bundle atual:</strong> 37</li>
        <li><strong>Mapeamentos controle-artefato:</strong> 26</li>
        <li><strong>Janela de atualidade das evidências:</strong> 35 dias</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Cobertura de controles (CC1-CC9)</h2>
      <p>Todos os controles SOC 2 Common Criteria no escopo (CC1 a CC9) são mapeados para evidências retidas no bundle mensal.</p>
      <p>Esse mapeamento suporta rastreabilidade direta do objetivo de controle até os artefatos concretos usados para revisão.</p>
    </section>

    <section class="doc-section">
      <h2>3. Como os controles são aplicados</h2>
      <p>O PayCal trata a aplicação como uma propriedade do sistema. Os controles são aplicados programaticamente, não apenas documentados.</p>
      <ul class="doc-fact-list">
        <li><strong>Autenticação:</strong> Fluxo de autenticação com suporte a passkey para fortalecer a resistência a phishing.</li>
        <li><strong>Integridade em tempo de execução:</strong> Monitoramento de integridade em tempo de execução com gerenciamento de estado operacional.</li>
        <li><strong>Endurecimento de saídas:</strong> Controles de sanitização Guardian para caminhos DOM/saída sensíveis.</li>
        <li><strong>Barreira de qualidade:</strong> Barreira PHPUnit de suíte completa automatizada antes que as evidências do bundle sejam aceitas.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Gestão de mudanças &amp; Testes</h2>
      <p>A governança de mudanças está alinhada ao CC8 com mudanças rastreadas, aprovações e evidências de testes.</p>
      <ul class="doc-fact-list">
        <li><strong>Registros de mudanças:</strong> 12</li>
        <li><strong>Registros de aprovação:</strong> 10</li>
        <li><strong>Resultados de testes:</strong> 1528 testes, 8351 asserções (aprovados)</li>
        <li><strong>Rastreamento teste-controle:</strong> 5 suítes, 5 aprovadas, 8 arquivos de teste vinculados</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Trilha de auditoria &amp; Integridade das evidências</h2>
      <p>Eventos de tempo de execução administrativos e relacionados à segurança são exportados com validação de livro-razão imutável para verificações de integridade.</p>
      <p><strong>Status atual de integridade do livro-razão:</strong> APROVADO.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Monitoramento contínuo &amp; Atualidade</h2>
      <p>As exportações de evidências são executadas continuamente e validadas em relação a uma política de atualidade determinística.</p>
      <p><strong>Resultado de atualidade atual:</strong> todos os artefatos mapeados estão dentro da janela de auditoria de 35 dias.</p>
    </section>

    <section class="doc-section">
      <h2>7. Status atual</h2>
      <p><strong>Status:</strong> Prontidão SOC 2 em andamento, com endurecimento contínuo de controles e atualizações determinísticas de evidências.</p>
      <p>O PayCal não reivindica certificação SOC 2 nem opinião de auditor nesta página. O acesso ao relatório formal permanece restrito por NDA.</p>
    </section>

    <section class="doc-section">
      <h2>Trechos de conformidade reutilizáveis</h2>
      <p><strong>Selo de rodapé:</strong> Prontidão em andamento • Controles Mapeados • Monitoramento Contínuo de Evidências</p>
      <p><strong>Bloco de resumo:</strong> CC1-CC9 mapeados, 37 artefatos, 26 links de controle, integridade do livro-razão aprovada e evidências de teste de suíte completa automatizada.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Referências</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Resumo de controle público sanitizado, narrativas determinísticas e caminho de contato de segurança.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">Resumo SOC 2 do PayCal</a>
          <span class="doc-ref-desc">Status, métricas e acesso NDA para este relatório.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Solicitar Relatório SOC 2 (NDA)</a>
          <span class="doc-ref-desc">Acesso restrito para revisões de due diligence de fornecedores e segurança.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Padrão Oficial</a>
          <span class="doc-ref-desc">O framework autoritativo que define os critérios SOC 2.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Visão geral do histórico e escopo dos Controles de Sistema e Organização.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Comunidade Reddit</a>
          <span class="doc-ref-desc">Discussão de profissionais sobre auditorias SOC 2 e preparação.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
