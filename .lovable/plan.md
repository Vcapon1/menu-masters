

## Criar Arquivos PHP Faltantes

### Problema Identificado

O arquivo `docs/php/index.php` referencia vários arquivos que não existem no projeto:

| Arquivo Referenciado | Linha | Status |
|---------------------|-------|--------|
| `/landing.php` | 18 | Faltando |
| `/templates/404.php` | 28 | Faltando |
| `/templates/expired.php` | 35 | Faltando |
| `/templates/classic/template.php` | 65 | Faltando |

Além disso, o comentário no index.php ainda diz "PREMIUM MENU" e precisa ser atualizado.

---

### Arquivos a Criar

#### 1. `docs/php/landing.php`
Página inicial do sistema quando acessado sem slug de restaurante.

Conteúdo:
- Logo e nome "Cardápio Floripa"
- Breve descrição do serviço
- Botão para contato/cadastro
- Link para o diretório de restaurantes
- Design responsivo seguindo identidade visual (cores primárias)

#### 2. `docs/php/templates/404.php`
Página de erro quando o restaurante não é encontrado.

Conteúdo:
- Mensagem amigável "Cardápio não encontrado"
- Sugestão para verificar o link
- Botão para voltar à página inicial
- Link para o diretório

#### 3. `docs/php/templates/expired.php`
Página exibida quando o plano do restaurante expirou.

Conteúdo:
- Mensagem informando que o cardápio está temporariamente indisponível
- Orientação para o restaurante renovar o plano
- Contato do suporte

#### 4. `docs/php/templates/classic/template.php`
Template padrão/fallback com design clássico.

Conteúdo:
- Layout simples e elegante
- Header com logo e nome
- Categorias e produtos
- Seguindo estrutura similar ao template Bold mas com design mais clean

---

### Alteração no Arquivo Existente

#### `docs/php/index.php`
- Atualizar comentário "PREMIUM MENU" para "CARDÁPIO FLORIPA"
- Atualizar URL de exemplo para cardapiofloripa.com.br

---

### Estrutura Final de Pastas

```text
docs/php/
├── admin/
├── config/
├── includes/
├── master/
├── templates/
│   ├── appetite/
│   │   ├── style.css
│   │   └── template.php
│   ├── bold/
│   │   └── template.php
│   ├── classic/          ← NOVO
│   │   └── template.php
│   ├── 404.php           ← NOVO
│   └── expired.php       ← NOVO
├── .htaccess
├── index.php
└── landing.php           ← NOVO
```

---

### Seção Técnica

**landing.php - Estrutura base:**
```php
<?php
/**
 * CARDÁPIO FLORIPA - Landing Page
 * Página inicial do sistema
 */
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= APP_NAME ?> - Cardápio Digital</title>
    <!-- CSS inline com cores da marca -->
</head>
<body>
    <!-- Hero com logo, título e CTA -->
    <!-- Seção de benefícios -->
    <!-- Link para diretório -->
    <!-- Footer -->
</body>
</html>
```

**404.php - Estrutura base:**
```php
<?php
/**
 * Página 404 - Cardápio não encontrado
 */
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<!-- Mensagem de erro amigável -->
```

**classic/template.php:**
Similar ao Bold, mas com:
- Cores mais neutras
- Layout sem overlay escuro
- Tipografia mais tradicional
- Sem efeito de hover animado

