import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";

interface DirectorySearchProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

const DirectorySearch = ({ 
  value, 
  onChange, 
  placeholder = "Buscar restaurante..." 
}: DirectorySearchProps) => {
  return (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
      <Input
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="pl-10 bg-background"
      />
    </div>
  );
};

export default DirectorySearch;
