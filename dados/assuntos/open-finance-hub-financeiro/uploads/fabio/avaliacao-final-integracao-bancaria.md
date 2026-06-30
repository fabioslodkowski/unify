# Avaliação Final — Integração Bancária para o Hub Financeiro

> **Para quem é este documento:** diretoria, time de produto, time técnico.
> **Objetivo:** explicar de forma clara e definitiva como funcionam as opções disponíveis para integrar o TMS com os bancos — parceiros, Open Finance e hub próprio — com custos, exemplos reais e recomendação final.

---

## Índice

1. [O problema que estamos resolvendo](#1-o-problema-que-estamos-resolvendo)
2. [O que é Open Finance de verdade](#2-o-que-é-open-finance-de-verdade)
3. [O que Open Finance não faz](#3-o-que-open-finance-não-faz)
4. [Os dois cenários da Brudam](#4-os-dois-cenários-da-brudam)
5. [Como funciona a integração banco a banco](#5-como-funciona-a-integração-banco-a-banco)
6. [O processo de certificado digital — o que ninguém explica](#6-o-processo-de-certificado-digital)
7. [Os parceiros do mercado — o que cada um faz de fato](#7-os-parceiros-do-mercado)
8. [Comparativo de custos](#8-comparativo-de-custos)
9. [O que o parceiro resolve e o que ele não resolve](#9-o-que-o-parceiro-resolve-e-o-que-ele-não-resolve)
10. [Casos de uso práticos para o TMS](#casos-de-uso-práticos-para-o-tms)
11. [Recomendação e caminho sugerido](#10-recomendação-e-caminho-sugerido)

---

## 1. O problema que estamos resolvendo

### A situação hoje

O TMS precisa se comunicar com bancos para duas categorias de operação:

**Receber dinheiro dos clientes da transportadora:**
- Emitir boleto para o embarcador pagar o frete
- Gerar QR Code Pix para pagamento imediato ou com prazo
- Saber quando foi pago (liquidação)

**Pagar fornecedores e motoristas:**
- Fazer transferências e pagamentos a partir da conta da transportadora

Hoje cada banco tem sua própria linguagem, seu próprio sistema de autenticação e suas próprias regras. Não existe um padrão universal. O que funciona no Itaú não funciona no Banrisul. O que o Santander pede é diferente do que o Sicredi pede.

**O resultado:** para dar suporte a 10 bancos diferentes, o TMS precisaria falar 10 idiomas diferentes. É exatamente esse problema que o Hub Financeiro resolve — ele traduz tudo.

### Uma analogia simples

Imagine que você precisa ligar para clientes em 10 países diferentes. Você poderia contratar 10 tradutores (um por idioma) ou contratar um serviço de tradução simultânea que conhece todos os idiomas. O Hub Financeiro é esse serviço de tradução — o TMS fala uma língua só com o Hub, e o Hub fala com cada banco no idioma dele.

---

## 2. O que é Open Finance de verdade

### A definição simples

Open Finance Brasil é um sistema criado e regulamentado pelo Banco Central que obriga os grandes bancos a abrirem suas APIs para terceiros. Na prática, isso significa que você pode acessar dados e movimentar dinheiro de qualquer banco participante por meio de um único canal.

### As quatro fases

| Fase | Quando entrou | O que liberou |
|---|---|---|
| 1 | Fev/2021 | Dados públicos dos bancos (tarifas, produtos, agências) |
| 2 | Ago/2021 | Dados do cliente: saldo, extrato, transações, cartões |
| 3 | Out/2021 | Pagamentos: Pix, transferências, iniciação de pagamento |
| 4 | Mar/2022+ | Investimentos, seguros, câmbio, previdência |

### O que o Open Finance permite na prática

**Exemplo 1 — Extrato multi-banco:**
Uma transportadora tem conta no Itaú, no Banrisul e no Sicredi. Com Open Finance, ela pode ver o extrato dos três bancos em uma única tela do TMS — sem precisar abrir o internet banking de cada um.

**Exemplo 2 — Pix via Open Finance (ITP):**
A transportadora quer pagar 50 motoristas de uma vez, cada um com conta em bancos diferentes. Via um Iniciador de Transação de Pagamento (ITP), ela autoriza uma única vez no TMS e os 50 Pix saem da conta dela para os motoristas — sem precisar abrir o app de cada banco.

**Exemplo 3 — Pix Automático:**
A Brudam cobra mensalidade dos clientes do TMS todo mês. Em vez de emitir boleto, o cliente autoriza uma única vez via Open Finance e a Brudam debita automaticamente todo mês da conta do cliente, em qualquer banco.

### Como participar do Open Finance

Existem três caminhos:

| Caminho | Como | Custo | Complexidade |
|---|---|---|---|
| **Usar um agregador** (Pluggy, TecnoSpeed, Belvo) | Contrata a API deles. Eles já são participantes | Mensalidade ou por transação | Baixa — integra em semanas |
| **Usar um provedor ITP** (Dock, Iniciador.com.br) | Você inicia pagamentos sem se tornar ITP | Por transação | Média |
| **Ser participante direto** | Você se torna uma instituição autorizada pelo BCB | R$ 500k a R$ 2mi + | Altíssima — 12 a 24 meses |

No contexto do Open Finance/Open Banking no Brasil, ITP significa Iniciador de Transação de Pagamento.
É uma empresa autorizada pelo Banco Central do Brasil para iniciar um pagamento em nome do cliente, sem precisar guardar o dinheiro dele.

O que um ITP faz:
- Inicia pagamentos via Pix
- Permite pagamentos diretamente da conta bancária
- Evita copiar e colar chaves Pix
- Pode integrar pagamentos dentro de aplicativos e sistemas

Para o TMS, o caminho viável é o primeiro: contratar um agregador.

---

## 3. O que Open Finance não faz

Esta é a parte mais importante — e a que mais gera confusão.

### O equívoco comum

Muitas fontes dizem que Open Finance permite "gerar cobranças". Isso é verdade, mas com uma condição crítica que geralmente não é mencionada: **o pagador precisa estar conectado e ter dado consentimento previamente.**

### A diferença fundamental

Imagine duas situações:

**Situação A — Boleto tradicional (como funciona hoje):**
```
Transportadora emite boleto → manda por email para o embarcador
Embarcador recebe, abre no banco dele e paga quando quiser
Transportadora não precisa de nenhuma conexão prévia com o embarcador
```

**Situação B — Cobrança via Open Finance (ITP — Iniciador de Transação de Pagamento):**
```
Embarcador precisa ter instalado o app do ITP
Embarcador precisa ter conectado a conta bancária no app
Embarcador precisa ter autorizado a transportadora a cobrar
→ Só então a transportadora consegue iniciar a cobrança
```

Para uma transportadora que tem 200 clientes (embarcadores) diferentes, pedir que cada um deles faça esse processo de conexão é praticamente inviável. A maioria vai recusar ou simplesmente ignorar.

### O que Open Finance cobre e o que não cobre no TMS

| Operação | Open Finance resolve? | Por quê |
|---|---|---|
| Emitir boleto para qualquer embarcador | ❌ Não | Embarcador não tem conexão prévia |
| Gerar QR Code Pix cobv para qualquer pagador | ❌ Não | Mesma razão |
| Arquivo CNAB de remessa e retorno | ❌ Não cobre | Fora do escopo regulatório |
| Pagamentos em lote para fornecedores | ✅ Sim | A transportadora controla sua própria conta |
| Extrato e saldo multi-banco | ✅ Sim | Com consentimento uma vez, acesso contínuo |
| Pix Automático para clientes fixos | ✅ Sim | Cliente autoriza uma vez, débito recorrente |
| Consultar saldo antes de pagar | ✅ Sim | Dado do próprio usuário |

**Conclusão prática:** Open Finance resolve a parte de pagamentos de saída e leitura de dados. Para emissão de cobranças (boleto, Pix cobv) para qualquer cliente — que é o coração do negócio do TMS — ainda é necessária a integração direta com o banco.

---

## 4. Os dois cenários da Brudam

Durante a análise, identificamos que a Brudam tem dois cenários financeiros distintos, com necessidades e soluções diferentes.

### Cenário 1 — Os clientes do TMS (transportadoras)

As transportadoras precisam:
- Emitir boleto para os embarcadores (quem paga o frete)
- Gerar QR Code Pix cobv para receber
- Pagar motoristas e fornecedores

**O problema:** os embarcadores são centenas de empresas diferentes, sem conexão prévia com o TMS. Não é possível exigir que todos se conectem via Open Finance antes de pagar uma nota fiscal de frete.

**O que resolve:** integração direta com o banco da transportadora via API proprietária — boleto registrado na conta dela, cobv no banco dela. Isso exige Hub Financeiro ou parceiro.

### Cenário 2 — A própria Brudam

A Brudam precisa:
- Cobrar mensalidade dos clientes do TMS (as transportadoras)
- Pagar comissões aos vendedores quando uma cobrança entra

**A diferença:** aqui a Brudam tem um relacionamento estabelecido com seus clientes. A transportadora já é cliente, já tem contrato. É viável pedir que ela autorize um débito automático via Open Finance uma única vez no início do contrato.

**O que resolve para a Brudam:**
- **Cobrar clientes:** Pix Automático via Open Finance — cliente autoriza uma vez, Brudam cobra todo mês sem boleto
- **Pagar vendedores:** Pix direto ou pagamento em lote quando a cobrança entra

**Por que isso importa:** para a Brudam em si, um agregador como Pluggy ou TecnoSpeed resolve bem e elimina a necessidade de gerenciar certificados por banco. Mas para os clientes do TMS emitindo cobranças, não resolve.

---

## 5. Como funciona a integração banco a banco

### O que acontece quando você integra diretamente com um banco

Quando o TMS (ou o Hub) integra com um banco sem intermediário, é como contratar um funcionário que fala fluente o idioma daquele banco. Esse funcionário precisa:

1. Se apresentar ao banco com credenciais válidas (autenticação)
2. Seguir as regras e formatos específicos daquele banco
3. Tratar os erros do jeito que aquele banco responde
4. Renovar as credenciais periodicamente

Cada banco tem regras diferentes. Veja o que encontramos nos três bancos que analisamos em detalhe:

### Autenticação — como o Hub "prova" que é o cliente para o banco

**Padrão OAuth 2.0 (todos os bancos):**
É como um crachá de acesso. O Hub apresenta `client_id` + `client_secret` para o banco e recebe um token temporário. Esse token é usado em todas as requisições por um período (geralmente 1 hora) e depois precisa ser renovado.

**mTLS — Autenticação mútua (obrigatório para Pix):**
O Banco Central exige que, além do OAuth, as comunicações Pix usem um certificado digital. É como um crachá físico com chip — além da senha, você também precisa apresentar o documento. Isso garante que só o sistema autorizado consegue fazer chamadas, mesmo que alguém intercepte o token OAuth.

### Comparativo de autenticação por banco

| Banco | Boleto | Pix |
|---|---|---|
| Itaú | OAuth + certificado dinâmico | OAuth + mesmo certificado |
| Santander | OAuth + X-Application-Key | OAuth + mTLS (cert A1) |
| Banrisul | OAuth + código beneficiário | OAuth + mTLS |

### Endpoints e formatos — cada banco fala diferente

**Exemplo real: criar uma cobrança Pix com vencimento**

No Banrisul:
```
PUT https://mtls-api.banrisul.com.br/pix/api-mtls/cobv/{txid}
{
  "calendario": { "dataDeVencimento": "2026-07-30" },
  "valor": { "original": "1500.00" },
  "devedor": { "cnpj": "12345678000100", "nome": "Empresa Pagadora SA" },
  "chave": "chave-pix@transportadora.com.br"
}
```

No Santander:
```
PUT https://trust-pix.santander.com.br/cobv/{txid}
{
  "calendario": { "dataDeVencimento": "2026-07-30" },
  "valor": { "original": "1500.00" },
  "devedor": { "cnpj": "12345678000100", "nome": "Empresa Pagadora SA" },
  "chave": "chave-pix@transportadora.com.br"
}
```

Nesse caso, a estrutura é parecida — porque ambos seguem o padrão BCB para Pix. Mas o boleto é completamente diferente entre os bancos:

No Banrisul, para ativar o BoletoPix você coloca:
```json
"hibrido": { "autoriza": "S" }
```

No Santander, para a mesma coisa você coloca:
```json
"txId": "abc123",
"key": { "dictKey": "chave@banco.com" }
```

O Hub Financeiro traduz tudo isso. O TMS manda sempre o mesmo comando, o Hub adapta para cada banco.

### O que é necessário por banco para entrar em produção

**Antes de qualquer integração, o cliente (transportadora) precisa ter com o banco:**

| Pré-requisito | Descrição |
|---|---|
| Conta PJ ativa | Óbvio, mas precisa estar ativa |
| Convênio de cobrança | Acordo com o banco para emitir boletos — feito pelo gerente |
| Chave Pix cadastrada | CNPJ, e-mail, telefone ou aleatória |
| Certificado digital A1 (Santander) | O mesmo que usa para NF-e/CT-e |
| Acesso ao portal de desenvolvedor | Depende do banco — alguns têm portal, outros exigem e-mail |

**Depois de ter esses dados, o processo de ativação tem um tempo de espera bancário:**

| Banco | Como ativar | Tempo estimado |
|---|---|---|
| Banrisul | E-mail para equipe de cobrança + validação de 5 testes | 5 a 15 dias úteis |
| Santander | Portal do desenvolvedor + ativação online | 3 a 10 dias úteis |
| Itaú | Solicitação via gerente + geração de certificado | 5 a 10 dias úteis |

---

## 6. O processo de certificado digital

### Por que existe essa complexidade

O Banco Central exige que todas as comunicações Pix usem um certificado digital (mTLS). Isso garante que só o sistema autorizado faz as chamadas. É a mesma lógica de um certificado SSL em sites — mas dos dois lados: o banco verifica quem está chamando, e o chamador verifica que está falando com o banco certo.

O problema é que **cada banco implementa esse processo de um jeito diferente**.

### Itaú — Certificado Dinâmico (gerado pela Brudam)

Este é o caso mais trabalhoso. O Itaú não usa o certificado A1 da empresa (o da nota fiscal). Ele gera um certificado específico para a integração.

**Como funciona:**

```
1. Transportadora solicita ao Itaú a liberação da API
   (via gerente ou Central Middle Market: 0800-770-1685)
        ↓
2. Itaú envia por e-mail:
   • Novo client_id (código único da empresa, ex: ebe27ae3-f2da-4d39-...)
   • Planilha Excel com token temporário — válido por 7 dias
        ↓ ← ATENÇÃO: 7 dias para agir, contados a partir do recebimento
3. Brudam usa esse token para gerar o certificado:
   • Cria uma chave privada (arquivo .key — fica só com a Brudam)
   • Cria um pedido de certificado (CSR) com os dados da empresa
   • Envia o CSR ao Itaú
   • Itaú assina e devolve o certificado (.crt)
   • Brudam une .key + .crt num único arquivo: itau_certificado.pfx
        ↓
4. O arquivo .pfx é colocado no servidor:
   /motor/app/storage/certs/{CNPJ da empresa}/itau_certificado.pfx
        ↓
5. client_id e client_secret são configurados no TMS
        ↓
6. Integração ativa — testada em produção (Itaú não tem sandbox funcional)
```

**Validade:** 1 ano. Quando expira, o processo começa do zero — e o client_id muda.

**Caso real (BRIX-759):**
Em março de 2026, o certificado da transportadora Brix expirou. Erro retornado pelo Itaú:
> *"Certificado expirado. Data de expiração: 2026-03-05T13:17:00Z. Ação: Emita um novo certificado."*

O cliente ficou sem gerar QR Code Pix por dias até a renovação ser feita. O novo client_id e client_secret foram diferentes dos anteriores — ambos precisaram ser atualizados no sistema.

### Santander — Usa o certificado A1 da NF-e

O Santander é mais simples. Ele usa o mesmo certificado digital A1 que a empresa já usa para assinar notas fiscais e CT-e. Toda empresa de transporte já tem esse certificado.

**Como funciona:**

```
1. Cliente acessa developer.santander.com.br com o login PJ
   (precisa ser o Usuário Master da empresa)
        ↓
2. Vai em "Minhas Aplicações" → cria uma nova aplicação em modo Produção
        ↓
3. Faz upload do arquivo .PFX do certificado A1 (o mesmo da NF-e)
        ↓
4. O portal Santander valida o certificado e gera:
   • client_id
   • client_secret
   • X-Application-Key (código fixo da aplicação)
        ↓
5. Cliente entrega esses três dados à Brudam
        ↓
6. Brudam cria o Workspace (via API) e configura o TMS
```

**Vantagem:** a empresa já tem o certificado. Não precisa gerar nada novo.
**Atenção na renovação:** quando o A1 vence (1 a 3 anos), o cliente precisa subir o novo no portal Santander.

### Banrisul — Processo a confirmar

A documentação pública do Banrisul menciona mTLS (as URLs têm o prefixo `mtls-api`) mas não detalha o processo de certificado. Deve ser confirmado no primeiro onboarding real com esse banco.

### Resumo — quem faz o quê

| Banco | Quem gera o cert | Quem faz upload | Validade | Muda na renovação |
|---|---|---|---|---|
| Itaú | Brudam (via Postman) | Brudam envia CSR ao Itaú | 1 ano | client_id e secret mudam sempre |
| Santander | Cliente já tem (A1 NF-e) | Cliente no portal Santander | 1–3 anos | Depende do A1 |
| Banrisul | A confirmar | A confirmar | A confirmar | A confirmar |

---

## 7. Os parceiros do mercado

### O que um parceiro realmente faz

Um parceiro de integração bancária (como TecnoSpeed, Celcoin ou Pluggy) é uma empresa que já fez o trabalho de integrar com os bancos. Em vez de você integrar com o Itaú, o Banrisul e o Santander separadamente, você integra com o parceiro uma única vez e ele se comunica com todos os bancos.

**Analogia:** é como contratar uma transportadora internacional que já tem contratos com todas as companhias aéreas e alfândegas do mundo. Você entrega o pacote para eles, e eles resolvem tudo. Se a companhia aérea mudar uma regra, é problema deles, não seu.

### O ponto crítico que ninguém menciona

Mesmo usando um parceiro, **a burocracia com o banco ainda existe**. O cliente ainda precisa:
- Ter conta PJ ativa
- Ter convênio de cobrança com o banco
- Gerar o certificado (no caso do Itaú, o processo de 7 dias ainda acontece)
- Fornecer client_id e client_secret para o parceiro

O que muda: o .pfx vai para o sistema do parceiro, não para o servidor da Brudam. Mas o processo de obtê-lo é igual.

---

### TecnoSpeed — PlugBank

**O que é:** um hub de APIs financeiras focado em software houses e ERPs brasileiros. É o produto mais alinhado com o cenário do TMS.

**O que oferece:**

| Produto | O que faz | Bancos |
|---|---|---|
| API de Boleto | Emite boleto registrado | 40+ bancos homologados |
| API de Pix | Gera QR Code cobv e cob | Mesmos bancos |
| CNAB | Gera remessa e processa retorno | CNAB 240 e 400 padronizados |
| DDA | Consulta boletos a pagar | — |
| Contas a Pagar | Pagamentos de fornecedores | — |
| Extrato | Consolida extratos bancários | — |

**Como funciona na prática:**

```
TMS envia:
{
  "banco": "341",       ← código do banco (341 = Itaú)
  "operacao": "boleto",
  "valor": 1500.00,
  "vencimento": "2026-07-30",
  "pagador": { ... }
}
        ↓
TecnoSpeed PlugBank traduz e envia para a API do Itaú
        ↓
Itaú registra o boleto e retorna código de barras + linha digitável
        ↓
TecnoSpeed devolve para o TMS no formato padrão deles
```

O TMS não precisa saber que o banco é Itaú ou Banrisul. Só manda o comando.

**Modelo de negócio:** Software house (Brudam) integra uma vez. Cada cliente do TMS configura as credenciais do próprio banco no painel TecnoSpeed.

**Custo:** não é público — sob consulta por volume. Estimativa de mercado para 500 clientes com 40.000 boletos/mês: R$ 22.000 a R$ 45.500/mês.

---

### Celcoin

**O que é:** uma infraestrutura financeira completa (Banking as a Service — BaaS). O cliente não usa o próprio banco — ele abre uma conta digital dentro da plataforma Celcoin.

**O que oferece:** boleto, Pix, pagamentos, cartões, conta digital, extrato.

**A diferença fundamental:**

```
TecnoSpeed:
  Transportadora tem conta no Itaú → boleto emitido na conta Itaú dela
  Dinheiro cai na conta Itaú da transportadora

Celcoin:
  Transportadora abre conta BaaS na Celcoin → boleto emitido na conta Celcoin
  Dinheiro cai na conta Celcoin → transportadora transfere para o Itaú dela depois
```

**Quando faz sentido:** para empresas que querem criar produtos financeiros embutidos (embedded finance) ou que não têm banco preferido. Para o TMS, onde as transportadoras já têm contas bancárias estabelecidas, pode gerar atrito.

**Custo:** transacional, sob consulta. Significativamente menor que a média de mercado.

---

### Pluggy

**O que é:** especialista em Open Finance — dados bancários e Pix ITP. Autorizada pelo BCB como ITP.

**O que oferece:**

| Produto | Status |
|---|---|
| Extrato e saldo multi-banco | ✅ Principal produto |
| Pix ITP (iniciação de pagamento) | ✅ Homologado |
| Pix Automático | ✅ Lançado em 2025 |
| Pagamentos em lote (via Open Finance) | ✅ Em produção |
| Boleto registrado | ⚠️ Beta — suporte só ao Inter no momento |
| CNAB | ❌ Não oferece |

**Quando faz sentido:** para o Cenário 2 da Brudam (cobrar clientes do TMS e pagar vendedores). Para o Cenário 1 (TMS emitindo cobranças para embarcadores), ainda não resolve.

**Custo:** a partir de R$ 2.500/mês (plano básico até 20 contas). Enterprise: consulta.

---

### Asaas

**O que é:** plataforma de cobranças simples, sem mensalidade, focada em pequenas empresas.

**O que oferece:** boleto, Pix, recorrência. Sem CNAB, sem pagamentos em lote, sem extrato multi-banco.

**Custo:** R$ 1,99 por transação.

**Para 500 clientes com 55.000 transações/mês:** R$ 109.000/mês — completamente inviável.

**Quando faz sentido:** para validar um produto com 1 ou 2 clientes iniciais, antes de qualquer integração bancária.

---

### Comparativo dos parceiros

| | TecnoSpeed | Celcoin | Pluggy | Asaas |
|---|---|---|---|---|
| Boleto registrado | ✅ 40+ bancos | ✅ Conta BaaS | ⚠️ Só Inter (beta) | ✅ |
| Pix cobrança | ✅ | ✅ | ✅ (via Open Finance) | ✅ |
| CNAB | ✅ | Não confirmado | ❌ | ❌ |
| Pagamentos em lote | ✅ | ✅ | ✅ (via Open Finance) | ❌ |
| Extrato multi-banco | ✅ | ✅ | ✅ Foco principal | ❌ |
| Usa banco existente do cliente | ✅ Sim | ❌ Abre conta BaaS | ✅ (dados) | ✅ |
| Certificado ainda necessário (Itaú) | ✅ Sim — igual | ✅ Sim — igual | N/A | N/A |
| Foco | Software house/ERP | BaaS/Fintech | Open Finance | PME simples |

---

## 8. Comparativo de custos

### Premissas do cenário Brudam (500 clientes TMS)

| Variável | Volume estimado/mês |
|---|---|
| Boletos emitidos | 40.000 |
| Pix cobranças | 15.000 |
| Pagamentos (fornecedores/motoristas) | 10.000 |
| **Total de operações** | **65.000** |

### Cenário A — Parceiro (TecnoSpeed ou Celcoin)

| Operação | Volume | Custo unitário estimado | Total estimado |
|---|---|---|---|
| Boleto emitido | 40.000 | R$ 0,40 – R$ 0,80 | R$ 16.000 – R$ 32.000 |
| Pix Cobrança | 15.000 | R$ 0,20 – R$ 0,50 | R$ 3.000 – R$ 7.500 |
| Pagamentos | 10.000 | R$ 0,30 – R$ 0,60 | R$ 3.000 – R$ 6.000 |
| **Total mensal** | | | **R$ 22.000 – R$ 45.500** |
| **Total anual** | | | **R$ 264.000 – R$ 546.000** |

> ⚠️ Preços não são públicos — estimativa baseada em referências de mercado. Negociação por volume pode reduzir em até 50%. Solicitar proposta formal com volumes reais.

### Cenário B — Hub próprio (desenvolvimento interno)

| Item | Custo |
|---|---|
| Desenvolvimento MVP (3–4 bancos principais) | 3 a 6 meses de equipe dev |
| Custo por transação | **R$ 0,00** |
| Custo mensal operacional | **~R$ 0** (só infraestrutura de servidor) |
| Manutenção anual | ~1 desenvolvedor parcial |

O cliente paga as tarifas bancárias diretamente ao banco (como já faz hoje). O Hub não adiciona nenhum custo por transação.

### Cenário C — Asaas (referência de comparação)

| Total mensal | Total anual |
|---|---|
| R$ 109.450 | R$ 1.313.400 |

Inviável para esse volume.

### Resumo do retorno do investimento (Hub próprio)

Se o desenvolvimento do MVP custar equivalente a 4 meses de equipe, e o custo de parceiro for R$ 22.000/mês no mínimo:

```
Breakeven: 4 meses de dev ÷ R$ 22.000/mês de economia = ~5 a 8 meses

Após o breakeven, a economia anual é de R$ 264.000 a R$ 546.000
```

Em 3 anos, a diferença entre usar parceiro e hub próprio pode ultrapassar R$ 1 milhão.

---

## 9. O que o parceiro resolve e o que ele não resolve

### O que o parceiro realmente entrega

| O parceiro resolve | O parceiro NÃO resolve |
|---|---|
| Desenvolvimento das integrações com os bancos | Burocracia com o banco (convênio, homologação) |
| Manter as APIs atualizadas quando o banco muda | Processo de certificado com o banco (Itaú: 7 dias, etc.) |
| Suportar 40 bancos em vez de 1 | Renovação anual de certificados |
| Padronizar o formato de resposta | Atualizar credenciais quando o banco muda |
| Infraestrutura de retry, circuit breaker | Dependência de preço do fornecedor |
| Entrar em produção em semanas | Risco de encerramento ou mudança de preço do parceiro |

### A burocracia que nunca some

Independente de usar parceiro ou hub próprio, **o cliente (transportadora) sempre precisará:**

1. Ter conta ativa no banco com convênio de cobrança
2. Solicitar habilitação de API ao banco
3. Gerar o certificado (no caso do Itaú, o processo de 7 dias)
4. Renovar o certificado todo ano (Itaú)
5. Atualizar credenciais quando mudarem

A diferença é que com parceiro, as credenciais vão para o painel do parceiro. Com hub próprio, vão para o servidor da Brudam. O trabalho de obtê-las é o mesmo.

---

## Casos de uso práticos para o TMS

Esta seção resume onde o Open Finance agrega valor dentro do contexto de uma transportadora.

### Cenário 1 — Receber do embarcador (B2B)

Exemplo:
- Transportadora presta um serviço para um embarcador.
- O Open Finance não envia automaticamente uma cobrança ao embarcador.
- A cobrança continua sendo feita por boleto, Pix Cobrança, ERP ou portal.
- Se o embarcador desejar pagar via Open Finance, um responsável deverá iniciar e autorizar o pagamento no banco.

**Conclusão:** o Open Finance não substitui boleto, Pix Cobrança ou CNAB para cobrar qualquer cliente.

### Cenário 2 — Pagar motoristas

- O TMS calcula os pagamentos.
- O Hub Financeiro inicia os pagamentos.
- O responsável financeiro autoriza no banco.
- Os Pix são enviados.

**Conclusão:** um dos principais benefícios do Open Finance.

### Cenário 3 — Pagar fornecedores

O mesmo fluxo vale para postos, oficinas, seguradoras, pedágios e parceiros.

### Cenário 4 — Consulta financeira

O Hub pode consolidar saldos, extratos e movimentações de vários bancos em uma única tela.

### Resumo

| Operação | Valor agregado |
|---|---|
| Consultar saldo | ⭐⭐⭐⭐⭐ |
| Consultar extrato | ⭐⭐⭐⭐⭐ |
| Conciliação | ⭐⭐⭐⭐⭐ |
| Pagar motoristas | ⭐⭐⭐⭐⭐ |
| Pagar fornecedores | ⭐⭐⭐⭐⭐ |
| Pagamentos em lote | ⭐⭐⭐⭐⭐ |
| Receber de embarcadores | ⭐⭐☆☆☆ |
| Emitir boletos | ☆☆☆☆☆ |
| CNAB | ☆☆☆☆☆ |
| Pix Cobrança | ★☆☆☆☆ |

**Mensagem principal**

O Open Finance complementa o Hub Financeiro. Seu maior valor está na consulta de dados bancários e nos pagamentos realizados pela própria transportadora, e não na substituição das integrações bancárias para cobrança.

---

## 10. Recomendação e caminho sugerido

### O que a análise mostrou

Após avaliar Open Finance, parceiros e hub próprio sob todos os ângulos — operacional, técnico e financeiro — a conclusão é:

**Open Finance não substitui a integração bancária direta para o TMS.** Resolve dados e pagamentos de saída. Não resolve emissão de cobranças para qualquer pagador sem conexão prévia.

**Parceiro resolve o problema técnico rapidamente, mas a um custo recorrente alto** — R$ 22k a R$ 46k/mês que cresce com o volume de clientes.

**Hub próprio elimina o custo recorrente mas exige investimento inicial de desenvolvimento** — 3 a 6 meses para um MVP funcional com os 3 a 4 bancos de maior uso.

### Caminho recomendado — três fases

#### Fase 1 — Curto prazo (0 a 6 meses): parceiro para validar

Contratar TecnoSpeed PlugBank como camada de integração bancária temporária.

- Objetivo: colocar o produto no mercado rapidamente
- Os clientes do TMS configuram as credenciais dos bancos no painel TecnoSpeed
- A Brudam valida o produto com clientes reais
- Custo: R$ 22k a R$ 46k/mês (investimento de validação)

#### Fase 2 — Médio prazo (6 a 18 meses): hub próprio banco a banco

Desenvolver o Hub Financeiro interno, começando pelos bancos de maior volume.

- Identificar os 3 bancos que concentram 70% dos clientes do TMS
- Desenvolver os adapters para esses bancos
- Migrar clientes do TecnoSpeed para o Hub à medida que os bancos ficam prontos
- Zerar o custo por transação progressivamente

#### Fase 3 — Longo prazo (18 meses+): Open Finance como complemento

Com o Hub estável, adicionar Open Finance via Pluggy para:
- Extrato consolidado multi-banco para os clientes do TMS
- Pix Automático para cobranças da Brudam com os clientes do TMS
- Pagamentos em lote para motoristas e fornecedores

**E para a Brudam internamente (Cenário 2):**
- Iniciar Open Finance via Pluggy já na Fase 1 para as cobranças internas da Brudam
- Pix Automático para mensalidades e Pix direto para comissões de vendedores

### Visão final da arquitetura

```
Hub Financeiro Brudam
│
├── Módulo Clientes TMS (transportadoras)
│   ├── Boleto registrado ──────────────── API direta por banco
│   ├── Pix Cobrança (cobv, cob) ──────── API direta por banco
│   ├── CNAB remessa/retorno ───────────── API direta por banco
│   └── Pix pagamentos (motoristas) ────── API direta ou Open Finance ITP
│
└── Módulo Brudam interno
    ├── Cobrança de clientes TMS ──────── Pix Automático via Open Finance
    └── Pagamento de comissões ─────────── Pix direto da conta Brudam
```

### Resumo executivo para a diretoria

| Pergunta | Resposta |
|---|---|
| Open Finance substitui o Hub? | Não — cobre dados e pagamentos, não cobranças |
| Parceiro como TecnoSpeed resolve? | Sim, mas a R$ 22k–46k/mês recorrentes |
| Hub próprio é viável? | Sim — 3 a 6 meses para MVP, sem custo por transação depois |
| A burocracia bancária some com o parceiro? | Não — certificados e credenciais continuam sendo necessários |
| Qual o caminho mais seguro? | Parceiro para validar → Hub próprio para escalar → Open Finance como complemento |
| Em quanto tempo o Hub próprio se paga? | 5 a 8 meses de economia em relação ao parceiro |

---

*Documento elaborado em: 2026-06-27*
*Baseado em: análise de APIs Banrisul, Santander e Itaú; caso real BRIX-759; pesquisa de mercado TecnoSpeed, Celcoin, Pluggy, Asaas; regulamentação Open Finance BCB.*
