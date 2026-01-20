import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { 
  ArrowLeft, 
  Eye,
  TrendingUp,
  Utensils,
  Calendar,
  BarChart3
} from "lucide-react";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
} from "recharts";

interface ProductView {
  productId: string;
  productName: string;
  views: number;
}

interface DailyAccess {
  date: string;
  views: number;
}

export default function AdminStats() {
  const [period, setPeriod] = useState("30");
  const navigate = useNavigate();

  useEffect(() => {
    const session = localStorage.getItem("adminSession");
    if (!session) {
      navigate("/admin");
      return;
    }
  }, [navigate]);

  // Mock data - In production, this would come from the backend
  const totalMenuViews = 1247;
  const totalProductViews = 3892;
  const avgDailyViews = Math.round(totalMenuViews / parseInt(period));
  const topViewsGrowth = 12.5;

  const dailyAccessData: DailyAccess[] = [
    { date: "01/01", views: 42 },
    { date: "02/01", views: 38 },
    { date: "03/01", views: 55 },
    { date: "04/01", views: 67 },
    { date: "05/01", views: 48 },
    { date: "06/01", views: 72 },
    { date: "07/01", views: 85 },
    { date: "08/01", views: 63 },
    { date: "09/01", views: 58 },
    { date: "10/01", views: 91 },
    { date: "11/01", views: 78 },
    { date: "12/01", views: 82 },
    { date: "13/01", views: 95 },
    { date: "14/01", views: 88 },
  ];

  const topProducts: ProductView[] = [
    { productId: "1", productName: "Hambúrguer Artesanal", views: 324 },
    { productId: "2", productName: "Pizza Margherita", views: 287 },
    { productId: "3", productName: "Salada Caesar", views: 198 },
    { productId: "4", productName: "Refrigerante 600ml", views: 176 },
    { productId: "5", productName: "Batata Frita", views: 165 },
    { productId: "6", productName: "Sobremesa do Dia", views: 142 },
    { productId: "7", productName: "Suco Natural", views: 128 },
    { productId: "8", productName: "Água Mineral", views: 112 },
  ];

  const categoryViews = [
    { name: "Pratos Principais", views: 520, color: "hsl(var(--primary))" },
    { name: "Bebidas", views: 340, color: "hsl(var(--chart-2))" },
    { name: "Entradas", views: 280, color: "hsl(var(--chart-3))" },
    { name: "Sobremesas", views: 190, color: "hsl(var(--chart-4))" },
    { name: "Combos", views: 150, color: "hsl(var(--chart-5))" },
  ];

  const hourlyData = [
    { hour: "08h", views: 12 },
    { hour: "10h", views: 28 },
    { hour: "12h", views: 95 },
    { hour: "14h", views: 67 },
    { hour: "16h", views: 34 },
    { hour: "18h", views: 78 },
    { hour: "20h", views: 112 },
    { hour: "22h", views: 45 },
  ];

  const stats = [
    {
      title: "Visualizações do Cardápio",
      value: totalMenuViews.toLocaleString('pt-BR'),
      icon: Eye,
      description: `Últimos ${period} dias`,
      trend: `+${topViewsGrowth}%`,
      trendUp: true,
    },
    {
      title: "Visualizações de Pratos",
      value: totalProductViews.toLocaleString('pt-BR'),
      icon: Utensils,
      description: "Cliques em detalhes",
      trend: "+8.3%",
      trendUp: true,
    },
    {
      title: "Média Diária",
      value: avgDailyViews.toString(),
      icon: Calendar,
      description: "Acessos por dia",
      trend: "+5.2%",
      trendUp: true,
    },
    {
      title: "Taxa de Engajamento",
      value: `${((totalProductViews / totalMenuViews) * 100).toFixed(1)}%`,
      icon: TrendingUp,
      description: "Cliques / Visualizações",
      trend: "+2.1%",
      trendUp: true,
    },
  ];

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
              <h1 className="font-bold text-lg">Estatísticas</h1>
              <p className="text-xs text-muted-foreground">Acompanhe os acessos ao seu cardápio</p>
            </div>
          </div>
          <Select value={period} onValueChange={setPeriod}>
            <SelectTrigger className="w-[140px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7">Últimos 7 dias</SelectItem>
              <SelectItem value="30">Últimos 30 dias</SelectItem>
              <SelectItem value="90">Últimos 90 dias</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8 space-y-6">
        {/* Stats Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {stats.map((stat) => (
            <Card key={stat.title}>
              <CardContent className="pt-6">
                <div className="flex items-center justify-between mb-2">
                  <stat.icon className="w-5 h-5 text-muted-foreground" />
                  <span className={`text-xs font-medium ${stat.trendUp ? 'text-green-600' : 'text-red-600'}`}>
                    {stat.trend}
                  </span>
                </div>
                <p className="text-2xl font-bold">{stat.value}</p>
                <p className="text-xs text-muted-foreground">{stat.title}</p>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Charts Row 1 */}
        <div className="grid md:grid-cols-2 gap-6">
          {/* Daily Access Chart */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <BarChart3 className="w-5 h-5" />
                Acessos Diários
              </CardTitle>
              <CardDescription>Visualizações do cardápio por dia</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-[300px]">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={dailyAccessData}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                    <XAxis dataKey="date" className="text-xs" />
                    <YAxis className="text-xs" />
                    <Tooltip 
                      contentStyle={{ 
                        backgroundColor: 'hsl(var(--card))',
                        border: '1px solid hsl(var(--border))',
                        borderRadius: '8px'
                      }}
                    />
                    <Line 
                      type="monotone" 
                      dataKey="views" 
                      stroke="hsl(var(--primary))" 
                      strokeWidth={2}
                      dot={{ fill: 'hsl(var(--primary))' }}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>

          {/* Hourly Distribution */}
          <Card>
            <CardHeader>
              <CardTitle>Horários de Pico</CardTitle>
              <CardDescription>Distribuição de acessos por horário</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-[300px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={hourlyData}>
                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                    <XAxis dataKey="hour" className="text-xs" />
                    <YAxis className="text-xs" />
                    <Tooltip 
                      contentStyle={{ 
                        backgroundColor: 'hsl(var(--card))',
                        border: '1px solid hsl(var(--border))',
                        borderRadius: '8px'
                      }}
                    />
                    <Bar 
                      dataKey="views" 
                      fill="hsl(var(--primary))" 
                      radius={[4, 4, 0, 0]}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Charts Row 2 */}
        <div className="grid md:grid-cols-2 gap-6">
          {/* Top Products */}
          <Card>
            <CardHeader>
              <CardTitle>Pratos Mais Visualizados</CardTitle>
              <CardDescription>Top 8 pratos com mais cliques</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {topProducts.map((product, index) => (
                  <div key={product.productId} className="flex items-center gap-3">
                    <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ${
                      index < 3 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'
                    }`}>
                      {index + 1}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium text-sm">{product.productName}</p>
                      <div className="w-full bg-muted rounded-full h-2 mt-1">
                        <div 
                          className="bg-primary h-2 rounded-full transition-all"
                          style={{ width: `${(product.views / topProducts[0].views) * 100}%` }}
                        />
                      </div>
                    </div>
                    <span className="text-sm font-medium text-muted-foreground">
                      {product.views}
                    </span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Category Distribution */}
          <Card>
            <CardHeader>
              <CardTitle>Visualizações por Categoria</CardTitle>
              <CardDescription>Distribuição de acessos</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="h-[250px]">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={categoryViews}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={100}
                      dataKey="views"
                      nameKey="name"
                      label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                      labelLine={false}
                    >
                      {categoryViews.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip 
                      contentStyle={{ 
                        backgroundColor: 'hsl(var(--card))',
                        border: '1px solid hsl(var(--border))',
                        borderRadius: '8px'
                      }}
                    />
                  </PieChart>
                </ResponsiveContainer>
              </div>
              <div className="grid grid-cols-2 gap-2 mt-4">
                {categoryViews.map((category) => (
                  <div key={category.name} className="flex items-center gap-2 text-sm">
                    <div 
                      className="w-3 h-3 rounded-full" 
                      style={{ backgroundColor: category.color }}
                    />
                    <span className="text-muted-foreground">{category.name}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Info Note */}
        <Card className="bg-muted/50">
          <CardContent className="pt-6">
            <p className="text-sm text-muted-foreground text-center">
              📊 As estatísticas são atualizadas a cada hora. Os dados exibidos são referentes ao período selecionado.
            </p>
          </CardContent>
        </Card>
      </main>
    </div>
  );
}
