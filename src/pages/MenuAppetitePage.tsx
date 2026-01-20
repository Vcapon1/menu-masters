import { useState, useRef, useEffect } from "react";
import { AppetiteHeader } from "@/components/menu/AppetiteHeader";
import { AppetiteCategoryNav } from "@/components/menu/AppetiteCategoryNav";
import { ProductCardAppetite } from "@/components/menu/ProductCardAppetite";
import { WhatsAppFloat } from "@/components/WhatsAppFloat";
import { useRef as useReactRef } from "react";
import { Grid3X3, List, Search, X } from "lucide-react";
import { cn } from "@/lib/utils";

// Import assets
import burgerImg from "@/assets/food-burger.jpg";
import pizzaImg from "@/assets/food-pizza.jpg";
import saladImg from "@/assets/food-salad.jpg";

// Mock data
const mockRestaurant = {
  name: "Sabor & Arte",
  logo: "",
  address: "Rua das Flores, 123 - Centro",
  isOpen: true,
  closingTime: "23:00",
  whatsapp: "5511999999999",
};

const mockCategories = [
  { id: "all", name: "Todos", icon: "🍽️" },
  { id: "burgers", name: "Burgers", icon: "🍔" },
  { id: "pizzas", name: "Pizzas", icon: "🍕" },
  { id: "salads", name: "Saladas", icon: "🥗" },
  { id: "drinks", name: "Bebidas", icon: "🥤" },
  { id: "desserts", name: "Sobremesas", icon: "🍰" },
];

const mockProducts = [
  {
    id: "1",
    categoryId: "burgers",
    name: "Smash Burger Duplo",
    description: "Dois smash de 90g, queijo cheddar, cebola caramelizada, picles e molho especial",
    price: 32.90,
    originalPrice: 38.90,
    image: burgerImg,
    badges: ["promo", "popular"] as const,
    isAvailable: true,
  },
  {
    id: "2",
    categoryId: "burgers",
    name: "Classic Bacon",
    description: "Hambúrguer artesanal 180g, bacon crocante, queijo prato, alface e tomate",
    price: 29.90,
    image: burgerImg,
    badges: [] as const,
    isAvailable: true,
  },
  {
    id: "3",
    categoryId: "burgers",
    name: "Veggie Burger",
    description: "Hambúrguer de grão de bico, queijo vegano, rúcula e molho de ervas",
    price: 28.90,
    image: burgerImg,
    badges: ["vegan"] as const,
    isAvailable: true,
  },
  {
    id: "4",
    categoryId: "pizzas",
    name: "Margherita Especial",
    description: "Molho de tomate San Marzano, mozzarella de búfala, manjericão fresco",
    price: 49.90,
    image: pizzaImg,
    badges: ["highlight"] as const,
    isAvailable: true,
  },
  {
    id: "5",
    categoryId: "pizzas",
    name: "Pepperoni Premium",
    description: "Pepperoni importado, mozzarella, orégano e azeite trufado",
    price: 54.90,
    image: pizzaImg,
    badges: ["popular"] as const,
    isAvailable: true,
  },
  {
    id: "6",
    categoryId: "salads",
    name: "Caesar Salad",
    description: "Alface romana, croutons, parmesão, molho caesar e frango grelhado",
    price: 26.90,
    image: saladImg,
    badges: [] as const,
    isAvailable: true,
  },
  {
    id: "7",
    categoryId: "salads",
    name: "Buddha Bowl",
    description: "Quinoa, grão de bico, legumes assados, abacate e tahine",
    price: 34.90,
    image: saladImg,
    badges: ["vegan", "highlight"] as const,
    isAvailable: true,
  },
  {
    id: "8",
    categoryId: "burgers",
    name: "BBQ Monster",
    description: "Hambúrguer 200g, onion rings, bacon, cheddar e molho BBQ defumado",
    price: 38.90,
    image: burgerImg,
    badges: ["out"] as const,
    isAvailable: false,
  },
];

export default function MenuAppetitePage() {
  const [activeCategory, setActiveCategory] = useState("all");
  const [viewMode, setViewMode] = useState<"list" | "grid">("list");
  const [searchQuery, setSearchQuery] = useState("");
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const sectionRefs = useRef<{ [key: string]: HTMLDivElement | null }>({});

  // Filter products
  const filteredProducts = mockProducts.filter((product) => {
    const matchesCategory = activeCategory === "all" || product.categoryId === activeCategory;
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                         product.description.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && (searchQuery ? matchesSearch : true);
  });

  // Group products by category
  const groupedProducts = mockCategories
    .filter((cat) => cat.id !== "all")
    .map((category) => ({
      ...category,
      products: filteredProducts.filter((p) => p.categoryId === category.id),
    }))
    .filter((group) => group.products.length > 0);

  // Handle category change - scroll to section
  const handleCategoryChange = (categoryId: string) => {
    setActiveCategory(categoryId);
    
    if (categoryId !== "all") {
      const section = sectionRefs.current[categoryId];
      if (section) {
        const headerOffset = 130; // Header + Nav height
        const elementPosition = section.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        window.scrollTo({
          top: offsetPosition,
          behavior: "smooth",
        });
      }
    } else {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };

  // Intersection Observer for scroll spy
  useEffect(() => {
    const observers: IntersectionObserver[] = [];
    
    mockCategories.forEach((category) => {
      if (category.id === "all") return;
      
      const section = sectionRefs.current[category.id];
      if (!section) return;

      const observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              setActiveCategory(category.id);
            }
          });
        },
        { threshold: 0.3, rootMargin: "-130px 0px -60% 0px" }
      );

      observer.observe(section);
      observers.push(observer);
    });

    return () => {
      observers.forEach((obs) => obs.disconnect());
    };
  }, []);

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <AppetiteHeader
        restaurantName={mockRestaurant.name}
        logo={mockRestaurant.logo}
        address={mockRestaurant.address}
        isOpen={mockRestaurant.isOpen}
        closingTime={mockRestaurant.closingTime}
      />

      {/* Category Navigation */}
      <AppetiteCategoryNav
        categories={mockCategories}
        activeCategory={activeCategory}
        onCategoryChange={handleCategoryChange}
      />

      {/* Toolbar */}
      <div className="sticky top-[116px] z-20 bg-background/95 backdrop-blur-md px-4 py-2 flex items-center justify-between gap-2 border-b border-border/30">
        {/* Search */}
        <div className={cn(
          "flex items-center gap-2 transition-all duration-200",
          isSearchOpen ? "flex-1" : ""
        )}>
          {isSearchOpen ? (
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <input
                type="text"
                placeholder="Buscar pratos..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-9 pr-9 py-2 rounded-full bg-muted text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                autoFocus
              />
              <button
                onClick={() => {
                  setIsSearchOpen(false);
                  setSearchQuery("");
                }}
                className="absolute right-3 top-1/2 -translate-y-1/2"
              >
                <X className="w-4 h-4 text-muted-foreground" />
              </button>
            </div>
          ) : (
            <button
              onClick={() => setIsSearchOpen(true)}
              className="p-2 rounded-full bg-muted text-muted-foreground hover:bg-muted/80 transition-colors"
            >
              <Search className="w-4 h-4" />
            </button>
          )}
        </div>

        {/* View Toggle */}
        {!isSearchOpen && (
          <div className="flex items-center bg-muted rounded-full p-1">
            <button
              onClick={() => setViewMode("list")}
              className={cn(
                "p-1.5 rounded-full transition-all",
                viewMode === "list"
                  ? "bg-background text-foreground shadow-sm"
                  : "text-muted-foreground"
              )}
            >
              <List className="w-4 h-4" />
            </button>
            <button
              onClick={() => setViewMode("grid")}
              className={cn(
                "p-1.5 rounded-full transition-all",
                viewMode === "grid"
                  ? "bg-background text-foreground shadow-sm"
                  : "text-muted-foreground"
              )}
            >
              <Grid3X3 className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>

      {/* Content */}
      <main className="px-4 pb-24 pt-4">
        {searchQuery && filteredProducts.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-muted-foreground">
              Nenhum prato encontrado para "{searchQuery}"
            </p>
          </div>
        ) : activeCategory === "all" || searchQuery ? (
          // Show grouped by category
          <div className="space-y-8">
            {groupedProducts.map((group) => (
              <section
                key={group.id}
                ref={(el: HTMLDivElement | null) => { sectionRefs.current[group.id] = el; }}
                className="scroll-mt-36"
              >
                {/* Category Title */}
                <div className="flex items-center gap-2 mb-4">
                  <span className="text-xl">{group.icon}</span>
                  <h2 className="font-semibold text-lg text-foreground">{group.name}</h2>
                  <div className="flex-1 h-px bg-border ml-2" />
                </div>

                {/* Products */}
                <div className={cn(
                  viewMode === "grid"
                    ? "grid grid-cols-2 gap-3"
                    : "space-y-3"
                )}>
                  {group.products.map((product, index) => (
                    <div
                      key={product.id}
                      className="animate-fade-in"
                      style={{ animationDelay: `${index * 50}ms` }}
                    >
                      <ProductCardAppetite
                        id={product.id}
                        name={product.name}
                        description={product.description}
                        price={product.price}
                        originalPrice={product.originalPrice}
                        image={product.image}
                        badges={[...product.badges]}
                        isAvailable={product.isAvailable}
                        viewMode={viewMode}
                        onAdd={() => {
                          // TODO: Add to cart
                          console.log("Add to cart:", product.name);
                        }}
                      />
                    </div>
                  ))}
                </div>
              </section>
            ))}
          </div>
        ) : (
          // Show single category
          <div className={cn(
            viewMode === "grid"
              ? "grid grid-cols-2 gap-3"
              : "space-y-3"
          )}>
            {filteredProducts.map((product, index) => (
              <div
                key={product.id}
                className="animate-fade-in"
                style={{ animationDelay: `${index * 50}ms` }}
              >
                <ProductCardAppetite
                  id={product.id}
                  name={product.name}
                  description={product.description}
                  price={product.price}
                  originalPrice={product.originalPrice}
                  image={product.image}
                  badges={[...product.badges]}
                  isAvailable={product.isAvailable}
                  viewMode={viewMode}
                  onAdd={() => {
                    console.log("Add to cart:", product.name);
                  }}
                />
              </div>
            ))}
          </div>
        )}
      </main>

      {/* WhatsApp Float */}
      <WhatsAppFloat phoneNumber={mockRestaurant.whatsapp} />
    </div>
  );
}
