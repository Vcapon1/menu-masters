import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { 
  ArrowLeft, 
  Plus, 
  Pencil, 
  Trash2,
  Package,
  Upload,
  X,
  Star,
  Leaf,
  Flame,
  Clock
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface Category {
  id: string;
  name: string;
}

interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  categoryId: string;
  image: string | null;
  badges: string[];
  isAvailable: boolean;
  createdAt: string;
}

const AVAILABLE_BADGES = [
  { id: "promo", label: "Promoção", icon: Flame, color: "bg-red-500" },
  { id: "vegan", label: "Vegano", icon: Leaf, color: "bg-green-500" },
  { id: "vegetarian", label: "Vegetariano", icon: Leaf, color: "bg-green-400" },
  { id: "highlight", label: "Destaque", icon: Star, color: "bg-yellow-500" },
  { id: "new", label: "Novo", icon: Clock, color: "bg-blue-500" },
];

export default function AdminProducts() {
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [filterCategory, setFilterCategory] = useState<string>("all");
  
  // Form state
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    categoryId: "",
    image: null as string | null,
    badges: [] as string[],
    isAvailable: true
  });

  const navigate = useNavigate();
  const { toast } = useToast();

  useEffect(() => {
    const session = localStorage.getItem("adminSession");
    if (!session) {
      navigate("/admin");
      return;
    }
    
    // Load data from localStorage
    const storedProducts = localStorage.getItem("products");
    const storedCategories = localStorage.getItem("categories");
    
    if (storedProducts) setProducts(JSON.parse(storedProducts));
    if (storedCategories) setCategories(JSON.parse(storedCategories));
  }, [navigate]);

  const saveProducts = (newProducts: Product[]) => {
    localStorage.setItem("products", JSON.stringify(newProducts));
    setProducts(newProducts);
  };

  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
      price: "",
      categoryId: "",
      image: null,
      badges: [],
      isAvailable: true
    });
    setEditingProduct(null);
  };

  const handleImageUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        toast({
          title: "Arquivo muito grande",
          description: "A imagem deve ter no máximo 5MB",
          variant: "destructive"
        });
        return;
      }

      const reader = new FileReader();
      reader.onloadend = () => {
        setFormData(prev => ({ ...prev, image: reader.result as string }));
      };
      reader.readAsDataURL(file);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.name.trim() || !formData.price || !formData.categoryId) {
      toast({
        title: "Erro",
        description: "Preencha todos os campos obrigatórios",
        variant: "destructive"
      });
      return;
    }

    const price = parseFloat(formData.price.replace(",", "."));
    if (isNaN(price) || price <= 0) {
      toast({
        title: "Erro",
        description: "Preço inválido",
        variant: "destructive"
      });
      return;
    }

    if (editingProduct) {
      const updated = products.map(prod =>
        prod.id === editingProduct.id
          ? { 
              ...prod, 
              name: formData.name.trim(),
              description: formData.description.trim(),
              price,
              categoryId: formData.categoryId,
              image: formData.image,
              badges: formData.badges,
              isAvailable: formData.isAvailable
            }
          : prod
      );
      saveProducts(updated);
      toast({
        title: "Produto atualizado!",
        description: `"${formData.name}" foi atualizado com sucesso.`
      });
    } else {
      const newProduct: Product = {
        id: Date.now().toString(),
        name: formData.name.trim(),
        description: formData.description.trim(),
        price,
        categoryId: formData.categoryId,
        image: formData.image,
        badges: formData.badges,
        isAvailable: formData.isAvailable,
        createdAt: new Date().toISOString()
      };
      saveProducts([...products, newProduct]);
      toast({
        title: "Produto criado!",
        description: `"${formData.name}" foi adicionado ao cardápio.`
      });
    }

    resetForm();
    setIsDialogOpen(false);
  };

  const handleEdit = (product: Product) => {
    setEditingProduct(product);
    setFormData({
      name: product.name,
      description: product.description,
      price: product.price.toString().replace(".", ","),
      categoryId: product.categoryId,
      image: product.image,
      badges: product.badges,
      isAvailable: product.isAvailable
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (id: string) => {
    const product = products.find(p => p.id === id);
    const updated = products.filter(prod => prod.id !== id);
    saveProducts(updated);
    setDeleteId(null);
    toast({
      title: "Produto removido",
      description: `"${product?.name}" foi excluído.`
    });
  };

  const toggleBadge = (badgeId: string) => {
    setFormData(prev => ({
      ...prev,
      badges: prev.badges.includes(badgeId)
        ? prev.badges.filter(b => b !== badgeId)
        : [...prev.badges, badgeId]
    }));
  };

  const getCategoryName = (categoryId: string) => {
    return categories.find(c => c.id === categoryId)?.name || "Sem categoria";
  };

  const filteredProducts = filterCategory === "all"
    ? products
    : products.filter(p => p.categoryId === filterCategory);

  return (
    <div className="min-h-screen bg-muted/30">
      {/* Header */}
      <header className="bg-card border-b sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Button variant="ghost" size="icon" onClick={() => navigate("/admin/dashboard")}>
              <ArrowLeft className="w-5 h-5" />
            </Button>
            <div>
              <h1 className="font-bold text-lg">Produtos</h1>
              <p className="text-xs text-muted-foreground">{products.length} produtos cadastrados</p>
            </div>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) resetForm();
          }}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Novo Produto
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>
                  {editingProduct ? "Editar Produto" : "Novo Produto"}
                </DialogTitle>
                <DialogDescription>
                  {editingProduct 
                    ? "Atualize as informações do produto"
                    : "Adicione um novo item ao cardápio"}
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                {/* Image Upload */}
                <div className="space-y-2">
                  <Label>Foto do Produto</Label>
                  <div className="relative">
                    {formData.image ? (
                      <div className="relative w-full h-40 rounded-lg overflow-hidden">
                        <img 
                          src={formData.image} 
                          alt="Preview" 
                          className="w-full h-full object-cover"
                        />
                        <button
                          type="button"
                          onClick={() => setFormData(prev => ({ ...prev, image: null }))}
                          className="absolute top-2 right-2 bg-black/50 text-white p-1 rounded-full hover:bg-black/70"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                    ) : (
                      <label className="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-lg cursor-pointer hover:bg-muted/50 transition-colors">
                        <Upload className="w-8 h-8 text-muted-foreground mb-2" />
                        <span className="text-sm text-muted-foreground">Clique para enviar foto</span>
                        <span className="text-xs text-muted-foreground">PNG, JPG até 5MB</span>
                        <input
                          type="file"
                          accept="image/*"
                          onChange={handleImageUpload}
                          className="hidden"
                        />
                      </label>
                    )}
                  </div>
                </div>

                {/* Name */}
                <div className="space-y-2">
                  <Label htmlFor="name">Nome *</Label>
                  <Input
                    id="name"
                    placeholder="Ex: X-Burger Especial"
                    value={formData.name}
                    onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                  />
                </div>

                {/* Description */}
                <div className="space-y-2">
                  <Label htmlFor="description">Descrição</Label>
                  <Textarea
                    id="description"
                    placeholder="Descreva os ingredientes e detalhes..."
                    value={formData.description}
                    onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                    rows={3}
                  />
                </div>

                {/* Price */}
                <div className="space-y-2">
                  <Label htmlFor="price">Preço (R$) *</Label>
                  <Input
                    id="price"
                    placeholder="29,90"
                    value={formData.price}
                    onChange={(e) => setFormData(prev => ({ ...prev, price: e.target.value }))}
                  />
                </div>

                {/* Category */}
                <div className="space-y-2">
                  <Label>Categoria *</Label>
                  {categories.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                      Nenhuma categoria. <Button variant="link" className="p-0 h-auto" onClick={() => navigate("/admin/categories")}>Criar categoria</Button>
                    </p>
                  ) : (
                    <Select 
                      value={formData.categoryId} 
                      onValueChange={(value) => setFormData(prev => ({ ...prev, categoryId: value }))}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Selecione uma categoria" />
                      </SelectTrigger>
                      <SelectContent>
                        {categories.map(cat => (
                          <SelectItem key={cat.id} value={cat.id}>{cat.name}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  )}
                </div>

                {/* Badges */}
                <div className="space-y-2">
                  <Label>Ícones / Tags</Label>
                  <div className="flex flex-wrap gap-2">
                    {AVAILABLE_BADGES.map(badge => (
                      <button
                        key={badge.id}
                        type="button"
                        onClick={() => toggleBadge(badge.id)}
                        className={`px-3 py-1.5 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 ${
                          formData.badges.includes(badge.id)
                            ? `${badge.color} text-white`
                            : "bg-muted text-muted-foreground hover:bg-muted/80"
                        }`}
                      >
                        <badge.icon className="w-3.5 h-3.5" />
                        {badge.label}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Availability */}
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="available"
                    checked={formData.isAvailable}
                    onCheckedChange={(checked) => 
                      setFormData(prev => ({ ...prev, isAvailable: checked as boolean }))
                    }
                  />
                  <Label htmlFor="available" className="cursor-pointer">
                    Produto disponível
                  </Label>
                </div>

                {/* Actions */}
                <div className="flex gap-2 justify-end pt-4">
                  <Button type="button" variant="outline" onClick={() => {
                    resetForm();
                    setIsDialogOpen(false);
                  }}>
                    Cancelar
                  </Button>
                  <Button type="submit">
                    {editingProduct ? "Salvar" : "Criar Produto"}
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </header>

      {/* Filter */}
      {categories.length > 0 && (
        <div className="container mx-auto px-4 py-4">
          <Select value={filterCategory} onValueChange={setFilterCategory}>
            <SelectTrigger className="w-full md:w-64">
              <SelectValue placeholder="Filtrar por categoria" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Todas as categorias</SelectItem>
              {categories.map(cat => (
                <SelectItem key={cat.id} value={cat.id}>{cat.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      )}

      {/* Main Content */}
      <main className="container mx-auto px-4 pb-8">
        {products.length === 0 ? (
          <Card className="text-center py-12">
            <CardContent>
              <div className="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                <Package className="w-8 h-8 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-semibold mb-2">Nenhum produto</h3>
              <p className="text-muted-foreground mb-4">
                {categories.length === 0 
                  ? "Crie categorias primeiro, depois adicione produtos"
                  : "Comece adicionando produtos ao seu cardápio"}
              </p>
              {categories.length === 0 ? (
                <Button onClick={() => navigate("/admin/categories")}>
                  Criar Categorias
                </Button>
              ) : (
                <Button onClick={() => setIsDialogOpen(true)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Adicionar Produto
                </Button>
              )}
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredProducts.map((product) => (
              <Card key={product.id} className={`overflow-hidden ${!product.isAvailable ? "opacity-60" : ""}`}>
                <div className="relative h-40 bg-muted">
                  {product.image ? (
                    <img 
                      src={product.image} 
                      alt={product.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <Package className="w-12 h-12 text-muted-foreground/50" />
                    </div>
                  )}
                  {!product.isAvailable && (
                    <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                      <Badge variant="secondary">Indisponível</Badge>
                    </div>
                  )}
                  {product.badges.length > 0 && (
                    <div className="absolute top-2 left-2 flex gap-1">
                      {product.badges.slice(0, 2).map(badgeId => {
                        const badge = AVAILABLE_BADGES.find(b => b.id === badgeId);
                        if (!badge) return null;
                        return (
                          <span 
                            key={badgeId}
                            className={`${badge.color} text-white text-xs px-2 py-0.5 rounded-full`}
                          >
                            {badge.label}
                          </span>
                        );
                      })}
                    </div>
                  )}
                </div>
                <CardContent className="p-4">
                  <div className="flex items-start justify-between gap-2 mb-2">
                    <div>
                      <h3 className="font-semibold line-clamp-1">{product.name}</h3>
                      <p className="text-xs text-muted-foreground">{getCategoryName(product.categoryId)}</p>
                    </div>
                    <span className="font-bold text-primary whitespace-nowrap">
                      R$ {product.price.toFixed(2).replace(".", ",")}
                    </span>
                  </div>
                  {product.description && (
                    <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                      {product.description}
                    </p>
                  )}
                  <div className="flex gap-2">
                    <Button 
                      variant="outline" 
                      size="sm" 
                      className="flex-1"
                      onClick={() => handleEdit(product)}
                    >
                      <Pencil className="w-3.5 h-3.5 mr-1" />
                      Editar
                    </Button>
                    <Button 
                      variant="outline" 
                      size="icon"
                      className="text-destructive hover:text-destructive shrink-0"
                      onClick={() => setDeleteId(product.id)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>

      {/* Delete Confirmation */}
      <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Excluir produto?</AlertDialogTitle>
            <AlertDialogDescription>
              Esta ação não pode ser desfeita.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              onClick={() => deleteId && handleDelete(deleteId)}
            >
              Excluir
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
