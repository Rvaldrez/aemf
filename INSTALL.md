# AEMF – Guia de Implantação Passo a Passo

> **Resposta rápida:** Sim, você pode apagar todos os arquivos do seu diretório e substituir pelos arquivos deste repositório, **exceto** a pasta `vendor/` (que deve ser regenerada com `composer install`). Siga os passos abaixo.

---

## Índice

1. [Requisitos do Servidor](#1-requisitos-do-servidor)
2. [Baixar os Arquivos do Repositório](#2-baixar-os-arquivos-do-repositório)
3. [Configurar o Banco de Dados MySQL](#3-configurar-o-banco-de-dados-mysql)
4. [Editar o Arquivo de Configuração](#4-editar-o-arquivo-de-configuração)
5. [Instalar as Dependências PHP (Composer)](#5-instalar-as-dependências-php-composer)
6. [Criar as Pastas de Upload](#6-criar-as-pastas-de-upload)
7. [Enviar os Arquivos para o Servidor (FTP/cPanel)](#7-enviar-os-arquivos-para-o-servidor-ftpcpanel)
8. [Criar as Tabelas do Banco de Dados](#8-criar-as-tabelas-do-banco-de-dados)
9. [Verificar o Sistema](#9-verificar-o-sistema)
10. [Guia de Uso do Sistema](#10-guia-de-uso-do-sistema)
11. [Checklist de Segurança para Produção](#11-checklist-de-segurança-para-produção)

---

## 1. Requisitos do Servidor

| Componente | Versão mínima |
|---|---|
| PHP | 7.4 ou superior (recomendado 8.x) |
| MySQL / MariaDB | 5.7 / 10.3 ou superior |
| Extensão PHP `pdo_mysql` | Habilitada |
| Extensão PHP `fileinfo` | Habilitada (necessária para PDF) |
| Extensão PHP `mbstring` | Habilitada |
| Espaço em disco | Mínimo 50 MB |

> A Hostinger Business e Premium suportam PHP 8.x e MySQL 8, atendendo todos os requisitos.

---

## 2. Baixar os Arquivos do Repositório

### Opção A – Git (recomendado, se disponível no servidor)

```bash
# No diretório do servidor (ex: public_html)
git clone https://github.com/Rvaldrez/aemf.git .
```

### Opção B – Download Manual (para quem usa cPanel/FTP)

1. Acesse: `https://github.com/Rvaldrez/aemf`
2. Clique em **Code → Download ZIP**
3. Extraia o ZIP no seu computador
4. Você verá a pasta com todos os arquivos do projeto

**Arquivos que devem estar presentes após o download:**

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
├── composer.json            ← Dependências PHP
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
└── vendor/                  ← Gerado pelo Composer (ver Passo 5)
```

---

## 3. Configurar o Banco de Dados MySQL

### 3.1 Criar o banco de dados (via cPanel → MySQL Databases)

1. Acesse **cPanel → Bancos de Dados MySQL**
2. Em "Criar novo banco de dados", digite um nome e clique em **Criar banco de dados**
   - Exemplo: `u999392040_aemfpar`
3. Em "Adicionar usuário ao banco de dados", selecione o usuário e o banco criados e conceda **TODOS OS PRIVILÉGIOS**
4. Anote:
   - **Host:** normalmente `localhost` (ou IP se o banco for remoto, ex: `193.203.175.155`)
   - **Nome do banco:** o que você criou
   - **Usuário:** o usuário MySQL
   - **Senha:** a senha do usuário MySQL

---

## 4. Editar o Arquivo de Configuração

Abra o arquivo `includes/config.php` em um editor de texto e preencha com os dados do seu banco:

```php
<?php
// includes/config.php

define('DB_HOST', 'localhost');             // ← seu host MySQL
define('DB_NAME', 'seu_banco_de_dados');   // ← nome do banco
define('DB_USER', 'seu_usuario_mysql');    // ← usuário MySQL
define('DB_PASS', 'sua_senha_mysql');      // ← senha MySQL
define('DB_CHARSET', 'utf8mb4');

// URL do seu site (sem barra no final)
define('SITE_URL', 'https://seu-dominio.com');

// Caminhos de upload (não alterar salvo necessidade)
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('TEMP_PATH',   dirname(__DIR__) . '/temp/');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting — colocar 0 em produção
error_reporting(0);
ini_set('display_errors', 0);
?>
```

> **Dica:** O arquivo `includes/config.php` é o **único** que você precisa editar. Não mexa nos demais arquivos de configuração.

---

## 5. Instalar as Dependências PHP (Composer)

O sistema usa a biblioteca `smalot/pdfparser` para ler PDFs de comprovantes.

### Opção A – Via SSH (Terminal)

```bash
# Na pasta raiz do projeto
composer install --no-dev --optimize-autoloader
```

### Opção B – Sem acesso SSH (upload manual da pasta vendor)

Se você não tem SSH, use a pasta `vendor/` que já está no repositório:

1. Baixe o ZIP do repositório (Passo 2)
2. A pasta `vendor/` já está incluída no ZIP
3. Envie a pasta `vendor/` inteira para o servidor via FTP junto com os demais arquivos

> A pasta `vendor/` **não é excluída** do repositório, então o ZIP já a contém.

---

## 6. Criar as Pastas de Upload

O sistema precisa de duas pastas com permissão de escrita:

### Via cPanel → Gerenciador de Arquivos

1. Acesse **cPanel → Gerenciador de Arquivos → public_html**
2. Crie a pasta `uploads/`
3. Crie a pasta `temp/`
4. Dê permissão `755` para ambas (clique com botão direito → Alterar permissões)

### Via FTP

Crie as pastas e ajuste permissões para `755`.

### Via SSH

```bash
mkdir -p uploads temp
chmod 755 uploads temp
```

---

## 7. Enviar os Arquivos para o Servidor (FTP/cPanel)

### Usando cPanel → Gerenciador de Arquivos

1. Acesse o Gerenciador de Arquivos → `public_html`
2. Clique em **Enviar** e selecione o ZIP do projeto
3. Extraia o ZIP diretamente no `public_html`
4. Verifique se o `index.php` está **dentro** do `public_html` (não dentro de uma subpasta)

### Usando FTP (FileZilla)

1. Conecte ao servidor com suas credenciais FTP
2. Navegue para `public_html/` no painel direito
3. Arraste todos os arquivos do projeto para `public_html/`

**Estrutura final esperada no servidor:**

```
public_html/
├── index.php
├── includes/config.php   ← já editado
├── api/
├── vendor/
├── uploads/              ← criado no Passo 6
└── temp/                 ← criado no Passo 6
```

---

## 8. Criar as Tabelas do Banco de Dados

Este passo cria todas as tabelas e insere as categorias e regras de classificação padrão.

1. Abra o navegador e acesse:
   ```
   https://seu-dominio.com/setup_database_fixed.php
   ```

2. Você verá uma lista de confirmações:
   ```
   ✓ Tabela categorias criada
   ✓ Tabela transacoes criada
   ✓ Tabela referencias criada
   ✓ Categorias inseridas
   ✓ Referências inseridas

   ✅ Setup concluído com sucesso!
   ```

3. **IMPORTANTE – segurança:** Após ver a mensagem de sucesso, **delete ou renomeie** o arquivo `setup_database_fixed.php` para evitar que alguém recrie o banco acidentalmente:
   - Via cPanel: clique com botão direito → Renomear → `setup_database_fixed.DONE`
   - Via FTP: renomeie ou apague

> ⚠️ **Nunca execute esse arquivo duas vezes em produção** – ele apaga e recria todas as tabelas.

---

## 9. Verificar o Sistema

Acesse as seguintes URLs para verificar que tudo funciona:

| URL | O que verifica |
|---|---|
| `https://seu-dominio.com/` | Dashboard principal carrega |
| `https://seu-dominio.com/admin_transacoes.php` | Painel admin de transações |
| `https://seu-dominio.com/admin_categorias.php` | Painel admin de categorias |
| `https://seu-dominio.com/upload_interface.php` | Tela de upload de arquivos |

Se alguma página mostrar erro de banco de dados, revise o `includes/config.php` (Passo 4).

---

## 10. Guia de Uso do Sistema

### 10.1 Importar o Extrato Bancário

1. Acesse **Admin → Importação** (`admin_transacoes.php` ou `admin_categorias.php`)
2. Clique em **Selecionar Extrato (OFX)**
3. Selecione o arquivo `Extrato AEMF.ofx` (formato OFX exportado pelo banco)
4. Clique em **Processar Documentos**
5. O sistema importa todas as transações automaticamente

### 10.2 Importar Comprovantes PDF

1. Na mesma tela de importação, clique em **Selecionar Comprovantes**
2. Selecione um ou mais arquivos PDF de comprovantes
3. Clique em **Processar Documentos**
4. O sistema tenta conciliar cada comprovante com uma transação existente pelo valor

### 10.3 Categorizar Transações Manualmente

1. Acesse a aba **Categorizar** em `admin_transacoes.php`
2. Transações sem categoria aparecem na lista
3. Para cada transação, selecione a categoria no menu suspenso e clique em **Salvar**
4. Para categorizar várias de uma vez, marque os checkboxes e use **Categorizar Selecionadas**

### 10.4 Aplicar Regras Automáticas

1. Em `admin_transacoes.php` → aba **Reclassificar**
2. Clique em **Aplicar Regras Automáticas**
3. O sistema aplica os padrões da tabela `referencias` (ex: qualquer transação com "PAVANELLO" vai para categoria "Contabilidade")

### 10.5 Gerenciar Categorias e Padrões

1. Acesse `admin_categorias.php`
2. Aba **Categorias**: adicionar, editar ou remover categorias contábeis
3. Aba **Padrões**: adicionar palavras-chave que identificam automaticamente uma categoria

### 10.6 Ver o Dashboard

1. Acesse `index.php` (página principal)
2. Use os botões de período (Mensal/Anual) e o seletor de mês
3. Os gráficos e tabela de transações carregam automaticamente via API

### 10.7 Credenciais de Login (acesso ao PDF consolidado)

O arquivo `login.php` / `acesso.php` protege o acesso ao PDF consolidado:

| Campo | Valor padrão |
|---|---|
| Usuário | `antonio` |
| Senha | `moraes123` |

> Para alterar, edite as linhas `$valid_username` e `$valid_password` em `acesso.php`.

---

## 11. Checklist de Segurança para Produção

Antes de colocar o sistema em produção, verifique:

- [ ] `setup_database_fixed.php` foi **deletado ou renomeado** após a criação das tabelas
- [ ] Em `includes/config.php`, `error_reporting(0)` e `ini_set('display_errors', 0)` estão configurados
- [ ] A senha do banco de dados em `includes/config.php` é forte e diferente da senha padrão
- [ ] As credenciais em `acesso.php` foram alteradas (usuário e senha do PDF)
- [ ] As pastas `uploads/` e `temp/` têm permissão `755` (não `777`)
- [ ] O arquivo `analyze_database.php` foi deletado ou protegido (expõe estrutura do banco)
- [ ] O arquivo `clear_transactions.php` foi deletado (apaga todas as transações)

---

## Perguntas Frequentes

**Posso apagar todos os arquivos do meu diretório e subir os arquivos deste repositório?**

Sim. Os arquivos deste repositório são o sistema completo. Basta:
1. Apagar os arquivos antigos no `public_html`
2. Subir os arquivos do repositório
3. Editar `includes/config.php` com seus dados de banco
4. Subir/criar a pasta `vendor/`
5. Criar as pastas `uploads/` e `temp/`
6. Acessar `setup_database_fixed.php` uma única vez

**O arquivo `default.php` pode ser apagado?**

Sim, é uma página padrão da Hostinger e não faz parte do sistema.

**Os arquivos `dir,` e `file,` podem ser apagados?**

Sim, são arquivos em branco sem função.

**O arquivo `index.phpx` pode ser apagado?**

Sim. O arquivo correto é `index.php` (com extensão `.php`). O `index.phpx` é uma cópia antiga.

**Preciso do `Extrato AEMF.ofx` e `Comprovantes AEMF.pdf` no servidor?**

Não necessariamente. Eles são arquivos de exemplo. Em produção, você fará o upload deles pela interface do sistema.
