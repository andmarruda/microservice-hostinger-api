# Branding — Hostinger VPS Engineer Console

Este documento é a fonte única de verdade para todas as decisões de identidade visual e verbal do projeto. Siga-o ao construir novas páginas, componentes, e-mails ou qualquer outra superfície visível ao usuário.

---

## 1. Identidade

| Propriedade | Valor |
|-------------|-------|
| **Nome do produto** | Hostinger VPS |
| **Descritor** | engineer console |
| **Empresa** | Novos Horizontes |
| **Tagline** | Gerenciamento de infraestrutura para engenheiros |
| **Público-alvo** | Engenheiros de software e operadores de infraestrutura |

### Lockup completo
```
Hostinger VPS
engineer console          ← sempre minúsculo, monospace, muted
```

### Referências abreviadas
- Em headings e navegação: **Hostinger VPS**
- Em prosa: "o console", "a plataforma"
- Nunca: "app Laravel", "o sistema", "a ferramenta"

---

## 2. Paleta de Cores

### Brand (ações primárias, destaques, CTAs)

| Token | Hex | Uso |
|-------|-----|-----|
| `--color-brand-50` | `#f5f3ff` | Fundos com tint em superfícies claras |
| `--color-brand-100` | `#ede9fe` | Hover de fundo (modo claro) |
| `--color-brand-200` | `#ddd6fe` | Bordas em superfícies claras |
| `--color-brand-400` | `#a78bfa` | Ícones e acentos em superfícies escuras |
| `--color-brand-500` | `#8b5cf6` | Destaques interativos |
| `--color-brand-600` | `#7c3aed` | **Cor de ação primária** — botões, links |
| `--color-brand-700` | `#6d28d9` | Hover para ações primárias |
| `--color-brand-800` | `#5b21b6` | Bordas em superfícies escuras |
| `--color-brand-900` | `#4c1d95` | Fundos escuros com tint de marca |
| `--color-brand-950` | `#2e1065` | Fundo mais profundo da marca (badges, pills) |

> `brand-600` é o primário canônico. Use-o em todo CTA, indicador de estado ativo e acento de marca.

### Neutros (superfícies, texto, bordas)

A UI usa a escala `slate` do Tailwind para superfícies escuras e `gray` para superfícies claras.

| Papel | Superfície clara | Superfície escura |
|-------|-----------------|------------------|
| Fundo de página | `white` / `gray-50` | `slate-950` |
| Card / painel | `white` | `slate-900` |
| Borda | `gray-200` | `slate-800` / `brand-800` |
| Texto principal | `slate-900` | `white` |
| Texto secundário | `slate-500` | `slate-400` |
| Placeholder / muted | `gray-400` | `slate-600` |

### Semânticos

| Significado | Fundo | Texto | Borda | Classes Tailwind |
|-------------|-------|-------|-------|-----------------|
| Sucesso | `green-50` | `green-800` | `green-200` | `bg-green-600` em botões |
| Alerta | `yellow-50` | `yellow-800` | `yellow-200` | — |
| Destrutivo | `red-50` | `red-600` / `red-800` | `red-200` | `bg-red-600` em botões |
| Informativo | `blue-50` | `blue-800` | `blue-200` | — |

---

## 3. Tipografia

### Fontes

| Papel | Família | Token CSS | Origem |
|-------|---------|-----------|--------|
| UI sans | Instrument Sans 400/500/600 | `--font-sans` | Bunny Fonts CDN |
| Código / terminal | ui-monospace → Cascadia Code → Source Code Pro → Menlo | `--font-mono` | Stack do sistema |

### Escala (padrão Tailwind, aplicada com consistência)

| Elemento | Tamanho | Peso | Exemplo de classe |
|----------|---------|------|-------------------|
| Heading de página (h1) | 36–48 px | 600 | `text-4xl font-semibold tracking-tight` |
| Heading de seção (h2) | 24 px | 600 | `text-2xl font-semibold tracking-tight` |
| Título de card | 16 px | 500 | `text-base font-medium` |
| Corpo | 14 px | 400 | `text-sm` |
| Legenda / badge | 12 px | 400–500 | `text-xs` |
| Rótulo de terminal | 12 px | 400 | `font-mono text-xs` |

### Regras de uso do monospace
Use `font-mono` para:
- Hostnames e endereços IP de servidores
- IDs de VPS e identificadores de recursos
- Saídas de terminal e strings de comando
- Descritores técnicos inline em prosa (ex.: badge `engineer console`)

---

## 4. Logomark

O logomark é um SVG simplificado de rack de servidores. Deve aparecer ao lado do nome do produto em toda shell autenticada e não autenticada.

### Construção
```
Container 36×36 px, rounded-lg (rx=8)
├── Rect externo: fundo brand-950, preenchimento brand-600 a 15% de opacidade
├── Unidade de servidor superior: stroke brand-400, 1.5px, rx=2
├── Unidade de servidor inferior: stroke brand-400, 1.5px, rx=2
├── LEDs de status: preenchimento brand-600, cx=12.5
└── Barras de drive: preenchimento brand-400 a 50% de opacidade
```

### Em fundos escuros (ex.: painel de branding, e-mails escuros)
- Preenchimento do container: `brand-600` a 15% de opacidade
- Strokes: `brand-400` (`#a78bfa`)
- LEDs: `brand-600` (`#7c3aed`)

### Em fundos claros (ex.: logo mobile, e-mails claros)
- Preenchimento do container: `brand-100`
- Strokes: `brand-600` (`#7c3aed`)
- LEDs: `brand-700` (`#6d28d9`)

### Espaço livre
Mantenha pelo menos `8px` de espaço livre em todos os lados. Nunca coloque o logomark diretamente contra uma borda.

### O que não fazer
- Não recolorir o logomark com cinza ou preto
- Não esticar ou distorcer o SVG
- Não usar o logomark sem o nome do produto em tamanhos menores que 24×24 px

---

## 5. Componentes de UI

Todos os componentes estão em `resources/js/components/ui/`. A seguir, a tabela canônica de variantes de cada um.

### Button

| Variante | Fundo | Texto | Usar para |
|----------|-------|-------|-----------|
| `default` | `gray-900` | branco | Ações gerais, submits de formulário |
| `destructive` | `red-600` | branco | Deletar, revogar, terminar |
| `outline` | white / `gray-50` | `gray-700` | Ações secundárias |
| `ghost` | transparente / `gray-100` no hover | `gray-700` | Botões de ícone, ações em linha de tabela |
| `link` | — | `gray-900` sublinhado | Links de texto inline |
| `success` | `green-600` | branco | Confirmar, aprovar |

Para **ações primárias de marca** (submit do login, CTA principal por página), use a cor de marca diretamente via `className`:
```
className="bg-brand-600 text-white hover:bg-brand-700 ..."
```
Isso é intencional — a variante `default` do `Button` usa `gray-900` para consistência geral da UI, enquanto botões com cor de marca são reservados para a ação mais destacada de uma tela.

### Badge

| Variante | Usar para |
|----------|-----------|
| `default` | Status neutro, rótulos |
| `success` | Em execução, ativo, aprovado |
| `warning` | Pendente, prestes a expirar, revisão necessária |
| `destructive` | Parado, falhou, revogado, atrasado |
| `info` | Estado informativo, na fila |
| `outline` | Tags somente leitura, rótulos não semânticos |

### Alert

| Variante | Usar para |
|----------|-----------|
| `default` | Avisos informativos gerais |
| `success` | Operação concluída com sucesso |
| `warning` | Problema não bloqueante que precisa de atenção |
| `destructive` | Erro, operação falhou, acesso negado |

### Input / Label
- Sempre associe cada `<Input>` a um `<Label>` apontando para o mesmo `id`
- Estado de erro: adicione `border-red-400 focus-visible:ring-red-400` ao input e renderize um `<p className="text-xs text-red-600">` imediatamente abaixo

---

## 6. Padrões de Layout

### Login / shell não autenticada
Layout de duas colunas em tela cheia:
- **Esquerda (60%, `lg+`):** `bg-slate-950` com utilitário `dot-grid` + gradiente radial de marca. Contém logo, tagline, lista de funcionalidades e rodapé da empresa.
- **Direita (40%):** `bg-white`. Contém logo (somente mobile), formulário e CTA.

Responsivo: o painel esquerdo fica `hidden` em telas menores que `lg`. Em mobile, o painel direito ocupa a tela cheia com o logo no topo.

### Shell autenticada (`AppLayout`)
- Sidebar: `bg-gray-900` com links de navegação `text-white`; item ativo: `bg-gray-800 text-white font-medium`
- Área de conteúdo: `bg-gray-50` ou `bg-white`
- Topbar: `bg-white border-b border-gray-200`

### Textura de fundo
O utilitário CSS `dot-grid` produz um padrão SVG repetido de 24×24 px (pontos de 1 px a 6% de opacidade branca). Use-o exclusivamente em superfícies escuras (`slate-900` e mais escuras) para adicionar profundidade sem distração.

### Elevação (sombra)

| Nível | Classe | Usar para |
|-------|-------|-----------|
| 0 | — | Tabelas planas, itens de sidebar |
| 1 | `shadow-sm` | Cards, painéis, modais |
| 2 | `shadow-md` | Dropdowns, elementos flutuantes |

---

## 7. Movimento

- Use `transition-colors` (150 ms) em todos os elementos interativos para mudanças de cor no hover/focus
- Use `animate-spin` exclusivamente para spinners de carregamento (ex.: botão em estado de processamento)
- Evite animações de layout ou efeitos disparados por scroll — esta é uma UI densa de dados, não uma página de marketing

---

## 8. Voz e Tom

### Princípios
1. **Breve e direto.** Engenheiros leem dashboards, não copywriting. Todo rótulo, heading e mensagem deve ser tão curto quanto possível, sem perder clareza.
2. **Voz ativa.** "Entrar" e não "Fazer login". "Terminar servidor" e não "Terminação de servidor".
3. **Honesto sobre o estado.** Nunca diga "Concluído!" quando uma operação está na fila. Use `queued`, `running`, `succeeded`, `failed`.

### Vocabulário

| Prefira | Evite |
|---------|-------|
| Entrar / Sign in | Login (substantivo usado como verbo) |
| Engenheiro | Usuário, usuário final |
| VPS | Servidor (exceto quando o contexto de domínio exige) |
| Terminar | Deletar (para ciclo de vida de VPS) |
| Conceder acesso | Adicionar usuário |
| Revogar acesso | Remover usuário |
| Log de auditoria | Log de atividades, histórico |

### Mensagens de erro
- Uma frase, caixa da frase, sem ponto final em erros inline de campo
- Para erros genéricos de autenticação: "These credentials do not match." (com ponto — frase completa)
- Nunca expor exceções internas ou stack traces na UI

### Identificadores monospace
Renderize todos os hostnames, endereços IP, UUIDs e IDs de VPS em `font-mono`. Exemplo:
```
192.168.1.1    → <code className="font-mono">192.168.1.1</code>
vps-abc-123    → <code className="font-mono">vps-abc-123</code>
```

---

## 9. Faça / Não Faça

### Cores
| Faça | Não faça |
|------|----------|
| Use `brand-600` no único CTA primário da tela | Usar cor de marca em todos os botões |
| Use cores semânticas (verde/vermelho/amarelo) para status | Usar roxo de marca para indicar status |
| Use `slate-*` para superfícies escuras | Misturar `slate` e `zinc` ou `gray` na mesma superfície escura |

### Tipografia
| Faça | Não faça |
|------|----------|
| Use `font-mono` para identificadores técnicos | Usar `font-mono` para texto corrido |
| Use `tracking-tight` em headings ≥ 24 px | Adicionar espaçamento extra em texto pequeno |
| Mantenha headings em caixa da frase | Usar CAIXA ALTA em headings |

### Componentes
| Faça | Não faça |
|------|----------|
| Use `variant="destructive"` no botão que confirma a exclusão | Usar `destructive` em botão que navega para uma confirmação de exclusão |
| Mostre um spinner dentro do botão durante ações assíncronas | Desabilitar o formulário e mostrar um overlay de carregamento separado |
| Use `Badge variant="success"` para VPS em execução | Usar `<span>` colorido diretamente |
