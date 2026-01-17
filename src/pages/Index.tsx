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
} from "lucide-react";
import heroImage from "@/assets/hero-menu.jpg";
import mockupPhone from "@/assets/mockup-phone.png";

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
    <div className="min-h-screen bg-background">
      <Header />

      {/* Hero Section */}
      <section className="relative pt-24 md:pt-32 pb-16 md:pb-24 overflow-hidden">
        <div
          className="absolute inset-0 bg-cover bg-center opacity-10"
          style={{ backgroundImage: `url(${heroImage})` }}
        />
        <div className="absolute inset-0 bg-gradient-hero" />

        <div className="container relative mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            {/* Text Content */}
            <div className="text-center lg:text-left animate-fade-in">
              <div className="inline-flex items-center gap-2 bg-primary/10 text-primary px-4 py-2 rounded-full text-sm font-medium mb-6">
                <Zap className="w-4 h-4" />
                <span>Cardápio Digital Profissional</span>
              </div>

              <h1 className="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-foreground leading-tight mb-6">
                Transforme seu{" "}
                <span className="text-gradient">cardápio</span> em uma{" "}
                <span className="text-gradient">máquina de vendas</span>
              </h1>

              <p className="text-lg md:text-xl text-muted-foreground mb-8 max-w-xl mx-auto lg:mx-0">
                Cardápio digital moderno, bonito e fácil de usar. Seus clientes
                escaneiam o QR Code e têm acesso instantâneo ao menu completo.
              </p>

              <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                <Button variant="hero" size="xl" className="gap-2">
                  Começar Agora
                  <ArrowRight className="w-5 h-5" />
                </Button>
                <Button variant="heroOutline" size="xl">
                  Ver Demonstração
                </Button>
              </div>

              <div className="flex items-center gap-6 mt-8 justify-center lg:justify-start">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success" />
                  <span className="text-sm text-muted-foreground">
                    Sem fidelidade
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5 text-success" />
                  <span className="text-sm text-muted-foreground">
                    Setup em 24h
                  </span>
                </div>
              </div>
            </div>

            {/* Phone Mockup */}
            <div className="flex justify-center animate-float">
              <img
                src={mockupPhone}
                alt="Premium Menu no smartphone"
                className="w-72 md:w-96 drop-shadow-2xl"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="vantagens" className="py-20 bg-muted/30">
        <div className="container mx-auto px-4">
          <div className="text-center mb-16">
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
      <section id="como-funciona" className="py-20">
        <div className="container mx-auto px-4">
          <div className="text-center mb-16">
            <h2 className="section-title mb-4">
              Como <span className="text-gradient">funciona</span>?
            </h2>
            <p className="section-subtitle mx-auto">
              Em 3 passos simples, seu cardápio digital está no ar.
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
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
              <div key={index} className="text-center animate-fade-in">
                <div className="w-16 h-16 rounded-2xl bg-gradient-primary flex items-center justify-center mx-auto mb-6 shadow-lg">
                  <span className="text-primary-foreground font-bold text-xl">
                    {item.step}
                  </span>
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
      <section className="py-20 bg-secondary text-secondary-foreground">
        <div className="container mx-auto px-4">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-display font-bold mb-6">
                Diferenciais que fazem a{" "}
                <span className="text-primary">diferença</span>
              </h2>
              <p className="text-secondary-foreground/80 mb-8">
                O Premium Menu não é apenas um cardápio digital. É uma
                ferramenta de vendas pensada para encantar seus clientes e
                aumentar seu faturamento.
              </p>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {differentials.map((item, index) => (
                  <div
                    key={index}
                    className="flex items-center gap-3 bg-secondary-foreground/5 p-4 rounded-xl"
                  >
                    <div className="w-10 h-10 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                      <item.icon className="w-5 h-5 text-primary-foreground" />
                    </div>
                    <span className="font-medium">{item.text}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex justify-center">
              <img
                src={heroImage}
                alt="Premium Menu showcase"
                className="rounded-3xl shadow-2xl max-w-md w-full"
              />
            </div>
          </div>
        </div>
      </section>

      {/* Pricing Section */}
      <section id="planos" className="py-20">
        <div className="container mx-auto px-4">
          <div className="text-center mb-16">
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
      <section id="contato" className="py-20 bg-muted/30">
        <div className="container mx-auto px-4">
          <div className="max-w-xl mx-auto">
            <div className="text-center mb-10">
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
