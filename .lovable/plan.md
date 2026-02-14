

# Banco de Imagens com Video Opcional

## Resumo

Criar uma biblioteca de imagens compartilhada que os restaurantes podem usar ao cadastrar pratos. Cada imagem do banco pode ter um video associado (ou nao). Ao selecionar, a URL da imagem (e do video, se houver) e salva diretamente nos campos `image` e `video` do produto. Ao editar um produto que usa imagem do banco, o admin ve claramente a origem e pode trocar.

## Como Funciona para o Restaurante

1. No formulario de prato, ao lado do campo de upload, aparece o botao **"Banco de Imagens"**
2. Abre um modal com galeria organizada por categoria (Bebidas, Sobremesas, etc.)
3. Cada thumbnail mostra um icone de video se tiver video associado
4. Ao clicar na imagem, ela e selecionada automaticamente - o preview aparece no formulario
5. Se a imagem tiver video, o campo de video tambem e preenchido automaticamente
6. Ao salvar, os campos `image` e `video` do produto recebem as URLs do banco (ex: `/uploads/stock-images/bebidas/coca-cola.webp`)
7. Ao editar o produto, se a imagem atual vem do banco (URL contem `stock-images/`), mostra um badge "Imagem do Banco" e o botao para trocar

## Estrutura do Banco de Dados

Nova tabela `stock_images`:

```text
stock_images
  id            INT (PK, AUTO_INCREMENT)
  category      VARCHAR(50)     -- "bebidas", "sobremesas", "acompanhamentos", "diversos"
  name          VARCHAR(100)    -- "Coca-Cola 350ml", "Pudim"
  filename      VARCHAR(200)    -- caminho relativo: "bebidas/coca-cola.webp"
  video_filename VARCHAR(200)   -- caminho relativo do video (NULL = sem video)
  tags          VARCHAR(500)    -- palavras-chave para busca: "refrigerante, cola, soda"
  sort_order    INT DEFAULT 0
  is_active     TINYINT(1) DEFAULT 1
  created_at    TIMESTAMP
```

As URLs finais sao montadas com um prefixo padrao:
- Imagem: `UPLOAD_URL . 'stock-images/' . filename`
- Video: `UPLOAD_URL . 'stock-images/videos/' . video_filename`

## Arquivos do Servidor

```text
/uploads/stock-images/
  bebidas/
    coca-cola.webp
    guarana.webp
    suco-laranja.webp
  sobremesas/
    pudim.webp
    petit-gateau.webp
  acompanhamentos/
    arroz.webp
    batata-frita.webp
  diversos/
    combo.webp
  videos/
    coca-cola.mp4
    petit-gateau.mp4
```

## Arquivos a Criar/Modificar

| Arquivo | Acao | Detalhes |
|---------|------|---------|
| `docs/database/schema.sql` | Modificar | Nova tabela `stock_images` |
| `docs/php/api/stock-images.php` | Criar | API para listar imagens filtradas por categoria e busca |
| `docs/php/admin/products.php` | Modificar | Botao "Banco de Imagens", modal de galeria, logica de selecao |
| `docs/php/master/stock-images.php` | Criar | CRUD para o Master Admin gerenciar o banco de imagens |
| `docs/php/includes/functions.php` | Modificar | Funcoes `getStockImages()` e `getStockCategories()` |

## Detalhes Tecnicos

### API: `docs/php/api/stock-images.php`

Endpoint publico (sem autenticacao, apenas leitura):

```text
GET /api/stock-images.php?category=bebidas&search=coca
Resposta: {
  "success": true,
  "images": [
    {
      "id": 1,
      "name": "Coca-Cola 350ml",
      "category": "bebidas",
      "image_url": "https://site.com/uploads/stock-images/bebidas/coca-cola.webp",
      "video_url": "https://site.com/uploads/stock-images/videos/coca-cola.mp4",
      "has_video": true
    }
  ],
  "categories": ["bebidas", "sobremesas", "acompanhamentos", "diversos"]
}
```

### Modal no `products.php`

- Grid de thumbnails com lazy loading
- Tabs por categoria no topo
- Campo de busca por nome/tag
- Icone de video sobre o thumbnail quando tem video
- Ao clicar: preenche `current_image` com a URL da imagem e `current_video` com a URL do video (se existir)
- Preview aparece no formulario imediatamente

### Deteccao de Imagem do Banco ao Editar

Ao abrir o modal de edicao de um produto:
- Se `product.image` contem `stock-images/`, mostra badge "Imagem do Banco" sobre o preview
- Permite trocar por outra do banco ou por upload proprio
- Se trocar por upload proprio, o campo `video` e limpo (a menos que o admin faca upload de video tambem)

### Master Admin: `docs/php/master/stock-images.php`

Interface para o admin master:
- Upload de novas imagens ao banco (com campo opcional de video)
- Definir nome, categoria e tags
- Ativar/desativar imagens
- Reordenar por categoria

