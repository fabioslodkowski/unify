# Onboarding de Cliente — Hub Financeiro

> Documento de processo. Define o que precisa acontecer, quem faz, e em que ordem para ativar um novo cliente do TMS nas integrações bancárias do Hub.

---

## Índice

- [Visão geral do fluxo](#visão-geral-do-fluxo)
- [Camada 1 — Pré-requisitos bancários (responsabilidade do cliente)](#camada-1--pré-requisitos-bancários)
- [Camada 2 — Informações que o cliente fornece ao Hub](#camada-2--informações-que-o-cliente-fornece-ao-hub)
- [Camada 3 — Ações que o Hub executa](#camada-3--ações-que-o-hub-executa)
- [Pontos críticos e decisões em aberto](#pontos-críticos-e-decisões-em-aberto)

---

## Visão geral do fluxo

```
Cliente entra no TMS
    ↓
Equipe Brudam abre checklist de onboarding bancário
    ↓
Cliente vai ao banco habilitar acesso via API (se ainda não tem)
    ↓  ← tempo de espera do banco (dias a semanas, fora do controle do Hub)
Cliente traz client_id, client_secret e demais dados
    ↓
Hub valida credenciais, cria workspace (Santander), registra webhook
    ↓
Equipe faz boleto de teste + valida retorno de liquidação
    ↓
Cliente ativo em produção
```

---

## Camada 1 — Pré-requisitos bancários

> O que o cliente precisa ter resolvido com o banco **antes** de qualquer integração.
> Nenhuma automação do Hub resolve isso — depende do gerente/banco.

| Pré-requisito | Banrisul | Santander | Observação |
|---|---|---|---|
| Conta PJ ativa | obrigatório | obrigatório | |
| Convênio de cobrança (carteira de boleto) ativo | obrigatório | obrigatório | Santander chama de `covenantCode` |
| Chave Pix registrada na conta | obrigatório | obrigatório | Pode ser CNPJ, e-mail, telefone ou aleatória |
| Certificado digital A1 válido | obrigatório (Pix) | obrigatório (Pix) | O mesmo certificado usado para emissão de NF-e/CT-e |
| Acesso ao portal developer do banco | cliente habilita | cliente habilita (Usuário Master PJ) | |

> **Sobre o certificado A1:** a empresa de transporte já possui este certificado — é o mesmo usado para assinar NF-e e CT-e. Não é necessário gerar um novo certificado específico para a API. O Hub **não gera** o certificado; o cliente usa o que já tem.

**Ação da equipe Brudam:** enviar ao cliente o checklist acima antes de iniciar qualquer configuração técnica. Não adianta pedir credenciais sem o convênio ativo.

---

## Camada 2 — Informações que o cliente fornece ao Hub

> Dados que só o cliente possui e precisa entregar à equipe Brudam para configuração.

### Dados bancários gerais

| Informação | Obrigatório | Descrição |
|---|---|---|
| CNPJ da empresa | ✅ | Usado como identificador principal |
| Razão social | ✅ | Para boletos e cobranças |
| Endereço completo | ✅ | CEP, cidade, UF |
| Banco(s) a integrar | ✅ | Quais bancos o cliente opera |
| Chave Pix cadastrada | ✅ | Por banco |

### Banrisul — dados específicos

| Informação | Campo | Quem obtém | Observação |
|---|---|---|---|
| Client ID OAuth | `client_id` | Cliente, no portal developers.banrisul.com.br | |
| Client Secret OAuth | `client_secret` | Cliente, no portal | |
| Código do beneficiário | `bergs-beneficiario` | Cliente, com o gerente do banco | 13 dígitos |
| Certificado A1 (.p12/.pfx) | mTLS Pix | Cliente já possui | Mesmo da NF-e/CT-e — cliente faz upload no portal Banrisul |

> **Processo Banrisul Pix:** o cliente acessa o portal de desenvolvedor do Banrisul, faz o cadastro e upload do seu certificado A1. O banco fornece o client_id e client_secret. O processo de homologação exige envio de e-mail para `gestao_sistemas_cobranca_operacional@banrisul.com.br`. Detalhes do registro de certificado devem ser confirmados durante o onboarding real (não documentado publicamente).

### Santander — dados específicos

| Informação | Campo | Quem obtém | Observação |
|---|---|---|---|
| Client ID OAuth | `client_id` | Cliente, no developer.santander.com.br | Gerado após upload do certificado |
| Client Secret OAuth | `client_secret` | Cliente, no portal | Gerado junto com o ClientID |
| Application Key | `X-Application-Key` | Cliente, no portal | Fixo por aplicação, não rotaciona |
| Código do convênio | `covenantCode` | Cliente, com o gerente do banco | |
| Certificado A1 (.p12/.pfx) | mTLS | Cliente já possui | Mesmo da NF-e/CT-e — cliente faz upload no portal Santander |

> **Processo Santander:** o Usuário Master PJ acessa o developer.santander.com.br, vai em "Minhas Aplicações", cria uma aplicação em modo **Produção** e faz upload do arquivo `.PFX` do certificado A1 (o mesmo da NF-e). O portal valida o certificado e gera ClientID + ClientSecret + X-Application-Key. O cliente então entrega esses três dados ao Hub.

---

## Camada 3 — Ações que o Hub executa

> O que o Hub faz automaticamente (ou a equipe técnica faz) após receber as credenciais do cliente.

| Ação | Responsável | Quando | Banco |
|---|---|---|---|
| Testar autenticação OAuth (client credentials) | Hub automático | Ao salvar credenciais | Todos |
| Criar Workspace vinculado ao covenantCode | Hub automático | Após OAuth validado | Santander |
| Registrar URL de webhook no banco | Hub automático | Na ativação | Todos (Pix) |
| Instalar certificado mTLS no HTTP client do Hub | Equipe técnica | Antes de ativar Pix | Todos (Pix) |
| Registrar boleto de teste no sandbox | Equipe técnica | Homologação | Todos |
| Validar retorno/webhook de liquidação | Equipe técnica | Homologação | Todos |
| Ativar cliente em produção | Equipe técnica | Após homologação aprovada | Todos |

---

## Pontos críticos e decisões em aberto

### 1 — Certificado mTLS: processo confirmado para Santander

**O certificado não é gerado pelo Hub.** O cliente usa o **certificado A1 que já possui** (emitido por AC autorizada — Certisign, Valid, Serasa etc.) — o mesmo usado para assinar NF-e e CT-e.

**Fluxo Santander (confirmado):**
```
1. Cliente acessa developer.santander.com.br com login PJ (Usuário Master)
2. Vai em "Minhas Aplicações" → cria aplicação em modo Produção
3. Faz upload do arquivo .PFX/.P12 do certificado A1
4. Portal valida o certificado e gera ClientID + ClientSecret + X-Application-Key
5. Cliente entrega essas credenciais ao Hub
6. Hub armazena e usa para autenticar nas chamadas
```

**Fluxo Banrisul (a confirmar durante onboarding real):** provável que seja similar — cliente faz upload do certificado A1 no portal e recebe as credenciais. Não está documentado publicamente.

**O que o Hub precisa guardar por cliente:**
- O arquivo do certificado A1 (.p12/.pfx) + senha do arquivo → usado pelo HTTP client do Hub para estabelecer mTLS nas chamadas Pix
- Ou apenas o ClientID + ClientSecret, se o banco usa o certificado só para registro e depois autentica via OAuth (verificar por banco)

> **Ponto de atenção sobre renovação:** o certificado A1 tem validade (1–3 anos). Quando o cliente renova o certificado da NF-e, precisa também atualizar no portal do banco e fornecer o novo arquivo ao Hub. O Hub deve monitorar a expiração e alertar com antecedência.

---

### 2 — Armazenamento seguro de credenciais

O Hub vai armazenar `client_secret`, `X-Application-Key` e certificados mTLS de centenas de clientes. Isso exige:

- Criptografia em repouso (nunca em texto plano no banco)
- Acesso auditado (log de quem consultou qual credencial)
- Rotação de secrets (quando o cliente regenera no banco)
- Isolamento por tenant (cliente A não pode ver credencial do cliente B)

> **Decisão necessária:** usar cofre dedicado (HashiCorp Vault, AWS Secrets Manager) ou criptografia na camada de aplicação?

---

### 3 — Tempo de espera do banco

Cada banco tem seu processo de homologação antes de liberar produção:

| Banco | Processo | Tempo estimado |
|---|---|---|
| Banrisul | Email para equipe de cobrança + validação de 5 requisições + PDFs | 5–15 dias úteis |
| Santander | Portal developer + ativação via portal | 3–10 dias úteis |

O cliente e o time comercial precisam ser informados desse prazo **no momento da venda** — não é culpa do Hub, é processo bancário.

> **Ação sugerida:** criar SLA de onboarding que deixa claro o tempo de espera bancário separado do tempo de configuração do Hub.

---

### 4 — Self-service vs. assistido

Duas formas de fazer o onboarding:

| Modelo | Como | Quando usar |
|---|---|---|
| **Assistido** | Equipe Brudam coleta dados e configura | MVP, clientes enterprise, bancos novos |
| **Self-service** | Cliente preenche um formulário no TMS, Hub configura automaticamente | Escala — quando os fluxos estiverem estáveis e documentados |

> **Recomendação:** começar assistido, mapear os pontos de atrito e automatizar depois.

---

## Checklist de onboarding (por banco)

### Banrisul

**Responsabilidade do cliente (faz no banco):**
- [ ] Conta PJ ativa com convênio de cobrança habilitado
- [ ] Chave Pix registrada na conta
- [ ] Certificado A1 válido (NF-e) — cliente já deve ter
- [ ] Acessa portal developers.banrisul.com.br, faz cadastro e upload do certificado A1
- [ ] Obtém e entrega: `client_id`, `client_secret`, código do beneficiário (13 dígitos)

**Responsabilidade do Hub/Brudam:**
- [ ] Valida credenciais OAuth
- [ ] Configura certificado A1 no HTTP client do Hub para chamadas mTLS
- [ ] Registra webhook de liquidação Pix
- [ ] Executa boleto de teste no sandbox
- [ ] Valida retorno de liquidação via webhook
- [ ] Solicita aprovação do banco para produção
- [ ] Ativa cliente em produção

### Santander

**Responsabilidade do cliente (faz no banco):**
- [ ] Conta PJ ativa com convênio de cobrança habilitado
- [ ] `covenantCode` obtido com o gerente
- [ ] Chave Pix registrada na conta
- [ ] Certificado A1 válido (NF-e) — cliente já deve ter
- [ ] Acessa developer.santander.com.br como Usuário Master PJ
- [ ] Em "Minhas Aplicações" → cria aplicação em modo **Produção** → faz upload do .PFX
- [ ] Obtém e entrega: `client_id`, `client_secret`, `X-Application-Key`

**Responsabilidade do Hub/Brudam:**
- [ ] Valida credenciais OAuth
- [ ] Cria Workspace via API (automático, usando `covenantCode`)
- [ ] Configura certificado A1 no HTTP client do Hub para chamadas mTLS
- [ ] Registra webhook de liquidação Pix
- [ ] Executa boleto de teste no sandbox
- [ ] Valida retorno de liquidação via webhook
- [ ] Ativa cliente em produção

---

*Documento criado em: 2026-06-27 | Complementa: analise inicial apis bancos v01.md*
