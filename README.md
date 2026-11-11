# Clima — Sistema de Monitoramento Climático Municipal

Sistema completo de monitoramento meteorológico pra prefeituras: estação
automática, pluviômetro manual, rede de pluviômetros cidadãos, previsão do
tempo, alertas, notícias e um algoritmo de "nowcasting" (previsão de chuva de
curtíssimo prazo) que se recalibra sozinho com base na precisão real.

Criado originalmente pela Prefeitura da Estância Turística de Pereira Barreto
(SP), em parceria com alunos da Univesp e o SAAE, e liberado aqui pra qualquer outra
prefeitura adaptar e usar.

**Veja funcionando:** [clima.pereirabarreto.sp.gov.br](https://clima.pereirabarreto.sp.gov.br) — site oficial da Prefeitura de Pereira Barreto.

## Funcionalidades

- **Painel público**: temperatura, umidade, pressão, vento, chuva, radiação
  solar, UV e evapotranspiração em tempo real, com gráficos e históricos de
  24h/7 dias/mês/ano.
- **Previsão do tempo**: integrada com a API do INMET (5 dias).
- **Nowcasting auto-calibrável**: estima a chance de chuva na próxima hora a
  partir de ponto de orvalho, pressão e vento — e ajusta os próprios critérios
  sozinho, toda semana, com base em quantas previsões realmente se confirmaram.
- **Pluviômetro Cidadão**: moradores e empresas cadastram seu próprio
  pluviômetro (com validação de CEP), passam por moderação, e lançam leituras
  de chuva manualmente — cada estação aparece publicamente só com um código
  (ex: `001-CENTRO`), nunca com nome ou endereço.
- **Captura de dados da estação de duas formas, trocáveis pelo admin sem
  mexer em código**:
  1. Intercepta o upload que a própria estação já faz.
  2. **API oficial do Weather Underground**: se a sua estação já sobe dados
     pra lá, dá pra puxar direto por uma API legítima, sem interceptar nada.
- **Painel administrativo**: dashboard com estatísticas, gestão de usuários
  (com 2FA via Google Authenticator), moderação do Pluviômetro Cidadão,
  correção manual de leituras com problema (ex: sensor sujo/entupido),
  gestão de alertas e notícias, recuperação de senha por e-mail.
- **Cache inteligente** pra manter o site rápido mesmo com tráfego e consultas
  pesadas no histórico.

## Stack

- PHP 8.1+ (sem framework — MVC simples, próprio)
- MySQL/MariaDB
- Apache (ou IIS, com ajustes) com `mod_rewrite`
- Composer
- Bootstrap 5 + JS puro no front

## Estrutura do projeto

```
app/
  Controllers/   Um controller por área (Inicio, Chuva, Previsao, Admin, Cidadao, Api...)
  Models/        Acesso a dados (uma classe por tabela/domínio)
  Services/      Integrações e lógica isolada (Nowcasting, Wunderground, TOTP, E-mail, CEP...)
  Views/         Templates PHP puro, organizados por página
config/
  config.example.php   Template — copie pra config.php e preencha
  config.php           Configuração real (NUNCA commitar — já está no .gitignore)
core/
  Router.php           Roteador simples baseado em array de rotas
  Controller.php       Classe base dos controllers
  Cache.php            Cache em arquivo
cron/
  previsao-inmet.php       Busca a previsão do tempo no INMET
  receber-estacao.php      Recebe dados da estação interceptando o upload que ela já faz
  sensor-wifi-local.php    Lê um sensor WiFi local de temp/umidade (opcional)
  chuva-manual-padrao.php  Preenche 0mm/não-choveu se ninguém lançar a leitura manual do dia
database/
  schema.sql     Estrutura completa do banco (gerada a partir de produção, só estrutura)
public/
  index.php      Front controller — único ponto de entrada do site
```

## Instalação

### 1. Requisitos

- PHP >= 8.1 com extensões `mysqli` e `curl`
- MySQL/MariaDB
- Composer
- Servidor web com suporte a `mod_rewrite` (Apache) apontando o **DocumentRoot
  pra pasta `public/`**, não pra raiz do projeto

### 2. Clonar e instalar dependências

```bash
git clone https://github.com/rafaelbassora/clima.git
cd clima
composer install
```

### 3. Banco de dados

Crie um banco MySQL e importe a estrutura:

```bash
mysql -u seu_usuario -p seu_banco < database/schema.sql
mysql -u seu_usuario -p seu_banco < database/seed.sql
```

> Algumas tabelas do dump (`anos_consulta`, `opcoes_pergunta`, `perguntas`,
> `chuva_old`, `local`) são resquícios de uma funcionalidade legada e não são
> usadas pelo sistema atual — pode ignorá-las ou removê-las se quiser um banco
> mais enxuto.

> O `seed.sql` é obrigatório, não opcional: sem ele, as telas `/admin/estacao`
> e a recalibração do nowcasting não têm em qual linha salvar as configurações
> e as alterações somem silenciosamente.

### 4. Configuração

```bash
cp config/config.example.php config/config.php
```

Edite `config/config.php` e preencha pelo menos:

| Chave | O que é |
|---|---|
| `db` | Credenciais do MySQL |
| `cidade.nome` / `cidade.uf` | Nome e UF da sua cidade (valida o CEP do Pluviômetro Cidadão) |
| `cidade.ibge` | Código IBGE da cidade (7 dígitos) — necessário pra previsão do INMET. Consulte em [ibge.gov.br/explica/codigos-dos-municipios.php](https://www.ibge.gov.br/explica/codigos-dos-municipios.php) |
| `app.nome_sistema` / `app.nome_curto` / `app.titulo` / `app.dominio` | Identidade do seu sistema |
| `branding.*` | Logo da prefeitura, cores, créditos institucionais |
| `parceiros` | Lista de logos de instituições parceiras exibidas no rodapé (pode deixar `[]`) |
| `mail.*` | Servidor SMTP pra recuperação de senha (admin e Pluviômetro Cidadão) |
| `admin.turnstile_secret` | Secret do [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) (anti-bot no login/cadastro) |
| `cron_token` | Token aleatório seu, usado pra autenticar as chamadas de cron nos endpoints `/api/*` |
| `ga_id` | ID do Google Analytics (opcional) |

### 5. Servidor web

Aponte o **DocumentRoot pra `public/`** (nunca pra raiz do projeto — os
outros diretórios não devem ficar acessíveis publicamente pela web).

Exemplo de vhost Apache:

```apache
<VirtualHost *:443>
    ServerName clima.suacidade.sp.gov.br
    DocumentRoot "/caminho/para/clima/public"
    SSLEngine on
    SSLCertificateFile "..."
    SSLCertificateKeyFile "..."
    <Directory "/caminho/para/clima/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 6. Tarefas agendadas (cron)

O sistema depende de algumas tarefas rodando periodicamente. Dois grupos:

**A. Chamadas HTTP aos endpoints `/api/*`** (rodam de qualquer máquina com
acesso ao domínio, usando o `cron_token` configurado):

```bash
# A cada minuto — detecta chuva repentina e corrige o nowcasting na hora
* * * * * curl -s -o /dev/null "https://SEU_DOMINIO/api/circuit-breaker?local=1&token=SEU_CRON_TOKEN"

# A cada 5 minutos — calcula a tendência de chuva (nowcasting)
*/5 * * * * curl -s -o /dev/null "https://SEU_DOMINIO/api/calculate-nowcast?local=1&minutos=60&token=SEU_CRON_TOKEN"

# A cada 10 minutos — pré-aquece o cache das consultas mais pesadas
*/10 * * * * curl -s -o /dev/null "https://SEU_DOMINIO/api/preload-cache?token=SEU_CRON_TOKEN"

# 1x por hora — valida os acertos/erros do nowcasting e recalibra 1x/semana
0 * * * * curl -s -o /dev/null "https://SEU_DOMINIO/api/validate-nowcasting?token=SEU_CRON_TOKEN"
```

**B. Scripts PHP locais no servidor** (pasta `cron/`), rodando diretamente no
servidor onde o projeto está instalado — recomendado agendar `previsao-inmet.php`
a cada minuto (ele mesmo decide os horários certos de buscar) e
`chuva-manual-padrao.php` a cada minuto também (só age às 7h):

```
php /caminho/para/clima/cron/previsao-inmet.php
php /caminho/para/clima/cron/chuva-manual-padrao.php
```

`sensor-wifi-local.php` e `receber-estacao.php` são **opcionais** — só usados
se sua prefeitura tiver, respectivamente, um sensor WiFi local próprio ou for
usar a captura local de dados interceptando o upload da estação (ver seção abaixo).

### 7. Captura de dados da estação meteorológica

Veja `/admin/estacao` depois de instalar — dá pra escolher entre:

- **Local**: configure o DNS da sua rede pra resolver o domínio
  que a estação já usa pra subir dados (ex: `api.algumaapidetempoXXXXXX.com`) pro IP do
  seu servidor, e aponte esse domínio pro script `cron/receber-estacao.php`.
- **API Wunderground**: se a estação já sobe dados pro Weather Underground,
  basta cadastrar a API Key (gratuita pra donos de estação — veja
  [wunderground.com/pws-api](https://www.wunderground.com/pws-api)) e o
  Station ID na tela do admin.

### 8. Primeiro usuário admin

Ainda não existe uma tela de "criar primeiro admin" — insira diretamente no
banco (senha com hash bcrypt):

```sql
INSERT INTO usuarios (name, email, password, is_admin, created_at, updated_at)
VALUES ('Seu Nome', 'seu@email.com', '$2y$12$...', 1, NOW(), NOW());
```

Gere o hash com: `php -r "echo password_hash('sua_senha', PASSWORD_BCRYPT);"`

## Customização visual

Tudo em `config.php` → `branding`:

```php
'branding' => [
    'favicon'         => '/img/logo.png', // ícone / preview de compartilhamento
    'logo_prefeitura' => '/img/logo.png', // logo exibido no rodapé
    'cor_primaria'    => '#0d6efd',
    'cor_destaque'    => '#ff6b35',
    'footer_creditos' => 'Texto livre do rodapé...',
    'footer_aviso'    => 'Aviso legal/institucional...',
],
'parceiros' => [
    ['nome' => 'Nome do Parceiro', 'logo' => '/img/logo-parceiro.png'],
],
```

Troque os arquivos de logo em `public/img/`.

## Segurança

- Nunca commite `config/config.php` (já está no `.gitignore`) — ele tem
  credenciais reais. Use `config.example.php` como referência.
- O `cron_token` protege os endpoints `/api/*` de chamadas não autorizadas —
  gere um valor aleatório forte, não reaproveite o do exemplo.
- 2FA (Google Authenticator) disponível pra contas de admin em `/admin/2fa`.
- Recuperação de senha por e-mail com token de uso único (1h de validade).

## Aviso sobre os dados

Os dados meteorológicos, previsões e alertas exibidos pelo sistema têm caráter
estritamente informativo e experimental: são gerados por estações automatizadas
e modelos simples, sujeitos a falhas, atrasos e imprecisões. Não substituem os
alertas oficiais da Defesa Civil nem os boletins do CPTEC/INPE ou INMET, e não
devem ser a única base para decisões pessoais, agrícolas, comerciais ou de
segurança. Os autores e as instituições envolvidas não se responsabilizam por
decisões tomadas com base nessas informações.

## Licença

Domínio público ([Unlicense](LICENSE)) — projeto desenvolvido com recursos
públicos. Qualquer um pode baixar, modificar, redistribuir e usar pra
qualquer finalidade, comercial ou não, sem precisar pedir permissão nem dar
crédito. Fornecido como está, sem garantias, responsabilidades ou suporte.

## Créditos

TCC e Projeto integrador Univesp, em colaboração com o SAAE e a Prefeitura
da Estância Turística de Pereira Barreto — 2024–2026.
