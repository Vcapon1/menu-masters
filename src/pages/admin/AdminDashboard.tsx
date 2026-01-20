import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { 
  UtensilsCrossed, 
  LayoutGrid, 
  Package, 
  QrCode, 
  LogOut,
  Plus,
  Eye,
  BarChart3
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface AdminSession {
  restaurantId: string;
  restaurantName: string;
  email: string;
  loggedIn: boolean;
}

export default function AdminDashboard() {
  const [session, setSession] = useState<AdminSession | null>(null);
  const navigate = useNavigate();
  const { toast } = useToast();

  useEffect(() => {
    const storedSession = localStorage.getItem("adminSession");
    if (!storedSession) {
      navigate("/admin");
      return;
    }
    
    const parsed = JSON.parse(storedSession);
    if (!parsed.loggedIn) {
      navigate("/admin");
      return;
    }
    
    setSession(parsed);
  }, [navigate]);

  const handleLogout = () => {
    localStorage.removeItem("adminSession");
    toast({
      title: "Logout realizado",
      description: "Até logo!",
    });
    navigate("/admin");
  };

  // Get stats from localStorage
  const categories = JSON.parse(localStorage.getItem("categories") || "[]");
  const products = JSON.parse(localStorage.getItem("products") || "[]");

  if (!session) return null;

  return (
    <div className="min-h-screen bg-muted/30">
      {/* Header */}
      <header className="bg-card border-b sticky top-0 z-50">
        <div className="container mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-gradient-premium rounded-xl flex items-center justify-center">
              <UtensilsCrossed className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="font-bold text-lg">{session.restaurantName}</h1>
              <p className="text-xs text-muted-foreground">{session.email}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Button 
              variant="outline" 
              size="sm"
              onClick={() => window.open(`/menu?id=${session.restaurantId}`, '_blank')}
            >
              <Eye className="w-4 h-4 mr-2" />
              Ver Menu
            </Button>
            <Button variant="ghost" size="sm" onClick={handleLogout}>
              <LogOut className="w-4 h-4" />
            </Button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        <div className="mb-8">
          <h2 className="text-2xl font-bold">Painel de Controle</h2>
          <p className="text-muted-foreground">Gerencie seu cardápio digital</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Categorias
              </CardTitle>
              <LayoutGrid className="w-4 h-4 text-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">{categories.length}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Produtos
              </CardTitle>
              <Package className="w-4 h-4 text-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">{products.length}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                QR Code
              </CardTitle>
              <QrCode className="w-4 h-4 text-primary" />
            </CardHeader>
            <CardContent>
              <Button variant="outline" size="sm" className="mt-1">
                Baixar QR Code
              </Button>
            </CardContent>
          </Card>
        </div>

        {/* Quick Actions */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => navigate("/admin/categories")}>
            <CardHeader>
              <div className="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center mb-4">
                <LayoutGrid className="w-6 h-6 text-primary" />
              </div>
              <CardTitle>Categorias</CardTitle>
              <CardDescription>
                Organize seu cardápio em categorias como Entradas, Pratos Principais, Bebidas, etc.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button className="w-full">
                <Plus className="w-4 h-4 mr-2" />
                Gerenciar Categorias
              </Button>
            </CardContent>
          </Card>

          <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => navigate("/admin/products")}>
            <CardHeader>
              <div className="w-12 h-12 bg-accent/10 rounded-xl flex items-center justify-center mb-4">
                <Package className="w-6 h-6 text-accent" />
              </div>
              <CardTitle>Pratos</CardTitle>
              <CardDescription>
                Adicione e edite seus pratos, bebidas e sobremesas com fotos e vídeos.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button className="w-full" variant="outline">
                <Plus className="w-4 h-4 mr-2" />
                Gerenciar Pratos
              </Button>
            </CardContent>
          </Card>

          <Card className="hover:shadow-lg transition-shadow cursor-pointer" onClick={() => navigate("/admin/stats")}>
            <CardHeader>
              <div className="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center mb-4">
                <BarChart3 className="w-6 h-6 text-green-600" />
              </div>
              <CardTitle>Estatísticas</CardTitle>
              <CardDescription>
                Acompanhe os acessos ao seu cardápio e veja quais pratos são mais populares.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button className="w-full" variant="outline">
                <BarChart3 className="w-4 h-4 mr-2" />
                Ver Estatísticas
              </Button>
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
}
