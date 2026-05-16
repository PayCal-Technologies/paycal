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
$pageTitle = 'Fortalecimento de Auth, Passkey e Redis — Maio 2026 - [PayCal]';
$pageLabel = 'Fortalecimento de Auth, Passkey & Redis — Maio 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Fortalecimento de Auth, Passkey &amp; Redis — Maio 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Fortalecimento de Auth, Passkey &amp; Redis — Maio 2026</h1>
    <p class="deck">
      Em 12 de maio de 2026, realizamos uma auditoria interna de nossa infraestrutura de autenticação,
      passkey e Redis. Encontramos onze problemas — todos em código que nós mesmos escrevemos. Este
      artigo documenta o que encontramos, por que era importante e o que exatamente modificamos.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Resumo executivo</h2>
      <table class="doc-table" aria-label="Resumo executivo das descobertas da auditoria">
        <tbody>
          <tr>
            <td><strong>Data da auditoria</strong></td>
            <td>12 de maio de 2026</td>
          </tr>
          <tr>
            <td><strong>Escopo</strong></td>
            <td>Autenticação, passkey (WebAuthn) e infraestrutura Redis</td>
          </tr>
          <tr>
            <td><strong>Total de descobertas</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Distribuição por gravidade</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Status de remediação</strong></td>
            <td>Todas as descobertas resolvidas no commit <code>493d5e44</code>. Suite de testes completa aprovada. Nenhuma regressão.</td>
          </tr>
          <tr>
            <td><strong>Evidência de exploração</strong></td>
            <td>Nenhuma</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Por que estamos publicando isso</h2>
      <p>
        Encontramos esses problemas em nosso próprio código de aplicação e camadas de infraestrutura
        — não em dependências de terceiros ou serviços externos. Código que revisamos, commitamos e entregamos.
      </p>
      <p>
        Publicamos isso porque a transparência em segurança exige mais do que divulgar CVEs externos ou
        ser aprovado em auditorias. Significa ser publicamente responsável quando nossa própria equipe
        entrega código que não atende ao padrão que definimos para nós mesmos.
      </p>
      <p>
        Não nos envergonhamos disso. A falha mais grave teria sido descobrir esses problemas e optar por
        não divulgá-los.
      </p>
    </section>

    <section class="doc-section">
      <h2>Metodologia de auditoria</h2>
      <p>
        Esta auditoria foi realizada internamente pela equipe de engenharia em 12 de maio de 2026. A
        revisão cobriu todos os caminhos de código relacionados ao gerenciamento de estado de autenticação,
        ciclo de vida de credenciais WebAuthn e gerenciamento de chaves Redis.
      </p>
      <ul class="doc-list">
        <li><strong>Revisão manual de código</strong> de todos os arquivos de controlador, domínio e infraestrutura envolvidos na criação de sessão, registro de passkey, login de passkey e fluxos de recuperação de conta.</li>
        <li><strong>Análise estática</strong> via PHPStan Nível 9 — tolerância zero para caminhos de código inseguros de tipos ou inalcançáveis.</li>
        <li><strong>Modelagem de ameaças</strong> contra a especificação WebAuthn Nível 2 (§6.1 dados do autenticador, §7.1 cerimônia de registro, §7.2 cerimônia de autenticação).</li>
        <li><strong>Teste de regressão</strong> com a suite de regressão PHPUnit completa após remediação. Todos os testes aprovados.</li>
      </ul>
      <p>Nenhum auditor externo, relatório de bug bounty ou incidente de segurança precedeu esta revisão. Esses problemas foram identificados por meio de um processo interno de rotina.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Nossa filosofia de engenharia</h2>
      <p>Esta auditoria revelou falhas em três princípios que consideramos fundamentais:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomicidade antes da correção.</strong> Se duas operações precisam acontecer juntas,
          trate-as como uma única operação ou não tente o design. Um sistema que está &ldquo;correto na
          maioria das vezes&rdquo; não está correto.
        </li>
        <li>
          <strong>Defesa em camadas.</strong> Nenhum controle único deve ser a única barreira em um
          limite de segurança. Se o banco de dados marca uma credencial como revogada, o caminho de
          registro também deve impor isso. A defesa não deve ter lacunas entre as camadas.
        </li>
        <li>
          <strong>Assimetria de informações como objetivo de design.</strong> Um invasor que sonda o
          sistema deve aprender o mínimo possível sobre o que acontece internamente. Mensagens de erro,
          entradas de log e tempos de resposta são todas superfícies de exposição.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Descoberta 1 &mdash; <code>hset + expire</code> não atômico (Race Condition Redis) <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Redis / Atomicidade</strong></p>
      <p>
        Em nove pontos de chamada, um hash Redis era escrito com <code>HSET</code> e então
        imediatamente recebendo um TTL com um comando <code>EXPIRE</code> separado:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Essas são duas viagens de ida e volta separadas ao Redis. Se o processo PHP encerrar, for
        interrompido, atingir um timeout ou o Redis experimentar uma falha momentânea entre elas,
        o hash é escrito sem expiração — e vive indefinidamente no Redis.
      </p>
      <p>Os pontos de chamada afetados e suas implicações de segurança:</p>
      <table class="doc-table" aria-label="Pontos de chamada afetados para hset+expire não atômico">
        <thead>
          <tr>
            <th scope="col">Ponto de chamada</th>
            <th scope="col">Tipo de chave</th>
            <th scope="col">Consequência do TTL ausente</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Registro de sessão</td>
            <td>Sessão nunca expira — conta acessível além da vida útil pretendida</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Dados de challenge expirados persistem além da vida útil pretendida, aumentando o risco de replay</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Igual ao anterior</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Igual ao anterior</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Recovery passkey challenge</td>
            <td>Dados de sessão de recuperação nunca expiram</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (emissão de código)</td>
            <td>Código de email de recuperação</td>
            <td>Códigos de uso único sobrevivem além da janela de expiração pretendida</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (reenvio de código)</td>
            <td>Código de email de recuperação</td>
            <td>Igual ao anterior</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Tokens de administrador de uso único</td>
            <td>Tokens projetados para expirar em 5 minutos podem persistir indefinidamente</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Registro de transação de recuperação</td>
            <td>Estado de transação de recuperação nunca é limpo</td>
          </tr>
        </tbody>
      </table>
      <p>
        Para sessões, isso é uma violação direta do tempo de vida de acesso. Uma sessão deve ter
        um limite rígido. Se o TTL nunca for definido, esse limite não existe.
      </p>
      <p>
        Para tokens de capacidade de uso único, um token projetado para ser válido exatamente por
        300 segundos pode ainda ser válido dias depois.
      </p>
      <p><strong>A correção:</strong> Introduzimos <code>Database::hsetex()</code> — um wrapper que executa
      ambas as operações dentro de uma transação Redis <code>MULTI/EXEC</code>, tornando-as atômicas.
      As operações são executadas na mesma unidade de execução, portanto a chave não pode existir sem
      que seu TTL seja aplicado. A chave tem dados e TTL, ou nada.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Cada ponto de chamada que emitia um <code>hset</code> seguido por <code>expire</code> na mesma chave foi convertido.</p>
    </section>

    <section class="doc-section">
      <h2>Descoberta 2 &mdash; Logout e invalidação CSRF podiam falhar silenciosamente <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Redis / Logout, CSRF</strong></p>
      <p>
        O método <code>Database::del()</code> — responsável pela exclusão de chaves Redis por padrão
        — enumerava chaves usando a <em>réplica de leitura</em> e então emitia comandos <code>DEL</code>
        para o <em>primário</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        A replicação Redis é assíncrona. Se a réplica estiver atrasada — mesmo por milissegundos —
        ela pode ainda não conter a chave que acabou de ser escrita. Nesse caso, <code>keys()</code>
        retorna uma lista vazia e nenhum <code>DEL</code> é emitido para o primário. A chave sobrevive.
      </p>
      <p>Os dois chamadores mais críticos de <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — logout:</strong> Quando um usuário faz logout,
          excluímos sua chave de sessão. Se a réplica estiver atrasada, a listagem de chaves de
          sessão retorna vazia, a exclusão nunca é acionada e a sessão ainda existe no primário.
          O usuário acredita ter feito logout. Não fez.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — invalidação de nonce:</strong> Os tokens CSRF
          são nonces de uso único. Após o primeiro uso, devem ser excluídos. Se a exclusão nunca
          for acionada, o token pode ser reutilizado em uma segunda solicitação. De uso único torna-se reutilizável.
        </li>
      </ul>
      <p>
        Esse bug é sutil porque se manifesta apenas sob carga ou durante atraso temporário de réplica.
        No desenvolvimento contra uma única instância Redis, nunca é acionado.
      </p>
      <p><strong>A correção:</strong> A enumeração e exclusão de chaves devem apontar para a mesma instância.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Descoberta 3 &mdash; Bypass de verificação de usuário WebAuthn <span class="doc-badge high">High</span></h2>
      <p><strong>Categoria: Autenticação</strong></p>
      <p>
        Em <code>AccountRecoveryController</code>, ao registrar uma passkey como parte da recuperação
        de conta, a chamada para <code>processCreate()</code> passava <code>false</code> para
        <code>requireUserVerification</code>:
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
        O challenge enviado ao cliente especificava <code>userVerification: 'required'</code> — o
        autenticador foi instruído a exigir que o usuário completasse uma verificação biométrica ou
        PIN. Mas ao verificar a resposta, estávamos dizendo à biblioteca para não impor que o flag
        UV estivesse definido.
      </p>
      <p>
        Um cliente modificado poderia enviar uma resposta do autenticador com o bit UV limpo. Nosso
        servidor a aceitaria sem exigir que a verificação biométrica realmente tivesse ocorrido.
      </p>
      <p>
        O fluxo de recuperação de conta é o caminho que um usuário percorre quando perdeu o acesso
        às suas outras credenciais. Esta é a superfície de autenticação de maior risco que gerenciamos.
        Enfraquecer a imposição biométrica aqui é exatamente a troca errada.
      </p>
      <p><strong>A correção:</strong> UV agora é imposto. Uma resposta na qual os dados do autenticador não carregam o flag UV definido é rejeitada.</p>
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
      <h2>Descoberta 4 &mdash; Detecção de clone via contador de assinatura perdia ataques de replay <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticação</strong></p>
      <p>Nossa detecção de clone de passkey verificava:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        A especificação WebAuthn Nível 2 (§6.1) afirma: se o contador de assinatura armazenado for
        diferente de zero e o novo contador de assinatura não for <em>estritamente maior</em> do que
        o valor armazenado, a credencial deve ser considerada potencialmente clonada. Nossa condição
        exigia <code>&lt;</code>, não <code>&lt;=</code>, então um contador igual — como em um ataque
        de replay — passava sem acionar o flag de clone.
      </p>
      <p><strong>A correção:</strong> Alinhado com a especificação.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Descoberta 5 &mdash; Contador de assinatura nem sempre era persistido <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticação</strong></p>
      <p>Após um login de passkey bem-sucedido, a atualização do contador de assinatura era condicionada a ser diferente de zero:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Alguns autenticadores retornam <code>0</code> como sentinela significando &ldquo;este
        dispositivo não implementa um contador.&rdquo; Se um dispositivo depois começar a retornar
        um contador real (atualização de firmware, ou o usuário registra a mesma credencial em uma
        plataforma que suporta contadores), nunca teríamos persistido o primeiro contador real porque
        tínhamos armazenado <code>0</code> para sempre.
      </p>
      <p>
        Detecção de clone (Descoberta 4) requer que o contador armazenado seja diferente de zero;
        um autenticador que marcamos permanentemente como <code>0</code> está permanentemente excluído
        da proteção baseada em contadores.
      </p>
      <p><strong>A correção:</strong> O contador de assinatura é sempre escrito. O limiar de detecção de clone lida com a interpretação.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Descoberta 6 &mdash; Passkey revogada podia ser re-registrada <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Autenticação</strong></p>
      <p>
        Quando uma credencial era marcada como revogada (detecção de clone acionada), não havia
        verificação no caminho de registro impedindo o re-registro do mesmo <code>credential_id</code>.
        Um adversário com a credencial de passkey bruta e acesso à conta poderia re-registrar a
        credencial revogada, apagando seu histórico comprometido.
      </p>
      <p>
        A revogação é significativa apenas se for permanente. Se puder ser sobrescrita via re-registro
        usando a mesma credencial, a detecção de clone não fornece nenhuma proteção duradoura.
      </p>
      <p><strong>A correção:</strong> Se <code>revoked_at</code> não estiver vazio em um registro de credencial existente,
      o re-registro é bloqueado com HTTP 403 e uma entrada de log de segurança é escrita.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Descoberta 7 &mdash; Enumeração de conta via respostas de erro diferentes <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Divulgação de informações</strong></p>
      <p>
        Quando um login de passkey era tentado com um email não reconhecido, o corpo da resposta
        de erro assumia uma forma diferente de outros casos de falha — um payload de dados vazio
        <code>[]</code> versus o corpo <code>{'error': 'passkey_invalid'}</code> retornado em outros
        lugares. Um cliente que sondasse a API poderia distinguir &ldquo;este email não tem conta&rdquo;
        de &ldquo;este email existe mas o challenge falhou&rdquo; inspecionando o corpo da resposta.
      </p>
      <p>
        Além disso, o endereço de email bruto era escrito no log de observabilidade. Pipelines de
        agregação de log nunca devem conter endereços de email brutos de usuários — se o sistema
        de log for comprometido, cada tentativa de enumeração se torna uma lista de emails.
      </p>
      <p><strong>A correção:</strong> Tanto &ldquo;email não encontrado&rdquo; quanto &ldquo;nenhuma credencial registrada&rdquo;
      agora retornam o mesmo corpo de erro. O log de observabilidade registra apenas um hash SHA-256 do
      email — suficiente para correlação de incidentes, insuficiente para reconstruir o endereço.</p>
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
      <h2>Descoberta 8 &mdash; Estado do banco de dados de chave de recuperação escrito antes da confirmação de entrega de email <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Categoria: Integridade de dados</strong></p>
      <p>
        Durante a geração de chave de recuperação de conta, o servidor escrevia
        <code>recovery_key_generated = 1</code> e <code>recovery_proof_key</code> no registro do
        usuário <em>antes</em> de enviar o email da chave de recuperação:
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
        Se o email falhasse ao ser enviado, o banco de dados mostraria <code>recovery_key_generated = 1</code>
        — o sistema acredita que uma chave foi emitida. O usuário nunca a recebeu.
      </p>
      <p>
        Não há caminho de regeneração para um usuário nesse estado. A recuperação de conta fica
        permanentemente quebrada para aquela conta até intervenção manual.
      </p>
      <p><strong>A correção:</strong> A entrega de email é confirmada primeiro. O estado do banco de dados reflete o que realmente aconteceu.</p>
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
      <h2>Descoberta 9 &mdash; Caminho de registro desabilitado ainda coletava campos de senha <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Superfície de ataque</strong></p>
      <p>
        <code>RegistrationController</code> ainda lia <code>password</code> e
        <code>confirm_password</code> do POST mesmo com o registro baseado em senha desabilitado.
        O registro no PayCal é exclusivamente via passkey.
      </p>
      <p>
        Coletar campos que não servem a nenhum propósito não é inofensivo. Cada valor lido da entrada
        do usuário é uma superfície: pode ser registrado, auditado, acidentalmente passado para outras
        funções ou incluído em payloads de erro. O princípio da superfície mínima exige que não
        coletemos o que não utilizamos.
      </p>
      <p><strong>A correção:</strong> Ambos os campos foram removidos do mapa de coleta de entrada.</p>
    </section>

    <section class="doc-section">
      <h2>Descoberta 10 &mdash; Email do usuário na resposta 403 de verificação de email <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Divulgação de informações</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — o middleware que impõe a verificação de email antes
        de conceder acesso a recursos protegidos — incluía <code>user_email</code> no corpo da
        resposta 403:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Se um invasor obtiver um token de sessão válido mas não verificado (via session fixation ou
        um link temporário comprometido), ele pode aprender o endereço de email associado à conta
        a partir do corpo da resposta 403 — sem ter fornecido o email ele mesmo. A única parte que
        se beneficia do email neste payload de erro é alguém que tem o token de sessão mas não o email.
      </p>
      <p><strong>A correção:</strong> O campo email foi removido do payload de erro.</p>
    </section>

    <section class="doc-section">
      <h2>Descoberta 11 &mdash; Código morto em <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Categoria: Código morto / Superfície de ataque</strong></p>
      <p>
        <code>EmailGarum</code> continha um método de 90 linhas, <code>verifyNewUserEmail()</code>,
        que lidava com um fluxo de alteração de email baseado em senha. Esse fluxo foi substituído
        quando a plataforma migrou para autenticação exclusivamente via passkey. O método não era
        chamado em nenhum lugar no codebase.
      </p>
      <p>
        Código morto não é neutro. Ele ocupa espaço na superfície de revisão de segurança, na
        análise estática e na carga cognitiva de qualquer pessoa que leia o arquivo. Também apresenta
        o risco de que um desenvolvedor futuro, sem saber que foi intencionalmente abandonado, possa
        conectá-lo a um novo fluxo sem contexto completo.
      </p>
      <p><strong>A correção:</strong> Removido. Todos os locais de chamada foram confirmados vazios antes da remoção.</p>
    </section>

    <section class="doc-section">
      <h2>Resumo de todas as descobertas</h2>
      <table class="doc-table" aria-label="Resumo de todas as descobertas">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Descoberta</th>
            <th scope="col">Gravidade</th>
            <th scope="col">Categoria</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td><code>hset + expire</code> não atômico em 9 pontos de chamada</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicidade</td></tr>
          <tr><td>2</td><td><code>del()</code> usando réplica de leitura para enumeração de chaves</td><td><span class="doc-badge high">High</span></td><td>Redis / Logout, CSRF</td></tr>
          <tr><td>3</td><td>Bypass UV WebAuthn no registro de recuperação de conta</td><td><span class="doc-badge high">High</span></td><td>Autenticação</td></tr>
          <tr><td>4</td><td>Detecção de clone via contador de assinatura perdia ataques de replay</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticação</td></tr>
          <tr><td>5</td><td>Contador de assinatura não persistido quando autenticador retorna zero</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticação</td></tr>
          <tr><td>6</td><td>Passkey revogada podia ser re-registrada</td><td><span class="doc-badge medium">Medium</span></td><td>Autenticação</td></tr>
          <tr><td>7</td><td>Enumeração de conta via corpo de erro + email bruto em logs</td><td><span class="doc-badge medium">Medium</span></td><td>Divulgação de informações</td></tr>
          <tr><td>8</td><td>Estado do banco de dados de chave de recuperação escrito antes da confirmação de email</td><td><span class="doc-badge medium">Medium</span></td><td>Integridade de dados</td></tr>
          <tr><td>9</td><td>Registro desabilitado ainda coletava campos de senha</td><td><span class="doc-badge low">Low</span></td><td>Superfície de ataque</td></tr>
          <tr><td>10</td><td>Email do usuário na resposta 403 de verificação de email</td><td><span class="doc-badge low">Low</span></td><td>Divulgação de informações</td></tr>
          <tr><td>11</td><td>Método morto <code>verifyNewUserEmail()</code> em EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Código morto</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>O que fizemos certo</h2>
      <p>No interesse de um quadro completo — as bases já em vigor:</p>
      <ul class="doc-list">
        <li>
          <strong>Autenticação passkey-first.</strong> A plataforma opera em WebAuthn sem fallback
          de senha para usuários passkey. O bypass UV e os problemas de detecção de clone eram falhas
          dentro de uma arquitetura fundamentalmente sólida.
        </li>
        <li>
          <strong>Tokens de capacidade de uso único.</strong> Mutações em nível de administrador já
          exigiam tokens frescos e com limite de tempo. A correção de atomicidade reforçou uma proteção
          existente em vez de adicionar uma ausente.
        </li>
        <li>
          <strong>Log de segurança assinado.</strong> Cada evento de segurança — incluindo os novos
          eventos <code>passkey_revoked_reregistration_blocked</code> adicionados neste commit — é
          escrito em um log assinado, somente de adição, com campos estruturados.
        </li>
        <li>
          <strong>PHPStan no Nível 9.</strong> Todos os 11 arquivos modificados foram validados com
          rigor máximo de análise estática. A suite de regressão completa passou sem regressões.
        </li>
        <li>
          <strong>A detecção de clone existia.</strong> A lógica estava presente e parcialmente correta.
          A Descoberta 4 era um erro de condição de borda, não uma funcionalidade ausente.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Impacto nos clientes</h2>
      <ul class="doc-list">
        <li><strong>Nenhuma evidência de exploração.</strong> Todas as descobertas foram identificadas internamente via revisão de código de rotina. Nenhum relatório externo, CVE ou incidente precedeu esta divulgação.</li>
        <li><strong>Nenhuma exposição de credenciais em texto simples.</strong> Nenhuma senha ou chave de recuperação foi exposta. Dados de credenciais em repouso permanecem criptografados. Dados biométricos nunca saem do dispositivo autenticador e nunca são transmitidos ou armazenados pelo PayCal.</li>
        <li><strong>Nenhuma evidência de acesso não autorizado a contas.</strong> Logs de segurança não mostram padrões anômalos consistentes com exploração desses vetores.</li>
        <li><strong>Todas as descobertas remediadas antes da divulgação.</strong> Cada problema descrito neste artigo foi corrigido, commitado e testado antes desta página ser publicada.</li>
        <li><strong>Suite de regressão completa validada.</strong> Suite PHPUnit completa e análise estática PHPStan Nível 9 concluídas com êxito após remediação.</li>
        <li><strong>Monitoramento ampliado.</strong> Novos eventos de log de segurança foram adicionados para imposição de revogação de passkey (Descoberta 6) para detectar anomalias futuras mais cedo.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Prevenção e controles de recorrência</h2>
      <p>Duas regras de engenharia adotadas como política permanente a partir desta auditoria:</p>
      <div class="subject-example-cutout" role="note" aria-label="Nova regra de engenharia: hsetex como padrão de escrita Redis padrão">
        <h3><code>hsetex</code> é o padrão de escrita Redis padrão</h3>
        <p>
          Qualquer código futuro que precise escrever um hash com TTL deve usar
          <code>Database::hsetex()</code>. O antigo padrão de duas etapas não é mais permitido.
          Regras PHPStan serão escritas para sinalizar novas ocorrências.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Nova regra de engenharia: primazia da instância de escrita para todas as operações de chave">
        <h3>Primazia da instância de escrita para todas as operações de chave</h3>
        <p>
          Qualquer operação Redis cuja correção depende da releitura do que acabou de ser escrito
          deve usar a instância de escrita. As réplicas de leitura são apenas para consultas de
          leitura intensa não críticas.
        </p>
      </div>
      <p>
        Auto-auditorias nesse nível de especificidade são um compromisso contínuo. Continuaremos
        publicando o que encontramos. Relatórios futuros serão publicados no
        <a href="<?php echo transparency_href('/transparency/'); ?>">Hub de Transparência</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Cronograma de divulgação</h2>
      <table class="doc-table" aria-label="Cronograma de divulgação">
        <thead>
          <tr>
            <th scope="col">Data</th>
            <th scope="col">Evento</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 de maio de 2026</time></td>
            <td>Descobertas identificadas durante uma sessão de auditoria interna de rotina</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de maio de 2026</time></td>
            <td>Todas as correções implementadas e commitadas (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de maio de 2026</time></td>
            <td>Suite de regressão PHPUnit completa aprovada, PHPStan Nível 9 limpo</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de maio de 2026</time></td>
            <td>Enviado para origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 de maio de 2026</time></td>
            <td>Este artigo de transparência publicado</td>
          </tr>
        </tbody>
      </table>
      <p>
        Todas as descobertas foram identificadas internamente. Nenhum relatório externo, CVE ou
        violação precedeu esta divulgação. Não há evidências de que qualquer das descobertas tenha
        sido explorada.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
