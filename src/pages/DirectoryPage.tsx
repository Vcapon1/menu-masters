import { useState, useMemo } from "react";
import { Link } from "react-router-dom";
import DirectorySearch from "@/components/directory/DirectorySearch";
import DirectoryFilters from "@/components/directory/DirectoryFilters";
import RestaurantCard from "@/components/directory/RestaurantCard";
import { DirectoryRestaurant, PriceRange } from "@/lib/directoryTypes";
import { Button } from "@/components/ui/button";
import { UtensilsCrossed, ArrowRight } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { MapPin, Phone, Clock, Instagram, Globe, ExternalLink } from "lucide-react";

// Mock data - will come from localStorage/API
const mockRestaurants: DirectoryRestaurant[] = [
  {
    id: "1",
    name: "Trattoria Bella Italia",
    slug: "trattoria-bella-italia",
    address: "Rua Augusta, 1234",
    neighborhood: "Jardins",
    city: "São Paulo",
    cuisineTypes: ["Italiana", "Pizza"],
    logo: "https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=400&h=300&fit=crop",
    phone: "(11) 3456-7890",
    whatsapp: "11934567890",
    instagram: "@trattoria_bella",
    website: "https://trattoriabella.com.br",
    openingHours: {
      monday: { open: "11:00", close: "23:00" },
      tuesday: { open: "11:00", close: "23:00" },
      wednesday: { open: "11:00", close: "23:00" },
      thursday: { open: "11:00", close: "23:00" },
      friday: { open: "11:00", close: "00:00" },
      saturday: { open: "12:00", close: "00:00" },
      sunday: { open: "12:00", close: "22:00" },
    },
    priceRange: "$$$",
    isClient: true,
    linkedClientId: "rest-1",
    menuUrl: "/menu-appetite",
    status: "active",
    internalNotes: "",
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: "2",
    name: "Burger House",
    slug: "burger-house",
    address: "Av. Paulista, 567",
    neighborhood: "Consolação",
    city: "São Paulo",
    cuisineTypes: ["Hambúrguer", "Americana"],
    logo: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop",
    phone: "(11) 2345-6789",
    whatsapp: "11923456789",
    instagram: "@burgerhouse_sp",
    website: "",
    openingHours: {
      monday: { open: "11:30", close: "22:00" },
      tuesday: { open: "11:30", close: "22:00" },
      wednesday: { open: "11:30", close: "22:00" },
      thursday: { open: "11:30", close: "22:00" },
      friday: { open: "11:30", close: "23:00" },
      saturday: { open: "12:00", close: "23:00" },
    },
    priceRange: "$$",
    isClient: false,
    status: "active",
    internalNotes: "Potencial cliente - ligar semana que vem",
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: "3",
    name: "Sushi Zen",
    slug: "sushi-zen",
    address: "Rua da Liberdade, 890",
    neighborhood: "Liberdade",
    city: "São Paulo",
    cuisineTypes: ["Japonesa", "Sushi"],
    logo: "https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=300&fit=crop",
    phone: "(11) 3456-7890",
    whatsapp: "11934567890",
    instagram: "@sushizen_sp",
    website: "https://sushizen.com.br",
    openingHours: {
      tuesday: { open: "18:00", close: "23:00" },
      wednesday: { open: "18:00", close: "23:00" },
      thursday: { open: "18:00", close: "23:00" },
      friday: { open: "18:00", close: "00:00" },
      saturday: { open: "12:00", close: "00:00" },
      sunday: { open: "12:00", close: "22:00" },
    },
    priceRange: "$$$$",
    isClient: true,
    linkedClientId: "rest-2",
    menuUrl: "/menu-bold",
    status: "active",
    internalNotes: "",
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: "4",
    name: "Café da Manhã",
    slug: "cafe-da-manha",
    address: "Rua Oscar Freire, 234",
    neighborhood: "Jardins",
    city: "São Paulo",
    cuisineTypes: ["Cafeteria", "Padaria", "Saudável"],
    logo: "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&h=300&fit=crop",
    phone: "(11) 4567-8901",
    whatsapp: "11945678901",
    instagram: "@cafedamanha_jardins",
    website: "",
    openingHours: {
      monday: { open: "07:00", close: "18:00" },
      tuesday: { open: "07:00", close: "18:00" },
      wednesday: { open: "07:00", close: "18:00" },
      thursday: { open: "07:00", close: "18:00" },
      friday: { open: "07:00", close: "18:00" },
      saturday: { open: "08:00", close: "16:00" },
    },
    priceRange: "$",
    isClient: false,
    status: "active",
    internalNotes: "Interessado - enviar proposta",
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
];

const DirectoryPage = () => {
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedCuisines, setSelectedCuisines] = useState<string[]>([]);
  const [selectedNeighborhoods, setSelectedNeighborhoods] = useState<string[]>([]);
  const [selectedPriceRange, setSelectedPriceRange] = useState<PriceRange | null>(null);
  const [selectedRestaurant, setSelectedRestaurant] = useState<DirectoryRestaurant | null>(null);

  // Load restaurants from localStorage or use mock data
  const restaurants = useMemo(() => {
    const stored = localStorage.getItem("directoryRestaurants");
    if (stored) {
      const parsed = JSON.parse(stored) as DirectoryRestaurant[];
      return parsed.filter((r) => r.status === "active");
    }
    return mockRestaurants;
  }, []);

  // Filter restaurants
  const filteredRestaurants = useMemo(() => {
    return restaurants.filter((restaurant) => {
      // Search query
      if (searchQuery) {
        const query = searchQuery.toLowerCase();
        const matchesName = restaurant.name.toLowerCase().includes(query);
        const matchesCuisine = restaurant.cuisineTypes.some((c) =>
          c.toLowerCase().includes(query)
        );
        const matchesNeighborhood = restaurant.neighborhood.toLowerCase().includes(query);
        if (!matchesName && !matchesCuisine && !matchesNeighborhood) {
          return false;
        }
      }

      // Cuisine filter
      if (selectedCuisines.length > 0) {
        const hasCuisine = selectedCuisines.some((c) =>
          restaurant.cuisineTypes.includes(c)
        );
        if (!hasCuisine) return false;
      }

      // Neighborhood filter
      if (selectedNeighborhoods.length > 0) {
        if (!selectedNeighborhoods.includes(restaurant.neighborhood)) {
          return false;
        }
      }

      // Price range filter
      if (selectedPriceRange && restaurant.priceRange !== selectedPriceRange) {
        return false;
      }

      return true;
    });
  }, [restaurants, searchQuery, selectedCuisines, selectedNeighborhoods, selectedPriceRange]);

  const clearAllFilters = () => {
    setSelectedCuisines([]);
    setSelectedNeighborhoods([]);
    setSelectedPriceRange(null);
    setSearchQuery("");
  };

  const formatWhatsAppLink = (phone: string) => {
    const cleanPhone = phone.replace(/\D/g, "");
    return `https://wa.me/55${cleanPhone}`;
  };

  const getDayLabel = (day: string) => {
    const labels: Record<string, string> = {
      monday: "Segunda",
      tuesday: "Terça",
      wednesday: "Quarta",
      thursday: "Quinta",
      friday: "Sexta",
      saturday: "Sábado",
      sunday: "Domingo",
    };
    return labels[day] || day;
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="bg-card border-b sticky top-0 z-40">
        <div className="max-w-7xl mx-auto px-4 py-4">
          <div className="flex items-center justify-between gap-4">
            <Link to="/" className="flex items-center gap-2">
              <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                <UtensilsCrossed className="w-5 h-5 text-primary-foreground" />
              </div>
              <div>
                <h1 className="font-bold text-lg">Guia Gastronômico</h1>
                <p className="text-xs text-muted-foreground">by Premium Menu</p>
              </div>
            </Link>

            <div className="flex-1 max-w-md hidden md:block">
              <DirectorySearch value={searchQuery} onChange={setSearchQuery} />
            </div>

            <Button variant="outline" asChild>
              <Link to="/">
                Para Restaurantes
                <ArrowRight className="w-4 h-4 ml-2" />
              </Link>
            </Button>
          </div>

          {/* Mobile Search */}
          <div className="mt-4 md:hidden">
            <DirectorySearch value={searchQuery} onChange={setSearchQuery} />
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 py-6">
        <div className="flex gap-6">
          {/* Filters */}
          <DirectoryFilters
            selectedCuisines={selectedCuisines}
            selectedNeighborhoods={selectedNeighborhoods}
            selectedPriceRange={selectedPriceRange}
            onCuisineChange={setSelectedCuisines}
            onNeighborhoodChange={setSelectedNeighborhoods}
            onPriceRangeChange={setSelectedPriceRange}
            onClearAll={clearAllFilters}
          />

          {/* Results */}
          <div className="flex-1">
            {/* Mobile Filter Button */}
            <div className="lg:hidden mb-4">
              <DirectoryFilters
                selectedCuisines={selectedCuisines}
                selectedNeighborhoods={selectedNeighborhoods}
                selectedPriceRange={selectedPriceRange}
                onCuisineChange={setSelectedCuisines}
                onNeighborhoodChange={setSelectedNeighborhoods}
                onPriceRangeChange={setSelectedPriceRange}
                onClearAll={clearAllFilters}
              />
            </div>

            {/* Results Count */}
            <div className="mb-4">
              <p className="text-sm text-muted-foreground">
                {filteredRestaurants.length}{" "}
                {filteredRestaurants.length === 1 ? "restaurante encontrado" : "restaurantes encontrados"}
              </p>
            </div>

            {/* Restaurant Grid */}
            {filteredRestaurants.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                {filteredRestaurants.map((restaurant) => (
                  <RestaurantCard
                    key={restaurant.id}
                    restaurant={restaurant}
                    onViewDetails={setSelectedRestaurant}
                  />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <UtensilsCrossed className="w-12 h-12 mx-auto text-muted-foreground/50 mb-4" />
                <h3 className="text-lg font-medium mb-2">Nenhum restaurante encontrado</h3>
                <p className="text-muted-foreground mb-4">
                  Tente ajustar os filtros ou busca
                </p>
                <Button variant="outline" onClick={clearAllFilters}>
                  Limpar filtros
                </Button>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* Restaurant Detail Dialog */}
      <Dialog open={!!selectedRestaurant} onOpenChange={() => setSelectedRestaurant(null)}>
        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
          {selectedRestaurant && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  {selectedRestaurant.name}
                  {selectedRestaurant.isClient && (
                    <Badge className="bg-primary">✨ Cardápio Digital</Badge>
                  )}
                </DialogTitle>
              </DialogHeader>

              <div className="space-y-4">
                {/* Image */}
                {selectedRestaurant.logo && (
                  <div className="aspect-video rounded-lg overflow-hidden">
                    <img
                      src={selectedRestaurant.logo}
                      alt={selectedRestaurant.name}
                      className="w-full h-full object-cover"
                    />
                  </div>
                )}

                {/* Cuisines & Price */}
                <div className="flex flex-wrap gap-2">
                  {selectedRestaurant.cuisineTypes.map((cuisine) => (
                    <Badge key={cuisine} variant="secondary">
                      {cuisine}
                    </Badge>
                  ))}
                  <Badge variant="outline">{selectedRestaurant.priceRange}</Badge>
                </div>

                {/* Address */}
                <div className="flex items-start gap-2">
                  <MapPin className="w-4 h-4 mt-1 text-muted-foreground" />
                  <div>
                    <p>{selectedRestaurant.address}</p>
                    <p className="text-sm text-muted-foreground">
                      {selectedRestaurant.neighborhood}, {selectedRestaurant.city}
                    </p>
                  </div>
                </div>

                {/* Opening Hours */}
                <div className="flex items-start gap-2">
                  <Clock className="w-4 h-4 mt-1 text-muted-foreground" />
                  <div className="text-sm">
                    <p className="font-medium mb-1">Horário de funcionamento</p>
                    <div className="grid grid-cols-2 gap-x-4 gap-y-1">
                      {(["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as const).map(
                        (day) => {
                          const hours = selectedRestaurant.openingHours[day];
                          return (
                            <div key={day} className="flex justify-between">
                              <span className="text-muted-foreground">{getDayLabel(day)}:</span>
                              <span>{hours ? `${hours.open} - ${hours.close}` : "Fechado"}</span>
                            </div>
                          );
                        }
                      )}
                    </div>
                  </div>
                </div>

                {/* Contact */}
                {selectedRestaurant.phone && (
                  <div className="flex items-center gap-2">
                    <Phone className="w-4 h-4 text-muted-foreground" />
                    <span>{selectedRestaurant.phone}</span>
                  </div>
                )}

                {/* Links */}
                <div className="flex flex-wrap gap-2">
                  {selectedRestaurant.instagram && (
                    <Button variant="outline" size="sm" asChild>
                      <a
                        href={`https://instagram.com/${selectedRestaurant.instagram.replace("@", "")}`}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <Instagram className="w-4 h-4 mr-2" />
                        Instagram
                      </a>
                    </Button>
                  )}
                  {selectedRestaurant.website && (
                    <Button variant="outline" size="sm" asChild>
                      <a href={selectedRestaurant.website} target="_blank" rel="noopener noreferrer">
                        <Globe className="w-4 h-4 mr-2" />
                        Site
                      </a>
                    </Button>
                  )}
                </div>

                {/* CTA Buttons */}
                <div className="flex gap-2 pt-2">
                  {selectedRestaurant.isClient && selectedRestaurant.menuUrl && (
                    <Button className="flex-1" asChild>
                      <a href={selectedRestaurant.menuUrl} target="_blank" rel="noopener noreferrer">
                        Ver Cardápio Completo
                        <ExternalLink className="w-4 h-4 ml-2" />
                      </a>
                    </Button>
                  )}
                  {selectedRestaurant.whatsapp && (
                    <Button
                      variant={selectedRestaurant.isClient ? "outline" : "default"}
                      className={selectedRestaurant.isClient ? "" : "flex-1"}
                      asChild
                    >
                      <a
                        href={formatWhatsAppLink(selectedRestaurant.whatsapp)}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <Phone className="w-4 h-4 mr-2" />
                        WhatsApp
                      </a>
                    </Button>
                  )}
                </div>
              </div>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default DirectoryPage;
