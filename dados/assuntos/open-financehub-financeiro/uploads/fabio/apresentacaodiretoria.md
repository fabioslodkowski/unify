# Estratégia de Integração Bancária — Apresentação à Diretoria

> **Objetivo deste documento:** apoiar a decisão sobre qual caminho seguir para modernizar a integração do TMS com os bancos.

---

## Contexto: qual problema estamos resolvendo

Hoje o TMS se comunica com cada banco de forma manual e fragmentada:

- Cada banco tem seu próprio layout de arquivo (CNAB)
- Cada banco tem sua própria API, autenticação e regras
- Qualquer mudança em um banco exige desenvolvimento específico
- Alto custo de manutenção. Baixa automação. Risco operacional

**O que queremos:** uma única camada que o TMS use para falar com qualquer banco, sem precisar saber os detalhes de cada um.

---

## Opção A — Open Finance Brasil

### O que é

Sistema regulado pelo Banco Central que padroniza APIs entre instituições financeiras. Todo banco participante implementa os mesmos endpoints.

### O que oferece

| Funcionalidade | Disponível |
|---|---|
| Consulta de saldo (multi-banco) | ✅ |
| Consulta de extrato (multi-banco) | ✅ |
| Consulta de transações | ✅ |
| Iniciação de Pix | ✅ |
| Pix Automático (recorrente) | ✅ |
| Dados de cartões e crédito | ✅ |

### O que **NÃO** oferece

| Funcionalidade | Situação |
|---|---|
| Emissão de boletos | ❌ Não cobre |
| Cancelamento/alteração de boletos | ❌ Não cobre |
| Geração de CNAB de remessa | ❌ Não cobre |
| Importação de CNAB de retorno | ❌ Não cobre |
| Pagamentos em lote (fornecedores) | ❌ Não cobre |
| Conciliação completa de cobranças | ❌ Não cobre |

> **Conclusão prática:** o Open Finance resolve a parte de *leitura de dados* e *Pix simples*. As operações do dia a dia de um TMS (boletos, CNAB, pagamentos em lote) continuam fora do escopo.

### Como usar — 3 caminhos possíveis

#### Caminho 1 — Usar um agregador (ex: Pluggy, TecnoSpeed, Belvo)

- Você contrata uma empresa que já é participante
- Integra a API deles (semanas)
- Sem burocracia com o Banco Central
- **Custo:** mensalidade por consentimento ativo ou por chamada de API
- **Risco:** dependência do fornecedor. Se ele mudar preço ou encerrar, você está preso

#### Caminho 2 — Usar provedor de infraestrutura de ITP (ex: Dock, Iniciador.com.br)

- Permite oferecer iniciação de Pix para os seus clientes
- Você não precisa de autorização do Banco Central
- **Custo:** por transação iniciada
- **Risco:** mesma dependência de fornecedor

#### Caminho 3 — Tornar-se participante direto do Open Finance

- Você passa a ser o "Pluggy"
- Exige autorização do Banco Central como Instituição de Pagamento
- **Custo estimado:** R$ 500 mil a R$ 2 milhões+ em infraestrutura, segurança, certificações e compliance
- **Tempo:** 12 a 24 meses até produção
- **Risco:** alto investimento antes de validar o produto no mercado

### Riscos do Open Finance

| Risco | Nível |
|---|---|
| Não cobre operações de boleto e CNAB | Alto — o TMS precisa dessas operações |
| Dependência de fornecedor (caminhos 1 e 2) | Médio |
| Custo regulatório e de compliance (caminho 3) | Alto |
| Mudanças nas regras do Banco Central | Médio (acontece, mas com aviso prévio) |
| Consentimento precisa ser renovado pelo cliente | Médio — fluxo adicional de UX |

### Resumo de custo estimado — Open Finance

| Caminho | Investimento inicial | Custo recorrente |
|---|---|---|
| Agregador (Pluggy etc.) | Baixo (integração simples) | R$ 0,05 a R$ 0,50 por consentimento/mês |
| Provedor ITP | Baixo | Por transação iniciada (variável) |
| Participante direto | R$ 500 mil – R$ 2 mi+ | Alto (equipe, compliance, infraestrutura) |

---

## Opção B — Hub Financeiro próprio (Gateway / Middleware)

### O que é

Uma camada de software desenvolvida internamente que fica entre o TMS e os bancos. O TMS envia um comando padronizado ("pagar este boleto", "gerar esta cobrança") e o Hub decide como executar — via API REST, via CNAB ou futuramente via Open Finance.

```
TMS
 │
 ▼
Hub Financeiro  ←──── única integração do TMS
 │
 ├── API Banco do Brasil
 ├── API Itaú
 ├── API Sicredi
 ├── CNAB Banrisul
 ├── API Pix Inter
 └── (Open Finance no futuro)
```

### O que resolve

| Funcionalidade | Atende |
|---|---|
| Emissão de boletos | ✅ |
| Cancelamento/alteração de boletos | ✅ |
| Geração e importação de CNAB | ✅ |
| Pagamentos em lote | ✅ |
| Pix Cobrança e Pix pagamento | ✅ |
| Extrato e saldo | ✅ |
| Conciliação | ✅ |
| Webhooks de liquidação | ✅ |
| Novos bancos (adicionar adaptador) | ✅ |
| Open Finance (como complemento futuro) | ✅ |

### Como funciona na prática

O TMS envia sempre o mesmo objeto:

```json
{
  "operacao": "emitir_boleto",
  "banco": "itau",
  "valor": 1500.00,
  "vencimento": "2026-07-10",
  "pagador": { ... }
}
```

O Hub traduz para a linguagem do banco, executa, e devolve o resultado padronizado. O TMS não sabe (e não precisa saber) se foi via API REST, CNAB ou outra tecnologia.

### Riscos do Hub próprio

| Risco | Nível | Mitigação |
|---|---|---|
| Banco muda a API sem aviso | Médio | Health check + monitoramento por banco |
| Alto custo de desenvolvimento inicial | Médio | Iniciar com 3–4 bancos principais |
| Manutenção contínua por banco | Médio | Padrão de adapter isola o impacto |
| Equipe interna precisa conhecer APIs bancárias | Médio | Documentação + banco de conhecimento interno |

### Custo estimado — Hub próprio

| Fase | Escopo | Estimativa |
|---|---|---|
| MVP (3–4 bancos principais) | Pix + Boleto + Extrato | 3–6 meses de desenvolvimento |
| Expansão (10 bancos) | + CNAB + Pagamentos em lote | +4–6 meses |
| Manutenção anual | Suporte a mudanças de API | 1 desenvolvedor dedicado parcial |

Sem custo por transação. Sem mensalidade para fornecedor externo. Investimento é em desenvolvimento.

---

## Comparativo direto — nosso cenário (TMS)

| Necessidade do TMS | Open Finance | Hub próprio |
|---|---|---|
| Emitir boletos | ❌ | ✅ |
| CNAB remessa/retorno | ❌ | ✅ |
| Pix Cobrança | Parcial (só iniciação) | ✅ |
| Pagamentos em lote | ❌ | ✅ |
| Extrato multi-banco | ✅ (via agregador) | ✅ |
| Sem dependência de fornecedor | ❌ (caminhos 1 e 2) | ✅ |
| Custo por transação | Sim | Não |
| Prazo para produção | Semanas (agregador) | Meses (MVP) |
| Cobre tudo que o TMS precisa | ❌ | ✅ |

---

## Recomendação

O Open Finance **não substitui** o Hub Financeiro para o nosso cenário. As operações centrais do TMS (boletos, CNAB, pagamentos em lote) estão fora do escopo do Open Finance regulado.

**A recomendação é:**

### Fase 1 — Hub Financeiro próprio (MVP)
Desenvolver o Hub com os 3–4 bancos de maior volume dos clientes.
Cobrir: Pix, Boleto, Extrato, CNAB básico.
Prazo estimado: 3 a 6 meses.

### Fase 2 — Expansão
Adicionar os demais bancos com base na demanda real dos clientes.
Adicionar monitoramento, circuit breaker e health check por banco.

### Fase 3 — Open Finance como complemento
Quando o Hub estiver estável, integrar Open Finance **via agregador** para enriquecer a leitura de dados (extrato multi-banco sem precisar de credenciais por banco).
Não para substituir — para complementar.

---

## Resumo executivo (uma página)

| | Open Finance (agregador) | Hub Financeiro próprio |
|---|---|---|
| Resolve boletos e CNAB? | ❌ Não | ✅ Sim |
| Resolve Pix? | Parcial | ✅ Sim |
| Resolve extrato multi-banco? | ✅ Sim | ✅ Sim |
| Precisa de autorização BCB? | Não (via agregador) | Não |
| Custo recorrente por transação? | Sim | Não |
| Dependência de fornecedor? | Alta | Baixa |
| Cobre 100% do TMS? | ❌ Não | ✅ Sim |
| Prazo MVP | Semanas | 3–6 meses |
| Risco regulatório | Baixo | Nenhum |

> **Decisão sugerida:** desenvolver o Hub Financeiro próprio como produto central, e usar Open Finance via agregador como camada complementar de leitura de dados quando houver demanda específica.
