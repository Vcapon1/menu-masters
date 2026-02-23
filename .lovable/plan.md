

# Importacao Inteligente de Cardapio por Foto (IA)

## Visao Geral

Adicionar no painel **Master Admin**, na pagina de gerenciamento de um restaurante, um botao **"Importar Cardapio por Foto"** que permite enviar uma ou mais fotos de um cardapio fisico. A IA (Google Gemini via Lovable AI Gateway) analisa as imagens e retorna categorias e produtos estruturados, prontos para revisao e insercao no banco.

## Fluxo do Administrador

```text
1. Master Admin abre a pagina do restaurante
          |
2. Clica em "Importar Cardapio por Foto"
          |
3. Upload de 1 a 5 fotos do cardapio fisico
          |
4. Sistema envia as imagens para Edge Function
   -> Edge Function chama Gemini com prompt estruturado
   -> Gemini retorna JSON com categorias e produtos
          |
5. Tela de revisao exibida ao admin:
   - Lista de categorias detectadas
   - Produtos com nome, descricao, preco
   - Checkboxes para selecionar/desmarcar itens
   - Campos editaveis para correcao manual
          |
6. Admin confirma -> sistema insere no banco via API PHP
```

## Arquitetura

O fluxo utiliza uma Edge Function (Supabase/Lovable Cloud) como backend para chamar a IA, e o frontend PHP faz a interface de upload, revisao e insercao.

```text
[Master Admin PHP]
      |
      | POST com imagem(ns) base64
      v
[Edge Function: menu-import-ai]
      |
      | Chamada ao Lovable AI Gateway (Gemini com visao)
      v
[Google Gemini 2.5 Flash]
      |
      | Retorna JSON estruturado
      v
[Edge Function retorna JSON]
      |
      v
[Master Admin PHP: Tela de revisao]
      |
      | POST confirmacao
      v
[PHP insere categorias + produtos no MySQL]
```

## Arquivos a Criar/Modificar

| Arquivo | Acao | Descricao |
|---------|------|-----------|
| `supabase/functions/menu-import-ai/index.ts` | Criar | Edge Function que recebe imagens e chama Gemini para extrair dados do cardapio |
| `docs/php/master/restaurants.php` | Modificar | Adicionar botao "Importar Cardapio por Foto" e modal de upload/revisao |
| `docs/php/admin/index.php` | Modificar | Adicionar atalho para importacao tambem no painel do restaurante |

## Detalhes Tecnicos

### Edge Function: menu-import-ai

Recebe imagens em base64, monta prompt com instrucoes para extrair:
- **Categorias**: nome
- **Produtos**: nome, descricao (se visivel), preco, categoria a que pertence

Prompt estruturado para Gemini:

```text
Analise esta foto de um cardapio de restaurante brasileiro.
Extraia TODAS as categorias e produtos visiveis.
Retorne APENAS um JSON valido no formato:
{
  "categories": [
    {
      "name": "Nome da Categoria",
      "products": [
        {
          "name": "Nome do Produto",
          "description": "Descricao se visivel, senao vazio",
          "price": 29.90
        }
      ]
    }
  ]
}
Regras:
- Precos em formato numerico (sem R$)
- Se houver tamanhos (P, M, G), use o campo sizes_prices
- Se nao conseguir ler um preco, coloque 0
- Mantenha acentuacao correta em portugues
```

Modelo utilizado: `google/gemini-2.5-flash` (suporta imagens, rapido e economico).

Uso de **tool calling** para garantir retorno JSON estruturado (conforme documentacao do Lovable AI).

### Interface no Master Admin (PHP)

**Modal de Upload:**
- Area de drag-and-drop para ate 5 fotos
- Preview das imagens antes de enviar
- Botao "Analisar com IA"
- Loading com mensagem "Analisando cardapio..."

**Tela de Revisao:**
- Tabela editavel com categorias e produtos extraidos
- Cada linha tem: checkbox de selecao, nome (editavel), descricao (editavel), preco (editavel)
- Categorias como headers colapsaveis
- Botao "Importar Selecionados" que faz POST para o PHP inserir no banco
- Botao "Descartar" para cancelar

**Insercao no Banco (PHP):**
- Cria categorias que nao existem
- Cria produtos vinculados ao restaurante e categoria
- Respeita limites do plano (max_categories, max_products)
- Exibe resumo: "X categorias e Y produtos importados"

### Pre-requisitos

- Lovable Cloud habilitado (para a Edge Function)
- LOVABLE_API_KEY ja disponivel automaticamente
- Nenhuma configuracao adicional necessaria pelo usuario

## Limitacoes e Consideracoes

- Fotos com baixa qualidade ou texto manuscrito podem ter menor precisao
- O admin sempre revisa antes de confirmar a importacao
- Cardapios muito extensos podem precisar de multiplas fotos
- Limite de 5 fotos por importacao para controlar custo de API
- Produtos duplicados (mesmo nome) sao sinalizados na revisao

