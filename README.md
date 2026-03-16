# AEMF – Dashboard Financeiro

Sistema de dashboard financeiro para **AEMF I Participações Ltda.**

## Funcionalidades

- **Importação de extratos** no formato OFX (Itaú e outros bancos)
- **Leitura de comprovantes PDF** com conciliação automática
- **Agrupamento por conta contábil** (categorias configuráveis)
- **Painel administrativo** para categorização manual e correções
- **Regras automáticas** de classificação por palavras-chave
- **Dashboard executivo** com resumo mensal/anual

## Como Implantar

Consulte o **[Guia de Implantação Passo a Passo](INSTALL.md)** para instruções completas de instalação.

## Estrutura Principal

| Arquivo | Função |
|---|---|
| `index.php` | Dashboard principal |
| `admin_transacoes.php` | Admin: importação e categorização |
| `admin_categorias.php` | Admin: categorias e padrões |
| `upload_interface.php` | Interface de upload |
| `setup_database_fixed.php` | Criação das tabelas (usar uma vez) |
| `includes/config.php` | **Configuração do banco de dados** |
