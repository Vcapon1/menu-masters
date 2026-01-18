import { useState } from "react";
import { ProductBadge } from "./ProductBadge";
import { cn } from "@/lib/utils";
import { Play, X, ShoppingBag } from "lucide-react";

interface ProductCardBoldProps {
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
  onOrder?: () => void;
}

export function ProductCardBold({
  name,
  description,
  price,
  originalPrice,
  image,
  video,
  badges = [],
  isAvailable = true,
  viewMode = "list",
  onOrder,
}: ProductCardBoldProps) {
  const [isImageExpanded, setIsImageExpanded] = useState(false);
  const [isVideoPlaying, setIsVideoPlaying] = useState(false);

  const isOutOfStock = !isAvailable || badges.includes("out");

  if (viewMode === "grid") {
    return (
      <>
        <div
          className={cn(
            "relative rounded-2xl overflow-hidden bg-gradient-to-br from-red-700 via-red-600 to-red-800 shadow-xl",
            isOutOfStock && "opacity-60"
          )}
        >
          {/* Background texture overlay */}
          <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E')] opacity-10" />
          
          {/* Badges */}
          {badges.length > 0 && (
            <div className="absolute top-3 left-3 z-10 flex gap-1">
              {badges.map((badge, index) => (
                <ProductBadge key={index} type={badge} size="sm" />
              ))}
            </div>
          )}

          {/* Content */}
          <div className="relative p-4">
            {/* Product Name */}
            <h3 className="font-display text-xl font-bold text-yellow-400 uppercase tracking-wide mb-1 drop-shadow-lg">
              {name}
            </h3>
            
            <p className="text-white/80 text-xs mb-3 line-clamp-2">
              {description}
            </p>

            {/* Image */}
            <div
              className="relative w-full aspect-square rounded-xl overflow-hidden cursor-pointer mb-3"
              onClick={() => !isOutOfStock && setIsImageExpanded(true)}
            >
              <img
                src={image}
                alt={name}
                className="w-full h-full object-cover transition-transform duration-300 hover:scale-110"
              />
              
              {/* Video Play Button */}
              {video && !isOutOfStock && (
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    setIsVideoPlaying(true);
                  }}
                  className="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity"
                >
                  <div className="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center">
                    <Play className="w-6 h-6 text-red-700 ml-1" />
                  </div>
                </button>
              )}

              {/* Out of Stock Overlay */}
              {isOutOfStock && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                  <span className="bg-white text-red-700 px-3 py-1 rounded-full font-bold text-xs">
                    ESGOTADO
                  </span>
                </div>
              )}
            </div>

            {/* Price Tag */}
            <div className="flex items-center justify-between">
              <div className="bg-yellow-400 rounded-xl px-3 py-2 shadow-lg">
                {originalPrice && (
                  <span className="text-red-700/60 text-xs line-through block">
                    R$ {originalPrice.toFixed(2)}
                  </span>
                )}
                <span className="text-red-700 font-bold text-lg">
                  R$ {price.toFixed(2)}
                </span>
              </div>

              {!isOutOfStock && (
                <button
                  onClick={onOrder}
                  className="bg-green-500 hover:bg-green-600 text-white rounded-xl px-3 py-2 font-bold text-xs flex items-center gap-1 transition-colors shadow-lg"
                >
                  <ShoppingBag className="w-4 h-4" />
                  PEDIR
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Modals */}
        {isImageExpanded && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
            onClick={() => setIsImageExpanded(false)}
          >
            <button
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white flex items-center justify-center"
              onClick={() => setIsImageExpanded(false)}
            >
              <X className="w-5 h-5 text-black" />
            </button>
            <img
              src={image}
              alt={name}
              className="max-w-full max-h-full rounded-2xl shadow-2xl"
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        )}

        {isVideoPlaying && video && (
          <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
            onClick={() => setIsVideoPlaying(false)}
          >
            <button
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white flex items-center justify-center"
              onClick={() => setIsVideoPlaying(false)}
            >
              <X className="w-5 h-5 text-black" />
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

  // LIST VIEW
  return (
    <>
      <div
        className={cn(
          "relative rounded-2xl overflow-hidden bg-gradient-to-r from-red-700 via-red-600 to-red-800 shadow-xl",
          isOutOfStock && "opacity-60"
        )}
      >
        {/* Background texture overlay */}
        <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E')] opacity-10" />

        <div className="relative flex items-center p-4 gap-4">
          {/* Left Content */}
          <div className="flex-1 min-w-0">
            {/* Badges */}
            {badges.length > 0 && (
              <div className="flex gap-1 mb-2">
                {badges.map((badge, index) => (
                  <ProductBadge key={index} type={badge} size="sm" />
                ))}
              </div>
            )}

            {/* Product Name */}
            <h3 className="font-display text-xl font-bold text-yellow-400 uppercase tracking-wide leading-tight drop-shadow-lg">
              {name}
            </h3>
            
            <p className="text-white/80 text-xs mt-1 line-clamp-2">
              {description}
            </p>

            {/* Order Button */}
            {!isOutOfStock && (
              <button
                onClick={onOrder}
                className="mt-3 bg-green-500 hover:bg-green-600 text-white rounded-lg px-4 py-1.5 font-bold text-xs flex items-center gap-1 transition-colors shadow-lg"
              >
                <ShoppingBag className="w-3 h-3" />
                FAZER PEDIDO
              </button>
            )}
          </div>

          {/* Right - Image & Price */}
          <div className="flex-shrink-0 relative">
            {/* Price Tag - positioned on top */}
            <div className="absolute -top-1 -right-1 z-10 bg-yellow-400 rounded-xl px-2 py-1 shadow-lg transform rotate-3">
              {originalPrice && (
                <span className="text-red-700/60 text-[10px] line-through block leading-none">
                  R$ {originalPrice.toFixed(2)}
                </span>
              )}
              <span className="text-red-700 font-bold text-sm leading-none">
                R$ {price.toFixed(2)}
              </span>
            </div>

            {/* Image */}
            <div
              className="w-28 h-28 rounded-xl overflow-hidden cursor-pointer shadow-xl border-2 border-yellow-400/50"
              onClick={() => !isOutOfStock && setIsImageExpanded(true)}
            >
              <img
                src={image}
                alt={name}
                className="w-full h-full object-cover transition-transform duration-300 hover:scale-110"
              />
              
              {/* Video Play Button */}
              {video && !isOutOfStock && (
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    setIsVideoPlaying(true);
                  }}
                  className="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 hover:opacity-100 transition-opacity"
                >
                  <div className="w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center">
                    <Play className="w-5 h-5 text-red-700 ml-0.5" />
                  </div>
                </button>
              )}

              {/* Out of Stock Overlay */}
              {isOutOfStock && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                  <span className="bg-white text-red-700 px-2 py-0.5 rounded-full font-bold text-[10px]">
                    ESGOTADO
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modals */}
      {isImageExpanded && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
          onClick={() => setIsImageExpanded(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white flex items-center justify-center"
            onClick={() => setIsImageExpanded(false)}
          >
            <X className="w-5 h-5 text-black" />
          </button>
          <img
            src={image}
            alt={name}
            className="max-w-full max-h-full rounded-2xl shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}

      {isVideoPlaying && video && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4"
          onClick={() => setIsVideoPlaying(false)}
        >
          <button
            className="absolute top-4 right-4 w-10 h-10 rounded-full bg-white flex items-center justify-center"
            onClick={() => setIsVideoPlaying(false)}
          >
            <X className="w-5 h-5 text-black" />
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
