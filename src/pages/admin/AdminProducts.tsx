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
  Clock,
  GripVertical,
  Video,
  ChevronUp,
  ChevronDown,
  Eye,
  EyeOff
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
  promoPrice: number | null;
  categoryId: string;
  image: string | null;
  video: string | null;
  badges: string[];
  isAvailable: boolean;
  hideWhenUnavailable: boolean;
  order: number;
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
    promoPrice: "",
    categoryId: "",
    image: null as string | null,
    video: null as string | null,
    badges: [] as string[],
    isAvailable: true,
    hideWhenUnavailable: false
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
    
    if (storedProducts) {
      const parsed = JSON.parse(storedProducts);
      // Migrate old products to include new fields
      const migrated = parsed.map((p: Product, index: number) => ({
        ...p,
        promoPrice: p.promoPrice ?? null,
        video: p.video ?? null,
        hideWhenUnavailable: p.hideWhenUnavailable ?? false,
        order: p.order ?? index
      }));
      setProducts(migrated);
    }
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
      promoPrice: "",
      categoryId: "",
      image: null,
      video: null,
      badges: [],
      isAvailable: true,
      hideWhenUnavailable: false
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

  const handleVideoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 50 * 1024 * 1024) {
        toast({
          title: "Arquivo muito grande",
          description: "O vídeo deve ter no máximo 50MB",
          variant: "destructive"
        });
        return;
      }

      const reader = new FileReader();
      reader.onloadend = () => {
        setFormData(prev => ({ ...prev, video: reader.result as string }));
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

    let promoPrice: number | null = null;
    if (formData.promoPrice.trim()) {
      promoPrice = parseFloat(formData.promoPrice.replace(",", "."));
      if (isNaN(promoPrice) || promoPrice <= 0) {
        toast({
          title: "Erro",
          description: "Preço promocional inválido",
          variant: "destructive"
        });
        return;
      }
      if (promoPrice >= price) {
        toast({
          title: "Erro",
          description: "O preço promocional deve ser menor que o preço normal",
          variant: "destructive"
        });
        return;
      }
    }

    if (editingProduct) {
      const updated = products.map(prod =>
        prod.id === editingProduct.id
          ? { 
              ...prod, 
              name: formData.name.trim(),
              description: formData.description.trim(),
              price,
              promoPrice,
              categoryId: formData.categoryId,
              image: formData.image,
              video: formData.video,
              badges: formData.badges,
              isAvailable: formData.isAvailable,
              hideWhenUnavailable: formData.hideWhenUnavailable
            }
          : prod
      );
      saveProducts(updated);
      toast({
        title: "Prato atualizado!",
        description: `"${formData.name}" foi atualizado com sucesso.`
      });
    } else {
      const maxOrder = products.length > 0 ? Math.max(...products.map(p => p.order)) : -1;
      const newProduct: Product = {
        id: Date.now().toString(),
        name: formData.name.trim(),
        description: formData.description.trim(),
        price,
        promoPrice,
        categoryId: formData.categoryId,
        image: formData.image,
        video: formData.video,
        badges: formData.badges,
        isAvailable: formData.isAvailable,
        hideWhenUnavailable: formData.hideWhenUnavailable,
        order: maxOrder + 1,
        createdAt: new Date().toISOString()
      };
      saveProducts([...products, newProduct]);
      toast({
        title: "Prato criado!",
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
      promoPrice: product.promoPrice?.toString().replace(".", ",") || "",
      categoryId: product.categoryId,
      image: product.image,
      video: product.video,
      badges: product.badges,
      isAvailable: product.isAvailable,
      hideWhenUnavailable: product.hideWhenUnavailable ?? false
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (id: string) => {
    const product = products.find(p => p.id === id);
    const updated = products.filter(prod => prod.id !== id);
    saveProducts(updated);
    setDeleteId(null);
    toast({
      title: "Prato removido",
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

  const moveProduct = (productId: string, direction: "up" | "down") => {
    const sortedProducts = [...products].sort((a, b) => a.order - b.order);
    const index = sortedProducts.findIndex(p => p.id === productId);
    
    if (direction === "up" && index > 0) {
      const prevProduct = sortedProducts[index - 1];
      const currentProduct = sortedProducts[index];
      const tempOrder = prevProduct.order;
      prevProduct.order = currentProduct.order;
      currentProduct.order = tempOrder;
    } else if (direction === "down" && index < sortedProducts.length - 1) {
      const nextProduct = sortedProducts[index + 1];
      const currentProduct = sortedProducts[index];
      const tempOrder = nextProduct.order;
      nextProduct.order = currentProduct.order;
      currentProduct.order = tempOrder;
    }
    
    saveProducts(sortedProducts);
  };

  const filteredProducts = (filterCategory === "all"
    ? products
    : products.filter(p => p.categoryId === filterCategory)
  ).sort((a, b) => a.order - b.order);

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
              <h1 className="font-bold text-lg">Pratos</h1>
              <p className="text-xs text-muted-foreground">{products.length} pratos cadastrados</p>
            </div>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => {
            setIsDialogOpen(open);
            if (!open) resetForm();
          }}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Novo Prato
              </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>
                  {editingProduct ? "Editar Prato" : "Novo Prato"}
                </DialogTitle>
                <DialogDescription>
                  {editingProduct 
                    ? "Atualize as informações do prato"
                    : "Adicione um novo item ao cardápio"}
                </DialogDescription>
              </DialogHeader>
              <form onSubmit={handleSubmit} className="space-y-4">
                {/* Image Upload */}
                <div className="space-y-2">
                  <Label>Foto do Prato</Label>
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

                {/* Video Upload */}
                <div className="space-y-2">
                  <Label>Vídeo do Prato (opcional)</Label>
                  <div className="relative">
                    {formData.video ? (
                      <div className="relative w-full h-40 rounded-lg overflow-hidden bg-black">
                        <video 
                          src={formData.video}
                          className="w-full h-full object-cover"
                          muted
                          loop
                          playsInline
                        />
                        <button
                          type="button"
                          onClick={() => setFormData(prev => ({ ...prev, video: null }))}
                          className="absolute top-2 right-2 bg-black/50 text-white p-1 rounded-full hover:bg-black/70"
                        >
                          <X className="w-4 h-4" />
                        </button>
                        <div className="absolute bottom-2 left-2 bg-black/50 text-white px-2 py-1 rounded text-xs flex items-center gap-1">
                          <Video className="w-3 h-3" />
                          Vídeo carregado
                        </div>
                      </div>
                    ) : (
                      <label className="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed rounded-lg cursor-pointer hover:bg-muted/50 transition-colors">
                        <Video className="w-6 h-6 text-muted-foreground mb-1" />
                        <span className="text-sm text-muted-foreground">Clique para enviar vídeo</span>
                        <span className="text-xs text-muted-foreground">MP4, WebM até 50MB</span>
                        <input
                          type="file"
                          accept="video/mp4,video/webm"
                          onChange={handleVideoUpload}
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

                {/* Price Fields */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="price">Preço (R$) *</Label>
                    <Input
                      id="price"
                      placeholder="29,90"
                      value={formData.price}
                      onChange={(e) => setFormData(prev => ({ ...prev, price: e.target.value }))}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="promoPrice">Preço Promocional (R$)</Label>
                    <Input
                      id="promoPrice"
                      placeholder="24,90"
                      value={formData.promoPrice}
                      onChange={(e) => setFormData(prev => ({ ...prev, promoPrice: e.target.value }))}
                    />
                  </div>
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
                <div className="space-y-3 pt-2 border-t">
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="available"
                      checked={formData.isAvailable}
                      onCheckedChange={(checked) => 
                        setFormData(prev => ({ ...prev, isAvailable: checked as boolean }))
                      }
                    />
                    <Label htmlFor="available" className="cursor-pointer">
                      Prato disponível
                    </Label>
                  </div>
                  
                  <div className="flex items-center space-x-2">
                    <Checkbox
                      id="hideWhenUnavailable"
                      checked={formData.hideWhenUnavailable}
                      onCheckedChange={(checked) => 
                        setFormData(prev => ({ ...prev, hideWhenUnavailable: checked as boolean }))
                      }
                    />
                    <Label htmlFor="hideWhenUnavailable" className="cursor-pointer text-sm">
                      Ocultar do cardápio quando indisponível
                    </Label>
                  </div>
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
                    {editingProduct ? "Salvar" : "Criar Prato"}
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

      {/* Main Content - List View */}
      <main className="container mx-auto px-4 pb-8">
        {products.length === 0 ? (
          <Card className="text-center py-12">
            <CardContent>
              <div className="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                <Package className="w-8 h-8 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-semibold mb-2">Nenhum prato</h3>
              <p className="text-muted-foreground mb-4">
                {categories.length === 0 
                  ? "Crie categorias primeiro, depois adicione pratos"
                  : "Comece adicionando pratos ao seu cardápio"}
              </p>
              {categories.length === 0 ? (
                <Button onClick={() => navigate("/admin/categories")}>
                  Criar Categorias
                </Button>
              ) : (
                <Button onClick={() => setIsDialogOpen(true)}>
                  <Plus className="w-4 h-4 mr-2" />
                  Adicionar Prato
                </Button>
              )}
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-2">
            {filteredProducts.map((product, index) => (
              <Card 
                key={product.id} 
                className={`overflow-hidden ${!product.isAvailable ? "opacity-60" : ""}`}
              >
                <CardContent className="p-3">
                  <div className="flex items-center gap-3">
                    {/* Order Controls */}
                    <div className="flex flex-col gap-0.5">
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={() => moveProduct(product.id, "up")}
                        disabled={index === 0}
                      >
                        <ChevronUp className="w-4 h-4" />
                      </Button>
                      <div className="flex items-center justify-center">
                        <GripVertical className="w-4 h-4 text-muted-foreground" />
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={() => moveProduct(product.id, "down")}
                        disabled={index === filteredProducts.length - 1}
                      >
                        <ChevronDown className="w-4 h-4" />
                      </Button>
                    </div>

                    {/* Image/Video Thumbnail */}
                    <div className="relative w-16 h-16 rounded-lg overflow-hidden bg-muted shrink-0">
                      {product.video ? (
                        <video 
                          src={product.video}
                          className="w-full h-full object-cover"
                          muted
                        />
                      ) : product.image ? (
                        <img 
                          src={product.image} 
                          alt={product.name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center">
                          <Package className="w-6 h-6 text-muted-foreground/50" />
                        </div>
                      )}
                      {product.video && (
                        <div className="absolute bottom-0.5 right-0.5 bg-black/70 rounded p-0.5">
                          <Video className="w-3 h-3 text-white" />
                        </div>
                      )}
                      {!product.isAvailable && (
                        <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                          <EyeOff className="w-4 h-4 text-white" />
                        </div>
                      )}
                    </div>

                    {/* Product Info */}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <h3 className="font-semibold text-sm line-clamp-1">{product.name}</h3>
                          <p className="text-xs text-muted-foreground">{getCategoryName(product.categoryId)}</p>
                        </div>
                        <div className="text-right shrink-0">
                          {product.promoPrice ? (
                            <>
                              <span className="text-xs text-muted-foreground line-through block">
                                R$ {product.price.toFixed(2).replace(".", ",")}
                              </span>
                              <span className="font-bold text-sm text-green-600">
                                R$ {product.promoPrice.toFixed(2).replace(".", ",")}
                              </span>
                            </>
                          ) : (
                            <span className="font-bold text-sm text-primary">
                              R$ {product.price.toFixed(2).replace(".", ",")}
                            </span>
                          )}
                        </div>
                      </div>

                      {/* Badges */}
                      {product.badges.length > 0 && (
                        <div className="flex flex-wrap gap-1 mt-1">
                          {product.badges.map(badgeId => {
                            const badge = AVAILABLE_BADGES.find(b => b.id === badgeId);
                            if (!badge) return null;
                            return (
                              <span 
                                key={badgeId}
                                className={`${badge.color} text-white text-[10px] px-1.5 py-0.5 rounded-full inline-flex items-center gap-0.5`}
                              >
                                <badge.icon className="w-2.5 h-2.5" />
                                {badge.label}
                              </span>
                            );
                          })}
                          {!product.isAvailable && (
                            <Badge variant="secondary" className="text-[10px] px-1.5 py-0.5">
                              Indisponível
                            </Badge>
                          )}
                          {product.hideWhenUnavailable && !product.isAvailable && (
                            <Badge variant="outline" className="text-[10px] px-1.5 py-0.5">
                              Oculto
                            </Badge>
                          )}
                        </div>
                      )}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-1 shrink-0">
                      <Button 
                        variant="ghost" 
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => handleEdit(product)}
                      >
                        <Pencil className="w-4 h-4" />
                      </Button>
                      <Button 
                        variant="ghost" 
                        size="icon"
                        className="h-8 w-8 text-destructive hover:text-destructive"
                        onClick={() => setDeleteId(product.id)}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
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
            <AlertDialogTitle>Excluir prato?</AlertDialogTitle>
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
