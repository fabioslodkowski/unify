# Análise Inicial — APIs Bancárias v01

> Documento vivo. Cada banco analisado ganha uma seção própria.
> Objetivo: entender o que cada banco oferece via API para subsidiar as decisões de arquitetura do Hub Financeiro.

---

## Índice

- [Conclusões transversais](#conclusões-transversais)
- [Banrisul — API de Cobrança (Boleto)](#banrisul--api-de-cobrança-boleto)
- [Banrisul — API Pix v2.9.0](#banrisul--api-pix-v290)
- [Santander — API de Boleto v2](#santander--api-de-boleto-v2)
- [Santander — API Pix v2.0](#santander--api-pix-v20)

---

## Conclusões transversais

Observações que valem para todos os bancos analisados até agora:

| Tema | Observação |
|---|---|
| Autenticação boleto | OAuth 2.0 Client Credentials (padrão entre bancos) |
| Autenticação Pix | OAuth 2.0 **+ mTLS** (exigência do Banco Central) |
| Padrão de endpoints | REST + JSON. Nomenclatura varia por banco |
| Retorno de boleto | CNAB de retorno (arquivo batch) |
| Retorno de Pix | Webhook em tempo real (sem CNAB) |
| Homologação | Cada banco exige processo próprio (sandbox + aprovação manual) |
| Certificados mTLS | O Hub precisará de gerenciamento de certificados por banco (Pix) |
| BoletoPix | Suportado nativamente no Banrisul e Santander — recomendado como padrão |
| Workspace (Santander) | Santander exige criar um Workspace antes de registrar boletos — pré-requisito de onboarding |
| Header X-Application-Key | Santander usa este header adicional em todas as requisições (além do Bearer token) |
| Prefixo "trust-" | URLs Santander (`trust-open.api.*`, `trust-pix.*`) — camada mTLS do lado deles |

### Impacto na arquitetura do Hub

```
Hub Financeiro
│
├── Adapter por banco
│   ├── auth/         → OAuth (boleto) ou OAuth + mTLS (Pix)
│   ├── cobranca/     → boleto, BoletoPix, cobv, cobr
│   ├── pagamentos/   → TED, Pix out, lote
│   ├── extrato/      → consulta, paginação
│   └── webhook/      → receber eventos de liquidação
│
└── Módulo de certificados
    └── gerenciar mTLS por banco (obrigatório para Pix)
```

---

## Banrisul — API de Cobrança (Boleto)

**Referência:** https://developers.banrisul.com.br/pages/docs/clientes-banrisul/api-cobranca-v1.html

### Autenticação

OAuth 2.0 Client Credentials:

```
POST /oauth/token
  client_id + client_secret + grant_type=client_credentials
→ Bearer token (validade: 3600s)
```

Headers obrigatórios em todas as requisições:

```
Authorization: Bearer {token}
bergs-beneficiario: {código 13 dígitos}   ← específico Banrisul
Accept: application/json
Content-Type: application/json
Accept-Encoding: gzip
```

### Ambientes

| Ambiente | URL base |
|---|---|
| Sandbox | `https://apidev.banrisul.com.br/cobranca/v1` |
| Produção | `https://api.banrisul.com.br/cobranca/v1` |

### Endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/boletos` | Registra boleto |
| `GET` | `/boletos` | Lista boletos com filtros |
| `GET` | `/boletos/{id}` | Consulta boleto específico |
| `PATCH` | `/boletos/{id}` | Altera vencimento ou abatimento |
| `POST` | `/boletos/{id}` | Baixa (cancela) boleto |
| `GET` | `/boletos/{id}/emitir` | Gera PDF do boleto |
| `POST` | `/webhook/testar` | Testa URL de webhook |

### Payload de registro (POST /boletos)

```json
{
  "ambiente": "P",
  "titulo": {
    "nosso_numero": "12345678",
    "seu_numero": "NF-001",
    "data_vencimento": "2026-07-30",
    "data_emissao": "2026-06-27",
    "valor_nominal": 1500.00,
    "especie": "DM",
    "pagador": {
      "tipo_pessoa": "J",
      "cpf_cnpj": "12345678000100",
      "nome": "Empresa Pagadora SA",
      "endereco": "Rua das Flores, 100",
      "cep": "90000000",
      "cidade": "Porto Alegre",
      "uf": "RS",
      "aceite": "A",
      "email": "financeiro@empresa.com"
    },
    "instrucoes": {
      "juros":   { "codigo": "1", "taxa": 1.00 },
      "multa":   { "codigo": "1", "taxa": 2.00 },
      "desconto":{ "codigo": "1", "data": "2026-07-25", "valor": 50.00 },
      "protesto":{ "codigo": "1", "prazo": 10 },
      "baixa":   { "codigo": "1", "prazo": 30 }
    },
    "hibrido": { "autoriza": "S" },
    "notas_fiscais": [{ "danfe": "35260612345678000100550010000000011234567890" }]
  }
}
```

### Resposta de registro (200/201)

```json
{
  "retorno": "02",
  "titulo": {
    "nosso_numero": "12345678",
    "codigo_barras": "03399.12345 67890.123456 78901.234567 8 12340000150000",
    "linha_digitavel": "03399123456789012345678901234567812340000150000"
  }
}
```

**Códigos de retorno:**

| Código | Significado |
|---|---|
| `01` | Registrado apenas na base Banrisul |
| `02` | Registrado na base centralizada (CIP) |
| `04` | Homologado (ambiente de testes) |

### Listagem com filtros (GET /boletos)

Parâmetros de query disponíveis:

```
situacao_titulo=A|B                 (obrigatório: A=ativo, B=baixado)
situacao_pagamento=1|2|3|4|5|6|8
data_vencimento_inicial=YYYY-MM-DD
data_vencimento_final=YYYY-MM-DD
data_registro_inicial=YYYY-MM-DD
data_registro_final=YYYY-MM-DD
nosso_numero_inicial=string
nosso_numero_final=string
pagador.cpf_cnpj=string
ordenacao=N|P|S|V
paginacao.pagina_atual=1-4
paginacao.itens_por_pagina=1-500
```

### Alteração (PATCH /boletos/{id})

Tipos de alteração disponíveis:

| tipo_alteracao | O que altera |
|---|---|
| `04` | Abatimento |
| `06` | Data de vencimento |

### Funcionalidades avançadas suportadas

| Funcionalidade | Suporte |
|---|---|
| BoletoPix (híbrido) | ✅ `hibrido: S` |
| Vinculo com NF-e / CTe | ✅ `notas_fiscais[].danfe` |
| Pagamento parcial | ✅ `pag_parcial` |
| Rateio entre beneficiários | ✅ `rateio` |
| Protesto automático | ✅ `instrucoes.protesto` |
| Baixa automática | ✅ `instrucoes.baixa` |
| Mensagens no boleto | ✅ até 9 linhas de 75 chars |
| Desconto | ✅ por valor ou percentual |
| Webhook de teste | ✅ `/webhook/testar` |
| PDF do boleto | ✅ `/boletos/{id}/emitir` |

### Validação do Nosso Número

- 8 dígitos livres + dígito verificador módulo 10 + dígito verificador módulo 11
- O Hub precisa calcular e garantir unicidade por beneficiário

### Processo de homologação

```
1. Email para: gestao_sistemas_cobranca_operacional@banrisul.com.br
2. Criar aplicação no portal dev → client_id + client_secret
3. Testar no sandbox com beneficiários fictícios:
   - 0010000001088 → OK
   - 0001000000252 → Sem permissão
   - 0001000000333 → Inapto
4. Enviar 5 requisições + PDFs para validação pelo banco
5. Ativar convênio de cobrança em produção
```

### Códigos de erro relevantes

| Código | Descrição |
|---|---|
| `OC4B0001` | Sem permissão para emissão |
| `OC4B0008` | Beneficiário inapto |
| `OC4B0009` | Nosso número duplicado |
| `OC4B0013` | CPF/CNPJ igual em beneficiário e pagador |
| `OC4B0050` | Nenhuma instrução informada |
| `16` (FEBRABAN) | Data de vencimento inválida |
| `46` (FEBRABAN) | Inscrição do pagador inválida |

### Contatos

- API: api@banrisul.com.br
- Cobrança: gestao_sistemas_cobranca_operacional@banrisul.com.br
- SAC: 0800 646 1515

---

## Banrisul — API Pix v2.9.0

**Referência:** https://developers.banrisul.com.br/pages/docs/clientes-banrisul/api-pix-v2.9.0.html

### Autenticação

OAuth 2.0 + **mTLS obrigatório** (exigência do Banco Central para todos os participantes Pix):

```
POST /auth/oauth/v2/token
  Authorization: Basic base64(client_id:client_secret)
  grant_type=client_credentials
  scope={escopos necessários}
→ Bearer token
```

A conexão TLS exige certificado mútuo (client certificate) instalado no HTTP client.

### Ambientes

| Ambiente | URL base |
|---|---|
| Sandbox | `https://mtls-api-h.banrisul.com.br/pix/api-mtls` |
| Produção | `https://mtls-api.banrisul.com.br/pix/api-mtls` |

### Scopes OAuth (solicitar apenas o necessário)

| Scope | Permissão |
|---|---|
| `cob.read` / `cob.write` | Pix imediato |
| `cobv.read` / `cobv.write` | Pix com vencimento |
| `cobr.read` / `cobr.write` | Cobrança recorrente |
| `lotecobv.read` / `lotecobv.write` | Lote de cobranças |
| `rec.read` / `rec.write` | Recorrências (Pix Automático) |
| `pix.read` / `pix.write` | Pix recebidos |
| `webhook.read` / `webhook.write` | Webhooks |

### Tipos de cobrança Pix

| Tipo | Endpoint base | Descrição | Relevância TMS |
|---|---|---|---|
| `cob` | `/cob` | Pix imediato, sem vencimento | Pagamentos avulsos |
| `cobv` | `/cobv` | **Pix com vencimento** + multa + juros | ⭐ Substituto do boleto |
| `cobr` | `/cobr` | Cobrança recorrente | Contratos mensais |
| `lotecobv` | `/lotecobv` | Lote de cobranças com vencimento | Faturamento em massa |

### Endpoints por tipo

#### Pix Imediato (cob)

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/cob/{txid}` | Cria cobrança imediata |
| `GET` | `/cob/{txid}` | Consulta |
| `PATCH` | `/cob/{txid}` | Atualiza |
| `GET` | `/cob` | Lista com filtros |

#### Pix com Vencimento (cobv) — principal para o TMS

| Método | Endpoint | Descrição |
|---|---|---|
| `PUT` | `/cobv/{txid}` | Cria cobrança com vencimento |
| `GET` | `/cobv/{txid}` | Consulta |
| `PATCH` | `/cobv/{txid}` | Atualiza |
| `GET` | `/cobv` | Lista com filtros |

#### Lote de Vencimento (lotecobv)

| Método | Endpoint | Descrição |
|---|---|---|
| `PUT` | `/lotecobv/{id}` | Cria/atualiza lote |
| `GET` | `/lotecobv/{id}` | Consulta lote |
| `PATCH` | `/lotecobv/{id}` | Atualiza cobranças do lote |
| `GET` | `/lotecobv` | Lista lotes |

#### Recorrências (Pix Automático)

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/rec` | Cria recorrência |
| `GET` | `/rec` | Lista recorrências |
| `GET` | `/rec/{idRec}` | Consulta recorrência |
| `PATCH` | `/rec/{idRec}` | Atualiza |
| `POST` | `/solicrec` | Solicita confirmação do pagador |
| `PATCH` | `/solicrec/{id}` | Cancela solicitação |

#### Pix Recebidos

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/pix` | Lista Pix recebidos |
| `GET` | `/pix/{e2eid}` | Consulta Pix específico |
| `PUT` | `/pix/{e2eid}/devolucao/{id}` | Solicita devolução |

#### Webhooks

| Método | Endpoint | Tipo |
|---|---|---|
| `PUT` | `/webhook/{chave}` | Configura webhook de Pix geral |
| `GET` | `/webhook/{chave}` | Consulta |
| `DELETE` | `/webhook/{chave}` | Remove |
| `PUT` | `/webhookrec` | Webhook de recorrência |
| `PUT` | `/webhookcobr` | Webhook de cobrança recorrente |

### Payload Pix com Vencimento (PUT /cobv/{txid})

```json
{
  "calendario": {
    "dataDeVencimento": "2026-07-30",
    "validadeAposVencimento": 30
  },
  "valor": {
    "original": "1500.00",
    "multa": "15.00",
    "juros": "2.00"
  },
  "devedor": {
    "cnpj": "12345678000100",
    "nome": "Empresa Pagadora SA",
    "logradouro": "Rua das Flores, 100",
    "cidade": "Porto Alegre",
    "uf": "RS",
    "cep": "90000000"
  },
  "chave": "chave-pix-da-transportadora"
}
```

### Lote de cobranças (PUT /lotecobv/{id})

```json
{
  "descricao": "Faturamento junho 2026",
  "cobsV": [
    {
      "txid": "abc123...",
      "calendario": { "dataDeVencimento": "2026-07-30" },
      "valor": { "original": "1500.00" },
      "devedor": { "cnpj": "...", "nome": "..." },
      "chave": "..."
    }
  ]
}
```

### Ciclo de vida das cobranças

```
cobv (com vencimento):
  ATIVA → CONCLUIDA               (pago)
        → VENCIDA                 (expirou após validadeAposVencimento)
        → REMOVIDA_PELO_USUARIO_RECEBEDOR

cobr (recorrente):
  CRIADA → ATIVA → LIQUIDADA
                 → CANCELADA
                 → REJEITADA

rec (recorrência/Pix Automático):
  CRIADA → APROVADA → (cobranças geradas)
         → CANCELADA
         → REJEITADA
         → EXPIRADA
```

### Periodicidades de recorrência disponíveis

`DIARIA` | `SEMANAL` | `QUINZENAL` | `MENSAL` | `BIMESTRAL` | `TRIMESTRAL` | `SEMESTRAL` | `ANUAL`

### Devolução (estorno de Pix recebido)

```json
PUT /pix/{e2eid}/devolucao/{id}
{
  "valor": "100.50"
}
```

Regras:
- Prazo máximo: 90 dias após a liquidação original
- Valor não pode superar o Pix original
- IDs de devolução não podem ser duplicados

### Validações importantes

| Campo | Regra |
|---|---|
| `txid` | 26–35 caracteres alfanuméricos, único por chave |
| `idRec` | Exatamente 29 caracteres, formato `R[A|N]xxxxxxxxyyyyMMddkkkkkkkkkkk` |
| `dataDeVencimento` | Deve ser ≥ data atual |
| `validadeAposVencimento` | ≥ 0 dias |
| `valor.original` | Deve ser > 0 |
| Desconto | Não pode ser ≥ valor original |
| Data desconto | Não pode ser > data de vencimento |

### Códigos de erro

| Erro | HTTP | Significado |
|---|---|---|
| `RequisicaoInvalida` | 400 | Formato inválido |
| `AcessoNegado` | 403 | Sem autorização |
| `CobVNaoEncontrada` | 404 | Cobrança com vencimento não encontrada |
| `CobVOperacaoInvalida` | 400 | Operação inválida para o status atual |
| `ErroInternoDoServidor` | 500 | Erro no servidor |
| `ServicoIndisponivel` | 503 | Manutenção |

---

## Comparativo Boleto vs. Pix — Banrisul

| | API Boleto | API Pix (cobv) |
|---|---|---|
| Autenticação | OAuth 2.0 | OAuth 2.0 + **mTLS** |
| Cobrança com prazo e juros | ✅ | ✅ |
| Cobrança em lote | ❌ (um por vez) | ✅ `lotecobv` |
| Cobrança recorrente | ❌ | ✅ `cobr` / `rec` |
| Retorno de liquidação | CNAB de retorno | **Webhook em tempo real** |
| PDF do documento | ✅ `/emitir` | ❌ (QR Code) |
| Protesto automático | ✅ | ❌ |
| Antecipação de recebíveis | ✅ | ❌ |
| Vinculo com NF-e / CTe | ✅ `notas_fiscais` | ❌ |
| Rateio entre beneficiários | ✅ | ❌ |
| Devolução/estorno | ❌ | ✅ (até 90 dias) |
| BoletoPix (híbrido) | ✅ `hibrido: S` | — |

### Recomendação de uso no Hub

| Situação | Canal recomendado |
|---|---|
| Pagador é empresa com AP estruturado | BoletoPix (`hibrido: S`) |
| Pagador aceita Pix com vencimento | `cobv` |
| Cobrança com necessidade de protesto | Boleto puro |
| Cobrança recorrente / mensalidade | `cobr` ou `rec` (Pix Automático) |
| Faturamento em massa | `lotecobv` |
| Antecipação de recebíveis | Boleto puro |

**Padrão sugerido para novos clientes do TMS:** emitir BoletoPix — o pagador recebe o boleto com QR Code e escolhe pagar pela linha digitável ou pelo Pix. Uma única emissão, dois canais de pagamento.

---

---

## Santander — API de Boleto v2

**Referência:** https://developer.santander.com.br/api/user-guide/collection-bill-management  
**Documentação atualizada em:** 12 de fevereiro de 2026

### Autenticação

```
Authorization: Bearer {token_oauth2}
X-Application-Key: {application_key}    ← obrigatório e único no Santander
Content-Type: application/json
```

O `X-Application-Key` é obtido no portal do desenvolvedor ao criar uma aplicação. É fixo por aplicação (não rotaciona como o token OAuth).

### Ambientes

| Ambiente | URL base |
|---|---|
| Sandbox | `https://trust-sandbox.api.santander.com.br/collection_bill_management/v2` |
| Produção | `https://trust-open.api.santander.com.br/collection_bill_management/v2` |

> O prefixo `trust-` nas URLs indica a camada de mTLS do lado Santander. O client precisa apresentar certificado nesta conexão.

### Conceito de Workspace (exclusivo Santander)

Antes de registrar boletos, é obrigatório criar um **Workspace** — equivalente a um "convênio" ou "carteira de cobrança" no Santander:

```
POST /workspaces
{
  "name": "Cobrança Transportadora",
  "covenantCode": "123456"    ← código do convênio bancário
}
→ { "workspace_id": "abc-def-..." }
```

Todos os boletos ficam vinculados ao `workspace_id`.

### Endpoints

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/workspaces` | Cria Workspace |
| `GET` | `/workspaces` | Lista Workspaces |
| `GET` | `/workspaces/{workspace_id}` | Consulta Workspace |
| `POST` | `/workspaces/{workspace_id}/bank_slips` | Registra boleto |
| `GET` | `/workspaces/{workspace_id}/bank_slips` | Lista boletos |
| `GET` | `/workspaces/{workspace_id}/bank_slips/{nsuCode}` | Consulta boleto específico |
| `DELETE` | `/workspaces/{workspace_id}/bank_slips/{nsuCode}` | Baixa (cancela) boleto |

### Payload de registro (POST /workspaces/{workspace_id}/bank_slips)

```json
{
  "nsuCode": "NF001JUN26",
  "covenantCode": "123456",
  "bankSlipNumber": "00001",
  "issueDate": "2026-06-27",
  "dueDate": "2026-07-30",
  "nominalValue": 1500.00,
  "payer": {
    "documentNumber": "12345678000100",
    "documentType": "CNPJ",
    "name": "Empresa Pagadora SA",
    "address": {
      "street": "Rua das Flores",
      "number": "100",
      "zipCode": "90000000",
      "city": "Porto Alegre",
      "state": "RS"
    }
  },
  "fine": {
    "type": "VALOR_FIXO",
    "amount": 30.00
  },
  "interest": {
    "type": "TAXA_MENSAL",
    "amount": 1.00
  },
  "discounts": [
    {
      "type": "VALOR_FIXO",
      "amount": 50.00,
      "limitDate": "2026-07-25"
    }
  ],
  "protest": {
    "type": "DIAS_UTEIS",
    "days": 10
  },
  "messages": ["Referente ao CTe 12345", "Vencimento: 30/07/2026"],
  "txId": "abc123txid456",
  "key": {
    "dictKey": "chave-pix@transportadora.com.br"
  },
  "partialPayment": false,
  "iof": 0.00
}
```

**Campos-chave específicos do Santander:**

| Campo | Tipo | Descrição |
|---|---|---|
| `nsuCode` | string (1-20) | Identificador único por dia na conta — deve ser único |
| `covenantCode` | string | Código do convênio bancário |
| `txId` + `key.dictKey` | string | Ativa BoletoPix — inclui QR Code Pix na resposta |
| `iof` | decimal | IOF para cobranças com tributação |

### Resposta de registro (201)

```json
{
  "nsuCode": "NF001JUN26",
  "bankSlipNumber": "00001",
  "barcode": "03399.12345 67890.123456 78901.234567 8 12340000150000",
  "digitableLine": "03399123456789012345678901234567812340000150000",
  "qrCodePix": "00020126...",
  "qrCodeUrl": "https://pix.santander.com.br/qr/...",
  "status": "REGISTERED"
}
```

Quando `txId` e `key.dictKey` são informados, a resposta inclui `qrCodePix` e `qrCodeUrl` — BoletoPix ativado.

### Tipos de protesto disponíveis

| Valor | Significado |
|---|---|
| `SEM_PROTESTO` | Sem protesto |
| `DIAS_CORRIDOS` | Protestar em N dias corridos após vencimento |
| `DIAS_UTEIS` | Protestar em N dias úteis após vencimento |
| `CADASTRO_CONVENIO` | Usar regra padrão do convênio |

### Funcionalidades avançadas suportadas

| Funcionalidade | Suporte |
|---|---|
| BoletoPix (híbrido) | ✅ `txId` + `key.dictKey` no mesmo request |
| Até 3 datas de desconto | ✅ array `discounts[]` |
| Multa e juros | ✅ `fine` + `interest` |
| Abatimento | ✅ `abatement` |
| Protesto | ✅ 4 tipos disponíveis |
| Pagamento parcial | ✅ `partialPayment: true` |
| IOF | ✅ `iof` |
| Rateio / split | ✅ `sharing` |
| Mensagens no boleto | ✅ array `messages[]` |

### Diferenças-chave vs. Banrisul

| Aspecto | Banrisul | Santander |
|---|---|---|
| Pré-requisito | Nenhum (usa `bergs-beneficiario`) | Workspace obrigatório |
| Header adicional | `bergs-beneficiario` | `X-Application-Key` |
| Identificador único | `nosso_numero` (8 dígitos + DV) | `nsuCode` (alfanumérico, por dia) |
| BoletoPix | `hibrido.autoriza: "S"` | `txId` + `key.dictKey` |
| Protesto | código numérico + prazo | tipo textual + prazo |
| Vincular NF-e/CTe | `notas_fiscais[].danfe` | não visto em doc — verificar |
| PDF do boleto | `GET /boletos/{id}/emitir` | não documentado — verificar |

### Processo de onboarding

```
1. Criar conta no developer.santander.com.br
2. Criar Application → obter X-Application-Key
3. Obter OAuth client_id + client_secret
4. Criar Workspace vinculado ao covenantCode (convênio)
5. Testar no sandbox (trust-sandbox.api.santander.com.br)
6. Solicitar ativação em produção via portal
```

---

## Santander — API Pix v2.0

**Referência:** https://developer.santander.com.br/api/user-guide/pix-qr-code-generation  
**Documentação atualizada em:** 12 de fevereiro de 2026

### Autenticação

OAuth 2.0 (Bearer token). O prefixo `trust-pix` nas URLs sugere mTLS do lado Santander — verificar exigência de certificado client durante onboarding (BCB obriga mTLS para todos PSPs Pix).

### Ambientes

| Ambiente | URL base |
|---|---|
| Sandbox | `https://pix.santander.com.br/api/v1/sandbox/` |
| Produção | `https://trust-pix.santander.com.br/` |

### Endpoints

#### Cob — Cobrança Imediata

| Método | Endpoint | Descrição |
|---|---|---|
| `PUT` | `/cob/{txid}` | Criar cobrança imediata |
| `PATCH` | `/cob/{txid}` | Revisar cobrança imediata |
| `GET` | `/cob/{txid}` | Consultar cobrança imediata |

#### CobV — Cobrança com Vencimento

| Método | Endpoint | Descrição |
|---|---|---|
| `PUT` | `/cobv/{txid}` | Criar cobrança com vencimento |
| `PATCH` | `/cobv/{txid}` | Revisar cobrança com vencimento |
| `GET` | `/cobv/{txid}` | Consultar cobrança com vencimento |

#### Pix Recebidos

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/pixrecebidos` | Consultar Pix recebidos com filtros |
| `GET` | `/pixrecebidos` (lista) | Consultar lista paginada de Pix recebidos |
| `PUT` | `/pixrecebidos/{e2eid}/devolucao/{id}` | Solicitar devolução |
| `GET` | `/pixrecebidos/{e2eid}/devolucao/{id}` | Consultar devolução |

> **Nota:** Santander usa `/pixrecebidos` em vez de `/pix` (padrão BCB). Ajuste no adapter necessário.

#### Webhook

| Método | Endpoint | Descrição |
|---|---|---|
| `PUT` | `/webhook` | Configurar Webhook Pix |
| `GET` | `/webhook` | Exibir informações do Webhook |
| `DELETE` | `/webhook` | Cancelar Webhook Pix |
| `GET` | `/webhooks` | Consultar Webhooks cadastrados |

### Filtros em GET /pixrecebidos

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `inicio` | datetime | Data/hora início (obrigatório) |
| `fim` | datetime | Data/hora fim (obrigatório, = inicio para grandes volumes) |
| `cnpj` | string | Filtrar por CNPJ do pagador |
| `cpf` | string | Filtrar por CPF do pagador |
| `paginacao.itensPorPagina` | integer (1-1000) | Default: 100 |
| `paginacao.paginaAtual` | integer (≥0) | Default: 0 |

> Para otimizar buscas de grande volume: `inicio` e `fim` devem ser iguais (mesmo dia).

### Callbacks (Pix recebido — webhook notificação)

O Santander envia callback quando um Pix é liquidado. O payload segue o padrão BCB com `endToEndId`, `valor`, `pagador`, `infoPagador`, `horario`.

### Diferenças vs. Banrisul Pix

| Aspecto | Banrisul | Santander |
|---|---|---|
| Versão | v2.9.0 | v2.0 |
| Endpoint pix recebidos | `/pix` | `/pixrecebidos` ← diferente! |
| Cobrança recorrente (cobr) | ✅ | ❌ não visto |
| Lote (lotecobv) | ✅ | ❌ não visto |
| Pix Automático (rec) | ✅ | API separada (Pix Automático) |
| Scopes granulares | ✅ (cob.read, cobv.write...) | não documentado — verificar |

### Outras APIs disponíveis no Santander (mapeadas, não analisadas em profundidade)

| API | Descrição | Relevância TMS |
|---|---|---|
| Pix Automático | Débito automático recorrente via Pix | Alta — contratos mensais |
| Transferências Pix | Pix out entre contas | Alta — pagamentos a fornecedores |
| TED | Transferência entre bancos | Média — legado |
| DDA | Consulta boletos em nome do pagador | Baixa para emissão, alta para conciliação |
| Transferências Inteligentes | Mover saldo entre contas próprias | Baixa |
| Saldo e Extrato | Consulta de saldo e extrato | Alta — conciliação |
| Contas e Tributos | Pagamento de contas, impostos, DARF | Média — pagamentos operacionais |

---

## Comparativo Santander vs. Banrisul — impacto no Hub

| Dimensão | Banrisul | Santander |
|---|---|---|
| Pré-requisito de conta | `bergs-beneficiario` header | Workspace (criação via API) |
| Autenticação boleto | OAuth 2.0 | OAuth 2.0 + X-Application-Key |
| Autenticação Pix | OAuth 2.0 + mTLS explícito | OAuth 2.0 (mTLS via trust- URLs) |
| BoletoPix | Campo `hibrido` | Campos `txId` + `key` |
| Retorno liquidação Pix | Webhook (padrão BCB `/pix`) | Webhook (endpoint `/pixrecebidos`) |
| Cobrança em lote Pix | ✅ `lotecobv` | ❌ não identificado |
| Pix Automático | ✅ integrado na API Pix | ✅ API separada |
| Complexidade de onboarding | Média | Alta (Workspace + X-Application-Key) |

### Impacto no adapter Santander

O adapter Santander no Hub precisará:
1. Gerenciar `workspace_id` por cliente do TMS (criado no onboarding)
2. Passar `X-Application-Key` fixo por cliente
3. Mapear `/pixrecebidos` em vez de `/pix` na coleta de liquidações
4. Verificar se mTLS é exigido também no boleto (URLs `trust-open.api.*`)
5. Criar workspace automaticamente no fluxo de ativação do cliente

---

*Documento atualizado em: 2026-06-27 | Próximos bancos a analisar: Itaú, Banco do Brasil, Sicredi, Inter, Bradesco*
