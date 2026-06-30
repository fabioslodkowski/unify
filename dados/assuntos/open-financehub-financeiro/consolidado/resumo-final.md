# Resumo Consolidado — Open Financehub Financeiro

## 🎯 Resumo Executivo

A análise consolidada indica que o **Open Financehub Financeiro** deve ser tratado como uma **plataforma/hub financeiro unificado**, capaz de padronizar integrações bancárias para o TMS/ERP, mas sem assumir que o **Open Finance** ou a **Pluggy** substituem integralmente as integrações bancárias tradicionais.

O ponto central é que **Open Finance resolve muito bem leitura, consentimento, saldo, extrato, transações e parte de iniciação de pagamentos**, mas **não substitui, sozinho, serviços operacionais essenciais para um TMS**, como:

- emissão e registro de boletos;
- CNAB remessa/retorno;
- pagamentos em lote;
- baixa/cancelamento de boletos;
- conciliação bancária operacional;
- Pix Cobrança;
- homologações específicas por banco.

A recomendação inicial é seguir uma **arquitetura híbrida**:

1. **Criar um Hub Financeiro interno com payloads padronizados**, servindo como camada única para o TMS.
2. **Avaliar um parceiro especializado em integrações bancárias para ERPs/TMSs**, com destaque inicial para **TecnoSpeed PlugBank**, por cobrir boleto, Pix, CNAB, pagamentos, conciliação e múltiplos bancos.
3. **Usar Pluggy como complemento estratégico**, principalmente para Open Finance, conexão bancária, saldo, extrato, consentimento, widget e inteligência de dados financeiros.
4. **Evitar construir tudo internamente desde o início**, devido ao alto custo de homologação, manutenção por banco, certificados mTLS, webhooks, layouts CNAB e variações de APIs.

---

## 📋 Contexto

O projeto discute a criação de um **Hub Financeiro/Open Financehub** para centralizar a comunicação entre o TMS/ERP e bancos/parceiros financeiros.

Segundo os arquivos analisados, o cenário atual parece envolver:

- geração de **CNAB de remessa** pelo TMS;
- processamento pelo banco;
- importação de **CNAB de retorno**;
- algumas integrações via **Pix Cobrança por API**;
- necessidade futura de substituir ou reduzir dependência de arquivos bancários;
- suporte a múltiplos bancos e múltiplos clientes;
- cenário-base de aproximadamente **500 clientes do TMS**, cada um com sua própria conta bancária.  
  Fonte: `analiseparceiros.md` — Fabio.

O objetivo estratégico é evoluir de um modelo baseado em arquivos e layouts específicos para um modelo baseado em:

```text
TMS/ERP
   ↓
Payload financeiro padrão
   ↓
Hub Financeiro
   ↓
APIs bancárias / parceiros / Open Finance / CNAB quando necessário
```

Também foi documentado que já existe um módulo inicial integrado à **Pluggy** no Hub Financeiro Brudam, usando React/SPA e backend Python, com fluxo de conexão bancária via widget.  
Fonte: `openfinanceknowledgebase.md` — Arthur.

---

## 💡 Principais Pontos Levantados

### 🏦 1. Open Finance no Brasil

O Open Finance é o sistema financeiro aberto regulamentado pelo Banco Central do Brasil, permitindo o compartilhamento padronizado de dados e serviços financeiros mediante consentimento do cliente.

Principais fases citadas:

| Fase | Escopo | Status |
|---|---|---|
| Fase 1 | Dados públicos das instituições financeiras | Concluída |
| Fase 2 | Dados de clientes: conta, cartão, câmbio e crédito | Concluída |
| Fase 3 | Iniciação de pagamentos: Pix, boleto, TED | Concluída |
| Fase 4 | Câmbio, investimentos, seguros e previdência | Em expansão |
| Portabilidade de crédito | Portabilidade via Open Finance | Início previsto/expansão entre 2025 e 2026 |

Fonte: `openfinanceknowledgebase.md` — Arthur.

Atores principais:

- **Detentor de dados:** banco que possui os dados do cliente.
- **Receptor de dados:** empresa que recebe os dados mediante consentimento.
- **ITP — Iniciador de Transação de Pagamento:** entidade autorizada a iniciar pagamentos.
- **Agregador/middleware:** empresas como Pluggy e Belvo, que abstraem a complexidade das conexões.

Fontes regulatórias citadas:

- Resolução BCB nº 32/2020;
- Resolução Conjunta nº 1/2020;
- participação obrigatória para instituições financeiras com mais de 5 milhões de clientes, conforme evolução regulatória citada.

Fonte: `openfinanceknowledgebase.md` — Arthur.

---

### 📈 2. Status de mercado do Open Finance

Foram levantados indicadores relevantes do mercado brasileiro:

| Indicador | Dado citado |
|---|---|
| Consentimentos ativos no Brasil | 143+ milhões |
| Crescimento de volume de dados | +110% entre dez/2024 e nov/2025 |
| Crédito originado via Open Finance | R$ 31 bilhões acumulados até meados de 2025 |
| Crédito no 1º semestre de 2025 | R$ 12 bilhões |
| Taxa de sucesso em conexões Belvo | Evolução de 52% para 63% em 12 meses |
| Portabilidade de crédito | Live desde nov/2025, segundo o documento |

Fonte: `openfinanceknowledgebase.md` — Arthur.

A tendência apontada é que, em 2026, o mercado demandará cada vez mais soluções que transformem conectividade bancária em **inteligência acionável**, como:

- score;
- análise de risco;
- automação de cobrança;
- conciliação;
- classificação de transações;
- inteligência financeira.

---

### 🔌 3. Módulo Pluggy já implementado

Foi descrito um módulo **Open Finance** integrado com a **Pluggy** no Hub Financeiro Brudam.

Características informadas:

- SPA React em `index.html`;
- porta `3456`;
- uso do **Pluggy Connect Widget**;
- autenticação e coleta de dados bancários via OAuth2;
- backend em `server.py`.

Fluxo implementado:

```text
Usuário clica em "Conectar Banco"
        ↓
Backend gera token em /api/pluggy/connect-token
        ↓
Pluggy Connect Widget é carregado via CDN
        ↓
Usuário autentica no banco via OAuth2
        ↓
Callback onSuccess salva itemId no backend
        ↓
/api/pluggy/accounts lista contas e saldos
        ↓
/api/pluggy/transactions?accountId=X lista extrato/transações
```

Endpoints visíveis no material:

| Endpoint | Método | Finalidade |
|---|---|---|
| `/api/pluggy/config` | GET | Verificar se a Pluggy está configurada |
| `/api/pluggy/config` | POST | Salvar configuração da Pluggy |
| `/api/pluggy/connect-token` | Provável POST/GET | Gerar token de conexão |
| `/api/pluggy/accounts` | GET | Listar contas e saldos |
| `/api/pluggy/transactions?accountId=X` | GET | Consultar extrato/transações |

Fonte: `openfinanceknowledgebase.md` — Arthur.  
Observação: parte do conteúdo do arquivo estava truncada, então os detalhes completos dos endpoints não estavam totalmente visíveis.

---

### 🧾 4. Não existe “CNAB via API” padronizado pela FEBRABAN

Um dos pontos mais importantes levantados é que **não existe atualmente um padrão FEBRABAN de “CNAB via API”**.

O que existe na prática:

- CNAB continua sendo arquivo padronizado por layouts bancários/FEBRABAN;
- bancos estão oferecendo APIs REST equivalentes a operações que antes eram feitas por CNAB;
- cada banco implementa sua própria API, nomenclatura, payloads, autenticação e processo de homologação;
- o Open Finance padroniza dados e iniciação de pagamentos, mas **não substitui as APIs corporativas de cobrança e pagamento usadas por ERPs/TMSs**.

Fonte: `analise1.md` — Fabio.

Equivalência prática:

| CNAB tradicional | Equivalente via API |
|---|---|
| Remessa de boletos | Criar/registrar boleto |
| Retorno de boletos | Consultar boleto + webhook |
| Baixa de boleto | Baixar/cancelar boleto |
| Pagamentos | Enviar pagamento |
| Extrato | Consultar extrato |
| Saldo | Consultar saldo |
| Pix | Criar cobrança Pix, consultar e receber webhook |

Fonte: `analise1.md` — Fabio.

---

### 🔁 5. Serviços bancários mais comuns via API

Os serviços mais comuns disponibilizados pelos grandes bancos são:

- cadastro/registro de boletos;
- alteração de boletos;
- cancelamento/baixa de boletos;
- consulta de boletos;
- webhook de liquidação;
- Pix imediato;
- Pix Automático;
- pagamentos via TED, Pix e tributos;
- extrato;
- saldo;
- DDA;
- débito automático em alguns bancos;
- arquivos de conciliação quando necessário.

Fonte: `analise1.md` — Fabio.

Padrões de endpoints normalmente observados:

```http
POST /boletos
GET /boletos/{id}
PATCH /boletos/{id}

POST /pix/cob
GET /pix/cob/{txid}

POST /pagamentos
GET /pagamentos/{id}

GET /contas/saldo
GET /contas/extrato
```

Fonte: `analise1.md` — Fabio.

---

### 🏗️ 6. Arquitetura sugerida para o Hub Financeiro

Foi proposta a ideia de um **Hub Bancário/Financeiro** como camada intermediária entre o TMS e múltiplos bancos:

```text
ERP/TMS
   │
API única interna
   │
Hub Financeiro
   │
Banco do Brasil API
Itaú API
Bradesco API
Santander API
Sicredi API
Sicoob API
Inter API
Caixa API
...
```

Fonte: `analise1.md` — Fabio.

A visão mais moderna é o TMS/ERP enviar um **JSON padronizado**, enquanto o Hub converte esse payload para:

- API específica de cada banco;
- layout CNAB específico, quando necessário;
- eventos internos padronizados;
- chamadas para parceiros externos;
- integrações Open Finance.

Quando o banco envia webhook de liquidação, pagamento ou Pix, o Hub converte para um **evento único e padronizado para o ERP/TMS**.

Fonte: `analise1.md` — Fabio.

---

### 🧩 7. Proposta de módulos da plataforma

Foi sugerido que o projeto não seja visto apenas como um “Hub Bancário”, mas como uma **Plataforma Financeira Unificada** ou **Financial Infrastructure Platform**.

Sugestão de módulos:

```text
BRIX Platform / Open Financehub

├── Connect
├── Banking
├── Payments
├── Collections
├── Open Finance
├── Intelligence
├── Webhooks
├── Dashboard
├── Developers
└── Admin
```

Fonte: `analise1.md` — Fabio.

Destaques:

#### Connect

O módulo **Connect** foi apontado como um dos maiores ativos da Pluggy, pois entrega um componente pronto para:

- escolha do banco;
- login;
- OAuth;
- MFA;
- consentimento;
- conexão da conta.

Foi sugerida a criação de algo semelhante:

```html
<script src="brix-connect.js"></script>
```

Fonte: `analise1.md` — Fabio.

#### Intelligence

Foi sugerido um módulo de inteligência para enriquecer dados bancários.

Exemplo:

Dado bruto do banco:

```text
PIX MERCADO123
```

Dado enriquecido:

```text
Categoria: Supermercado
Nome: Mercado XYZ
Cidade: Porto Alegre
Latitude: ...
```

Fonte: `analise1.md` — Fabio.

#### Dashboard

Foi sugerido um painel para gestão de:

- empresas;
- contas;
- conectores;
- logs;
- webhooks;
- pagamentos;
- eventos;
- integrações;
- administração da plataforma.

Fonte: `analise1.md` — Fabio.

---

### 🏦 8. Análise de APIs bancárias específicas

#### Banrisul — API de Cobrança/Boleto

A API de Cobrança do Banrisul usa OAuth 2.0 Client Credentials.

Fluxo de autenticação:

```http
POST /oauth/token
client_id + client_secret + grant_type=client_credentials
→ Bearer token com validade de 3600s
```

Headers obrigatórios:

```http
Authorization: Bearer {token}
bergs-beneficiario: {código 13 dígitos}
Accept: application/json
Content-Type: application/json
Accept-Encoding: gzip
```

Ambientes:

| Ambiente | URL |
|---|---|
| Sandbox | `https://apidev.banrisul.com.br/cobranca/v1` |
| Produção | `https://api.banrisul.com.br/cobranca/v1` |

Endpoints citados:

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/boletos` | Registra boleto |
| GET | `/boletos` | Lista boletos com filtros |
| GET | `/boletos/{id}` | Consulta boleto específico |
| PATCH | `/boletos/{id}` | Altera vencimento ou abatimento |
| POST | `/boletos/{id}` | Baixa/cancela boleto |
| GET | `/boletos/{id}/emitir` | Gera PDF do boleto |
| POST | `/webhook/testar` | Testa URL de webhook |

Fonte: `analise-inicial-apis-bancos-v01.md` — Fabio.

---

#### Banrisul e Santander — Pix, boleto e BoletoPix

Conclusões transversais levantadas:

| Tema | Observação |
|---|---|
| Autenticação boleto | OAuth 2.0 Client Credentials |
| Autenticação Pix | OAuth 2.0 + mTLS |
| Padrão dos endpoints | REST + JSON, com variação por banco |
| Retorno de boleto | CNAB de retorno ou consulta/webhook dependendo do banco |
| Retorno de Pix | Webhook em tempo real |
| Homologação | Processo próprio por banco |
| Certificados mTLS | Necessário gerenciar certificados por banco |
| BoletoPix | Suportado nativamente no Banrisul e Santander; recomendado como padrão |
| Santander Workspace | Santander exige criar Workspace antes de registrar boletos |
| Santander Header | Exige `X-Application-Key` além do Bearer token |
| Santander URLs | Usa prefixo `trust-`, indicando camada mTLS |

Fonte: `analise-inicial-apis-bancos-v01.md` — Fabio.

Impacto arquitetural sugerido:

```text
Hub Financeiro
│
├── Adapter por banco
│   ├── auth/         → OAuth ou OAuth + mTLS
│   ├── cobranca/     → boleto, BoletoPix, cobv, cobr
│   ├── pagamentos/   → TED, Pix out, lote
│   ├── extrato/      → consulta e paginação
│   └── webhook/      → eventos de liquidação
│
└── Módulo de certificados
    └── gerenciar mTLS por banco
```

Fonte: `analise-inicial-apis-bancos-v01.md` — Fabio.

---

### 🔎 9. Avaliação da Pluggy

A Pluggy foi avaliada sob duas perspectivas.

#### O que a Pluggy faz muito bem

- conecta contas bancárias;
- consulta saldo;
- consulta extrato;
- lista transações;
- identifica pagamentos recebidos;
- consolida movimentações de vários bancos;
- inicia pagamentos quando o banco/produto suportar;
- fornece widget pronto de conexão;
- gerencia consentimento Open Finance;
- normaliza dados;
- oferece webhooks;
- pode agregar inteligência financeira.

Fontes:  
`analise1.md` — Fabio.  
`analise2.md` — Fabio.  
`openfinanceknowledgebase.md` — Arthur.

#### Limitações apontadas

A Pluggy **não substitui integralmente** o fluxo operacional de um TMS, especialmente em:

- geração/registro de boletos;
- cancelamento de boletos;
- CNAB remessa e retorno;
- pagamentos em lote;
- pagamento de fornecedores;
- conciliação operacional completa;
- layout e homologação específica de cada banco.

Fonte: `analise2.md` — Fabio.

Conclusão levantada:

> A Pluggy resolve parcialmente a integração. Ela é forte para leitura de dados, Open Finance e conexão bancária, mas não necessariamente substitui as APIs bancárias operacionais usadas por um ERP/TMS.

Fonte: `analise2.md` — Fabio.

Pergunta estratégica sugerida:

> A Pluggy consegue substituir 80% das integrações bancárias do TMS ou apenas a parte de Open Finance?

Fonte: `analise2.md` — Fabio.

---

### 🤝 10. Análise de parceiros

#### TecnoSpeed — PlugBank

Foco: software houses brasileiras, ERPs e TMSs.

Oferece:

- boleto com mais de 40 bancos homologados;
- Pix Cobrança;
- Pix pagamento;
- CNAB remessa e retorno;
- Open Finance;
- conciliação;
- pagamentos em lote.

Modelo:

- software house integra uma vez;
- cada cliente usa o próprio banco;
- PlugBank atua como ponte.

Preço:

- não publicado;
- modelo por volume de transações/emissões;
- necessário solicitar proposta.

Conclusão:

- é o produto mais próximo da necessidade do TMS;
- cobre boleto, Pix, CNAB e Open Finance em uma API;
- forte candidato para solução principal.

Fonte: `analiseparceiros.md` — Fabio.

---

#### Celcoin

Foco: BaaS completo para fintechs, ERPs e marketplaces.

Oferece:

- boleto;
- Pix Cobrança;
- pagamentos;
- extrato consolidado;
- conta digital;
- cartões;
- Open Finance.

Modelo:

- transacional;
- paga conforme uso;
- sem setup elevado, segundo a análise.

Dados citados:

- casos de sucesso: Neon, Sky, PipeImob, Cumbuca;
- processa aproximadamente R$ 30 bilhões/mês.

Conclusão:

- opção robusta;
- pode ser mais ampla do que o necessário para o TMS;
- interessante se houver estratégia futura de serviços financeiros completos.

Fonte: `analiseparceiros.md` — Fabio.

---

#### Pluggy

Foco:

- Open Finance;
- dados financeiros;
- Pix pagamento;
- conexão bancária.

Oferece:

- extrato e dados via Open Finance;
- iniciação de Pix;
- Pix Automático;
- widget de conexão bancária.

Não oferece diretamente, segundo a análise:

- emissão de boletos registrados;
- CNAB;
- pagamentos em lote.

Preço citado:

- a partir de **R$ 2.500/mês** no plano básico, até 20 contas;
- plano enterprise sob consulta.

Conclusão:

- excelente complemento;
- não deve ser considerada solução principal única para boleto/CNAB/pagamentos operacionais do TMS.

Fonte: `analiseparceiros.md` — Fabio.

---

#### Asaas

Foco:

- cobranças simples;
- boleto;
- Pix;
- cartão.

Oferece:

- boleto registrado;
- Pix Cobrança;
- recorrência;
- API completa.

Não oferece:

- CNAB;
- pagamentos em lote para fornecedores;
- Open Finance;
- extrato multibanco.

Preço citado:

- sem mensalidade;
- R$ 1,99 por transação Pix ou boleto;
- promoção inicial de R$ 0,99 nos três primeiros meses.

Conclusão:

- bom para validar cobrança rapidamente;
- limitado para o cenário completo do TMS.

Fonte: `analiseparceiros.md` — Fabio.

---

#### Zoop

Foco:

- marketplaces;
- white-label;
- maquininhas;
- split de pagamentos.

Oferece:

- boleto;
- Pix;
- cartão;
- white-label de pagamentos;
- split.

Preço de referência citado:

- boleto pago: aproximadamente 1,93% + R$ 2,10;
- Pix: aproximadamente 2,72%.

Conclusão:

- modelo percentual pode ficar caro para cobranças de frete com valores altos;
- não parece ideal para TMS.

Fonte: `analiseparceiros.md` — Fabio.

---

#### Dock

Foco:

- BaaS;
- ITP;
- Pix via Open Finance;
- conta digital;
- cartões.

Oferece:

- Pix, inclusive automático;
- boleto;
- conta digital;
- cartões.

Preço:

- não publicado;
- sob consulta.

Conclusão:

- solução robusta;
- mais adequada para fintechs ou empresas que querem lançar produtos financeiros próprios;
- pode ser mais ampla do que a necessidade inicial do TMS.

Fonte: `analiseparceiros.md` — Fabio.

---

## ✅ Pontos de Consenso

1. **Open Finance é importante, mas não resolve tudo sozinho.**  
   Ele é excelente para dados, consentimento, saldo, extrato e iniciação de pagamentos, mas não substitui integralmente boleto, CNAB, pagamentos em lote e APIs corporativas bancárias.

2. **Não existe padrão FEBRABAN de CNAB via API.**  
   Cada banco oferece suas próprias APIs REST/JSON, com padrões parecidos, mas payloads, autenticação, homologação e regras diferentes.

3. **O TMS precisa de uma camada única de abstração.**  
   A ideia de um Hub Financeiro com payload padronizado é considerada correta.

4. **Webhooks são essenciais.**  
   Principalmente para Pix e liquidação em tempo real.

5. **Ainda será necessário lidar com especificidades bancárias.**  
   Exemplos:  
   - `bergs-beneficiario` no Banrisul;  
   - `X-Application-Key` no Santander;  
   - Workspace no Santander;  
   - mTLS para Pix;  
   - homologação própria de cada banco.

6. **BoletoPix deve ser considerado padrão preferencial quando disponível.**  
   Banrisul e Santander foram citados como suportando nativamente esse modelo.

7. **Pluggy é forte em Connect/Open Finance.**  
   O widget de consentimento e conexão bancária é um ativo relevante.

8. **Para o cenário de ERP/TMS, TecnoSpeed PlugBank parece o parceiro mais aderente inicialmente.**  
   Principalmente por cobrir boleto, Pix, CNAB, conciliação e pagamentos em lote.

---

## ⚡ Pontos Divergentes

1. **Pluggy como solução principal vs complemento**

   - Uma visão sugere que a Pluggy evoluiu para uma infraestrutura financeira mais ampla, com Open Finance, payments, Pix Automático, boletos beta, inteligência, webhooks e dashboard.  
     Fonte: `analise1.md` — Fabio.
   - Outra visão alerta que, para o TMS, a Pluggy provavelmente não substitui boleto, CNAB e pagamentos em lote, atuando mais como complemento.  
     Fonte: `analise2.md` e `analiseparceiros.md` — Fabio.

2. **Construir hub próprio vs contratar parceiro**

   - Uma abordagem é construir internamente adapters para cada banco, layouts CNAB, APIs, webhooks e certificados.
   - Outra abordagem é usar um parceiro como TecnoSpeed, Celcoin ou Dock para reduzir complexidade.
   - A alternativa mais equilibrada parece ser híbrida: hub interno padronizado + parceiros/adapters externos.

3. **Hub Bancário vs Plataforma Financeira Unificada**

   - Uma visão mais simples chama o projeto de Hub Bancário.
   - Outra visão sugere posicionar como **Financial Infrastructure Platform**, com módulos de Connect, Banking, Payments, Collections, Intelligence, Dashboard, Developers e Admin.

4. **Uso de BaaS completo**

   - Celcoin e Dock oferecem soluções robustas.
   - Porém, podem ser mais amplas do que a necessidade inicial do TMS, caso o objetivo seja apenas cobrança, pagamento e conciliação.

---

## ⚠️ Riscos Identificados

1. **Subestimar a complexidade das APIs bancárias**  
   Cada banco possui regras, autenticação, payloads, homologações e exceções próprias.

2. **Assumir que Open Finance substitui CNAB e APIs corporativas**  
   Esse é um risco arquitetural importante. Open Finance não cobre integralmente os fluxos operacionais de cobrança e pagamento de um TMS.

3. **Dependência excessiva de um único fornecedor**  
   Usar Pluggy, TecnoSpeed, Celcoin ou outro parceiro como única camada pode criar lock-in.

4. **Custo operacional de homologação banco a banco**  
   Caso o Hub seja construído internamente, será necessário homologar e manter integrações com múltiplos bancos.

5. **Gestão de certificados mTLS**  
   Pix exige OAuth 2.0 + mTLS. O Hub precisará armazenar, renovar e operar certificados com segurança.

6. **Segurança e compliance**  
   O Hub lidará com dados financeiros sensíveis, consentimentos, tokens, extratos, pagamentos e informações bancárias.

7. **Webhooks e idempotência**  
   Eventos de liquidação, Pix e pagamentos precisam ser tratados com idempotência, rastreabilidade e retentativas.

8. **Conciliação incompleta**  
   Se parte do fluxo ficar no parceiro, parte no banco e parte no TMS, pode haver divergências entre saldo, extrato, boletos e títulos internos.

9. **Custo por transação em modelos percentuais**  
   Parceiros como Zoop, com percentual sobre valor, podem gerar custos elevados em operações de frete.

10. **Limites de planos e escalabilidade**  
   Exemplo: Pluggy com plano básico citado a partir de R$ 2.500/mês até 20 contas pode não se adequar ao cenário de 500 clientes sem negociação enterprise.

---

## 💰 Custos e Impactos

### Custos citados

| Fornecedor | Modelo / Custo citado | Observação |
|---|---|---|
| Pluggy | A partir de R$ 2.500/mês até 20 contas | Enterprise sob consulta |
| Asaas | Sem mensalidade; R$ 1,99 por Pix ou boleto; promo R$ 0,99 | Bom para validação simples |
| Zoop | Boleto: ~1,93% + R$ 2,10; Pix: ~2,72% | Pode ficar caro para fretes altos |
| TecnoSpeed PlugBank | Não publicado | Proposta sob consulta por volume |
| Celcoin | Não publicado | Comercial sob consulta |
| Dock | Não publicado | Comercial sob consulta |

Fonte: `analiseparceiros.md` — Fabio.

### Impactos técnicos

- necessidade de criar **modelo financeiro canônico**;
- desenvolvimento de adapters por banco ou por parceiro;
- módulo de autenticação OAuth2;
- módulo de certificados mTLS;
- módulo de webhooks;
- trilha de auditoria;
- reconciliação de eventos;
- armazenamento seguro de credenciais;
- monitoramento e logs;
- painel administrativo.

### Impactos de produto

- possibilidade de oferecer ao cliente uma experiência financeira unificada;
- redução de dependência de CNAB no longo prazo;
- maior automação de cobrança;
- conciliação mais rápida;
- dados financeiros em tempo real;
- potencial criação de novos módulos: Connect, Intelligence, Dashboard e Developers.

### Impactos operacionais

- menor trabalho manual com arquivos;
- maior necessidade de suporte técnico especializado;
- processos de onboarding bancário por cliente;
- gestão de consentimentos Open Finance;
- gestão de permissões por empresa/usuário;
- suporte a múltiplos bancos e múltiplas carteiras.

---

## ❓ Dúvidas em Aberto

1. **A Pluggy consegue substituir 80% das integrações bancárias necessárias ao TMS ou apenas a camada de Open Finance?**

2. **A Pluggy oferece, de forma madura e disponível comercialmente, APIs de:**
   - boleto registrado;
   - Pix Cobrança;
   - pagamentos em lote;
   - CNAB;
   - conciliação;
   - webhooks operacionais?

3. **Qual será o escopo inicial do Hub Financeiro?**
   - cobrança?
   - pagamento?
   - extrato?
   - saldo?
   - conciliação?
   - Open Finance?
   - CNAB?
   - todos?

4. **Quais bancos são prioritários para os clientes atuais do TMS?**

5. **Quantos clientes usarão o módulo no primeiro ano?**

6. **O modelo será por conta bancária, por CNPJ, por cliente ou por transação?**

7. **A empresa quer apenas integrar bancos ou também oferecer serviços financeiros próprios?**

8. **Será necessário manter CNAB por compatibilidade com clientes e bancos legados?**

9. **Qual parceiro tem melhor SLA, suporte e cobertura para o segmento de transporte?**

10. **Como será tratado o consentimento Open Finance por cliente final?**

11. **Quem será responsável pelos certificados mTLS: cliente, Hub, parceiro ou banco?**

12. **Qual será a estratégia de segurança para tokens, client secrets, certificados e dados bancários?**

13. **Como será feita a conciliação entre títulos do TMS, boletos registrados, Pix recebidos e extrato bancário?**

---

## 🏆 Recomendação Inicial

A recomendação inicial é **não posicionar a Pluggy como solução única do Open Financehub Financeiro**.

A melhor estratégia é adotar uma arquitetura em camadas:

```text
TMS/ERP
   ↓
API Financeira Canônica do Hub
   ↓
Orquestrador Financeiro
   ↓
Adapters internos e/ou parceiros
   ├── TecnoSpeed PlugBank / parceiro bancário principal
   ├── Pluggy para Open Finance, Connect, saldo e extrato
   ├── APIs diretas de bancos estratégicos
   └── CNAB legado quando necessário
```

### Estratégia recomendada

1. **Usar o Hub Financeiro como camada proprietária de padronização**
   - O TMS fala sempre com a API interna.
   - O Hub decide se usa parceiro, banco direto, CNAB ou Open Finance.

2. **Avaliar TecnoSpeed PlugBank como parceiro principal**
   - Mais aderente ao cenário de software house/TMS.
   - Cobre boleto, CNAB, Pix, pagamentos e conciliação.

3. **Manter Pluggy como componente complementar e estratégico**
   - Open Finance;
   - conexão bancária;
   - saldo;
   - extrato;
   - transações;
   - consentimento;
   - inteligência financeira;
   - possível iniciação de Pix.

4. **Evitar construir todos os adapters bancários internamente no MVP**
   - Construção própria deve ser reservada para bancos de alto volume ou casos estratégicos.

5. **Começar por um MVP controlado**
   - poucos bancos;
   - poucos clientes;
   - escopo financeiro limitado;
   - métricas claras de sucesso.

### Decisão sugerida

**Construir o Open Financehub como plataforma interna canônica, mas usar parceiros especializados para acelerar a cobertura bancária.**

Pluggy deve ser considerada **parte do ecossistema**, não necessariamente o núcleo operacional de cobrança e pagamento.

---

## 🚀 Próximos Passos

1. **Definir escopo do MVP**
   - cobrança via boleto;
   - Pix Cobrança;
   - conciliação;
   - saldo/extrato;
   - CNAB legado;
   - pagamentos em lote.

2. **Mapear bancos prioritários dos clientes**
   - Banrisul;
   - Santander;
   - Itaú;
   - Banco do Brasil;
   - Bradesco;
   - Sicredi;
   - Sicoob;
   - Inter;
   - Caixa;
   - outros relevantes.

3. **Solicitar propostas comerciais**
   - TecnoSpeed PlugBank;
   - Celcoin;
   - Pluggy enterprise;
   - Dock;
   - eventualmente Asaas para MVP simples de cobrança.

4. **Fazer matriz de aderência funcional**

   Avaliar por fornecedor:

   - boleto registrado;
   - Pix Cobrança;
   - Pix pagamento;
   - Pix Automático;
   - CNAB remessa;
   - CNAB retorno;
   - pagamentos em lote;
   - conciliação;
   - extrato;
   - saldo;
   - Open Finance;
   - webhooks;
   - SLA;
   - suporte;
   - bancos homologados;
   - custo por transação;
   - custo mensal;
   - tempo de implantação.

5. **Validar tecnicamente a Pluggy**
   - confirmar produtos disponíveis;
   - testar limites de contas;
   - validar webhooks;
   - validar iniciação de pagamentos;
   - verificar se há boleto/Pix Cobrança em produção ou beta;
   - entender pricing para 500 clientes.

6. **Desenhar modelo canônico do Hub**
   - `Customer`;
   - `BankAccount`;
   - `BankConnection`;
   - `Payment`;
   - `Collection`;
   - `Invoice/Boleto`;
   - `PixCharge`;
   - `Transaction`;
   - `WebhookEvent`;
   - `Reconciliation`;
   - `Consent`.

7. **Desenhar arquitetura de segurança**
   - OAuth2;
   - mTLS;
   - criptografia de credenciais;
   - segregação por cliente;
   - logs auditáveis;
   - rotação de certificados;
   - permissões por usuário.

8. **Criar prova de conceito**
   - um banco direto, como Banrisul ou Santander;
   - um parceiro, como PlugBank;
   - Pluggy para Open Finance;
   - um fluxo completo de cobrança e conciliação.

9. **Criar dashboard administrativo**
   - status dos bancos;
   - contas conectadas;
   - logs;
   - webhooks;
   - falhas;
   - reprocessamentos;
   - certificados;
   - consentimentos.

10. **Definir estratégia de convivência com CNAB**
   - manter CNAB para clientes/bancos legados;
   - priorizar API para novos fluxos;
   - migrar gradualmente.

---

## 📚 Fontes Utilizadas

1. **`openfinanceknowledgebase.md` — Arthur**  
   Base de conhecimento sobre Open Finance no Brasil, status de mercado, módulo Pluggy implementado, fluxo técnico, endpoints e contexto regulatório.

2. **`analise-inicial-apis-bancos-v01.md` — Fabio**  
   Análise inicial das APIs bancárias, incluindo Banrisul, Santander, autenticação OAuth2, mTLS, BoletoPix, webhooks, adapters por banco e impacto arquitetural.

3. **`analise1.md` — Fabio**  
   Discussão sobre inexistência de “CNAB via API” padronizado pela FEBRABAN, equivalência entre CNAB e APIs REST, proposta de Hub Bancário/Plataforma Financeira, módulos Connect, Intelligence, Dashboard e visão inspirada na Pluggy.

4. **`analise2.md` — Fabio**  
   Análise sobre o papel real da Pluggy no contexto do TMS, distinção entre Open Finance e APIs operacionais bancárias, limitações para boleto/CNAB/pagamentos em lote e reflexão sobre possíveis arquiteturas do Hub Financeiro.

5. **`analiseparceiros.md` — Fabio**  
   Comparativo de parceiros para integração bancária: TecnoSpeed PlugBank, Celcoin, Pluggy, Asaas, Zoop e Dock, com foco no cenário de 500 clientes do TMS.

---
*Atualizado em 30/06/2026 00:31 via OPENAI (gpt-5.5) · Unify*
