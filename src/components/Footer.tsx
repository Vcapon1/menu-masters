export function Footer() {
  return (
    <footer className="bg-secondary text-secondary-foreground py-12">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          {/* Brand */}
          <div className="md:col-span-2">
            <div className="flex items-center gap-2 mb-4">
              <div className="w-10 h-10 rounded-xl bg-gradient-primary flex items-center justify-center">
                <span className="text-primary-foreground font-bold text-xl">P</span>
              </div>
              <span className="font-display text-xl font-bold">
                Premium<span className="text-primary">Menu</span>
              </span>
            </div>
            <p className="text-secondary-foreground/70 text-sm max-w-md">
              Transforme seu restaurante com cardápios digitais modernos. 
              Aumente suas vendas e melhore a experiência dos seus clientes.
            </p>
          </div>

          {/* Links */}
          <div>
            <h4 className="font-semibold mb-4">Produto</h4>
            <ul className="space-y-2 text-sm text-secondary-foreground/70">
              <li><a href="#vantagens" className="hover:text-primary transition-colors">Vantagens</a></li>
              <li><a href="#planos" className="hover:text-primary transition-colors">Planos</a></li>
              <li><a href="#como-funciona" className="hover:text-primary transition-colors">Como Funciona</a></li>
            </ul>
          </div>

          {/* Support */}
          <div>
            <h4 className="font-semibold mb-4">Suporte</h4>
            <ul className="space-y-2 text-sm text-secondary-foreground/70">
              <li><a href="#contato" className="hover:text-primary transition-colors">Contato</a></li>
              <li><a href="#" className="hover:text-primary transition-colors">FAQ</a></li>
              <li><a href="#" className="hover:text-primary transition-colors">Termos de Uso</a></li>
            </ul>
          </div>
        </div>

        <div className="border-t border-secondary-foreground/10 pt-8 text-center text-sm text-secondary-foreground/50">
          <p>© {new Date().getFullYear()} Premium Menu. Todos os direitos reservados.</p>
        </div>
      </div>
    </footer>
  );
}
