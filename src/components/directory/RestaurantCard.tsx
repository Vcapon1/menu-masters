import { MapPin, Phone, Clock, ExternalLink, Instagram, Globe } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { DirectoryRestaurant } from "@/lib/directoryTypes";

interface RestaurantCardProps {
  restaurant: DirectoryRestaurant;
  onViewDetails?: (restaurant: DirectoryRestaurant) => void;
}

const RestaurantCard = ({ restaurant, onViewDetails }: RestaurantCardProps) => {
  const formatWhatsAppLink = (phone: string) => {
    const cleanPhone = phone.replace(/\D/g, "");
    return `https://wa.me/55${cleanPhone}`;
  };

  const getTodayHours = () => {
    const days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
    const today = days[new Date().getDay()] as keyof typeof restaurant.openingHours;
    const hours = restaurant.openingHours[today];
    
    if (hours) {
      return `${hours.open} - ${hours.close}`;
    }
    return "Fechado";
  };

  return (
    <Card 
      className={`group overflow-hidden transition-all hover:shadow-lg cursor-pointer ${
        restaurant.isClient ? "ring-2 ring-primary/50" : ""
      }`}
      onClick={() => onViewDetails?.(restaurant)}
    >
      <div className="relative aspect-video bg-muted overflow-hidden">
        {restaurant.logo ? (
          <img
            src={restaurant.logo}
            alt={restaurant.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary/20 to-secondary/20">
            <span className="text-4xl font-bold text-primary/40">
              {restaurant.name.charAt(0)}
            </span>
          </div>
        )}
        
        {/* Badges */}
        <div className="absolute top-2 left-2 flex flex-wrap gap-1">
          {restaurant.isClient && (
            <Badge className="bg-primary text-primary-foreground shadow-md">
              ✨ Cardápio Digital
            </Badge>
          )}
          <Badge variant="secondary" className="shadow-sm">
            {restaurant.priceRange}
          </Badge>
        </div>
      </div>

      <CardContent className="p-4">
        <div className="space-y-3">
          {/* Name & Cuisines */}
          <div>
            <h3 className="font-semibold text-lg line-clamp-1">{restaurant.name}</h3>
            <div className="flex flex-wrap gap-1 mt-1">
              {restaurant.cuisineTypes.slice(0, 3).map((cuisine) => (
                <Badge key={cuisine} variant="outline" className="text-xs">
                  {cuisine}
                </Badge>
              ))}
              {restaurant.cuisineTypes.length > 3 && (
                <Badge variant="outline" className="text-xs">
                  +{restaurant.cuisineTypes.length - 3}
                </Badge>
              )}
            </div>
          </div>

          {/* Location */}
          <div className="flex items-start gap-2 text-sm text-muted-foreground">
            <MapPin className="w-4 h-4 mt-0.5 shrink-0" />
            <span className="line-clamp-1">
              {restaurant.neighborhood}, {restaurant.city}
            </span>
          </div>

          {/* Hours */}
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <Clock className="w-4 h-4 shrink-0" />
            <span>Hoje: {getTodayHours()}</span>
          </div>

          {/* Actions */}
          <div className="flex gap-2 pt-2">
            {restaurant.isClient && restaurant.menuUrl && (
              <Button size="sm" className="flex-1" asChild>
                <a href={restaurant.menuUrl} target="_blank" rel="noopener noreferrer">
                  Ver Cardápio
                  <ExternalLink className="w-3 h-3 ml-1" />
                </a>
              </Button>
            )}
            
            {restaurant.whatsapp && (
              <Button 
                size="sm" 
                variant={restaurant.isClient ? "outline" : "default"}
                className={restaurant.isClient ? "" : "flex-1"}
                asChild
                onClick={(e) => e.stopPropagation()}
              >
                <a 
                  href={formatWhatsAppLink(restaurant.whatsapp)} 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  <Phone className="w-3 h-3 mr-1" />
                  WhatsApp
                </a>
              </Button>
            )}

            {restaurant.instagram && (
              <Button 
                size="icon" 
                variant="ghost" 
                className="shrink-0"
                asChild
                onClick={(e) => e.stopPropagation()}
              >
                <a 
                  href={`https://instagram.com/${restaurant.instagram.replace("@", "")}`} 
                  target="_blank" 
                  rel="noopener noreferrer"
                >
                  <Instagram className="w-4 h-4" />
                </a>
              </Button>
            )}

            {restaurant.website && (
              <Button 
                size="icon" 
                variant="ghost" 
                className="shrink-0"
                asChild
                onClick={(e) => e.stopPropagation()}
              >
                <a href={restaurant.website} target="_blank" rel="noopener noreferrer">
                  <Globe className="w-4 h-4" />
                </a>
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default RestaurantCard;
