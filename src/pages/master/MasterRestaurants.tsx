import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
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
  Shield,
  ArrowLeft,
  Plus,
  Search,
  Edit,
  Trash2,
  ExternalLink,
  Store,
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface Restaurant {
  id: string;
  name: string;
  email: string;
  phone: string;
  plan: string;
  status: "active" | "inactive" | "trial";
  template: string;
  createdAt: string;
}

const MasterRestaurants = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [restaurants, setRestaurants] = useState<Restaurant[]>([]);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingRestaurant, setEditingRestaurant] = useState<Restaurant | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    plan: "basic",
    status: "trial" as "active" | "inactive" | "trial",
    template: "classic",
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
      // Mock data
      const mockData: Restaurant[] = [
        { id: "1", name: "Pizzaria Bella", email: "contato@bella.com", phone: "(11) 99999-0001", plan: "pro", status: "active", template: "bold", createdAt: "2024-01-15" },
        { id: "2", name: "Burger House", email: "contato@burger.com", phone: "(11) 99999-0002", plan: "basic", status: "active", template: "classic", createdAt: "2024-02-10" },
        { id: "3", name: "Sushi Master", email: "contato@sushi.com", phone: "(11) 99999-0003", plan: "enterprise", status: "active", template: "minimal", createdAt: "2024-01-20" },
        { id: "4", name: "Café Aroma", email: "contato@aroma.com", phone: "(11) 99999-0004", plan: "basic", status: "trial", template: "classic", createdAt: "2024-03-01" },
      ];
      setRestaurants(mockData);
      localStorage.setItem("masterRestaurants", JSON.stringify(mockData));
    }
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
    const updated = restaurants.filter((r) => r.id !== id);
    setRestaurants(updated);
    localStorage.setItem("masterRestaurants", JSON.stringify(updated));
    toast({ title: "Sucesso", description: "Restaurante removido" });
  };

  const handleEdit = (restaurant: Restaurant) => {
    setEditingRestaurant(restaurant);
    setFormData({
      name: restaurant.name,
      email: restaurant.email,
      phone: restaurant.phone,
      plan: restaurant.plan,
      status: restaurant.status,
      template: restaurant.template,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingRestaurant(null);
    setFormData({ name: "", email: "", phone: "", plan: "basic", status: "trial", template: "classic" });
  };

  const filteredRestaurants = restaurants.filter((r) =>
    r.name.toLowerCase().includes(search.toLowerCase()) ||
    r.email.toLowerCase().includes(search.toLowerCase())
  );

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "active":
        return <Badge className="bg-green-600">Ativo</Badge>;
      case "inactive":
        return <Badge variant="secondary">Inativo</Badge>;
      case "trial":
        return <Badge className="bg-yellow-600">Trial</Badge>;
      default:
        return null;
    }
  };

  const getPlanBadge = (plan: string) => {
    switch (plan) {
      case "basic":
        return <Badge variant="outline" className="border-slate-500 text-slate-300">Basic</Badge>;
      case "pro":
        return <Badge className="bg-blue-600">Pro</Badge>;
      case "enterprise":
        return <Badge className="bg-purple-600">Enterprise</Badge>;
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
            <DialogContent className="bg-slate-800 border-slate-700 text-white">
              <DialogHeader>
                <DialogTitle>{editingRestaurant ? "Editar" : "Novo"} Restaurante</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label>Nome *</Label>
                  <Input
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="bg-slate-700 border-slate-600"
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
                <div className="grid grid-cols-3 gap-4">
                  <div className="space-y-2">
                    <Label>Plano</Label>
                    <Select value={formData.plan} onValueChange={(v) => setFormData({ ...formData, plan: v })}>
                      <SelectTrigger className="bg-slate-700 border-slate-600">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-slate-700 border-slate-600">
                        <SelectItem value="basic">Basic</SelectItem>
                        <SelectItem value="pro">Pro</SelectItem>
                        <SelectItem value="enterprise">Enterprise</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Status</Label>
                    <Select value={formData.status} onValueChange={(v: any) => setFormData({ ...formData, status: v })}>
                      <SelectTrigger className="bg-slate-700 border-slate-600">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-slate-700 border-slate-600">
                        <SelectItem value="trial">Trial</SelectItem>
                        <SelectItem value="active">Ativo</SelectItem>
                        <SelectItem value="inactive">Inativo</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label>Template</Label>
                    <Select value={formData.template} onValueChange={(v) => setFormData({ ...formData, template: v })}>
                      <SelectTrigger className="bg-slate-700 border-slate-600">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent className="bg-slate-700 border-slate-600">
                        <SelectItem value="classic">Classic</SelectItem>
                        <SelectItem value="bold">Bold</SelectItem>
                        <SelectItem value="minimal">Minimal</SelectItem>
                      </SelectContent>
                    </Select>
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
                  <TableHead className="text-slate-300">Restaurante</TableHead>
                  <TableHead className="text-slate-300">Contato</TableHead>
                  <TableHead className="text-slate-300">Plano</TableHead>
                  <TableHead className="text-slate-300">Status</TableHead>
                  <TableHead className="text-slate-300">Template</TableHead>
                  <TableHead className="text-slate-300 text-right">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredRestaurants.map((restaurant) => (
                  <TableRow key={restaurant.id} className="border-slate-700">
                    <TableCell className="text-white font-medium">{restaurant.name}</TableCell>
                    <TableCell>
                      <div className="text-sm text-slate-300">{restaurant.email}</div>
                      <div className="text-xs text-slate-500">{restaurant.phone}</div>
                    </TableCell>
                    <TableCell>{getPlanBadge(restaurant.plan)}</TableCell>
                    <TableCell>{getStatusBadge(restaurant.status)}</TableCell>
                    <TableCell className="text-slate-300 capitalize">{restaurant.template}</TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
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
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-slate-400 hover:text-blue-400"
                          asChild
                        >
                          <Link to={`/menu-bold?id=${restaurant.id}`} target="_blank">
                            <ExternalLink className="w-4 h-4" />
                          </Link>
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
