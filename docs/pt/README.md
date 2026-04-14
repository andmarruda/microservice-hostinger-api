# Microserviço de Gerenciamento de Infraestrutura Hostinger — Documentação em Português

## Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [Requisitos](#requisitos)
4. [Instalação e Configuração](#instalação-e-configuração)
5. [Variáveis de Ambiente](#variáveis-de-ambiente)
6. [Autenticação](#autenticação)
7. [Papéis e Permissões](#papéis-e-permissões)
8. [Referência da API](#referência-da-api)
9. [Interface Web](#interface-web)
10. [Jobs Agendados](#jobs-agendados)
11. [Governança e Conformidade](#governança-e-conformidade)
12. [Observabilidade](#observabilidade)
13. [Testes](#testes)
14. [Registros de Decisão de Arquitetura](#registros-de-decisão-de-arquitetura)

---

## Visão Geral

Este microserviço funciona como uma **camada de gerenciamento com controle de acesso** sobre a API da Hostinger. Em vez de fornecer credenciais da API diretamente a cada membro da equipe, este serviço:

- Aplica controle de acesso baseado em papéis (RBAC) sobre todos os recursos Hostinger
- Armazena em cache os dados de leitura para evitar consumo excessivo de cota de API
- Registra cada mutação em um log de auditoria imutável
- Automatiza tarefas de governança: revisões de acesso, detecção de drift, expiração de concessões obsoletas
- Expõe uma interface web completa em React/Inertia.js para operadores humanos
- Expõe uma API REST JSON versionada para consumidores programáticos

**Caso de uso principal:** Uma empresa opera múltiplas instâncias VPS, domínios e zonas DNS sob uma única conta Hostinger. Vários times precisam de acesso — cada um com diferentes níveis de autoridade. Este serviço medeia todo o acesso por meio de um único gateway controlado.

---

## Arquitetura

### Laravel 12 Modular

A aplicação é dividida em **11 módulos**, cada um autocontido em `app/Modules/`:

| Módulo | Responsabilidade |
|--------|-----------------|
| `AuthModule` | Registro de usuário (por convite), login, logout |
| `VpsModule` | Lista de VPS, detalhes, firewall, chaves SSH, snapshots, ações de ciclo de vida |
| `HostingerProxyModule` | Camada de cache somente leitura sobre as respostas da API Hostinger |
| `GovernanceModule` | Revisões de acesso, exportação de log de auditoria, aprovações de permissão |
| `PermissionModule` | Atribuição de papéis/permissões, integração com Spatie |
| `PolicyModule` | Aplicação de políticas (verificações pré-ação) |
| `ObservabilityModule` | Log estruturado, detecção de requisições lentas, InstrumentedCache |
| `SecurityResourceModule` | Regras de firewall, chaves SSH, snapshots por VPS |
| `OpsModule` | Health checks internos, rastreamento de cota, estatísticas de cache, contagens de DB |
| `DriftModule` | Detecção de drift (estado Hostinger vs. registros locais) |
| `FrontendModule` | Controladores de página Inertia.js + rotas web |

### Fluxo de requisição

```
Navegador / Cliente API
         │
         ▼
   Roteador Laravel
         │
   ┌─────┴─────┐
   │  Web      │  (autenticação por sessão via middleware 'auth')
   │  Routes   │──► Controladores FrontendModule ──► Use Cases ──► HostingerProxy / DB
   └───────────┘
   ┌───────────┐
   │  API      │  (autenticação por token via Sanctum 'auth:sanctum')
   │  Routes   │──► Controladores de Módulo ──► Use Cases ──► HostingerProxy / DB
   └───────────┘
```

### Padrão Use Case

Toda operação é encapsulada em uma classe Use Case que retorna um **objeto Result** tipado:

```php
$result = $useCase->execute($input);

if ($result->success()) {
    return Inertia::render('Page', $result->data());
}
if ($result->forbidden()) {
    abort(403);
}
```

Estados de resultado: `success`, `forbidden`, `policyDenied`, `notFound`, `conflict`, `quotaExceeded`, `rateLimited`

---

## Requisitos

- PHP 8.3+
- Composer 2.x
- Node.js 20+ / npm 10+
- SQLite (padrão, sem configuração) **ou** MySQL/PostgreSQL
- Um [token de API Hostinger](https://developers.hostinger.com) válido

---

## Instalação e Configuração

### 1. Clonar e instalar dependências

```bash
git clone https://github.com/andmarruda/microservice-hostinger-api.git
cd microservice-hostinger-api

composer install
npm install
```

### 2. Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite o `.env` — no mínimo defina:

```env
HOSTINGER_API_TOKEN=seu_token_aqui
```

### 3. Executar migrations e seeds

```bash
php artisan migrate
php artisan db:seed          # Cria um usuário root e papéis de exemplo
```

### 4. Compilar assets do frontend

```bash
npm run build
```

### 5. Iniciar o servidor de desenvolvimento

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite dev server (hot reload)
npm run dev
```

Abra `http://localhost:8000/login` no seu navegador.

### 6. Iniciar o worker de filas (para jobs e operações assíncronas)

```bash
php artisan queue:work
```

### 7. Iniciar o agendador (opcional, para tarefas automatizadas)

```bash
php artisan schedule:work
```

---

## Variáveis de Ambiente

### Aplicação

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `APP_ENV` | `local` | Nome do ambiente (`local`, `production`) |
| `APP_DEBUG` | `true` | Habilita modo debug (desativar em produção) |
| `APP_URL` | `http://localhost` | URL pública da aplicação |
| `APP_KEY` | — | Gerado por `php artisan key:generate` |

### Banco de Dados

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `DB_CONNECTION` | `sqlite` | Driver de banco (`sqlite`, `mysql`, `pgsql`) |
| `DB_HOST` | `127.0.0.1` | Host do banco (MySQL/PostgreSQL) |
| `DB_PORT` | `3306` | Porta do banco |
| `DB_DATABASE` | — | Nome do banco ou caminho do arquivo SQLite |
| `DB_USERNAME` | — | Usuário do banco |
| `DB_PASSWORD` | — | Senha do banco |

### Fila e Cache

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `QUEUE_CONNECTION` | `database` | Driver de fila (`database`, `redis`, `sync`) |
| `CACHE_STORE` | `database` | Driver de cache (`database`, `redis`, `memcached`) |
| `SESSION_DRIVER` | `database` | Driver de sessão |
| `SESSION_LIFETIME` | `120` | Tempo de vida da sessão em minutos |

### API Hostinger

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `HOSTINGER_API_BASE_URL` | `https://developers.hostinger.com` | Endpoint da API Hostinger |
| `HOSTINGER_API_TOKEN` | — | **Obrigatório.** Seu token de API Hostinger |
| `HOSTINGER_API_TIMEOUT_SECONDS` | `10` | Timeout das requisições HTTP |

### TTLs de Cache Hostinger

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `HOSTINGER_CACHE_TTL_VPS_LIST` | `86400` | Tempo de vida do cache da lista de VPS (segundos) |
| `HOSTINGER_CACHE_TTL_OS_TEMPLATES` | `86400` | Tempo de vida do cache de templates de SO |
| `HOSTINGER_CACHE_TTL_DATACENTERS` | `86400` | Tempo de vida do cache de datacenters |
| `HOSTINGER_CACHE_TTL_DOMAIN_AVAILABILITY` | `3600` | Tempo de vida do cache de disponibilidade de domínio |

### Controles de Cota da API

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `HOSTINGER_API_QUOTA_WARN_AT` | `800` | Registra aviso quando chamadas diárias excedem este valor |
| `HOSTINGER_API_QUOTA_HARD_LIMIT` | — | Bloqueia requisições com 503 quando este limite é atingido |

### Observabilidade

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `LOG_CHANNEL` | `stack` | Canal de log (`stack`, `json` para log estruturado) |
| `SLOW_REQUEST_THRESHOLD_MS` | `2000` | Limite em milissegundos para avisos de requisição lenta |

### Políticas de Retenção

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `AUDIT_LOG_RETENTION_DAYS` | `365` | Dias para manter logs de auditoria de infraestrutura |
| `AUTH_LOG_RETENTION_DAYS` | `365` | Dias para manter logs de auditoria de autenticação |
| `DRIFT_REPORT_RETENTION_DAYS` | `90` | Dias para manter relatórios de drift arquivados |
| `ACCESS_REVIEW_RETENTION_DAYS` | `730` | Dias para manter revisões de acesso concluídas |
| `FAILED_JOB_RETENTION_DAYS` | `30` | Dias para manter jobs de fila com falha |

### Autenticação de API

| Variável | Padrão | Descrição |
|----------|--------|-----------|
| `SANCTUM_TOKEN_EXPIRY_MINUTES` | — | Tempo de vida do token (deixe vazio para tokens sem expiração) |

---

## Autenticação

### Interface web (sessão)

A interface web usa autenticação por sessão do Laravel:

1. Acesse `GET /login`
2. Envie email + senha pelo formulário
3. Cookie de sessão é emitido após autenticação
4. `POST /logout` encerra a sessão

### API (Bearer token via Sanctum)

Todas as rotas de API exigem um token `Bearer` no cabeçalho `Authorization`:

```http
Authorization: Bearer <seu-token-sanctum>
```

Tokens são criados através da API de autenticação:

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "usuario@exemplo.com",
  "password": "senha"
}
```

Resposta:
```json
{
  "token": "1|AbCdEfGhIjKlMnOp...",
  "user": { "id": 1, "name": "Alice", "email": "usuario@exemplo.com" }
}
```

### Registro por convite

Novos usuários não podem se auto-registrar. Um usuário **root** ou **manager** existente deve convidá-los:

```http
POST /api/v1/auth/invite
Authorization: Bearer <token-root>

{
  "email": "novousuario@exemplo.com",
  "role": "operator"
}
```

O usuário convidado recebe um link: `GET /register/{token}` (web) ou:

```http
POST /api/v1/auth/register
{
  "token": "<token-de-convite>",
  "name": "Novo Usuário",
  "password": "senha-segura",
  "password_confirmation": "senha-segura"
}
```

---

## Papéis e Permissões

### Papéis incorporados

| Papel | Descrição |
|-------|-----------|
| `root` | Acesso total a tudo, incluindo páginas Ops |
| `manager` | Pode gerenciar usuários, revisar acessos, aprovar permissões |
| `operator` | Pode executar ações de ciclo de vida de VPS nos VPS atribuídos |
| `viewer` | Acesso somente leitura aos recursos atribuídos |

### Permissões

As permissões seguem o padrão `recurso.ação`:

| Permissão | Descrição |
|-----------|-----------|
| `vps.read` | Listar e visualizar detalhes de VPS |
| `vps.write` | Iniciar, parar, reiniciar VPS |
| `vps.firewall` | Gerenciar regras de firewall |
| `vps.ssh-keys` | Gerenciar chaves SSH |
| `vps.snapshots` | Gerenciar snapshots |
| `domains.read` | Visualizar portfólio de domínios |
| `dns.read` | Visualizar zonas DNS |
| `dns.write` | Modificar registros DNS |
| `billing.read` | Visualizar informações de faturamento |
| `governance.reviews` | Gerenciar revisões de acesso |
| `governance.audit` | Exportar logs de auditoria |
| `governance.approvals` | Aprovar solicitações de permissão |

### Concessões com escopo de recurso

O acesso a VPS individuais é controlado via registros `VpsAccessGrant`. Um usuário com `vps.read` só pode ver as instâncias VPS para as quais recebeu acesso explicitamente.

---

## Referência da API

URL base: `/api/v1`

Todos os endpoints retornam JSON. Respostas de erro seguem o formato:
```json
{
  "message": "Mensagem de erro descritiva",
  "errors": { "campo": ["Erro de validação"] }
}
```

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/auth/login` | Emite um token Sanctum |
| POST | `/auth/logout` | Revoga o token atual |
| POST | `/auth/invite` | Envia um convite (manager+) |
| POST | `/auth/register` | Aceita um convite e cria conta |

### VPS

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/vps` | Lista instâncias VPS acessíveis ao usuário atual |
| GET | `/vps/{id}` | Obtém detalhes do VPS |
| POST | `/vps/{id}/start` | Inicia um VPS |
| POST | `/vps/{id}/stop` | Para um VPS |
| POST | `/vps/{id}/reboot` | Reinicia um VPS |
| GET | `/vps/{id}/firewall` | Lista regras de firewall |
| GET | `/vps/{id}/ssh-keys` | Lista chaves SSH |
| GET | `/vps/{id}/snapshots` | Lista snapshots |
| GET | `/vps/{id}/metrics` | Obtém métricas de recursos atuais |
| GET | `/vps/{id}/actions` | Obtém histórico de ações |
| GET | `/vps/{id}/backups` | Lista backups |

### Domínios e DNS

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/domains` | Lista portfólio de domínios |
| GET | `/domains/check?domain=exemplo.com` | Verifica disponibilidade de domínio |
| GET | `/domains/{domain}/forwarding` | Obtém regras de redirecionamento |
| GET | `/domains/{domain}/whois` | Obtém dados WHOIS |
| GET | `/dns/{domain}` | Obtém registros da zona DNS |
| GET | `/dns/{domain}/snapshots` | Lista snapshots de DNS |

### Faturamento

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/billing/catalog` | Lista planos VPS disponíveis |
| GET | `/billing/subscriptions` | Lista assinaturas ativas |
| GET | `/billing/payment-methods` | Lista métodos de pagamento salvos |

### Governança

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/governance/reviews` | Lista revisões de acesso |
| POST | `/governance/reviews` | Cria uma nova revisão de acesso |
| GET | `/governance/reviews/{id}` | Obtém detalhes da revisão |
| POST | `/governance/reviews/{id}/items/{itemId}` | Decide sobre um item da revisão (aprovar/revogar) |
| GET | `/governance/audit` | Consulta logs de auditoria |
| GET | `/governance/audit/export?format=csv` | Baixa CSV do log de auditoria |
| GET | `/governance/approvals` | Lista solicitações de aprovação de permissão |
| POST | `/governance/approvals/{id}/approve` | Aprova uma solicitação de permissão |

### Ops (somente root)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/ops/health` | Status de saúde do serviço |
| GET | `/ops/quota` | Uso de cota da API Hostinger |
| GET | `/ops/cache` | Estatísticas de acertos/erros de cache |
| GET | `/ops/database` | Contagens de linhas das tabelas do banco |

---

## Interface Web

A interface web é construída com **React 19 + Inertia.js v2 + Tailwind CSS 4**.

### Páginas

| URL | Página | Descrição |
|-----|--------|-----------|
| `/login` | Auth / Login | Formulário de email + senha |
| `/register/{token}` | Auth / Registro | Formulário de aceite de convite |
| `/` | Dashboard | Cards de resumo: contagem de VPS, revisões abertas, relatórios de drift |
| `/vps` | Lista de VPS | Tabela de todos os VPS acessíveis com Start/Stop/Reboot |
| `/vps/{id}` | Detalhes do VPS | Abas: Detalhes, Métricas, Ações, Backups |
| `/vps/{id}/firewall` | Firewall do VPS | Tabela de regras de firewall |
| `/vps/{id}/ssh-keys` | Chaves SSH do VPS | Lista de chaves SSH |
| `/vps/{id}/snapshots` | Snapshots do VPS | Lista de snapshots |
| `/domains` | Portfólio de Domínios | Lista de domínios com status |
| `/domains/check` | Disponibilidade de Domínio | Verificador de disponibilidade de domínio |
| `/dns/{domain}` | Zona DNS | Registros DNS de um domínio |
| `/billing` | Faturamento | Abas: Assinaturas, Catálogo, Métodos de Pagamento |
| `/governance/reviews` | Revisões de Acesso | Lista de revisões com botão de criação |
| `/governance/reviews/{id}` | Detalhe da Revisão | Itens com botões Aprovar / Revogar |
| `/governance/audit` | Log de Auditoria | Log de auditoria com filtros e exportação CSV |
| `/governance/approvals` | Aprovações | Solicitações de aprovação de permissão pendentes |
| `/ops/health` | Ops Health | Saúde do serviço (somente root) |
| `/ops/quota` | Ops Quota | Gauges de cota de API (somente root) |
| `/ops/cache` | Ops Cache | Estatísticas de cache (somente root) |
| `/ops/database` | Ops Database | Contagens de linhas e retenção do DB (somente root) |

### Navegação

A barra lateral do **AppLayout** agrupa a navegação da seguinte forma:

- **VPS** → Lista de VPS
- **Domínios** → Portfólio / Disponibilidade
- **Faturamento** → Índice de faturamento
- **Governança** → Revisões de Acesso / Log de Auditoria / Aprovações
- **Ops** → Health / Quota / Cache / Database *(visível apenas para root)*

A barra superior exibe o nome do usuário logado e um botão **Sair**.

Mensagens flash (sucesso / erro) aparecem automaticamente como toast descartável.

---

## Jobs Agendados

Sete jobs rodam em agendamento via `php artisan schedule:work` (ou cron):

| Job | Agendamento | Descrição |
|-----|-------------|-----------|
| `ExpireInvitations` | A cada hora | Marca convites pendentes como expirados após o prazo |
| `ExpireAccessGrants` | A cada hora | Remove concessões de acesso VPS após a data de expiração |
| `WarmHostingerCache` | Diário 03:00 | Pré-popula o cache de leitura Hostinger |
| `PruneAuditLogs` | Diário 02:00 | Exclui logs de auditoria mais antigos que a janela de retenção |
| `FlagStaleAccessGrants` | Diário 04:00 | Sinaliza concessões cujo VPS não existe mais na Hostinger |
| `RunDriftScan` | Diário 04:30 | Detecta drift entre o estado Hostinger e os registros locais |
| `ArchiveOldDriftReports` | Diário 03:30 | Arquiva relatórios de drift resolvidos/descartados |

Todos os jobs usam `withoutOverlapping(10)` para evitar execuções concorrentes.

---

## Governança e Conformidade

### Revisões de Acesso

Uma **Revisão de Acesso** é uma auditoria periódica de quem tem acesso a quais VPS. Os revisores examinam cada concessão e decidem `aprovar` (manter) ou `revogar` (remover).

Ciclo de vida: `pending` → `completed` ou `cancelled`

### Detecção de Drift

O job `RunDriftScan` compara a lista de instâncias VPS retornada pela API Hostinger com os registros `VpsAccessGrant` locais. Qualquer discrepância (VPS excluído na Hostinger mas ainda referenciado localmente) cria um **DriftReport** com status `open`. Operadores podem resolver ou descartar relatórios pelo dashboard.

### Log de Auditoria

Toda operação que muda estado cria uma entrada `InfraAuditLog` registrando:
- `actor_id` / `actor_email` — quem realizou a ação
- `action` — ex.: `vps.start`, `dns.write`
- `resource_type` / `resource_id` — o que foi afetado
- `outcome` — `success` ou `failure`
- `performed_at` — timestamp

Logs de auditoria são retidos por `AUDIT_LOG_RETENTION_DAYS` dias (padrão: 365).

### Aprovações de Permissão

Usuários podem solicitar permissões elevadas (ex.: `vps.write`). A solicitação aparece na fila de Aprovações em Governança. Um manager ou usuário root revisa e aprova. O solicitante não pode aprovar sua própria solicitação.

---

## Observabilidade

### Log estruturado

Defina `LOG_CHANNEL=json` em produção para emitir linhas de log JSON estruturado compatíveis com Datadog, CloudWatch e agregadores similares.

### Detecção de requisições lentas

Qualquer requisição HTTP que exceda `SLOW_REQUEST_THRESHOLD_MS` (padrão: 2000ms) é registrada em nível `warning` com contexto (rota, duração, usuário).

### InstrumentedCache

Todas as respostas da API Hostinger são armazenadas em cache via `InstrumentedCache::remember()`, que rastreia contagens de acertos e erros por chave de cache. A página `/ops/cache` exibe essas estatísticas com percentuais de taxa de acerto.

### Rastreamento de cota

`HostingerQuotaTracker` conta chamadas de saída para a API Hostinger. Quando as chamadas excedem `HOSTINGER_API_QUOTA_WARN_AT`, um aviso é registrado. Quando `HOSTINGER_API_QUOTA_HARD_LIMIT` é definido e atingido, a API retorna HTTP 503 para proteger a conta Hostinger.

---

## Testes

### Testes PHP (PHPUnit)

```bash
php artisan test          # Executa todos os 194 testes
php artisan test --filter VpsModuleTest
```

### Testes JavaScript (Vitest)

```bash
npm run test              # Executa todos os testes uma vez
npm run test:watch        # Modo watch
npm run test:coverage     # Executa com relatório de cobertura
```

Limites de cobertura aplicados:

| Métrica | Limite |
|---------|--------|
| Statements | 90% |
| Lines | 90% |
| Functions | 90% |
| Branches | 85% |

Cobertura atual: **~95% statements / ~91% branches** com 306 testes em 31 arquivos de teste.

### Estrutura de testes

```
resources/js/
├── test/
│   ├── setup.ts                      # Setup global (jest-dom, resets de mock)
│   └── mocks/
│       └── inertia.tsx               # Mock global do Inertia.js
├── components/ui/__tests__/          # Testes unitários de componentes UI
├── hooks/__tests__/                  # Testes unitários de hooks
├── layouts/__tests__/                # Testes de layout
└── pages/
    ├── Auth/__tests__/
    ├── Billing/__tests__/
    ├── Dns/__tests__/
    ├── Domains/__tests__/
    ├── Governance/
    │   ├── AccessReviews/__tests__/
    │   ├── Approvals/__tests__/
    │   └── __tests__/
    ├── Ops/__tests__/
    └── Vps/__tests__/
```

---

## Registros de Decisão de Arquitetura

Todas as principais decisões de arquitetura estão documentadas em `docs/adrs/`:

| ADR | Título |
|-----|--------|
| ADR-001 | Registro de Usuário por Convite |
| ADR-002 | Operações de Escrita do Ciclo de Vida VPS via Proxy Controlado |
| ADR-003 | Gerenciamento de Regras de Firewall, Chaves SSH e Snapshots |
| ADR-004 | Cobertura de Auditoria Expandida para Mutações de Infraestrutura |
| ADR-005 | Sistema de Permissões Baseado em Papéis com Spatie Laravel Permissions |
| ADR-006 | Proxy de Recurso Hostinger Somente Leitura |
| ADR-007 | Autenticação JWT e por Sessão |
| ADR-008 | Versionamento de API e Normalização de Resposta |
| ADR-009 | Estratégia de Rate Limiting |
| ADR-010 | Aplicação Orientada a Políticas |
| ADR-011 | Tarefas Agendadas e Automatizadas |
| ADR-012 | Detecção de Drift |
| ADR-013 | Observabilidade e Log Estruturado |
| ADR-014 | Ferramentas de Conformidade e Governança |
| ADR-015 | Otimização de Desempenho e Controle de Custos |
| ADR-016 | Estratégia de Implementação do Frontend |
