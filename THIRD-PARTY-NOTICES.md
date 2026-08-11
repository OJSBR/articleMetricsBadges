# Third-party services — what this plugin loads, and under whose terms

This plugin is licensed under the **GNU GPL v3**, and it contains **no third-party code**.
It ships only markup and three script URLs. Each provider's JavaScript is fetched at runtime
by the reader's browser, directly from the provider's CDN — it is never redistributed here.

Because of that, the GPL covers this repository cleanly. What the GPL **cannot** grant you is
the right to use the providers' services: each one has its own terms, and **those terms bind
the journal that switches a provider on**, not OJSBR and not this plugin.

**Every provider is disabled by default.** Turning one on is a deliberate act by someone who
knows the journal is entitled to it.

## What is loaded

| Provider | Script loaded on the article page | Data sent |
|---|---|---|
| PlumX (Elsevier) | `https://cdn.plu.mx/widget-all.js` | Article DOI, plus the reader's IP address, user agent and referrer |
| Dimensions (Digital Science) | `https://badge.dimensions.ai/badge.js` | Article DOI, plus the reader's IP address, user agent and referrer |
| Altmetric | `https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js` | Article DOI, plus the reader's IP address, user agent and referrer |

Nothing is cached or stored by the plugin: it renders the badge markup and the provider's
script does the rest.

## Terms, in summary

The summaries below reflect the providers' published terms as consulted on **2026-08-11**.
They are a pointer, not legal advice, and vendor terms change — check the links before you
enable anything.

### PlumX Metrics Artifact Widget — Elsevier

- Embedding the widget forms an agreement with Elsevier. The right granted is limited,
  non-exclusive, non-transferable, **non-sublicensable** and revocable, to display the widget
  on a site you control.
- You may not locally cache Elsevier content, and you may not make the widget available on a
  third-party site you do not control unless that site accepts the terms too.
- Provided free of charge to **non-commercial open access journals and regional repositories,
  on request and approval**.
- Terms: <https://plu.mx/plum/developers/widgets>

Because the right is **non-sublicensable**, the GPL licence of this plugin conveys no right
whatsoever over the widget. Each journal accepts Elsevier's terms on its own account.

### Dimensions Badges and Metrics API — Digital Science

- Free for **individual researchers** (own work, non-commercial) and for **registered academic
  institutions** for internal, non-commercial purposes, including free institutional websites.
- Use by or on behalf of a business or other organisation is **not authorised** without a
  written agreement. Publishers are directed to `publishing@digital-science.com`.
- Requires attribution on the page, linking back to dimensions.ai; no more than one request
  per second; only the latest metrics may be displayed (no caching of values).
- Terms: <https://www.dimensions.ai/policies-terms-metrics/>

### Altmetric Badges

- Free badges are for **individual researchers' personal pages** and for **institutional
  repositories**.
- Use on a journal (or any commercial context) requires a **licence issued by Altmetric** —
  they track badge usage because their own data sources require it.
- Terms and licensing: <https://www.altmetric.com/solutions/free-tools/free-badges-for-individual-researchers/>

## Trademarks

PlumX and Elsevier are trademarks of Elsevier B.V.; Dimensions is a trademark of Digital
Science & Research Solutions; Altmetric is a trademark of Altmetric LLP. The names are used
here only to identify the services the plugin can display. No affiliation with, sponsorship
by or endorsement from any of them is claimed. No provider logo is distributed in this
repository — every logo you see in a badge comes from the provider's own CDN at render time.

## Privacy

Each enabled provider receives the reader's IP address, user agent, referrer and the article
DOI. This is a third-party disclosure: name the providers you enable in the journal's privacy
statement, and consider your local data-protection rules (in Brazil, the LGPD).

---

## 🇧🇷 Português

Este plugin é licenciado sob a **GNU GPL v3** e **não contém código de terceiros**. Ele
distribui apenas marcação HTML e três URLs de script. O JavaScript de cada provedor é buscado
em tempo de execução pelo navegador do leitor, direto do CDN do provedor — nunca é
redistribuído aqui.

Por isso a GPL cobre este repositório sem conflito. O que a GPL **não** pode conceder é o
direito de usar os serviços: cada provedor tem termos próprios, e **esses termos vinculam a
revista que liga o provedor**, não a OJSBR nem este plugin.

**Todos os provedores vêm desligados.** Ligar é um ato deliberado de quem sabe que a revista
tem direito àquilo.

### O que é carregado

| Provedor | Script carregado na página do artigo | Dado enviado |
|---|---|---|
| PlumX (Elsevier) | `https://cdn.plu.mx/widget-all.js` | DOI do artigo, além de IP, user agent e referrer do leitor |
| Dimensions (Digital Science) | `https://badge.dimensions.ai/badge.js` | DOI do artigo, além de IP, user agent e referrer do leitor |
| Altmetric | `https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js` | DOI do artigo, além de IP, user agent e referrer do leitor |

### Termos, em resumo

Os resumos abaixo refletem os termos publicados pelos provedores, consultados em
**11/08/2026**. São um ponteiro, não parecer jurídico, e termos de fornecedor mudam — confira
os links antes de habilitar qualquer coisa.

**PlumX Metrics Artifact Widget (Elsevier).** Embutir o widget constitui acordo com a
Elsevier. O direito concedido é limitado, não exclusivo, intransferível, **não sublicenciável**
e revogável, para exibir em site que você controla. É vedado cachear o conteúdo localmente e
disponibilizar o widget em site de terceiro que você não controla sem que esse terceiro
aceite os termos. Gratuito para **revistas de acesso aberto não comerciais e repositórios
regionais, mediante pedido e aprovação**. Termos: <https://plu.mx/plum/developers/widgets>

Como o direito é **não sublicenciável**, a licença GPL deste plugin não transfere direito
nenhum sobre o widget: cada revista aceita os termos da Elsevier por conta própria.

**Dimensions Badges e Metrics API (Digital Science).** Gratuito para **pesquisador individual**
(obra própria, uso não comercial) e para **instituição acadêmica registrada**, em uso interno
não comercial, incluindo site institucional gratuito. O uso por ou em nome de empresa ou outra
organização **não é autorizado** sem acordo escrito; editoras devem procurar
`publishing@digital-science.com`. Exige atribuição na página com link para dimensions.ai, no
máximo uma requisição por segundo e exibição sempre da métrica mais recente (sem cache).
Termos: <https://www.dimensions.ai/policies-terms-metrics/>

**Altmetric Badges.** Os badges gratuitos são para **página pessoal de pesquisador** e para
**repositório institucional**. O uso em revista (ou em qualquer contexto comercial) exige
**licença emitida pela Altmetric** — eles controlam quem usa porque as próprias fontes de
dados exigem. Termos:
<https://www.altmetric.com/solutions/free-tools/free-badges-for-individual-researchers/>

### Marcas

PlumX e Elsevier são marcas da Elsevier B.V.; Dimensions é marca da Digital Science & Research
Solutions; Altmetric é marca da Altmetric LLP. Os nomes são usados aqui apenas para
identificar os serviços que o plugin pode exibir. Não se alega vínculo, patrocínio ou endosso
de nenhum deles. Nenhum logotipo de provedor é distribuído neste repositório — todo logo que
aparece no selo vem do CDN do próprio provedor no momento da renderização.

### Privacidade

Cada provedor habilitado recebe IP, user agent, referrer e o DOI do artigo do leitor. Isso é
compartilhamento com terceiro: cite na declaração de privacidade da revista os provedores que
você habilitar e observe a LGPD.
