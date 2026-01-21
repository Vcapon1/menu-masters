import { LucideIcon } from "lucide-react";
import { cn } from "@/lib/utils";

interface FeatureCardProps {
  icon: LucideIcon;
  title: string;
  description: string;
  className?: string;
  style?: React.CSSProperties;
}

export function FeatureCard({ icon: Icon, title, description, className, style }: FeatureCardProps) {
  return (
    <div
      className={cn(
        "group relative p-6 rounded-2xl bg-card border border-border/30 hover:border-primary/30 transition-all duration-500 hover:-translate-y-1 overflow-hidden",
        className
      )}
      style={style}
    >
      {/* Glow effect on hover */}
      <div className="absolute inset-0 bg-gradient-radial opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
      
      {/* Top border glow */}
      <div className="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
      
      <div className="relative">
        <div className="w-14 h-14 rounded-xl bg-gradient-primary flex items-center justify-center mb-5 shadow-lg group-hover:shadow-glow transition-all duration-500 group-hover:scale-110">
          <Icon className="w-7 h-7 text-primary-foreground" />
        </div>
        <h3 className="text-lg font-semibold text-foreground mb-2">{title}</h3>
        <p className="text-muted-foreground text-sm leading-relaxed">{description}</p>
      </div>
    </div>
  );
}
