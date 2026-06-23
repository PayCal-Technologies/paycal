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
    <span class="current">Membresía organizacional y filosofía de roles</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Esta página explica la transición de la semántica de equipo ligeramente acoplada a un modelo
      explícito de relación Organización <strong>&lt;-&gt;</strong> Miembro, la política de roles
      actual y los principios que usamos para mantener los permisos auditables y seguros.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
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
        La estructura Organización <strong>&lt;-&gt;</strong> Miembro le da a cada actor una relación
        explícita con una organización, con comportamiento de estado, rol y alcance respaldado por política.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cambios en la relación Organización <strong>&lt;-&gt;</strong> Miembro</h2>
      <ul class="doc-list">
        <li>La membresía se representa como una relación explícita en lugar de un estado de UI implícito.</li>
        <li>Los estados del ciclo de vida — solicitud de acceso, invitación, aprobación, activación y revocación — son aplicados por la política del backend.</li>
        <li>Los paneles de organización y las notificaciones ahora reflejan las transiciones de relación y los resultados de rol de manera más consistente.</li>
        <li>El comportamiento compartido de la organización está gobernado por el estado de membresía antes de que se procesen las acciones privilegiadas.</li>
      </ul>
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
        La colaboración organizacional se intersecta con los controles de cifrado y consentimiento.
        Las comprobaciones de membresía y roles condicionan el comportamiento compartido del sobre
        organizacional para que las operaciones sensibles permanezcan vinculadas a la política.
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
