# Comparativo de Certificados e Credenciais — Por Banco

> Documento baseado em evidências reais: Itaú (BRIX-759, arquivos cert_110326), Santander (portal developer + Kobana/CIGAM), Banrisul (documentação pública parcial).
> Banrisul marcado como "a confirmar" onde não há evidência direta.

---

## Quadro geral — visão rápida

| Dimensão | Itaú | Santander | Banrisul |
|---|---|---|---|
| **Tipo de certificado** | Dinâmico (gerado pela Brudam) | A1 existente do cliente (NF-e/CT-e) | A confirmar |
| **Quem gera o cert** | Brudam (key + CSR via Postman) | Cliente já possui | A confirmar |
| **Quem registra no banco** | Brudam envia CSR → Itaú assina | Cliente faz upload no portal | A confirmar |
| **Validade do cert** | **1 ano** | 1–3 anos (validade do A1) | A confirmar |
| **Na renovação: credentials mudam?** | ✅ **Sim — client_id e secret novos** | A verificar | A confirmar |
| **Janela crítica** | **7 dias** (token do Itaú) | Não tem — cliente faz quando quiser | A confirmar |
| **Quem detecta expiração** | Erro em produção (reativo) | Erro em produção (reativo) | Reativo |
| **Sandbox disponível** | ❌ Não funciona — testa em produção | ✅ Sim | ✅ Sim |
| **Tela de configuração no TMS** | Tela 629 | Conta bancária (422) | Conta bancária (422) |

---

## Itaú — processo detalhado (confirmado por BRIX-759)

### Onboarding inicial

```
1. Cliente solicita ao Itaú liberação da API Pix (via gerente ou Central 0800-770-1685)
     ↓
2. Itaú envia email com:
   • client_id (UUID)
   • Token inicial em planilha Excel — válido 7 dias
     ↓ ← JANELA DE 7 DIAS para agir
3. Brudam usa Postman collection "Certificado Dinâmico [Portal do Desenvolvedor]"
   • Autentica com o token inicial
   • Gera chave privada (.key)
   • Gera CSR com client_id como CN
   • Envia CSR → Itaú retorna certificado assinado (.crt)
   • Combina .key + .crt → itau_certificado.pfx
     ↓
4. Brudam coloca o .pfx no servidor:
   /var/www/clientes/base/motor/app/storage/certs/{cnpj}/itau_certificado.pfx
     ↓
5. Atualizar tela 629 com client_id + client_secret
     ↓
6. Testar em produção (cotação → QR Code → pagamento)
```

### Renovação (confirmada por BRIX-759)

**Mesmo fluxo do onboarding.** O Itaú emite novo client_id + novo token de 7 dias. Tudo muda.

| O que muda na renovação | Detalhe |
|---|---|
| `client_id` | **Novo UUID** — diferente do anterior |
| `client_secret` | **Novo** — gerado no processo |
| `.pfx` no servidor | **Substituído** — novo certificado |
| Tela 629 | **Atualizada** — com os dois novos valores |

> **Evidência direta (BRIX-759):** certificado expirou 05/03/2026 → Brudam gerou novo em 11/03/2026 → novo client_id `ddfd4193-...` (diferente do original `ebe27ae3-...`) → novo client_secret `20afe24f-...` → válido até 11/03/2027.

### Atenção operacional Itaú

| Ponto | Detalhe |
|---|---|
| Token de 7 dias | Se a Brudam demorar mais que 7 dias após receber o token do banco, o processo precisa ser reiniciado |
| Sem sandbox | Todos os testes e a própria geração de certificado são feitos em produção |
| Detecção de expiração | Reativa — só descoberta quando cliente reporta erro (403 C500) |
| Arquivos gerados | `.key` (privado, root-only) + `.csr` + `.pfx` — guardar os três por cliente |
| Contato Itaú | suportemiddle@itau-unibanco.com.br / 0800-770-1685 (Middle Market) |

---

## Santander — processo detalhado (confirmado por fontes externas)

### Onboarding inicial

```
1. Cliente solicita ao gerente: convênio de cobrança ativo + covenantCode
     ↓
2. Cliente acessa developer.santander.com.br com login PJ (Usuário Master)
     ↓
3. "Minhas Aplicações" → cria aplicação em modo Produção
   • Faz upload do arquivo .PFX do certificado A1 (mesmo da NF-e)
     ↓
4. Portal gera automaticamente:
   • client_id
   • client_secret
   • X-Application-Key (fixo, não rotaciona)
     ↓
5. Cliente entrega os três valores à Brudam
     ↓
6. Hub cria Workspace via API (automático, usando covenantCode)
     ↓
7. Hub configura webhook + testa boleto no sandbox
```

### Renovação

Quando o certificado A1 da NF-e é renovado (pelo cliente, junto à AC — Certisign, Serasa, etc.):

```
1. Cliente obtém novo .PFX do certificado A1 renovado
     ↓
2. Acessa o portal Santander → edita a aplicação
   • Faz upload do novo .PFX
     ↓
3. Portal pode gerar novos client_id / client_secret (a verificar)
     ↓
4. Se credentials mudaram: atualizar configuração no TMS
```

| O que muda na renovação | Detalhe |
|---|---|
| `.pfx` | Renovado junto com o A1 — cliente faz no portal |
| `client_id` / `client_secret` | A verificar se mudam após novo upload |
| `X-Application-Key` | Fixo por aplicação — provavelmente não muda |
| Tela de config TMS | Atualizar se credentials mudaram |

### Diferencial Santander

| Ponto | Detalhe |
|---|---|
| Certificado | Reutiliza o A1 da NF-e — sem gerar novo |
| Quem faz o upload | **O cliente**, não a Brudam |
| Sandbox | ✅ Disponível (`trust-sandbox.api.santander.com.br`) |
| Header extra | `X-Application-Key` em todas as requisições |
| Workspace | Criado pela Brudam via API após receber credenciais |
| Contato | developer.santander.com.br / gerente / chat no app |

---

## Banrisul — processo (parcialmente confirmado)

### O que está confirmado

- OAuth 2.0 + mTLS (URL prefix `mtls-api.banrisul.com.br`)
- Credenciais (client_id + client_secret) fornecidas pelo banco após cadastro no portal
- Header obrigatório: `bergs-beneficiario` (13 dígitos — código do beneficiário)
- Homologação via email: `gestao_sistemas_cobranca_operacional@banrisul.com.br`
- Sandbox: `https://mtls-api-h.banrisul.com.br`

### O que está pendente de confirmação

| Ponto | Status |
|---|---|
| Tipo de certificado (A1 NF-e ou dinâmico como Itaú?) | **A confirmar no onboarding real** |
| Processo de upload/registro do certificado no portal | Não documentado publicamente |
| O que muda na renovação | Não documentado |
| Validade do certificado | Não documentado |
| Responsável pelo processo (cliente ou Brudam?) | A definir |

---

## Comparativo completo — operação por operação

### Onboarding inicial

| Etapa | Itaú | Santander | Banrisul |
|---|---|---|---|
| Quem solicita ao banco | Cliente (via gerente) | Cliente (via gerente) | Cliente (via email) |
| O que o banco fornece | client_id + token 7 dias | Nada ainda (só após upload do cert) | client_id + client_secret (após cadastro) |
| Quem gera o certificado | **Brudam** (Postman collection) | **Cliente** (já tem o A1) | A confirmar |
| Onde registra o certificado | Itaú recebe CSR via Postman | Cliente faz upload no portal | A confirmar |
| Quem faz o upload | Brudam | Cliente | A confirmar |
| Janela de tempo crítica | **7 dias** (token expira) | Sem janela | A confirmar |
| Onde fica o .pfx | `/motor/app/storage/certs/{cnpj}/` | Mesmo caminho (a confirmar) | Mesmo caminho (a confirmar) |
| Tela de config TMS | 629 | 422 (conta bancária) | 422 (conta bancária) |
| Sandbox | ❌ Produção direto | ✅ Sandbox disponível | ✅ Sandbox disponível |

### Renovação de certificado

| Etapa | Itaú | Santander | Banrisul |
|---|---|---|---|
| Gatilho | Cert expira (1 ano) | Cert A1 expira (1–3 anos) | A confirmar |
| Cliente precisa solicitar ao banco? | ✅ Sim — novo token | ✅ Sim — novo .PFX no portal | A confirmar |
| Brudam precisa agir? | ✅ Gera novo cert via Postman | Só se credentials mudarem | A confirmar |
| client_id muda? | ✅ **Sim, sempre** | A verificar | A confirmar |
| client_secret muda? | ✅ **Sim, sempre** | A verificar | A confirmar |
| .pfx no servidor muda? | ✅ **Sim** — substituir manualmente | ✅ **Sim** — novo A1 | A confirmar |
| Tela de config atualiza? | ✅ **Obrigatório** | Depende | A confirmar |
| Quem detecta hoje | Erro em produção (reativo) | Erro em produção (reativo) | Reativo |

---

## Impacto na arquitetura do Hub

Para o Hub suportar esses três modelos diferentes de certificado, o adapter de cada banco precisará:

```
Hub — módulo de certificados e credenciais
│
├── Itaú adapter
│   ├── Armazena: client_id, client_secret, itau_certificado.pfx por CNPJ
│   ├── Alerta: 30 dias antes do cert expirar
│   ├── Processo de renovação: assistido pela Brudam (Postman + substitui pfx)
│   └── Obs: client_id e secret SEMPRE mudam na renovação
│
├── Santander adapter
│   ├── Armazena: client_id, client_secret, X-Application-Key, workspace_id por CNPJ
│   ├── Alerta: 30 dias antes do A1 expirar (validade vem no próprio cert)
│   ├── Processo de renovação: cliente faz no portal, entrega novas credentials
│   └── Obs: X-Application-Key provavelmente não muda
│
└── Banrisul adapter
    ├── Armazena: client_id, client_secret, bergs-beneficiario, [cert?] por CNPJ
    ├── Alerta: a definir após confirmar tipo de cert
    └── Processo de renovação: a definir
```

### Funcionalidade crítica a implementar no Hub

**Monitor de expiração de certificados** — hoje nenhum banco é monitorado proativamente. A expiração só é detectada quando o cliente reporta erro. Para os três bancos, o Hub deve:

1. Ler a data de expiração do `.pfx` de cada cliente
2. Disparar alerta interno 30 dias antes
3. Disparar alerta crítico 7 dias antes (especialmente Itaú — coincide com a janela do token)
4. Registrar no log qual cliente, qual banco, qual cert está próximo de expirar

---

*Documento criado em: 2026-06-27 | Baseado em: BRIX-759, arquivos ItauPIX, portal Santander, docs Banrisul*
