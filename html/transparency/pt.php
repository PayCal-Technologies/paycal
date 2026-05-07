<?php
/**
 * Public Transparency Hub — Português
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

$readMoreLabel = 'Leia mais';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Centro de transparência - [PayCal]';
$pageLabel = 'Centro de transparência';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Centro de transparência</span>
    </nav>

    <header class="doc-article-header">
      <h1>Centro de transparência</h1>
      <p class="deck">Publicamos como o PayCal funciona para que os usuários possam verificar as decisões, não apenas confiar nas declarações.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Filosofia da plataforma</h2>
        <p>O PayCal é construído em torno de operações inspecionáveis: as fórmulas são documentadas, os limites de telemetria são explícitos e a retenção é finita por padrão.</p>
        <p>Nosso princípio é simples: se um sistema afeta a folha de pagamento ou a privacidade, os usuários devem ser capazes de entender como ele funciona e como é governado.</p>
        <p>O faturamento de assinaturas é processado pelo Stripe. O suporte do Stripe está disponível em <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>As atualizações recentes que moldaram o produto — incluindo fluxos de faturamento e governança de perfil — são rastreadas em nossas páginas de framework/backend e governança de testes.</p>
      </section>

      <div class="doc-panel-grid doc-panel-grid--responsive-3" aria-label="Painéis de detalhes de transparência">
        <section class="doc-section">
          <h2>Status de auditoria de segurança</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Esta página publica o status atual de auditoria, o escopo fechado, as referências de evidências e os compromissos de bloqueio de lançamento que preservam a postura de segurança.</p>
          <ul class="doc-fact-list">
            <li>O status do ciclo atual é publicado com data de verificação e cadência de revisão.</li>
            <li>A cobertura do escopo inclui controles de ciclo de vida do runtime, isolamento de telemetria, governança de correlação e endurecimento de funções privilegiadas.</li>
            <li>O snapshot de validação inclui Playwright, JS, PHPStan nível 9 e resultados de testes de backend.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Métricas da plataforma e privacidade</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>A página de métricas explica a telemetria operacional coletada para confiabilidade e planejamento de capacidade.</p>
          <ul class="doc-fact-list">
            <li>As chaves de telemetria e exemplos são publicados para que as afirmações sejam verificáveis.</li>
            <li>O escopo de coleta é apenas agregado, com limites rígidos e sem identificadores pessoais nas chaves.</li>
            <li>A retenção segue um ciclo de vida definido: dados brutos, agregados e purga automática.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Acessibilidade e conformidade WCAG</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Usamos WCAG 2.1 nível AA como nosso padrão de acessibilidade de trabalho e publicamos o trabalho de acessibilidade recente em linguagem simples.</p>
          <ul class="doc-fact-list">
            <li>A navegação principal suporta uso do teclado, links de salto e atalhos de tecla única documentados para destinos principais.</li>
            <li>O tratamento de atalhos é protegido e não é acionado ao digitar em campos editáveis ou quando há caixas de diálogo abertas.</li>
            <li>A cobertura recente de regressão verifica cabeçalhos, refluxo/espaçamento de texto, caminhos de navegação e a entrega de feedback de acessibilidade.</li>
            <li>Os bloqueadores de contraste rigorosos em nível de rota nas páginas públicas principais foram corrigidos; o trabalho de contraste em todo o tema continua.</li>
            <li>Os usuários podem iniciar um relatório de acessibilidade na página de acessibilidade e continuá-lo pelo fluxo de contato seguro.</li>
            <li>A página de transparência de acessibilidade agora publica a data da última verificação, o escopo de verificação, as limitações conhecidas e a próxima data de revisão.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verificação e governança</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Esta página documenta como o PayCal aplica políticas por meio de testes, hooks, limites de runtime e controles de segurança.</p>
          <ul class="doc-fact-list">
            <li>Os hooks pre-commit e pre-push aplicam o PHPStan nível 9 e rejeitam os desvios de linha de base.</li>
            <li>A CI executa validação em etapas em jobs de unidade, integração, contrato, ordem aleatória e cobertura.</li>
            <li>Os controles de runtime aplicam limites de taxa, janelas TTL e bloqueios de resposta a abuso para fluxos sensíveis.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Capacidades de rede</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Este artigo publica os protocolos de transporte e os controles de cabeçalhos de resposta usados para proteger o comportamento do navegador e da rede.</p>
          <ul class="doc-fact-list">
            <li>Documenta a aplicação de HTTPS, o precarregamento HSTS e o anúncio HTTP/3 (QUIC).</li>
            <li>Lista a linha de base atual de cabeçalhos de segurança, incluindo CSP, COOP, COEP, CORP e cabeçalhos de endurecimento do navegador.</li>
            <li>Explica a negociação de protocolo e o comportamento de fallback em clientes modernos.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governança de testes</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Este artigo documenta como executamos a validação de backend, frontend e acessibilidade e quais gates são tratados como bloqueadores de lançamento.</p>
          <ul class="doc-fact-list">
            <li>Mostra o inventário ativo de suites PHPUnit e a divisão por categoria.</li>
            <li>Documenta os comandos de validação bloqueadores de lançamento usados nas varreduras <code>/mis</code>.</li>
            <li>Explica como as evidências de teste são sincronizadas em changelogs e notas de fonte da verdade.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Governança de dependências e CI/CD</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Este artigo publica como as dependências npm são controladas e como os gates de CI são aplicados antes do lançamento.</p>
          <ul class="doc-fact-list">
            <li>Documenta a política npm lockfile-first e os requisitos de automação <code>npm ci</code>.</li>
            <li>Mapeia os gates de qualidade JavaScript e as etapas do pipeline de backend para os controles de workflow.</li>
            <li>Lista as limitações de documentação conhecidas e as melhorias de governança planejadas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Registro de alterações de framework e backend</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Esta página rastreia a arquitetura de backend e as alterações no nível de framework com explicações públicas do que mudou e por quê.</p>
          <ul class="doc-fact-list">
            <li>Resume as alterações de serviço/controlador que afetam materialmente o comportamento.</li>
            <li>Mapeia as alterações de lançamento para os controles de segurança e governança.</li>
            <li>Inclui referências ao changelog detalhado e artefatos de auditoria.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Experiência do produto e alterações de faturamento</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>As principais atualizações de conta, faturamento e fluxos de perfil são explicadas junto com a governança de backend e testes para que os usuários possam auditar as alterações de UX e comportamento.</p>
          <ul class="doc-fact-list">
            <li>Rastreia o tratamento do estado de faturamento e as alterações no contrato de status de assinatura.</li>
            <li>Captura as salvaguardas de ações destrutivas, como frases de confirmação explícitas para exclusão de conta.</li>
            <li>Vincula atualizações voltadas ao produto com evidências de verificação e governança de lançamento.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Metodologia fiscal</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>A página de impostos documenta nossas fórmulas, limites e exemplos alinhados com a CRA usados para estimativas.</p>
          <ul class="doc-fact-list">
            <li>As fórmulas de CPP, OAS, EI, imposto federal/provincial e salário líquido são documentadas com exemplos práticos.</li>
            <li>Os limites e taxas do ano fiscal atual são publicados e vinculados a referências da CRA.</li>
            <li>A qualidade do cálculo é validada com uma suite de testes automatizada e atualizações anuais de taxas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Arquitetura de e-mail</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>A página de e-mail explica quais e-mails transacionais o PayCal envia, como os modelos são renderizados e como a confiabilidade de entrega é verificada.</p>
          <ul class="doc-fact-list">
            <li>As famílias de modelos específicas de fluxo são documentadas nos caminhos de verificação, recuperação, alteração de e-mail e suporte de contato.</li>
            <li>As responsabilidades de entrega são separadas entre a orquestração do EmailGarum e o tratamento do protocolo SMTP do EmailTransport.</li>
            <li>Os testes ao vivo opt-in para varreduras de modelos e verificação de saúde DKIM/DMARC são documentados.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Testes de carga de earnings</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Este artigo publica resultados de benchmarks A/B reproduzíveis para renderização antecipada versus carregamento lento de seções em <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Inclui uma matriz de 10 execuções para conjuntos de dados reais e sintéticos de 2025/2026.</li>
            <li>Reporta DOMContentLoaded, tempo de disponibilidade da seção e as compensações de chamadas de API.</li>
            <li>Documenta o método de teste e a interpretação para revisão pública.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mapa de super-heróis</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>A página de Super-heróis documenta os componentes transversais temáticos do PayCal e o problema operacional específico que cada um resolve.</p>
          <ul class="doc-fact-list">
            <li>Inclui ShadowTalon, Guardian, Phantom Wing, Lens e EmailGarum.</li>
            <li>Explica onde cada componente é usado e qual limite de risco ele protege.</li>
            <li>Fornece âncoras de verificação para que as afirmações de implementação possam ser inspecionadas diretamente no código e nos testes.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Paradigma de extensões</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
          <p>Este artigo explica como o PayCal Core permanece estável enquanto pacotes de extensão fornecem comportamento configurável para diferentes modelos de implantação.</p>
          <ul class="doc-fact-list">
            <li>Esclarece a separação entre PayCal Core e extensões básicas publicadas no repositório.</li>
            <li>Documenta como terceiros podem construir extensões próprias a partir deste repositório.</li>
            <li>Explica como o paycal.app canônico usa variantes privadas de extensão para diferenciar a plataforma.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/extensions/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
