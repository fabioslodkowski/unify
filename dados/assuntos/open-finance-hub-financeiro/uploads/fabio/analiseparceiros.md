# Análise de Parceiros — Integração Bancária para o Hub Financeiro

> **Cenário base:** 500 clientes do TMS. Cada cliente é uma empresa de transporte com conta própria nos bancos.

---

## Os principais players do mercado

### 1. TecnoSpeed — PlugBank

**Foco:** Software houses brasileiras. É o produto mais direcionado para ERPs e TMSs.

**O que oferece:**
- Boleto (40+ bancos homologados)
- Pix Cobrança e Pix pagamento
- CNAB remessa e retorno
- Open Finance (extrato, dados)
- Conciliação
- Pagamentos em lote

**Modelo:** A software house se integra uma vez. Cada cliente do ERP usa o próprio banco — o PlugBank age como ponte.

**Preço:** Não publicado. Modelo por volume de transações/emissões. Necessário solicitar proposta comercial.

**Referência de mercado:** Mais de 40 bancos homologados, amplamente usado por ERPs e TMSs no Brasil.

**Ponto forte para o nosso cenário:** É o produto mais próximo do que precisamos — boleto, Pix, CNAB e Open Finance em uma única API, voltado a software houses.

---

### 2. Celcoin

**Foco:** BaaS completo — fintechs, ERPs, marketplaces.

**O que oferece:**
- Boleto e Pix Cobrança
- Pagamentos (contas, fornecedores)
- Extrato consolidado
- Conta digital (se necessário)
- Cartões (se necessário)
- Open Finance

**Modelo:** Transacional — paga pelo que usa, sem setup elevado.

**Casos de sucesso:** Neon, Sky, PipeImob, Cumbuca. Processa R$ 30 bilhões/mês.

**Preço:** Não publicado. Comercial sob consulta por volume.

**Diferença para o nosso cenário:** Celcoin é mais robusto — entrega até conta digital. Para o TMS, pode ser mais do que o necessário, mas é uma opção sólida se quisermos escalar para serviços financeiros completos no futuro.

---

### 3. Pluggy

**Foco:** Open Finance, dados financeiros, Pix pagamento.

**O que oferece:**
- Extrato e dados de qualquer banco via Open Finance
- Iniciação de Pix
- Pix Automático (recorrência)
- Widget de conexão bancária pronto

**O que NÃO oferece diretamente:**
- Emissão de boletos registrados
- CNAB
- Pagamentos em lote

**Preço:** A partir de **R$ 2.500/mês** (plano básico, até 20 contas). Plano enterprise: consulta.

**Conclusão para o nosso cenário:** Pluggy resolve a parte de leitura de dados e Pix, mas não substitui a integração de boleto e CNAB. Seria um complemento, não a solução principal.

---

### 4. Asaas

**Foco:** Cobranças — boleto, Pix, cartão. Simples e sem mensalidade.

**O que oferece:**
- Boleto registrado
- Pix Cobrança
- Recorrência
- API completa

**O que NÃO oferece:**
- CNAB
- Pagamentos em lote para fornecedores
- Open Finance
- Extrato multi-banco

**Preço:**
- Sem mensalidade
- **R$ 1,99 por transação** (Pix ou Boleto)
- Promo nos primeiros 3 meses: R$ 0,99

**Conclusão para o nosso cenário:** Muito simples para o TMS — falta CNAB e pagamentos em lote. Mas pode ser uma porta de entrada rápida e barata para validar o módulo de cobranças.

---

### 5. Zoop

**Foco:** Marketplaces, white-label, maquininhas.

**O que oferece:**
- Boleto, Pix, cartão
- White-label de pagamentos
- Split de pagamentos

**Preço (referência pública):**
- Boleto pago: ~1,93% + R$ 2,10 por transação
- Pix: ~2,72% por transação

**Conclusão para o nosso cenário:** Modelo de percentual sobre o valor — para cobranças altas de frete, o custo explode. Não é o modelo ideal para TMS.

---

### 6. Dock

**Foco:** BaaS + ITP (iniciação de Pix via Open Finance).

**O que oferece:**
- Pix (inclusive automático)
- Boleto
- Conta digital
- Cartões

**Preço:** Não publicado. Sob consulta.

**Conclusão:** Robusto, mas voltado a fintechs que querem lançar produtos financeiros. Para o TMS, pode ser mais do que o necessário.

---

## Comparativo geral

| | TecnoSpeed | Celcoin | Pluggy | Asaas | Zoop |
|---|---|---|---|---|---|
| Boleto registrado | ✅ | ✅ | ❌ | ✅ | ✅ |
| CNAB remessa/retorno | ✅ | Parcial | ❌ | ❌ | ❌ |
| Pix Cobrança | ✅ | ✅ | ✅ | ✅ | ✅ |
| Pagamentos em lote | ✅ | ✅ | ❌ | ❌ | ❌ |
| Open Finance / extrato multi-banco | ✅ | ✅ | ✅ | ❌ | ❌ |
| Focado em software house/ERP | ✅ | Parcial | Parcial | ❌ | ❌ |
| Preço público | ❌ | ❌ | ✅ (R$2.500/mês) | ✅ (R$1,99/tx) | ✅ (~R$2,10/boleto) |
| Modelo | Volume | Transacional | Consentimento/mês | Por transação | % + fixo |

---

## Estimativa de custo — 500 clientes TMS

### Premissas (estimativa conservadora)

| Variável | Valor estimado |
|---|---|
| Clientes ativos | 500 |
| Boletos emitidos por cliente/mês | 80 |
| Total de boletos/mês | 40.000 |
| Pix recebidos por cliente/mês | 30 |
| Total de Pix/mês | 15.000 |
| Pagamentos feitos por cliente/mês | 20 |
| Total de pagamentos/mês | 10.000 |

### Cenário 1 — Parceiro (TecnoSpeed ou Celcoin, preço estimado por volume)

> Preços não são públicos. Baseado em referências do mercado para volume acima de 30.000 transações/mês.

| Operação | Volume/mês | Custo unitário estimado | Total estimado |
|---|---|---|---|
| Boleto emitido | 40.000 | R$ 0,40 – R$ 0,80 | R$ 16.000 – R$ 32.000 |
| Pix Cobrança | 15.000 | R$ 0,20 – R$ 0,50 | R$ 3.000 – R$ 7.500 |
| Pagamentos | 10.000 | R$ 0,30 – R$ 0,60 | R$ 3.000 – R$ 6.000 |
| **Total mensal estimado** | | | **R$ 22.000 – R$ 45.500** |

> ⚠️ Estes valores são estimativas de mercado. A negociação por volume pode reduzir em até 50%. Solicitar proposta formal com TecnoSpeed e Celcoin com os volumes reais.

### Cenário 2 — Asaas (preço público, sem mensalidade)

| Operação | Volume/mês | Custo unitário | Total |
|---|---|---|---|
| Boleto ou Pix | 55.000 | R$ 1,99 | R$ 109.450 |
| **Total mensal** | | | **R$ 109.450** |

> ❌ Inviável neste volume. Asaas funciona para clientes com baixo volume de transações.

### Cenário 3 — Hub próprio (sem parceiro)

| Item | Custo |
|---|---|
| Desenvolvimento MVP (3-4 bancos) | 3 a 6 meses de equipe |
| Custo por transação | **R$ 0,00** |
| Manutenção anual | ~1 dev parcial |
| Custo mensal operacional | **R$ 0,00** (só infra) |

> Os clientes pagam as tarifas bancárias diretamente ao próprio banco (como hoje). O Hub não tem custo por transação.

---

## Análise de custo por cliente — resumo

| Modelo | Custo total/mês | Custo por cliente/mês |
|---|---|---|
| Parceiro (volume negociado) | R$ 22.000 – R$ 45.500 | R$ 44 – R$ 91 |
| Hub próprio | ~R$ 0 (só infra) | ~R$ 0 |
| Asaas (referência) | R$ 109.000 | R$ 218 |

---

## O que o parceiro resolve vs. o que o Hub próprio resolve

| Pergunta | Parceiro | Hub próprio |
|---|---|---|
| Evita desenvolvimento de integrações bancárias? | ✅ Sim | ❌ Não — esse É o trabalho |
| Tem custo recorrente por volume? | ✅ Sim | ❌ Não |
| Pode repassar o custo ao cliente? | Sim (se o modelo permitir) | Não se aplica |
| Posso monetizar os serviços financeiros? | Sim (markup) | Sim (cobrar no plano do TMS) |
| Dependência de terceiro? | Alta | Nenhuma |
| Prazo para entrar em produção? | Semanas | 3-6 meses |
| Cobre CNAB? | TecnoSpeed: sim. Outros: não | ✅ Sim |
| Risco de aumento de preço? | Alto | Nenhum |

---

## Cases relevantes

| Empresa | Parceiro usado | O que usa |
|---|---|---|
| **Neon** | Celcoin | Conta digital + Pix + boleto como BaaS |
| **PipeImob** (ERP imobiliário) | Celcoin | Integração financeira embarcada no ERP |
| **Sankhya** (ERP) | API própria BB + TecnoSpeed | Boleto rápido por API + integrações proprietárias |
| **ERPs regionais** | TecnoSpeed PlugBank | Boleto multi-banco sem desenvolvimento bancário próprio |

---

## Recomendação para o nosso cenário

### Caminho A — MVP rápido com parceiro

Use **TecnoSpeed (PlugBank)** como camada de integração bancária temporária.

- Prazo: semanas para entrar em produção
- Custo estimado: R$ 22.000 – R$ 45.000/mês para 500 clientes
- Você valida o produto, ganha clientes, coleta dados reais de uso

### Caminho B — Hub próprio desde o início

Constrói a camada de integração internamente, banco a banco.

- Prazo: 3 a 6 meses para MVP
- Custo operacional: zero por transação
- Controle total. Sem dependência de fornecedor.

### Caminho recomendado — Híbrido em fases

| Fase | O que fazer |
|---|---|
| Curto prazo (0–6 meses) | Contratar TecnoSpeed para os bancos mais usados pelos clientes. Validar o produto. |
| Médio prazo (6–18 meses) | Desenvolver o Hub próprio banco a banco, começando pelos de maior volume. |
| Longo prazo (18 meses+) | Migrar completamente para o Hub próprio. Adicionar Open Finance via Pluggy como complemento de dados. |

> Essa abordagem coloca o produto no mercado em semanas, valida a demanda com custo controlado e constrói a independência gradualmente.
