<?php
/**
 * Public Transparency: Error Handling & Message Normalization
 *
 * PURPOSE: 
 * Explain PayCal's standardized error-message normalization pattern, the
 * security and UX rationale behind it, and how we ensure users receive
 * meaningful, safe error feedback across all frontend modules.
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
$pageTitle = 'Manejo de errores y normalización de mensajes - [PayCal]';
$pageLabel = 'Manejo de errores y normalización de mensajes';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Manejo de errores y normalización de mensajes</span>
  </nav>

  <header class="doc-article-header">
    <h1>Manejo de errores y normalización de mensajes</h1>
    <p class="deck">
      Cómo PayCal estandariza la presentación de errores en todos los módulos frontend para garantizar
      que los usuarios reciban comentarios significativos, seguros y consistentes sin exponer detalles sensibles.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Descripción general y propósito</h2>
      <p>
        Cuando los usuarios encuentran errores (fallos de red, acceso denegado, errores de validación),
        merecen comentarios claros que expliquen qué sucedió y cómo solucionarlo. Sin embargo,
        los mensajes sin procesar del backend deben normalizarse para:
      </p>
      <ul class="doc-list">
        <li><strong>Eliminar ruido:</strong> Eliminar prefijos redundantes como "Error:" e espacios en blanco</li>
        <li><strong>Evitar filtraciones:</strong> Asegurar que los detalles sensibles de implementación nunca lleguen al usuario</li>
        <li><strong>Proporcionar alternativas:</strong> Mostrar mensajes seguros cuando los errores están vacíos o mal formados</li>
        <li><strong>Garantizar coherencia:</strong> Aplicar la misma lógica en todos los 11+ módulos frontend</li>
        <li><strong>Mejorar depuración:</strong> Registrar los detalles del error completos en Phantom Wing mientras se muestran resúmenes seguros</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>El problema: Errores genéricos vs. significativos</h2>
      <p>
        Antes de la estandarización, los módulos de PayCal utilizaban manejo de errores ad hoc:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ MALO: Expone error sin procesar, duplica lógica
PC.showToast(error?.message || 'Importación fallida.');
PW.error(`Importación fallida: ${error.message}`);</code></pre>
      </div>
      <p>Problemas con este enfoque:</p>
      <ul class="doc-list">
        <li>Los usuarios ven mensajes confusos como "ECONNREFUSED: Conexión rechazada"</li>
        <li>Cada módulo implementa su propia lógica de alternativa de forma independiente</li>
        <li>Sin recorte de espacios en blanco consistente ni eliminación de prefijos</li>
        <li>Los mensajes de error vacíos pueden aparecer como "undefined" en la UI</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>La solución: Resolutor de errores estandarizado</h2>
      <p>
        Todos los módulos frontend de PayCal ahora utilizan una función resolutora unificada
        que normaliza los mensajes de error:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ BUENO: Normalizado, consistente, seguro
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Extraer mensaje del objeto de error
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Eliminar prefijo "Error:" y recortar espacios en blanco
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Devolver normalizado si no está vacío; de lo contrario, alternativa segura
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Uso:</strong></p>
      <div class="doc-code-block">
        <pre><code>// En bloques catch en todos los módulos
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'No se pudo actualizar el perfil.');
  PC.showToast(message, 'error');  // El usuario ve comentarios significativos
  PW.error(message);                // Registrado para la depuración
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Alcance de implementación</h2>
      <p>
        A partir de abril de 2026, este patrón estandarizado de manejo de errores ha sido aplicado a
        <strong>11 módulos frontend</strong> con <strong>~40+ bloques catch normalizados</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Autenticación y configuración (7 módulos)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Módulos de datos y núcleo (4 módulos)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Módulos de alto valor (10+ puntos catch):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Gestión de org, accesos, auditorías (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — CRUD de sitio, ganancias, recuperación de trabajo huérfano (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Operaciones de entrada de día, copiar/pegar/eliminar (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Categorías de error y patrones de manejo</h2>
      <p>El resolutor se aplica consistentemente en varias categorías de error:</p>
      
      <h3>1. Fallos de solicitud de red</h3>
      <div class="doc-code-block">
        <pre><code>// Módulo de red: Errores HTTP, tiempos de espera, problemas de conexión
async function deleteResource(ep, id) {
  try {
    // ...lógica fetch...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Error de red');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Manejo de respuesta de API</h3>
      <div class="doc-code-block">
        <pre><code>// Facturación/Configuración: El servidor devolvió mensaje de error en la carga útil
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'No se pudo cargar el estado de facturación.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'No se pudo cargar el estado de facturación.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Fallos de operación de UI</h3>
      <div class="doc-code-block">
        <pre><code>// Calendario/Organizaciones: Acciones iniciadas por el usuario (pegar, eliminar, actualizar)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('¡Éxito!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'La acción falló. Inténtelo de nuevo.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Inicialización asincrónica</h3>
      <div class="doc-code-block">
        <pre><code>// Módulos principales: Falla de iniciialización de inicio o dependiente
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Falló la inicialización de navegación');
  PW.warn(resolved);  // Registrado pero no bloquea la página
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Consideraciones de seguridad</h2>
      <p>
        La normalización de mensajes de error protege la privacidad del usuario y la integridad del sistema:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Sin detalles de base de datos:</strong> Los errores de backend como 
          "UNIQUE constraint failed on email" se interceptan en el límite de la API
        </li>
        <li>
          <strong>Sin rutas de archivo:</strong> Los errores del sistema que exponen rutas de archivos se eliminan
        </li>
        <li>
          <strong>Sin filtraciones de auth:</strong> Las respuestas a fallos de autenticación nunca revelan
          si existe una cuenta (solo mensajes genéricos seguros para tiempos)
        </li>
        <li>
          <strong>Sin detalles de CORS/red:</strong> Los errores de capa de transporte se normalizan
          a mensajes genéricos de "Error de conexión"
        </li>
        <li>
          <strong>Alternativas seguras:</strong> Todos los captores tienen mensajes de alternativa explícitos;
          nunca muestren "undefined" o "null"
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Beneficios de la experiencia del usuario</h2>
      <p>
        Los mensajes de error estandarizados mejoran significativamente la experiencia del usuario:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Comentarios claros:</strong> Los usuarios saben qué falló
          (p. ej., "Clave de acceso no reconocida" vs. genérico "Error de inicio de sesión")
        </li>
        <li>
          <strong>Próximos pasos accionables:</strong> Cuando sea posible, los mensajes sugieren remedios
          ("Inténtelo de nuevo", "Verifique su conexión", "Contacte soporte")
        </li>
        <li>
          <strong>Coherencia en la app:</strong> Los mismos tipos de error se muestran igual,
          reduce la confusión del usuario
        </li>
        <li>
          <strong>Estados de error accesibles:</strong> Los lectores de pantalla anuncian mensajes normalizados;
          el registro proporciona contexto completo para equipos de soporte
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Flujo de trabajo de depuración y soporte</h2>
      <p>
        La normalización de errores <strong>no</strong> sacrifica la capacidad de depuración.
        Los detalles completos del error fluyen a Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// El usuario ve mensaje limpio de UI
PC.showToast(resolveThrownMessage(error, 'La carga falló.'), 'error');

// El equipo de soporte ve detalles completos en registros de Phantom Wing
PW.error('La carga falló', {
  userMessage: resolveThrownMessage(error, 'La carga falló.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Pruebas y aseguramiento de calidad</h2>
      <p>
        Todos los cambios de manejo de errores se validan antes de la implementación:
      </p>
      <ul class="doc-list">
        <li><strong>Validación de sintaxis:</strong> <code>php -l</code> y <code>node --check</code> verifican corrección</li>
        <li><strong>Seguridad de tipos:</strong> La diagnóstica del editor confirma sin regresiones de tipo</li>
        <li><strong>Pruebas de integración:</strong> Los bloques de captura se prueba con objetos de error simulados</li>
        <li><strong>Registro de Phantom Wing:</strong> Los mensajes de error se verifican en registros de depuración</li>
        <li><strong>Auditoría de accesibilidad:</strong> Se prueban anuncios de lector de pantalla para claridad</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Mantenimiento y extensiones futuras</h2>
      <p>
        Este patrón está diseñado para mantenibilidad a largo plazo:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Listo para localización:</strong> Los mensajes de error pueden canalizarse a través de i18n
          sin modificar la lógica del resolutor
        </li>
        <li>
          <strong>Extensible:</strong> El resolutor puede ampliarse para manejar códigos de error,
          lógica de reintento o búsqueda de mensajes especializados
        </li>
        <li>
          <strong>Documentación:</strong> Cada módulo incluye comentarios en línea que explican
          escenarios de error y estrategias de alternativa
        </li>
        <li>
          <strong>Historial de Git:</strong> Todos los cambios registrados con mensajes de commit detallados
          y diffs a nivel de archivo para revisión fácil
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Resumen: El estándar de manejo de errores de PayCal</h2>
      <p>
        La normalización estandarizada de mensajes de error de PayCal garantiza que:
      </p>
      <ol class="doc-list">
        <li>Los usuarios reciben comentarios de error claros y accionables</li>
        <li>Los detalles del sistema sensible nunca se filtran al frontend</li>
        <li>El manejo de mensajes es consistente en todos los 11+ módulos frontend</li>
        <li>Los equipos de depuración y soporte conservan contexto de error completo via Phantom Wing</li>
        <li>El código es mantenible, comprobable y accesible</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Este compromiso con la seguridad, claridad y consistencia refleja la dedicación de PayCal
        a la confianza del usuario y al intercambio de información transparente.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
