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
        "relative flex flex-col rounded-2xl p-8 transition-all duration-500 hover:-translate-y-2 overflow-hidden",
        highlighted
          ? "bg-gradient-to-b from-card to-card/80 border-2 border-primary/50 shadow-glow-lg scale-105 z-10"
          : "bg-card border border-border/30 hover:border-primary/30"
      )}
    >
      {/* Top glow for highlighted */}
      {highlighted && (
        <div className="absolute top-0 left-0 right-0 h-1 bg-gradient-primary" />
      )}

      {highlightLabel && (
        <div className="absolute -top-px left-1/2 -translate-x-1/2">
          <span className="bg-gradient-primary text-primary-foreground text-xs font-semibold px-4 py-1.5 rounded-b-lg">
            {highlightLabel}
          </span>
        </div>
      )}

      <div className="mb-6">
        <h3 className="text-2xl font-bold text-foreground mb-2">{name}</h3>
        <p className="text-sm text-muted-foreground">{description}</p>
      </div>

      <div className="mb-8">
        <div className="flex items-baseline gap-1">
          <span className="text-4xl font-bold text-foreground">R${monthlyPrice}</span>
          <span className="text-sm text-muted-foreground">/mês</span>
        </div>
        <p className="text-sm mt-2 text-primary">
          ou R${annualPrice}/mês no plano anual
        </p>
      </div>

      <ul className="space-y-3 mb-8 flex-grow">
        {features.map((feature, index) => (
          <li key={index} className="flex items-start gap-3">
            {feature.included ? (
              <div className="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                <Check className="w-3 h-3 text-primary" />
              </div>
            ) : (
              <div className="w-5 h-5 rounded-full bg-muted flex items-center justify-center flex-shrink-0 mt-0.5">
                <X className="w-3 h-3 text-muted-foreground" />
              </div>
            )}
            <span
              className={cn(
                "text-sm",
                !feature.included && "text-muted-foreground/50"
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
        className={cn("w-full", highlighted && "shadow-glow")}
      >
        Começar agora
      </Button>
    </div>
  );
}
