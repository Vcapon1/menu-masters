

## Cores Padrão por Template - Melhorias

### Situação Atual

Seu sistema já tem a infraestrutura básica implementada:
- O arquivo `src/lib/templatePresets.ts` define cores padrão para cada template
- Quando você cria um **novo** restaurante e seleciona um template, as cores são preenchidas automaticamente

### O Que Falta Para Otimizar o Onboard

Identificamos 3 melhorias para deixar o fluxo mais prático:

---

### 1. Botão "Restaurar Cores Padrão"

Adicionar um botão na seção de cores que permite restaurar as cores do template selecionado a qualquer momento.

**Benefício**: Ao editar um restaurante, se o cliente quiser voltar às cores originais do template, basta clicar no botão.

**Implementação**:
- Adicionar botão na seção "Cores do Tema" do formulário em `MasterRestaurants.tsx`
- Usar a função `getDefaultColors()` já existente
- Mostrar descrição do preset selecionado para orientar o usuário

---

### 2. Adicionar Cores Padrão na Tabela de Templates (SQL)

Incluir as cores diretamente no banco de dados para que o PHP também tenha acesso.

**Benefício**: O backend PHP pode carregar cores padrão automaticamente ao criar um restaurante.

**Alteração no schema.sql**:
```sql
ALTER TABLE templates ADD COLUMN default_colors JSON;
-- Exemplo: {"primary": "#f97316", "secondary": "#1f2937", ...}
```

---

### 3. Criar Variações Temáticas por Tipo de Comida (Futuro)

Além do template de layout, oferecer **presets de cores por nicho**:

| Tema | Cores Características |
|------|----------------------|
| Japonesa | Vermelho escuro, preto, branco, dourado |
| Italiana | Verde, vermelho, creme |
| Mexicana | Amarelo, verde, vermelho vibrante |
| Hamburguer | Marrom, amarelo, vermelho |
| Pizzaria | Vermelho tomate, amarelo queijo |
| Vegano | Verde folha, marrom terra |
| Cafeteria | Marrom café, creme, caramelo |
| Churrascaria | Vermelho carne, preto, dourado |

**Implementação**: Dropdown adicional ou botões de sugestão na seção de cores.

---

### Plano de Implementação

#### Fase 1 - Botão Restaurar Cores (Implementar Agora)

**Arquivo**: `src/pages/master/MasterRestaurants.tsx`

Alterações:
1. Importar `getTemplatePreset` para exibir descrição
2. Adicionar função `handleResetColors()` que aplica cores do preset atual
3. Adicionar botão com ícone de refresh na seção "Cores do Tema"
4. Exibir badge com nome e descrição do preset selecionado

#### Fase 2 - Sincronizar com SQL

**Arquivo**: `docs/database/schema.sql`

Alterações:
1. Adicionar coluna `default_colors` (JSON) na tabela `templates`
2. Atualizar INSERTs com cores padrão de cada template

#### Fase 3 - Presets Temáticos (Opcional/Futuro)

**Novo arquivo**: `src/lib/cuisineColorPresets.ts`

Criar objeto com sugestões de cores por tipo de culinária, integrado como botões de sugestão rápida no formulário.

---

### Seção Técnica

**Código do botão restaurar cores:**

```typescript
const handleResetColors = () => {
  const colors = getDefaultColors(formData.template);
  if (colors) {
    setFormData({
      ...formData,
      ...colors,
    });
    toast({ 
      title: "Cores restauradas", 
      description: `Aplicadas cores padrão do template ${formData.template}` 
    });
  }
};
```

**Estrutura do JSON de cores no SQL:**

```json
{
  "primary": "#f97316",
  "secondary": "#1f2937", 
  "accent": "#f59e0b",
  "button": "#f97316",
  "buttonText": "#ffffff",
  "font": "#1f2937"
}
```

---

### Resultado Esperado

- Ao criar restaurante: cores preenchidas automaticamente (já funciona)
- Ao editar restaurante: botão para restaurar cores originais
- Backend PHP: acesso às cores padrão via banco de dados
- Futuro: sugestões de cores por nicho gastronômico

