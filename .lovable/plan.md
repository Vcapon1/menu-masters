

## Renomear para "Cardápio Floripa" + Ajustar Hero

### Resumo das Alterações

Duas mudanças principais:
1. **Trocar nome da marca** de "Premium Menu" para "Cardápio Floripa" em todos os lugares
2. **Tornar o overlay do hero mais leve** para a imagem de fundo ficar mais visível

---

### Arquivos a Modificar

| Arquivo | Alterações |
|---------|-----------|
| `src/components/Header.tsx` | Trocar logo "P" → "C" e nome "PremiumMenu" → "CardápioFloripa" |
| `src/components/Footer.tsx` | Trocar logo, nome, email (contato@cardapiofloripa.com) e copyright |
| `src/pages/Index.tsx` | Trocar referências "Premium Menu" nas seções Vantagens e Diferenciais + ajustar overlays do hero |
| `src/pages/DirectoryPage.tsx` | Trocar "by Premium Menu" → "by Cardápio Floripa" |
| `src/pages/MenuPage.tsx` | Trocar rodapé "Premium Menu" |
| `src/pages/MenuBoldPage.tsx` | Trocar rodapé "Premium Menu" |
| `src/pages/master/TemplatePreview.tsx` | Trocar rodapé "Premium Menu" |
| `src/components/WhatsAppFloat.tsx` | Trocar mensagem padrão do WhatsApp |

---

### Detalhes do Hero (Index.tsx)

**Overlay atual (linhas 136-137):**
```jsx
<div className="absolute inset-0 bg-gradient-to-r from-background via-background/95 to-background/70" />
<div className="absolute inset-0 bg-gradient-to-t from-background via-transparent to-background/50" />
```

**Overlay proposto (mais leve):**
```jsx
<div className="absolute inset-0 bg-gradient-to-r from-background via-background/80 to-background/40" />
<div className="absolute inset-0 bg-gradient-to-t from-background via-transparent to-background/30" />
```

Alterações:
- Gradiente horizontal: `via-background/95` → `via-background/80` e `to-background/70` → `to-background/40`
- Gradiente vertical: `to-background/50` → `to-background/30`

---

### Padrão Visual do Nome

- **Logo**: Letra "C" no ícone (ao invés de "P")
- **Texto**: "Cardápio" em branco + "Floripa" em laranja (cor primária)
- **Email**: contato@cardapiofloripa.com
- **Copyright**: © 2026 Cardápio Floripa

---

### Resultado Esperado

- Nome "Cardápio Floripa" consistente em toda a aplicação
- Hero com imagem de fundo mais visível, mantendo legibilidade do texto
- Identidade visual regional para Florianópolis

