import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import Index from "./pages/Index";
import MenuPage from "./pages/MenuPage";
import MenuBoldPage from "./pages/MenuBoldPage";
import NotFound from "./pages/NotFound";
import AdminLogin from "./pages/admin/AdminLogin";
import AdminDashboard from "./pages/admin/AdminDashboard";
import AdminCategories from "./pages/admin/AdminCategories";
import AdminProducts from "./pages/admin/AdminProducts";
import MasterLogin from "./pages/master/MasterLogin";
import MasterDashboard from "./pages/master/MasterDashboard";
import MasterRestaurants from "./pages/master/MasterRestaurants";
import MasterPlans from "./pages/master/MasterPlans";
import MasterReports from "./pages/master/MasterReports";
import MasterTemplates from "./pages/master/MasterTemplates";

const queryClient = new QueryClient();

const App = () => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Toaster />
      <Sonner />
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Index />} />
          <Route path="/menu" element={<MenuPage />} />
          <Route path="/menu-bold" element={<MenuBoldPage />} />
          <Route path="/admin" element={<AdminLogin />} />
          <Route path="/admin/dashboard" element={<AdminDashboard />} />
          <Route path="/admin/categories" element={<AdminCategories />} />
          <Route path="/admin/products" element={<AdminProducts />} />
          <Route path="/master" element={<MasterLogin />} />
          <Route path="/master/dashboard" element={<MasterDashboard />} />
          <Route path="/master/restaurants" element={<MasterRestaurants />} />
          <Route path="/master/plans" element={<MasterPlans />} />
          <Route path="/master/reports" element={<MasterReports />} />
          <Route path="/master/templates" element={<MasterTemplates />} />
          {/* ADD ALL CUSTOM ROUTES ABOVE THE CATCH-ALL "*" ROUTE */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </BrowserRouter>
    </TooltipProvider>
  </QueryClientProvider>
);

export default App;
