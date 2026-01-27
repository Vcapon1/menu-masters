import { useState } from "react";
import { useSearchParams } from "react-router-dom";
import { CategoryFilter } from "@/components/menu/CategoryFilter";
import { ProductCard } from "@/components/menu/ProductCard";
import { cn } from "@/lib/utils";
import foodBurger from "@/assets/food-burger.jpg";
import foodPizza from "@/assets/food-pizza.jpg";
import foodSalad from "@/assets/food-salad.jpg";

// Mock data - would come from database
const mockRestaurant = {
  id: "ABC123",
  name: "Restaurante Exemplo",
  logo: null,
  primaryColor: "25 95% 53%",
  template: "visual" as const,
  isActive: true,
  planExpired: false,
};

const mockCategories = ["Hambúrgueres", "Pizzas", "Saladas", "Bebidas", "Sobremesas"];

const mockProducts = [
  {
    id: "1",
    name: "Smash Burger Duplo",
    description: "Dois smash de 90g, queijo cheddar, bacon crocante, alface americana e molho especial",
    price: 38.90,
    originalPrice: 45.90,
    image: foodBurger,
    category: "Hambúrgueres",
    badges: ["promo", "popular"] as const,
    isAvailable: true,
  },
  {
    id: "2",
    name: "Pizza Margherita",
    description: "Molho de tomate italiano, mussarela de búfala, manjericão fresco e azeite extravirgem",
    price: 49.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["vegetarian"] as const,
    isAvailable: true,
  },
  {
    id: "3",
    name: "Salada Caesar com Frango",
    description: "Mix de folhas, frango grelhado, croutons, parmesão e molho caesar",
    price: 32.90,
    image: foodSalad,
    category: "Saladas",
    badges: ["highlight"] as const,
    isAvailable: true,
  },
  {
    id: "4",
    name: "Cheese Bacon",
    description: "Hambúrguer 180g, queijo prato, bacon, cebola caramelizada, picles e molho bbq",
    price: 34.90,
    image: foodBurger,
    category: "Hambúrgueres",
    badges: [] as const,
    isAvailable: true,
  },
  {
    id: "5",
    name: "Pizza Pepperoni",
    description: "Molho de tomate, mussarela, pepperoni importado e orégano",
    price: 54.90,
    image: foodPizza,
    category: "Pizzas",
    badges: ["popular"] as const,
    isAvailable: false,
  },
  {
    id: "6",
    name: "Bowl Vegano",
    description: "Quinoa, grão de bico, abacate, tomate cereja, rúcula e tahine",
    price: 29.90,
    image: foodSalad,
    category: "Saladas",
    badges: ["vegan"] as const,
    isAvailable: true,
  },
];

export default function MenuPage() {
  const [searchParams] = useSearchParams();
  const restaurantId = searchParams.get("id");
  const [activeCategory, setActiveCategory] = useState("all");

  // Simulating different states
  if (!restaurantId) {
    return (
      <div className="menu-container flex items-center justify-center min-h-screen bg-background p-4">
        <div className="text-center">
          <div className="w-20 h-20 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
            <span className="text-4xl">🍽️</span>
          </div>
          <h1 className="text-2xl font-bold text-foreground mb-2">
            Cardápio não encontrado
          </h1>
          <p className="text-muted-foreground">
            Verifique se o link está correto ou escaneie o QR Code novamente.
          </p>
        </div>
      </div>
    );
  }

  // Simulating restaurant not found
  if (restaurantId !== "ABC123" && restaurantId !== "demo") {
    return (
      <div className="menu-container flex items-center justify-center min-h-screen bg-background p-4">
        <div className="text-center">
          <div className="w-20 h-20 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
            <span className="text-4xl">😕</span>
          </div>
          <h1 className="text-2xl font-bold text-foreground mb-2">
            Restaurante não encontrado
          </h1>
          <p className="text-muted-foreground">
            Este cardápio não existe ou foi desativado.
          </p>
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

  return (
    <div className="menu-container min-h-screen bg-background">
      {/* Header */}
      <header className="sticky top-0 z-40 bg-background/95 backdrop-blur-md border-b border-border">
        <div className="px-4 py-4">
          <div className="flex items-center gap-3">
            {restaurant.logo ? (
              <img
                src={restaurant.logo}
                alt={restaurant.name}
                className="w-12 h-12 rounded-xl object-cover"
              />
            ) : (
              <div className="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center">
                <span className="text-primary-foreground font-bold text-xl">
                  {restaurant.name.charAt(0)}
                </span>
              </div>
            )}
            <div>
              <h1 className="font-bold text-lg text-foreground">
                {restaurant.name}
              </h1>
              <p className="text-sm text-muted-foreground">Cardápio Digital</p>
            </div>
          </div>
        </div>

        {/* Category Filter */}
        <CategoryFilter
          categories={categories}
          activeCategory={activeCategory}
          onCategoryChange={setActiveCategory}
        />
      </header>

      {/* Products Grid */}
      <main className="px-4 py-6">
        <div
          className={cn(
            "grid gap-4",
            restaurant.template === "visual"
              ? "grid-cols-2"
              : "grid-cols-1"
          )}
        >
          {filteredProducts.map((product) => (
            <ProductCard
              key={product.id}
              id={product.id}
              name={product.name}
              description={product.description}
              price={product.price}
              originalPrice={product.originalPrice}
              image={product.image}
              badges={product.badges as any}
              isAvailable={product.isAvailable}
              template={restaurant.template}
            />
          ))}
        </div>

        {filteredProducts.length === 0 && (
          <div className="text-center py-12">
            <p className="text-muted-foreground">
              Nenhum produto encontrado nesta categoria.
            </p>
          </div>
        )}
      </main>

      {/* Footer */}
      <footer className="px-4 py-8 text-center border-t border-border">
        <p className="text-xs text-muted-foreground">
          Cardápio digital por{" "}
          <span className="font-semibold text-primary">Cardápio Floripa</span>
        </p>
      </footer>
    </div>
  );
}
