# Por que usar boleto ainda?

> Contexto: clientes do TMS são transportadoras que cobram de empresas (B2B). O pagador define o meio de pagamento — não a transportadora.

---

## O contexto do TMS muda tudo

Os clientes do TMS são **transportadoras**. Elas não cobram pessoas físicas — cobram **empresas** (B2B). E no B2B brasileiro, o fluxo de pagamento é diferente do B2C.

---

## Por que o boleto ainda é necessário no B2B

### 1 — Prazo de pagamento (o mais importante)
No transporte, raramente se paga na entrega. O padrão é 30/60/90 dias após a emissão do CTe.

O boleto foi construído para isso: tem data de vencimento, juros automáticos, multa, e o pagador sabe exatamente quando e quanto vai pagar.

O Pix Cobrança (com QR dinâmico) também suporta prazo — mas a adoção no B2B ainda é baixa.

### 2 — O departamento financeiro do pagador
Grandes empresas (os clientes das transportadoras) têm departamentos de contas a pagar que processam boletos em lote via DDA ou homebanking. O fluxo deles é:

```
Recebe boleto → Agenda no banco → Banco paga no vencimento
```

Pedir que eles mudem para Pix exige que mudem o processo interno deles — não é decisão da transportadora.

### 3 — Antecipação de recebíveis (capital de giro)
Boleto pode ser descontado no banco antes do vencimento. A transportadora emite um boleto de R$50k para 60 dias e consegue adiantar esse valor no banco como capital de giro.

**Pix não tem esse mecanismo.** É a maior limitação do Pix no B2B hoje.

### 4 — Entidades governamentais e grandes empresas
Prefeituras, estatais e muitas empresas de grande porte só pagam via boleto ou TED. Não é opção da transportadora — é exigência contratual do pagador.

### 5 — Integração com NF-e / CTe
O boleto é ligado ao documento fiscal. O fluxo de contas a receber do ERP/TMS inteiro é construído em torno disso: emite CTe → gera boleto → aguarda retorno de liquidação.

---

## O que o Pix resolve hoje (e o que não resolve)

| Situação | Pix resolve? |
|---|---|
| Pagamento imediato (B2C) | ✅ Melhor que boleto |
| Pagamento com prazo 30/60/90 dias (B2B) | ⚠️ Pix Cobrança resolve, mas adoção baixa |
| Pagador é grande empresa com AP estruturado | ❌ Dificilmente aceita mudar o processo |
| Pagador é governo | ❌ Quase sempre exige boleto |
| Antecipação de recebíveis | ❌ Pix não tem esse mecanismo |
| Cobranças recorrentes com valor variável | ✅ Pix Automático resolve |
| Pagamento em lote pelo pagador | ❌ Ainda fraco no B2B |

---

## A tendência real do mercado

O boleto **está morrendo lentamente**, mas não morreu. O caminho do mercado é:

```
Boleto puro
    ↓
BoletoPix (boleto com QR para pagar por Pix)     ← hoje a maioria está aqui
    ↓
Pix Cobrança com prazo (QR dinâmico)             ← chegando
    ↓
Pix Automático + Open Finance                    ← futuro
```

O **BoletoPix** é o ponto de equilíbrio atual: a transportadora emite um boleto normal, mas o pagador pode pagar via Pix antes do vencimento. O melhor dos dois mundos — e já é suportado pelos bancos.

---

## O que isso muda na arquitetura do Hub

O Hub precisa suportar boleto porque os clientes atuais precisam. Mas a modelagem deve tratar **cobrança** como o conceito central, e boleto como um dos canais de entrega:

```
Hub — módulo Collections
├── Boleto registrado          ← necessário hoje
├── BoletoPix                  ← necessário hoje
├── Pix Cobrança (QR dinâmico) ← crescendo
└── Pix Automático             ← futuro
```

Assim, quando o mercado B2B migrar de boleto para Pix Cobrança, o TMS não precisa mudar nada — só o Hub adiciona o novo canal.

---

## Conclusão

**O boleto ainda é necessário para o TMS porque o pagador (empresa, governo) é quem dita o meio de pagamento — não a transportadora.** A transportadora não pode simplesmente trocar boleto por Pix sem que os clientes dela aceitem mudar o processo financeiro deles.

O que faz sentido **hoje**: suportar boleto + BoletoPix + Pix Cobrança ao mesmo tempo, e deixar o cliente do TMS escolher qual emitir para cada pagador.
