

# Template Pizzaria com Pizza Builder Integrado — ✅ IMPLEMENTADO

## Resumo

Template dedicado para pizzarias (slug: `pizzaria`) com visual temático escuro/quente e sistema de montagem multi-sabores integrado ao cart.js.

## Arquivos Criados/Modificados

| Arquivo | Status |
|---------|--------|
| `docs/database/schema.sql` | ✅ Colunas `allow_multi_flavor` e `flavor_config` em categories + INSERT template |
| `docs/php/admin/categories.php` | ✅ Campos multi-sabor + config de sabores por tamanho |
| `docs/php/includes/cart.js` | ✅ Métodos `openPizzaBuilder()`, `pizzaBuilderAction()`, `closePizzaBuilder()` |
| `docs/php/index.php` | ✅ Exporta `MULTI_FLAVOR_CATEGORIES` para JS global |
| `docs/php/templates/pizzaria/template.php` | ✅ Template completo com botão "Montar Pizza" |
| `src/lib/templatePresets.ts` | ✅ Preset de cores pizzaria |
| `docs/php/master/templates.php` | ✅ Ícone 🍕 registrado |

## Funcionalidade Multi-Sabor

- Cada categoria pode ter `allow_multi_flavor` ativado
- `flavor_config` JSON define quantos sabores por tamanho: `{"P": 1, "M": 2, "G": 3}`
- Se tamanho permite 1 sabor: fluxo normal
- Se permite 2+: abre Pizza Builder com seleção visual de sabores
- Preço = maior preço entre sabores selecionados
