import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { X, Filter } from "lucide-react";
import { CUISINE_TYPES, NEIGHBORHOODS, PriceRange } from "@/lib/directoryTypes";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { ScrollArea } from "@/components/ui/scroll-area";

interface DirectoryFiltersProps {
  selectedCuisines: string[];
  selectedNeighborhoods: string[];
  selectedPriceRange: PriceRange | null;
  onCuisineChange: (cuisines: string[]) => void;
  onNeighborhoodChange: (neighborhoods: string[]) => void;
  onPriceRangeChange: (range: PriceRange | null) => void;
  onClearAll: () => void;
}

const PRICE_RANGES: { value: PriceRange; label: string }[] = [
  { value: "$", label: "$ - Econômico" },
  { value: "$$", label: "$$ - Moderado" },
  { value: "$$$", label: "$$$ - Caro" },
  { value: "$$$$", label: "$$$$ - Luxo" },
];

const DirectoryFilters = ({
  selectedCuisines,
  selectedNeighborhoods,
  selectedPriceRange,
  onCuisineChange,
  onNeighborhoodChange,
  onPriceRangeChange,
  onClearAll,
}: DirectoryFiltersProps) => {
  const hasActiveFilters =
    selectedCuisines.length > 0 ||
    selectedNeighborhoods.length > 0 ||
    selectedPriceRange !== null;

  const toggleCuisine = (cuisine: string) => {
    if (selectedCuisines.includes(cuisine)) {
      onCuisineChange(selectedCuisines.filter((c) => c !== cuisine));
    } else {
      onCuisineChange([...selectedCuisines, cuisine]);
    }
  };

  const toggleNeighborhood = (neighborhood: string) => {
    if (selectedNeighborhoods.includes(neighborhood)) {
      onNeighborhoodChange(selectedNeighborhoods.filter((n) => n !== neighborhood));
    } else {
      onNeighborhoodChange([...selectedNeighborhoods, neighborhood]);
    }
  };

  const FilterContent = () => (
    <div className="space-y-6">
      {/* Price Range */}
      <div>
        <h4 className="font-medium mb-2 text-sm">Faixa de Preço</h4>
        <Select
          value={selectedPriceRange || "all"}
          onValueChange={(value) =>
            onPriceRangeChange(value === "all" ? null : (value as PriceRange))
          }
        >
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Todas as faixas" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todas as faixas</SelectItem>
            {PRICE_RANGES.map((range) => (
              <SelectItem key={range.value} value={range.value}>
                {range.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Cuisine Types */}
      <div>
        <h4 className="font-medium mb-2 text-sm">Tipo de Comida</h4>
        <ScrollArea className="h-48">
          <div className="flex flex-wrap gap-2 pr-4">
            {CUISINE_TYPES.map((cuisine) => (
              <Badge
                key={cuisine}
                variant={selectedCuisines.includes(cuisine) ? "default" : "outline"}
                className="cursor-pointer hover:opacity-80 transition-opacity"
                onClick={() => toggleCuisine(cuisine)}
              >
                {cuisine}
              </Badge>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Neighborhoods */}
      <div>
        <h4 className="font-medium mb-2 text-sm">Bairro</h4>
        <ScrollArea className="h-36">
          <div className="flex flex-wrap gap-2 pr-4">
            {NEIGHBORHOODS.map((neighborhood) => (
              <Badge
                key={neighborhood}
                variant={selectedNeighborhoods.includes(neighborhood) ? "default" : "outline"}
                className="cursor-pointer hover:opacity-80 transition-opacity"
                onClick={() => toggleNeighborhood(neighborhood)}
              >
                {neighborhood}
              </Badge>
            ))}
          </div>
        </ScrollArea>
      </div>

      {hasActiveFilters && (
        <Button variant="outline" onClick={onClearAll} className="w-full">
          <X className="w-4 h-4 mr-2" />
          Limpar Filtros
        </Button>
      )}
    </div>
  );

  return (
    <>
      {/* Desktop Filters - Sidebar */}
      <div className="hidden lg:block w-64 shrink-0">
        <div className="sticky top-4 bg-card border rounded-lg p-4">
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-semibold flex items-center gap-2">
              <Filter className="w-4 h-4" />
              Filtros
            </h3>
            {hasActiveFilters && (
              <Badge variant="secondary">
                {selectedCuisines.length + selectedNeighborhoods.length + (selectedPriceRange ? 1 : 0)}
              </Badge>
            )}
          </div>
          <FilterContent />
        </div>
      </div>

      {/* Mobile Filters - Sheet */}
      <div className="lg:hidden">
        <Sheet>
          <SheetTrigger asChild>
            <Button variant="outline" className="w-full">
              <Filter className="w-4 h-4 mr-2" />
              Filtros
              {hasActiveFilters && (
                <Badge variant="secondary" className="ml-2">
                  {selectedCuisines.length + selectedNeighborhoods.length + (selectedPriceRange ? 1 : 0)}
                </Badge>
              )}
            </Button>
          </SheetTrigger>
          <SheetContent side="left" className="w-[300px]">
            <SheetHeader>
              <SheetTitle>Filtros</SheetTitle>
            </SheetHeader>
            <div className="mt-4">
              <FilterContent />
            </div>
          </SheetContent>
        </Sheet>
      </div>
    </>
  );
};

export default DirectoryFilters;
