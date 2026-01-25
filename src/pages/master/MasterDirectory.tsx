import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
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
  Shield,
  ArrowLeft,
  Plus,
  Search,
  Pencil,
  Trash2,
  MapPin,
  ExternalLink,
  Eye,
  Users,
  UserCheck,
  FileText,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import {
  DirectoryRestaurant,
  PriceRange,
  DirectoryStatus,
  CUISINE_TYPES,
  NEIGHBORHOODS,
} from "@/lib/directoryTypes";

const DAYS_OF_WEEK = [
  { key: "monday", label: "Segunda" },
  { key: "tuesday", label: "Terça" },
  { key: "wednesday", label: "Quarta" },
  { key: "thursday", label: "Quinta" },
  { key: "friday", label: "Sexta" },
  { key: "saturday", label: "Sábado" },
  { key: "sunday", label: "Domingo" },
] as const;

const emptyRestaurant: Omit<DirectoryRestaurant, "id" | "createdAt" | "updatedAt"> = {
  name: "",
  slug: "",
  address: "",
  neighborhood: "",
  city: "São Paulo",
  cuisineTypes: [],
  logo: "",
  phone: "",
  whatsapp: "",
  instagram: "",
  website: "",
  openingHours: {},
  priceRange: "$$",
  isClient: false,
  status: "draft",
  internalNotes: "",
};

const MasterDirectory = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [restaurants, setRestaurants] = useState<DirectoryRestaurant[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<DirectoryStatus | "all">("all");
  const [clientFilter, setClientFilter] = useState<"all" | "clients" | "prospects">("all");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRestaurant, setEditingRestaurant] = useState<DirectoryRestaurant | null>(null);
  const [formData, setFormData] = useState(emptyRestaurant);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [cuisineInput, setCuisineInput] = useState("");

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }
    loadRestaurants();
  }, [navigate]);

  const loadRestaurants = () => {
    const stored = localStorage.getItem("directoryRestaurants");
    if (stored) {
      setRestaurants(JSON.parse(stored));
    }
  };

  const saveRestaurants = (data: DirectoryRestaurant[]) => {
    localStorage.setItem("directoryRestaurants", JSON.stringify(data));
    setRestaurants(data);
  };

  const generateSlug = (name: string) => {
    return name
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/(^-|-$)/g, "");
  };

  const handleOpenDialog = (restaurant?: DirectoryRestaurant) => {
    if (restaurant) {
      setEditingRestaurant(restaurant);
      setFormData({
        name: restaurant.name,
        slug: restaurant.slug,
        address: restaurant.address,
        neighborhood: restaurant.neighborhood,
        city: restaurant.city,
        cuisineTypes: restaurant.cuisineTypes,
        logo: restaurant.logo,
        phone: restaurant.phone,
        whatsapp: restaurant.whatsapp,
        instagram: restaurant.instagram,
        website: restaurant.website,
        openingHours: restaurant.openingHours,
        priceRange: restaurant.priceRange,
        isClient: restaurant.isClient,
        linkedClientId: restaurant.linkedClientId,
        menuUrl: restaurant.menuUrl,
        status: restaurant.status,
        internalNotes: restaurant.internalNotes,
      });
    } else {
      setEditingRestaurant(null);
      setFormData(emptyRestaurant);
    }
    setIsDialogOpen(true);
  };

  const handleSubmit = () => {
    if (!formData.name.trim()) {
      toast({ title: "Nome é obrigatório", variant: "destructive" });
      return;
    }

    const slug = formData.slug || generateSlug(formData.name);
    const now = new Date().toISOString();

    if (editingRestaurant) {
      const updated = restaurants.map((r) =>
        r.id === editingRestaurant.id
          ? { ...r, ...formData, slug, updatedAt: now }
          : r
      );
      saveRestaurants(updated);
      toast({ title: "Restaurante atualizado com sucesso!" });
    } else {
      const newRestaurant: DirectoryRestaurant = {
        ...formData,
        id: `dir-${Date.now()}`,
        slug,
        createdAt: now,
        updatedAt: now,
      };
      saveRestaurants([...restaurants, newRestaurant]);
      toast({ title: "Restaurante adicionado ao diretório!" });
    }

    setIsDialogOpen(false);
    setEditingRestaurant(null);
    setFormData(emptyRestaurant);
  };

  const handleDelete = () => {
    if (!deleteId) return;
    const updated = restaurants.filter((r) => r.id !== deleteId);
    saveRestaurants(updated);
    setDeleteId(null);
    toast({ title: "Restaurante removido do diretório" });
  };

  const addCuisineType = () => {
    if (cuisineInput.trim() && !formData.cuisineTypes.includes(cuisineInput.trim())) {
      setFormData({
        ...formData,
        cuisineTypes: [...formData.cuisineTypes, cuisineInput.trim()],
      });
      setCuisineInput("");
    }
  };

  const removeCuisineType = (cuisine: string) => {
    setFormData({
      ...formData,
      cuisineTypes: formData.cuisineTypes.filter((c) => c !== cuisine),
    });
  };

  const updateOpeningHours = (
    day: string,
    field: "open" | "close",
    value: string
  ) => {
    setFormData({
      ...formData,
      openingHours: {
        ...formData.openingHours,
        [day]: {
          ...formData.openingHours[day as keyof typeof formData.openingHours],
          [field]: value,
        },
      },
    });
  };

  const toggleDayClosed = (day: string) => {
    const current = formData.openingHours[day as keyof typeof formData.openingHours];
    if (current) {
      const newHours = { ...formData.openingHours };
      delete newHours[day as keyof typeof newHours];
      setFormData({ ...formData, openingHours: newHours });
    } else {
      setFormData({
        ...formData,
        openingHours: {
          ...formData.openingHours,
          [day]: { open: "09:00", close: "18:00" },
        },
      });
    }
  };

  // Filter restaurants
  const filteredRestaurants = restaurants.filter((r) => {
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      if (
        !r.name.toLowerCase().includes(query) &&
        !r.neighborhood.toLowerCase().includes(query)
      ) {
        return false;
      }
    }
    if (statusFilter !== "all" && r.status !== statusFilter) return false;
    if (clientFilter === "clients" && !r.isClient) return false;
    if (clientFilter === "prospects" && r.isClient) return false;
    return true;
  });

  // Stats
  const totalCount = restaurants.length;
  const clientsCount = restaurants.filter((r) => r.isClient).length;
  const prospectsCount = totalCount - clientsCount;

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      {/* Header */}
      <header className="bg-slate-800/50 backdrop-blur-sm border-b border-purple-500/30">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => navigate("/master/dashboard")}
              className="text-slate-300 hover:text-white"
            >
              <ArrowLeft className="w-5 h-5" />
            </Button>
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center">
                <MapPin className="w-5 h-5 text-white" />
              </div>
              <div>
                <h1 className="text-xl font-bold text-white">Diretório de Restaurantes</h1>
                <p className="text-xs text-slate-400">Guia Gastronômico & Prospecção</p>
              </div>
            </div>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" asChild className="border-slate-600 text-slate-300">
              <Link to="/guia" target="_blank">
                <Eye className="w-4 h-4 mr-2" />
                Ver Guia Público
              </Link>
            </Button>
            <Button onClick={() => handleOpenDialog()} className="bg-teal-600 hover:bg-teal-700">
              <Plus className="w-4 h-4 mr-2" />
              Adicionar
            </Button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-4 flex items-center gap-4">
              <div className="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                <Users className="w-6 h-6 text-blue-400" />
              </div>
              <div>
                <p className="text-sm text-slate-400">Total no Diretório</p>
                <p className="text-2xl font-bold text-white">{totalCount}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-4 flex items-center gap-4">
              <div className="w-12 h-12 bg-green-600/20 rounded-lg flex items-center justify-center">
                <UserCheck className="w-6 h-6 text-green-400" />
              </div>
              <div>
                <p className="text-sm text-slate-400">Clientes Ativos</p>
                <p className="text-2xl font-bold text-white">{clientsCount}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-4 flex items-center gap-4">
              <div className="w-12 h-12 bg-orange-600/20 rounded-lg flex items-center justify-center">
                <FileText className="w-6 h-6 text-orange-400" />
              </div>
              <div>
                <p className="text-sm text-slate-400">Prospects</p>
                <p className="text-2xl font-bold text-white">{prospectsCount}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <Card className="bg-slate-800/50 border-slate-700 mb-6">
          <CardContent className="p-4">
            <div className="flex flex-col md:flex-row gap-4">
              <div className="flex-1 relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <Input
                  placeholder="Buscar por nome ou bairro..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10 bg-slate-900 border-slate-600 text-white"
                />
              </div>
              <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v as DirectoryStatus | "all")}>
                <SelectTrigger className="w-[150px] bg-slate-900 border-slate-600 text-white">
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="active">Ativos</SelectItem>
                  <SelectItem value="pending">Pendentes</SelectItem>
                  <SelectItem value="draft">Rascunhos</SelectItem>
                </SelectContent>
              </Select>
              <Select value={clientFilter} onValueChange={(v) => setClientFilter(v as "all" | "clients" | "prospects")}>
                <SelectTrigger className="w-[150px] bg-slate-900 border-slate-600 text-white">
                  <SelectValue placeholder="Tipo" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="clients">Clientes</SelectItem>
                  <SelectItem value="prospects">Prospects</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Table */}
        <Card className="bg-slate-800/50 border-slate-700">
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="border-slate-700">
                  <TableHead className="text-slate-300">Restaurante</TableHead>
                  <TableHead className="text-slate-300">Bairro</TableHead>
                  <TableHead className="text-slate-300">Tipo</TableHead>
                  <TableHead className="text-slate-300">Faixa</TableHead>
                  <TableHead className="text-slate-300">Status</TableHead>
                  <TableHead className="text-slate-300">Cliente?</TableHead>
                  <TableHead className="text-slate-300 text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredRestaurants.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center text-slate-400 py-8">
                      {restaurants.length === 0
                        ? "Nenhum restaurante cadastrado. Clique em 'Adicionar' para começar."
                        : "Nenhum resultado encontrado para os filtros selecionados."}
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredRestaurants.map((restaurant) => (
                    <TableRow key={restaurant.id} className="border-slate-700">
                      <TableCell>
                        <div className="flex items-center gap-3">
                          {restaurant.logo ? (
                            <img
                              src={restaurant.logo}
                              alt={restaurant.name}
                              className="w-10 h-10 rounded-lg object-cover"
                            />
                          ) : (
                            <div className="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-slate-400">
                              {restaurant.name.charAt(0)}
                            </div>
                          )}
                          <div>
                            <p className="font-medium text-white">{restaurant.name}</p>
                            <p className="text-xs text-slate-400">{restaurant.cuisineTypes.slice(0, 2).join(", ")}</p>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell className="text-slate-300">{restaurant.neighborhood || "-"}</TableCell>
                      <TableCell>
                        <div className="flex flex-wrap gap-1">
                          {restaurant.cuisineTypes.slice(0, 2).map((t) => (
                            <Badge key={t} variant="outline" className="text-xs border-slate-600 text-slate-300">
                              {t}
                            </Badge>
                          ))}
                        </div>
                      </TableCell>
                      <TableCell className="text-slate-300">{restaurant.priceRange}</TableCell>
                      <TableCell>
                        <Badge
                          variant={
                            restaurant.status === "active"
                              ? "default"
                              : restaurant.status === "pending"
                              ? "secondary"
                              : "outline"
                          }
                          className={
                            restaurant.status === "active"
                              ? "bg-green-600"
                              : restaurant.status === "pending"
                              ? "bg-yellow-600"
                              : "border-slate-600"
                          }
                        >
                          {restaurant.status === "active"
                            ? "Ativo"
                            : restaurant.status === "pending"
                            ? "Pendente"
                            : "Rascunho"}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {restaurant.isClient ? (
                          <Badge className="bg-purple-600">✓ Cliente</Badge>
                        ) : (
                          <Badge variant="outline" className="border-slate-600 text-slate-400">
                            Prospect
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleOpenDialog(restaurant)}
                            className="text-slate-400 hover:text-white"
                          >
                            <Pencil className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setDeleteId(restaurant.id)}
                            className="text-slate-400 hover:text-red-400"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </main>

      {/* Add/Edit Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              {editingRestaurant ? "Editar Restaurante" : "Adicionar ao Diretório"}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-6">
            {/* Basic Info */}
            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <Label>Nome do Restaurante *</Label>
                <Input
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="Ex: Trattoria Bella Italia"
                />
              </div>
              <div>
                <Label>Slug (URL)</Label>
                <Input
                  value={formData.slug}
                  onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                  placeholder="Auto-gerado do nome"
                />
              </div>
              <div>
                <Label>Cidade</Label>
                <Input
                  value={formData.city}
                  onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                  placeholder="São Paulo"
                />
              </div>
            </div>

            {/* Location */}
            <div className="grid grid-cols-2 gap-4">
              <div className="col-span-2">
                <Label>Endereço</Label>
                <Input
                  value={formData.address}
                  onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                  placeholder="Rua, número - Complemento"
                />
              </div>
              <div>
                <Label>Bairro</Label>
                <Select
                  value={formData.neighborhood}
                  onValueChange={(v) => setFormData({ ...formData, neighborhood: v })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione" />
                  </SelectTrigger>
                  <SelectContent>
                    {NEIGHBORHOODS.map((n) => (
                      <SelectItem key={n} value={n}>
                        {n}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Faixa de Preço</Label>
                <Select
                  value={formData.priceRange}
                  onValueChange={(v) => setFormData({ ...formData, priceRange: v as PriceRange })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="$">$ - Econômico</SelectItem>
                    <SelectItem value="$$">$$ - Moderado</SelectItem>
                    <SelectItem value="$$$">$$$ - Caro</SelectItem>
                    <SelectItem value="$$$$">$$$$ - Luxo</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Cuisine Types */}
            <div>
              <Label>Tipos de Comida</Label>
              <div className="flex gap-2 mb-2">
                <Select value="" onValueChange={(v) => {
                  if (v && !formData.cuisineTypes.includes(v)) {
                    setFormData({
                      ...formData,
                      cuisineTypes: [...formData.cuisineTypes, v],
                    });
                  }
                }}>
                  <SelectTrigger className="flex-1">
                    <SelectValue placeholder="Adicionar tipo..." />
                  </SelectTrigger>
                  <SelectContent>
                    {CUISINE_TYPES.filter((c) => !formData.cuisineTypes.includes(c)).map((c) => (
                      <SelectItem key={c} value={c}>
                        {c}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="flex flex-wrap gap-2">
                {formData.cuisineTypes.map((cuisine) => (
                  <Badge
                    key={cuisine}
                    variant="secondary"
                    className="cursor-pointer hover:bg-destructive hover:text-destructive-foreground"
                    onClick={() => removeCuisineType(cuisine)}
                  >
                    {cuisine} ×
                  </Badge>
                ))}
              </div>
            </div>

            {/* Contact */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Telefone</Label>
                <Input
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  placeholder="(11) 3456-7890"
                />
              </div>
              <div>
                <Label>WhatsApp</Label>
                <Input
                  value={formData.whatsapp}
                  onChange={(e) => setFormData({ ...formData, whatsapp: e.target.value })}
                  placeholder="11934567890"
                />
              </div>
              <div>
                <Label>Instagram</Label>
                <Input
                  value={formData.instagram}
                  onChange={(e) => setFormData({ ...formData, instagram: e.target.value })}
                  placeholder="@restaurante"
                />
              </div>
              <div>
                <Label>Website</Label>
                <Input
                  value={formData.website}
                  onChange={(e) => setFormData({ ...formData, website: e.target.value })}
                  placeholder="https://..."
                />
              </div>
            </div>

            {/* Logo */}
            <div>
              <Label>URL do Logo/Foto</Label>
              <Input
                value={formData.logo}
                onChange={(e) => setFormData({ ...formData, logo: e.target.value })}
                placeholder="https://..."
              />
            </div>

            {/* Opening Hours */}
            <div>
              <Label className="mb-2 block">Horário de Funcionamento</Label>
              <div className="space-y-2">
                {DAYS_OF_WEEK.map(({ key, label }) => {
                  const hours = formData.openingHours[key as keyof typeof formData.openingHours];
                  const isOpen = !!hours;
                  return (
                    <div key={key} className="flex items-center gap-3">
                      <Button
                        type="button"
                        variant={isOpen ? "default" : "outline"}
                        size="sm"
                        className="w-24"
                        onClick={() => toggleDayClosed(key)}
                      >
                        {label}
                      </Button>
                      {isOpen ? (
                        <>
                          <Input
                            type="time"
                            value={hours.open}
                            onChange={(e) => updateOpeningHours(key, "open", e.target.value)}
                            className="w-28"
                          />
                          <span className="text-muted-foreground">até</span>
                          <Input
                            type="time"
                            value={hours.close}
                            onChange={(e) => updateOpeningHours(key, "close", e.target.value)}
                            className="w-28"
                          />
                        </>
                      ) : (
                        <span className="text-muted-foreground text-sm">Fechado</span>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Status & Client */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label>Status</Label>
                <Select
                  value={formData.status}
                  onValueChange={(v) => setFormData({ ...formData, status: v as DirectoryStatus })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Rascunho</SelectItem>
                    <SelectItem value="pending">Pendente</SelectItem>
                    <SelectItem value="active">Ativo</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>É Cliente Premium Menu?</Label>
                <Select
                  value={formData.isClient ? "yes" : "no"}
                  onValueChange={(v) => setFormData({ ...formData, isClient: v === "yes" })}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="no">Não (Prospect)</SelectItem>
                    <SelectItem value="yes">Sim (Cliente)</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* If client, link to menu */}
            {formData.isClient && (
              <div>
                <Label>URL do Cardápio Digital</Label>
                <Input
                  value={formData.menuUrl || ""}
                  onChange={(e) => setFormData({ ...formData, menuUrl: e.target.value })}
                  placeholder="/menu-appetite ou URL completa"
                />
              </div>
            )}

            {/* Internal Notes */}
            <div>
              <Label>Notas Internas (Prospecção)</Label>
              <Textarea
                value={formData.internalNotes}
                onChange={(e) => setFormData({ ...formData, internalNotes: e.target.value })}
                placeholder="Notas visíveis apenas para você (ex: ligar semana que vem, interessado no plano Premium...)"
                rows={3}
              />
            </div>

            {/* Actions */}
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancelar
              </Button>
              <Button onClick={handleSubmit}>
                {editingRestaurant ? "Salvar Alterações" : "Adicionar ao Diretório"}
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>

      {/* Delete Confirmation */}
      <AlertDialog open={!!deleteId} onOpenChange={() => setDeleteId(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Remover do Diretório?</AlertDialogTitle>
            <AlertDialogDescription>
              Esta ação não pode ser desfeita. O restaurante será removido do diretório e do guia público.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancelar</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive hover:bg-destructive/90">
              Remover
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default MasterDirectory;
