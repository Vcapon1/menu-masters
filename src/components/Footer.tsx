import { Mail, Phone, MapPin } from "lucide-react";

export function Footer() {
  return (
    <footer className="relative bg-card border-t border-border/30 py-16 overflow-hidden">
      {/* Ambient glow */}
      <div className="absolute bottom-0 left-1/4 w-[400px] h-[400px] bg-primary/10 rounded-full blur-[120px]" />
      
      <div className="container relative mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
          {/* Brand */}
          <div className="md:col-span-2">
            <div className="flex items-center gap-3 mb-6">
              <div className="w-10 h-10 rounded-xl bg-gradient-primary flex items-center justify-center shadow-glow">
                <span className="text-primary-foreground font-bold text-xl">P</span>
              </div>
              <span className="font-display text-xl font-bold text-foreground">
                Premium<span className="text-primary">Menu</span>
              </span>
            </div>
            <p className="text-muted-foreground text-sm max-w-sm mb-6">
              Transforme seu restaurante com cardápios digitais modernos. 
              Aumente suas vendas e melhore a experiência dos seus clientes.
            </p>
            <div className="flex flex-col gap-3">
              <a href="mailto:contato@premiummenu.com" className="flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors">
                <Mail className="w-4 h-4" />
                contato@premiummenu.com
              </a>
              <a href="tel:+5511999999999" className="flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors">
                <Phone className="w-4 h-4" />
                (11) 99999-9999
              </a>
            </div>
          </div>

          {/* Links */}
          <div>
            <h4 className="font-semibold text-foreground mb-4">Produto</h4>
            <ul className="space-y-3 text-sm text-muted-foreground">
              <li>
                <a href="#vantagens" className="hover:text-primary transition-colors">Vantagens</a>
              </li>
              <li>
                <a href="#planos" className="hover:text-primary transition-colors">Planos</a>
              </li>
              <li>
                <a href="#como-funciona" className="hover:text-primary transition-colors">Como Funciona</a>
              </li>
            </ul>
          </div>

          {/* Support */}
          <div>
            <h4 className="font-semibold text-foreground mb-4">Suporte</h4>
            <ul className="space-y-3 text-sm text-muted-foreground">
              <li>
                <a href="#contato" className="hover:text-primary transition-colors">Contato</a>
              </li>
              <li>
                <a href="#" className="hover:text-primary transition-colors">FAQ</a>
              </li>
              <li>
                <a href="#" className="hover:text-primary transition-colors">Termos de Uso</a>
              </li>
              <li>
                <a href="#" className="hover:text-primary transition-colors">Privacidade</a>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-border/30 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-sm text-muted-foreground">
            © {new Date().getFullYear()} Premium Menu. Todos os direitos reservados.
          </p>
          <div className="flex items-center gap-6 text-sm text-muted-foreground">
            <a href="#" className="hover:text-primary transition-colors">Instagram</a>
            <a href="#" className="hover:text-primary transition-colors">Facebook</a>
            <a href="#" className="hover:text-primary transition-colors">LinkedIn</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
