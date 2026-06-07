<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Cumplimiento SOC 2 en PayCal - [PayCal]';
$pageLabel = 'Cumplimiento SOC 2 en PayCal';

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Ruta de navegación">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Centro de transparencia</a>
    <span class="separator">/</span>
    <span class="current">Cumplimiento SOC 2 en PayCal</span>
  </nav>

  <header class="doc-article-header">
    <h1>Preparación SOC 2 y modelo de seguridad de PayCal</h1>
    <p class="deck">Una vista técnica de cómo PayCal mapea los controles SOC 2 a comportamientos del sistema aplicados y evidencias generadas continuamente.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Visión general</h2>
      <p>PayCal opera un programa de seguridad alineado con SOC 2, centrado en la aplicación verificable y las evidencias rastreables, no en simples afirmaciones documentales.</p>
      <ul class="doc-fact-list">
        <li><strong>Controles en alcance:</strong> CC1-CC9</li>
        <li><strong>Artefactos en el bundle actual:</strong> 37</li>
        <li><strong>Mapeos control-artefacto:</strong> 26</li>
        <li><strong>Ventana de vigencia de evidencias:</strong> 35 días</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Cobertura de controles (CC1-CC9)</h2>
      <p>Todos los controles SOC 2 Common Criteria en alcance (CC1 a CC9) están mapeados a evidencias retenidas en el bundle mensual.</p>
      <p>Este mapeo admite trazabilidad directa del objetivo de control a los artefactos concretos utilizados para revisión.</p>
    </section>

    <section class="doc-section">
      <h2>3. Cómo se aplican los controles</h2>
      <p>PayCal trata la aplicación como una propiedad del sistema. Los controles se aplican programáticamente, no solo se documentan.</p>
      <ul class="doc-fact-list">
        <li><strong>Autenticación:</strong> Flujo de autenticación con soporte de llave de acceso para fortalecer la resistencia al phishing.</li>
        <li><strong>Integridad en tiempo de ejecución:</strong> Monitorización de integridad en tiempo de ejecución con gestión del estado operacional.</li>
        <li><strong>Endurecimiento de salidas:</strong> Controles de desinfección Guardian para rutas DOM/salida sensibles.</li>
        <li><strong>Barrera de calidad:</strong> Barrera PHPUnit de suite completa automatizada antes de que se acepten las evidencias del bundle.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Gestión de cambios &amp; Pruebas</h2>
      <p>La gobernanza de cambios está alineada con CC8 con cambios rastreados, aprobaciones y evidencias de pruebas.</p>
      <ul class="doc-fact-list">
        <li><strong>Registros de cambios:</strong> 12</li>
        <li><strong>Registros de aprobación:</strong> 10</li>
        <li><strong>Resultados de pruebas:</strong> 1528 pruebas, 8351 aserciones (aprobadas)</li>
        <li><strong>Traza prueba-control:</strong> 5 suites, 5 aprobadas, 8 archivos de prueba vinculados</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Registro de auditoría &amp; Integridad de evidencias</h2>
      <p>Los eventos administrativos y de seguridad en tiempo de ejecución se exportan con validación de registro inmutable para verificaciones de integridad.</p>
      <p><strong>Estado actual de integridad del registro:</strong> APROBADO.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Monitorización continua &amp; Vigencia</h2>
      <p>Las exportaciones de evidencias se ejecutan continuamente y se validan contra una política de vigencia determinista.</p>
      <p><strong>Resultado de vigencia actual:</strong> todos los artefactos mapeados están dentro de la ventana de auditoría de 35 días.</p>
    </section>

    <section class="doc-section">
      <h2>7. Estado actual</h2>
      <p><strong>Estado:</strong> Preparación SOC 2 en curso, con endurecimiento continuo de controles y actualizaciones deterministas de evidencias.</p>
      <p>PayCal no reclama certificación SOC 2 ni opinión de auditor en esta página. El acceso al informe formal permanece bajo NDA.</p>
    </section>

    <section class="doc-section">
      <h2>Fragmentos de cumplimiento reutilizables</h2>
      <p><strong>Insignia de pie de página:</strong> Preparación en curso • Controles mapeados • Monitorización continua de evidencias</p>
      <p><strong>Bloque de resumen:</strong> CC1-CC9 mapeados, 37 artefactos, 26 enlaces de control, integridad del registro aprobada y evidencias de pruebas de suite completa automatizada.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Referencias</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Resumen de control público depurado, narrativos deterministas y ruta de contacto de seguridad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">Resumen SOC 2 de PayCal</a>
          <span class="doc-ref-desc">Estado, métricas y acceso NDA para este informe.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">Solicitar informe SOC 2 (NDA)</a>
          <span class="doc-ref-desc">Acceso restringido para revisiones de due diligence de proveedores y seguridad.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Estándar oficial</a>
          <span class="doc-ref-desc">El marco de referencia que define los criterios SOC 2.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Descripción general del historial y alcance de los controles de sistemas y organizaciones.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Comunidad de Reddit</a>
          <span class="doc-ref-desc">Discusión de practicantes sobre auditorías SOC 2 y preparación.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
