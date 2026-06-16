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
  'TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Diagnósticos opcionais &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_DIAGNOSTICS_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      O PayCal inclui uma camada de diagnósticos opcional que você controla. Aqui está
      exatamente o que ela coleta, o que permanece no seu dispositivo e como é utilizada.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Visão geral</h2>
      <p>
        O PayCal vem com uma camada de diagnósticos integrada chamada <strong>Phantom Wing</strong>.
        Por padrão, ela está quase completamente silenciosa — captura apenas erros graves não
        tratados e nunca envia nada sem a sua ativação explícita.
      </p>
      <p>
        Se você encontrar um problema e quiser compartilhar mais contexto com o suporte, pode
        habilitar diagnósticos adicionais em
        <a href="/settings/diagnostics/">Configurações → Depuração (Opcional)</a>.
        Cada configuração é independente; você pode ativar apenas a relevante.
        As três estão <strong>Desativadas</strong> por padrão.
      </p>
    </section>

    <section class="doc-section">
      <h2>Os três controles de opt-in</h2>
      <p>
        Cada controle fica no painel <strong>Depuração (Opcional)</strong> na parte inferior
        da sua página de Configurações. Eles foram projetados apenas para solução de problemas
        — ativá-los pode deixar as interações de página ligeiramente mais lentas porque trabalho
        adicional é realizado no navegador.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Configuração</th>
            <th>O que ela ativa</th>
            <th>Quem a vê</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Mensagens do console</strong></td>
            <td>
              Emite avisos, logs informativos e marcadores de desempenho no console de
              desenvolvedor do seu navegador. Útil para autodiagnóstico — abra o DevTools e
              procure mensagens com o prefixo <code>[PayCal]</code> ou marcadores emoji.
            </td>
            <td>Somente você (seu console do navegador, nunca transmitido)</td>
          </tr>
          <tr>
            <td><strong>Diagnósticos detalhados</strong></td>
            <td>
              Ativa o registro interno de eventos passo a passo. O Phantom Wing captura o ciclo
              de vida completo das operações (carregamentos de calendário, envios de formulários,
              eventos de sessão) em um log em memória incluído em qualquer relatório de suporte
              que você escolher compartilhar.
            </td>
            <td>Somente você, a menos que compartilhe um relatório de suporte</td>
          </tr>
          <tr>
            <td><strong>Insights de rede</strong></td>
            <td>
              Registra os tempos de requisição de API — quanto tempo leva cada ida e volta ao
              servidor, os tamanhos de resposta e se batching ou cache foi aplicado. Ajuda a
              diagnosticar lentidão em operações específicas.
            </td>
            <td>Somente você (seu console do navegador, nunca transmitido)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>O que o Phantom Wing faz por padrão</h2>
      <p>
        Mesmo com os três controles desativados, o Phantom Wing executa um monitor base leve
        que captura apenas falhas graves:
      </p>
      <ul class="doc-list">
        <li>Exceções JavaScript não capturadas (<code>window.onerror</code>)</li>
        <li>Rejeições de promessas não tratadas</li>
        <li>Chamadas Fetch que falham com um erro de rede (não erros HTTP — esses são tratados por funcionalidade)</li>
      </ul>
      <p>
        Esses dados base permanecem inteiramente em memória e nunca são transmitidos para lugar
        algum. São exibidos em um resumo de um segundo no console do navegador ao carregar a
        página para que você possa ver rapidamente se algo deu errado, depois são descartados.
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
      <h2>Phantom Wing &amp; Telemetria</h2>
      <p>
        O Phantom Wing tem um canal de telemetria leve usado para medir a confiabilidade de
        funcionalidades de forma agregada — por exemplo, detectar se uma operação específica
        está falhando em uma taxa incomum na plataforma.
      </p>
      <h3>O que a telemetria envia</h3>
      <ul class="doc-list">
        <li>Contagens de eventos anonimizadas agrupadas por hora (ex.: <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Categoria e tipo do erro — nunca a mensagem de erro completa ou o stack trace</li>
        <li>Sem identificadores de usuário, sem tokens de sessão, sem endereços IP</li>
      </ul>
      <h3>O que a telemetria nunca envia</h3>
      <ul class="doc-list">
        <li>Seu nome, e-mail ou qualquer detalhe da conta</li>
        <li>Ganhos, período de pagamento ou dados financeiros</li>
        <li>Mensagens de erro completas ou stack traces</li>
        <li>Caminhos de URL ou strings de consulta</li>
        <li>Teclas digitadas ou valores de campos de formulário</li>
      </ul>
      <h3>Limitação de taxa &amp; recuo</h3>
      <p>
        As submissões de telemetria são limitadas no lado do servidor por usuário por minuto.
        Se o seu cliente exceder o limite, o servidor confirma silenciosamente e descarta o
        excesso — nada é armazenado. O cliente também aplica recuo exponencial: após dois
        falhos consecutivos no lado do servidor, desabilita automaticamente a submissão de
        telemetria por dez minutos.
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
      <h2>Redação de dados</h2>
      <p>
        Antes que qualquer valor seja armazenado em memória ou transmitido via telemetria, o
        Phantom Wing aplica uma passagem de redação automática. Valores que correspondem a
        padrões sensíveis conhecidos são substituídos por <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>Endereços de e-mail</li>
        <li>Tokens Bearer e valores do cabeçalho de autorização</li>
        <li>Tokens CSRF</li>
        <li>Strings que parecem chaves criptográficas ou blobs codificados em base64 acima de um comprimento mínimo</li>
      </ul>
      <p>
        A redação opera sobre todos os argumentos passados aos métodos de console interceptados
        e todos os valores de campo de telemetria antes do enfileiramento. Não pode ser
        contornada habilitando configurações de diagnóstico.
      </p>
    </section>

    <section class="doc-section">
      <h2>Guardas de escopo: páginas onde os diagnósticos são suprimidos</h2>
      <p>
        A submissão de telemetria é completamente suprimida nas páginas de autenticação
        (<code>/auth/</code>). Isso significa que mesmo se os Insights de rede estiverem
        ativados, nenhuma telemetria é transmitida enquanto você estiver nos fluxos de login,
        cadastro ou recuperação. Esta é uma medida de defesa em profundidade para evitar
        qualquer possibilidade de que dados adjacentes a credenciais apareçam em canais de
        diagnóstico.
      </p>
    </section>

    <section class="doc-section">
      <h2>Seu controle</h2>
      <p>
        As três configurações de diagnóstico são armazenadas como preferências de conta, não
        como cookies do navegador. Elas seguem sua conta em todos os dispositivos e sessões e
        estão <strong>Desativadas</strong> por padrão para cada conta — incluindo novas contas.
        Você pode alterá-las a qualquer momento em
        <a href="/settings/diagnostics/">Configurações → Depuração (Opcional)</a>.
      </p>
      <p>
        Desativar uma configuração tem efeito imediato no próximo carregamento de página.
        Nenhum dado de diagnóstico é retido entre sessões: o log em memória do Phantom Wing
        é apagado quando você navega para outro lugar ou fecha a aba.
      </p>
    </section>

    <section class="doc-section">
      <h2>Resumo</h2>
      <ol class="doc-list">
        <li>Todos os três controles de depuração estão <strong>Desativados</strong> por padrão e devem ser habilitados explicitamente por você</li>
        <li>Mensagens do console e Insights de rede nunca saem do seu dispositivo</li>
        <li>Diagnósticos detalhados permanecem em memória e só são compartilhados se você optar por compartilhar um relatório de suporte</li>
        <li>A telemetria envia apenas contagens de eventos anonimizadas e agregadas — zero dados pessoais</li>
        <li>Todos os valores são redatados antes do armazenamento ou transmissão, independentemente das configurações de diagnóstico</li>
        <li>A telemetria é completamente suprimida em todas as páginas de autenticação</li>
        <li>A limitação de taxa e o recuo automático do cliente evitam qualquer excesso de relatório acidental</li>
      </ol>
      <p class="doc-section-footer-note">
        O Phantom Wing é projetado para que você possa deixar todos os diagnósticos desativados
        indefinidamente. Os controles de opt-in existem para dar a você e à equipe de suporte
        uma linguagem compartilhada quando algo der errado — não para coletar dados por padrão.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
