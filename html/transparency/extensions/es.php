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
$pageTitle = 'Paradigma de Extensiones - [PayCal]';
$pageLabel = 'Paradigma de Extensiones';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Paradigma de Extensiones</span>
  </nav>

  <header class="doc-article-header">
    <h1>Paradigma de Extensiones</h1>
    <p class="deck">
      PayCal está diseñado de modo que la lógica empresarial central permanezca estable mientras
      las capas de extensión pueden adaptar las funcionalidades para diferentes despliegues
      y estrategias de producto.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Arquitectura núcleo primero</h2>
      <p>
        <strong>PayCal Core</strong> contiene la lógica canónica de dominio y controladores:
        cálculos, validación, permisos, política de ciclo de vida y contratos de API compartidos.
      </p>
      <p>
        El Core permanece independiente de las extensiones por diseño. Los puntos de integración
        están aislados mediante contratos de puente para que los servicios centrales puedan
        probarse independientemente de los paquetes específicos de tiempo de ejecución.
      </p>
    </section>

    <section class="doc-section">
      <h2>Extensiones básicas incluidas en este repositorio</h2>
      <p>
        Este repositorio incluye <strong>implementaciones de extensiones básicas</strong> que
        proporcionan el comportamiento predeterminado para los puntos de extensión. Actúan como
        paquetes de referencia públicos y valores predeterminados seguros para despliegues auto-alojados.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> hooks de capacidad de facturación base y selección de modo</li>
        <li><strong>earnings-ytd:</strong> renderización base de YTD y puntos de hook de ganancias</li>
        <li><strong>organization-signals:</strong> hooks de señal de organización base</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Modelo de extensiones de terceros</h2>
      <p>
        Los terceros que usan este repositorio pueden crear y mantener sus propios paquetes
        de extensión. El modelo recomendado es:
      </p>
      <ol class="doc-list">
        <li>Mantener la lógica del Core sin modificar siempre que sea posible</li>
        <li>Implementar el comportamiento personalizado en los paquetes de extensión</li>
        <li>Vincular los paquetes personalizados a través del bootstrap de extensión documentado y los puntos de hook</li>
        <li>Preservar los contratos del Core para que las actualizaciones upstream sigan siendo manejables</li>
      </ol>
      <p>
        Esto permite despliegues competitivos y específicos de verticales sin forzar
        forks a largo plazo del código de dominio central.
      </p>
    </section>

    <section class="doc-section">
      <h2>Diferenciación de la plataforma canónica paycal.app</h2>
      <p>
        La plataforma canónica <code>https://paycal.app</code> ejecuta <strong>variantes de
        extensión privadas</strong> sobre el mismo Core y paradigma de extensiones básicas.
      </p>
      <p>
        Estas variantes privadas son una capa de diferenciación de producto deliberada para
        los entornos operados por PayCal. Pueden ajustar los flujos de trabajo, el comportamiento
        de las capacidades e integraciones específicas de la UI manteniendo la compatibilidad
        con la misma arquitectura central.
      </p>
      <ul class="doc-list">
        <li>La lógica del Core sigue siendo compartida y auditable</li>
        <li>Las extensiones públicas/básicas siguen disponibles en el repositorio</li>
        <li>Las extensiones privadas proporcionan la diferenciación de la plataforma canónica</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Compromisos de transparencia</h2>
      <ul class="doc-list">
        <li>Los contratos del Core están documentados y probados en los puntos de extensión</li>
        <li>Los límites de puente son explícitos para hacer que el acoplamiento sea descubrible</li>
        <li>El comportamiento de las extensiones puede evolucionar sin desestabilizar los servicios centrales</li>
        <li>Los adoptantes auto-alojados son libres de construir estrategias de extensión alternativas</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
