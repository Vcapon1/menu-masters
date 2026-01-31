
# Plano: Corrigir Modais e Erro 500 do Diretório

## Resumo dos Problemas

1. Modal de pratos sem rolagem adequada e sem preview de imagem/video
2. Modal de restaurantes sem preview das imagens já carregadas
3. Página do Diretório com erro 500

---

## Parte 1: Diretório - O que é e por que está dando erro

### O que e o Diretorio?
O Diretorio (Guia Gastronomico) e uma funcionalidade separada para listar restaurantes da cidade, **incluindo os que NAO sao clientes**. Serve como:
- Guia gastronomico publico de Florianopolis
- Ferramenta de prospeccao de novos clientes
- Vinculo entre restaurantes do guia e clientes do sistema

### Causa do Erro 500
O codigo PHP esta tentando usar a coluna `is_featured` que nao existe na tabela `directory_restaurants`:
```php
$sql .= " ORDER BY is_featured DESC, name ASC";  // Linha 100
<?php if ($dr['is_featured']): ?>  // Linha 195
```

### Solucao
**Opcao A - Adicionar coluna ao banco:**
```sql
ALTER TABLE directory_restaurants 
ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
```

**Opcao B - Remover referencia** se nao quiser usar destaque (mais simples)

---

## Parte 2: Modal de Pratos - Rolagem + Preview

### Arquivo: `docs/php/admin/products.php`

### Alteracoes:

1. **Estrutura do Modal** (linhas 268-364)
   - Adicionar estilos CSS para header fixo, body rolavel, footer fixo
   - Mesma estrutura usada no modal de restaurantes

2. **Preview de Imagem Atual** (apos linha 318)
   - Adicionar div para mostrar imagem atual
   - Adicionar div para mostrar video atual (se houver)

3. **JavaScript** (funcao `editProduct`)
   - Preencher o preview com a imagem/video do produto

### Codigo CSS a adicionar:
```css
.modal-container {
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}
.modal-header {
    flex-shrink: 0;
    padding: 1.25rem;
    border-bottom: 1px solid #374151;
}
.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}
.modal-footer {
    flex-shrink: 0;
    padding: 1rem 1.5rem;
    border-top: 1px solid #374151;
}
```

### Codigo HTML para Preview:
```html
<div>
    <label class="block text-sm mb-1">Imagem</label>
    <div id="current-image-preview" class="mb-2 hidden">
        <img id="preview-img" src="" class="w-24 h-24 rounded object-cover border border-gray-600">
        <span class="text-xs text-gray-400 ml-2">Imagem atual</span>
    </div>
    <input type="file" name="image" ...>
</div>
```

---

## Parte 3: Modal de Restaurantes - Preview de Imagens

### Arquivo: `docs/php/master/restaurants.php`

### Alteracoes:

1. **Preview de Logo** (apos linha 749)
   - Adicionar div `#preview-logo` com imagem

2. **Preview de Banner** (apos linha 755)
   - Adicionar div `#preview-banner` com imagem

3. **Preview de Background** (apos linha 761)
   - Adicionar div `#preview-bg` com imagem

4. **JavaScript** (funcao `editRestaurant`)
   - Mostrar previews das imagens existentes

### Codigo HTML para cada preview:
```html
<div>
    <label class="block text-sm mb-1">Logo</label>
    <div id="preview-logo" class="mb-2 hidden">
        <img src="" class="w-16 h-16 rounded object-cover border border-gray-600">
    </div>
    <input type="file" name="logo" ...>
</div>
```

### JavaScript para mostrar previews:
```javascript
function editRestaurant(r) {
    // ... codigo existente ...
    
    // Mostrar preview do logo
    const logoPreview = document.getElementById('preview-logo');
    if (r.logo) {
        logoPreview.querySelector('img').src = r.logo;
        logoPreview.classList.remove('hidden');
    } else {
        logoPreview.classList.add('hidden');
    }
    
    // Repetir para banner e background
}
```

---

## Arquivos a Modificar

```text
docs/php/admin/products.php      - Modal com scroll + preview de imagem/video
docs/php/master/restaurants.php  - Preview das imagens no modal
docs/php/master/directory.php    - Corrigir referencia a is_featured
docs/database/schema.sql         - Adicionar coluna is_featured
```

---

## Ordem de Execucao

1. Primeiro corrigir o Diretorio (SQL + PHP) - resolve o erro 500
2. Depois melhorar o modal de Pratos - scroll + preview
3. Por ultimo adicionar previews no modal de Restaurantes

---

## Secao Tecnica

### Problema do Modal sem Scroll
O modal atual usa `max-h-[90vh] overflow-y-auto` no container inteiro, mas nao tem estrutura flex para manter header/footer fixos. A solucao e:

```css
.modal-container {
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}
.modal-body {
    flex: 1;
    overflow-y: auto;
}
```

### Preview de Imagem com JavaScript
Ao editar um produto/restaurante, o JavaScript recebe o objeto completo com URLs das imagens. Basta:
1. Verificar se a URL existe
2. Definir o `src` da tag `<img>`
3. Remover classe `hidden` do container

### Coluna is_featured
Usada para destacar restaurantes no topo da listagem do diretorio. O ORDER BY coloca os featured primeiro.
