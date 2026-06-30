# Estudo -- Hub Financeiro x Open Finance x Parceiros

## Objetivo

Avaliar a melhor estratégia para evoluir o módulo financeiro do TMS,
reduzindo a dependência de arquivos CNAB e aumentando a automação
através de APIs bancárias.

------------------------------------------------------------------------

# Cenário atual

Hoje o TMS possui:

-   Geração de remessa CNAB.
-   Importação de retorno CNAB.
-   Emissão de boletos.
-   Contas a pagar.
-   Contas a receber.
-   Geração de Pix Cobrança (em evolução).
-   Integrações específicas com bancos.

Problema atual:

-   Cada banco possui layout próprio.
-   Cada banco possui APIs próprias.
-   Cada banco possui autenticação diferente.
-   Alto custo de manutenção.

------------------------------------------------------------------------

# Objetivo do Hub Financeiro

Criar uma camada única entre o TMS e os bancos.

Fluxo esperado:

TMS → Payload Financeiro Padrão → Hub Financeiro → Banco/API/CNAB

O TMS não conhecerá mais detalhes de cada banco.

------------------------------------------------------------------------

# Opção 1 -- Desenvolver Hub próprio

## Vantagens

-   Controle total.
-   Independência de fornecedores.
-   Sem mensalidade por cliente.
-   Possibilidade de suportar APIs e CNAB simultaneamente.
-   Evolução gradual banco a banco.
-   Pode adicionar novos bancos conforme demanda.
-   Regras de negócio ficam centralizadas.

## Desvantagens

-   Desenvolvimento maior.
-   Manutenção contínua.
-   Cada banco possui documentação diferente.
-   Cada cliente precisa obter credenciais (Client ID, Client Secret e,
    quando exigido, certificado).

------------------------------------------------------------------------

# Opção 2 -- Utilizar parceiro (Pluggy, Celcoin, etc.)

## Vantagens

-   Menor esforço de integração.
-   APIs padronizadas.
-   Menor manutenção.
-   Evolução acompanhada pelo fornecedor.
-   Em alguns casos dispensa integração individual com vários bancos.

## Desvantagens

-   Custo mensal.
-   Dependência do fornecedor.
-   Alterações de preço.
-   Limitação às funcionalidades disponibilizadas.
-   Risco caso o fornecedor altere APIs ou encerre serviços.

------------------------------------------------------------------------

# Open Finance

## O que é

Padrão regulado pelo Banco Central para compartilhamento de dados
financeiros e iniciação de pagamentos.

## O que oferece

-   Consulta de contas.
-   Consulta de saldo.
-   Consulta de extratos.
-   Consulta de transações.
-   Iniciação de pagamentos (quando suportado).

## O que NÃO resolve

-   Emissão de boletos.
-   Registro de boletos.
-   Cancelamento de boletos.
-   Geração de CNAB.
-   Importação de CNAB.
-   Todas as operações bancárias específicas utilizadas por um ERP/TMS.

------------------------------------------------------------------------

# É possível consumir Open Finance diretamente?

Na prática, não para uma empresa de software comum.

É necessário possuir autorização regulatória do Banco Central (por
exemplo, como Instituição de Pagamento ou Iniciadora de Transação de
Pagamento) ou utilizar um parceiro autorizado.

------------------------------------------------------------------------

# OFX

Não é recomendado investir.

Motivos:

-   Arquivo legado.
-   Não é comunicação em tempo real.
-   APIs bancárias substituem praticamente todas as vantagens do OFX.

------------------------------------------------------------------------

# Arquitetura sugerida

TMS ↓ Payload Financeiro Padrão ↓ Hub Financeiro ├── APIs Bancárias ├──
CNAB ├── Pix ├── Boletos └── Futuramente Open Finance

------------------------------------------------------------------------

# Conclusão

Para o cenário atual do TMS, a estratégia mais sólida parece ser:

1.  Criar um Hub Financeiro próprio.
2.  Padronizar um único payload financeiro.
3.  Implementar adaptadores por banco.
4.  Manter suporte a CNAB enquanto necessário.
5.  Evoluir gradualmente para APIs.
6.  Avaliar parceiros apenas quando houver ganho financeiro ou redução
    significativa de esforço.
7.  Tratar o Open Finance como complemento, e não como substituto das
    integrações bancárias.
