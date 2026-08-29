# Article Metrics Badges — OJS plugin

[![OJS](https://img.shields.io/badge/OJS-3.3%20%7C%203.4%20%7C%203.5-brightgreen)](https://pkp.sfu.ca/ojs/)
[![Version](https://img.shields.io/badge/version-1.1.0.1-blue)](version.xml)
[![License](https://img.shields.io/badge/license-GPL--3.0-lightgrey)](LICENSE)

**⬇️ Install package:** [OJS 3.5](https://github.com/OJSBR/articleMetricsBadges/releases/download/1.1.0.1/articleMetricsBadges-1.1.0.1.tar.gz) · [OJS 3.4](https://github.com/OJSBR/articleMetricsBadges/releases/download/1.1.0.0-ojs3.4/articleMetricsBadges-1.1.0.0-ojs3.4.tar.gz) · [OJS 3.3](https://github.com/OJSBR/articleMetricsBadges/releases/download/1.1.0.0-ojs3.3/articleMetricsBadges-1.1.0.0-ojs3.3.tar.gz) — or browse all [Releases](../../releases).

A generic plugin for **Open Journal Systems (OJS)** that displays article-level metric badges
from **PlumX**, **Dimensions** and **Altmetric**. Each provider is switched on independently,
and so is each display position — inside the article page, in the sidebar block, or both at
the same time.

> **Developed and maintained by [OJSBR](https://ojsbr.com).** See the
> [Credits & authorship](#credits--authorship) section below.

## Compatibility & branches

| OJS version | Branch | Plugin release |
|-------------|--------|----------------|
| OJS 3.5.x   | [`stable-3_5_0`](../../tree/stable-3_5_0) *(default)* | 1.1.0.1 |
| OJS 3.4.x   | [`stable-3_4_0`](../../tree/stable-3_4_0) | 1.1.0.0-ojs3.4 |
| OJS 3.3.x   | [`stable-3_3_0`](../../tree/stable-3_3_0) | 1.1.0.0-ojs3.3 |

> **Do not rename the folder.** OJS 3.4/3.5 derive the plugin's class namespace from the
> installation directory, so the folder must stay `articleMetricsBadges`.

## What it does

- **Three providers, independently switchable** — PlumX (Elsevier), Dimensions (Digital
  Science) and Altmetric. Enable one, two or all three. Only the providers you enable load a
  script on the article page.
- **Two positions, independently switchable** — inside the article page (below the abstract,
  in the details column, or at the bottom of the page) and/or as a sidebar block. Both at once
  is fine.
- **Renders only where it makes sense** — the badges appear on article pages, and only when
  the article has a DOI. No DOI, no markup, no third-party script.
- **Native alignment** — on the article page the badges are wrapped in the same
  `section.item` structure the theme uses for the abstract and the downloads chart, so they
  line up with the rest of the page instead of hanging off the margin.
- **Per-provider display options** — PlumX widget type, orientation, width, border, hide when
  empty; Dimensions badge style and hide-when-uncited; Altmetric badge type and popover side.

## Installation

1. Download the release for your OJS version (or clone the matching branch).
2. Install via **Settings → Website → Plugins → Upload A New Plugin**, or extract the folder
   into `plugins/generic/` so you get `plugins/generic/articleMetricsBadges/`.
3. Enable **Article Metrics Badges** under the *Generic* plugins list.

## Configuration

Open the plugin settings and choose the providers and the positions. **Every provider starts
disabled**: each one has its own terms of use, so enabling is a deliberate act — see
[Third-party services & licensing](#third-party-services--licensing).

To use the sidebar block, tick **In the sidebar block** in the settings and then add
**Article Metrics Badges (sidebar)** to the sidebar under **Settings → Website → Appearance →
Sidebar**.

Badges only render for articles that have a DOI, and each provider only shows data for DOIs it
has already indexed — a DOI that exists in OJS but was never deposited with the registration
agency will never display anything.

## Third-party services & licensing

The plugin is GPL v3 and **contains no third-party code**: it emits markup plus three script
URLs, and the provider's JavaScript is fetched by the reader's browser from the provider's own
CDN. The GPL therefore covers this repository cleanly — but it cannot grant rights over the
services themselves. **Each provider's terms bind the journal that enables it.**

- **PlumX (Elsevier)** — free for non-commercial open access journals and regional
  repositories, on request and approval. The right granted is non-sublicensable, so this
  plugin's licence conveys nothing about the widget.
- **Dimensions (Digital Science)** — free for individual researchers and registered academic
  institutions; use by a business or other organisation is not authorised without a written
  agreement. Requires attribution linking to dimensions.ai.
- **Altmetric** — free badges are for personal pages and institutional repositories; use on a
  journal requires a licence from Altmetric.

Each enabled provider also receives the reader's IP address, user agent and the article DOI —
name them in the journal's privacy statement.

Full detail, links and trademark notices: [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).

## How it works (technical)

- **Scripts** — enqueued from a `TemplateManager::display` hook via `addJavaScript()`, so the
  plugin does not depend on the theme calling the page-footer hook. Only enabled providers are
  enqueued, and only on article pages that have a DOI.
- **Inline badges** — written into one of `Templates::Article::Main`,
  `Templates::Article::Details` or `Templates::Article::Footer::PageFooter`, according to the
  chosen position, wrapped in `section.item` for theme alignment.
- **Sidebar block** — a `BlockPlugin` registered at runtime by the generic plugin, sharing its
  settings; it renders nothing unless the sidebar position is enabled.
- **DOI** — read from the submission in the template context via `getStoredPubId('doi')`; the
  page router is checked with `instanceof` first, so backend AJAX requests are ignored.
- Nothing is cached or proxied: the badge markup carries the DOI and the provider's script
  does the rest.

## Tests

Verified on live journals across the three supported lines: OJS 3.3.0.21 (two journals),
OJS 3.4.0.9 and OJS 3.5.0.5 — plugin enabled, badges rendered on article pages with a DOI,
absent on articles without one, alignment checked against the theme's own `section.item`
elements, and the settings form saved and reopened. An automated suite is not shipped yet.

## Credits & authorship

- **Developed and maintained by** [OJSBR](https://ojsbr.com) — original plugin.
- **Honourable mention** to the **University Library System, University of Pittsburgh**, whose
  [Plum Analytics Artifact Widget](https://github.com/ulsdevteam/ojs-plum-plugin) plugin (GNU
  GPL) is where the approach of placing a PlumX widget on the OJS article page — the template
  hooks, the DOI lookup and the sidebar block registered by a generic plugin — was learned.
  This is a new implementation, but it would not exist without that work.
- PlumX, Dimensions and Altmetric are third-party services of their respective owners; see
  [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).
- Distributed under the **GNU GPL v3**.

## Contributing

Issues and pull requests are welcome. Please target the branch matching the OJS version you
are working against. See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

Distributed under the **GNU GPL v3**. See [`LICENSE`](LICENSE) and `docs/COPYING`.

---

## 🇧🇷 Português

Plugin genérico para o **Open Journal Systems (OJS)** que exibe selos de métricas por artigo
do **PlumX**, do **Dimensions** e do **Altmetric**. Cada provedor é ligado de forma
independente, e cada posição também — dentro da página do artigo, no bloco da barra lateral,
ou nos dois ao mesmo tempo.

> **Desenvolvido e mantido pela [OJSBR](https://ojsbr.com).** Veja a seção
> [Créditos e autoria](#créditos-e-autoria) abaixo.

### Compatibilidade e branches

| Versão do OJS | Branch | Release do plugin |
|---------------|--------|-------------------|
| OJS 3.5.x     | `stable-3_5_0` *(padrão)* | 1.1.0.0 |
| OJS 3.4.x     | `stable-3_4_0` | 1.1.0.0-ojs3.4 |
| OJS 3.3.x     | `stable-3_3_0` | 1.1.0.0-ojs3.3 |

> **Não renomeie a pasta.** O OJS 3.4/3.5 deriva o namespace da classe do diretório de
> instalação, então a pasta precisa continuar `articleMetricsBadges`.

### O que faz

- **Três provedores, ligáveis à parte** — PlumX (Elsevier), Dimensions (Digital Science) e
  Altmetric. Ligue um, dois ou os três. Só os provedores habilitados carregam script na página.
- **Duas posições, ligáveis à parte** — na página do artigo (abaixo do resumo, na coluna de
  detalhes ou no rodapé) e/ou como bloco da barra lateral. As duas ao mesmo tempo funciona.
- **Só aparece onde faz sentido** — os selos saem em página de artigo e apenas quando o artigo
  tem DOI. Sem DOI, nenhuma marcação e nenhum script de terceiro.
- **Alinhamento nativo** — na página do artigo os selos são embrulhados na mesma estrutura
  `section.item` que o tema usa no resumo e no gráfico de downloads, então alinham com o resto
  da página em vez de nascerem fora da margem.
- **Opções por provedor** — tipo de widget, orientação, largura, borda e ocultar-quando-vazio
  no PlumX; estilo do selo e ocultar-sem-citação no Dimensions; tipo de selo e lado da janela
  no Altmetric.

### Instalação

Instale em **Configurações → Website → Plugins → Enviar um novo plugin**, ou extraia a pasta
em `plugins/generic/` (ficando `plugins/generic/articleMetricsBadges/`). Depois ative o
**Selos de Métricas do Artigo** na lista de plugins *Genéricos*.

### Configuração

Nas configurações, escolha os provedores e as posições. **Todo provedor começa desligado**:
cada um tem termos de uso próprios, então habilitar é ato deliberado — veja
[Serviços de terceiros e licenciamento](#serviços-de-terceiros-e-licenciamento).

Para usar o bloco lateral, marque **No bloco da barra lateral** nas configurações e depois
adicione **Selos de Métricas do Artigo (barra lateral)** em **Configurações → Website →
Aparência → Barra lateral**.

Os selos só saem em artigo com DOI, e cada provedor só mostra dado de DOI que ele já indexou —
um DOI que existe no OJS mas nunca foi depositado na agência de registro nunca vai exibir nada.

### Serviços de terceiros e licenciamento

O plugin é GPL v3 e **não contém código de terceiros**: ele emite marcação e três URLs de
script, e o JavaScript do provedor é buscado pelo navegador do leitor no CDN do próprio
provedor. A GPL cobre este repositório sem conflito — mas não pode conceder direito sobre os
serviços. **Os termos de cada provedor vinculam a revista que o habilita.**

- **PlumX (Elsevier)** — gratuito para revistas de acesso aberto não comerciais e repositórios
  regionais, mediante pedido e aprovação. O direito concedido é não sublicenciável, ou seja, a
  licença deste plugin não transfere nada sobre o widget.
- **Dimensions (Digital Science)** — gratuito para pesquisador individual e instituição
  acadêmica registrada; uso por empresa ou outra organização não é autorizado sem acordo
  escrito. Exige atribuição com link para dimensions.ai.
- **Altmetric** — os badges gratuitos são para página pessoal e repositório institucional; o
  uso em revista exige licença da Altmetric.

Cada provedor habilitado também recebe IP, user agent e o DOI do artigo do leitor — cite-os na
declaração de privacidade da revista.

Detalhe completo, links e marcas: [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).

### Testes

Verificado em revistas reais nas três linhas suportadas: OJS 3.3.0.21 (duas revistas),
OJS 3.4.0.9 e OJS 3.5.0.5 — plugin habilitado, selos renderizados em artigos com DOI, ausentes
em artigos sem DOI, alinhamento conferido contra os próprios `section.item` do tema, e
formulário de configuração salvo e reaberto. Ainda não há suíte automatizada.

### Créditos e autoria

- **Desenvolvido e mantido pela** [OJSBR](https://ojsbr.com) — plugin autoral.
- **Menção honrosa** à **University Library System, University of Pittsburgh**, cujo plugin
  [Plum Analytics Artifact Widget](https://github.com/ulsdevteam/ojs-plum-plugin) (GNU GPL) é
  onde se aprendeu a forma de colocar o widget do PlumX na página do artigo do OJS — os hooks
  de template, a obtenção do DOI e o bloco lateral registrado por um plugin genérico. Esta é
  uma implementação nova, mas não existiria sem aquele trabalho.
- PlumX, Dimensions e Altmetric são serviços de terceiros de seus respectivos titulares; veja
  [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).
- Distribuído sob a **GNU GPL v3**.

### Licença

Distribuído sob a **GNU GPL v3**. Veja [`LICENSE`](LICENSE) e `docs/COPYING`.
