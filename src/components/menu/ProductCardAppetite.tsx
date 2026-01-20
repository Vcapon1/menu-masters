import { useState } from "react";
import { ProductBadge } from "./ProductBadge";
import { cn } from "@/lib/utils";
import { Play, X, Plus } from "lucide-react";

interface ProductCardAppetiteProps {
  id: string;
  name: string;
  description: string;
  price: number;
  originalPrice?: number;
  image: string;
  video?: string;
  badges?: Array<"promo" | "vegan" | "vegetarian" | "highlight" | "popular" | "out">;
  isAvailable?: boolean;
  viewMode?: "list" | "grid";
  onAdd?: () => void;
}

export function ProductCardAppetite({
  name,
  description,
  price,
  originalPrice,
  image,
  video,
  badges = [],
  isAvailable = true,
  viewMode = "list",
  onAdd,
}: ProductCardAppetiteProps) {
  const [isImageExpanded, setIsImageExpanded] = useState(false);
  const [isVideoPlaying, setIsVideoPlaying] = useState(false);
  const [imageLoaded, setImageLoaded] = useState(false);

  const isOutOfStock = !isAvailable || badges.includes("out");
  const hasPromo = badges.includes("promo") && originalPrice;

  if (viewMode === "grid") {
    return (
      <>
        <div
          className={cn(
            "relative bg-card rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 group",
            isOutOfStock && "opacity-60"
          )}
        >
          {/* Image */}
          <div
            className="relative aspect-square overflow-hidden cursor-pointer"
            onClick={() => !isOutOfStock && setIsImageExpanded(true)}
          >
            {/* Skeleton */}
            {!imageLoaded && (
              <div className="absolute inset-0 bg-muted animate-pulse" />
            )}
            
            <img
              src={image}
              alt={name}
              className={cn(
                "w-full h-full object-cover transition-all duration-500 group-hover:scale-105",
                !imageLoaded && "opacity-0"
              )}
              onLoad={() => setImageLoaded(true)}
            />

            {/* Badges */}
            {badges.length > 0 && (
              <div className="absolute top-2 left-2 flex flex-wrap gap-1">
                {badges.filter(b => b !== "out").map((badge, index) => (
                  <ProductBadge key={index} type={badge} size="sm" />
                ))}
              </div>
            )}

            {/* Video Play Button */}
            {video && !isOutOfStock && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  setIsVideoPlaying(true);
                }}
                className="absolute bottom-2 right-2 w-8 h-8 rounded-full bg-foreground/80 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Play className="w-4 h-4 text-background ml-0.5" fill="currentColor" />
              </button>
            )}

            {/* Out of Stock Overlay */}
            {isOutOfStock && (
              <div className="absolute inset-0 flex items-center justify-center bg-background/70">
                <span className="bg-muted text-muted-foreground px-3 py-1 rounded-full font-medium text-xs">
                  Indisponível
                </span>
              </div>
            )}
          </div>

          {/* Content */}
          <div className="p-3">
            <h3 className="font-semibold text-foreground text-sm line-clamp-1">
              {name}
            </h3>
            
            <p className="text-muted-foreground text-xs mt-0.5 line-clamp-2 min-h-[2rem]">
              {description}
            </p>

            <div className="flex items-center justify-between mt-2">
              <div className="flex items-baseline gap-1.5">
                {hasPromo && (
                  <span className="text-muted-foreground text-xs line-through">
                    R$ {originalPrice.toFixed(2)}
                  </span>
                )}
                <span className={cn(
                  "font-bold",
                  hasPromo ? "text-promo" : "text-foreground"
                )}>
                  R$ {price.toFixed(2)}
                </span>
              </div>

              {!isOutOfStock && (
                <button
                  onClick={onAdd}
                  className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center hover:scale-110 transition-transform shadow-md"
                >
                  <Plus className="w-5 h-5" />
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Image Modal */}
        {isImageExpanded && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
            onClick={() => setIsImageExpanded(false)}
          >
            <button
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 backdrop-blur flex items-center justify-center hover:bg-white/20 transition-colors"
              onClick={() => setIsImageExpanded(false)}
            >
              <X className="w-5 h-5 text-white" />
            </button>
            <img
              src={image}
              alt={name}
              className="max-w-full max-h-full rounded-2xl shadow-2xl"
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        )}

        {/* Video Modal */}
        {isVideoPlaying && video && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
            onClick={() => setIsVideoPlaying(false)}
          >
            <button
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 backdrop-blur flex items-center justify-center hover:bg-white/20 transition-colors"
              onClick={() => setIsVideoPlaying(false)}
            >
              <X className="w-5 h-5 text-white" />
            </button>
            <video
              src={video}
              controls
              autoPlay
              className="max-w-full max-h-full rounded-2xl shadow-2xl"
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        )}
      </>
    );
  }

  // LIST VIEW (Default - iFood style)
  return (
    <>
      <div
        className={cn(
          "relative bg-card rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 group",
          isOutOfStock && "opacity-60"
        )}
      >
        <div className="flex p-3 gap-3">
          {/* Left Content */}
          <div className="flex-1 min-w-0 flex flex-col justify-between">
            {/* Badges */}
            {badges.filter(b => b !== "out").length > 0 && (
              <div className="flex flex-wrap gap-1 mb-1.5">
                {badges.filter(b => b !== "out").map((badge, index) => (
                  <ProductBadge key={index} type={badge} size="sm" />
                ))}
              </div>
            )}

            {/* Name & Description */}
            <div className="flex-1">
              <h3 className="font-semibold text-foreground text-base leading-tight">
                {name}
              </h3>
              <p className="text-muted-foreground text-sm mt-1 line-clamp-2">
                {description}
              </p>
            </div>

            {/* Price */}
            <div className="flex items-center gap-2 mt-2">
              {hasPromo && (
                <span className="text-muted-foreground text-sm line-through">
                  R$ {originalPrice.toFixed(2)}
                </span>
              )}
              <span className={cn(
                "font-bold text-lg",
                hasPromo ? "text-promo" : "text-foreground"
              )}>
                R$ {price.toFixed(2)}
              </span>
            </div>
          </div>

          {/* Right - Image */}
          <div className="relative flex-shrink-0">
            <div
              className="w-24 h-24 rounded-lg overflow-hidden cursor-pointer shadow-sm"
              onClick={() => !isOutOfStock && setIsImageExpanded(true)}
            >
              {/* Skeleton */}
              {!imageLoaded && (
                <div className="absolute inset-0 bg-muted animate-pulse rounded-lg" />
              )}
              
              <img
                src={image}
                alt={name}
                className={cn(
                  "w-full h-full object-cover transition-all duration-300 group-hover:scale-105",
                  !imageLoaded && "opacity-0"
                )}
                onLoad={() => setImageLoaded(true)}
              />

              {/* Video Indicator */}
              {video && !isOutOfStock && (
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    setIsVideoPlaying(true);
                  }}
                  className="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <div className="w-8 h-8 rounded-full bg-white/90 flex items-center justify-center">
                    <Play className="w-4 h-4 text-foreground ml-0.5" fill="currentColor" />
                  </div>
                </button>
              )}

              {/* Out of Stock Overlay */}
              {isOutOfStock && (
                <div className="absolute inset-0 flex items-center justify-center bg-background/70 rounded-lg">
                  <span className="bg-muted text-muted-foreground px-2 py-0.5 rounded-full font-medium text-[10px]">
                    Indisponível
                  </span>
                </div>
              )}
            </div>

            {/* Add Button */}
            {!isOutOfStock && (
              <button
                onClick={onAdd}
                className="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-primary text-primary-foreground flex items-center justify-center hover:scale-110 transition-transform shadow-md"
              >
                <Plus className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Image Modal */}
      {isImageExpanded && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
          onClick={() => setIsImageExpanded(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 backdrop-blur flex items-center justify-center hover:bg-white/20 transition-colors"
            onClick={() => setIsImageExpanded(false)}
          >
            <X className="w-5 h-5 text-white" />
          </button>
          <img
            src={image}
            alt={name}
            className="max-w-full max-h-full rounded-2xl shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}

      {/* Video Modal */}
      {isVideoPlaying && video && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
          onClick={() => setIsVideoPlaying(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 backdrop-blur flex items-center justify-center hover:bg-white/20 transition-colors"
            onClick={() => setIsVideoPlaying(false)}
          >
            <X className="w-5 h-5 text-white" />
          </button>
          <video
            src={video}
            controls
            autoPlay
            className="max-w-full max-h-full rounded-2xl shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}
    </>
  );
}
