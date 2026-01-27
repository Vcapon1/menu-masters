import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
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
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  ArrowLeft,
  Plus,
  Search,
  Edit,
  Trash2,
  Store,
  Upload,
  Eye,
  AlertTriangle,
  Clock,
  CheckCircle,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { getDefaultColors, getTemplatePreset } from "@/lib/templatePresets";
import { RotateCcw } from "lucide-react";

interface Restaurant {
  id: string;
  name: string;
  slug: string;
  address: string;
  email: string;
  phone: string;
  internalNotes: string;
  template: string;
  logo: string;
  banner: string;
  backgroundImage: string;
  backgroundVideo: string;
  primaryColor: string;
  secondaryColor: string;
  accentColor: string;
  buttonColor: string;
  buttonTextColor: string;
  fontColor: string;
  plan: string;
  status: "active" | "inactive" | "pending";
  createdAt: string;
  expiresAt: string;
}

const templates = [
  { id: "appetite", name: "Appetite - Mobile-First (iFood)", minPlan: "basic" },
  { id: "classic", name: "Clássico - Equilibrado", minPlan: "basic" },
  { id: "visual", name: "Visual - Imagens Grandes", minPlan: "premium" },
  { id: "modern", name: "Moderno - Clean", minPlan: "premium" },
  { id: "bold", name: "Bold - Alto Contraste", minPlan: "premium" },
  { id: "elegant", name: "Elegante - Sofisticado", minPlan: "personalite" },
  { id: "minimal", name: "Minimalista - Ultra Clean", minPlan: "personalite" },
];

const plans = [
  { id: "basic", name: "Básico", order: 1 },
  { id: "premium", name: "Premium", order: 2 },
  { id: "personalite", name: "Personalité", order: 3 },
];

const getPlanOrder = (planId: string) => plans.find(p => p.id === planId)?.order || 0;

const getTemplatesForPlan = (planId: string) => {
  const planOrder = getPlanOrder(planId);
  return templates.filter(t => getPlanOrder(t.minPlan) <= planOrder);
};

const getExpirationStatus = (expiresAt: string) => {
  if (!expiresAt) return { status: "unknown", days: 0, label: "Não definida" };
  
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const expDate = new Date(expiresAt);
  expDate.setHours(0, 0, 0, 0);
  const daysLeft = Math.ceil((expDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

  if (daysLeft < 0) {
    return { status: "expired", days: Math.abs(daysLeft), label: `Expirado há ${Math.abs(daysLeft)} dias` };
  }
  if (daysLeft === 0) {
    return { status: "expired", days: 0, label: "Expira hoje" };
  }
  if (daysLeft <= 30) {
    return { status: "warning", days: daysLeft, label: `Expira em ${daysLeft} dias` };
  }
  return { status: "ok", days: daysLeft, label: `${daysLeft} dias restantes` };
};

const MasterRestaurants = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [restaurants, setRestaurants] = useState<Restaurant[]>([]);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRestaurant, setEditingRestaurant] = useState<Restaurant | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<{ id: string; name: string } | null>(null);
  const [deletePassword, setDeletePassword] = useState("");
  const [formData, setFormData] = useState({
    name: "",
    slug: "",
    address: "",
    email: "",
    phone: "",
    internalNotes: "",
    template: "classic",
    logo: "",
    banner: "",
    backgroundImage: "",
    backgroundVideo: "",
    primaryColor: "#dc2626",
    secondaryColor: "#fbbf24",
    accentColor: "#ff6b00",
    buttonColor: "#dc2626",
    buttonTextColor: "#ffffff",
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
      const today = new Date();
      const mockData: Restaurant[] = [
        {
          id: "1",
          name: "Pizzaria Bella",
          slug: "pizzaria-bella",
          address: "Rua das Flores, 123 - Centro",
          email: "contato@bella.com",
          phone: "(11) 99999-0001",
          internalNotes: "Cliente desde 2023. Negociou desconto de 10% no plano anual.",
          template: "bold",
          logo: "/placeholder.svg",
          banner: "/placeholder.svg",
          backgroundImage: "",
          backgroundVideo: "",
          primaryColor: "#dc2626",
          secondaryColor: "#fbbf24",
          accentColor: "#ff6b00",
          buttonColor: "#dc2626",
          buttonTextColor: "#ffffff",
          fontColor: "#ffffff",
          plan: "premium",
          status: "active",
          createdAt: "2024-01-15",
          expiresAt: new Date(today.getTime() + 15 * 24 * 60 * 60 * 1000).toISOString().split("T")[0], // 15 days
        },
        {
          id: "2",
          name: "Burger House",
          slug: "burger-house",
          address: "Av. Principal, 456 - Jardins",
          email: "contato@burger.com",
          phone: "(11) 99999-0002",
          internalNotes: "",
          template: "classic",
          logo: "/placeholder.svg",
          banner: "/placeholder.svg",
          backgroundImage: "",
          backgroundVideo: "",
          primaryColor: "#16a34a",
          secondaryColor: "#22c55e",
          accentColor: "#10b981",
          buttonColor: "#16a34a",
          buttonTextColor: "#ffffff",
          fontColor: "#ffffff",
          plan: "basic",
          status: "active",
          createdAt: "2024-02-10",
          expiresAt: new Date(today.getTime() + 90 * 24 * 60 * 60 * 1000).toISOString().split("T")[0], // 90 days
        },
        {
          id: "3",
          name: "Sushi Master",
          slug: "sushi-master",
          address: "Rua Japão, 789 - Liberdade",
          email: "contato@sushimaster.com",
          phone: "(11) 99999-0003",
          internalNotes: "Plano expirado. Tentou renovação mas não concluiu.",
          template: "modern",
          logo: "/placeholder.svg",
          banner: "/placeholder.svg",
          backgroundImage: "",
          backgroundVideo: "",
          primaryColor: "#1e293b",
          secondaryColor: "#f97316",
          accentColor: "#f59e0b",
          buttonColor: "#f97316",
          buttonTextColor: "#ffffff",
          fontColor: "#ffffff",
          plan: "premium",
          status: "inactive",
          createdAt: "2024-03-01",
          expiresAt: new Date(today.getTime() - 10 * 24 * 60 * 60 * 1000).toISOString().split("T")[0], // Expired 10 days ago
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

  const handleTemplateChange = (templateId: string) => {
    const colors = getDefaultColors(templateId);
    if (colors && !editingRestaurant) {
      // Apply preset colors for new restaurants
      setFormData({
        ...formData,
        template: templateId,
        ...colors,
      });
    } else {
      setFormData({ ...formData, template: templateId });
    }
  };

  const handleResetColors = () => {
    const colors = getDefaultColors(formData.template);
    if (colors) {
      setFormData({
        ...formData,
        ...colors,
      });
      toast({ 
        title: "Cores restauradas", 
        description: `Aplicadas cores padrão do template ${getTemplatePreset(formData.template)?.name || formData.template}` 
      });
    }
  };

  const currentPreset = getTemplatePreset(formData.template);

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

  const handleDeleteRequest = (id: string, name: string) => {
    setDeleteConfirm({ id, name });
    setDeletePassword("");
  };

  const handleDeleteConfirm = () => {
    if (deletePassword !== "admin123") {
      toast({ title: "Erro", description: "Senha incorreta", variant: "destructive" });
      return;
    }
    if (!deleteConfirm) return;
    
    const updated = restaurants.filter((r) => r.id !== deleteConfirm.id);
    setRestaurants(updated);
    localStorage.setItem("masterRestaurants", JSON.stringify(updated));
    toast({ title: "Sucesso", description: "Restaurante removido" });
    setDeleteConfirm(null);
    setDeletePassword("");
  };

  const handleEdit = (restaurant: Restaurant) => {
    setEditingRestaurant(restaurant);
    setFormData({
      name: restaurant.name,
      slug: restaurant.slug,
      address: restaurant.address,
      email: restaurant.email,
      phone: restaurant.phone,
      internalNotes: restaurant.internalNotes || "",
      template: restaurant.template,
      logo: restaurant.logo,
      banner: restaurant.banner,
      backgroundImage: restaurant.backgroundImage || "",
      backgroundVideo: restaurant.backgroundVideo,
      primaryColor: restaurant.primaryColor,
      secondaryColor: restaurant.secondaryColor,
      accentColor: restaurant.accentColor || "#ff6b00",
      buttonColor: restaurant.buttonColor || restaurant.primaryColor,
      buttonTextColor: restaurant.buttonTextColor || "#ffffff",
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
      internalNotes: "",
      template: "classic",
      logo: "",
      banner: "",
      backgroundImage: "",
      backgroundVideo: "",
      primaryColor: "#dc2626",
      secondaryColor: "#fbbf24",
      accentColor: "#ff6b00",
      buttonColor: "#dc2626",
      buttonTextColor: "#ffffff",
      fontColor: "#ffffff",
      plan: "basic",
      status: "pending",
      expiresAt: "",
    });
  };

  const handleFileChange = (field: "logo" | "banner" | "backgroundImage", e: React.ChangeEvent<HTMLInputElement>) => {
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

  // Sort by expiration urgency: expired first, then warning, then ok
  const sortedRestaurants = [...filteredRestaurants].sort((a, b) => {
    const statusA = getExpirationStatus(a.expiresAt);
    const statusB = getExpirationStatus(b.expiresAt);
    const priority = { expired: 0, warning: 1, ok: 2, unknown: 3 };
    return priority[statusA.status as keyof typeof priority] - priority[statusB.status as keyof typeof priority];
  });

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

  const getExpirationBadge = (expiresAt: string) => {
    const { status, label } = getExpirationStatus(expiresAt);
    
    switch (status) {
      case "expired":
        return (
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge className="bg-red-600 hover:bg-red-700 cursor-help flex items-center gap-1">
                <AlertTriangle className="w-3 h-3" />
                Expirado
              </Badge>
            </TooltipTrigger>
            <TooltipContent>
              <p>{label}</p>
            </TooltipContent>
          </Tooltip>
        );
      case "warning":
        return (
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge className="bg-yellow-600 hover:bg-yellow-700 cursor-help flex items-center gap-1">
                <Clock className="w-3 h-3" />
                Expirando
              </Badge>
            </TooltipTrigger>
            <TooltipContent>
              <p>{label}</p>
            </TooltipContent>
          </Tooltip>
        );
      case "ok":
        return (
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge className="bg-green-600/80 hover:bg-green-700 cursor-help flex items-center gap-1">
                <CheckCircle className="w-3 h-3" />
                OK
              </Badge>
            </TooltipTrigger>
            <TooltipContent>
              <p>{label}</p>
            </TooltipContent>
          </Tooltip>
        );
      default:
        return <span className="text-slate-400 text-sm">-</span>;
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
            <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-3xl max-h-[90vh] overflow-y-auto">
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
                  <div className="space-y-2">
                    <Label>Observações Internas</Label>
                    <Textarea
                      value={formData.internalNotes}
                      onChange={(e) => setFormData({ ...formData, internalNotes: e.target.value })}
                      className="bg-slate-700 border-slate-600 min-h-[80px]"
                      placeholder="Anotações sobre negociação, acordos especiais, etc. (visível apenas para o admin master)"
                    />
                  </div>
                </div>

                {/* Imagens e Mídia */}
                <div className="space-y-4 border-t border-slate-700 pt-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Imagens e Mídia</h3>
                  <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-2">
                      <Label>Logotipo</Label>
                      <div className="flex flex-col gap-2">
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => handleFileChange("logo", e)}
                          className="hidden"
                          id="logo-upload"
                        />
                        <label
                          htmlFor="logo-upload"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-700 border border-slate-600 rounded-md cursor-pointer hover:bg-slate-600 transition-colors text-sm"
                        >
                          <Upload className="h-4 w-4" />
                          Enviar Logo
                        </label>
                        {formData.logo && (
                          <img src={formData.logo} alt="Logo" className="h-12 w-12 object-cover rounded" />
                        )}
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Banner</Label>
                      <div className="flex flex-col gap-2">
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => handleFileChange("banner", e)}
                          className="hidden"
                          id="banner-upload"
                        />
                        <label
                          htmlFor="banner-upload"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-700 border border-slate-600 rounded-md cursor-pointer hover:bg-slate-600 transition-colors text-sm"
                        >
                          <Upload className="h-4 w-4" />
                          Enviar Banner
                        </label>
                        {formData.banner && (
                          <img src={formData.banner} alt="Banner" className="h-12 w-24 object-cover rounded" />
                        )}
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Imagem de Fundo</Label>
                      <div className="flex flex-col gap-2">
                        <Input
                          type="file"
                          accept="image/*"
                          onChange={(e) => handleFileChange("backgroundImage", e)}
                          className="hidden"
                          id="bg-image-upload"
                        />
                        <label
                          htmlFor="bg-image-upload"
                          className="flex items-center gap-2 px-4 py-2 bg-slate-700 border border-slate-600 rounded-md cursor-pointer hover:bg-slate-600 transition-colors text-sm"
                        >
                          <Upload className="h-4 w-4" />
                          Enviar Fundo
                        </label>
                        {formData.backgroundImage && (
                          <img src={formData.backgroundImage} alt="Fundo" className="h-12 w-24 object-cover rounded" />
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
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Cores do Tema</h3>
                      {currentPreset && (
                        <p className="text-xs text-slate-400 mt-1">
                          <span className="text-purple-300">{currentPreset.name}</span> — {currentPreset.description}
                        </p>
                      )}
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={handleResetColors}
                      className="flex items-center gap-2 border-purple-500/50 text-purple-300 hover:bg-purple-500/20 hover:text-purple-200"
                    >
                      <RotateCcw className="w-4 h-4" />
                      Restaurar Padrão
                    </Button>
                  </div>
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
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
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
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Cor de Destaque</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.accentColor}
                          onChange={(e) => setFormData({ ...formData, accentColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.accentColor}
                          onChange={(e) => setFormData({ ...formData, accentColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
                        />
                      </div>
                    </div>
                  </div>
                  <div className="grid grid-cols-3 gap-4">
                    <div className="space-y-2">
                      <Label>Cor dos Botões</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.buttonColor}
                          onChange={(e) => setFormData({ ...formData, buttonColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.buttonColor}
                          onChange={(e) => setFormData({ ...formData, buttonColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Texto dos Botões</Label>
                      <div className="flex items-center gap-2">
                        <Input
                          type="color"
                          value={formData.buttonTextColor}
                          onChange={(e) => setFormData({ ...formData, buttonTextColor: e.target.value })}
                          className="w-12 h-10 p-1 cursor-pointer bg-slate-700 border-slate-600"
                        />
                        <Input
                          value={formData.buttonTextColor}
                          onChange={(e) => setFormData({ ...formData, buttonTextColor: e.target.value })}
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
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
                          className="flex-1 bg-slate-700 border-slate-600 text-xs"
                        />
                      </div>
                    </div>
                  </div>
                  {/* Preview das cores */}
                  <div className="rounded-lg overflow-hidden border border-slate-600">
                    <div
                      className="p-4 flex items-center justify-between gap-4"
                      style={{ 
                        backgroundColor: formData.primaryColor,
                        backgroundImage: formData.backgroundImage ? `url(${formData.backgroundImage})` : 'none',
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                      }}
                    >
                      <span style={{ color: formData.fontColor }} className="font-bold">
                        Preview do Texto
                      </span>
                      <div className="flex gap-2">
                        <span
                          className="px-3 py-1 rounded-full text-sm font-medium"
                          style={{ backgroundColor: formData.accentColor, color: formData.fontColor }}
                        >
                          Destaque
                        </span>
                        <span
                          className="px-3 py-1 rounded-full text-sm font-medium"
                          style={{ backgroundColor: formData.secondaryColor, color: formData.primaryColor }}
                        >
                          Badge
                        </span>
                      </div>
                    </div>
                    <div className="p-3 bg-slate-700/50 flex justify-center">
                      <button
                        className="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        style={{ backgroundColor: formData.buttonColor, color: formData.buttonTextColor }}
                      >
                        Exemplo de Botão
                      </button>
                    </div>
                  </div>
                </div>

                {/* Plano e Template */}
                <div className="space-y-4 border-t border-slate-700 pt-4">
                  <h3 className="text-sm font-semibold text-purple-400 uppercase tracking-wide">Plano e Template</h3>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label>Plano *</Label>
                      <Select value={formData.plan} onValueChange={(v) => setFormData({ ...formData, plan: v, template: getTemplatesForPlan(v)[0]?.id || "classic" })}>
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
                    <div className="space-y-2">
                      <Label>Template do Cardápio</Label>
                      <Select value={formData.template} onValueChange={handleTemplateChange}>
                        <SelectTrigger className="bg-slate-700 border-slate-600">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent className="bg-slate-700 border-slate-600">
                          {getTemplatesForPlan(formData.plan).map((t) => (
                            <SelectItem key={t.id} value={t.id}>
                              {t.name}
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
                  <TableHead className="text-slate-300">Validade</TableHead>
                  <TableHead className="text-slate-300 text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {sortedRestaurants.map((restaurant) => (
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
                        {restaurant.internalNotes && (
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <p className="text-xs text-yellow-500/80 truncate max-w-[200px] cursor-help">
                                📝 {restaurant.internalNotes}
                              </p>
                            </TooltipTrigger>
                            <TooltipContent className="max-w-[300px]">
                              <p className="text-sm">{restaurant.internalNotes}</p>
                            </TooltipContent>
                          </Tooltip>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-slate-300">
                      {templates.find((t) => t.id === restaurant.template)?.name || restaurant.template}
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <div
                              className="w-6 h-6 rounded-full border border-slate-600 cursor-help"
                              style={{ backgroundColor: restaurant.primaryColor }}
                            />
                          </TooltipTrigger>
                          <TooltipContent>Primária</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <div
                              className="w-6 h-6 rounded-full border border-slate-600 cursor-help"
                              style={{ backgroundColor: restaurant.secondaryColor }}
                            />
                          </TooltipTrigger>
                          <TooltipContent>Secundária</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <div
                              className="w-6 h-6 rounded-full border border-slate-600 cursor-help"
                              style={{ backgroundColor: restaurant.accentColor || restaurant.primaryColor }}
                            />
                          </TooltipTrigger>
                          <TooltipContent>Destaque</TooltipContent>
                        </Tooltip>
                      </div>
                    </TableCell>
                    <TableCell>{getPlanBadge(restaurant.plan)}</TableCell>
                    <TableCell>{getStatusBadge(restaurant.status)}</TableCell>
                    <TableCell>
                      <div className="flex flex-col gap-1">
                        {getExpirationBadge(restaurant.expiresAt)}
                        <span className="text-xs text-slate-500">{restaurant.expiresAt || "-"}</span>
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-purple-400 hover:text-purple-300"
                              onClick={() => navigate(`/master/preview?id=${restaurant.id}`)}
                            >
                              <Eye className="w-4 h-4" />
                            </Button>
                          </TooltipTrigger>
                          <TooltipContent>Preview com cores</TooltipContent>
                        </Tooltip>
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
                          onClick={() => handleDeleteRequest(restaurant.id, restaurant.name)}
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

      {/* Modal de Confirmação de Exclusão com Senha */}
      <Dialog open={!!deleteConfirm} onOpenChange={(open) => !open && setDeleteConfirm(null)}>
        <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-md">
          <DialogHeader>
            <DialogTitle className="text-red-400 flex items-center gap-2">
              <AlertTriangle className="w-5 h-5" />
              Confirmar Exclusão
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <p className="text-slate-300">
              Você está prestes a excluir o restaurante <strong className="text-white">{deleteConfirm?.name}</strong>. 
              Esta ação é irreversível.
            </p>
            <div className="space-y-2">
              <Label>Digite sua senha para confirmar:</Label>
              <Input
                type="password"
                value={deletePassword}
                onChange={(e) => setDeletePassword(e.target.value)}
                className="bg-slate-700 border-slate-600"
                placeholder="Senha do Admin Master"
              />
            </div>
            <div className="flex gap-2">
              <Button 
                onClick={handleDeleteConfirm} 
                className="flex-1 bg-red-600 hover:bg-red-700"
              >
                Confirmar Exclusão
              </Button>
              <Button 
                variant="outline" 
                onClick={() => setDeleteConfirm(null)}
                className="border-slate-600"
              >
                Cancelar
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default MasterRestaurants;
