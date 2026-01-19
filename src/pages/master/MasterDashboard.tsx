import { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
  Shield,
  Store,
  CreditCard,
  BarChart3,
  Palette,
  LogOut,
  Users,
  TrendingUp,
  DollarSign,
  Activity,
} from "lucide-react";

const MasterDashboard = () => {
  const navigate = useNavigate();
  const [stats, setStats] = useState({
    totalRestaurants: 0,
    activeRestaurants: 0,
    totalRevenue: 0,
    activeSubscriptions: 0,
  });

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }

    // Load mock stats
    const restaurants = JSON.parse(localStorage.getItem("masterRestaurants") || "[]");
    setStats({
      totalRestaurants: restaurants.length || 12,
      activeRestaurants: restaurants.filter((r: any) => r.status === "active").length || 10,
      totalRevenue: 15890,
      activeSubscriptions: 8,
    });
  }, [navigate]);

  const handleLogout = () => {
    localStorage.removeItem("masterAuth");
    localStorage.removeItem("masterUser");
    navigate("/master");
  };

  const menuItems = [
    {
      title: "Restaurantes",
      description: "Gerenciar todos os restaurantes",
      icon: Store,
      href: "/master/restaurants",
      color: "bg-blue-600",
    },
    {
      title: "Planos",
      description: "Configurar planos e preços",
      icon: CreditCard,
      href: "/master/plans",
      color: "bg-green-600",
    },
    {
      title: "Relatórios",
      description: "Análises e métricas",
      icon: BarChart3,
      href: "/master/reports",
      color: "bg-orange-600",
    },
    {
      title: "Templates",
      description: "Gerenciar templates de cardápio",
      icon: Palette,
      href: "/master/templates",
      color: "bg-pink-600",
    },
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      {/* Header */}
      <header className="bg-slate-800/50 backdrop-blur-sm border-b border-purple-500/30">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
              <Shield className="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 className="text-xl font-bold text-white">Admin Master</h1>
              <p className="text-xs text-slate-400">Painel de Controle</p>
            </div>
          </div>
          <Button
            variant="ghost"
            onClick={handleLogout}
            className="text-slate-300 hover:text-white hover:bg-slate-700"
          >
            <LogOut className="w-4 h-4 mr-2" />
            Sair
          </Button>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 py-8">
        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-slate-400">Total Restaurantes</p>
                  <p className="text-3xl font-bold text-white">{stats.totalRestaurants}</p>
                </div>
                <div className="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                  <Users className="w-6 h-6 text-blue-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-slate-400">Ativos</p>
                  <p className="text-3xl font-bold text-white">{stats.activeRestaurants}</p>
                </div>
                <div className="w-12 h-12 bg-green-600/20 rounded-lg flex items-center justify-center">
                  <Activity className="w-6 h-6 text-green-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-slate-400">Receita Mensal</p>
                  <p className="text-3xl font-bold text-white">
                    R$ {stats.totalRevenue.toLocaleString("pt-BR")}
                  </p>
                </div>
                <div className="w-12 h-12 bg-purple-600/20 rounded-lg flex items-center justify-center">
                  <DollarSign className="w-6 h-6 text-purple-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-slate-800/50 border-slate-700">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-slate-400">Assinaturas Ativas</p>
                  <p className="text-3xl font-bold text-white">{stats.activeSubscriptions}</p>
                </div>
                <div className="w-12 h-12 bg-orange-600/20 rounded-lg flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-orange-400" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Menu Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {menuItems.map((item) => (
            <Link key={item.href} to={item.href}>
              <Card className="bg-slate-800/50 border-slate-700 hover:border-purple-500/50 transition-all cursor-pointer group">
                <CardContent className="p-6">
                  <div className="flex items-center gap-4">
                    <div className={`w-14 h-14 ${item.color} rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform`}>
                      <item.icon className="w-7 h-7 text-white" />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-white">{item.title}</h3>
                      <p className="text-sm text-slate-400">{item.description}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      </main>
    </div>
  );
};

export default MasterDashboard;
