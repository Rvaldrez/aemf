# AEMF – Guia de Implantação na Hostinger (Passo a Passo)

> **Resposta rápida:** Sim, você pode apagar todos os arquivos do `public_html` e substituir pelos arquivos deste repositório. Siga os passos abaixo para uma implantação correta na Hostinger.

---

## Índice

1. [Verificar a Versão do PHP na Hostinger](#1-verificar-a-versão-do-php-na-hostinger)
2. [Baixar os Arquivos do Repositório](#2-baixar-os-arquivos-do-repositório)
3. [Criar o Banco de Dados MySQL na Hostinger](#3-criar-o-banco-de-dados-mysql-na-hostinger)
4. [Editar o Arquivo de Configuração](#4-editar-o-arquivo-de-configuração)
5. [Enviar os Arquivos para o Servidor](#5-enviar-os-arquivos-para-o-servidor)
6. [Instalar as Dependências PHP (Composer)](#6-instalar-as-dependências-php-composer)
7. [Criar as Pastas de Upload](#7-criar-as-pastas-de-upload)
8. [Criar as Tabelas do Banco de Dados](#8-criar-as-tabelas-do-banco-de-dados)
9. [Verificar o Sistema](#9-verificar-o-sistema)
10. [Guia de Uso do Sistema](#10-guia-de-uso-do-sistema)
11. [Checklist de Segurança para Produção](#11-checklist-de-segurança-para-produção)

---

## 1. Verificar a Versão do PHP na Hostinger

O sistema requer **PHP 7.4 ou superior** (recomendado PHP 8.1+).

**Como verificar/alterar no hPanel:**

1. Acesse [hpanel.hostinger.com](https://hpanel.hostinger.com) e faça login
2. Clique em **Gerenciar** no seu plano de hospedagem
3. No menu lateral, vá em **Avançado → Configuração do PHP**
4. Selecione a versão **PHP 8.1** (ou 8.2) e clique em **Salvar**
5. Na mesma tela, confirme que as seguintes extensões estão habilitadas:
   - `pdo_mysql` ✓
   - `fileinfo` ✓
   - `mbstring` ✓

> Os planos Hostinger Business, Premium e acima já incluem PHP 8.x e MySQL 8 por padrão.

---

## 2. Baixar os Arquivos do Repositório

### Opção A – Via SSH na Hostinger (mais rápido)

A Hostinger disponibiliza SSH nos planos Business e acima.

**Ativar o acesso SSH:**
1. No hPanel → **Avançado → Acesso SSH**
2. Clique em **Ativar SSH**
3. Anote o **hostname SSH** (ex: `srv123.hostinger.com`) e a **porta** (geralmente `65002`)
4. O **usuário SSH** também é exibido nessa tela (ex: `u999392040`) — use-o no comando abaixo
5. Use a mesma senha do hPanel

**Conectar e baixar:**
```bash
# No seu computador (Terminal/PuTTY)
# O usuário e host exatos estão visíveis em hPanel → Avançado → Acesso SSH
ssh SEU_USUARIO@SEU_HOSTNAME -p 65002

# Já dentro do servidor:
cd ~/public_html

# Remover arquivos antigos (se quiser substituir tudo)
rm -rf *

# Baixar o repositório
git clone https://github.com/Rvaldrez/aemf.git .
```

> **PuTTY (Windows):** Host = `srv123.hostinger.com`, Port = `65002`, Connection type = SSH.

### Opção B – Download Manual + Gerenciador de Arquivos

1. Acesse: `https://github.com/Rvaldrez/aemf`
2. Clique em **Code → Download ZIP**
3. Extraia o ZIP no seu computador
4. Você verá todos os arquivos do projeto prontos para envio

**Arquivos que devem estar presentes:**

```
/
├── index.php                ← Dashboard principal
├── login.php                ← Página de login
├── acesso.php               ← Processamento do login
├── upload_interface.php     ← Interface de importação de arquivos
├── admin_transacoes.php     ← Admin: importação + categorização
├── admin_categorias.php     ← Admin: categorias + padrões
├── classify_manual.php      ← Classificação manual de transações
├── setup_database_fixed.php ← Criação das tabelas (usar uma vez)
├── composer.json
├── composer.lock
├── includes/
│   ├── config.php           ← ⚠️ EDITAR com seus dados
│   └── database.php         ← Classe de conexão (não editar)
├── api/
│   ├── process_documents.php
│   ├── dashboard_api.php
│   ├── admin_api.php
│   ├── test_extrato.php
│   └── process_comprovantes.php
└── vendor/                  ← Dependências PHP (ver Passo 6)
```

---

## 3. Criar o Banco de Dados MySQL na Hostinger

**No hPanel:**

1. No menu lateral, clique em **Bancos de Dados → Bancos de Dados MySQL**
2. Em **Criar um novo banco de dados**, preencha:
   - **Nome do banco de dados:** ex. `aemfpar` (a Hostinger adiciona o prefixo automaticamente, ficando algo como `u999392040_aemfpar`)
   - **Nome de usuário:** ex. `aemfpar`
   - **Senha:** crie uma senha forte e anote
3. Clique em **Criar**
4. Anote as informações exibidas:

| Campo | Valor típico na Hostinger |
|---|---|
| **Host** | `127.0.0.1` ou `localhost` |
| **Nome do banco** | `u999392040_aemfpar` (com prefixo) |
| **Usuário** | `u999392040_aemfpar` (com prefixo) |
| **Senha** | a senha que você criou |
| **Porta** | `3306` |

> O nome completo com prefixo é mostrado na tela após a criação. Copie exatamente.

---

## 4. Editar o Arquivo de Configuração

Antes de enviar os arquivos para o servidor, edite `includes/config.php` com os dados do banco criado no Passo 3.

Abra o arquivo em qualquer editor de texto (Notepad, VS Code, etc.):

```php
<?php
// includes/config.php

define('DB_HOST', '127.0.0.1');                   // ← use 127.0.0.1 na Hostinger
define('DB_NAME', 'u999392040_aemfpar');          // ← nome completo com prefixo
define('DB_USER', 'u999392040_aemfpar');          // ← usuário completo com prefixo
define('DB_PASS', 'sua_senha_forte_aqui');        // ← senha criada no Passo 3
define('DB_CHARSET', 'utf8mb4');

// URL do seu site (sem barra no final)
define('SITE_URL', 'https://seudominio.com.br');

// Caminhos de upload (não alterar)
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('TEMP_PATH',   dirname(__DIR__) . '/temp/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting — manter 0 em produção
error_reporting(0);
ini_set('display_errors', 0);
?>
```

> ⚠️ **Atenção:** Na Hostinger, o host do banco de dados é `127.0.0.1` (não o IP externo). Use os nomes completos com prefixo (`u999392040_...`) que aparecem no hPanel.

---

## 5. Enviar os Arquivos para o Servidor

### Opção A – Gerenciador de Arquivos do hPanel (sem instalar nada)

1. No hPanel → **Arquivos → Gerenciador de Arquivos**
2. Navegue até a pasta `public_html`
3. Se quiser **substituir tudo**: selecione todos os arquivos existentes → **Excluir**
4. Clique em **Fazer Upload** (ícone de seta para cima)
5. Selecione o **ZIP** baixado do repositório e envie
6. Após o upload, clique com o botão direito no ZIP → **Extrair**
7. Extraia diretamente em `public_html/` (sem subpastas extras)
8. Confira que `index.php` está visível dentro do `public_html`

> Se o ZIP criar uma subpasta (ex: `aemf-main/`), mova o conteúdo dela para `public_html/` e depois delete a subpasta vazia.

### Opção B – FTP com FileZilla

1. No hPanel → **Arquivos → Contas FTP**
2. Crie uma conta FTP ou use as credenciais exibidas
3. No FileZilla, configure:
   - **Host:** `ftp.seudominio.com.br`
   - **Usuário:** usuário FTP do hPanel
   - **Senha:** senha FTP
   - **Porta:** `21`
4. No painel direito (servidor), navegue para `public_html/`
5. No painel esquerdo (seu computador), selecione todos os arquivos do projeto
6. Arraste tudo para `public_html/`

**Estrutura final esperada em `public_html/`:**

```
public_html/
├── index.php
├── includes/
│   └── config.php    ← já editado (Passo 4)
├── api/
├── vendor/
├── uploads/          ← criar no Passo 7
└── temp/             ← criar no Passo 7
```

---

## 6. Instalar as Dependências PHP (Composer)

O sistema usa `smalot/pdfparser` para ler PDFs de comprovantes.

### Opção A – Via SSH na Hostinger (recomendado)

```bash
# Conecte via SSH (como mostrado no Passo 2)
cd ~/public_html

# Instalar o Composer (se não estiver disponível)
curl -sS https://getcomposer.org/installer | php

# Instalar as dependências
php composer.phar install --no-dev --optimize-autoloader
```

> Na maioria dos servidores Hostinger Business, o Composer já está disponível globalmente:
> ```bash
> composer install --no-dev --optimize-autoloader
> ```

### Opção B – Upload manual da pasta `vendor/` (sem SSH)

A pasta `vendor/` já está incluída no ZIP do repositório. Basta:

1. Baixar o ZIP do repositório (Passo 2 – Opção B)
2. A pasta `vendor/` já vem junto
3. Enviá-la via Gerenciador de Arquivos ou FTP junto com os demais arquivos

> A pasta `vendor/` **não é excluída** pelo `.gitignore`, então o ZIP do GitHub já a contém.

---

## 7. Criar as Pastas de Upload

### Via Gerenciador de Arquivos do hPanel

1. No hPanel → **Arquivos → Gerenciador de Arquivos**
2. Abra a pasta `public_html`
3. Clique em **Nova pasta** e crie `uploads`
4. Clique em **Nova pasta** e crie `temp`
5. Clique com botão direito em `uploads` → **Permissões** → defina `755`
6. Repita para `temp`

### Via SSH

```bash
cd ~/public_html
mkdir -p uploads temp
chmod 755 uploads temp
```

---

## 8. Criar as Tabelas do Banco de Dados

Este passo cria todas as tabelas e insere as categorias e regras de classificação padrão. **Execute apenas uma vez.**

1. Abra o navegador e acesse:
   ```
   https://seudominio.com.br/setup_database_fixed.php
   ```

2. Você verá as seguintes confirmações:
   ```
   ✓ Tabela categorias criada
   ✓ Tabela transacoes criada
   ✓ Tabela referencias criada
   ✓ Categorias inseridas
   ✓ Referências inseridas

   ✅ Setup concluído com sucesso!
   ```

3. **IMPORTANTE – segurança:** Após ver a mensagem de sucesso, **delete** o arquivo `setup_database_fixed.php`:
   - **Via Gerenciador de Arquivos (hPanel):** clique com botão direito no arquivo → **Excluir**
   - **Via SSH:** `rm ~/public_html/setup_database_fixed.php`

> ⚠️ **Nunca execute esse arquivo duas vezes** – ele **apaga e recria** todas as tabelas, perdendo todos os dados.

---

## 9. Verificar o Sistema

Acesse as seguintes URLs para confirmar que tudo funciona:

| URL | O que verifica |
|---|---|
| `https://seudominio.com.br/` | Dashboard principal carrega |
| `https://seudominio.com.br/admin_transacoes.php` | Painel admin de transações |
| `https://seudominio.com.br/admin_categorias.php` | Painel admin de categorias |
| `https://seudominio.com.br/upload_interface.php` | Tela de upload de arquivos |

**Problemas comuns na Hostinger:**

| Erro | Solução |
|---|---|
| `Erro de conexão com banco de dados` | Revise `includes/config.php` – use `127.0.0.1` como host e nomes com prefixo |
| Página em branco | Verifique a versão do PHP no hPanel (Passo 1) |
| `Class 'Database' not found` | A pasta `vendor/` está ausente – refaça o Passo 6 |
| Erro 500 | Ative a exibição de erros temporariamente: em `includes/config.php` mude para `error_reporting(E_ALL)` e `ini_set('display_errors', 1)` |

---

## 10. Guia de Uso do Sistema

### 10.1 Importar o Extrato Bancário

1. Acesse `admin_transacoes.php`
2. Clique em **Selecionar Extrato (OFX)**
3. Selecione o arquivo `.ofx` exportado pelo seu banco (ex: `Extrato AEMF.ofx`)
4. Clique em **Processar Documentos**
5. O sistema importa todas as transações automaticamente

> **Dica – exportar extrato OFX:** A maioria dos bancos brasileiros oferece exportação em formato OFX. No Internet Banking, procure por **Extratos → Exportar → Formato OFX** e selecione o período desejado.

### 10.2 Importar Comprovantes PDF

1. Na mesma tela de importação, clique em **Selecionar Comprovantes**
2. Selecione um ou mais arquivos PDF de comprovantes (PIX, TED, boletos)
3. Clique em **Processar Documentos**
4. O sistema tenta conciliar cada comprovante com uma transação pelo valor (tolerância de R$ 0,02)

### 10.3 Categorizar Transações Manualmente

1. Acesse `admin_transacoes.php` → aba **Categorizar**
2. Transações sem categoria aparecem na lista
3. Para cada transação, selecione a categoria e clique em **Salvar**
4. Para categorizar várias de uma vez, marque os checkboxes e use **Categorizar Selecionadas**

### 10.4 Aplicar Regras Automáticas

1. Em `admin_transacoes.php` → aba **Reclassificar**
2. Clique em **Aplicar Regras Automáticas**
3. O sistema aplica os padrões da tabela `referencias` (ex: transações com "PAVANELLO" → "Contabilidade")

### 10.5 Gerenciar Categorias e Padrões

1. Acesse `admin_categorias.php`
2. Aba **Categorias**: adicionar, editar ou remover categorias contábeis
3. Aba **Padrões**: adicionar palavras-chave que identificam automaticamente uma categoria

### 10.6 Ver o Dashboard

1. Acesse `index.php` (página principal)
2. Use os botões de período (Mensal/Anual) e o seletor de mês
3. Os gráficos e tabela de transações carregam automaticamente via API

### 10.7 Credenciais de Login (acesso ao PDF consolidado)

| Campo | Valor padrão |
|---|---|
| Usuário | `antonio` |
| Senha | `moraes123` |

> Para alterar, edite `$valid_username` e `$valid_password` em `acesso.php`.

---

## 11. Checklist de Segurança para Produção

Antes de usar o sistema em produção, confirme cada item:

- [ ] `setup_database_fixed.php` foi **deletado** do servidor (hPanel → Gerenciador de Arquivos)
- [ ] Em `includes/config.php`, `error_reporting(0)` e `ini_set('display_errors', 0)` estão ativos
- [ ] A senha do banco de dados em `includes/config.php` é forte (não é a senha padrão)
- [ ] As credenciais em `acesso.php` foram alteradas (usuário e senha do PDF)
- [ ] As pastas `uploads/` e `temp/` têm permissão `755` (não `777`)
- [ ] O arquivo `analyze_database.php` foi **deletado** (expõe estrutura do banco)
- [ ] O arquivo `clear_transactions.php` foi **deletado** (apaga todas as transações)
- [ ] O arquivo `analyze_structure.php` foi **deletado** (diagnóstico, não necessário em produção)

---

## Perguntas Frequentes

**Posso apagar todos os arquivos do `public_html` e subir os arquivos deste repositório?**

Sim. Basta:
1. Apagar os arquivos antigos em `public_html` (hPanel → Gerenciador de Arquivos)
2. Subir os arquivos do repositório
3. Editar `includes/config.php` com os dados do seu banco da Hostinger
4. Garantir que a pasta `vendor/` foi enviada
5. Criar as pastas `uploads/` e `temp/`
6. Acessar `setup_database_fixed.php` uma única vez e depois deletá-lo

**Qual o host do banco de dados na Hostinger?**

Use `127.0.0.1` (não o IP externo do servidor). Alguns planos também aceitam `localhost`.

**O arquivo `default.php` pode ser apagado?**

Sim, é uma página padrão da Hostinger e não faz parte do sistema.

**Os arquivos `dir,` e `file,` podem ser apagados?**

Sim, são arquivos em branco sem função.

**O arquivo `index.phpx` pode ser apagado?**

Sim. O arquivo correto é `index.php`. O `index.phpx` é uma cópia antiga.

**Preciso dos arquivos `Extrato AEMF.ofx` e `Comprovantes AEMF.pdf` no servidor?**

Não. Eles são exemplos locais. Em produção, o upload é feito pela interface do sistema.

**Como acessar o phpMyAdmin na Hostinger?**

hPanel → **Bancos de Dados → phpMyAdmin** → clique em **Entrar no phpMyAdmin**. Útil para verificar se as tabelas foram criadas corretamente.
