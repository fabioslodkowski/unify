# Open Finance — Base de Conhecimento Brudam

> **Objetivo:** Repositório consolidado de conhecimento técnico e estratégico sobre Open Finance para subsidiar a criação do projeto no Git e alinhamento com o líder técnico.
>
> **Última atualização:** 2026-06-29
>
> **Responsável:** Arthur Estima

---

## Índice

1. [O que é Open Finance no Brasil](#1-o-que-é-open-finance-no-brasil)
2. [Status do Mercado](#2-status-do-mercado-2025-2026)
3. [O que construímos — Módulo Pluggy](#3-o-que-construímos--módulo-pluggy)
4. [Arquitetura Técnica](#4-arquitetura-técnica)
5. [Parceiros e Fornecedores](#5-parceiros-e-fornecedores)
6. [Banrisul — Pergunta & Resposta](#6-banrisul--pergunta--resposta)
7. [Casos de Uso para a Brudam](#7-casos-de-uso-para-a-brudam)
8. [Backlog Técnico](#8-backlog-técnico)
9. [Referências](#9-referências)

---

## 1. O que é Open Finance no Brasil

Open Finance é o sistema financeiro aberto regulamentado pelo Banco Central do Brasil que permite o **compartilhamento padronizado de dados e serviços financeiros** entre instituições autorizadas, mediante consentimento expresso do cliente.

### Fases de implementação (BCB)

| Fase | Escopo | Status |
|------|--------|--------|
| **Fase 1** | Dados públicos das IFs (produtos, tarifas, canais) | ✅ Concluída |
| **Fase 2** | Dados de clientes (conta, cartão, câmbio, crédito) | ✅ Concluída |
| **Fase 3** | Iniciação de pagamentos (PIX, boleto, TED) | ✅ Concluída |
| **Fase 4** | Dados de câmbio, investimentos, seguros, previdência | ✅ Em expansão |
| **Portabilidade de crédito** | Portabilidade via Open Finance | 🔄 Início: nov/2025, expansão 2026 |

### Atores do ecossistema

- **Detentor de dados (Transmissor):** banco que tem os dados do cliente (Banrisul, Santander, Itaú, etc.)
- **Receptor de dados:** empresa que recebe os dados com consentimento (pode ser a Brudam)
- **Iniciador de Transação de Pagamento (ITP):** autorizado a disparar pagamentos
- **Agregador (middleware):** empresa que abstrai a complexidade e fornece API unificada (Pluggy, Belvo)

### Regulamentação

- Resolução BCB nº 32/2020 e complementares
- Resolução Conjunta nº 1/2020 (Bacen + CMN)
- Participação **obrigatória** para IFs com mais de 5 milhões de clientes (desde 2025)

---

## 2. Status do Mercado 2025–2026

| Indicador | Dado |
|-----------|------|
| Consentimentos ativos no Brasil | **143+ milhões** |
| Volume de dados (crescimento) | **+110%** (dez/2024 → nov/2025) |
| Crédito originado via Open Finance | **R$ 31 bilhões** (acumulado até mid-2025) |
| Crédito só no 1º semestre 2025 | **R$ 12 bilhões** |
| Taxa de sucesso nas conexões (Belvo) | 52% → **63%** (melhoria em 12 meses) |
| Portabilidade de crédito | Live desde **nov/2025** |

> **Tendência 2026:** O BCB está expandindo a participação obrigatória para IFs menores. Cresce a demanda por soluções que transformam conectividade em inteligência acionável (score, análise de risco, automação de cobrança).

---

## 3. O que construímos — Módulo Pluggy

### Contexto

Implementamos no Hub Financeiro Brudam (SPA React em `index.html`, porta 3456) um módulo **Open Finance** integrado com a [Pluggy](https://pluggy.ai), usando o **Pluggy Connect Widget** para autenticação e coleta de dados bancários.

### Fluxo implementado

```
Usuário clica "Conectar Banco"
        ↓
Backend /api/pluggy/connect-token  (gera token de sessão)
        ↓
Pluggy Connect Widget (carregado via CDN, autenticação OAuth2 com o banco)
        ↓
Callback onSuccess → salva itemId no backend
        ↓
/api/pluggy/accounts  → lista contas e saldos
        ↓
/api/pluggy/transactions?accountId=X  → extrato em tempo real
```

### Endpoints do backend (`server.py`)

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/api/pluggy/config` | GET | Verifica se Pluggy está configurado |
| `/api/pluggy/config` | POST | Salva `clientId` + `clientSecret` |
| `/api/pluggy/connect-token` | POST | Gera token de sessão para o widget |
| `/api/pluggy/items` | GET | Lista bancos conectados |
| `/api/pluggy/items` | POST | Registra novo banco após conexão |
| `/api/pluggy/items/:id` | DELETE | Desconecta banco |
| `/api/pluggy/accounts` | GET | Saldos de todas as contas conectadas |
| `/api/pluggy/transactions` | GET | Extrato (`?accountId=&pageSize=`) |

### Telas do módulo

- **Setup:** formulário para inserir Client ID + Secret da Pluggy
- **Overview:** KPIs (saldo total, cartão, investimentos, bancos conectados) + cards por conta
- **Contas:** tabela com agência, número, saldo, limite; gerenciamento de conexões
- **Transações:** extrato agrupado por mês, com crédito/débito colorido
- **Configurações:** atualização de credenciais

### SDK utilizado

```html
<script src="https://cdn.pluggy.ai/pluggy-connect/v2.2.1/pluggy-connect.js"></script>
```

### Credenciais necessárias (ambiente)

```
PLUGGY_CLIENT_ID=xxxx
PLUGGY_CLIENT_SECRET=xxxx
```

Obtidas em: `app.pluggy.ai` → Configurações → API Keys → Nova aplicação

---

## 4. Arquitetura Técnica

### Diagrama de fluxo

```
┌─────────────────────────────────────────────────────────────────┐
│                        BRUDAM HUB                               │
│  React SPA (index.html)                                          │
│  ┌──────────────────┐                                            │
│  │  OpenFinanceView │ ─── fetch('/api/pluggy/*') ─────────────── │
│  └──────────────────┘                                            │
└───────────────────────────────────────────────────────┬─────────┘
                                                        │
                                          Python BaseHTTPServer
                                          (server.py)
                                                        │
                                     ┌──────────────────▼──────────────┐
                                     │        Pluggy API                │
                                     │   api.pluggy.ai                  │
                                     │                                  │
                                     │  ┌───────────┐  ┌────────────┐  │
                                     │  │ Banrisul  │  │  Santander │  │
                                     │  └───────────┘  └────────────┘  │
                                     │  ┌───────────┐  ┌────────────┐  │
                                     │  │   Itaú    │  │  Bradesco  │  │
                                     │  └───────────┘  └────────────┘  │
                                     │  + 200+ instituições             │
                                     └─────────────────────────────────┘
```

### Stack

| Camada | Tecnologia |
|--------|-----------|
| Frontend | React 18 + Babel CDN (sem build step) |
| Backend | Python `BaseHTTPRequestHandler` |
| Banco de dados | MySQL em `sistema.db.brudam.com.br:34500` |
| Open Finance middleware | Pluggy (autorizado BCB como ITP) |
| Auth com bancos | OAuth2 via Pluggy Connect Widget |

### Tipos de conta suportados pela Pluggy

| Tipo | Descrição |
|------|-----------|
| `BANK` | Conta corrente / poupança |
| `CREDIT` | Cartão de crédito (fatura + limite) |
| `INVESTMENT` | Investimentos (CDB, LCI, etc.) |
| `LOAN` | Empréstimos |

---

## 5. Parceiros e Fornecedores

### 5.1 Pluggy — Parceiro Atual (Implementado)

**Website:** [pluggy.ai](https://www.pluggy.ai)

**Modelo:** Agregador de dados + ITP autorizado pelo BCB (Resolução BCB nº 80/2021)

**Diferenciais:**
- Cobertura: 200+ instituições financeiras no Brasil
- Widget pronto (Pluggy Connect) — OAuth2 com os bancos sem desenvolver fluxo próprio
- SDK JavaScript + APIs REST documentadas
- Sandbox gratuito para testes
- Modelo de cobrança por requisição (pay-as-you-go)

**Casos de uso cobertos pela Pluggy:**
- Agregação de saldos e extratos
- Iniciação de pagamentos (PIX)
- Relatórios financeiros automáticos para ERPs/TMS
- Conciliação bancária

**Avaliação para a Brudam:** ✅ **Já integrado.** Ideal para o MVP — baixo custo de entrada, widget pronto, sem necessidade de certificação própria no BCB.

---

### 5.2 Belvo — Parceiro Estratégico a Avaliar

**Website:** [belvo.com](https://belvo.com/pt-br)

**CFO:** **Leandro Piano** (ex-colega do Arthur na empresa anterior)

**Modelo:** Plataforma de inteligência em Open Finance — vai além da agregação de dados e entrega **inteligência acionável**: categorização, score, automação de cobrança com IA.

**Scale (2025–2026):**
- Processa **~10% de todos os consentimentos ativos do Brasil** (≈ 14 milhões)
- Crescimento de **+110%** no volume de requisições (dez/2024 → nov/2025)
- Clientes: Nubank, Banco Inter, JPMorgan

**Principais funcionalidades:**

| Funcionalidade | Descrição |
|----------------|-----------|
| **Agregação Open Finance** | Dados de conta, extrato, limite, investimentos via consentimento |
| **Dados de Emprego (INSS)** | Integração de dados previdenciários para análise de renda |
| **Score e Categorização** | Inteligência sobre comportamento financeiro — renda, gastos, padrões |
| **Cobrança Inteligente** | Monitoramento de saldo + disparo de PIX no momento ideal + AI agents (WhatsApp/voz) |
| **Portabilidade de Crédito** | Nova funcionalidade (live nov/2025) |
| **White Label** | Infraestrutura sob licença regulatória da Belvo — cliente não precisa de credencial própria no BCB |

**Case: Banco Inter**
> Inter integrou a Belvo para transformar sua jornada de crédito:
> - 210 mil consentimentos em 3 meses; 46 mil conexões em 1 dia
> - Taxa de sucesso subiu de **62% → 76%**
> - Validação automática de renda (INSS + Open Finance) — eliminou comprovação manual
> - Meta: atingir 60 milhões de correntistas até 2026

**Visão do CFO Leandro Piano:**
> *"O público está enxergando o benefício no final. Quando alguém percebe que clicar para compartilhar dados resulta em ofertas de crédito que antes não tinha, ou uma experiência de onboarding muito mais rápida, a barreira cai."*
> — Leandro Piano, CFO da Belvo (Finsiders Brasil, 2025)

**Avaliação para a Brudam:** ⭐ **Alto potencial estratégico.** A conexão com o Leandro é um ativo. A Belvo é especialmente interessante se a Brudam quiser oferecer **análise de crédito dos transportadores** ou **automação de cobrança** para os embarcadores.

**Próximo passo sugerido:** Contato direto com Leandro Piano via LinkedIn para explorar parceria/pricing customizado.

---

### 5.3 Outras opções do mercado

| Player | Modelo | Diferencial | Status |
|--------|--------|-------------|--------|
| **Quanto** | Aggregator + Analytics | Foco em PMEs, forte em categorização | Avaliar |
| **Celcoin** | BaaS + Open Finance | Infraestrutura para crédito B2B, APIs de portabilidade | Avaliar |
| **Fintech do Bem** | Open Finance social | Foco em inclusão financeira e desbancarizados | Nicho |
| **Klever** | Wallet + Open Finance | Foco em criptoativos + Open Finance | Fora do escopo |
| **Certificação Própria BCB** | Participante direto | Sem custo de middleware, mas exige certificação FAPI/MTLS e estrutura jurídica de IF | Longo prazo |

---

## 6. Banrisul — Pergunta & Resposta

> **Contexto:** Entramos em contato com o Banrisul para entender as possibilidades de integração via Open Finance, dado que o Banrisul é um dos bancos primários da Brudam.
>
> **Data:** 26–29 de junho de 2026
> **Contato Banrisul:** Rodrigo Zortea — Técnico Bancário, Gerência de Integração e Conectividade, Unidade de Inovação Financeira (`api@banrisul.com.br`)

---

### Pergunta enviada (26/jun/2026)

**De:** Arthur Estima `<arthur.estima@brudam.com.br>`
**Para:** `InovacaoCon@banrisul.com.br`
**Cc:** Daniel Carvalho `<daniel@brudam.com.br>`
**Assunto:** Integração Banrisul - Brudam

> Olá pessoal, tudo bem?
>
> Hoje temos um script que roda a inserção de arquivos de remessa para dentro do sistema financeiro Brudam, para termos atualizado as movimentações de pagamentos e recebimentos pelo banco.
>
> Nós queremos evoluir esse processo manual para uma integração via Open Finance, para que possamos ter todos esses dados de forma atualizada e com mais agilidade.
>
> Além disso, como comercializamos nosso software para os clientes, gostaríamos de oferecer também essa possibilidade para eles.
>
> Vocês podem nos auxiliar nesse projeto?
>
> Abraços
> **Arthur Estima**
> Diretor Financeiro

---

### Resposta do Banrisul (29/jun/2026)

**De:** Inovacao Conectividade `<InovacaoCon@banrisul.com.br>`
**Respondente:** Rodrigo Zortea — Técnico Bancário, Gerência de Integração e Conectividade

> Olá, bom dia!
>
> Infelizmente, no momento, não temos esta funcionalidade disponível (integração via Open Finance) via troca de arquivos ou API.
>
> Estamos em processo de desenvolvimento de nossa API de Saldos e Extratos, mas, nesta fase, ela permitirá acesso apenas às informações das contas Banrisul, sem uma previsão, neste momento, para a disponibilização da integração via Open Finance.
>
> Qualquer dúvida, estamos à disposição.

---

### ⚠️ Análise e Impacto

| Aspecto | Status |
|---------|--------|
| Open Finance nativo Banrisul | ❌ **Não disponível** |
| API de Saldos e Extratos própria | 🔄 Em desenvolvimento (sem prazo) |
| Integração via middleware (Pluggy/Belvo) | ✅ **Possível** — Pluggy conecta ao Banrisul via Open Finance regulatório do BCB |
| Arquivo de remessa atual | ✅ Funciona (solução legada mantida) |

**Conclusão crítica:** O Banrisul ainda não oferece integração Open Finance diretamente para parceiros via API própria. Isso **não impede** a integração — o **Pluggy já conecta ao Banrisul** através do protocolo Open Finance regulatório do BCB (onde o Banrisul participa obrigatoriamente por ter mais de 5M de clientes). A rota é:

```
Brudam Hub → Pluggy API → Open Finance BCB → Banrisul (participante obrigatório)
```

**Próximos passos recomendados:**
1. Manter arquivo de remessa como fallback enquanto Pluggy é testado
2. Testar conexão Banrisul via **Pluggy Sandbox** — verificar cobertura de extratos e saldos
3. Acompanhar o desenvolvimento da **API própria de Saldos e Extratos do Banrisul** — quando lançar, pode ser alternativa mais direta
4. Solicitar ao Rodrigo Zortea acesso à **beta/preview** da API em desenvolvimento

### O que o Banrisul disponibiliza via Open Finance

Com base na documentação pública:

| API | Disponível | Observação |
|-----|-----------|------------|
| Dados de conta corrente | ✅ | Via consentimento do cliente |
| Extrato de transações | ✅ | Histórico via consentimento |
| Dados de crédito | ✅ | Limites, contratos |
| Iniciação de pagamento (PIX) | ✅ | Requer credencial de ITP |
| Crédito Consignado | ✅ | API específica para parceiros |
| Saque Aniversário FGTS | ✅ | API para parceiros autorizados |

### Requisitos para integração direta com Banrisul

Para conectar **diretamente** (sem middleware como Pluggy/Belvo), a Brudam precisaria:

1. Cadastrar-se no **Diretório do Open Finance Brasil** (diretorio.openbankingbrasil.org.br)
2. Obter **certificado digital MTLS** (mútua autenticação TLS) — ICP-Brasil
3. Implementar **FAPI 1.0 Advanced** (Financial-grade API Security Profile) para OAuth2
4. Manter **DCR (Dynamic Client Registration)** com cada instituição participante
5. Passar por processo de **homologação** no sandbox do BCB

> **Conclusão:** Integração direta é viável mas custosa (semanas/meses de engenharia + custos jurídicos de registro). **Recomendação de curto prazo:** usar Pluggy ou Belvo como middleware e reservar a integração direta para fase futura.

---

## 7. Casos de Uso para a Brudam

A Brudam é um **TMS (Transportation Management System)**. Os casos de uso de Open Finance mais relevantes para nosso contexto:

### 7.1 Conciliação Bancária Automática ⭐ (Já iniciada)

**Problema:** Atualmente a reconciliação de saldos Banrisul/Santander é feita manualmente.

**Solução Open Finance:**
- Conexão direta via Pluggy → extrato em tempo real
- Matching automático de lançamentos do TMS vs. extrato bancário
- Alertas de divergências

**Status:** MVP implementado no Hub Financeiro (módulo Open Finance com Pluggy).

---

### 7.2 Score de Crédito dos Transportadores

**Problema:** Brudam não tem visibilidade financeira dos transportadores antes de liberar crédito ou condições especiais.

**Solução Open Finance:**
- Transportador autoriza compartilhamento de dados bancários
- Belvo/Pluggy categorizam renda, fluxo de caixa, comportamento de pagamento
- Score gerado internamente para decisão de crédito/limite

**Parceiro ideal:** Belvo (camada de inteligência, dados INSS + Open Finance)

---

### 7.3 Cobrança Inteligente de Embarcadores

**Problema:** Inadimplência de embarcadores no pagamento de fretes.

**Solução Open Finance:**
- Monitorar saldo da conta do embarcador em tempo real (via consentimento)
- Disparar cobrança PIX automaticamente quando saldo disponível detectado
- AI agent via WhatsApp para negociação de atraso
- Redução de inadimplência sem aumento de equipe

**Parceiro ideal:** Belvo (caso de uso de cobrança com IA)

---

### 7.4 Onboarding Financeiro de Clientes

**Problema:** Processo atual de onboarding de novos clientes exige documentação manual de renda.

**Solução Open Finance:**
- Cliente compartilha dados bancários via widget (Pluggy Connect / Belvo)
- Validação automática de faturamento, renda, capacidade de pagamento
- Redução do ciclo de onboarding de dias para minutos

---

### 7.5 Visão Consolidada de Caixa da Brudam

**Problema:** Brudam tem contas em Banrisul e Santander — visão consolidada hoje é manual.

**Solução Open Finance:**
- Ambas as contas conectadas via Pluggy
- Dashboard único com saldo consolidado, extrato unificado, projeção de fluxo
- Alertas de saldo crítico

**Status:** Estrutura criada, aguarda conexão das contas Banrisul/Santander no módulo Pluggy.

---

## 8. Backlog Técnico

### Para o líder técnico — próximas implementações

| # | Tarefa | Prioridade | Complexidade | Parceiro |
|---|--------|-----------|-------------|---------|
| 1 | Conectar contas Banrisul e Santander no módulo Pluggy (produção) | 🔴 Alta | Baixa | Pluggy |
| 2 | Backend: persistir `itemId` + `accountId` no MySQL, não só em memória | 🔴 Alta | Média | — |
| 3 | Webhook Pluggy: receber notificações de novos lançamentos em tempo real | 🟡 Média | Média | Pluggy |
| 4 | Matching automático: lançamentos DRE vs. extrato Open Finance | 🟡 Média | Alta | — |
| 5 | Tela de reconciliação com % de matches e divergências pendentes | 🟡 Média | Média | — |
| 6 | Avaliar Belvo para score de transportadores (POC) | 🟢 Baixa | Alta | Belvo |
| 7 | Implementar portabilidade de crédito via Open Finance | 🟢 Baixa | Alta | Belvo/Pluggy |
| 8 | Automação de cobrança com monitoramento de saldo | 🟢 Baixa | Alta | Belvo |

### Questões em aberto

- [ ] Banrisul suporta **webhook de notificação** quando novo lançamento ocorre?
- [ ] Custo por requisição na Pluggy — escalar para 100 chamadas/dia qual o custo?
- [ ] Belvo tem modelo white-label sem necessidade de credencial BCB própria?
- [ ] A Brudam precisaria ser registrada no Diretório BCB para escalar além do MVP?
- [ ] Prazo e custo para certificação como participante direto do Open Finance Brasil?

---

## 9. Referências

### Regulamentação
- [Banco Central do Brasil — Open Finance](https://www.bcb.gov.br/estabilidadefinanceira/openfinance)
- [Diretório de Participantes Open Finance Brasil](https://web.directory.openbankingbrasil.org.br)
- [Manual de Integração Open Finance Brasil](https://openfinancebrasil.atlassian.net/wiki/spaces/OF/pages/37945515)

### Pluggy (parceiro atual)
- [pluggy.ai](https://www.pluggy.ai) — site oficial
- [Pluggy para ERPs](https://www.pluggy.ai/erp)
- [Pluggy Open Finance para empresas (2026)](https://www.pluggy.ai/blog/open-finance-para-empresas)
- [GitHub Pluggy](https://github.com/pluggyai)

### Belvo (parceiro a avaliar)
- [belvo.com/pt-br](https://belvo.com/pt-br) — site oficial
- [Case Banco Inter × Belvo](https://belvo.com/pt-br/historias-de-sucesso/banco-inter/)
- [Casos de uso: Cobrança com IA](https://belvo.com/pt-br/casos-uso/cobranca/)
- [Estudo: Impacto do Open Finance no Crédito](https://belvo.com/pt-br/blog/estudo-impacto-do-open-finance-no-credito/)
- [Belvo: 15 milhões de consentimentos — Finsiders Brasil](https://finsidersbrasil.com.br/conteudo-de-marca/com-15-milhoes-de-consentimentos-belvo-mapeia-a-maturacao-do-open-finance/)

### Banrisul
- [Portal do Desenvolvedor Banrisul](https://developers.banrisul.com.br)
- [Open Finance Banrisul](https://developers.banrisul.com.br/BPI/link/open-finance.html)

### Mercado
- [Open Finance Brasil — 100 milhões de clientes](https://finsidersbrasil.com.br/economia-open/brasil-lidera-open-finance-no-mundo-com-100-milhoes-de-clientes/)
- [Celcoin — Open Finance B2B](https://celcoin.com.br/articles/melhor-plataforma-openfinance-credito-b2b/)

---

*Documento gerado em 2026-06-29. Manter atualizado conforme evolução do projeto.*
