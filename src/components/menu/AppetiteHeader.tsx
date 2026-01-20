import { MapPin, Clock, ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";

interface AppetiteHeaderProps {
  restaurantName: string;
  logo?: string;
  address?: string;
  isOpen?: boolean;
  closingTime?: string;
  className?: string;
}

export function AppetiteHeader({
  restaurantName,
  logo,
  address,
  isOpen = true,
  closingTime,
  className,
}: AppetiteHeaderProps) {
  return (
    <header
      className={cn(
        "sticky top-0 z-40 bg-background/95 backdrop-blur-md border-b border-border/50",
        className
      )}
    >
      <div className="px-4 py-3">
        <div className="flex items-center gap-3">
          {/* Logo */}
          {logo ? (
            <div className="w-11 h-11 rounded-full overflow-hidden border-2 border-primary/20 shadow-md flex-shrink-0">
              <img
                src={logo}
                alt={restaurantName}
                className="w-full h-full object-cover"
              />
            </div>
          ) : (
            <div className="w-11 h-11 rounded-full bg-gradient-primary flex items-center justify-center text-primary-foreground font-bold text-lg shadow-md flex-shrink-0">
              {restaurantName.charAt(0)}
            </div>
          )}

          {/* Info */}
          <div className="flex-1 min-w-0">
            <h1 className="font-semibold text-foreground text-lg truncate">
              {restaurantName}
            </h1>
            
            <div className="flex items-center gap-2 text-muted-foreground text-sm">
              {address && (
                <div className="flex items-center gap-1 truncate">
                  <MapPin className="w-3 h-3 flex-shrink-0" />
                  <span className="truncate">{address}</span>
                </div>
              )}
            </div>
          </div>

          {/* Status Badge */}
          <div className="flex-shrink-0">
            {isOpen ? (
              <div className="flex items-center gap-1.5 bg-success/10 text-success px-2.5 py-1 rounded-full text-xs font-medium">
                <span className="w-1.5 h-1.5 rounded-full bg-success animate-pulse" />
                <span>Aberto</span>
                {closingTime && (
                  <span className="text-success/70">até {closingTime}</span>
                )}
              </div>
            ) : (
              <div className="flex items-center gap-1.5 bg-destructive/10 text-destructive px-2.5 py-1 rounded-full text-xs font-medium">
                <Clock className="w-3 h-3" />
                <span>Fechado</span>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
}
