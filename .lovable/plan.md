
# Plano: Criar Template "Hero" (Estilo Hamburgeria)

## Visao Geral

Criar um novo template chamado **Hero** com design escuro e impactante, inspirado na imagem de referencia. Caracteristicas principais:

- Hero section com banner de fundo e logo centralizado (aceita PNG transparente)
- Navegacao horizontal de categorias estilo chips
- Cards de produto com imagem grande, badges, e icone de play para video
- Modal de detalhes ao clicar no produto com foto/video expandido
- Tema escuro com acentos dourados/laranja

---

## Estrutura Visual

```text
+------------------------------------------+
|                                          |
|            [BANNER FUNDO]                |
|                                          |
|         [LOGO - PNG TRANSPARENTE]        |
|                                          |
+------------------------------------------+
| [LANCHES] [COMBOS] [REFRIGERANTES] [...]  |
+------------------------------------------+
|                                          |
|  Combos                                  |
|  ========================================|
|  +------------------------------------+  |
|  |  [BADGE]              [PLAY ICON]  |  |
|  |  Combo Premium                     |  |
|  |  R$ 39,00                          |  |
|  |                                    |  |
|  +------------------------------------+  |
|  +------------------------------------+  |
|  |  [BADGE]              [PLAY ICON]  |  |
|  |  Combo Vegano                      |  |
|  |  R$ 39,00                          |  |
|  +------------------------------------+  |
|                                          |
+------------------------------------------+

        MODAL (ao clicar no prato)
+------------------------------------------+
|  [X]                                     |
|  +------------------------------------+  |
|  |                                    |  |
|  |      [IMAGEM OU VIDEO GRANDE]      |  |
|  |                                    |  |
|  +------------------------------------+  |
|                                          |
|  Combo Premium                           |
|  Hamburguer artesanal com queijo...      |
|                                          |
|  [BADGE] [BADGE]                         |
|                                          |
|  R$ 39,00                                |
+------------------------------------------+
```

---

## Arquivos a Criar/Modificar

### 1. Criar pasta e arquivo do template

**Arquivo:** `docs/php/templates/hero/template.php`

Template completo com:
- Hero section com banner fullwidth e logo centralizado
- Navegacao horizontal de categorias
- Cards de produto estilo imagem grande
- Modal de detalhes do produto
- Suporte a video com icone de play

### 2. Adicionar template ao banco de dados

**Arquivo:** `docs/database/schema.sql`

Adicionar INSERT do template "Hero" com cores padrao (escuro com dourado/laranja):

```sql
INSERT INTO `templates` (...) VALUES
('Hero', 'hero', 'Design impactante com hero banner - ideal para hamburgerias', 
 2, 1, 1, 1, 1, 
 '{"primary": "#f59e0b", "secondary": "#fbbf24", "accent": "#f97316", 
   "button": "#f59e0b", "buttonText": "#000000", "font": "#ffffff"}');
```

### 3. Adicionar preset de cores no React

**Arquivo:** `src/lib/templatePresets.ts`

Adicionar entrada "hero" com cores:
- Primary: Dourado (#f59e0b)
- Secondary: Amarelo (#fbbf24)
- Accent: Laranja (#f97316)
- Background: Preto (#0a0a0a)
- Font: Branco (#ffffff)

### 4. Atualizar referencia no Master Admin

**Arquivo:** `docs/php/master/templates.php`

Adicionar icone para o template "hero" no array de icones.

---

## Secao Tecnica: Detalhes do Template

### Hero Section

```php
<section class="hero" style="background-image: url('<?= $restaurant['banner'] ?>')">
    <div class="hero-overlay">
        <?php if ($restaurant['logo']): ?>
            <img src="<?= $restaurant['logo'] ?>" alt="..." class="hero-logo">
        <?php endif; ?>
    </div>
</section>
```

CSS do Hero:
```css
.hero {
    height: 35vh;
    min-height: 200px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, transparent 50%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-logo {
    max-width: 200px;
    max-height: 150px;
    object-fit: contain; /* PNG transparente sem crop */
    filter: drop-shadow(0 4px 20px rgba(0,0,0,0.5));
}
```

### Cards de Produto

Cards fullwidth com imagem de fundo, informacoes sobrepostas:

```css
.product-card {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid var(--accent);
    background: rgba(0,0,0,0.6);
    cursor: pointer;
}

.product-card-bg {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.product-card-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 12px;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
}

.play-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
```

### Modal de Detalhes

```html
<div id="productModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        
        <div class="modal-media">
            <img id="modalImage" src="" alt="">
            <video id="modalVideo" controls style="display:none"></video>
        </div>
        
        <div class="modal-info">
            <h2 id="modalName"></h2>
            <p id="modalDescription"></p>
            <div id="modalBadges" class="modal-badges"></div>
            <div id="modalPrice" class="modal-price"></div>
        </div>
    </div>
</div>
```

JavaScript para abrir modal:
```javascript
function openProductModal(product) {
    const modal = document.getElementById('productModal');
    const img = document.getElementById('modalImage');
    const video = document.getElementById('modalVideo');
    
    // Mostrar imagem ou video
    if (product.video) {
        img.style.display = 'none';
        video.style.display = 'block';
        video.src = product.video;
    } else {
        video.style.display = 'none';
        img.style.display = 'block';
        img.src = product.image;
    }
    
    document.getElementById('modalName').textContent = product.name;
    document.getElementById('modalDescription').textContent = product.description;
    document.getElementById('modalPrice').textContent = 'R$ ' + product.price;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('productModal');
    const video = document.getElementById('modalVideo');
    
    modal.classList.add('hidden');
    video.pause();
    document.body.style.overflow = '';
}
```

### Cores Padrao do Template

| Variavel | Valor | Uso |
|----------|-------|-----|
| --background | #0a0a0a | Fundo principal |
| --primary | #f59e0b | Titulos de categorias, linha |
| --secondary | #fbbf24 | Preco, destaques |
| --accent | #f97316 | Bordas dos cards |
| --font | #ffffff | Texto geral |
| --badge-promo | #dc2626 | Badge promocao |
| --badge-chef | #3b82f6 | Badge sugestao do chef |

---

## Ordem de Implementacao

1. Criar arquivo `docs/php/templates/hero/template.php` com todo o HTML/CSS/JS
2. Adicionar INSERT no `docs/database/schema.sql`
3. Adicionar preset no `src/lib/templatePresets.ts`
4. Atualizar icone no `docs/php/master/templates.php`

---

## Features Incluidas

- [x] Hero fullscreen com banner de fundo
- [x] Logo PNG transparente sem corte circular
- [x] Navegacao horizontal de categorias (scroll touch)
- [x] Cards com imagem grande e overlay de informacoes
- [x] Badge de promocao e sugestao do chef
- [x] Icone de play para pratos com video
- [x] Modal de detalhes ao clicar no produto
- [x] Suporte a video no modal
- [x] Linha dourada decorativa nos titulos de categoria
- [x] Tema escuro por padrao
