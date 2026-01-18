import { useState } from "react";
import { useSearchParams } from "react-router-dom";
import { CategoryFilter } from "@/components/menu/CategoryFilter";
import { ProductCardBold } from "@/components/menu/ProductCardBold";
import { cn } from "@/lib/utils";
import { LayoutGrid, List, Phone, MapPin } from "lucide-react";
import foodBurger from "@/assets/food-burger.jpg";
import foodPizza from "@/assets/food-pizza.jpg";
import foodSalad from "@/assets/food-salad.jpg";

// Mock data
const mockRestaurant = {
  id: "BOLD123",
  name: "Fast Pizza",
  logo: null,
  address: "Avenida das Nações, 245, São Paulo",
  phone: "+5511999999999",
  isActive: true,
};

const mockCategories = ["Pizzas", "Hambúrgueres", "Bebidas", "Sobremesas"];

const mockProducts = [
  {
    id: "1",
    name: "Especial de 4 Queijos",
    description: "Pizza com blend de mussarela, parmesão, gorgonzola e provolone derretidos",
    price: 23.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["highlight"] as const,
    isAvailable: true,
  },
  {
    id: "2",
    name: "Especial de Calabresa",
    description: "Calabresa fatiada, cebola roxa, azeitonas pretas e orégano",
    price: 26.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["popular"] as const,
    isAvailable: true,
  },
  {
    id: "3",
    name: "Especial Vegetariana",
    description: "Legumes frescos grelhados, queijo e molho especial da casa",
    price: 29.90,
    image: foodSalad,
    category: "Pizzas",
    badges: ["vegetarian", "vegan"] as const,
    isAvailable: true,
  },
  {
    id: "4",
    name: "Smash Burger Duplo",
    description: "Dois smash de 90g, queijo cheddar, bacon crocante e molho especial",
    price: 38.90,
    originalPrice: 45.90,
    image: foodBurger,
    category: "Hambúrgueres",
    badges: ["promo", "popular"] as const,
    isAvailable: true,
  },
  {
    id: "5",
    name: "Pizza Pepperoni",
    description: "Molho de tomate, mussarela, pepperoni importado e orégano",
    price: 54.90,
    image: foodPizza,
    category: "Pizzas",
    badges: [] as const,
    isAvailable: false,
  },
  {
    id: "6",
    name: "Cheese Bacon Deluxe",
    description: "Hambúrguer 180g, queijo prato duplo, bacon crocante, cebola caramelizada",
    price: 34.90,
    image: foodBurger,
    category: "Hambúrgueres",
    badges: [] as const,
    isAvailable: true,
  },
];

export default function MenuBoldPage() {
  const [searchParams] = useSearchParams();
  const restaurantId = searchParams.get("id");
  const [activeCategory, setActiveCategory] = useState("all");
  const [viewMode, setViewMode] = useState<"list" | "grid">("list");

  // Error states
  if (!restaurantId) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-red-900 via-red-700 to-red-900 flex items-center justify-center p-4">
        <div className="text-center text-white">
          <div className="w-20 h-20 rounded-full bg-yellow-400 flex items-center justify-center mx-auto mb-4">
            <span className="text-4xl">🍕</span>
          </div>
          <h1 className="text-2xl font-bold mb-2">Cardápio não encontrado</h1>
          <p className="text-white/70">Verifique se o link está correto.</p>
        </div>
      </div>
    );
  }

  if (restaurantId !== "BOLD123" && restaurantId !== "bold") {
    return (
      <div className="min-h-screen bg-gradient-to-br from-red-900 via-red-700 to-red-900 flex items-center justify-center p-4">
        <div className="text-center text-white">
          <div className="w-20 h-20 rounded-full bg-yellow-400 flex items-center justify-center mx-auto mb-4">
            <span className="text-4xl">😕</span>
          </div>
          <h1 className="text-2xl font-bold mb-2">Restaurante não encontrado</h1>
          <p className="text-white/70">Este cardápio não existe ou foi desativado.</p>
        </div>
      </div>
    );
  }

  const restaurant = mockRestaurant;
  const categories = mockCategories;

  const filteredProducts =
    activeCategory === "all"
      ? mockProducts
      : mockProducts.filter((p) => p.category === activeCategory);

  const handleOrder = (productName: string) => {
    const message = `Olá! Gostaria de pedir: ${productName}`;
    window.open(`https://wa.me/${restaurant.phone}?text=${encodeURIComponent(message)}`, "_blank");
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-red-900 via-red-700 to-red-900">
      {/* Background texture */}
      <div className="fixed inset-0 bg-[url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E')] opacity-20 pointer-events-none" />

      {/* Header */}
      <header className="sticky top-0 z-40 bg-gradient-to-b from-red-900/95 to-red-800/95 backdrop-blur-md border-b border-yellow-400/30">
        <div className="px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              {restaurant.logo ? (
                <img
                  src={restaurant.logo}
                  alt={restaurant.name}
                  className="w-14 h-14 rounded-xl object-cover border-2 border-yellow-400"
                />
              ) : (
                <div className="w-14 h-14 rounded-xl bg-yellow-400 flex items-center justify-center shadow-lg">
                  <span className="text-red-700 font-display font-bold text-2xl">
                    {restaurant.name.charAt(0)}
                  </span>
                </div>
              )}
              <div>
                <h1 className="font-display font-bold text-2xl text-yellow-400 uppercase tracking-wide drop-shadow-lg">
                  {restaurant.name}
                </h1>
                <p className="text-xs text-white/70 flex items-center gap-1">
                  <MapPin className="w-3 h-3" />
                  {restaurant.address}
                </p>
              </div>
            </div>

            {/* View Toggle */}
            <div className="flex bg-red-950/50 rounded-xl p-1">
              <button
                onClick={() => setViewMode("list")}
                className={cn(
                  "p-2 rounded-lg transition-colors",
                  viewMode === "list"
                    ? "bg-yellow-400 text-red-700"
                    : "text-white/70 hover:text-white"
                )}
              >
                <List className="w-5 h-5" />
              </button>
              <button
                onClick={() => setViewMode("grid")}
                className={cn(
                  "p-2 rounded-lg transition-colors",
                  viewMode === "grid"
                    ? "bg-yellow-400 text-red-700"
                    : "text-white/70 hover:text-white"
                )}
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
              className={cn(
                "flex-shrink-0 px-4 py-2 rounded-full font-bold text-sm transition-colors",
                activeCategory === "all"
                  ? "bg-yellow-400 text-red-700"
                  : "bg-red-950/50 text-white/80 hover:bg-red-950"
              )}
            >
              Todos
            </button>
            {categories.map((category) => (
              <button
                key={category}
                onClick={() => setActiveCategory(category)}
                className={cn(
                  "flex-shrink-0 px-4 py-2 rounded-full font-bold text-sm transition-colors",
                  activeCategory === category
                    ? "bg-yellow-400 text-red-700"
                    : "bg-red-950/50 text-white/80 hover:bg-red-950"
                )}
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
            <ProductCardBold
              key={product.id}
              id={product.id}
              name={product.name}
              description={product.description}
              price={product.price}
              originalPrice={product.originalPrice}
              image={product.image}
              badges={product.badges as any}
              isAvailable={product.isAvailable}
              viewMode={viewMode}
              onOrder={() => handleOrder(product.name)}
            />
          ))}
        </div>

        {filteredProducts.length === 0 && (
          <div className="text-center py-12">
            <p className="text-white/70">Nenhum produto nesta categoria.</p>
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="relative px-4 py-6 border-t border-yellow-400/30">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="font-display text-lg font-bold text-yellow-400 uppercase">
              {restaurant.name}
            </h2>
            <p className="text-xs text-white/60 flex items-center gap-1">
              <MapPin className="w-3 h-3" />
              {restaurant.address}
            </p>
          </div>
          <a
            href={`https://wa.me/${restaurant.phone}`}
            target="_blank"
            rel="noopener noreferrer"
            className="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center shadow-lg hover:bg-green-600 transition-colors"
          >
            <Phone className="w-6 h-6 text-white" />
          </a>
        </div>
        <p className="text-center text-xs text-white/40 mt-4">
          Cardápio digital por <span className="text-yellow-400 font-semibold">Premium Menu</span>
        </p>
      </footer>
    </div>
  );
}
