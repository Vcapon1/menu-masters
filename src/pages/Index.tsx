import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { WhatsAppFloat } from "@/components/WhatsAppFloat";
import { ContactForm } from "@/components/ContactForm";
import { PlanCard } from "@/components/PlanCard";
import { FeatureCard } from "@/components/FeatureCard";
import { Button } from "@/components/ui/button";
import {
  Smartphone,
  Zap,
  TrendingUp,
  Palette,
  QrCode,
  Image,
  PlayCircle,
  Filter,
  Tag,
  Star,
  CheckCircle,
  ArrowRight,
  Sparkles,
} from "lucide-react";
import heroImage from "@/assets/hero-digital-menu.jpg";

const plans = [
  {
    name: "Basic",
    description: "Perfeito para começar seu cardápio digital",
    monthlyPrice: 65,
    annualPrice: 45,
    features: [
      { text: "Lista completa de produtos", included: true },
      { text: "Nome, preço e foto dos itens", included: true },
      { text: "Clique para ampliar imagens", included: true },
      { text: "URL única + QR Code", included: true },
      { text: "Filtro por categorias", included: false },
      { text: "Ícones especiais (vegano, promoção)", included: false },
      { text: "Vídeos nos produtos", included: false },
      { text: "Logotipo e cores personalizadas", included: false },
    ],
  },
  {
    name: "Premium",
    description: "O mais completo para restaurantes exigentes",
    monthlyPrice: 99,
    annualPrice: 79,
    features: [
      { text: "Tudo do plano Basic", included: true },
      { text: "Filtro por categorias (estilo iFood)", included: true },
      { text: "Ícones: promoção, vegano, destaque", included: true },
      { text: "Vídeos por produto", included: true },
      { text: "Logotipo do restaurante", included: true },
      { text: "Cores personalizadas", included: true },
      { text: "Indicador de mais pedidos", included: true },
      { text: "Status de produto em falta", included: true },
    ],
    highlighted: true,
    highlightLabel: "Mais Popular",
  },
  {
    name: "Personalité",
    description: "Layout exclusivo para sua marca",
    monthlyPrice: 199,
    annualPrice: 149,
    features: [
      { text: "Tudo do plano Premium", included: true },
      { text: "Layout totalmente personalizado", included: true },
      { text: "Design exclusivo da sua marca", included: true },
      { text: "Consultoria de design inclusa", included: true },
      { text: "Suporte prioritário", included: true },
      { text: "Integrações especiais", included: true },
      { text: "Múltiplos cardápios", included: true },
      { text: "Relatórios de visualização", included: true },
    ],
  },
];

const features = [
  {
    icon: Smartphone,
    title: "100% Mobile-First",
    description:
      "Design otimizado para smartphones. Seus clientes terão a melhor experiência na palma da mão.",
  },
  {
    icon: Zap,
    title: "Atualização Instantânea",
    description:
      "Altere preços, adicione produtos ou marque itens em falta em segundos. Sem complicação.",
  },
  {
    icon: TrendingUp,
    title: "Aumente Suas Vendas",
    description:
      "Fotos apetitosas e destaques estratégicos estimulam pedidos maiores e mais lucrativos.",
  },
  {
    icon: Palette,
    title: "Sua Marca, Seu Estilo",
    description:
      "Personalize cores, logotipo e layout para combinar perfeitamente com a identidade do seu restaurante.",
  },
  {
    icon: QrCode,
    title: "QR Code Automático",
    description:
      "Receba seu QR Code pronto para imprimir. Cole nas mesas e seu cardápio está no ar.",
  },
  {
    icon: Image,
    title: "Fotos que Vendem",
    description:
      "Imagens grandes e de qualidade que fazem a boca do cliente salivar antes mesmo de pedir.",
  },
];

const differentials = [
  { icon: PlayCircle, text: "Vídeos dos pratos em ação" },
  { icon: Filter, text: "Filtro por categorias estilo iFood" },
  { icon: Tag, text: "Ícones de vegano, promoção e destaques" },
  { icon: Star, text: "Indicador de mais pedidos" },
];

export default function Index() {
  return (
    <div className="min-h-screen bg-background noise-overlay">
      <Header />

      {/* Hero Section */}
      <section className="relative min-h-screen flex items-center overflow-hidden">
        {/* Background Image with Overlay */}
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: `url(${heroImage})` }}
        />
        <div className="absolute inset-0 bg-gradient-to-r from-background via-background/95 to-background/70" />
        <div className="absolute inset-0 bg-gradient-to-t from-background via-transparent to-background/50" />
        
        {/* Ambient glow */}
        <div className="absolute top-1/2 left-1/4 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[120px] -translate-y-1/2" />

        <div className="container relative mx-auto px-4 py-32">
          <div className="max-w-3xl">
            {/* Badge */}
            <div className="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 text-primary px-4 py-2 rounded-full text-sm font-medium mb-8 animate-fade-in">
              <Sparkles className="w-4 h-4" />
              <span>Cardápio Digital Profissional</span>
            </div>

            {/* Headline */}
            <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-display font-bold text-foreground leading-[1.1] mb-6 animate-fade-in">
              Transforme seu{" "}
              <span className="text-gradient">cardápio</span>
              <br />
              em uma{" "}
              <span className="text-gradient">máquina de vendas</span>
            </h1>

            {/* Subtitle */}
            <p className="text-lg md:text-xl text-muted-foreground mb-10 max-w-2xl animate-fade-in delay-100">
              Cardápio digital moderno e elegante. Seus clientes escaneiam o QR Code 
              e têm acesso instantâneo ao menu completo do seu restaurante.
            </p>

            {/* CTAs */}
            <div className="flex flex-col sm:flex-row gap-4 mb-12 animate-fade-in delay-200">
              <Button variant="hero" size="xl" className="gap-2 shadow-glow">
                Começar Agora
                <ArrowRight className="w-5 h-5" />
              </Button>
              <Button variant="heroOutline" size="xl">
                Ver Demonstração
              </Button>
            </div>

            {/* Trust Badges */}
            <div className="flex flex-wrap items-center gap-6 animate-fade-in delay-300">
              <div className="flex items-center gap-2">
                <CheckCircle className="w-5 h-5 text-primary" />
                <span className="text-sm text-muted-foreground">Sem fidelidade</span>
              </div>
              <div className="flex items-center gap-2">
                <CheckCircle className="w-5 h-5 text-primary" />
                <span className="text-sm text-muted-foreground">Setup em 24h</span>
              </div>
              <div className="flex items-center gap-2">
                <CheckCircle className="w-5 h-5 text-primary" />
                <span className="text-sm text-muted-foreground">Suporte dedicado</span>
              </div>
            </div>
          </div>
        </div>

        {/* Scroll indicator */}
        <div className="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce-gentle">
          <div className="w-6 h-10 rounded-full border-2 border-muted-foreground/30 flex items-start justify-center p-2">
            <div className="w-1.5 h-2.5 bg-primary rounded-full" />
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="vantagens" className="py-24 relative">
        <div className="absolute inset-0 bg-gradient-radial opacity-50" />
        
        <div className="container relative mx-auto px-4">
          <div className="text-center mb-16">
            <span className="text-primary font-medium text-sm uppercase tracking-wider mb-4 block">
              Vantagens
            </span>
            <h2 className="section-title mb-4">
              Por que escolher o{" "}
              <span className="text-gradient">Premium Menu</span>?
            </h2>
            <p className="section-subtitle mx-auto">
              Tudo o que você precisa para ter um cardápio digital profissional
              que impressiona seus clientes.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((feature, index) => (
              <FeatureCard
                key={index}
                icon={feature.icon}
                title={feature.title}
                description={feature.description}
                className="animate-fade-in"
                style={{ animationDelay: `${index * 100}ms` } as React.CSSProperties}
              />
            ))}
          </div>
        </div>
      </section>

      {/* How It Works Section */}
      <section id="como-funciona" className="py-24 relative overflow-hidden">
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-primary/10 rounded-full blur-[150px]" />
        
        <div className="container relative mx-auto px-4">
          <div className="text-center mb-16">
            <span className="text-primary font-medium text-sm uppercase tracking-wider mb-4 block">
              Simples e Rápido
            </span>
            <h2 className="section-title mb-4">
              Como <span className="text-gradient">funciona</span>?
            </h2>
            <p className="section-subtitle mx-auto">
              Em 3 passos simples, seu cardápio digital está no ar.
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {[
              {
                step: "01",
                title: "Cadastre-se",
                description:
                  "Crie sua conta e envie os dados do seu restaurante: produtos, fotos, preços e categorias.",
              },
              {
                step: "02",
                title: "Personalize",
                description:
                  "Escolha o template, adicione seu logotipo e defina as cores da sua marca.",
              },
              {
                step: "03",
                title: "Publique",
                description:
                  "Receba seu QR Code exclusivo e cole nas mesas. Pronto, seu cardápio está no ar!",
              },
            ].map((item, index) => (
              <div key={index} className="text-center animate-fade-in group">
                <div className="relative inline-block mb-6">
                  <div className="w-20 h-20 rounded-2xl bg-gradient-primary flex items-center justify-center shadow-glow group-hover:shadow-glow-lg transition-shadow duration-500">
                    <span className="text-primary-foreground font-bold text-2xl">
                      {item.step}
                    </span>
                  </div>
                  {index < 2 && (
                    <div className="hidden md:block absolute top-1/2 left-full w-full h-0.5 bg-gradient-to-r from-primary/50 to-transparent -translate-y-1/2 ml-4" />
                  )}
                </div>
                <h3 className="text-xl font-semibold text-foreground mb-3">
                  {item.title}
                </h3>
                <p className="text-muted-foreground">{item.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Differentials Section */}
      <section className="py-24 relative">
        <div className="container mx-auto px-4">
          <div className="glow-card p-8 md:p-12 lg:p-16">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <span className="text-primary font-medium text-sm uppercase tracking-wider mb-4 block">
                  Diferenciais
                </span>
                <h2 className="text-3xl md:text-4xl font-display font-bold text-foreground mb-6">
                  Diferenciais que fazem a{" "}
                  <span className="text-gradient">diferença</span>
                </h2>
                <p className="text-muted-foreground mb-8">
                  O Premium Menu não é apenas um cardápio digital. É uma
                  ferramenta de vendas pensada para encantar seus clientes e
                  aumentar seu faturamento.
                </p>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {differentials.map((item, index) => (
                    <div
                      key={index}
                      className="flex items-center gap-3 bg-muted/50 hover:bg-muted p-4 rounded-xl transition-colors duration-300 group"
                    >
                      <div className="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0 group-hover:shadow-glow transition-shadow duration-300">
                        <item.icon className="w-5 h-5 text-primary-foreground" />
                      </div>
                      <span className="font-medium text-foreground">{item.text}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="relative">
                <div className="absolute inset-0 bg-gradient-primary opacity-20 blur-3xl rounded-full" />
                <img
                  src={heroImage}
                  alt="Premium Menu showcase"
                  className="relative rounded-2xl shadow-2xl w-full"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section id="planos" className="py-24 relative">
        <div className="absolute inset-0 bg-gradient-radial opacity-30" />
        
        <div className="container relative mx-auto px-4">
          <div className="text-center mb-16">
            <span className="text-primary font-medium text-sm uppercase tracking-wider mb-4 block">
              Planos
            </span>
            <h2 className="section-title mb-4">
              Escolha o plano <span className="text-gradient">ideal</span>
            </h2>
            <p className="section-subtitle mx-auto">
              Planos flexíveis que cabem no seu bolso e crescem com seu
              negócio.
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {plans.map((plan, index) => (
              <PlanCard
                key={index}
                {...plan}
              />
            ))}
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section id="contato" className="py-24 relative">
        <div className="container mx-auto px-4">
          <div className="max-w-xl mx-auto">
            <div className="text-center mb-10">
              <span className="text-primary font-medium text-sm uppercase tracking-wider mb-4 block">
                Contato
              </span>
              <h2 className="section-title mb-4">
                Fale <span className="text-gradient">Conosco</span>
              </h2>
              <p className="section-subtitle mx-auto">
                Preencha o formulário abaixo e entraremos em contato em até
                24 horas.
              </p>
            </div>

            <div className="glass-card p-8">
              <ContactForm />
            </div>
          </div>
        </div>
      </section>

      <Footer />
      <WhatsAppFloat phoneNumber="5511999999999" />
    </div>
  );
}
