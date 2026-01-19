import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { ArrowLeft, Plus, Edit, Trash2, CreditCard, Check } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface Plan {
  id: string;
  name: string;
  price: number;
  billingCycle: "monthly" | "yearly";
  features: string[];
  maxProducts: number;
  maxCategories: number;
  isActive: boolean;
  isPopular: boolean;
}

const MasterPlans = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [plans, setPlans] = useState<Plan[]>([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<Plan | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    price: 0,
    billingCycle: "monthly" as "monthly" | "yearly",
    features: "",
    maxProducts: 50,
    maxCategories: 10,
    isActive: true,
    isPopular: false,
  });

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }
    loadPlans();
  }, [navigate]);

  const loadPlans = () => {
    const saved = localStorage.getItem("masterPlans");
    if (saved) {
      setPlans(JSON.parse(saved));
    } else {
      const mockData: Plan[] = [
        {
          id: "1",
          name: "Basic",
          price: 49.90,
          billingCycle: "monthly",
          features: ["Até 50 produtos", "5 categorias", "1 template", "Suporte por email"],
          maxProducts: 50,
          maxCategories: 5,
          isActive: true,
          isPopular: false,
        },
        {
          id: "2",
          name: "Pro",
          price: 99.90,
          billingCycle: "monthly",
          features: ["Até 200 produtos", "20 categorias", "Todos os templates", "QR Code ilimitado", "Suporte prioritário"],
          maxProducts: 200,
          maxCategories: 20,
          isActive: true,
          isPopular: true,
        },
        {
          id: "3",
          name: "Enterprise",
          price: 199.90,
          billingCycle: "monthly",
          features: ["Produtos ilimitados", "Categorias ilimitadas", "Templates exclusivos", "API acesso", "Suporte 24/7", "Multi-loja"],
          maxProducts: -1,
          maxCategories: -1,
          isActive: true,
          isPopular: false,
        },
      ];
      setPlans(mockData);
      localStorage.setItem("masterPlans", JSON.stringify(mockData));
    }
  };

  const handleSave = () => {
    if (!formData.name || formData.price <= 0) {
      toast({ title: "Erro", description: "Preencha os campos obrigatórios", variant: "destructive" });
      return;
    }

    const featuresArray = formData.features.split("\n").filter((f) => f.trim());

    let updated: Plan[];
    if (editingPlan) {
      updated = plans.map((p) =>
        p.id === editingPlan.id
          ? { ...p, ...formData, features: featuresArray }
          : p
      );
      toast({ title: "Sucesso", description: "Plano atualizado" });
    } else {
      const newPlan: Plan = {
        id: Date.now().toString(),
        ...formData,
        features: featuresArray,
      };
      updated = [...plans, newPlan];
      toast({ title: "Sucesso", description: "Plano criado" });
    }

    setPlans(updated);
    localStorage.setItem("masterPlans", JSON.stringify(updated));
    setIsDialogOpen(false);
    resetForm();
  };

  const handleDelete = (id: string) => {
    const updated = plans.filter((p) => p.id !== id);
    setPlans(updated);
    localStorage.setItem("masterPlans", JSON.stringify(updated));
    toast({ title: "Sucesso", description: "Plano removido" });
  };

  const handleEdit = (plan: Plan) => {
    setEditingPlan(plan);
    setFormData({
      name: plan.name,
      price: plan.price,
      billingCycle: plan.billingCycle,
      features: plan.features.join("\n"),
      maxProducts: plan.maxProducts,
      maxCategories: plan.maxCategories,
      isActive: plan.isActive,
      isPopular: plan.isPopular,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingPlan(null);
    setFormData({
      name: "",
      price: 0,
      billingCycle: "monthly",
      features: "",
      maxProducts: 50,
      maxCategories: 10,
      isActive: true,
      isPopular: false,
    });
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
            <div className="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
              <CreditCard className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">Planos</h1>
              <p className="text-xs text-slate-400">{plans.length} planos configurados</p>
            </div>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
            <DialogTrigger asChild>
              <Button className="bg-purple-600 hover:bg-purple-700">
                <Plus className="w-4 h-4 mr-2" />
                Novo Plano
              </Button>
            </DialogTrigger>
            <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-lg">
              <DialogHeader>
                <DialogTitle>{editingPlan ? "Editar" : "Novo"} Plano</DialogTitle>
              </DialogHeader>
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Nome *</Label>
                    <Input
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="bg-slate-700 border-slate-600"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Preço (R$) *</Label>
                    <Input
                      type="number"
                      step="0.01"
                      value={formData.price}
                      onChange={(e) => setFormData({ ...formData, price: parseFloat(e.target.value) })}
                      className="bg-slate-700 border-slate-600"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Max Produtos (-1 = ilimitado)</Label>
                    <Input
                      type="number"
                      value={formData.maxProducts}
                      onChange={(e) => setFormData({ ...formData, maxProducts: parseInt(e.target.value) })}
                      className="bg-slate-700 border-slate-600"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Max Categorias (-1 = ilimitado)</Label>
                    <Input
                      type="number"
                      value={formData.maxCategories}
                      onChange={(e) => setFormData({ ...formData, maxCategories: parseInt(e.target.value) })}
                      className="bg-slate-700 border-slate-600"
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Recursos (um por linha)</Label>
                  <textarea
                    value={formData.features}
                    onChange={(e) => setFormData({ ...formData, features: e.target.value })}
                    className="w-full h-24 bg-slate-700 border border-slate-600 rounded-md p-2 text-white text-sm"
                    placeholder="Recurso 1&#10;Recurso 2&#10;Recurso 3"
                  />
                </div>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <Switch
                      checked={formData.isActive}
                      onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
                    />
                    <Label>Ativo</Label>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch
                      checked={formData.isPopular}
                      onCheckedChange={(checked) => setFormData({ ...formData, isPopular: checked })}
                    />
                    <Label>Popular</Label>
                  </div>
                </div>
                <Button onClick={handleSave} className="w-full bg-purple-600 hover:bg-purple-700">
                  {editingPlan ? "Atualizar" : "Criar"} Plano
                </Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <Card
              key={plan.id}
              className={`bg-slate-800/50 border-slate-700 relative ${
                plan.isPopular ? "ring-2 ring-purple-500" : ""
              }`}
            >
              {plan.isPopular && (
                <Badge className="absolute -top-3 left-1/2 -translate-x-1/2 bg-purple-600">
                  Mais Popular
                </Badge>
              )}
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-white">{plan.name}</CardTitle>
                  <div className="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-slate-400 hover:text-white"
                      onClick={() => handleEdit(plan)}
                    >
                      <Edit className="w-4 h-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-slate-400 hover:text-red-400"
                      onClick={() => handleDelete(plan.id)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
                <div className="flex items-baseline gap-1">
                  <span className="text-3xl font-bold text-white">
                    R$ {plan.price.toFixed(2).replace(".", ",")}
                  </span>
                  <span className="text-slate-400">/mês</span>
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {plan.features.map((feature, idx) => (
                    <div key={idx} className="flex items-center gap-2">
                      <Check className="w-4 h-4 text-green-500" />
                      <span className="text-sm text-slate-300">{feature}</span>
                    </div>
                  ))}
                </div>
                <div className="mt-4 pt-4 border-t border-slate-700 flex items-center gap-2">
                  <Badge variant={plan.isActive ? "default" : "secondary"}>
                    {plan.isActive ? "Ativo" : "Inativo"}
                  </Badge>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </main>
    </div>
  );
};

export default MasterPlans;
