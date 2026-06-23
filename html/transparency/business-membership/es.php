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
    <span class="current">Conexiones empresariales y filosofía de roles</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Esta página explica la transición de la semántica de equipo ligeramente acoplada a
      Conexiones explícitas. Una conexión dice quién está vinculado con quién. Membresía, rol,
      consentimiento y acceso a datos protegidos son decisiones de política separadas.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Por qué existe este modelo</h2>
      <p>
        La colaboración en nómina tiene un impacto real en la seguridad. Un modelo de roles fácil
        de leer, probar y auditar es más seguro que un modelo construido a partir de comprobaciones
        puntuales dispersas.
      </p>
      <p>
        La conexión Business <strong>&lt;-&gt;</strong> Miembro le da a cada actor un vínculo de identidad
        explícito con un negocio. La membresía activa, la autoridad de rol, el consentimiento sobre
        datos protegidos y futuras concesiones entre personas permanecen separados de ese vínculo.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cambios en la conexión Business <strong>&lt;-&gt;</strong> Miembro</h2>
      <ul class="doc-list">
        <li>Las conexiones se representan explícitamente en lugar de inferirse del estado de la interfaz.</li>
        <li>Los estados del ciclo de vida — solicitud de acceso, invitación, aprobación, activación y revocación — son aplicados por la política del backend.</li>
        <li>Los paneles Business y las notificaciones ahora reflejan transiciones de conexión y resultados de rol de manera más consistente.</li>
        <li>El comportamiento compartido de Business está gobernado por membresía activa y política de rol antes de procesar acciones privilegiadas.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Conexión, membresía, consentimiento y concesiones</h2>
      <p>
        PayCal ahora trata estos conceptos como separados:
      </p>
      <ul class="doc-list">
        <li><strong>Conexión:</strong> un vínculo de identidad entre una persona y un negocio, o entre dos personas.</li>
        <li><strong>Membresía:</strong> el estado activo de participación en un negocio usado para colaborar en el workspace.</li>
        <li><strong>Consentimiento:</strong> la aprobación del miembro para compartir datos de trabajo protegidos.</li>
        <li><strong>Concesión:</strong> un permiso explícito, como vista delegada de calendario o una futura capacidad de recuperación confiable.</li>
      </ul>
      <p>
        Una conexión por sí sola no concede reportes protegidos, exportaciones, visibilidad de nómina,
        autoridad de recuperación ni la capacidad de actuar por otra persona.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cambios de roles y filosofía de roles actual</h2>
      <p>
        Los roles son impulsados por capacidades, con restricciones de alcance aplicadas por operación. La línea base actual:
      </p>
      <ul class="doc-list">
        <li><strong>propietario:</strong> control soberano que incluye transferencia de propiedad y acciones de gobernanza de alta confianza.</li>
        <li><strong>gerente:</strong> control operativo diario sin autoridad de transferencia de propiedad.</li>
        <li><strong>colaborador:</strong> operador de confianza con autoridad de escritura restringida por el alcance asignado.</li>
        <li><strong>miembro:</strong> participación limitada en autoservicio con derechos de mutación restringidos.</li>
        <li><strong>observador:</strong> visibilidad de solo lectura sin permisos de escritura.</li>
      </ul>
      <p>
        Favorecemos la composición explícita de capacidades y alcances sobre los indicadores de roles sobrecargados. Esto hace que los resultados de los roles sean más fáciles de probar y razonar.
      </p>
    </section>

    <section class="doc-section">
      <h2>Filosofía de seguridad y cifrado</h2>
      <p>
        La colaboración Business se intersecta con los controles de cifrado y consentimiento.
        Membresía activa, comprobaciones de rol y estado de consentimiento condicionan el comportamiento
        compartido del sobre Business para que las operaciones sensibles permanezcan vinculadas a la política.
      </p>
      <ul class="doc-list">
        <li>El estado de membresía y consentimiento se valida antes de proceder con operaciones seguras compartidas.</li>
        <li>Los cambios de rol y las transiciones de membresía se tratan como eventos relevantes para la seguridad, no solo eventos de UX.</li>
        <li>Los caminos de denegación de acceso son un comportamiento esperado ante una falta de concordancia de política y se exponen para la auditabilidad.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Filosofía operacional futura</h2>
      <ul class="doc-list">
        <li><strong>Fuente de política única:</strong> las decisiones de rol y alcance deben originarse en mapas de política de backend compartidos.</li>
        <li><strong>UI como proyección:</strong> las interfaces deben mostrar los resultados de política en lugar de duplicar la lógica de autorización.</li>
        <li><strong>Transiciones trazables:</strong> las aprobaciones, cambios de rol y revocaciones deben permanecer observables y revisables.</li>
        <li><strong>Transparencia en versiones:</strong> los cambios de comportamiento en membresía y roles están documentados en registros de cambios y páginas de transparencia.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
