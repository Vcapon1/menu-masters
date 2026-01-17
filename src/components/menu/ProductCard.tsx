import { useState } from "react";
import { ProductBadge } from "./ProductBadge";
import { cn } from "@/lib/utils";
import { Play, X } from "lucide-react";

interface ProductCardProps {
  id: string;
  name: string;
  description: string;
  price: number;
  originalPrice?: number;
  image: string;
  video?: string;
  badges?: Array<"promo" | "vegan" | "vegetarian" | "highlight" | "popular" | "out">;
  isAvailable?: boolean;
  template?: "visual" | "classic" | "modern";
}

export function ProductCard({
  name,
  description,
  price,
  originalPrice,
  image,
  video,
  badges = [],
  isAvailable = true,
  template = "visual",
}: ProductCardProps) {
  const [isImageExpanded, setIsImageExpanded] = useState(false);
  const [isVideoPlaying, setIsVideoPlaying] = useState(false);

  const isOutOfStock = !isAvailable || badges.includes("out");

  return (
    <>
      <div
        className={cn(
          "product-card",
          isOutOfStock && "opacity-60"
        )}
      >
        {/* Image/Video Container */}
        <div
          className={cn(
            "relative overflow-hidden cursor-pointer",
            template === "visual" ? "aspect-square" : "aspect-video"
          )}
          onClick={() => !isOutOfStock && setIsImageExpanded(true)}
        >
          <img
            src={image}
            alt={name}
            className="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
          />

          {/* Video Play Button */}
          {video && !isOutOfStock && (
            <button
              onClick={(e) => {
                e.stopPropagation();
                setIsVideoPlaying(true);
              }}
              className="absolute inset-0 flex items-center justify-center bg-foreground/20 opacity-0 hover:opacity-100 transition-opacity"
            >
              <div className="w-16 h-16 rounded-full bg-primary/90 flex items-center justify-center">
                <Play className="w-8 h-8 text-primary-foreground ml-1" />
              </div>
            </button>
          )}

          {/* Badges */}
          {badges.length > 0 && (
            <div className="absolute top-3 left-3 flex gap-1">
              {badges.map((badge, index) => (
                <ProductBadge key={index} type={badge} size="sm" />
              ))}
            </div>
          )}

          {/* Out of Stock Overlay */}
          {isOutOfStock && (
            <div className="absolute inset-0 flex items-center justify-center bg-foreground/50">
              <span className="bg-background text-foreground px-4 py-2 rounded-full font-semibold text-sm">
                Indisponível
              </span>
            </div>
          )}
        </div>

        {/* Content */}
        <div className="p-4">
          <h3 className="font-semibold text-foreground text-lg mb-1 line-clamp-1">
            {name}
          </h3>
          <p className="text-muted-foreground text-sm mb-3 line-clamp-2">
            {description}
          </p>
          <div className="flex items-baseline gap-2">
            {originalPrice && (
              <span className="price-old">R${originalPrice.toFixed(2)}</span>
            )}
            <span className="price-tag">R${price.toFixed(2)}</span>
          </div>
        </div>
      </div>

      {/* Image Modal */}
      {isImageExpanded && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/80 backdrop-blur-sm p-4"
          onClick={() => setIsImageExpanded(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-background flex items-center justify-center"
            onClick={() => setIsImageExpanded(false)}
          >
            <X className="w-5 h-5 text-foreground" />
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
          className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/80 backdrop-blur-sm p-4"
          onClick={() => setIsVideoPlaying(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-background flex items-center justify-center"
            onClick={() => setIsVideoPlaying(false)}
          >
            <X className="w-5 h-5 text-foreground" />
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
