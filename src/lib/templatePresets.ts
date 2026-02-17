/**
 * Template Color Presets
 * Default color palettes for each menu template
 */

export interface TemplatePreset {
  id: string;
  name: string;
  primaryColor: string;
  secondaryColor: string;
  accentColor: string;
  buttonColor: string;
  buttonTextColor: string;
  fontColor: string;
  description: string;
}

export const templatePresets: Record<string, TemplatePreset> = {
  appetite: {
    id: "appetite",
    name: "Appetite",
    primaryColor: "#f97316",
    secondaryColor: "#1f2937",
    accentColor: "#f59e0b",
    buttonColor: "#f97316",
    buttonTextColor: "#ffffff",
    fontColor: "#1f2937",
    description: "Moderno estilo iFood - laranja vibrante com fundo claro",
  },
  bold: {
    id: "bold",
    name: "Bold",
    primaryColor: "#dc2626",
    secondaryColor: "#fbbf24",
    accentColor: "#f59e0b",
    buttonColor: "#dc2626",
    buttonTextColor: "#ffffff",
    fontColor: "#ffffff",
    description: "Alto contraste vermelho e amarelo - impactante",
  },
  classic: {
    id: "classic",
    name: "Classic",
    primaryColor: "#1f2937",
    secondaryColor: "#f59e0b",
    accentColor: "#d97706",
    buttonColor: "#f59e0b",
    buttonTextColor: "#1f2937",
    fontColor: "#1f2937",
    description: "Elegante e equilibrado - tons neutros com dourado",
  },
  minimal: {
    id: "minimal",
    name: "Minimal",
    primaryColor: "#18181b",
    secondaryColor: "#71717a",
    accentColor: "#3f3f46",
    buttonColor: "#18181b",
    buttonTextColor: "#ffffff",
    fontColor: "#18181b",
    description: "Ultra clean - preto e branco sofisticado",
  },
  dark: {
    id: "dark",
    name: "Dark Mode",
    primaryColor: "#7c3aed",
    secondaryColor: "#a78bfa",
    accentColor: "#8b5cf6",
    buttonColor: "#7c3aed",
    buttonTextColor: "#ffffff",
    fontColor: "#f4f4f5",
    description: "Tema escuro moderno - roxo sofisticado",
  },
  elegant: {
    id: "elegant",
    name: "Elegant",
    primaryColor: "#b45309",
    secondaryColor: "#78350f",
    accentColor: "#d97706",
    buttonColor: "#b45309",
    buttonTextColor: "#ffffff",
    fontColor: "#292524",
    description: "Sofisticado - tons amadeirados e dourados",
  },
  modern: {
    id: "modern",
    name: "Modern",
    primaryColor: "#0ea5e9",
    secondaryColor: "#0284c7",
    accentColor: "#38bdf8",
    buttonColor: "#0ea5e9",
    buttonTextColor: "#ffffff",
    fontColor: "#0f172a",
    description: "Clean e moderno - azul tecnológico",
  },
  hero: {
    id: "hero",
    name: "Hero",
    primaryColor: "#f59e0b",
    secondaryColor: "#fbbf24",
    accentColor: "#f97316",
    buttonColor: "#f59e0b",
    buttonTextColor: "#000000",
    fontColor: "#ffffff",
    description: "Design impactante com hero banner - ideal para hamburgerias",
  },
  visual: {
    id: "visual",
    name: "Visual",
    primaryColor: "#059669",
    secondaryColor: "#10b981",
    accentColor: "#34d399",
    buttonColor: "#059669",
    buttonTextColor: "#ffffff",
    fontColor: "#1f2937",
    description: "Focado em imagens grandes - verde fresco",
  },
  zen: {
    id: "zen",
    name: "Zen",
    primaryColor: "#d4a574",
    secondaryColor: "#a3845a",
    accentColor: "#f59e0b",
    buttonColor: "#d4a574",
    buttonTextColor: "#0c0a09",
    fontColor: "#fafaf9",
    description: "Minimalista oriental - dourado elegante com fundo escuro",
  },
  pizzaria: {
    id: "pizzaria",
    name: "Pizzaria",
    primaryColor: "#c0392b",
    secondaryColor: "#d4a574",
    accentColor: "#e74c3c",
    buttonColor: "#c0392b",
    buttonTextColor: "#ffffff",
    fontColor: "#faf5f0",
    description: "Tema quente para pizzarias - vermelho com dourado",
  },
};

export const getTemplatePreset = (templateId: string): TemplatePreset | undefined => {
  return templatePresets[templateId];
};

export const getDefaultColors = (templateId: string) => {
  const preset = templatePresets[templateId];
  if (!preset) return null;
  
  return {
    primaryColor: preset.primaryColor,
    secondaryColor: preset.secondaryColor,
    accentColor: preset.accentColor,
    buttonColor: preset.buttonColor,
    buttonTextColor: preset.buttonTextColor,
    fontColor: preset.fontColor,
  };
};
