import { useEffect, useState, useMemo } from "react";
import { useNavigate, useSearchParams, Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
  ArrowLeft,
  LayoutGrid,
  List,
  Phone,
  MapPin,
  Monitor,
  Smartphone,
  ExternalLink,
} from "lucide-react";
import foodBurger from "@/assets/food-burger.jpg";
import foodPizza from "@/assets/food-pizza.jpg";
import foodSalad from "@/assets/food-salad.jpg";

interface Restaurant {
  id: string;
  name: string;
  slug: string;
  address: string;
  phone: string;
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
  template: string;
}

const mockCategories = ["Pizzas", "Hambúrgueres", "Bebidas", "Sobremesas"];

const mockProducts = [
  {
    id: "1",
    name: "Especial de 4 Queijos",
    description: "Pizza com blend de mussarela, parmesão, gorgonzola e provolone",
    price: 23.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["highlight"],
    isAvailable: true,
  },
  {
    id: "2",
    name: "Especial de Calabresa",
    description: "Calabresa fatiada, cebola roxa, azeitonas pretas",
    price: 26.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["popular"],
    isAvailable: true,
  },
  {
    id: "3",
    name: "Smash Burger Duplo",
    description: "Dois smash de 90g, queijo cheddar, bacon crocante",
    price: 38.90,
    originalPrice: 45.90,
    image: foodBurger,
    category: "Hambúrgueres",
    badges: ["promo", "popular"],
    isAvailable: true,
  },
  {
    id: "4",
    name: "Salada Fresh",
    description: "Mix de folhas, tomate cereja, queijo e molho especial",
    price: 19.90,
    image: foodSalad,
    category: "Bebidas",
    badges: ["vegetarian"],
    isAvailable: true,
  },
];

const TemplatePreview = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const restaurantId = searchParams.get("id");
  const [restaurant, setRestaurant] = useState<Restaurant | null>(null);
  const [activeCategory, setActiveCategory] = useState("all");
  const [viewMode, setViewMode] = useState<"list" | "grid">("list");
  const [deviceView, setDeviceView] = useState<"desktop" | "mobile">("mobile");

  useEffect(() => {
    const isAuth = localStorage.getItem("masterAuth");
    if (!isAuth) {
      navigate("/master");
      return;
    }

    if (!restaurantId) {
      navigate("/master/restaurants");
      return;
    }

    const saved = localStorage.getItem("masterRestaurants");
    if (saved) {
      const restaurants: Restaurant[] = JSON.parse(saved);
      const found = restaurants.find((r) => r.id === restaurantId || r.slug === restaurantId);
      if (found) {
        setRestaurant(found);
      } else {
        navigate("/master/restaurants");
      }
    }
  }, [navigate, restaurantId]);

  const filteredProducts = useMemo(() => {
    return activeCategory === "all"
      ? mockProducts
      : mockProducts.filter((p) => p.category === activeCategory);
  }, [activeCategory]);

  const cssVars = useMemo(() => {
    if (!restaurant) return {};
    return {
      "--preview-primary": restaurant.primaryColor,
      "--preview-secondary": restaurant.secondaryColor,
      "--preview-accent": restaurant.accentColor || restaurant.primaryColor,
      "--preview-button": restaurant.buttonColor || restaurant.primaryColor,
      "--preview-button-text": restaurant.buttonTextColor || "#ffffff",
      "--preview-font": restaurant.fontColor || "#ffffff",
    } as React.CSSProperties;
  }, [restaurant]);

  if (!restaurant) {
    return (
      <div className="min-h-screen bg-slate-900 flex items-center justify-center">
        <p className="text-white">Carregando...</p>
      </div>
    );
  }

  const formatPrice = (price: number) =>
    price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

  const getBadgeStyle = (badge: string) => {
    switch (badge) {
      case "highlight":
        return { backgroundColor: restaurant.secondaryColor, color: "#000" };
      case "popular":
        return { backgroundColor: restaurant.accentColor, color: "#fff" };
      case "promo":
        return { backgroundColor: "#22c55e", color: "#fff" };
      case "vegetarian":
        return { backgroundColor: "#16a34a", color: "#fff" };
      default:
        return {};
    }
  };

  const getBadgeLabel = (badge: string) => {
    switch (badge) {
      case "highlight":
        return "⭐ Destaque";
      case "popular":
        return "🔥 Popular";
      case "promo":
        return "💰 Promoção";
      case "vegetarian":
        return "🥬 Vegetariano";
      default:
        return badge;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
      {/* Header */}
      <header className="bg-slate-800/50 backdrop-blur-sm border-b border-purple-500/30 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Link to="/master/restaurants">
              <Button variant="ghost" size="icon" className="text-slate-300 hover:text-white">
                <ArrowLeft className="w-5 h-5" />
              </Button>
            </Link>
            <div>
              <h1 className="text-lg font-bold text-white">Preview do Template</h1>
              <p className="text-xs text-slate-400">{restaurant.name}</p>
            </div>
          </div>
          <div className="flex items-center gap-4">
            {/* Device Toggle */}
            <div className="flex bg-slate-700/50 rounded-lg p-1">
              <button
                onClick={() => setDeviceView("mobile")}
                className={cn(
                  "p-2 rounded transition-colors",
                  deviceView === "mobile"
                    ? "bg-purple-600 text-white"
                    : "text-slate-400 hover:text-white"
                )}
              >
                <Smartphone className="w-4 h-4" />
              </button>
              <button
                onClick={() => setDeviceView("desktop")}
                className={cn(
                  "p-2 rounded transition-colors",
                  deviceView === "desktop"
                    ? "bg-purple-600 text-white"
                    : "text-slate-400 hover:text-white"
                )}
              >
                <Monitor className="w-4 h-4" />
              </button>
            </div>
            <Button
              variant="outline"
              size="sm"
              className="border-purple-500 text-purple-300"
              onClick={() => window.open(`/menu-bold?id=${restaurant.slug}`, "_blank")}
            >
              <ExternalLink className="w-4 h-4 mr-2" />
              Abrir em Nova Aba
            </Button>
          </div>
        </div>
      </header>

      {/* Preview Container */}
      <main className="p-6 flex justify-center">
        <div
          className={cn(
            "bg-slate-950 rounded-2xl overflow-hidden shadow-2xl transition-all duration-300",
            deviceView === "mobile" ? "w-[390px]" : "w-full max-w-4xl"
          )}
          style={cssVars}
        >
          {/* Mobile Frame */}
          {deviceView === "mobile" && (
            <div className="h-6 bg-slate-900 flex items-center justify-center">
              <div className="w-20 h-4 bg-slate-800 rounded-full" />
            </div>
          )}

          {/* Template Content */}
          <div
            className="min-h-[700px] overflow-y-auto"
            style={{
              background: restaurant.backgroundImage
                ? `linear-gradient(to bottom, ${restaurant.primaryColor}ee, ${restaurant.primaryColor}dd), url(${restaurant.backgroundImage}) center/cover`
                : `linear-gradient(135deg, ${restaurant.primaryColor}, ${restaurant.primaryColor}cc)`,
            }}
          >
            {/* Background texture overlay */}
            <div className="fixed inset-0 bg-[url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E')] opacity-10 pointer-events-none" />

            {/* Header */}
            <header
              className="sticky top-0 z-40 backdrop-blur-md border-b"
              style={{
                background: `linear-gradient(to bottom, ${restaurant.primaryColor}f5, ${restaurant.primaryColor}ee)`,
                borderColor: `${restaurant.secondaryColor}40`,
              }}
            >
              <div className="px-4 py-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    {restaurant.logo ? (
                      <img
                        src={restaurant.logo}
                        alt={restaurant.name}
                        className="w-14 h-14 rounded-xl object-cover border-2"
                        style={{ borderColor: restaurant.secondaryColor }}
                      />
                    ) : (
                      <div
                        className="w-14 h-14 rounded-xl flex items-center justify-center shadow-lg"
                        style={{ backgroundColor: restaurant.secondaryColor }}
                      >
                        <span
                          className="font-bold text-2xl"
                          style={{ color: restaurant.primaryColor }}
                        >
                          {restaurant.name.charAt(0)}
                        </span>
                      </div>
                    )}
                    <div>
                      <h1
                        className="font-bold text-2xl uppercase tracking-wide drop-shadow-lg"
                        style={{ color: restaurant.secondaryColor }}
                      >
                        {restaurant.name}
                      </h1>
                      <p
                        className="text-xs flex items-center gap-1 opacity-70"
                        style={{ color: restaurant.fontColor }}
                      >
                        <MapPin className="w-3 h-3" />
                        {restaurant.address || "Endereço não informado"}
                      </p>
                    </div>
                  </div>

                  {/* View Toggle */}
                  <div
                    className="flex rounded-xl p-1"
                    style={{ backgroundColor: `${restaurant.primaryColor}80` }}
                  >
                    <button
                      onClick={() => setViewMode("list")}
                      className="p-2 rounded-lg transition-colors"
                      style={{
                        backgroundColor: viewMode === "list" ? restaurant.secondaryColor : "transparent",
                        color: viewMode === "list" ? restaurant.primaryColor : `${restaurant.fontColor}99`,
                      }}
                    >
                      <List className="w-5 h-5" />
                    </button>
                    <button
                      onClick={() => setViewMode("grid")}
                      className="p-2 rounded-lg transition-colors"
                      style={{
                        backgroundColor: viewMode === "grid" ? restaurant.secondaryColor : "transparent",
                        color: viewMode === "grid" ? restaurant.primaryColor : `${restaurant.fontColor}99`,
                      }}
                    >
                      <LayoutGrid className="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>

              {/* Category Filter */}
              <div className="px-4 pb-3">
                <div className="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
                  <button
                    onClick={() => setActiveCategory("all")}
                    className="flex-shrink-0 px-4 py-2 rounded-full font-bold text-sm transition-colors"
                    style={{
                      backgroundColor: activeCategory === "all" ? restaurant.secondaryColor : `${restaurant.primaryColor}80`,
                      color: activeCategory === "all" ? restaurant.primaryColor : `${restaurant.fontColor}cc`,
                    }}
                  >
                    Todos
                  </button>
                  {mockCategories.map((category) => (
                    <button
                      key={category}
                      onClick={() => setActiveCategory(category)}
                      className="flex-shrink-0 px-4 py-2 rounded-full font-bold text-sm transition-colors"
                      style={{
                        backgroundColor: activeCategory === category ? restaurant.secondaryColor : `${restaurant.primaryColor}80`,
                        color: activeCategory === category ? restaurant.primaryColor : `${restaurant.fontColor}cc`,
                      }}
                    >
                      {category}
                    </button>
                  ))}
                </div>
              </div>
            </header>

            {/* Products */}
            <main className="relative px-4 py-6">
              <div
                className={cn(
                  "grid gap-4",
                  viewMode === "grid" ? "grid-cols-2" : "grid-cols-1"
                )}
              >
                {filteredProducts.map((product) => (
                  <div
                    key={product.id}
                    className={cn(
                      "rounded-2xl overflow-hidden shadow-lg transition-transform hover:scale-[1.02]",
                      viewMode === "list" ? "flex" : ""
                    )}
                    style={{
                      backgroundColor: `${restaurant.primaryColor}90`,
                      borderLeft: `4px solid ${restaurant.secondaryColor}`,
                    }}
                  >
                    <img
                      src={product.image}
                      alt={product.name}
                      className={cn(
                        "object-cover",
                        viewMode === "list" ? "w-28 h-28" : "w-full h-32"
                      )}
                    />
                    <div className="p-3 flex-1">
                      {/* Badges */}
                      {product.badges.length > 0 && (
                        <div className="flex flex-wrap gap-1 mb-2">
                          {product.badges.map((badge) => (
                            <Badge
                              key={badge}
                              className="text-xs px-2 py-0.5"
                              style={getBadgeStyle(badge)}
                            >
                              {getBadgeLabel(badge)}
                            </Badge>
                          ))}
                        </div>
                      )}
                      <h3
                        className="font-bold text-sm mb-1"
                        style={{ color: restaurant.fontColor }}
                      >
                        {product.name}
                      </h3>
                      <p
                        className="text-xs opacity-70 mb-2 line-clamp-2"
                        style={{ color: restaurant.fontColor }}
                      >
                        {product.description}
                      </p>
                      <div className="flex items-center justify-between">
                        <div>
                          {product.originalPrice && (
                            <span
                              className="text-xs line-through opacity-50 mr-2"
                              style={{ color: restaurant.fontColor }}
                            >
                              {formatPrice(product.originalPrice)}
                            </span>
                          )}
                          <span
                            className="font-bold text-lg"
                            style={{ color: restaurant.secondaryColor }}
                          >
                            {formatPrice(product.price)}
                          </span>
                        </div>
                        <button
                          className="px-3 py-1 rounded-lg text-xs font-bold transition-colors"
                          style={{
                            backgroundColor: restaurant.buttonColor,
                            color: restaurant.buttonTextColor,
                          }}
                        >
                          Pedir
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </main>

            {/* Footer */}
            <footer
              className="relative px-4 py-6 border-t"
              style={{ borderColor: `${restaurant.secondaryColor}40` }}
            >
              <div className="flex items-center justify-between">
                <div>
                  <h2
                    className="text-lg font-bold uppercase"
                    style={{ color: restaurant.secondaryColor }}
                  >
                    {restaurant.name}
                  </h2>
                  <p
                    className="text-xs flex items-center gap-1 opacity-60"
                    style={{ color: restaurant.fontColor }}
                  >
                    <MapPin className="w-3 h-3" />
                    {restaurant.address || "Endereço não informado"}
                  </p>
                </div>
                <div
                  className="w-12 h-12 rounded-full flex items-center justify-center shadow-lg"
                  style={{ backgroundColor: "#22c55e" }}
                >
                  <Phone className="w-6 h-6 text-white" />
                </div>
              </div>
              <p
                className="text-center text-xs mt-4 opacity-40"
                style={{ color: restaurant.fontColor }}
              >
                Cardápio digital por{" "}
                <span style={{ color: restaurant.secondaryColor }} className="font-semibold">
                  Cardápio Floripa
                </span>
              </p>
            </footer>
          </div>
        </div>
      </main>

      {/* Color Legend */}
      <div className="fixed bottom-4 left-4 bg-slate-800/90 backdrop-blur rounded-xl p-4 shadow-xl">
        <h4 className="text-xs font-semibold text-slate-400 mb-3 uppercase tracking-wide">
          Cores Aplicadas
        </h4>
        <div className="grid gap-2 text-xs">
          <div className="flex items-center gap-2">
            <div
              className="w-5 h-5 rounded border border-slate-600"
              style={{ backgroundColor: restaurant.primaryColor }}
            />
            <span className="text-slate-300">Primária</span>
          </div>
          <div className="flex items-center gap-2">
            <div
              className="w-5 h-5 rounded border border-slate-600"
              style={{ backgroundColor: restaurant.secondaryColor }}
            />
            <span className="text-slate-300">Secundária</span>
          </div>
          <div className="flex items-center gap-2">
            <div
              className="w-5 h-5 rounded border border-slate-600"
              style={{ backgroundColor: restaurant.accentColor }}
            />
            <span className="text-slate-300">Destaque</span>
          </div>
          <div className="flex items-center gap-2">
            <div
              className="w-5 h-5 rounded border border-slate-600"
              style={{ backgroundColor: restaurant.buttonColor }}
            />
            <span className="text-slate-300">Botões</span>
          </div>
          <div className="flex items-center gap-2">
            <div
              className="w-5 h-5 rounded border border-slate-600"
              style={{ backgroundColor: restaurant.fontColor }}
            />
            <span className="text-slate-300">Fonte</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TemplatePreview;
