import { useRef, useEffect, useState } from "react";
import { cn } from "@/lib/utils";

interface Category {
  id: string;
  name: string;
  icon?: string;
}

interface AppetiteCategoryNavProps {
  categories: Category[];
  activeCategory: string;
  onCategoryChange: (categoryId: string) => void;
  className?: string;
}

export function AppetiteCategoryNav({
  categories,
  activeCategory,
  onCategoryChange,
  className,
}: AppetiteCategoryNavProps) {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [showLeftFade, setShowLeftFade] = useState(false);
  const [showRightFade, setShowRightFade] = useState(true);

  const handleScroll = () => {
    if (!scrollRef.current) return;
    const { scrollLeft, scrollWidth, clientWidth } = scrollRef.current;
    setShowLeftFade(scrollLeft > 10);
    setShowRightFade(scrollLeft < scrollWidth - clientWidth - 10);
  };

  useEffect(() => {
    const ref = scrollRef.current;
    if (ref) {
      ref.addEventListener("scroll", handleScroll);
      handleScroll();
      return () => ref.removeEventListener("scroll", handleScroll);
    }
  }, []);

  // Scroll active category into view
  useEffect(() => {
    const activeButton = scrollRef.current?.querySelector(
      `[data-category="${activeCategory}"]`
    );
    if (activeButton) {
      activeButton.scrollIntoView({
        behavior: "smooth",
        block: "nearest",
        inline: "center",
      });
    }
  }, [activeCategory]);

  return (
    <nav
      className={cn(
        "sticky top-[60px] z-30 bg-background/95 backdrop-blur-md border-b border-border/30",
        className
      )}
    >
      <div className="relative">
        {/* Left Fade */}
        {showLeftFade && (
          <div className="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-background to-transparent z-10 pointer-events-none" />
        )}

        {/* Right Fade */}
        {showRightFade && (
          <div className="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-background to-transparent z-10 pointer-events-none" />
        )}

        {/* Scrollable Categories */}
        <div
          ref={scrollRef}
          className="flex gap-2 px-4 py-3 overflow-x-auto scrollbar-hide"
          style={{ WebkitOverflowScrolling: "touch" }}
        >
          {categories.map((category) => {
            const isActive = activeCategory === category.id;
            return (
              <button
                key={category.id}
                data-category={category.id}
                onClick={() => onCategoryChange(category.id)}
                className={cn(
                  "flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all duration-200",
                  isActive
                    ? "bg-primary text-primary-foreground shadow-md scale-105"
                    : "bg-muted text-muted-foreground hover:bg-muted/80"
                )}
              >
                {category.icon && <span>{category.icon}</span>}
                <span>{category.name}</span>
              </button>
            );
          })}
        </div>
      </div>
    </nav>
  );
}
