

# Plano: Seletor de Tipos de Cozinha com Autocomplete

## Objetivo
Substituir o campo de texto livre por um seletor inteligente que:
- Mostra tipos de cozinha ja cadastrados (usando DISTINCT)
- Permite adicionar novos tipos
- Suporta multipla selecao (tags/badges)

---

## Fluxo da Interface

```text
+--------------------------------------------------+
|  Tipos de Cozinha                                |
|  +--------------------------------------------+  |
|  | [Italiana x] [Pizza x]      [+ Adicionar]  |  |
|  +--------------------------------------------+  |
|                                                  |
|  Sugestoes:                                      |
|  [Brasileira] [Japonesa] [Hamburger] [Sushi]... |
+--------------------------------------------------+
```

1. **Tags selecionadas**: Badges removiveis para tipos ja escolhidos
2. **Botao Adicionar**: Campo para digitar novo tipo
3. **Sugestoes**: Lista de tipos existentes no banco (DISTINCT)

---

## Implementacao

### 1. Buscar Tipos Existentes (PHP)

Adicionar no inicio do arquivo, junto com as outras queries:

```php
// Buscar tipos de cozinha unicos do banco
$existingCuisines = [];
try {
    $stmt = db()->query("SELECT DISTINCT cuisine_types FROM directory_restaurants WHERE cuisine_types IS NOT NULL");
    $allCuisines = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Extrair valores unicos do JSON
    foreach ($allCuisines as $jsonCuisines) {
        $cuisines = json_decode($jsonCuisines, true) ?? [];
        foreach ($cuisines as $c) {
            $c = trim($c);
            if ($c && !in_array($c, $existingCuisines)) {
                $existingCuisines[] = $c;
            }
        }
    }
    sort($existingCuisines);
} catch (Exception $e) {
    // Fallback silencioso
}
```

### 2. HTML do Seletor (Modal)

Substituir o campo atual (linhas 283-286) por:

```html
<div>
    <label class="block text-sm text-gray-400 mb-1">Tipos de Cozinha</label>
    
    <!-- Tags selecionadas -->
    <div id="selected-cuisines" class="flex flex-wrap gap-2 mb-2 min-h-[32px]">
        <!-- Tags serao inseridas via JS -->
    </div>
    
    <!-- Campo para adicionar novo -->
    <div class="flex gap-2">
        <input type="text" id="new-cuisine-input" 
               placeholder="Digite ou selecione abaixo" 
               class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-sm">
        <button type="button" onclick="addCuisine()" 
                class="bg-orange-600 hover:bg-orange-700 px-3 py-2 rounded-lg text-sm">
            Adicionar
        </button>
    </div>
    
    <!-- Sugestoes existentes -->
    <div class="mt-3">
        <span class="text-xs text-gray-500">Sugestoes:</span>
        <div class="flex flex-wrap gap-1 mt-1">
            <?php foreach ($existingCuisines as $cuisine): ?>
                <button type="button" 
                        onclick="addCuisineFromSuggestion('<?= htmlspecialchars($cuisine, ENT_QUOTES) ?>')"
                        class="cuisine-suggestion px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-xs transition">
                    <?= htmlspecialchars($cuisine) ?>
                </button>
            <?php endforeach; ?>
            <?php if (empty($existingCuisines)): ?>
                <span class="text-xs text-gray-500 italic">Nenhum tipo cadastrado ainda</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Campo hidden para enviar ao form -->
    <input type="hidden" name="cuisine_types" id="cuisine-types-hidden" value="">
</div>
```

### 3. JavaScript para Gerenciar Tags

```javascript
let selectedCuisines = [];

function updateCuisinesDisplay() {
    const container = document.getElementById('selected-cuisines');
    const hidden = document.getElementById('cuisine-types-hidden');
    
    container.innerHTML = selectedCuisines.map(c => `
        <span class="bg-orange-600/20 text-orange-400 px-2 py-1 rounded text-sm flex items-center gap-1">
            ${escapeHtml(c)}
            <button type="button" onclick="removeCuisine('${escapeHtml(c)}')" 
                    class="hover:text-red-400">&times;</button>
        </span>
    `).join('');
    
    hidden.value = selectedCuisines.join(',');
    
    // Esconder sugestoes ja selecionadas
    document.querySelectorAll('.cuisine-suggestion').forEach(btn => {
        if (selectedCuisines.includes(btn.textContent.trim())) {
            btn.classList.add('hidden');
        } else {
            btn.classList.remove('hidden');
        }
    });
}

function addCuisine() {
    const input = document.getElementById('new-cuisine-input');
    const value = input.value.trim();
    
    if (value && !selectedCuisines.includes(value)) {
        selectedCuisines.push(value);
        updateCuisinesDisplay();
    }
    input.value = '';
    input.focus();
}

function addCuisineFromSuggestion(cuisine) {
    if (!selectedCuisines.includes(cuisine)) {
        selectedCuisines.push(cuisine);
        updateCuisinesDisplay();
    }
}

function removeCuisine(cuisine) {
    selectedCuisines = selectedCuisines.filter(c => c !== cuisine);
    updateCuisinesDisplay();
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Permitir adicionar com Enter
document.getElementById('new-cuisine-input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCuisine();
    }
});
```

---

## Arquivo a Modificar

```text
docs/php/master/directory.php
```

---

## Beneficios

1. **Consistencia**: Evita duplicatas como "Italiana" vs "italiana" vs "Italiaana"
2. **Agilidade**: Clique rapido nas sugestoes em vez de digitar
3. **Flexibilidade**: Ainda permite criar novos tipos quando necessario
4. **Sem nova tabela**: Usa DISTINCT diretamente na coluna JSON existente

---

## Secao Tecnica

### Por que DISTINCT no JSON?
O campo `cuisine_types` armazena um array JSON (ex: `["Italiana", "Pizza"]`). A query busca todos os registros e o PHP extrai valores unicos:

```php
// Cada row retorna algo como: '["Italiana", "Pizza"]'
// O PHP decodifica e agrupa em um array unico
```

### Processamento no Backend
O campo hidden envia valores separados por virgula (`Italiana,Pizza,Nova`), e o PHP converte para JSON antes de salvar:

```php
$cuisineTypes = json_encode(array_filter(explode(',', $_POST['cuisine_types'] ?? '')));
```

Isso ja esta implementado na acao `create` (linha 35).

