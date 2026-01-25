
## Guia Gastronômico - Cadastro Geral de Restaurantes

### Visão Estratégica

Essa funcionalidade abre **3 oportunidades de negócio**:

1. **Ferramenta de Prospecção**: Você cadastra restaurantes da região e pode acompanhar quais ainda não são clientes, facilitando o contato comercial.

2. **Guia Gastronômico Público**: Uma página pública onde qualquer pessoa pode buscar restaurantes por tipo de comida, bairro, etc. Isso gera tráfego orgânico e posiciona a marca.

3. **Upsell Natural**: Restaurantes do guia que ainda não são clientes verão seus concorrentes com cardápio digital e podem se interessar.

### Estrutura Proposta

Criar uma entidade separada chamada **"Estabelecimentos"** (ou "Diretório") que é independente dos restaurantes clientes:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| name | string | Nome do restaurante |
| slug | string | URL amigável |
| address | string | Endereço completo |
| neighborhood | string | Bairro (para filtro) |
| city | string | Cidade |
| cuisineType | string[] | Tipos de comida (Italiana, Japonesa, etc) |
| logo | string | Logotipo |
| phone | string | Telefone |
| whatsapp | string | WhatsApp |
| instagram | string | Instagram |
| openingHours | objeto | Horário de funcionamento |
| priceRange | enum | Faixa de preço ($, $$, $$$, $$$$) |
| isClient | boolean | É cliente Premium Menu? |
| clientId | string | ID do restaurante cliente (se aplicável) |
| status | enum | Ativo, Pendente, Rascunho |
| notes | string | Notas internas para prospecção |

### Arquivos a Criar/Modificar

**Novos Arquivos:**

| Arquivo | Função |
|---------|--------|
| `src/pages/master/MasterDirectory.tsx` | Gerenciamento do diretório no Master Admin |
| `src/pages/DirectoryPage.tsx` | Página pública do guia gastronômico |
| `src/components/directory/RestaurantCard.tsx` | Card para exibir restaurante no guia |
| `src/components/directory/DirectoryFilters.tsx` | Filtros: tipo de comida, bairro, preço |
| `src/components/directory/DirectorySearch.tsx` | Busca por nome |

**Arquivos a Modificar:**

| Arquivo | Alteração |
|---------|-----------|
| `src/App.tsx` | Adicionar rotas `/guia` e `/master/directory` |
| `src/pages/master/MasterDashboard.tsx` | Adicionar card de acesso ao Diretório |
| `docs/database/schema.sql` | Adicionar tabela `directory_restaurants` |

### Fluxo de Uso

```text
+------------------+     +------------------+     +------------------+
| Master Admin     | --> | Cadastra no      | --> | Restaurante      |
| acessa Diretório |     | Diretório        |     | aparece no Guia  |
+------------------+     +------------------+     +------------------+
                                |
                                v
                         +------------------+
                         | Se fechar        |
                         | contrato, marca  |
                         | como "Cliente"   |
                         +------------------+
```

### Layout do Guia Público

- **Header**: Logo Premium Menu + busca
- **Filtros laterais**: Tipo de comida, Bairro, Faixa de preço, Horário
- **Grid de cards**: Foto, nome, tipo, endereço, badge "Cardápio Digital" se for cliente
- **Card expandido**: Ao clicar, mostra detalhes + botão "Ver Cardápio" se for cliente

### Diferencial Visual para Clientes

Restaurantes que são clientes Premium Menu terão:
- Badge especial "Cardápio Digital Disponível"
- Botão direto para acessar o menu
- Destaque visual (borda ou selo)

Isso incentiva outros restaurantes a também contratar.

### Benefícios

- **SEO**: Páginas de guia geram tráfego orgânico
- **Prospecção organizada**: Visualize quem ainda não é cliente
- **Prova social**: Clientes aparecem em destaque
- **Valor agregado**: Restaurantes clientes ganham exposição extra

### Ordem de Implementação

1. Criar página de gerenciamento no Master Admin (`MasterDirectory.tsx`)
2. Implementar CRUD completo com campos do diretório
3. Criar página pública do guia (`DirectoryPage.tsx`)
4. Implementar filtros e busca
5. Adicionar integração: vincular estabelecimento a cliente existente
6. Adicionar rota no `App.tsx`
7. Atualizar schema do banco para PHP

### Seção Técnica

**Interface TypeScript para o Diretório:**

```typescript
interface DirectoryRestaurant {
  id: string;
  name: string;
  slug: string;
  address: string;
  neighborhood: string;
  city: string;
  cuisineTypes: string[]; // ["Italiana", "Pizza"]
  logo: string;
  phone: string;
  whatsapp: string;
  instagram: string;
  website: string;
  openingHours: {
    monday?: { open: string; close: string };
    tuesday?: { open: string; close: string };
    // ...
  };
  priceRange: "$" | "$$" | "$$$" | "$$$$";
  isClient: boolean;
  linkedClientId?: string;
  status: "active" | "pending" | "draft";
  internalNotes: string;
  createdAt: string;
  updatedAt: string;
}
```

**Nova tabela SQL:**

```sql
CREATE TABLE directory_restaurants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  address TEXT,
  neighborhood VARCHAR(100),
  city VARCHAR(100) DEFAULT 'São Paulo',
  cuisine_types JSON,
  logo VARCHAR(500),
  phone VARCHAR(30),
  whatsapp VARCHAR(30),
  instagram VARCHAR(100),
  website VARCHAR(255),
  opening_hours JSON,
  price_range ENUM('$', '$$', '$$$', '$$$$') DEFAULT '$$',
  is_client TINYINT(1) DEFAULT 0,
  linked_restaurant_id INT UNSIGNED NULL,
  status ENUM('active', 'pending', 'draft') DEFAULT 'draft',
  internal_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (linked_restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL
);
```

