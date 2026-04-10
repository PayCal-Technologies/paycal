<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Paradigma de Extensoes - [PayCal]';
$pageLabel = 'Paradigma de Extensoes';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Paradigma de Extensoes</span>
  </nav>

  <header class="doc-article-header">
    <h1>Paradigma de Extensoes</h1>
    <p class="deck">
      O PayCal foi projetado para manter a logica central estavel, enquanto
      as camadas de extensao podem adaptar recursos para diferentes implantacoes
      e estrategias de produto.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Arquitetura Core First</h2>
      <p>
        O <strong>PayCal Core</strong> contem a logica canonica de dominio e controladores:
        calculos, validacao, permissoes, politicas de ciclo de vida e contratos
        compartilhados de API.
      </p>
      <p>
        O Core permanece agnostico a extensoes por design. Os pontos de integracao
        sao isolados por contratos de ponte para que os servicos centrais possam ser
        testados independentemente de pacotes especificos de runtime.
      </p>
    </section>

    <section class="doc-section">
      <h2>Extensoes basicas incluidas neste repositorio</h2>
      <p>
        Este repositorio publica <strong>implementacoes basicas de extensoes</strong> que
        fornecem comportamento padrao para os pontos de extensao. Elas atuam como
        pacotes de referencia publicos e padroes seguros para implantacoes self-hosted.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> hooks basicos de cobranca e selecao de modo</li>
        <li><strong>earnings-ytd:</strong> renderizacao base de YTD e pontos de hook de ganhos</li>
        <li><strong>organization-signals:</strong> hooks basicos de sinais de organizacao</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Modelo de extensoes para terceiros</h2>
      <p>
        Terceiros que usam este repositorio podem criar e manter seus proprios
        pacotes de extensao. O modelo recomendado e:
      </p>
      <ol class="doc-list">
        <li>Manter a logica core sem modificacao sempre que possivel</li>
        <li>Implementar comportamento customizado em pacotes de extensao</li>
        <li>Conectar pacotes customizados pelos seams de bootstrap e hooks documentados</li>
        <li>Preservar contratos do core para facilitar upgrades futuros</li>
      </ol>
      <p>
        Isso permite implantacoes competitivas e especificas por vertical sem
        exigir forks permanentes da logica central.
      </p>
    </section>

    <section class="doc-section">
      <h2>Diferenciacao da plataforma canonica paycal.app</h2>
      <p>
        A plataforma canonica <code>https://paycal.app</code> executa <strong>variantes
        privadas de extensoes</strong> sobre o mesmo core e paradigma de extensoes basicas.
      </p>
      <p>
        Essas variantes privadas sao uma camada deliberada de diferenciacao de
        produto para os ambientes operados pela PayCal. Elas podem ajustar fluxos,
        comportamento de capacidades e integracoes de UI mantendo compatibilidade
        com a mesma arquitetura central.
      </p>
      <ul class="doc-list">
        <li>A logica core permanece compartilhada e auditavel</li>
        <li>As extensoes publicas/basicas continuam disponiveis no repositorio</li>
        <li>As extensoes privadas diferenciam a plataforma canonica</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Compromissos de transparencia</h2>
      <ul class="doc-list">
        <li>Contratos do core sao documentados e testados nos seams de extensao</li>
        <li>Limites de ponte sao explicitos para tornar acoplamentos auditaveis</li>
        <li>Comportamento de extensoes pode evoluir sem desestabilizar servicos centrais</li>
        <li>Adotantes self-hosted podem construir estrategias alternativas de extensao</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
