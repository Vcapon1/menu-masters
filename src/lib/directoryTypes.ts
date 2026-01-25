// Types for the Restaurant Directory / Guia Gastronômico

export interface OpeningHours {
  monday?: { open: string; close: string };
  tuesday?: { open: string; close: string };
  wednesday?: { open: string; close: string };
  thursday?: { open: string; close: string };
  friday?: { open: string; close: string };
  saturday?: { open: string; close: string };
  sunday?: { open: string; close: string };
}

export type PriceRange = "$" | "$$" | "$$$" | "$$$$";
export type DirectoryStatus = "active" | "pending" | "draft";

export interface DirectoryRestaurant {
  id: string;
  name: string;
  slug: string;
  address: string;
  neighborhood: string;
  city: string;
  cuisineTypes: string[];
  logo: string;
  phone: string;
  whatsapp: string;
  instagram: string;
  website: string;
  openingHours: OpeningHours;
  priceRange: PriceRange;
  isClient: boolean;
  linkedClientId?: string;
  menuUrl?: string;
  status: DirectoryStatus;
  internalNotes: string;
  createdAt: string;
  updatedAt: string;
}

// Common cuisine types for suggestions
export const CUISINE_TYPES = [
  "Brasileira",
  "Italiana",
  "Japonesa",
  "Chinesa",
  "Mexicana",
  "Árabe",
  "Portuguesa",
  "Francesa",
  "Americana",
  "Pizza",
  "Hambúrguer",
  "Churrascaria",
  "Frutos do Mar",
  "Vegetariana",
  "Vegana",
  "Saudável",
  "Fast Food",
  "Cafeteria",
  "Padaria",
  "Doces",
  "Sorvetes",
  "Bar",
  "Pub",
  "Sushi",
  "Açaí",
  "Self-Service",
  "Rodízio",
  "Food Truck",
  "Delivery",
  "Marmita",
] as const;

// Common neighborhoods (can be expanded per city)
export const NEIGHBORHOODS = [
  "Centro",
  "Jardins",
  "Pinheiros",
  "Vila Madalena",
  "Itaim Bibi",
  "Moema",
  "Vila Olímpia",
  "Brooklin",
  "Consolação",
  "Perdizes",
  "Higienópolis",
  "Bela Vista",
  "Liberdade",
  "Santa Cecília",
  "Lapa",
] as const;
