import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";

export function ContactForm() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { toast } = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Simulate form submission
    await new Promise((resolve) => setTimeout(resolve, 1000));

    toast({
      title: "Mensagem enviada!",
      description: "Em breve entraremos em contato com você.",
    });

    setFormData({ name: "", email: "", phone: "" });
    setIsSubmitting(false);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="space-y-2">
        <Label htmlFor="name" className="text-foreground font-medium">
          Nome completo
        </Label>
        <Input
          id="name"
          name="name"
          type="text"
          placeholder="Seu nome"
          value={formData.name}
          onChange={handleChange}
          required
          className="h-12 rounded-xl border-border bg-background"
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="email" className="text-foreground font-medium">
          E-mail
        </Label>
        <Input
          id="email"
          name="email"
          type="email"
          placeholder="seu@email.com"
          value={formData.email}
          onChange={handleChange}
          required
          className="h-12 rounded-xl border-border bg-background"
        />
      </div>

      <div className="space-y-2">
        <Label htmlFor="phone" className="text-foreground font-medium">
          Telefone
        </Label>
        <Input
          id="phone"
          name="phone"
          type="tel"
          placeholder="(00) 00000-0000"
          value={formData.phone}
          onChange={handleChange}
          required
          className="h-12 rounded-xl border-border bg-background"
        />
      </div>

      <Button
        type="submit"
        variant="hero"
        size="xl"
        className="w-full mt-6"
        disabled={isSubmitting}
      >
        {isSubmitting ? "Enviando..." : "Quero meu cardápio digital"}
      </Button>

      <p className="text-xs text-muted-foreground text-center">
        Ao enviar, você concorda com nossa política de privacidade.
      </p>
    </form>
  );
}
