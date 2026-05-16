<?php
/**
 * Public Transparency: Auth, Passkey, and Redis Hardening — May 2026
 *
 * PURPOSE: Disclose all findings from the May 12, 2026 internal security audit of
 * authentication, passkey, and Redis infrastructure. Describes each flaw, its
 * risk, and exactly how it was fixed.
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
$pageTitle = 'Fortalecimiento de Auth, Passkey y Redis — Mayo 2026 - [PayCal]';
$pageLabel = 'Fortalecimiento de Auth, Passkey & Redis — Mayo 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Fortalecimiento de Auth, Passkey &amp; Redis — Mayo 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Fortalecimiento de Auth, Passkey &amp; Redis — Mayo 2026</h1>
    <p class="deck">
      El 12 de mayo de 2026, realizamos una auditoría interna de nuestra infraestructura de
      autenticación, llave de acceso y Redis. Encontramos once problemas, todos en código que
      nosotros mismos escribimos. Este artículo documenta lo que encontramos, por qué importaba
      y exactamente qué cambiamos.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Resumen ejecutivo</h2>
      <table class="doc-table" aria-label="Resumen ejecutivo de los hallazgos de la auditoría">
        <tbody>
          <tr>
            <td><strong>Fecha de auditoría</strong></td>
            <td>12 de mayo de 2026</td>
          </tr>
          <tr>
            <td><strong>Alcance</strong></td>
            <td>Autenticación, llave de acceso (WebAuthn) e infraestructura Redis</td>
          </tr>
          <tr>
            <td><strong>Total de hallazgos</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Distribución por severidad</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Estado de remediación</strong></td>
            <td>Todos los hallazgos resueltos en el commit <code>493d5e44</code>. Suite de pruebas completa aprobada. Sin regresiones.</td>
          </tr>
          <tr>
            <td><strong>Evidencia de explotación</strong></td>
            <td>Ninguna</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Por qué publicamos esto</h2>
      <p>
        Encontramos estos problemas en nuestro propio código de aplicación y capas de infraestructura,
        no en dependencias de terceros ni en servicios externos. Código que revisamos, confirmamos
        y entregamos.
      </p>
      <p>
        Publicamos esto porque la transparencia en seguridad requiere más que divulgar CVEs externos
        o aprobar auditorías. Significa ser públicamente responsables cuando nuestro propio equipo
        entrega código que no cumple el estándar que nos hemos fijado.
      </p>
      <p>
        No estamos avergonzados por esto. El fallo más grave habría sido descubrir estos problemas
        y decidir no divulgarlos.
      </p>
    </section>

    <section class="doc-section">
      <h2>Metodología de auditoría</h2>
      <p>
        Esta auditoría fue realizada internamente por el equipo de ingeniería el 12 de mayo de 2026.
        La revisión cubrió todas las rutas de código relacionadas con la gestión del estado de
        autenticación, el ciclo de vida de credenciales WebAuthn y el manejo de claves Redis.
      </p>
      <ul class="doc-list">
        <li><strong>Revisión manual de código</strong> de todos los archivos de controlador, dominio e infraestructura involucrados en la creación de sesiones, registro de passkey, inicio de sesión con passkey y flujos de recuperación de cuenta.</li>
        <li><strong>Análisis estático</strong> mediante PHPStan Nivel 9 — tolerancia cero para rutas de código con tipos inseguros o inaccesibles.</li>
        <li><strong>Modelado de amenazas</strong> contra la especificación WebAuthn Nivel 2 (§6.1 datos del autenticador, §7.1 ceremonia de registro, §7.2 ceremonia de autenticación).</li>
        <li><strong>Pruebas de regresión</strong> con la suite de regresión completa de PHPUnit tras la remediación. Todas las pruebas aprobadas.</li>
      </ul>
      <p>Ningún auditor externo, informe de bug bounty o incidente de seguridad precedió a esta revisión. Estos problemas fueron identificados mediante un proceso interno rutinario.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Nuestra filosofía de ingeniería</h2>
      <p>Esta auditoría reveló fallos en tres principios que consideramos fundamentales:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomicidad ante corrección.</strong> Si dos operaciones deben ocurrir juntas,
          trátelas como una sola operación o no intente el diseño en absoluto. Un sistema que es
          &ldquo;correcto la mayor parte del tiempo&rdquo; no es correcto.
        </li>
        <li>
          <strong>Defensa en capas.</strong> Ningún control único debería ser la única barrera para
          un límite de seguridad. Si la base de datos marca un credential como revocado, la ruta de
          registro también debe aplicarlo. La defensa no debe tener huecos entre capas.
        </li>
        <li>
          <strong>Asimetría de información como objetivo de diseño.</strong> Un atacante que sondee
          el sistema debería aprender lo menos posible sobre lo que ocurre en su interior. Los mensajes
          de error, las entradas de registro y el tiempo de respuesta son todas superficies de exposición.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 1 &mdash; <code>hset + expire</code> no atómico (condición de carrera en Redis) <span class="doc-badge high">High</span></h2>
      <p><strong>Categoría: Redis / Atomicidad</strong></p>
      <p>
        En nueve puntos de llamada, un hash de Redis se escribía con <code>HSET</code> y luego
        inmediatamente se asignaba un TTL con un comando <code>EXPIRE</code> separado:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Esos son dos viajes de ida y vuelta separados a Redis. Si el proceso PHP finaliza, es
        interrumpido, supera un tiempo de espera, o Redis experimenta un fallo momentáneo entre
        ellos, el hash se escribe sin expiración — y vive indefinidamente en Redis.
      </p>
      <p>Los puntos de llamada afectados y sus implicaciones de seguridad:</p>
      <table class="doc-table" aria-label="Puntos de llamada afectados por hset+expire no atómico">
        <thead>
          <tr>
            <th scope="col">Punto de llamada</th>
            <th scope="col">Tipo de clave</th>
            <th scope="col">Consecuencia del TTL faltante</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Registro de sesión</td>
            <td>La sesión nunca expira — cuenta accesible más allá de su vida útil prevista</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge de inscripción)</td>
            <td>Challenge WebAuthn</td>
            <td>Los datos de challenge caducados persisten más allá de su vida útil prevista, aumentando el riesgo de repetición</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge de registro)</td>
            <td>Challenge WebAuthn</td>
            <td>Igual que el anterior</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (challenge de inicio de sesión)</td>
            <td>Challenge WebAuthn</td>
            <td>Igual que el anterior</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Challenge de passkey de recuperación</td>
            <td>Los datos de sesión de recuperación nunca expiran</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (emisión del código)</td>
            <td>Código de correo de recuperación</td>
            <td>Los códigos de un solo uso sobreviven más allá de su ventana de expiración prevista</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (reenvío del código)</td>
            <td>Código de correo de recuperación</td>
            <td>Igual que el anterior</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Tokens de administrador de un solo uso</td>
            <td>Los tokens diseñados para expirar en 5 minutos pueden sobrevivir indefinidamente</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Registro de transacción de recuperación</td>
            <td>El estado de transacción de recuperación nunca se limpia</td>
          </tr>
        </tbody>
      </table>
      <p>
        Para las sesiones, esto es una violación directa de la vida útil de acceso. Una sesión
        debería tener un límite estricto. Si el TTL nunca se establece, ese límite no existe.
      </p>
      <p>
        Para los tokens de capacidad de un solo uso, un token diseñado para ser válido exactamente
        300 segundos puede seguir siendo válido días después.
      </p>
      <p><strong>La corrección:</strong> Introdujimos <code>Database::hsetex()</code> — un wrapper que ejecuta
      ambas operaciones dentro de una transacción Redis <code>MULTI/EXEC</code>, haciéndolas atómicas.
      Las operaciones se ejecutan en la misma unidad de ejecución, de modo que la clave no puede existir
      sin que su TTL se aplique. La clave tiene datos y TTL, o no tiene nada.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Cada punto de llamada que emitía un <code>hset</code> seguido de <code>expire</code> en la misma clave fue convertido.</p>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 2 &mdash; El cierre de sesión y la invalidación CSRF podían fallar silenciosamente <span class="doc-badge high">High</span></h2>
      <p><strong>Categoría: Redis / Cierre de sesión, CSRF</strong></p>
      <p>
        El método <code>Database::del()</code> — responsable de eliminar claves Redis por patrón —
        enumeraba claves usando la <em>réplica de lectura</em> y luego emitía comandos
        <code>DEL</code> al <em>primario</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        La replicación Redis es asíncrona. Si la réplica está retrasada, incluso por milisegundos,
        puede no contener todavía la clave que acaba de escribirse. En ese caso, <code>keys()</code>
        devuelve una lista vacía y no se emite ningún <code>DEL</code> al primario. La clave sobrevive.
      </p>
      <p>Los dos invocadores más críticos de <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — cierre de sesión:</strong> Cuando un usuario cierra
          sesión, eliminamos su clave de sesión. Si la réplica está retrasada, la lista de claves de
          sesión devuelve vacía, la eliminación nunca se activa, y la sesión sigue existiendo en el
          primario. El usuario cree que ha cerrado sesión. No lo ha hecho.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — invalidación de nonce:</strong> Los tokens CSRF
          son nonces de un solo uso. Después de su primer uso, deben eliminarse. Si la eliminación
          nunca se activa, el token puede reutilizarse en una segunda solicitud. De un solo uso se
          convierte en reutilizable.
        </li>
      </ul>
      <p>
        Este error es sutil porque solo se manifiesta bajo carga o durante retraso temporal de la
        réplica. En desarrollo contra una sola instancia de Redis, nunca se activa.
      </p>
      <p><strong>La corrección:</strong> La enumeración y eliminación de claves deben apuntar a la misma instancia.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 3 &mdash; Bypass de verificación de usuario WebAuthn <span class="doc-badge high">High</span></h2>
      <p><strong>Categoría: Autenticación</strong></p>
      <p>
        En <code>AccountRecoveryController</code>, al registrar un passkey como parte de la
        recuperación de cuenta, la llamada a <code>processCreate()</code> pasaba <code>false</code>
        para <code>requireUserVerification</code>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    false,  // requireUserVerification — should be true
    true
);</code></pre>
      </div>
      <p>
        El challenge enviado al cliente especificaba <code>userVerification: 'required'</code> — se
        le decía al autenticador que el usuario debía completar una verificación biométrica o PIN.
        Pero al verificar la respuesta, le estábamos diciendo a la biblioteca que no aplicara que
        el flag UV estuviera establecido.
      </p>
      <p>
        Un cliente modificado podría enviar una respuesta del autenticador con el bit UV borrado.
        Nuestro servidor lo aceptaría sin exigir que la verificación biométrica hubiera ocurrido
        realmente.
      </p>
      <p>
        El flujo de recuperación de cuenta es el camino que toma un usuario cuando ha perdido el
        acceso a sus otros credenciales. Esta es la superficie de autenticación de mayor riesgo que
        operamos. Debilitar la aplicación biométrica aquí es exactamente el compromiso equivocado.
      </p>
      <p><strong>La corrección:</strong> UV ahora se aplica. Una respuesta donde los datos del autenticador no
      llevan el flag UV establecido es rechazada.</p>
      <div class="doc-code-block">
        <pre><code>// After — UV enforced
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    true,   // requireUserVerification — enforced
    true
);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 4 &mdash; La detección de clonación por contador de firma ignoraba los ataques de repetición <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoría: Autenticación</strong></p>
      <p>Nuestra detección de clonación de passkey verificaba:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        La especificación WebAuthn Nivel 2 (§6.1) establece: si el contador de firma almacenado no
        es cero y el nuevo contador de firma no es <em>estrictamente mayor</em> que el valor almacenado,
        el credential debe considerarse potencialmente clonado. Nuestra condición requería <code>&lt;</code>,
        no <code>&lt;=</code>, por lo que un contador igual — como en un ataque de repetición — pasaba
        sin activar el indicador de clonación.
      </p>
      <p><strong>La corrección:</strong> Alineada con la especificación.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 5 &mdash; El contador de firma no siempre se persistía <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoría: Autenticación</strong></p>
      <p>Después de un inicio de sesión exitoso con passkey, la actualización del contador de firma estaba condicionada a que no fuera cero:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Algunos autenticadores devuelven <code>0</code> como señal que significa &ldquo;este
        dispositivo no implementa un contador.&rdquo; Si un dispositivo luego comienza a devolver
        un contador real (actualización de firmware, o el usuario registra el mismo credential en
        una plataforma que soporte contadores), nunca persisteríamos el primer contador real porque
        habíamos almacenado <code>0</code> para siempre.
      </p>
      <p>
        La detección de clonación (Hallazgo 4) requiere que el contador almacenado no sea cero; un
        autenticador que etiquetamos permanentemente como <code>0</code> queda permanentemente excluido
        de la protección basada en contadores.
      </p>
      <p><strong>La corrección:</strong> El contador de firma siempre se escribe. El umbral de detección de clonación gestiona la interpretación.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 6 &mdash; Un passkey revocado podía volver a registrarse <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoría: Autenticación</strong></p>
      <p>
        Cuando un credential era marcado como revocado (detección de clonación activada), no había
        ninguna comprobación en la ruta de registro que impidiera volver a registrar el mismo
        <code>credential_id</code>. Un adversario con el credential de passkey en bruto y acceso
        a la cuenta podría volver a registrar el credential revocado, borrando su historial comprometido.
      </p>
      <p>
        La revocación solo es significativa si es permanente. Si puede sobrescribirse mediante
        un nuevo registro usando el mismo credential, la detección de clonación no proporciona
        ninguna protección duradera.
      </p>
      <p><strong>La corrección:</strong> Si <code>revoked_at</code> no está vacío en un registro de credential existente,
      el nuevo registro se bloquea con HTTP 403 y se escribe una entrada en el registro de seguridad.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 7 &mdash; Enumeración de cuentas mediante respuestas de error diferentes <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoría: Divulgación de información</strong></p>
      <p>
        Cuando se intentaba un inicio de sesión con passkey con un correo electrónico no reconocido,
        el cuerpo de la respuesta de error tomaba una forma diferente a los otros casos de fallo —
        un payload de datos vacío <code>[]</code> versus el cuerpo <code>{'error': 'passkey_invalid'}</code>
        devuelto en otros lugares. Un cliente sondeando la API podría distinguir &ldquo;este correo
        no tiene cuenta&rdquo; de &ldquo;este correo existe pero el challenge falló&rdquo; inspeccionando
        el cuerpo de la respuesta.
      </p>
      <p>
        Además, la dirección de correo electrónico sin procesar se escribía en el registro de
        observabilidad. Las canalizaciones de agregación de registros nunca deberían contener
        direcciones de correo electrónico de usuarios sin procesar — si el sistema de registros
        se compromete, cada intento de enumeración se convierte en una lista de correos electrónicos.
      </p>
      <p><strong>La corrección:</strong> Tanto &ldquo;correo no encontrado&rdquo; como &ldquo;sin credenciales registrados&rdquo;
      ahora devuelven el mismo cuerpo de error. El registro de observabilidad solo registra un hash SHA-256
      del correo electrónico — suficiente para la correlación de incidentes, insuficiente para reconstruir
      la dirección.</p>
      <div class="doc-code-block">
        <pre><code>// Before
Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

// After
Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 8 &mdash; Estado de clave de recuperación escrito antes de confirmar la entrega del correo <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoría: Integridad de datos</strong></p>
      <p>
        Al generar claves de recuperación de cuenta, el servidor escribía <code>recovery_key_generated = 1</code>
        y <code>recovery_proof_key</code> en el registro del usuario <em>antes</em> de enviar el
        correo electrónico de la clave de recuperación:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — DB written first, email second
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
]);
$sent = EmailGarum::sendRecoveryKeyEmail(...);</code></pre>
      </div>
      <p>
        Si el correo electrónico no se podía enviar, la base de datos mostraría
        <code>recovery_key_generated = 1</code> — el sistema cree que se emitió una clave.
        El usuario nunca la recibió.
      </p>
      <p>
        No hay una ruta de regeneración para un usuario en este estado. La recuperación de cuenta
        queda permanentemente rota para esa cuenta hasta una intervención manual.
      </p>
      <p><strong>La corrección:</strong> La entrega del correo electrónico se confirma primero. El estado de la base de datos refleja lo que realmente sucedió.</p>
      <div class="doc-code-block">
        <pre><code>// After — email first, then persist
$sent = EmailGarum::sendRecoveryKeyEmail(...);
if ($sent) {
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
    ]);
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 9 &mdash; La ruta de registro desactivada aún recopilaba campos de contraseña <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoría: Superficie de ataque</strong></p>
      <p>
        <code>RegistrationController</code> aún leía <code>password</code> y
        <code>confirm_password</code> desde POST aunque el registro basado en contraseña estaba
        desactivado. El registro de PayCal es exclusivamente mediante passkey.
      </p>
      <p>
        Recopilar campos que no sirven para nada no es inofensivo. Cada valor leído desde la entrada
        del usuario es una superficie: puede registrarse, auditarse, pasarse accidentalmente a otras
        funciones o incluirse en payloads de error. El principio de superficie mínima requiere que
        no recopilemos lo que no usamos.
      </p>
      <p><strong>La corrección:</strong> Ambos campos fueron eliminados del mapa de recopilación de entrada.</p>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 10 &mdash; Correo electrónico del usuario en la respuesta 403 de verificación de correo <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoría: Divulgación de información</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — el middleware que aplica la verificación de correo
        antes de conceder acceso a recursos protegidos — incluía <code>user_email</code> en el
        cuerpo de la respuesta 403:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Si un atacante obtiene un token de sesión válido pero no verificado (mediante fijación de
        sesión o un enlace temporal comprometido), puede conocer la dirección de correo electrónico
        asociada a la cuenta desde el cuerpo de la respuesta 403 — sin haber proporcionado el correo
        electrónico él mismo. La única parte que se beneficia del correo en este payload de error
        es alguien que tiene el token de sesión pero no el correo.
      </p>
      <p><strong>La corrección:</strong> El campo de correo electrónico fue eliminado del payload de error.</p>
    </section>

    <section class="doc-section">
      <h2>Hallazgo 11 &mdash; Código muerto en <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoría: Código muerto / Superficie de ataque</strong></p>
      <p>
        <code>EmailGarum</code> contenía un método de 90 líneas, <code>verifyNewUserEmail()</code>,
        que manejaba un flujo de cambio de correo electrónico basado en contraseña. Este flujo fue
        reemplazado cuando la plataforma pasó a autenticación exclusivamente mediante passkey. El
        método no era invocado en ningún lugar del código.
      </p>
      <p>
        El código muerto no es neutral. Ocupa espacio en la superficie de revisión de seguridad,
        en el análisis estático y en la carga cognitiva de cualquiera que lea el archivo. También
        presenta el riesgo de que un futuro desarrollador, desconociendo que fue abandonado
        intencionalmente, pueda conectarlo a un nuevo flujo sin contexto completo.
      </p>
      <p><strong>La corrección:</strong> Eliminado. Todos los puntos de llamada fueron confirmados como vacíos antes de la eliminación.</p>
    </section>

    <section class="doc-section">
      <h2>Resumen de todos los hallazgos</h2>
      <table class="doc-table" aria-label="Resumen de todos los hallazgos">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Hallazgo</th>
            <th scope="col">Severidad</th>
            <th scope="col">Categoría</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td><code>hset + expire</code> no atómico en 9 puntos de llamada</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicidad</td></tr>
          <tr><td>2</td><td><code>del()</code> usando réplica de lectura para enumeración de claves</td><td><span class="doc-badge high">High</span></td><td>Redis / Cierre de sesión, CSRF</td></tr>
          <tr><td>3</td><td>Bypass de UV WebAuthn en el registro de recuperación de cuenta</td><td><span class="doc-badge high">High</span></td><td>Autenticación</td></tr>
          <tr><td>4</td><td>Detección de clonación por contador de firma ignoraba ataques de repetición</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticación</td></tr>
          <tr><td>5</td><td>Contador de firma no persistido cuando el autenticador devuelve cero</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticación</td></tr>
          <tr><td>6</td><td>Passkey revocado podía volver a registrarse</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticación</td></tr>
          <tr><td>7</td><td>Enumeración de cuentas via cuerpo de error + correo sin procesar en registros</td><td><span class="doc-badge medium">Medium</span></td><td>Divulgación de información</td></tr>
          <tr><td>8</td><td>Estado BD de clave de recuperación escrito antes de confirmación del correo</td><td><span class="doc-badge medium">Medium</span></td><td>Integridad de datos</td></tr>
          <tr><td>9</td><td>Registro desactivado aún recopilaba campos de contraseña</td><td><span class="doc-badge low">Low</span></td><td>Superficie de ataque</td></tr>
          <tr><td>10</td><td>Correo del usuario en la respuesta 403 de verificación de correo</td><td><span class="doc-badge low">Low</span></td><td>Divulgación de información</td></tr>
          <tr><td>11</td><td>Método muerto <code>verifyNewUserEmail()</code> en EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Código muerto</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Lo que hicimos bien</h2>
      <p>En aras de un cuadro completo, las bases ya establecidas:</p>
      <ul class="doc-list">
        <li>
          <strong>Autenticación passkey-first.</strong> La plataforma funciona con WebAuthn sin
          fallback de contraseña para usuarios de passkey. El bypass de UV y los problemas de
          detección de clonación eran defectos dentro de una arquitectura fundamentalmente sólida.
        </li>
        <li>
          <strong>Tokens de capacidad de un solo uso.</strong> Las mutaciones a nivel administrador
          ya requerían tokens nuevos y limitados en el tiempo. La corrección de atomicidad fortaleció
          una protección existente en lugar de agregar una faltante.
        </li>
        <li>
          <strong>Registro de seguridad firmado.</strong> Cada evento de seguridad — incluidos los
          nuevos eventos <code>passkey_revoked_reregistration_blocked</code> añadidos en este commit —
          se escribe en un registro firmado, solo de adición, con campos estructurados.
        </li>
        <li>
          <strong>PHPStan en Nivel 9.</strong> Los 11 archivos modificados fueron validados con el
          máximo rigor de análisis estático. La suite de regresión completa pasó sin regresiones.
        </li>
        <li>
          <strong>La detección de clonación existía.</strong> La lógica estaba presente y era
          parcialmente correcta. El Hallazgo 4 fue un error de condición de borde, no una
          característica faltante.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Impacto en los clientes</h2>
      <ul class="doc-list">
        <li><strong>Sin evidencia de explotación.</strong> Todos los hallazgos fueron identificados internamente mediante revisión de código rutinaria. Ningún informe externo, CVE o incidente precedió a esta divulgación.</li>
        <li><strong>Sin exposición de credenciales en texto claro.</strong> No se expusieron contraseñas ni claves de recuperación. Los datos de credenciales en reposo permanecen cifrados. Los datos biométricos nunca salen del dispositivo autenticador y nunca son transmitidos ni almacenados por PayCal.</li>
        <li><strong>Sin evidencia de acceso no autorizado a cuentas.</strong> Los registros de seguridad no muestran patrones anómalos consistentes con la explotación de estos vectores.</li>
        <li><strong>Todos los hallazgos remediados antes de la divulgación.</strong> Cada problema descrito en este artículo fue corregido, confirmado y probado antes de que se publicara esta página.</li>
        <li><strong>Suite de regresión completa validada.</strong> Suite PHPUnit completa y análisis estático PHPStan Nivel 9 completados limpiamente después de la remediación.</li>
        <li><strong>Monitoreo ampliado.</strong> Se agregaron nuevos eventos de registro de seguridad para la aplicación de revocación de passkey (Hallazgo 6) para detectar anomalías futuras más temprano.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Controles de prevención y recurrencia</h2>
      <p>Dos reglas de ingeniería adoptadas como política permanente a partir de esta auditoría:</p>
      <div class="subject-example-cutout" role="note" aria-label="Nueva regla de ingeniería: hsetex como patrón de escritura Redis por defecto">
        <h3><code>hsetex</code> es el patrón de escritura Redis por defecto</h3>
        <p>
          Cualquier código futuro que necesite escribir un hash con un TTL debe usar
          <code>Database::hsetex()</code>. El antiguo patrón de dos pasos ya no está permitido.
          Se escribirán reglas de PHPStan para señalar nuevas ocurrencias.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Nueva regla de ingeniería: primacía de la instancia de escritura para todas las operaciones de clave">
        <h3>Primacía de la instancia de escritura para todas las operaciones de clave</h3>
        <p>
          Cualquier operación Redis cuya corrección dependa de releer lo que acaba de escribirse
          debe usar la instancia de escritura. Las réplicas de lectura son solo para consultas
          de alta lectura no críticas.
        </p>
      </div>
      <p>
        Las autoauditorías a este nivel de especificidad son un compromiso continuo. Seguiremos
        publicando lo que encontremos. Los informes futuros se publicarán en el
        <a href="<?php echo transparency_href('/transparency/'); ?>">Centro de transparencia</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cronología de divulgación</h2>
      <table class="doc-table" aria-label="Cronología de divulgación">
        <thead>
          <tr>
            <th scope="col">Fecha</th>
            <th scope="col">Evento</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 de mayo de 2026</time></td>
            <td>Hallazgos identificados durante una sesión de auditoría interna rutinaria</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de mayo de 2026</time></td>
            <td>Todas las correcciones implementadas y confirmadas (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de mayo de 2026</time></td>
            <td>Suite de regresión completa de PHPUnit aprobada, PHPStan Nivel 9 limpio</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de mayo de 2026</time></td>
            <td>Publicado en origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de mayo de 2026</time></td>
            <td>Este artículo de transparencia publicado</td>
          </tr>
        </tbody>
      </table>
      <p>
        Todos los hallazgos fueron identificados internamente. Ningún informe externo, CVE o
        brecha precedió a esta divulgación. No hay evidencia de que alguno de los hallazgos
        haya sido explotado.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
