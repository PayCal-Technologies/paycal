---
title: Relatório de transparência de segurança de aplicações
date: 2026-03-31
author: PayCal Segurança
tags: security, appsec, billing_hardening
---

## Metadados do relatório

◆ Data: 2026-03-31
◆ Âmbito: Tratamento de pedidos, redirecionamentos, proteções de API e limites de confiança
◆ Referência: Auditoria de segurança interna (2026-03-31)

## Visão geral

Concluímos recentemente uma revisão de segurança de aplicações focada em vetores de ataque do mundo real que afetam aplicações web modernas. Este esforço priorizou a **redução prática de riscos** sem perturbar o comportamento normal do produto.

Este documento descreve o que foi identificado, o que foi alterado e como abordamos a segurança contínua.

### Evento desencadeador e relatórios externos

Fomos alertados hoje por relatórios confirmados sobre o comprometimento do pacote npm Axios. Esse alerta desencadeou diretamente este ciclo completo de auditoria e varredura interna do sistema.

Referências técnicas externas:
◆ BleepingComputer: [Hackers comprometem pacote npm Axios para distribuir malware multiplataforma](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Ataque à cadeia de fornecimento do Axios distribui RAT multiplataforma através de conta npm comprometida](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Explosão na cadeia de fornecimento: pacote npm popular com backdoor para instalar um RAT](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Principais conclusões

Identificámos e remediámos três riscos de segurança significativos:

◆ Gestão de redirecionamentos: vetor de redirecionamento aberto (corrigido)
◆ Confiança em cabeçalhos: envenenamento Host/cabeçalho (corrigido)
◆ Proteção de API: verificações CSRF em falta (corrigidas)

## O que corrigimos

### 1) Segurança em redirecionamentos (mudança de idioma)

**Problema**
Os redirecionamentos dependiam de `HTTP_REFERER`, que pode estar ausente ou ser manipulado. Isto cria potenciais cadeias de phishing usando domínios de confiança.

**Resolução**
◆ Validação rigorosa do host aplicada
◆ Apenas redirecionamentos internos ou da mesma origem são permitidos
◆ Retorno padrão a `/` quando a validação falha

**Resultado**
Os redirecionamentos estão agora **explicitamente limitados a origens de confiança**.

### 2) Limites de confiança de cabeçalhos (fluxos de faturação)

**Problema**
Os cabeçalhos encaminhados (ex. host/proto) influenciavam a lógica de origem sem verificar a fonte do pedido. Uma configuração incorreta poderia permitir a manipulação do host.

**Resolução**
◆ Introduzido o **controlo de proxy confiável**
◆ Cabeçalhos encaminhados apenas são aceites de infraestrutura conhecida
◆ Todos os outros casos regressam à origem canónica da aplicação

**Resultado**
O tratamento da origem é agora **determinístico e resistente ao spoofing de cabeçalhos**.

### 3) Proteção CSRF (ações de faturação)

**Problema**
Os endpoints de faturação autenticados careciam de validação CSRF. Isto expunha os endpoints de mutação a falsificação de pedidos entre sites em sessões válidas.

**Resolução**
◆ Validação CSRF aplicada a todas as mutações de faturação
◆ Lógica de verificação de tokens centralizada
◆ O frontend envia tokens de forma consistente

**Resultado**
Todas as operações de faturação que modificam estado requerem agora **pedidos explicitamente iniciados pelo utilizador**.

## Revisão adicional

### Superfícies de execução de comandos

Revimos caminhos de código contendo primitivas de execução (ex. shell/exec).

**Estado atual**
◆ Sem exposição ativa através de controladores ou rotas públicas
◆ Sem evidência de invocação em tempo de execução em caminhos de pedido

**Posição**
◆ Tratar como **ferramentas internas não públicas exclusivamente**
◆ Candidato para remoção ou isolamento futuro

## Verificação

Todas as alterações foram validadas através de:

◆ Lint de PHP em ficheiros modificados
◆ Diagnósticos estáticos do editor
◆ Inspeção manual de fluxos de pedidos

Não foram introduzidos problemas de sintaxe ou tempo de execução.

## Princípios de segurança aplicados

Este reforço reafirma alguns princípios fundamentais:

◆ **Negação por defeito** sobre confiança implícita
◆ **Limites de confiança explícitos** (ex. proxies, origens)
◆ **Validação em cada ponto de entrada externo**
◆ **Controlos de segurança centralizados** sobre verificações dispersas

## O que isto significa para os utilizadores

◆ Risco de phishing reduzido por abuso de redirecionamentos
◆ Garantias mais sólidas em torno das ações de faturação
◆ Integridade melhorada no tratamento de pedidos e validação de origem

Não é necessária qualquer ação por parte dos utilizadores.

## Trabalho em curso

Tratamos a segurança como um processo contínuo. Os próximos passos incluem:

◆ Testes de integração: comportamento de validação de redirecionamentos
◆ Testes de integração: aplicação CSRF em endpoints
◆ Testes de integração: tratamento de limites de confiança de proxy
◆ Varrimentos periódicos: sumidouros de redirecionamentos
◆ Varrimentos periódicos: regressões de confiança de cabeçalhos
◆ Triagem interna de rotas de alto risco

## Ficheiros atualizados

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Nota de encerramento

Este esforço focou-se na eliminação de **caminhos de exploração realistas**, não de casos extremos teóricos. Continuaremos a priorizar mudanças que melhorem significativamente a segurança preservando a fiabilidade do produto.
