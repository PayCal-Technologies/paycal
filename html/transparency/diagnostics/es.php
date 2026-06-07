<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
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
$pageTitle = 'Diagnósticos opcionales y Phantom Wing - [PayCal]';
$pageLabel = 'Diagnósticos opcionales y Phantom Wing';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Diagnósticos opcionales &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1>Diagnósticos opcionales &amp; Phantom Wing</h1>
    <p class="deck">
      PayCal incluye una capa de diagnósticos opcional que usted controla. Aquí se explica
      exactamente qué recopila, qué permanece en su dispositivo y cómo se utiliza.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Descripción general</h2>
      <p>
        PayCal incluye una capa de diagnósticos integrada llamada <strong>Phantom Wing</strong>.
        De forma predeterminada, está casi completamente en silencio — solo captura errores
        graves no controlados y nunca envía nada sin su activación explícita.
      </p>
      <p>
        Si tiene un problema y desea compartir más contexto con el soporte, puede activar
        diagnósticos adicionales en
        <a href="/settings/">Configuración → Depuración (Opcional)</a>.
        Cada configuración es independiente; puede activar solo la que sea relevante.
        Las tres están <strong>Desactivadas</strong> de forma predeterminada.
      </p>
    </section>

    <section class="doc-section">
      <h2>Los tres controles de activación voluntaria</h2>
      <p>
        Cada control se encuentra en el panel <strong>Depuración (Opcional)</strong> en la parte
        inferior de su página de Configuración. Están diseñados solo para solución de problemas
        — activarlos puede ralentizar ligeramente las interacciones de página porque se realiza
        trabajo adicional en el navegador.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Configuración</th>
            <th>Qué activa</th>
            <th>Quién lo ve</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Mensajes de consola</strong></td>
            <td>
              Emite advertencias, registros informativos y marcadores de rendimiento en la consola
              de desarrollador de su navegador. Útil para el autodiagnóstico — abra las DevTools
              y busque mensajes con el prefijo <code>[PayCal]</code> o marcadores emoji.
            </td>
            <td>Solo usted (su consola del navegador, nunca transmitido)</td>
          </tr>
          <tr>
            <td><strong>Diagnósticos detallados</strong></td>
            <td>
              Activa el registro de eventos interno paso a paso. Phantom Wing captura el ciclo de
              vida completo de las operaciones (cargas de calendario, envíos de formularios,
              eventos de sesión) en un registro en memoria que se incluye en cualquier informe
              de soporte que elija compartir.
            </td>
            <td>Solo usted, a menos que comparta un informe de soporte</td>
          </tr>
          <tr>
            <td><strong>Perspectivas de red</strong></td>
            <td>
              Registra los tiempos de solicitud de API — cuánto tarda cada ida y vuelta al
              servidor, los tamaños de respuesta y si se aplicó agrupación o almacenamiento en
              caché. Ayuda a diagnosticar la lentitud en operaciones específicas.
            </td>
            <td>Solo usted (su consola del navegador, nunca transmitido)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Qué hace Phantom Wing de forma predeterminada</h2>
      <p>
        Incluso con los tres controles desactivados, Phantom Wing ejecuta un monitor de base
        ligero que captura solo fallos graves:
      </p>
      <ul class="doc-list">
        <li>Excepciones de JavaScript no capturadas (<code>window.onerror</code>)</li>
        <li>Rechazos de promesa no controlados</li>
        <li>Llamadas Fetch que fallan con un error de red (no errores HTTP — esos se manejan por función)</li>
      </ul>
      <p>
        Estos datos de base permanecen completamente en memoria y nunca se transmiten a ningún
        lugar. Se muestran en un resumen de un segundo en la consola del navegador al cargar la
        página para que pueda ver rápidamente si algo salió mal y luego se descartan.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Telemetría</h2>
      <p>
        Phantom Wing tiene un canal de telemetría ligero utilizado para medir la fiabilidad de
        las funciones de forma agregada — por ejemplo, detectar si una operación en particular
        falla a una tasa inusual en toda la plataforma.
      </p>
      <h3>Qué envía la telemetría</h3>
      <ul class="doc-list">
        <li>Recuentos de eventos anonimizados agrupados por hora (p. ej., <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Categoría y tipo de error — nunca el mensaje de error completo ni el seguimiento de pila</li>
        <li>Sin identificadores de usuario, sin tokens de sesión, sin direcciones IP</li>
      </ul>
      <h3>Qué nunca envía la telemetría</h3>
      <ul class="doc-list">
        <li>Su nombre, correo electrónico o cualquier detalle de cuenta</li>
        <li>Ingresos, período de pago o datos financieros</li>
        <li>Mensajes de error completos o seguimientos de pila</li>
        <li>Rutas de URL o cadenas de consulta</li>
        <li>Pulsaciones de teclas o valores de campos de formulario</li>
      </ul>
      <h3>Limitación de velocidad &amp; retroceso</h3>
      <p>
        Las entregas de telemetría están limitadas del lado del servidor por usuario por minuto.
        Si su cliente supera el umbral, el servidor confirma silenciosamente y descarta el exceso
        — nada se almacena. El cliente también aplica retroceso exponencial: después de dos fallos
        consecutivos del lado del servidor, deshabilita automáticamente la entrega de telemetría
        durante diez minutos.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Redacción de datos</h2>
      <p>
        Antes de que cualquier valor se almacene en memoria o se transmita a través de la
        telemetría, Phantom Wing aplica un pase de redacción automática. Los valores que
        coinciden con patrones sensibles conocidos se reemplazan por <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>Direcciones de correo electrónico</li>
        <li>Tokens Bearer y valores de encabezado de autorización</li>
        <li>Tokens CSRF</li>
        <li>Cadenas que parecen claves criptográficas o blobs codificados en base64 por encima de una longitud mínima</li>
      </ul>
      <p>
        La redacción opera sobre todos los argumentos pasados a los métodos de consola
        interceptados y todos los valores de campo de telemetría antes de la cola. No puede
        omitirse habilitando la configuración de diagnóstico.
      </p>
    </section>

    <section class="doc-section">
      <h2>Guardias de ámbito: páginas donde se suprime el diagnóstico</h2>
      <p>
        La entrega de telemetría está completamente suprimida en las páginas de autenticación
        (<code>/auth/</code>). Esto significa que incluso si las Perspectivas de red están
        activadas, no se transmite telemetría mientras se encuentra en los flujos de inicio de
        sesión, registro o recuperación. Esta es una medida de defensa en profundidad para
        evitar cualquier posibilidad de que datos adyacentes a credenciales aparezcan en canales
        de diagnóstico.
      </p>
    </section>

    <section class="doc-section">
      <h2>Su control</h2>
      <p>
        Las tres configuraciones de diagnóstico se almacenan como preferencias de cuenta, no
        como cookies del navegador. Siguen su cuenta en todos los dispositivos y sesiones y
        están <strong>Desactivadas</strong> de forma predeterminada para cada cuenta —
        incluidas las nuevas. Puede cambiarlas en cualquier momento en
        <a href="/settings/">Configuración → Depuración (Opcional)</a>.
      </p>
      <p>
        Desactivar una configuración tiene efecto inmediato en la siguiente carga de página.
        No se retienen datos de diagnóstico entre sesiones: el registro en memoria de
        Phantom Wing se borra cuando navega a otro lugar o cierra la pestaña.
      </p>
    </section>

    <section class="doc-section">
      <h2>Resumen</h2>
      <ol class="doc-list">
        <li>Los tres controles de depuración están <strong>Desactivados</strong> de forma predeterminada y deben ser activados explícitamente por usted</li>
        <li>Los Mensajes de consola y las Perspectivas de red nunca abandonan su dispositivo</li>
        <li>Los Diagnósticos detallados permanecen en memoria y solo se comparten si elige compartir un informe de soporte</li>
        <li>La telemetría envía solo recuentos de eventos anonimizados y agregados — cero datos personales</li>
        <li>Todos los valores son redactados antes del almacenamiento o la transmisión, independientemente de la configuración de diagnóstico</li>
        <li>La telemetría está completamente suprimida en todas las páginas de autenticación</li>
        <li>La limitación de velocidad y el retroceso automático del cliente evitan cualquier exceso de reporte accidental</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Phantom Wing está diseñado para que pueda dejar todos los diagnósticos desactivados
        indefinidamente. Los controles de activación existen para dar a usted y al equipo de
        soporte un lenguaje compartido cuando algo sale mal — no para recopilar datos de
        forma predeterminada.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
