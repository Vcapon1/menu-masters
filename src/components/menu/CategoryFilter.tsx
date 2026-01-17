import { cn } from "@/lib/utils";

interface CategoryFilterProps {
  categories: string[];
  activeCategory: string;
  onCategoryChange: (category: string) => void;
}

export function CategoryFilter({
  categories,
  activeCategory,
  onCategoryChange,
}: CategoryFilterProps) {
  return (
    <div className="overflow-x-auto scrollbar-hide py-4 -mx-4 px-4">
      <div className="flex gap-3 min-w-max">
        <button
          onClick={() => onCategoryChange("all")}
          className={cn(
            "category-pill",
            activeCategory === "all" ? "category-pill-active" : "category-pill-inactive"
          )}
        >
          Todos
        </button>
        {categories.map((category) => (
          <button
            key={category}
            onClick={() => onCategoryChange(category)}
            className={cn(
              "category-pill",
              activeCategory === category
                ? "category-pill-active"
                : "category-pill-inactive"
            )}
          >
            {category}
          </button>
        ))}
      </div>
    </div>
  );
}
