import { Check, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface PlanFeature {
  text: string;
  included: boolean;
}

interface PlanCardProps {
  name: string;
  description: string;
  monthlyPrice: number;
  annualPrice: number;
  features: PlanFeature[];
  highlighted?: boolean;
  highlightLabel?: string;
}

export function PlanCard({
  name,
  description,
  monthlyPrice,
  annualPrice,
  features,
  highlighted = false,
  highlightLabel,
}: PlanCardProps) {
  return (
    <div
      className={cn(
        "relative flex flex-col rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1",
        highlighted
          ? "bg-secondary text-secondary-foreground shadow-2xl scale-105 z-10"
          : "bg-card text-card-foreground shadow-lg border border-border"
      )}
    >
      {highlightLabel && (
        <div className="absolute -top-4 left-1/2 -translate-x-1/2">
          <span className="bg-gradient-primary text-primary-foreground text-sm font-semibold px-4 py-1.5 rounded-full">
            {highlightLabel}
          </span>
        </div>
      )}

      <div className="mb-6">
        <h3
          className={cn(
            "text-2xl font-bold mb-2",
            highlighted ? "text-secondary-foreground" : "text-foreground"
          )}
        >
          {name}
        </h3>
        <p
          className={cn(
            "text-sm",
            highlighted ? "text-secondary-foreground/80" : "text-muted-foreground"
          )}
        >
          {description}
        </p>
      </div>

      <div className="mb-6">
        <div className="flex items-baseline gap-1">
          <span
            className={cn(
              "text-4xl font-bold",
              highlighted ? "text-secondary-foreground" : "text-foreground"
            )}
          >
            R${monthlyPrice}
          </span>
          <span
            className={cn(
              "text-sm",
              highlighted ? "text-secondary-foreground/70" : "text-muted-foreground"
            )}
          >
            /mês
          </span>
        </div>
        <p
          className={cn(
            "text-sm mt-1",
            highlighted ? "text-primary" : "text-primary"
          )}
        >
          ou R${annualPrice}/mês no plano anual
        </p>
      </div>

      <ul className="space-y-3 mb-8 flex-grow">
        {features.map((feature, index) => (
          <li key={index} className="flex items-start gap-3">
            {feature.included ? (
              <Check
                className={cn(
                  "w-5 h-5 mt-0.5 flex-shrink-0",
                  highlighted ? "text-primary" : "text-success"
                )}
              />
            ) : (
              <X
                className={cn(
                  "w-5 h-5 mt-0.5 flex-shrink-0",
                  highlighted ? "text-secondary-foreground/40" : "text-muted-foreground/50"
                )}
              />
            )}
            <span
              className={cn(
                "text-sm",
                !feature.included &&
                  (highlighted
                    ? "text-secondary-foreground/50"
                    : "text-muted-foreground/50")
              )}
            >
              {feature.text}
            </span>
          </li>
        ))}
      </ul>

      <Button
        variant={highlighted ? "hero" : "heroOutline"}
        size="xl"
        className="w-full"
      >
        Começar agora
      </Button>
    </div>
  );
}
