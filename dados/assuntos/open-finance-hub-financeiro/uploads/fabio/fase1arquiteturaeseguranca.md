# Fase 1 - Arquitetura e Segurança do Hub Financeiro

## 1. Objetivo da fase 1

A fase 1 do Hub Financeiro deve criar uma camada controlada entre o TMS/Brudam e os bancos, sem substituir o financeiro atual do TMS.

O TMS continua sendo o sistema de origem dos dados financeiros:

- contas bancárias cadastradas;
- contas a pagar;
- contas a receber;
- lançamentos financeiros;
- boletos;
- dados de pagador/beneficiário;
- regras comerciais da transportadora.

O Hub Financeiro passa a ser a camada de execução, comunicação bancária, auditoria, monitoramento e conciliação técnica.

Nesta fase, o objetivo não deve ser trocar todo o CNAB por API de uma vez. O caminho mais seguro é permitir convivência entre:

- CNAB manual atual;
- CNAB controlado pelo Hub;
- APIs bancárias por banco;
- Pix e boleto com Pix quando disponíveis;
- Open Finance como complemento, não como substituto da integração bancária tradicional.

## 2. Princípio arquitetural

O desenho recomendado é:

```text
TMS / Brudam
  financeiro
  contas bancárias
  lançamentos
  regras comerciais
  emissão solicitada pelo usuário

Hub Financeiro
  orquestração
  idempotência
  auditoria
  adapters bancários
  segurança
  logs técnicos
  monitoramento
  webhooks
  conciliação

Bancos / Provedores
  API Santander
  API Itaú
  API Banco do Brasil
  CNAB
  Pix
  Open Finance
```

O Hub não deve decidir regra financeira de negócio que pertence ao TMS. Ele deve receber comandos claros, executar no canal bancário correto e devolver o resultado de forma rastreável.

## 3. Papel do TMS e do Hub

### TMS

Responsabilidades que devem permanecer no TMS:

- criar o lançamento financeiro;
- definir valor, vencimento, pagador, beneficiário e conta bancária;
- controlar vínculo com minuta, fatura, cliente, filial e empresa;
- aplicar regra comercial;
- exibir boleto, status e histórico para o usuário;
- manter o identificador interno, como `id_lancamento`;
- decidir cancelamento, baixa manual, renegociação ou reemissão conforme regra de negócio.

### Hub Financeiro

Responsabilidades novas ou centralizadas no Hub:

- receber comandos financeiros do TMS;
- validar payload mínimo obrigatório;
- identificar banco, conta, convênio e canal de integração;
- gerar ou reservar identificadores técnicos quando aplicável;
- aplicar adapter do banco;
- controlar idempotência;
- armazenar payload enviado e resposta recebida;
- registrar status técnico e status bancário;
- guardar protocolos, NSU, nosso número, trace ID e erros;
- processar webhooks;
- consultar banco em caso de timeout ou status indefinido;
- disponibilizar retorno padronizado ao TMS;
- manter observabilidade operacional.

## 4. Convivência com CNAB

Hoje o TMS já possui contas bancárias cadastradas e gera lançamentos a pagar e a receber, inclusive boletos por arquivos CNAB/TXT manuais.

Esse fluxo não deve ser descartado na fase 1. O CNAB continua importante por três motivos:

- nem todos os bancos, convênios ou clientes estarão prontos para API;
- alguns processos bancários ainda são mais maduros via remessa/retorno;
- a migração precisa ser gradual para reduzir risco operacional.

A proposta é evoluir o cadastro da conta bancária para indicar o modo de integração:

```text
conta bancaria
  banco
  agência
  conta
  carteira
  convênio
  ambiente
  forma_integracao:
    CNAB_MANUAL
    CNAB_HUB
    API_BOLETO
    API_PIX
    OPEN_FINANCE
```

### CNAB manual

Fluxo atual:

```text
TMS gera remessa TXT
usuário envia no banco
usuário baixa retorno
TMS importa retorno
```

### CNAB controlado pelo Hub

Fluxo intermediário recomendado:

```text
TMS cria lançamento
Hub gera remessa
Hub armazena arquivo
Hub controla envio ou disponibilização
Hub importa retorno
Hub devolve status ao TMS
```

Esse modelo já melhora controle e rastreabilidade, mesmo antes de usar API bancária.

### API bancária

Fluxo novo por banco:

```text
TMS cria lançamento
Hub monta payload bancário
Hub chama API do banco
Banco registra boleto/pagamento
Hub salva resposta
Hub devolve dados finais ao TMS
```

## 5. Fluxo de emissão de boleto por API

O fluxo padrão de boleto via API deve ser:

```text
1. TMS cria lançamento financeiro.
2. TMS envia solicitação de emissão ao Hub.
3. Hub valida dados obrigatórios.
4. Hub verifica idempotência.
5. Hub identifica banco, conta, convênio e adapter.
6. Hub gera ou reserva identificadores técnicos.
7. Hub chama a API bancária.
8. Banco registra o boleto.
9. Hub grava resposta completa.
10. Hub devolve ao TMS linha digitável, código de barras, status e identificadores.
```

O TMS não deve enviar apenas `id_lancamento` ao banco. O banco exige dados completos do título, do pagador, do convênio e da conta.

O `id_lancamento` deve ser tratado como referência interna do TMS, não como identificador bancário único.

## 6. Exemplo Santander - emissão de boleto

Na documentação oficial do Santander para emissão de boletos, a API exige que o request contenha campos como:

- `environment`;
- `nsuCode`;
- `nsuDate`;
- `covenantCode`;
- `bankNumber`;
- `dueDate`;
- `issueDate`;
- `nominalValue`;
- `payer`;
- `documentKind`;
- `paymentType`.

Ponto crítico: no Santander, o campo `bankNumber` representa o "nosso número" do boleto e é obrigatório no request.

Portanto, no caso Santander, a API não elimina totalmente a necessidade da regra atual existente no TMS, como o `SantandrBRD`. A responsabilidade deve ser reorganizada.

### Antes

```text
SantandrBRD
  gera nosso número
  calcula dígitos
  monta boleto
  monta CNAB
```

### Proposta

```text
SantanderRules
  regra de nosso número
  carteira
  convênio
  validações bancárias

SantanderCnabAdapter
  geração de remessa
  leitura de retorno

SantanderApiAdapter
  emissão por API
  consulta
  instruções
  cancelamento/baixa quando disponível
  PDF quando disponível
```

O legado não deve ser descartado. Ele deve ser isolado em regras reutilizáveis e adapters por canal.

## 7. Identificadores e idempotência

O Hub precisa controlar os identificadores internos e externos.

Campos recomendados:

```text
id_operacao_hub
id_lancamento_tms
id_empresa
id_filial
banco
agencia
conta
carteira
convenio
canal_integracao
workspace_id
covenantCode
bankNumber
clientNumber
nsuCode
nsuDate
status_operacao
status_bancario
payload_hash
payload_enviado
resposta_banco
trace_id_erro
created_at
updated_at
```

### `id_lancamento`

Deve ser usado como vínculo interno com o TMS.

No Santander, ele pode ser enviado no campo `clientNumber`, que representa o "seu número" do boleto, desde que respeite o tamanho e formato aceitos pelo banco.

### `bankNumber`

Representa o nosso número no Santander.

Deve ser gerado/controlado pelo TMS ou Hub usando a regra do banco, carteira e convênio.

### `nsuCode`

No Santander, é o identificador da chamada/boleto. Deve ser exclusivo por dia e limitado a 20 caracteres alfanuméricos.

Não é recomendável usar somente `id_lancamento` sem controle adicional, porque pode haver colisão por ambiente, empresa, banco ou reprocessamento.

Uma estratégia mais segura:

```text
nsuCode = sequência própria do Hub por banco/convênio/dia
clientNumber = referência do TMS, como id_lancamento
```

### Regra de idempotência

O Hub deve impedir duplicidade operacional.

Chave sugerida:

```text
id_lancamento_tms + banco + conta + convênio + tipo_operacao
```

Se a mesma operação for solicitada novamente, o Hub deve:

- retornar a operação já criada, se ela foi concluída;
- consultar o banco, se o status estiver indefinido;
- bloquear duplicidade, se houver risco de emissão em duplicidade.

## 8. Tratamento de timeout e status indefinido

Em integração bancária, timeout não significa falha de negócio.

Possíveis cenários:

- request não chegou ao banco;
- request chegou e foi processado;
- banco processou, mas a resposta não voltou;
- banco retornou erro temporário;
- Hub caiu após enviar e antes de salvar resposta.

Regra recomendada:

```text
Nunca reenviar automaticamente uma emissão de boleto após timeout sem antes consultar o banco.
```

Fluxo:

```text
1. Marca operação como STATUS_INDETERMINADO.
2. Salva payload e identificadores usados.
3. Executa consulta por bankNumber, covenantCode, nsuCode ou endpoint disponível.
4. Se encontrar o boleto, consolida como registrado.
5. Se não encontrar e a janela segura permitir, libera nova tentativa controlada.
```

## 9. Retorno para o TMS

O Hub deve devolver uma resposta padronizada ao TMS, independente do banco.

Exemplo:

```json
{
  "status": "REGISTRADO",
  "id_operacao_hub": "123456",
  "id_lancamento_tms": "987654",
  "banco": "033",
  "canal": "API_BOLETO",
  "nosso_numero": "12345678901",
  "seu_numero": "987654",
  "linha_digitavel": "03399...",
  "codigo_barras": "0339...",
  "qr_code_pix": null,
  "qr_code_url": null,
  "protocolo_banco": "ABC123",
  "mensagem": "Boleto registrado com sucesso"
}
```

Em caso de erro:

```json
{
  "status": "ERRO",
  "id_operacao_hub": "123456",
  "id_lancamento_tms": "987654",
  "banco": "033",
  "canal": "API_BOLETO",
  "codigo_erro": "VALIDATION_ERROR",
  "mensagem": "Pagador sem CEP válido",
  "trace_id_banco": "trace-id-retornado-pelo-banco"
}
```

## 10. Monitoramento e controle operacional

A fase 1 precisa nascer com monitoramento. Sem isso, a API bancária vira apenas um CNAB automático sem controle real.

### Painel operacional mínimo

Indicadores:

- boletos emitidos com sucesso;
- boletos rejeitados;
- operações em processamento;
- operações com timeout;
- operações com status indefinido;
- webhooks recebidos;
- webhooks com erro;
- tempo médio de resposta por banco;
- falhas por banco;
- falhas por convênio;
- falhas por certificado;
- falhas por autenticação;
- quantidade de retentativas;
- divergências de conciliação.

### Estados de operação

Estados recomendados:

```text
CRIADO
VALIDADO
ENVIANDO
ENVIADO
REGISTRADO
REJEITADO
STATUS_INDETERMINADO
AGUARDANDO_CONSULTA
LIQUIDADO
BAIXADO
CANCELADO
ERRO_TECNICO
ERRO_NEGOCIO
```

### Auditoria

Toda operação deve ter trilha:

- quem solicitou;
- quando solicitou;
- origem da solicitação;
- payload recebido do TMS;
- payload enviado ao banco;
- resposta do banco;
- headers técnicos relevantes sem segredos;
- status anterior;
- status novo;
- motivo da alteração;
- usuário ou processo responsável.

## 11. Webhooks

Webhooks devem ser tratados como eventos externos não confiáveis até validação.

Regras:

- validar origem;
- validar assinatura ou autenticação quando o banco disponibilizar;
- validar mTLS quando aplicável;
- registrar payload bruto;
- aplicar idempotência por ID de evento;
- processar de forma assíncrona;
- não depender apenas do webhook para conciliação;
- reconciliar periodicamente por consulta ativa.

Fluxo:

```text
Banco envia webhook
Hub valida segurança
Hub salva evento bruto
Hub enfileira processamento
Hub identifica operação
Hub atualiza status
Hub notifica TMS
```

## 12. Segurança

O Hub Financeiro manipula dados financeiros, dados pessoais e credenciais bancárias. Portanto, deve ser tratado como componente crítico.

### Autenticação entre TMS e Hub

Recomendado:

- OAuth 2.0 client credentials ou assinatura HMAC por cliente;
- credenciais por ambiente;
- rotação de segredo;
- escopo por operação;
- allowlist de origem quando aplicável;
- TLS obrigatório.

### Autenticação entre Hub e banco

Depende do banco, mas deve suportar:

- OAuth 2.0;
- bearer token;
- API key;
- mTLS;
- certificado A1;
- certificado por cliente/convênio;
- rotação e expiração controlada.

No Santander, a documentação indica uso de token, `X-Application-Key`, certificado digital/mTLS e estrutura de workspace para cobrança.

### Segredos e certificados

Não armazenar segredos em banco de dados comum sem proteção.

Recomendado:

- cofre de segredos;
- criptografia em repouso;
- controle de acesso por papel;
- trilha de auditoria no uso do segredo;
- alerta de expiração de certificado;
- rotação planejada;
- segregação por ambiente;
- nunca gravar segredo completo em log.

### Criptografia

Obrigatório:

- TLS em trânsito;
- criptografia de dados sensíveis em repouso;
- hashing de payload para idempotência;
- mascaramento de CPF/CNPJ, conta, agência, token e certificado em logs;
- backups criptografados.

## 13. LGPD

O Hub trata dados pessoais, principalmente dados de pagadores, favorecidos e usuários operadores.

Dados envolvidos:

- nome;
- CPF/CNPJ;
- endereço;
- CEP;
- e-mail;
- telefone;
- dados bancários;
- dados de cobrança;
- histórico de pagamento;
- identificadores internos.

Princípios que devem guiar o desenho:

- finalidade: usar dados apenas para execução financeira;
- necessidade: trafegar somente o mínimo necessário;
- adequação: cada dado precisa ter motivo operacional;
- segurança: proteger dados contra acesso indevido;
- prevenção: reduzir risco antes do incidente;
- transparência: permitir rastreabilidade e prestação de contas;
- responsabilização: manter evidência de controles aplicados.

Medidas práticas:

- mascarar dados pessoais em logs;
- separar payload bruto de visualização operacional;
- restringir acesso por perfil;
- registrar acesso a dados sensíveis;
- definir retenção de payloads;
- permitir anonimização ou descarte quando juridicamente possível;
- classificar dados por sensibilidade;
- documentar operadores e controladores;
- validar base legal com jurídico/compliance.

Observação: a aderência final à LGPD deve ser validada com jurídico/compliance, especialmente retenção, compartilhamento com bancos/provedores e resposta a incidentes.

## 14. Banco Central, segurança e conformidade

O Hub deve ser desenhado seguindo boas práticas compatíveis com exigências regulatórias do setor financeiro, especialmente para segurança cibernética, gestão de riscos, continuidade, auditoria, terceiros e rastreabilidade.

Pontos que precisam ser considerados desde a fase 1:

- política de segurança cibernética;
- gestão de incidentes;
- plano de continuidade;
- controle de acesso;
- segregação de funções;
- gestão de terceiros;
- trilha de auditoria;
- rastreabilidade de transações;
- proteção de credenciais;
- testes de segurança;
- monitoramento contínuo;
- gestão de vulnerabilidades;
- backup e recuperação;
- classificação de dados;
- governança de mudanças.

Mesmo que o Hub não seja uma instituição financeira regulada diretamente em todos os cenários, ele opera em um fluxo financeiro crítico e deve nascer com padrão compatível com ambiente bancário.

## 15. Open Finance

Open Finance não substitui o Hub Financeiro.

Ele pode complementar o projeto em cenários como:

- consulta de saldo;
- consulta de extrato;
- iniciação de pagamento;
- conciliação;
- visão financeira consolidada;
- Pix via iniciação quando aplicável.

Mas para o cenário atual do TMS, ainda são necessários:

- boleto;
- CNAB;
- APIs proprietárias dos bancos;
- convênios de cobrança;
- regras por carteira;
- nosso número;
- remessa e retorno em clientes que ainda não usam API.

Portanto, a arquitetura deve suportar Open Finance como mais um canal, não como base única do projeto.

## 16. Onboarding bancário

Cada banco tem exigências próprias.

O onboarding deve controlar:

- cliente;
- banco;
- conta;
- convênio;
- carteira;
- ambiente sandbox;
- ambiente produção;
- certificado;
- API key;
- client ID;
- client secret;
- workspace;
- webhook;
- status de homologação;
- status de produção.

No Santander, a documentação trabalha com conceito de workspace para cobrança. Antes de emitir boletos, o cliente precisa ter o workspace e o convênio corretamente configurados.

## 17. Modelo de adapters

O Hub deve evitar regras bancárias espalhadas.

Modelo sugerido:

```text
BankAdapterInterface
  emitirBoleto()
  consultarBoleto()
  baixarBoleto()
  alterarBoleto()
  gerarPdf()
  processarWebhook()
  importarRetornoCnab()
  gerarRemessaCnab()

SantanderApiAdapter
SantanderCnabAdapter
ItauApiAdapter
ItauCnabAdapter
BancoBrasilApiAdapter
BancoBrasilCnabAdapter
```

Separar:

- regra bancária;
- transporte HTTP;
- autenticação;
- persistência;
- regra de negócio do TMS.

## 18. Banco de dados mínimo

Tabelas ou entidades recomendadas:

```text
hub_contas_bancarias
hub_credenciais_bancarias
hub_certificados
hub_workspaces
hub_operacoes
hub_operacao_eventos
hub_payloads
hub_webhooks
hub_erros_bancarios
hub_cnab_remessas
hub_cnab_retornos
hub_logs_auditoria
```

Dados sensíveis devem ser criptografados ou referenciados por cofre de segredos.

## 19. Controle de erros

Separar erros técnicos de erros de negócio.

### Erro técnico

Exemplos:

- timeout;
- banco indisponível;
- falha TLS;
- certificado expirado;
- token inválido;
- erro 500;
- indisponibilidade de DNS;
- falha de rede.

### Erro de negócio

Exemplos:

- pagador inválido;
- CEP inválido;
- CPF/CNPJ inválido;
- convênio inválido;
- carteira incompatível;
- nosso número duplicado;
- vencimento fora da regra;
- valor inválido.

O TMS precisa receber erro claro para o usuário corrigir quando for erro de negócio. Erro técnico deve virar retentativa, alerta ou status operacional.

## 20. Faseamento recomendado

### Fase 1 - Base segura e boleto

Escopo:

- modelo de contas e credenciais;
- operação de boleto;
- idempotência;
- auditoria;
- Santander como primeiro adapter API;
- CNAB mantido como fallback;
- painel operacional mínimo;
- logs e segurança;
- conciliação básica.

### Fase 2 - Expansão bancária

Escopo:

- Itaú;
- Banco do Brasil;
- Bradesco;
- Sicoob/Sicredi se necessário;
- CNAB Hub;
- mais instruções bancárias;
- baixa, alteração e consulta;
- webhooks por banco.

### Fase 3 - Open Finance e Pix avançado

Escopo:

- Open Finance para dados e pagamentos;
- Pix;
- Pix Automático quando aplicável;
- conciliação avançada;
- dashboards financeiros.

## 21. Decisão técnica recomendada

O melhor caminho técnico para iniciar o projeto é:

1. Não remover CNAB.
2. Criar o Hub como camada intermediária.
3. Manter o TMS como dono do lançamento.
4. Criar adapters por banco e por canal.
5. Começar com boleto Santander API por ser um caso real e documentado.
6. Reaproveitar regras atuais como geração de nosso número.
7. Implementar idempotência antes da primeira integração bancária.
8. Implementar auditoria e logs desde o início.
9. Tratar certificado, token e segredo como ativos críticos.
10. Separar erro técnico de erro de negócio.
11. Criar monitoramento operacional desde a fase 1.
12. Validar LGPD, Bacen e contratos bancários com jurídico/compliance antes da produção.

## 22. Fontes e referências

- Documentação Santander: https://developer.santander.com.br/api/user-guide/issuance-of-bills
- API Santander de cobrança: `collection_bill_management/v2`
- LGPD - Lei 13.709/2018: https://www.planalto.gov.br/
- Banco Central do Brasil: https://www.bcb.gov.br/
- Open Finance Brasil: https://openfinancebrasil.org.br/

## 23. Observações finais

Este documento consolida a discussão inicial de arquitetura e segurança.

Ele não substitui:

- análise jurídica;
- validação contratual com bancos;
- homologação bancária;
- revisão de segurança;
- desenho definitivo de banco de dados;
- especificação técnica de endpoints;
- matriz de responsabilidade entre TMS, Hub e cliente.

Para a fase 1, a recomendação é construir uma base pequena, auditável e segura, começando por boleto Santander API, mantendo CNAB como fallback e garantindo que o TMS continue sendo o dono do lançamento financeiro.
