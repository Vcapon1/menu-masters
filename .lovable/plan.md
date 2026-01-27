
## Correções de Coerência PHP/MySQL

### Objetivo
Alinhar todos os arquivos PHP e SQL com a nova marca "Cardápio Floripa" e corrigir pequenas inconsistências de nomenclatura.

### Arquivos a Modificar

| Arquivo | Alterações |
|---------|-----------|
| `docs/php/config/database.php` | Trocar APP_NAME para "Cardápio Floripa" e APP_URL de exemplo |
| `docs/database/schema.sql` | Corrigir email admin, cidade padrão do diretório, comentários |
| `docs/php/templates/appetite/template.php` | Corrigir nome da variável `bg_color` → `background_color` |

---

### Alterações Detalhadas

#### 1. `docs/php/config/database.php`

Trocar:
- `APP_NAME`: "Premium Menu" → "Cardápio Floripa"
- `APP_URL`: "https://seudominio.com" → "https://cardapiofloripa.com.br"

#### 2. `docs/database/schema.sql`

- **Linha 199**: Email do admin master: `admin@premiummenu.com` → `admin@cardapiofloripa.com.br`
- **Linha 210**: Cidade padrão do diretório: `São Paulo` → `Florianópolis`
- **Linha 219**: Comentário do is_client: `É cliente Premium Menu?` → `É cliente Cardápio Floripa?`

#### 3. `docs/php/templates/appetite/template.php`

- **Linha 14**: Variável incorreta `bg_color` → `background_color` (alinhado com coluna do banco)

---

### Verificação de Consistência das Cores

Confirmei que as cores nos templates PHP correspondem aos presets TypeScript:

| Template | primary | secondary | accent | button | buttonText | font |
|----------|---------|-----------|--------|--------|------------|------|
| Appetite | #f97316 | #1f2937 | #f59e0b | #f97316 | #ffffff | #1f2937 |
| Bold | #dc2626 | #fbbf24 | #f59e0b | #dc2626 | #ffffff | #ffffff |
| Classic | #1f2937 | #f59e0b | #d97706 | #f59e0b | #1f2937 | #1f2937 |

Todos os valores estão sincronizados entre `src/lib/templatePresets.ts` e `docs/database/schema.sql`.

---

### Campos do Banco Confirmados

A tabela `restaurants` no schema SQL inclui todos os campos usados no React:

| Campo SQL | Usado no React | Status |
|-----------|----------------|--------|
| primary_color | primaryColor | ✅ |
| secondary_color | secondaryColor | ✅ |
| accent_color | accentColor | ✅ |
| button_color | buttonColor | ✅ |
| button_text_color | buttonTextColor | ✅ |
| font_color | fontColor | ✅ |
| background_color | backgroundColor | ✅ |
| background_image | backgroundImage | ✅ |
| background_video | backgroundVideo | ✅ |
| logo | logo | ✅ |
| banner | banner | ✅ |
| internal_notes | internalNotes | ✅ |
| address | address | ✅ |

---

### Verificação do Guia Gastronômico

A tabela `directory_restaurants` está correta e inclui:
- `cuisine_types` (JSON) ✅
- `opening_hours` (JSON) ✅
- `price_range` (ENUM) ✅
- `is_client` + `linked_restaurant_id` ✅
- `menu_url` ✅
- `status` (ENUM) ✅

Alinhada com `src/lib/directoryTypes.ts`.

---

### Resumo das Correções

1. Trocar marca "Premium Menu" → "Cardápio Floripa" nos arquivos PHP
2. Corrigir variável `bg_color` → `background_color` no template Appetite
3. Atualizar cidade padrão "São Paulo" → "Florianópolis" no diretório
4. Atualizar email do admin master

Após essas correções, o projeto estará 100% coerente para deploy.
