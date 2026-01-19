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
import { ArrowLeft, Plus, Edit, Trash2, Palette, Eye, ExternalLink } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface Template {
  id: string;
  name: string;
  slug: string;
  description: string;
  previewImage: string;
  primaryColor: string;
  secondaryColor: string;
  isActive: boolean;
  isPremium: boolean;
  usageCount: number;
}

const MasterTemplates = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [templates, setTemplates] = useState<Template[]>([]);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<Template | null>(null);
  const [formData, setFormData] = useState({
    name: "",
    slug: "",
    description: "",
    previewImage: "",
    primaryColor: "#dc2626",
    secondaryColor: "#fbbf24",
    isActive: true,
    isPremium: false,
  });

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }
    loadTemplates();
  }, [navigate]);

  const loadTemplates = () => {
    const saved = localStorage.getItem("masterTemplates");
    if (saved) {
      setTemplates(JSON.parse(saved));
    } else {
      const mockData: Template[] = [
        {
          id: "1",
          name: "Classic",
          slug: "classic",
          description: "Layout clássico e elegante para restaurantes tradicionais",
          previewImage: "/placeholder.svg",
          primaryColor: "#1f2937",
          secondaryColor: "#f59e0b",
          isActive: true,
          isPremium: false,
          usageCount: 45,
        },
        {
          id: "2",
          name: "Bold",
          slug: "bold",
          description: "Design vibrante e moderno com cores fortes",
          previewImage: "/placeholder.svg",
          primaryColor: "#dc2626",
          secondaryColor: "#fbbf24",
          isActive: true,
          isPremium: false,
          usageCount: 32,
        },
        {
          id: "3",
          name: "Minimal",
          slug: "minimal",
          description: "Estilo minimalista e clean para cafeterias e bistrôs",
          previewImage: "/placeholder.svg",
          primaryColor: "#000000",
          secondaryColor: "#ffffff",
          isActive: true,
          isPremium: true,
          usageCount: 18,
        },
        {
          id: "4",
          name: "Dark Mode",
          slug: "dark",
          description: "Tema escuro sofisticado para bares e lounges",
          previewImage: "/placeholder.svg",
          primaryColor: "#7c3aed",
          secondaryColor: "#a78bfa",
          isActive: true,
          isPremium: true,
          usageCount: 12,
        },
      ];
      setTemplates(mockData);
      localStorage.setItem("masterTemplates", JSON.stringify(mockData));
    }
  };

  const handleSave = () => {
    if (!formData.name || !formData.slug) {
      toast({ title: "Erro", description: "Preencha os campos obrigatórios", variant: "destructive" });
      return;
    }

    let updated: Template[];
    if (editingTemplate) {
      updated = templates.map((t) =>
        t.id === editingTemplate.id
          ? { ...t, ...formData, usageCount: editingTemplate.usageCount }
          : t
      );
      toast({ title: "Sucesso", description: "Template atualizado" });
    } else {
      const newTemplate: Template = {
        id: Date.now().toString(),
        ...formData,
        usageCount: 0,
      };
      updated = [...templates, newTemplate];
      toast({ title: "Sucesso", description: "Template criado" });
    }

    setTemplates(updated);
    localStorage.setItem("masterTemplates", JSON.stringify(updated));
    setIsDialogOpen(false);
    resetForm();
  };

  const handleDelete = (id: string) => {
    const updated = templates.filter((t) => t.id !== id);
    setTemplates(updated);
    localStorage.setItem("masterTemplates", JSON.stringify(updated));
    toast({ title: "Sucesso", description: "Template removido" });
  };

  const handleEdit = (template: Template) => {
    setEditingTemplate(template);
    setFormData({
      name: template.name,
      slug: template.slug,
      description: template.description,
      previewImage: template.previewImage,
      primaryColor: template.primaryColor,
      secondaryColor: template.secondaryColor,
      isActive: template.isActive,
      isPremium: template.isPremium,
    });
    setIsDialogOpen(true);
  };

  const resetForm = () => {
    setEditingTemplate(null);
    setFormData({
      name: "",
      slug: "",
      description: "",
      previewImage: "",
      primaryColor: "#dc2626",
      secondaryColor: "#fbbf24",
      isActive: true,
      isPremium: false,
    });
  };

  const getPreviewUrl = (slug: string) => {
    if (slug === "bold") return "/menu-bold?id=preview";
    return `/menu?id=preview`;
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
            <div className="w-10 h-10 bg-pink-600 rounded-lg flex items-center justify-center">
              <Palette className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">Templates</h1>
              <p className="text-xs text-slate-400">{templates.length} templates disponíveis</p>
            </div>
          </div>
          <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
            <DialogTrigger asChild>
              <Button className="bg-purple-600 hover:bg-purple-700">
                <Plus className="w-4 h-4 mr-2" />
                Novo Template
              </Button>
            </DialogTrigger>
            <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-lg">
              <DialogHeader>
                <DialogTitle>{editingTemplate ? "Editar" : "Novo"} Template</DialogTitle>
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
                    <Label>Slug *</Label>
                    <Input
                      value={formData.slug}
                      onChange={(e) => setFormData({ ...formData, slug: e.target.value.toLowerCase().replace(/\s/g, "-") })}
                      className="bg-slate-700 border-slate-600"
                      placeholder="nome-do-template"
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Descrição</Label>
                  <Input
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    className="bg-slate-700 border-slate-600"
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Cor Primária</Label>
                    <div className="flex gap-2">
                      <input
                        type="color"
                        value={formData.primaryColor}
                        onChange={(e) => setFormData({ ...formData, primaryColor: e.target.value })}
                        className="w-10 h-10 rounded cursor-pointer"
                      />
                      <Input
                        value={formData.primaryColor}
                        onChange={(e) => setFormData({ ...formData, primaryColor: e.target.value })}
                        className="bg-slate-700 border-slate-600 flex-1"
                      />
                    </div>
                  </div>
                  <div className="space-y-2">
                    <Label>Cor Secundária</Label>
                    <div className="flex gap-2">
                      <input
                        type="color"
                        value={formData.secondaryColor}
                        onChange={(e) => setFormData({ ...formData, secondaryColor: e.target.value })}
                        className="w-10 h-10 rounded cursor-pointer"
                      />
                      <Input
                        value={formData.secondaryColor}
                        onChange={(e) => setFormData({ ...formData, secondaryColor: e.target.value })}
                        className="bg-slate-700 border-slate-600 flex-1"
                      />
                    </div>
                  </div>
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
                      checked={formData.isPremium}
                      onCheckedChange={(checked) => setFormData({ ...formData, isPremium: checked })}
                    />
                    <Label>Premium</Label>
                  </div>
                </div>
                <Button onClick={handleSave} className="w-full bg-purple-600 hover:bg-purple-700">
                  {editingTemplate ? "Atualizar" : "Criar"} Template
                </Button>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {templates.map((template) => (
            <Card key={template.id} className="bg-slate-800/50 border-slate-700 overflow-hidden group">
              {/* Preview */}
              <div className="relative h-40 bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center">
                <div
                  className="w-24 h-24 rounded-lg flex items-center justify-center"
                  style={{
                    background: `linear-gradient(135deg, ${template.primaryColor}, ${template.secondaryColor})`,
                  }}
                >
                  <Palette className="w-10 h-10 text-white" />
                </div>
                <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                  <Button
                    size="sm"
                    variant="secondary"
                    className="opacity-0 group-hover:opacity-100 transition-opacity"
                    asChild
                  >
                    <Link to={getPreviewUrl(template.slug)} target="_blank">
                      <Eye className="w-4 h-4 mr-1" />
                      Preview
                    </Link>
                  </Button>
                </div>
                {template.isPremium && (
                  <Badge className="absolute top-2 right-2 bg-purple-600">Premium</Badge>
                )}
              </div>

              <CardContent className="p-4">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-lg font-semibold text-white">{template.name}</h3>
                  <div className="flex gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-slate-400 hover:text-white h-8 w-8"
                      onClick={() => handleEdit(template)}
                    >
                      <Edit className="w-4 h-4" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-slate-400 hover:text-red-400 h-8 w-8"
                      onClick={() => handleDelete(template.id)}
                    >
                      <Trash2 className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
                <p className="text-sm text-slate-400 mb-3">{template.description}</p>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div
                      className="w-5 h-5 rounded-full border border-slate-600"
                      style={{ backgroundColor: template.primaryColor }}
                    />
                    <div
                      className="w-5 h-5 rounded-full border border-slate-600"
                      style={{ backgroundColor: template.secondaryColor }}
                    />
                  </div>
                  <div className="text-sm text-slate-400">
                    {template.usageCount} usos
                  </div>
                </div>
                <div className="mt-3 pt-3 border-t border-slate-700">
                  <Badge variant={template.isActive ? "default" : "secondary"}>
                    {template.isActive ? "Ativo" : "Inativo"}
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

export default MasterTemplates;
