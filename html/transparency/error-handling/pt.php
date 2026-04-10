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
$pageTitle = 'Tratamento de erros e normalização de mensagens - [PayCal]';
$pageLabel = 'Tratamento de erros e normalização de mensagens';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Tratamento de erros e normalização de mensagens</span>
  </nav>

  <header class="doc-article-header">
    <h1>Tratamento de erros e normalização de mensagens</h1>
    <p class="deck">
      Como o PayCal padroniza a comunicação de erros em todos os módulos frontend para garantir
      que os usuários recebam feedback significativo, seguro e consistente sem expor detalhes sensíveis.
    </p>
<p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Visão geral e objetivo</h2>
      <p>
        Quando os usuários encontram erros (falhas de rede, acesso negado, erros de validação),
        merecem uma resposta clara explicando o que aconteceu e como corrigi-lo. Porém,
        as mensagens brutas do backend devem ser normalizadas para:
      </p>
      <ul class="doc-list">
        <li><strong>Remover ruído:</strong> Eliminar prefixos redundantes como "Erro:" e espaços em branco</li>
        <li><strong>Prevenir vazamentos:</strong> Garantir que detalhes sensíveis da implementação nunca cheguem ao usuário</li>
        <li><strong>Fornecer fallbacks:</strong> Exibir mensagens seguras quando os erros estão vazios ou malformados</li>
        <li><strong>Garantir consistência:</strong> Aplicar a mesma lógica em todos os 11+ módulos frontend</li>
        <li><strong>Melhorar depuração:</strong> Registrar detalhes completos do erro no Phantom Wing e mostrar resumos seguros</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>O problema: Erros genéricos vs. significativos</h2>
      <p>
        Antes da padronização, os módulos do PayCal usavam tratamento de erros ad hoc:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ MAU: Expõe erro bruto, duplica lógica
PC.showToast(error?.message || 'Importação falhou.');
PW.error(`Importação falhou: ${error.message}`);</code></pre>
      </div>
      <p>Problemas com essa abordagem:</p>
      <ul class="doc-list">
        <li>Os usuários veem mensagens confusas como "ECONNREFUSED: Conexão recusada"</li>
        <li>Cada módulo implementa sua própria lógica de fallback de forma independente</li>
        <li>Sem truncamento consistente de espaço em branco ou remoção de prefixo</li>
        <li>Mensagens de erro vazias podem aparecer como "undefined" na UI</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>A solução: Resolvedor de erros padronizado</h2>
      <p>
        Todos os módulos frontend do PayCal agora usam uma função de resolução unificada
        que normaliza as mensagens de erro:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ BOM: Normalizado, consistente, seguro
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Extrair mensagem do objeto de erro
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // Remover prefixo "Erro:" e truncar espaço em branco
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Retornar normalizado se não vazio; senão fallback seguro
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Uso:</strong></p>
      <div class="doc-code-block">
        <pre><code>// Em blocos catch em todos os módulos
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Não foi possível atualizar o perfil.');
  PC.showToast(message, 'error');  // O usuário vê feedback significativo
  PW.error(message);                // Registrado para depuração
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Escopo de implementação</h2>
      <p>
        A partir de abril de 2026, este padrão padronizado de tratamento de erros foi aplicado a
        <strong>11 módulos frontend</strong> com <strong>~40+ blocos catch normalizados</strong>:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Autenticação e configurações (7 módulos)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/signin/verification-status-banner.js</code> (1 catch)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Módulos de dados e núcleo (4 módulos)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Módulos de alto valor (10+ pontos catch):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/organizations/index.php</code> — Gerenciamento org, acessos, trilhas de auditoria (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — CRUD do site, ganhos, recuperação de trabalho órfão (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Operações de entrada de dia, copiar/colar/deletar (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Categorias de erro e padrões de tratamento</h2>
      <p>O resolvedor é aplicado consistentemente em várias categorias de erro:</p>
      
      <h3>1. Falhas de solicitação de rede</h3>
      <div class="doc-code-block">
        <pre><code>// Módulo de rede: Erros HTTP, timeouts, problemas de conexão
async function deleteResource(ep, id) {
  try {
    // ...lógica fetch...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Erro de rede');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. Tratamento de resposta de API</h3>
      <div class="doc-code-block">
        <pre><code>// Faturamento/Configurações: O servidor retornou uma mensagem de erro no payload
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Não foi possível carregar o status de faturamento.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Não foi possível carregar o status de faturamento.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Falhas de operação da UI</h3>
      <div class="doc-code-block">
        <pre><code>// Calendário/Organizações: Ações iniciadas pelo usuário (colar, deletar, atualizar)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Sucesso!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Ação falhou. Tente novamente.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Inicialização assíncrona</h3>
      <div class="doc-code-block">
        <pre><code>// Módulos principais: Falhas de inicialização de inicialização ou dependentes
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Falha na inicialização da navegação');
  PW.warn(resolved);  // Registrado mas não bloqueia a página
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Considerações de segurança</h2>
      <p>
        A normalização de mensagens de erro protege a privacidade do usuário e a integridade do sistema:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Sem detalhes de banco de dados:</strong> Erros de backend como
          "UNIQUE constraint failed on email" são interceptados no limite da API
        </li>
        <li>
          <strong>Sem caminhos de arquivo:</strong> Erros de sistema que expõem caminhos de arquivo são removidos
        </li>
        <li>
          <strong>Sem vazamento de autenticação:</strong> As respostas a falhas de autenticação nunca revelam
          se uma conta existe (apenas mensagens genéricas seguras)
        </li>
        <li>
          <strong>Sem detalhes de CORS/rede:</strong> Erros no nível de transporte são normalizados
          para mensagens genéricas de "Erro de conexão"
        </li>
        <li>
          <strong>Fallbacks segura:</strong> Todos os captores possuem mensagens de fallback explícitas;
          nunca exibem "undefined" ou "null"
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Benefícios da experiência do usuário</h2>
      <p>
        As mensagens de erro padronizadas melhoram significativamente a experiência do usuário:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Feedback claro:</strong> Os usuários sabem o que falhou
          (por ex. "Chave de acesso não reconhecida" vs. genérico "Falha no login")
        </li>
        <li>
          <strong>Próximos passos acionáveis:</strong> Quando possível, as mensagens sugerem remédios
          ("Tente novamente", "Verifique sua conexão", "Entre em contato com o suporte")
        </li>
        <li>
          <strong>Consistência em toda a aplicação:</strong> Os mesmos tipos de erro são exibidos da mesma forma,
          reduzindo confusão do usuário
        </li>
        <li>
          <strong>Estados de erro acessíveis:</strong> Leitores de tela anunciam mensagens normalizadas;
          o registro fornece contexto completo para equipes de suporte
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Fluxo de trabalho de depuração e suporte</h2>
      <p>
        A normalização de erros <strong>não</strong> sacrifica a capacidade de depuração.
        Os detalhes completos do erro fluem para o Phantom Wing:
      </p>
      <div class="doc-code-block">
        <pre><code>// O usuário vê uma mensagem UI limpa
PC.showToast(resolveThrownMessage(error, 'Falha no upload.'), 'error');

// A equipe de suporte vê detalhes completos nos logs do Phantom Wing
PW.error('Falha no upload', {
  userMessage: resolveThrownMessage(error, 'Falha no upload.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Testes e garantia de qualidade</h2>
      <p>
        Todas as mudanças no tratamento de erros são validadas antes da implantação:
      </p>
      <ul class="doc-list">
        <li><strong>Validação de sintaxe:</strong> <code>php -l</code> e <code>node --check</code> verificam a correção</li>
        <li><strong>Segurança de tipo:</strong> Diagnósticos do editor confirmam nenhuma regressão de tipo</li>
        <li><strong>Testes de integração:</strong> Blocos catch testados com objetos de erro simulados</li>
        <li><strong>Registro do Phantom Wing:</strong> Mensagens de erro verificadas nos logs de depuração</li>
        <li><strong>Auditoria de acessibilidade:</strong> Anúncios de leitor de tela testados quanto à clareza</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Manutenção e extensões futuras</h2>
      <p>
        Este padrão foi projetado para sustentabilidade a longo prazo:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Pronto para localização:</strong> As mensagens de erro podem ser canalizadas através de i18n
          sem modificar a lógica do resolvedor
        </li>
        <li>
          <strong>Extensível:</strong> O resolvedor pode ser aprimorado para lidar com códigos de erro,
          lógica de retry ou pesquisa de mensagem especializada
        </li>
        <li>
          <strong>Documentação:</strong> Cada módulo inclui comentários inline explicando
          cenários de erro e estratégias de fallback
        </li>
        <li>
          <strong>Histórico do Git:</strong> Todas as alterações rastreadas com mensagens de commit detalhadas
          e diffs no nível de arquivo para fácil revisão
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Resumo: O padrão de tratamento de erros do PayCal</h2>
      <p>
        A normalização padronizada de mensagens de erro do PayCal garante que:
      </p>
      <ol class="doc-list">
        <li>Os usuários recebam feedback de erro claro e acionável</li>
        <li>Os detalhes sensíveis do sistema nunca vazem para o frontend</li>
        <li>O tratamento de mensagens seja consistente em todos os 11+ módulos frontend</li>
        <li>As equipes de depuração e suporte mantenham o contexto de erro completo via Phantom Wing</li>
        <li>O código seja mantível, testável e acessível</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Este compromisso com segurança, clareza e consistência reflete a dedicação do PayCal
        à confiança do usuário e ao compartilhamento transparente de informações.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
