import { cn } from "@/lib/utils";

interface ProductBadgeProps {
  type: "promo" | "vegan" | "vegetarian" | "highlight" | "popular" | "out";
  size?: "sm" | "md";
}

const badgeConfig = {
  promo: {
    label: "%",
    title: "Promoção",
    className: "bg-promo text-promo-foreground",
  },
  vegan: {
    label: "V",
    title: "Vegano",
    className: "bg-vegan text-vegan-foreground",
  },
  vegetarian: {
    label: "VG",
    title: "Vegetariano",
    className: "bg-success text-success-foreground",
  },
  highlight: {
    label: "★",
    title: "Destaque",
    className: "bg-accent text-accent-foreground",
  },
  popular: {
    label: "🔥",
    title: "Mais Pedido",
    className: "bg-primary text-primary-foreground",
  },
  out: {
    label: "X",
    title: "Indisponível",
    className: "bg-muted text-muted-foreground",
  },
};

export function ProductBadge({ type, size = "md" }: ProductBadgeProps) {
  const config = badgeConfig[type];

  return (
    <span
      className={cn(
        "inline-flex items-center justify-center rounded-full font-bold",
        config.className,
        size === "sm" ? "w-5 h-5 text-[10px]" : "w-7 h-7 text-xs"
      )}
      title={config.title}
    >
      {config.label}
    </span>
  );
}
