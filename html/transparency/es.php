<?php
/**
 * Public Transparency Hub — Español
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

$readMoreLabel = 'Leer más';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Centro de transparencia - [PayCal]';
$pageLabel = 'Centro de transparencia';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Centro de transparencia</span>
    </nav>

    <header class="doc-article-header">
      <h1>Centro de transparencia</h1>
      <p class="deck">Publicamos cómo funciona PayCal para que los usuarios puedan verificar las decisiones, no solo confiar en las declaraciones.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Filosofía de la plataforma</h2>
        <p>PayCal está diseñado en torno a operaciones inspeccionables: las fórmulas están documentadas, los límites de telemetría son explícitos y la retención es finita por defecto.</p>
        <p>Nuestro principio es simple: si un sistema afecta a la nómina o a la privacidad, los usuarios deben poder entender cómo funciona y cómo se gobierna.</p>
        <p>La facturación de suscripciones es procesada por Stripe. El soporte de Stripe está disponible en <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>
        <p>Las actualizaciones recientes que han dado forma al producto, incluidos los flujos de facturación y gobernanza de perfil, se rastrean en nuestras páginas de framework/backend y gobernanza de pruebas.</p>
      </section>

      <div class="doc-panel-grid" aria-label="Paneles de detalle de transparencia">
        <section class="doc-section">
          <h2>Estado de la auditoría de seguridad</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Esta página publica el estado de auditoría actual, el alcance cerrado, las referencias de evidencia y los compromisos de bloqueo de versiones que preservan la postura de seguridad.</p>
          <ul class="doc-fact-list">
            <li>El estado del ciclo actual se publica con fecha de verificación y cadencia de revisión.</li>
            <li>La cobertura de alcance incluye controles de ciclo de vida de runtime, aislamiento de telemetría, gobernanza de correlación y endurecimiento de roles privilegiados.</li>
            <li>El snapshot de validación incluye Playwright, JS, PHPStan nivel 9 y resultados de pruebas backend.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Métricas de plataforma y privacidad</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La página de métricas explica la telemetría operacional recopilada para fiabilidad y planificación de capacidad.</p>
          <ul class="doc-fact-list">
            <li>Las claves de telemetría y ejemplos se publican para que las afirmaciones sean verificables.</li>
            <li>El alcance de recopilación es únicamente agregado, con límites estrictos y sin identificadores personales en las claves.</li>
            <li>La retención sigue un ciclo de vida definido: datos brutos, agregados y purga automática.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Accesibilidad y cumplimiento WCAG</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Utilizamos WCAG 2.1 nivel AA como estándar de accesibilidad de trabajo y publicamos el trabajo de accesibilidad reciente en lenguaje sencillo.</p>
          <ul class="doc-fact-list">
            <li>La navegación principal admite el uso del teclado, enlaces de salto y atajos de una sola tecla documentados para destinos principales.</li>
            <li>El manejo de atajos es seguro y no se activa mientras se escribe en campos editables o cuando hay diálogos abiertos.</li>
            <li>La cobertura de regresión reciente verifica encabezados, reflow/espaciado de texto, rutas de navegación y la entrega de comentarios de accesibilidad.</li>
            <li>Los bloqueadores de contraste estrictos a nivel de ruta en páginas públicas principales han sido corregidos; el trabajo de contraste en todo el tema continúa.</li>
            <li>Los usuarios pueden iniciar un informe de accesibilidad desde la página de accesibilidad y continuarlo a través del flujo de contacto seguro.</li>
            <li>La página de transparencia de accesibilidad ahora publica la fecha de última verificación, el alcance de verificación, las limitaciones conocidas y la próxima fecha de revisión.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Verificación y gobernanza</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Esta página documenta cómo PayCal aplica políticas a través de pruebas, hooks, límites de runtime y controles de seguridad.</p>
          <ul class="doc-fact-list">
            <li>Los hooks pre-commit y pre-push aplican PHPStan nivel 9 y rechazan los bypass de línea base.</li>
            <li>La CI ejecuta validación por etapas en trabajos de unidad, integración, contrato, orden aleatorio y cobertura.</li>
            <li>Los controles de runtime aplican límites de tasa, ventanas TTL y bloqueos de respuesta a abuso para flujos sensibles.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Capacidades de red</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Este artículo publica los protocolos de transporte y los controles de encabezados de respuesta utilizados para asegurar el comportamiento del navegador y la red.</p>
          <ul class="doc-fact-list">
            <li>Documenta la aplicación de HTTPS, la precarga HSTS y el anuncio de HTTP/3 (QUIC).</li>
            <li>Lista la línea base actual de encabezados de seguridad, incluidos CSP, COOP, COEP, CORP y encabezados de endurecimiento del navegador.</li>
            <li>Explica la negociación de protocolo y el comportamiento de fallback en clientes modernos.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Gobernanza de pruebas</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Este artículo documenta cómo ejecutamos la validación de backend, frontend y accesibilidad y qué puertas se tratan como bloqueadores de versión.</p>
          <ul class="doc-fact-list">
            <li>Muestra el inventario activo de suites PHPUnit y la división por categorías.</li>
            <li>Documenta los comandos de validación bloqueadores de versión usados en los sweeps <code>/mis</code>.</li>
            <li>Explica cómo la evidencia de prueba se sincroniza en changelogs y notas de fuente de verdad.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Gobernanza de dependencias y CI/CD</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Este artículo publica cómo se controlan las dependencias de npm y cómo se aplican las puertas de CI antes del lanzamiento.</p>
          <ul class="doc-fact-list">
            <li>Documenta la política npm lockfile-first y los requisitos de automatización <code>npm ci</code>.</li>
            <li>Mapea las puertas de calidad de JavaScript y las etapas del pipeline de backend a los controles de workflow.</li>
            <li>Lista las limitaciones de documentación conocidas y las mejoras de gobernanza planificadas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Registro de cambios de framework y backend</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Esta página rastrea la arquitectura de backend y los cambios a nivel de framework con explicaciones públicas de qué cambió y por qué.</p>
          <ul class="doc-fact-list">
            <li>Resume los cambios de servicio/controlador que afectan materialmente al comportamiento.</li>
            <li>Mapea los cambios de versión a los controles de seguridad y gobernanza.</li>
            <li>Incluye referencias al changelog detallado y artefactos de auditoría.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Experiencia de producto y cambios de facturación</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Las actualizaciones importantes de cuenta, facturación y flujos de perfil se explican junto con la gobernanza de backend y pruebas para que los usuarios puedan auditar tanto los cambios de UX como de comportamiento.</p>
          <ul class="doc-fact-list">
            <li>Rastrea el manejo de estado de facturación y cambios de contrato de estado de suscripción.</li>
            <li>Captura las salvaguardas para acciones destructivas, como frases explícitas de confirmación de eliminación de cuenta.</li>
            <li>Vincula las actualizaciones orientadas al producto con la evidencia de verificación y gobernanza de versiones.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Metodología fiscal</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>La página de impuestos documenta nuestras fórmulas, umbrales y ejemplos alineados con la CRA utilizados para estimaciones.</p>
          <ul class="doc-fact-list">
            <li>Las fórmulas de CPP, OAS, EI, impuesto federal/provincial y salario neto están documentadas con ejemplos resueltos.</li>
            <li>Los umbrales y tasas del año fiscal actual están publicados y vinculados a referencias de la CRA.</li>
            <li>La calidad del cálculo se valida con una suite de pruebas automatizada y actualizaciones anuales de tasas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Arquitectura de correo electrónico</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>La página de correo electrónico explica qué correos transaccionales envía PayCal, cómo se renderizan las plantillas y cómo se verifica la fiabilidad de entrega.</p>
          <ul class="doc-fact-list">
            <li>Las familias de plantillas específicas de flujo están documentadas para los caminos de verificación, recuperación, cambio de correo y soporte de contacto.</li>
            <li>Las responsabilidades de entrega están separadas entre la orquestación de EmailGarum y el manejo del protocolo SMTP de EmailTransport.</li>
            <li>Las pruebas en vivo opt-in para sweeps de plantillas y verificación de salud DKIM/DMARC están documentadas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Pruebas de carga de earnings</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Este artículo publica resultados de benchmarks A/B reproducibles para el renderizado anticipado versus la carga diferida de secciones en <code>/earnings/</code>.</p>
          <ul class="doc-fact-list">
            <li>Incluye una matriz de 10 ejecuciones para conjuntos de datos reales y sintéticos de 2025/2026.</li>
            <li>Informa DOMContentLoaded, el tiempo de preparación de sección y las compensaciones de llamadas de API.</li>
            <li>Documenta el método de prueba e interpretación para revisión pública.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Mapa de superhéroes</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>La página de Superhéroes documenta los componentes transversales temáticos de PayCal y el problema operacional específico que cada uno resuelve.</p>
          <ul class="doc-fact-list">
            <li>Incluye ShadowTalon, Guardian, Phantom Wing, Lens y EmailGarum.</li>
            <li>Explica dónde se usa cada componente y qué límite de riesgo protege.</li>
            <li>Proporciona anclas de verificación para que las afirmaciones de implementación puedan inspeccionarse directamente en el código y las pruebas.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
