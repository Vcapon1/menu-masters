import { cn } from "@/lib/utils";

interface ProductBadgeProps {
  type: "promo" | "vegan" | "vegetarian" | "highlight" | "popular" | "out";
  size?: "sm" | "md";
}

const badgeConfig = {
  promo: {
    label: "Promoção",
    className: "bg-promo text-promo-foreground",
  },
  vegan: {
    label: "Vegano",
    className: "bg-vegan text-vegan-foreground",
  },
  vegetarian: {
    label: "Vegetariano",
    className: "bg-success text-success-foreground",
  },
  highlight: {
    label: "Destaque",
    className: "bg-accent text-accent-foreground",
  },
  popular: {
    label: "Mais Pedido",
    className: "bg-primary text-primary-foreground",
  },
  out: {
    label: "Indisponível",
    className: "bg-muted text-muted-foreground",
  },
};

export function ProductBadge({ type, size = "md" }: ProductBadgeProps) {
  const config = badgeConfig[type];

  return (
    <span
      className={cn(
        "inline-flex items-center justify-center rounded-full font-medium",
        config.className,
        size === "sm" ? "px-2 py-0.5 text-[10px]" : "px-2.5 py-1 text-xs"
      )}
    >
      {config.label}
    </span>
  );
}
