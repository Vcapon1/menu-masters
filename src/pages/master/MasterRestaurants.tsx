import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  ArrowLeft,
  Plus,
  Search,
  Edit,
  Trash2,
  ExternalLink,
  Store,
  Upload,
  Eye,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface Restaurant {
  id: string;
  name: string;
  slug: string;
  address: string;
  email: string;
  phone: string;
  template: string;
  logo: string;
  banner: string;
  backgroundVideo: string;
  primaryColor: string;
  secondaryColor: string;
  fontColor: string;
  plan: string;
  status: "active" | "inactive" | "pending";
  createdAt: string;
  expiresAt: string;
}

const templates = [
  { id: "visual", name: "Visual - Imagens Grandes" },
  { id: "classic", name: "Clássico - Equilibrado" },
  { id: "modern", name: "Moderno - Clean" },
  { id: "bold", name: "Bold - Alto Contraste" },
];

const plans = [
  { id: "basic", name: "Básico" },
  { id: "premium", name: "Premium" },
  { id: "personalite", name: "Personalité" },
];

const MasterRestaurants = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [restaurants, setRestaurants] = useState<Restaurant[]>([]);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRestaurant, setEditingRestaurant] = useState<Restaurant | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    slug: "",
    address: "",
    email: "",
    phone: "",
    template: "classic",
    logo: "",
    banner: "",
    backgroundVideo: "",
    primaryColor: "#dc2626",
    secondaryColor: "#fbbf24",
    fontColor: "#ffffff",
    plan: "basic",
    status: "pending" as "active" | "inactive" | "pending",
    expiresAt: "",
  });

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }
    loadRestaurants();
  }, [navigate]);

  const loadRestaurants = () => {
    const saved = localStorage.getItem("masterRestaurants");
    if (saved) {
      setRestaurants(JSON.parse(saved));
    } else {
      const mockData: Restaurant[] = [
        {
          id: "1",
          name: "Pizzaria Bella",
          slug: "pizzaria-bella",
          address: "Rua das Flores, 123 - Centro",
          email: "contato@bella.com",
          phone: "(11) 99999-0001",
          template: "bold",
          logo: "/placeholder.svg",
          banner: "/placeholder.svg",
          backgroundVideo: "",
          primaryColor: "#dc2626",
          secondaryColor: "#fbbf24",
          fontColor: "#ffffff",
          plan: "premium",
          status: "active",
          createdAt: "2024-01-15",
          expiresAt: "2025-01-15",
        },
        {
          id: "2",
          name: "Burger House",
          slug: "burger-house",
          address: "Av. Principal, 456 - Jardins",
          email: "contato@burger.com",
          phone: "(11) 99999-0002",
          template: "classic",
          logo: "/placeholder.svg",
          banner: "/placeholder.svg",
          backgroundVideo: "",
          primaryColor: "#16a34a",
          secondaryColor: "#22c55e",
          fontColor: "#ffffff",
          plan: "basic",
          status: "active",
          createdAt: "2024-02-10",
          expiresAt: "2025-02-10",
        },
      ];
      setRestaurants(mockData);
      localStorage.setItem("masterRestaurants", JSON.stringify(mockData));
    }
  };

  const generateSlug = (name: string) => {
    return name
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/(^-|-$)/g, "");
  };

  const handleNameChange = (name: string) => {
    setFormData({
      ...formData,
      name,
      slug: editingRestaurant ? formData.slug : generateSlug(name),
    });
  };

  const handleSave = () => {
    if (!formData.name || !formData.email) {
      toast({ title: "Erro", description: "Preencha os campos obrigatórios", variant: "destructive" });
      return;
    }

    let updated: Restaurant[];
    if (editingRestaurant) {
      updated = restaurants.map((r) =>
        r.id === editingRestaurant.id ? { ...r, ...formData } : r
      );
      toast({ title: "Sucesso", description: "Restaurante atualizado" });
    } else {
      const newRestaurant: Restaurant = {
        id: Date.now().toString(),
        ...formData,
        createdAt: new Date().toISOString().split("T")[0],
      };
      updated = [...restaurants, newRestaurant];
      toast({ title: "Sucesso", description: "Restaurante criado" });
    }

    setRestaurants(updated);
    localStorage.setItem("masterRestaurants", JSON.stringify(updated));
    setIsDialogOpen(false);
    resetForm();
  };

  const handleDelete = (id: string) => {
    if (!confirm("Tem certeza que deseja excluir este restaurante?")) return;
    const updated = restaurants.filter((r) => r.id !== id);
    setRestaurants(updated);
    localStorage.setItem("masterRestaurants", JSON.stringify(updated));
    toast({ title: "Sucesso", description: "Restaurante removido" });
  };

  const handleEdit = (restaurant: Restaurant) => {
    setEditingRestaurant(restaurant);
    setFormData({
      name: restaurant.name,
      slug: restaurant.slug,
      address: restaurant.address,
      email: restaurant.email,
      phone: restaurant.phone,
      template: restaurant.template,
      logo: restaurant.logo,
      banner: restaurant.banner,
      backgroundVideo: restaurant.backgroundVideo,
      primaryColor: restaurant.primaryColor,
      secondaryColor: restaurant.secondaryColor,
      fontColor: restaurant.fontColor,
      plan: restaurant.plan,
      status: restaurant.status,
      expiresAt: restaurant.expiresAt,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingRestaurant(null);
    setFormData({
      name: "",
      slug: "",
      address: "",
      email: "",
      phone: "",
      template: "classic",
      logo: "",
      banner: "",
      backgroundVideo: "",
      primaryColor: "#dc2626",
      secondaryColor: "#fbbf24",
      fontColor: "#ffffff",
      plan: "basic",
      status: "pending",
      expiresAt: "",
    });
  };

  const handleFileChange = (field: "logo" | "banner", e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onloadend = () => {
        setFormData({ ...formData, [field]: reader.result as string });
      };
      reader.readAsDataURL(file);
    }
  };

  const filteredRestaurants = restaurants.filter((r) =>
    r.name.toLowerCase().includes(search.toLowerCase()) ||
    r.email.toLowerCase().includes(search.toLowerCase()) ||
    r.slug.toLowerCase().includes(search.toLowerCase())
  );

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "active":
        return <Badge className="bg-green-600">Ativo</Badge>;
      case "inactive":
        return <Badge variant="secondary">Inativo</Badge>;
      case "pending":
        return <Badge className="bg-yellow-600">Pendente</Badge>;
      default:
        return null;
    }
  };

  const getPlanBadge = (plan: string) => {
    switch (plan) {
      case "basic":
        return <Badge variant="outline" className="border-slate-500 text-slate-300">Básico</Badge>;
      case "premium":
        return <Badge className="bg-blue-600">Premium</Badge>;
      case "personalite":
        return <Badge className="bg-purple-600">Personalité</Badge>;
      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      {/* Header */}
      <header className="bg-slate-800/50 backdrop-blur-sm border-b border-purple-500/30">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Link to="/master/dashboard">
              <Button variant="ghost" size="icon" className="text-slate-300 hover:text-white">
                <ArrowLeft className="w-5 h-5" />
              </Button>
            </Link>
            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <Store className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">Restaurantes</h1>
              <p className="text-xs text-slate-400">{restaurants.length} cadastrados</p>
            </div>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
            <DialogTrigger asChild>
              <Button className="bg-purple-600 hover:bg-purple-700">
                <Plus className="w-4 h-4 mr-2" />
                Novo Restaurante
              </Button>
            </DialogTrigger>
            <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-2xl max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>{editingRestaurant ? "Editar" : "Novo"} Restaurante</DialogTitle>
              </DialogHeader>
              <div className="space-y-6">
                {/* Informações Básicas */}
                <div className="space-y-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Informações Básicas</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Nome *</Label>
                      <Input
                        value={formData.name}
                        onChange={(e) => handleNameChange(e.target.value)}
                        className="bg-slate-700 border-slate-600"
                        placeholder="Ex: Burger House"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Slug (URL)</Label>
                      <Input
                        value={formData.slug}
                        onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                        className="bg-slate-700 border-slate-600"
                        placeholder="burger-house"
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Endereço</Label>
                    <Input
                      value={formData.address}
                      onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                      className="bg-slate-700 border-slate-600"
                      placeholder="Rua das Flores, 123 - Centro"
                    />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Email *</Label>
                      <Input
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        className="bg-slate-700 border-slate-600"
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Telefone</Label>
                      <Input
                        value={formData.phone}
                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                        className="bg-slate-700 border-slate-600"
                      />
                    </div>
                  </div>
                </div>

                {/* Imagens e Mídia */}
                <div className="space-y-4 border-t border-slate-700 pt-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Imagens e Mídia</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Logotipo</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => handleFileChange("logo", e)}
                          className="hidden"
                          id="logo-upload"
                        />
                        <label
                          htmlFor="logo-upload"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-700 border border-slate-600 rounded-md cursor-pointer hover:bg-slate-600 transition-colors"
                        >
                          <Upload className="h-4 w-4" />
                          Enviar Logo
                        </label>
                        {formData.logo && (
                          <img src={formData.logo} alt="Logo" className="h-10 w-10 object-cover rounded" />
                        )}
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Banner</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => handleFileChange("banner", e)}
                          className="hidden"
                          id="banner-upload"
                        />
                        <label
                          htmlFor="banner-upload"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-700 border border-slate-600 rounded-md cursor-pointer hover:bg-slate-600 transition-colors"
                        >
                          <Upload className="h-4 w-4" />
                          Enviar Banner
                        </label>
                        {formData.banner && (
                          <img src={formData.banner} alt="Banner" className="h-10 w-20 object-cover rounded" />
                        )}
                      </div>
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>URL do Vídeo de Fundo (opcional)</Label>
                    <Input
                      value={formData.backgroundVideo}
                      onChange={(e) => setFormData({ ...formData, backgroundVideo: e.target.value })}
                      className="bg-slate-700 border-slate-600"
                      placeholder="https://exemplo.com/video.mp4"
                    />
                  </div>
                </div>

                {/* Cores do Tema */}
                <div className="space-y-4 border-t border-slate-700 pt-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Cores do Tema</h3>
                  <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-2">
                      <Label>Cor Primária</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.primaryColor}
                          onChange={(e) => setFormData({ ...formData, primaryColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.primaryColor}
                          onChange={(e) => setFormData({ ...formData, primaryColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Cor Secundária</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.secondaryColor}
                          onChange={(e) => setFormData({ ...formData, secondaryColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.secondaryColor}
                          onChange={(e) => setFormData({ ...formData, secondaryColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Cor da Fonte</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.fontColor}
                          onChange={(e) => setFormData({ ...formData, fontColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.fontColor}
                          onChange={(e) => setFormData({ ...formData, fontColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600"
                        />
                      </div>
                    </div>
                  </div>
                  {/* Preview das cores */}
                  <div
                    className="p-4 rounded-lg flex items-center justify-center gap-4"
                    style={{ backgroundColor: formData.primaryColor }}
                  >
                    <span style={{ color: formData.fontColor }} className="font-bold">
                      Preview do Texto
                    </span>
                    <span
                      className="px-3 py-1 rounded-full text-sm font-medium"
                      style={{ backgroundColor: formData.secondaryColor, color: formData.primaryColor }}
                    >
                      Badge
                    </span>
                  </div>
                </div>

                {/* Template e Plano */}
                <div className="space-y-4 border-t border-slate-700 pt-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Template e Plano</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Template do Cardápio</Label>
                      <Select value={formData.template} onValueChange={(v) => setFormData({ ...formData, template: v })}>
                        <SelectTrigger className="bg-slate-700 border-slate-600">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="bg-slate-700 border-slate-600">
                          {templates.map((t) => (
                            <SelectItem key={t.id} value={t.id}>
                              {t.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Plano</Label>
                      <Select value={formData.plan} onValueChange={(v) => setFormData({ ...formData, plan: v })}>
                        <SelectTrigger className="bg-slate-700 border-slate-600">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="bg-slate-700 border-slate-600">
                          {plans.map((p) => (
                            <SelectItem key={p.id} value={p.id}>
                              {p.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Status</Label>
                      <Select value={formData.status} onValueChange={(v: "active" | "inactive" | "pending") => setFormData({ ...formData, status: v })}>
                        <SelectTrigger className="bg-slate-700 border-slate-600">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="bg-slate-700 border-slate-600">
                          <SelectItem value="pending">Pendente</SelectItem>
                          <SelectItem value="active">Ativo</SelectItem>
                          <SelectItem value="inactive">Inativo</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    <div className="space-y-2">
                      <Label>Data de Expiração</Label>
                      <Input
                        type="date"
                        value={formData.expiresAt}
                        onChange={(e) => setFormData({ ...formData, expiresAt: e.target.value })}
                        className="bg-slate-700 border-slate-600"
                      />
                    </div>
                  </div>
                </div>

                <Button onClick={handleSave} className="w-full bg-purple-600 hover:bg-purple-700">
                  {editingRestaurant ? "Atualizar" : "Criar"} Restaurante
                </Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Search */}
        <div className="mb-6">
          <div className="relative max-w-md">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input
              placeholder="Buscar restaurantes..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-10 bg-slate-800/50 border-slate-700 text-white"
            />
          </div>
        </div>

        {/* Table */}
        <Card className="bg-slate-800/50 border-slate-700">
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="border-slate-700 hover:bg-transparent">
                  <TableHead className="text-slate-300">Logo</TableHead>
                  <TableHead className="text-slate-300">Restaurante</TableHead>
                  <TableHead className="text-slate-300">Template</TableHead>
                  <TableHead className="text-slate-300">Cores</TableHead>
                  <TableHead className="text-slate-300">Plano</TableHead>
                  <TableHead className="text-slate-300">Status</TableHead>
                  <TableHead className="text-slate-300">Expira em</TableHead>
                  <TableHead className="text-slate-300 text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredRestaurants.map((restaurant) => (
                  <TableRow key={restaurant.id} className="border-slate-700">
                    <TableCell>
                      <img
                        src={restaurant.logo || "/placeholder.svg"}
                        alt={restaurant.name}
                        className="h-10 w-10 object-cover rounded-full"
                      />
                    </TableCell>
                    <TableCell>
                      <div>
                        <p className="text-white font-medium">{restaurant.name}</p>
                        <p className="text-xs text-slate-500">/{restaurant.slug}</p>
                        <p className="text-xs text-slate-400">{restaurant.address}</p>
                      </div>
                    </TableCell>
                    <TableCell className="text-slate-300">
                      {templates.find((t) => t.id === restaurant.template)?.name || restaurant.template}
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        <div
                          className="w-6 h-6 rounded-full border border-slate-600"
                          style={{ backgroundColor: restaurant.primaryColor }}
                          title="Primária"
                        />
                        <div
                          className="w-6 h-6 rounded-full border border-slate-600"
                          style={{ backgroundColor: restaurant.secondaryColor }}
                          title="Secundária"
                        />
                        <div
                          className="w-6 h-6 rounded-full border border-slate-600"
                          style={{ backgroundColor: restaurant.fontColor }}
                          title="Fonte"
                        />
                      </div>
                    </TableCell>
                    <TableCell>{getPlanBadge(restaurant.plan)}</TableCell>
                    <TableCell>{getStatusBadge(restaurant.status)}</TableCell>
                    <TableCell className="text-slate-300">{restaurant.expiresAt || "-"}</TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-slate-400 hover:text-white"
                          onClick={() => window.open(`/menu-bold?id=${restaurant.slug}`, "_blank")}
                          title="Visualizar cardápio"
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-slate-400 hover:text-white"
                          onClick={() => handleEdit(restaurant)}
                        >
                          <Edit className="w-4 h-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-slate-400 hover:text-red-400"
                          onClick={() => handleDelete(restaurant.id)}
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </main>
    </div>
  );
};

export default MasterRestaurants;
