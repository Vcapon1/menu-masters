import { MessageCircle } from "lucide-react";

interface WhatsAppFloatProps {
  phoneNumber: string;
  message?: string;
}

export function WhatsAppFloat({ phoneNumber, message = "Olá! Gostaria de saber mais sobre o Premium Menu." }: WhatsAppFloatProps) {
  const handleClick = () => {
    const encodedMessage = encodeURIComponent(message);
    const url = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
    window.open(url, '_blank');
  };

  return (
    <button
      onClick={handleClick}
      className="whatsapp-float group"
      aria-label="Contato via WhatsApp"
    >
      <MessageCircle className="w-7 h-7 group-hover:scale-110 transition-transform" />
    </button>
  );
}
