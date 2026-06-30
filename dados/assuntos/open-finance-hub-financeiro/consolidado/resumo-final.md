# Resumo Consolidado — Open Finance Hub Financeiro

## 🎯 Resumo Executivo

O **Open Finance não substitui integralmente o Hub Financeiro** necessário para o TMS/Admin Brudam.

A conclusão consolidada dos arquivos é que o Open Finance resolve bem a camada de **consulta, agregação de dados bancários, saldos, extratos, transações e iniciação de Pix**, mas **não cobre de forma suficiente as operações financeiras centrais de um TMS**, como:

- emissão e registro de boletos;
- alteração e baixa de boletos;
- geração e leitura de CNAB;
- pagamentos em lote;
- conciliação completa de cobranças;
- gestão de layouts, certificados e regras específicas por banco.

Portanto, a recomendação inicial é seguir com uma arquitetura de **Hub Financeiro próprio/orquestrador**, com payload financeiro padronizado para o TMS/Admin Brudam e conectores especializados para:

1. **CNAB**, onde ainda for necessário;
2. **APIs bancárias diretas**, para bancos prioritários;
3. **Pix e BoletoPix**, preferencialmente via API;
4. **parceiros especializados**, como TecnoSpeed/PlugBank ou Celcoin, para acelerar cobertura multi-banco;
5. **Open Finance/Pluggy/Belvo**, como camada complementar para leitura de dados, extrato, saldo, enriquecimento e conciliação.

A Pluggy já foi estudada e inclusive há um módulo inicial implementado, mas, conforme os documentos analisados, ela deve ser vista como **complemento de Open Finance**, não como substituta completa da integração bancária operacional.

---

## 📋 Contexto

O problema central levantado pela equipe é que o **TMS/Admin Brudam depende hoje de fluxos financeiros fragmentados**, compostos por:

- geração manual ou semiautomática de arquivos **CNAB de remessa**;
- importação de **CNAB de retorno**;
- integrações específicas por banco;
- APIs diferentes para boleto, Pix, extrato e pagamentos;
- certificados digitais e mTLS;
- credenciais e regras distintas;
- homologações individuais por banco;
- baixa padronização e alto custo de manutenção.

O objetivo declarado é definir uma **arquitetura unificada para o financeiro do TMS e do Admin Brudam**, separando:

- o que deve continuar via CNAB;
- o que deve migrar para APIs bancárias;
- o que pode usar Open Finance;
- onde parceiros como Pluggy, TecnoSpeed, Celcoin ou Belvo fazem sentido;
- quais são os custos e impactos envolvidos.

Também foi registrado pela equipe que o parceiro **Asaas foi estudado, mas não será considerado na documentação geral**.  
Fonte: decisão consolidada da equipe, registrada por Fabio.

---

## 💡 Principais Pontos Levantados

### 1. Open Finance resolve apenas parte do problema

O Open Finance no Brasil é um sistema regulado pelo Banco Central que permite compartilhamento padronizado de dados e serviços financeiros mediante consentimento do cliente.

Segundo Arthur, no arquivo `openfinanceknowledgebase.md`, o Open Finance contempla:

| Área | Cobertura |
|---|---|
| Dados públicos de instituições financeiras | Sim |
| Dados de contas, cartões, crédito e câmbio | Sim |
| Iniciação de pagamentos, especialmente Pix | Sim |
| Dados de investimentos, seguros e previdência | Em expansão |
| Portabilidade de crédito | Em expansão a partir de 2025/2026 |

Porém, conforme Fabio reforça em `analise2.md` e `apresentacaodiretoria.md`, o Open Finance **não cobre adequadamente** operações como:

- emissão de boleto registrado;
- cancelamento/alteração de boleto;
- geração de CNAB de remessa;
- importação de CNAB de retorno;
- pagamentos em lote para fornecedores;
- conciliação completa de cobranças;
- regras específicas de cobrança bancária.

Conclusão prática: **Open Finance é útil, mas não é o motor operacional completo do financeiro do TMS.**

---

### 2. Pluggy é forte em dados, mas não substitui APIs bancárias operacionais

A Pluggy foi analisada em mais de um documento.

Segundo Arthur, em `openfinanceknowledgebase.md`, já foi implementado um módulo inicial no Hub Financeiro Brudam usando o **Pluggy Connect Widget**, com fluxo de autenticação bancária e coleta de dados.

Fluxo descrito:

```text
Usuário clica em "Conectar Banco"
        ↓
Backend gera token em /api/pluggy/connect-token
        ↓
Pluggy Connect Widget autentica no banco via OAuth2
        ↓
Callback onSuccess salva itemId no backend
        ↓
/api/pluggy/accounts lista contas e saldos
        ↓
/api/pluggy/transactions consulta extratos/transações
```

Endpoints citados por Arthur:

| Endpoint | Método | Finalidade |
|---|---|---|
| `/api/pluggy/config` | GET | Verificar configuração da Pluggy |
| `/api/pluggy/config` | POST | Salvar configuração |
| `/api/pluggy/connect-token` | POST | Gerar token de conexão |
| `/api/pluggy/accounts` | GET | Consultar contas e saldos |
| `/api/pluggy/transactions` | GET | Consultar transações |

Fabio, em `analise2.md` e `analiseparceiros.md`, destaca que a Pluggy faz bem:

- conexão com contas bancárias;
- consulta de saldo;
- consulta de extrato;
- listagem de transações;
- identificação de pagamentos recebidos;
- consolidação de movimentações multi-banco;
- iniciação de pagamentos/Pix quando suportado.

Mas Fabio também ressalta que a Pluggy **não substitui a maior parte do fluxo financeiro operacional do TMS**, pois não oferece diretamente:

- emissão de boletos registrados;
- CNAB de remessa e retorno;
- pagamentos em lote;
- fluxo completo de cobrança bancária.

Portanto, a Pluggy deve ser posicionada como **camada complementar de Open Finance**, especialmente para dados e conciliação, e não como solução principal para boleto, CNAB e pagamentos.

---

### 3. APIs bancárias continuam necessárias para operações críticas

No arquivo `analise-inicial-apis-bancos-v01.md`, Fabio analisou APIs do Banrisul e Santander, incluindo cobrança/boleto e Pix.

As conclusões transversais indicam que, apesar de existir alguma padronização, ainda há muitas diferenças relevantes por banco.

| Tema | Observação consolidada |
|---|---|
| Autenticação para boleto | OAuth 2.0 Client Credentials |
| Autenticação para Pix | OAuth 2.0 + mTLS |
| Padrão técnico | REST + JSON, mas com nomenclaturas diferentes |
| Retorno de boleto | Ainda pode depender de CNAB de retorno |
| Retorno de Pix | Webhook em tempo real |
| Homologação | Cada banco exige processo próprio |
| Certificados | Hub precisará gerenciar certificados mTLS por banco |
| BoletoPix | Suportado por Banrisul e Santander; recomendado como padrão |
| Santander | Exige Workspace antes de registrar boletos |
| Santander | Usa header `X-Application-Key` além do Bearer Token |
| Santander | Usa URLs com prefixo `trust-`, relacionado à camada mTLS |

A arquitetura sugerida por Fabio para o Hub inclui adaptadores por banco:

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
    └── gerenciamento mTLS por banco
```

Essa análise reforça que o Hub precisará lidar com **diferenças operacionais reais entre bancos**, mesmo quando houver APIs modernas.

---

### 4. CNAB ainda não desaparece no curto prazo

A equipe levantou a dúvida se seria necessário manter um Hub capaz de gerar envios e processar retornos bancários com base no layout de cada banco.

A resposta consolidada é: **sim, pelo menos em uma fase de transição**.

Fabio, em `analise2.md`, descreve que o fluxo atual provavelmente envolve:

```text
TMS gera CNAB de remessa
Banco processa
TMS importa CNAB de retorno
Algumas integrações usam Pix Cobrança por API
```

Mesmo com APIs bancárias e Open Finance, os documentos indicam que:

- nem todos os bancos/produtos terão API madura;
- alguns retornos de boleto ainda dependem de arquivo;
- clientes podem continuar usando bancos ou carteiras antigas;
- layouts CNAB/FEBRABAN ainda serão necessários em determinados cenários;
- uma migração total para API será gradual.

Portanto, o Hub Financeiro deve suportar **modelo híbrido**: CNAB + APIs bancárias + Open Finance + parceiros.

---

### 5. Parceiros podem acelerar a cobertura multi-banco

No arquivo `analiseparceiros.md`, Fabio compara players relevantes para integração bancária.

#### TecnoSpeed — PlugBank

Pontos levantados:

- foco em software houses brasileiras;
- produto direcionado para ERPs e TMSs;
- boleto em mais de 40 bancos homologados;
- Pix Cobrança e Pix pagamento;
- CNAB remessa e retorno;
- Open Finance;
- conciliação;
- pagamentos em lote;
- modelo em que a software house integra uma vez e cada cliente usa sua conta bancária.

Conclusão do arquivo: **é o produto mais próximo do cenário Brudam**, pois combina boleto, Pix, CNAB e Open Finance em uma API voltada a software houses.

#### Celcoin

Pontos levantados:

- BaaS mais completo;
- boleto e Pix Cobrança;
- pagamentos;
- extrato consolidado;
- conta digital, se necessário;
- cartões, se necessário;
- Open Finance;
- modelo transacional;
- casos de sucesso e escala relevante.

Conclusão: é robusto e pode ser interessante se a Brudam quiser evoluir para serviços financeiros mais completos, mas talvez entregue mais do que o necessário inicialmente.

#### Pluggy

Pontos levantados:

- foco em Open Finance;
- extrato e dados bancários;
- iniciação de Pix;
- Pix Automático;
- widget pronto de conexão bancária.

Limitações:

- não emite boleto registrado diretamente;
- não trabalha com CNAB;
- não cobre pagamentos em lote.

Conclusão: complemento, não solução principal.

#### Zoop

Pontos levantados:

- foco em marketplaces, white-label, maquininhas e split;
- oferece boleto, Pix e cartão;
- modelo com percentual sobre transação.

Conclusão: para cobranças altas de frete, o custo pode crescer muito. Não parece o modelo ideal para TMS.

#### Dock

Pontos levantados:

- BaaS e iniciação de Pix via Open Finance;
- Pix, Pix Automático, boleto, conta digital e cartões;
- preço sob consulta.

Conclusão: solução robusta, mas mais voltada a fintechs e produtos financeiros próprios.

#### Asaas

O Asaas foi citado em `analiseparceiros.md`, mas há uma decisão consolidada da equipe, registrada por Fabio, de que **não será considerado na documentação geral**. Por isso, não deve ser priorizado nesta recomendação.

---

### 6. Existem três caminhos estratégicos possíveis

Com base em `apresentacaodiretoria.md` e `analise2.md`, há três caminhos principais:

#### Caminho A — Usar apenas Open Finance/agregador

Exemplo:

```text
TMS
 │
Pluggy / Belvo / Agregador
 │
Bancos
```

Vantagens:

- integração mais rápida;
- boa cobertura para dados, saldos e extratos;
- menor burocracia regulatória;
- widget e consentimento prontos.

Limitações:

- não cobre boleto e CNAB;
- não substitui integrações bancárias operacionais;
- dependência de fornecedor;
- consentimento precisa ser gerenciado e renovado.

#### Caminho B — Construir Hub Financeiro próprio

Exemplo:

```text
TMS/Admin Brudam
       │
Payload Financeiro Padrão
       │
Hub Financeiro
       ├── CNAB BB
       ├── CNAB Itaú
       ├── CNAB Sicredi
       ├── API Santander
       ├── API Pix Inter
       ├── API Sicoob
       └── ...
```

Vantagens:

- maior controle;
- padronização interna;
- independência parcial de fornecedores;
- possibilidade de evoluir por banco/produto;
- arquitetura aderente ao legado e ao futuro.

Limitações:

- maior esforço técnico;
- manutenção contínua de layouts, APIs e certificados;
- necessidade de homologação banco a banco.

#### Caminho C — Modelo híbrido com Hub próprio + parceiros

Exemplo:

```text
TMS/Admin Brudam
       │
Payload Financeiro Padrão
       │
Hub Financeiro Brudam
       ├── TecnoSpeed/PlugBank ou Celcoin
       ├── APIs diretas dos bancos prioritários
       ├── CNAB quando necessário
       ├── Pluggy/Belvo para Open Finance
       └── Motor de conciliação
```

Vantagens:

- equilíbrio entre controle e velocidade;
- reduz esforço de integração banco a banco;
- permite manter CNAB onde necessário;
- permite usar Open Finance onde faz sentido;
- cria uma camada interna padronizada para o TMS/Admin.

Limitações:

- exige desenho arquitetural cuidadoso;
- pode criar dependência parcial de parceiros;
- requer análise comercial e técnica detalhada.

Este é o caminho mais aderente ao problema descrito.

---

## ✅ Pontos de Consenso

1. **Open Finance não resolve sozinho o financeiro do TMS/Admin Brudam.**  
   Fontes: Fabio em `analise2.md` e `apresentacaodiretoria.md`; Arthur em `openfinanceknowledgebase.md`.

2. **Pluggy é útil para conexão bancária, saldos, extratos e transações, mas não substitui boleto, CNAB e pagamentos em lote.**  
   Fontes: Fabio em `analise2.md` e `analiseparceiros.md`; Arthur em `openfinanceknowledgebase.md`.

3. **O Hub Financeiro ainda é necessário como camada de orquestração e padronização.**  
   Fontes: Fabio em `analise2.md`, `analise-inicial-apis-bancos-v01.md` e `apresentacaodiretoria.md`.

4. **CNAB continuará existindo em algum grau durante a transição.**  
   Fontes: Fabio em `analise2.md` e `analise-inicial-apis-bancos-v01.md`.

5. **APIs bancárias diretas ainda terão papel importante, especialmente para boleto, Pix Cobrança, BoletoPix e pagamentos.**  
   Fonte: Fabio em `analise-inicial-apis-bancos-v01.md`.

6. **Pix deve ser tratado com cuidado especial por exigir OAuth 2.0 + mTLS.**  
   Fonte: Fabio em `analise-inicial-apis-bancos-v01.md`.

7. **BoletoPix aparece como padrão recomendado onde houver suporte.**  
   Fonte: Fabio em `analise-inicial-apis-bancos-v01.md`.

8. **TecnoSpeed/PlugBank parece ser o parceiro mais aderente ao cenário de software house/TMS.**  
   Fonte: Fabio em `analiseparceiros.md`.

9. **Celcoin é alternativa robusta, especialmente se a estratégia futura incluir serviços financeiros mais amplos.**  
   Fonte: Fabio em `analiseparceiros.md`.

10. **Tornar-se participante direto do Open Finance não parece adequado neste momento pelo custo, prazo e complexidade regulatória.**  
    Fonte: Fabio em `apresentacaodiretoria.md`.

---

## ⚡ Pontos Divergentes

### 1. Pluggy como solução principal ou complementar

Há uma expectativa inicial de que a Pluggy pudesse substituir integrações bancárias.

Porém, a análise de Fabio indica que ela substitui principalmente a parte de **leitura e agregação financeira**, não a operação bancária completa.

**Divergência resolvida parcialmente:**  
A Pluggy pode ser usada, mas como complemento ao Hub, não como núcleo único do financeiro.

---

### 2. Construir tudo internamente ou usar parceiro

Há dois caminhos possíveis:

- construir conectores próprios para cada banco;
- usar parceiros como TecnoSpeed/PlugBank ou Celcoin para reduzir complexidade.

**Ponto em aberto:**  
Ainda é necessário comparar custo, cobertura bancária, SLA, contrato, lock-in e aderência técnica antes de decidir o nível de dependência de parceiro.

---

### 3. Manter CNAB ou migrar agressivamente para APIs

Existe consenso de que CNAB não desaparecerá imediatamente, mas ainda não está definido:

- quais bancos/produtos continuarão via CNAB;
- quais serão migrados primeiro para API;
- se o Hub deverá gerar CNAB internamente ou delegar isso a um parceiro.

---

### 4. Escopo do Hub Financeiro

Há uma diferença importante entre:

- Hub como simples conversor CNAB/API;
- Hub como orquestrador completo de cobrança, pagamento e conciliação;
- Hub como produto financeiro estratégico para a Brudam.

A recomendação tende ao modelo de **orquestrador completo**, mas a profundidade do escopo ainda precisa ser definida.

---

## ⚠️ Riscos Identificados

| Risco | Nível | Descrição | Fonte |
|---|---:|---|---|
| Acreditar que Open Finance substitui boleto/CNAB | Alto | Pode levar a uma arquitetura incompleta | Fabio |
| Dependência excessiva de fornecedor | Médio/Alto | Pluggy, TecnoSpeed, Celcoin ou outro podem alterar preço, escopo ou SLA | Fabio |
| Complexidade de homologação por banco | Alto | Cada banco tem sandbox, aprovação e regras próprias | Fabio |
| Gestão de certificados mTLS | Alto | Pix exige OAuth 2.0 + mTLS, com certificados por banco | Fabio |
| Manutenção de layouts CNAB | Médio/Alto | Layouts variam por banco e podem mudar | Fabio |
| Consentimento no Open Finance | Médio | Cliente precisa autorizar e renovar acesso | Arthur/Fabio |
| Custo regulatório para participação direta no Open Finance | Alto | Exige autorização, compliance, segurança e infraestrutura | Fabio |
| Custo transacional elevado em alguns parceiros | Médio/Alto | Modelos percentuais podem ser ruins para fretes de alto valor | Fabio |
| Fragmentação de arquitetura | Alto | Risco de criar múltiplas integrações sem um domínio financeiro padronizado | Consolidação |
| Escopo excessivo no início | Médio | Tentar cobrir todos os bancos e produtos de uma vez pode atrasar entrega | Consolidação |

---

## 💰 Custos e Impactos

### Custos conhecidos ou estimados

| Item | Custo/Modelo | Observação | Fonte |
|---|---|---|---|
| Pluggy | A partir de R$ 2.500/mês no plano básico, até 20 contas | Enterprise sob consulta | Fabio |
| Open Finance via agregador | Baixo investimento inicial; recorrente por consentimento ou chamada | Estimativa citada: R$ 0,05 a R$ 0,50 por consentimento/mês em modelo genérico | Fabio |
| Provedor ITP | Custo por transação iniciada | Depende do fornecedor | Fabio |
| Participante direto Open Finance | R$ 500 mil a R$ 2 milhões+ | Prazo estimado de 12 a 24 meses | Fabio |
| TecnoSpeed/PlugBank | Não publicado | Precisa proposta comercial por volume | Fabio |
| Celcoin | Não publicado | Modelo transacional/comercial sob consulta | Fabio |
| Zoop | Percentual sobre transação | Pode ficar caro para fretes de alto valor | Fabio |
| Asaas | Estudado, mas excluído da doc geral | Não considerar na recomendação | Fabio/equipe |

---

### Impactos técnicos

A adoção do Hub Financeiro implica criar ou contratar capacidades para:

- padronizar payloads financeiros do TMS/Admin;
- transformar comandos internos em CNAB, API bancária ou chamada de parceiro;
- gerenciar credenciais por cliente e banco;
- gerenciar certificados mTLS;
- controlar tokens OAuth;
- receber webhooks de Pix e liquidação;
- importar CNAB de retorno;
- conciliar títulos, pagamentos, baixas e extratos;
- manter rastreabilidade/auditoria das operações;
- tratar retentativas, idempotência e falhas;
- suportar ambientes de homologação e produção por banco.

---

### Impactos de negócio

| Impacto | Descrição |
|---|---|
| Redução de trabalho manual | Menos geração/importação manual de arquivos |
| Menor risco operacional | Padronização diminui erros de layout e processo |
| Melhor conciliação | Uso combinado de retorno, webhook e extrato |
| Maior velocidade de onboarding | Parceiros podem acelerar cobertura multi-banco |
| Nova dependência estratégica | Escolha de parceiro deve ser feita com cuidado |
| Potencial novo produto | Hub pode virar diferencial competitivo do TMS/Admin Brudam |

---

## ❓ Dúvidas em Aberto

1. **Quais bancos devem ser priorizados na primeira fase?**  
   Exemplo: Banrisul, Santander, Itaú, Banco do Brasil, Sicredi, Sicoob, Caixa, Inter etc.

2. **Quais produtos financeiros são obrigatórios no MVP?**  
   - boleto registrado;
   - BoletoPix;
   - Pix Cobrança;
   - Pix pagamento;
   - CNAB remessa;
   - CNAB retorno;
   - pagamento de fornecedores;
   - extrato;
   - conciliação.

3. **O Hub deve gerar CNAB internamente ou delegar a um parceiro como TecnoSpeed/PlugBank?**

4. **Qual será o papel definitivo da Pluggy?**  
   Apenas Open Finance/dados? Conciliação? Iniciação de Pix? Complemento ao extrato?

5. **A Brudam quer apenas integrar bancos ou também oferecer serviços financeiros?**  
   Essa resposta muda a atratividade de Celcoin, Dock e outros BaaS.

6. **Qual é o volume esperado?**  
   - quantidade de clientes;
   - contas conectadas;
   - boletos/mês;
   - Pix/mês;
   - pagamentos em lote;
   - retornos CNAB;
   - consultas de extrato.

7. **Qual modelo comercial é aceitável?**  
   - mensalidade fixa;
   - custo por cliente;
   - custo por conta;
   - custo por transação;
   - percentual sobre valor;
   - modelo híbrido.

8. **Quais SLAs são necessários?**  
   Especialmente para cobrança, liquidação, baixa, Pix e pagamento de fornecedores.

9. **Como será feita a gestão de consentimento Open Finance por cliente?**

10. **Como serão armazenados certificados, client secrets, tokens e credenciais bancárias?**

11. **Quais responsabilidades ficam no TMS/Admin e quais ficam no Hub?**

12. **Como lidar com clientes que já possuem convênios bancários próprios?**

---

## 🏆 Recomendação Inicial

A recomendação consolidada é seguir com uma arquitetura de **Hub Financeiro Brudam em modelo híbrido**, e não apostar exclusivamente em Open Finance ou Pluggy.

### Recomenda-se:

1. **Criar o Hub Financeiro como camada central de orquestração**
   
   O TMS/Admin Brudam deve falar com uma API financeira interna padronizada, sem conhecer detalhes de CNAB, banco, certificado ou parceiro.

2. **Manter suporte a CNAB na fase inicial**
   
   CNAB ainda será necessário para bancos/produtos sem API madura ou para clientes com processos legados.

3. **Migrar gradualmente para APIs bancárias**
   
   Priorizar bancos e produtos de maior volume, começando por cobrança, BoletoPix, Pix Cobrança e retorno em tempo real.

4. **Avaliar TecnoSpeed/PlugBank como principal parceiro de short list**
   
   Pelo material analisado, é o parceiro mais aderente ao cenário de software house/TMS, pois cobre boleto, Pix, CNAB, conciliação e pagamentos em lote.

5. **Avaliar Celcoin como alternativa estratégica**
   
   Especialmente se houver interesse futuro em conta digital, produtos financeiros, BaaS ou expansão além da integração bancária tradicional.

6. **Usar Pluggy/Open Finance como camada complementar**
   
   Aplicações recomendadas:
   - consulta de saldos;
   - extratos;
   - transações;
   - enriquecimento de dados;
   - apoio à conciliação;
   - iniciação de Pix onde fizer sentido.

7. **Não seguir, neste momento, com participação direta no Open Finance**
   
   O custo, prazo e complexidade regulatória não parecem compatíveis com a necessidade atual.

8. **Não considerar Asaas na recomendação principal**
   
   Conforme decisão já tomada pela equipe.

### Arquitetura recomendada

```text
TMS / Admin Brudam
        │
        │ Payload financeiro padrão
        ▼
Hub Financeiro Brudam
        │
        ├── Motor de Cobrança
        │   ├── Boleto
        │   ├── BoletoPix
        │   └── Pix Cobrança
        │
        ├── Motor de Pagamentos
        │   ├── Pix pagamento
        │   ├── pagamentos em lote
        │   └── fornecedores
        │
        ├── Motor CNAB
        │   ├── remessa
        │   └── retorno
        │
        ├── Motor de Conciliação
        │   ├── CNAB retorno
        │   ├── webhooks
        │   └── extrato/Open Finance
        │
        ├── Conectores Bancários Diretos
        │   ├── Banrisul
        │   ├── Santander
        │   └── demais bancos prioritários
        │
        ├── Parceiros
        │   ├── TecnoSpeed/PlugBank
        │   ├── Celcoin
        │   └── outros avaliados
        │
        └── Open Finance
            ├── Pluggy
            └── Belvo/opcional
```

---

## 🚀 Próximos Passos

### 1. Definir escopo do MVP

Sugestão de MVP:

- cadastro de contas/convênios bancários por cliente;
- emissão de boleto ou BoletoPix;
- Pix Cobrança;
- recepção de webhook Pix;
- importação de CNAB retorno;
- consulta de status de cobrança;
- conciliação básica;
- painel operacional no Admin Brudam.

---

### 2. Priorizar bancos da primeira fase

Criar ranking com base em:

- volume de clientes por banco;
- volume de boletos/Pix;
- dificuldade técnica;
- disponibilidade de API;
- dependência atual de CNAB;
- impacto operacional.

Banrisul e Santander já possuem análise inicial e podem servir como referência técnica.

---

### 3. Abrir contato comercial/técnico com parceiros

Prioridade sugerida:

1. **TecnoSpeed/PlugBank**
2. **Celcoin**
3. **Pluggy**, para delimitar escopo real de Open Finance e Pix
4. **Belvo**, se houver interesse em alternativa para dados/Open Finance

Solicitar:

- tabela de preços;
- cobertura bancária;
- cobertura de CNAB;
- cobertura de Pix e BoletoPix;
- pagamentos em lote;
- conciliação;
- SLA;
- documentação técnica;
- ambiente sandbox;
- modelo multi-cliente/multi-conta;
- política de segurança e LGPD;
- limites transacionais;
- tempo médio de onboarding.

---

### 4. Desenhar domínio financeiro interno

Criar modelo canônico para entidades como:

- conta bancária;
- banco;
- convênio;
- carteira;
- cobrança;
- boleto;
- Pix Cobrança;
- pagamento;
- fornecedor;
- retorno;
- liquidação;
- conciliação;
- extrato;
- transação;
- certificado;
- credencial;
- webhook.

---

### 5. Definir matriz de decisão: CNAB x API x Parceiro x Open Finance

Para cada operação, definir o canal preferencial:

| Operação | Canal recomendado inicial |
|---|---|
| Boleto registrado | API bancária ou parceiro |
| BoletoPix | API bancária ou parceiro |
| Pix Cobrança | API bancária ou parceiro |
| Retorno de boleto | CNAB e/ou webhook, conforme banco |
| Retorno Pix | Webhook |
| Extrato | Open Finance/Pluggy ou API bancária |
| Conciliação | Hub Financeiro combinando CNAB, webhook e extrato |
| Pagamento em lote | Parceiro ou API bancária |
| Dados financeiros consolidados | Open Finance |

---

### 6. Criar POC técnica

Sugestão de POC:

- 1 banco com API direta, como Banrisul ou Santander;
- 1 parceiro, preferencialmente TecnoSpeed/PlugBank;
- 1 integração Open Finance já iniciada com Pluggy;
- 1 fluxo completo de cobrança até conciliação.

Fluxo ideal da POC:

```text
TMS gera cobrança
      ↓
Hub registra boleto/BoletoPix
      ↓
Banco/parceiro confirma registro
      ↓
Cliente paga
      ↓
Hub recebe webhook ou CNAB retorno
      ↓
Hub consulta extrato/Open Finance se necessário
      ↓
Hub concilia
      ↓
TMS/Admin recebe status final
```

---

### 7. Definir requisitos de segurança

Itens mínimos:

- cofre de segredos;
- criptografia de credenciais;
- gestão segura de certificados mTLS;
- segregação por cliente;
- logs auditáveis;
- controle de permissões;
- LGPD;
- rastreabilidade de consentimentos Open Finance;
- retentativas e idempotência;
- monitoramento de webhooks;
- alertas operacionais.

---

### 8. Produzir documento de decisão para diretoria

Com base neste consolidado, preparar uma versão executiva contendo:

- problema atual;
- limitações do Open Finance;
- necessidade do Hub;
- opções de build/buy/híbrido;
- custos estimados;
- riscos;
- recomendação;
- cronograma de MVP.

---

## 📚 Fontes Utilizadas

1. **`openfinanceknowledgebase.md` — Usuário: Arthur**  
   Conteúdo utilizado:
   - definição de Open Finance no Brasil;
   - fases de implementação;
   - atores do ecossistema;
   - regulamentação;
   - dados de mercado 2025/2026;
   - módulo Pluggy implementado;
   - fluxo de conexão com Pluggy;
   - endpoints do backend;
   - casos de uso e visão técnica de Open Finance.

2. **`analise-inicial-apis-bancos-v01.md` — Usuário: Fabio**  
   Conteúdo utilizado:
   - análise de APIs do Banrisul e Santander;
   - autenticação OAuth 2.0;
   - exigência de mTLS para Pix;
   - diferenças entre boleto, Pix, CNAB e webhook;
   - necessidade de adapters por banco;
   - módulo de certificados;
   - recomendação de BoletoPix;
   - particularidades do Santander e Banrisul.

3. **`analise2.md` — Usuário: Fabio**  
   Conteúdo utilizado:
   - análise sobre o papel real da Pluggy;
   - comparação entre fluxo atual CNAB e futuro Hub;
   - entendimento de que Pluggy não substitui boleto/CNAB;
   - discussão sobre arquiteturas possíveis;
   - defesa do Hub Financeiro como orquestrador.

4. **`analiseparceiros.md` — Usuário: Fabio**  
   Conteúdo utilizado:
   - comparação entre TecnoSpeed/PlugBank, Celcoin, Pluggy, Asaas, Zoop e Dock;
   - avaliação de aderência ao cenário de TMS;
   - custos conhecidos ou modelos comerciais;
   - conclusão de que TecnoSpeed/PlugBank é o parceiro mais próximo do cenário Brudam;
   - conclusão de que Pluggy é complementar.

5. **`apresentacaodiretoria.md` — Usuário: Fabio**  
   Conteúdo utilizado:
   - explicação executiva do problema;
   - comparação entre Open Finance e Hub Financeiro;
   - limitações do Open Finance;
   - caminhos possíveis de adoção;
   - riscos do Open Finance;
   - custos estimados para agregador, ITP e participação direta;
   - recomendação implícita de não depender apenas de Open Finance.

6. **Decisão consolidada da equipe — Fabio**  
   Conteúdo utilizado:
   - parceiro Asaas foi estudado, mas não será considerado na documentação geral.

---
*Atualizado em 30/06/2026 08:54 via OPENAI (gpt-5.5) · Unify*
