/**
 * PREMIUM MENU - Sistema de Carrinho Modular
 * 
 * Gerencia carrinho em localStorage, modal de variações,
 * drawer flutuante e finalização por modo (WhatsApp, Mesa, etc.)
 * 
 * Variáveis globais esperadas (definidas pelo template PHP):
 * - CART_MODE: objeto com dados do modo de carrinho
 * - RESTAURANT: objeto com dados do restaurante
 * - TABLE_NUMBER: número da mesa (se modo mesa)
 * - IS_OPEN: boolean se restaurante está aberto
 * - PRODUCT_VARIATIONS: objeto {productId: [variações]}
 */

const Cart = {
    items: [],
    storageKey: 'cart_' + (RESTAURANT?.id || 'default'),

    init() {
        this.load();
        this.renderFloatingButton();
        if (!IS_OPEN && CART_MODE) {
            this.showClosedBanner();
        }
        OrderTracker.init();
    },

    load() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            this.items = saved ? JSON.parse(saved) : [];
        } catch (e) {
            this.items = [];
        }
    },

    save() {
        localStorage.setItem(this.storageKey, JSON.stringify(this.items));
        this.updateBadge();
    },

    addItem(product, quantity = 1, sizeSelected = null, sizePrice = null, variations = [], notes = '') {
        const unitPrice = sizePrice || product.promoPrice || product.price;
        const variationsTotal = variations.reduce((sum, v) => sum + (v.price || 0), 0);
        const finalUnitPrice = unitPrice + variationsTotal;

        const item = {
            id: Date.now() + Math.random(),
            productId: product.id,
            productName: product.name,
            quantity,
            sizeSelected,
            sizePrice: sizePrice || unitPrice,
            variations,
            unitPrice: finalUnitPrice,
            subtotal: finalUnitPrice * quantity,
            notes,
            image: product.image || null
        };

        this.items.push(item);
        this.save();
        this.showToast(product.name + ' adicionado ao carrinho');
    },

    removeItem(itemId) {
        this.items = this.items.filter(i => i.id !== itemId);
        this.save();
    },

    updateQuantity(itemId, quantity) {
        const item = this.items.find(i => i.id === itemId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            item.subtotal = item.unitPrice * item.quantity;
            this.save();
        }
    },

    getTotal() {
        return this.items.reduce((sum, item) => sum + item.subtotal, 0);
    },

    getCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    clear() {
        this.items = [];
        this.save();
    },

    _getSavedTableNumber() {
        try {
            return localStorage.getItem('saved_table_' + (RESTAURANT?.id || 'default')) || '';
        } catch (e) { return ''; }
    },

    _saveTableNumber(num) {
        try {
            if (num) localStorage.setItem('saved_table_' + (RESTAURANT?.id || 'default'), num);
        } catch (e) {}
    },

    // ========== UI: Botão Flutuante ==========
    renderFloatingButton() {
        if (!CART_MODE) return;

        const btn = document.createElement('div');
        btn.id = 'cart-float';
        btn.innerHTML = `
            <button onclick="Cart.openDrawer()" class="cart-float-btn" aria-label="Abrir carrinho">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <span id="cart-badge" class="cart-badge" style="display:none">0</span>
            </button>
        `;
        document.body.appendChild(btn);
        this.updateBadge();
    },

    updateBadge() {
        const badge = document.getElementById('cart-badge');
        if (!badge) return;
        const count = this.getCount();
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    },

    showToast(message) {
        const existing = document.getElementById('cart-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.className = 'cart-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    },

    showClosedBanner() {
        const banner = document.createElement('div');
        banner.className = 'closed-banner';
        banner.innerHTML = `
            <span>🔴 Estamos fechados no momento</span>
            <small>Você pode navegar pelo cardápio, mas pedidos estão desativados.</small>
        `;
        document.body.prepend(banner);
    },

    // ========== UI: Modal de Variações ==========
    openVariationsModal(product) {
        if (!IS_OPEN) {
            this.showToast('Restaurante fechado no momento');
            return;
        }

        const variations = PRODUCT_VARIATIONS[product.id] || [];
        const sizesPrices = product.sizesPrices || null;
        const hasVariations = variations.length > 0;
        const hasSizes = sizesPrices && sizesPrices.length > 0;

        // Se não tem variações nem tamanhos, adicionar direto
        if (!hasVariations && !hasSizes) {
            this.addItem(product);
            return;
        }

        // Criar modal
        const modal = document.createElement('div');
        modal.id = 'variations-modal';
        modal.className = 'variations-modal active';
        
        let sizesHtml = '';
        if (hasSizes) {
            sizesHtml = `
                <div class="var-group" data-required="true">
                    <h4 class="var-group-title">Tamanho <span class="var-required">*obrigatório</span></h4>
                    <div class="var-options">
                        ${sizesPrices.map((s, i) => `
                            <label class="var-option">
                                <input type="radio" name="size" value="${i}" data-label="${s.label}" data-price="${s.price}" ${i === 0 ? 'checked' : ''}>
                                <span class="var-option-label">${s.label}</span>
                                <span class="var-option-price">R$ ${parseFloat(s.price).toFixed(2).replace('.', ',')}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        let variationsHtml = '';
        if (hasVariations) {
            variationsHtml = variations.map(group => {
                const options = typeof group.options === 'string' ? JSON.parse(group.options) : group.options;
                const isRadio = group.max_selections == 1;
                const inputType = isRadio ? 'radio' : 'checkbox';
                const inputName = isRadio ? `var_${group.id}` : `var_${group.id}[]`;

                return `
                    <div class="var-group" data-required="${group.is_required ? 'true' : 'false'}" data-group-id="${group.id}">
                        <h4 class="var-group-title">
                            ${group.group_name}
                            ${group.is_required ? '<span class="var-required">*obrigatório</span>' : '<span class="var-optional">opcional</span>'}
                            ${group.max_selections > 1 ? `<span class="var-max">(máx ${group.max_selections})</span>` : ''}
                        </h4>
                        <div class="var-options">
                            ${options.map((opt, i) => `
                                <label class="var-option">
                                    <input type="${inputType}" name="${inputName}" value="${i}" 
                                           data-label="${opt.label}" data-price="${opt.price || 0}"
                                           data-max="${group.max_selections}">
                                    <span class="var-option-label">${opt.label}</span>
                                    ${opt.price > 0 ? `<span class="var-option-price">+R$ ${parseFloat(opt.price).toFixed(2).replace('.', ',')}</span>` : ''}
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `;
            }).join('');
        }

        modal.innerHTML = `
            <div class="variations-overlay" onclick="Cart.closeVariationsModal()"></div>
            <div class="variations-content">
                <div class="variations-header">
                    <h3>${product.name}</h3>
                    <button onclick="Cart.closeVariationsModal()" class="variations-close">&times;</button>
                </div>
                <div class="variations-body">
                    ${sizesHtml}
                    ${variationsHtml}
                </div>
                <div class="variations-footer">
                    <div class="qty-control">
                        <button onclick="Cart.changeQty(-1)" class="qty-btn">−</button>
                        <span id="var-qty">1</span>
                        <button onclick="Cart.changeQty(1)" class="qty-btn">+</button>
                    </div>
                    <button onclick="Cart.confirmVariations()" class="var-add-btn">
                        Adicionar <span id="var-total-price"></span>
                    </button>
                </div>
            </div>
        `;

        modal._product = product;
        modal._qty = 1;
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        // Attach change listeners for price update
        modal.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', () => Cart.updateVariationTotal());
            // Limit checkbox selections
            if (input.type === 'checkbox') {
                input.addEventListener('change', function() {
                    const max = parseInt(this.dataset.max) || 99;
                    const checked = this.closest('.var-options').querySelectorAll('input:checked');
                    if (checked.length > max) {
                        this.checked = false;
                    }
                });
            }
        });

        this.updateVariationTotal();
    },

    changeQty(delta) {
        const modal = document.getElementById('variations-modal');
        if (!modal) return;
        modal._qty = Math.max(1, (modal._qty || 1) + delta);
        document.getElementById('var-qty').textContent = modal._qty;
        this.updateVariationTotal();
    },

    updateVariationTotal() {
        const modal = document.getElementById('variations-modal');
        if (!modal) return;

        const product = modal._product;
        const qty = modal._qty || 1;

        // Size price
        const sizeInput = modal.querySelector('input[name="size"]:checked');
        let basePrice = sizeInput ? parseFloat(sizeInput.dataset.price) : (product.promoPrice || product.price);

        // Variations price
        let varsPrice = 0;
        modal.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked').forEach(input => {
            if (input.name !== 'size') {
                varsPrice += parseFloat(input.dataset.price || 0);
            }
        });

        const total = (basePrice + varsPrice) * qty;
        const totalEl = document.getElementById('var-total-price');
        if (totalEl) {
            totalEl.textContent = '- R$ ' + total.toFixed(2).replace('.', ',');
        }
    },

    confirmVariations() {
        const modal = document.getElementById('variations-modal');
        if (!modal) return;

        const product = modal._product;
        const qty = modal._qty || 1;
        const notes = document.getElementById('var-notes')?.value || '';

        // Validate required groups
        const requiredGroups = modal.querySelectorAll('.var-group[data-required="true"]');
        for (const group of requiredGroups) {
            const checked = group.querySelectorAll('input:checked');
            if (checked.length === 0) {
                const title = group.querySelector('.var-group-title')?.textContent?.trim() || 'campo';
                this.showToast('Selecione: ' + title);
                return;
            }
        }

        // Get size
        const sizeInput = modal.querySelector('input[name="size"]:checked');
        const sizeSelected = sizeInput ? sizeInput.dataset.label : null;
        const sizePrice = sizeInput ? parseFloat(sizeInput.dataset.price) : null;

        // Get variations
        const variations = [];
        modal.querySelectorAll('input:checked').forEach(input => {
            if (input.name === 'size') return;
            variations.push({
                group: input.closest('.var-group')?.querySelector('.var-group-title')?.childNodes[0]?.textContent?.trim() || '',
                option: input.dataset.label,
                price: parseFloat(input.dataset.price || 0)
            });
        });

        this.addItem(product, qty, sizeSelected, sizePrice, variations, notes);
        this.closeVariationsModal();
    },

    closeVariationsModal() {
        const modal = document.getElementById('variations-modal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    },

    // ========== UI: Drawer do Carrinho ==========
    openDrawer() {
        const existing = document.getElementById('cart-drawer');
        if (existing) existing.remove();

        const drawer = document.createElement('div');
        drawer.id = 'cart-drawer';
        drawer.className = 'cart-drawer active';

        const itemsHtml = this.items.length === 0
            ? '<p class="cart-empty">Seu carrinho está vazio</p>'
            : this.items.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <strong>${item.productName}</strong>
                        ${item.sizeSelected ? `<small class="cart-item-size">${item.sizeSelected}</small>` : ''}
                        ${item.variations?.length ? `<small class="cart-item-vars">${item.variations.map(v => v.option).join(', ')}</small>` : ''}
                        ${item.notes ? `<small class="cart-item-notes">📝 ${item.notes}</small>` : ''}
                    </div>
                    <div class="cart-item-actions">
                        <div class="cart-item-qty">
                            <button onclick="Cart.updateQuantity(${item.id}, ${item.quantity - 1}); Cart.openDrawer();" class="qty-btn-sm">−</button>
                            <span>${item.quantity}</span>
                            <button onclick="Cart.updateQuantity(${item.id}, ${item.quantity + 1}); Cart.openDrawer();" class="qty-btn-sm">+</button>
                        </div>
                        <span class="cart-item-price">R$ ${item.subtotal.toFixed(2).replace('.', ',')}</span>
                        <button onclick="Cart.removeItem(${item.id}); Cart.openDrawer();" class="cart-item-remove">✕</button>
                    </div>
                </div>
            `).join('');

        const total = this.getTotal();
        
        drawer.innerHTML = `
            <div class="cart-drawer-overlay" onclick="Cart.closeDrawer()"></div>
            <div class="cart-drawer-content">
                <div class="cart-drawer-header">
                    <h3>Seu Pedido</h3>
                    <button onclick="Cart.closeDrawer()" class="cart-drawer-close">&times;</button>
                </div>
                <div class="cart-drawer-body">
                    ${CART_MODE?.slug === 'table' ? `
                    <div class="cart-table-field" style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.1);">
                        <label style="font-size:0.85rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:6px;">📍 Qual sua mesa? *</label>
                        <input type="number" id="cart-table-number" min="1" placeholder="Número da mesa" 
                         value="${this._tableNumber || Cart._getSavedTableNumber() || ''}"
                               style="width:100%;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:10px 14px;color:#fff;font-size:0.95rem;"
                               oninput="Cart._tableNumber = this.value">
                    </div>
                    ` : ''}
                    ${itemsHtml}
                </div>
                ${this.items.length > 0 ? `
                <div class="cart-drawer-footer">
                    <div class="cart-drawer-total">
                        <span>Total</span>
                        <strong>R$ ${total.toFixed(2).replace('.', ',')}</strong>
                    </div>
                    ${CART_MODE?.slug === 'whatsapp' ? `
                    <div class="cart-drawer-notes">
                        <textarea id="cart-general-notes" placeholder="Observações gerais do pedido..." rows="2" class="var-notes">${this._generalNotes || ''}</textarea>
                    </div>
                    ` : ''}
                    <button onclick="Cart.finalize()" class="cart-finalize-btn">
                        Finalizar Pedido
                    </button>
                    <button onclick="Cart.clear(); Cart.openDrawer();" class="cart-clear-btn">
                        Limpar carrinho
                    </button>
                </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(drawer);
        document.body.style.overflow = 'hidden';
    },

    closeDrawer() {
        // Save general notes
        const notesEl = document.getElementById('cart-general-notes');
        if (notesEl) this._generalNotes = notesEl.value;

        const drawer = document.getElementById('cart-drawer');
        if (drawer) {
            drawer.remove();
            document.body.style.overflow = '';
        }
    },

    // ========== Finalização por Modo ==========
    finalize() {
        if (this.items.length === 0) {
            this.showToast('Carrinho vazio');
            return;
        }

        // Validar mesa obrigatória no modo table
        if (CART_MODE?.slug === 'table') {
            const tableInput = document.getElementById('cart-table-number');
            const tableNum = tableInput?.value?.trim();
            if (!tableNum) {
                this.showToast('Informe o número da mesa');
                tableInput?.focus();
                tableInput?.style && (tableInput.style.borderColor = '#f87171');
                return;
            }
            this._tableNumber = tableNum;
            this._saveTableNumber(tableNum);
        }

        const generalNotes = document.getElementById('cart-general-notes')?.value || '';
        this._generalNotes = generalNotes;

        const mode = CART_MODE?.slug || 'whatsapp';

        switch (mode) {
            case 'whatsapp':
                this.finalizeWhatsApp(generalNotes);
                break;
            case 'table':
            case 'delivery':
            case 'full':
                this.finalizeInternal(mode, generalNotes);
                break;
            default:
                this.showToast('Modo de pedido não configurado');
        }
    },

    finalizeWhatsApp(generalNotes) {
        const whatsappNumber = CART_MODE?.config?.whatsapp_number || RESTAURANT?.whatsapp || '';
        if (!whatsappNumber) {
            this.showToast('Número de WhatsApp não configurado');
            return;
        }

        const cleanNumber = whatsappNumber.replace(/\D/g, '');
        const header = CART_MODE?.config?.msg_header || `🍽️ *Pedido - ${RESTAURANT.name}*`;

        let message = header + '\n\n';

        if (TABLE_NUMBER) {
            message += `📍 *Mesa:* ${TABLE_NUMBER}\n\n`;
        }

        message += '━━━━━━━━━━━━━━━━\n';

        this.items.forEach(item => {
            message += `\n*${item.quantity}x ${item.productName}*`;
            if (item.sizeSelected) message += ` (${item.sizeSelected})`;
            if (item.variations?.length) {
                message += '\n   ' + item.variations.map(v => {
                    let s = `▸ ${v.option}`;
                    if (v.price > 0) s += ` (+R$${v.price.toFixed(2).replace('.', ',')})`;
                    return s;
                }).join('\n   ');
            }
            if (item.notes) message += `\n   📝 ${item.notes}`;
            message += `\n   💰 R$ ${item.subtotal.toFixed(2).replace('.', ',')}\n`;
        });

        message += '\n━━━━━━━━━━━━━━━━';
        message += `\n\n💰 *Total: R$ ${this.getTotal().toFixed(2).replace('.', ',')}*`;

        if (generalNotes) {
            message += `\n\n📝 *Observações:* ${generalNotes}`;
        }

        const encoded = encodeURIComponent(message);
        const url = `https://wa.me/55${cleanNumber}?text=${encoded}`;
        window.open(url, '_blank');

        this.closeDrawer();
        this.clear();
    },

    finalizeInternal(mode, generalNotes) {
        // Redireciona para página de checkout
        const checkoutData = {
            items: this.items,
            mode: mode,
            tableNumber: this._tableNumber || TABLE_NUMBER || '',
            generalNotes: generalNotes,
            restaurantId: RESTAURANT.id,
            total: this.getTotal()
        };

        localStorage.setItem('checkout_data', JSON.stringify(checkoutData));
        window.location.href = `/checkout.php?restaurant=${RESTAURANT.slug}&mode=${mode}`;
    }
};

// ========== OrderTracker: Barra de Status do Pedido ==========
const OrderTracker = {
    _interval: null,
    _bar: null,
    _data: null,

    statusMap: {
        pending:    { label: 'Recebido',    color: '#eab308', pulse: true },
        confirmed:  { label: 'Confirmado',  color: '#eab308', pulse: true },
        preparing:  { label: 'Preparando',  color: '#f97316', pulse: true },
        ready:      { label: 'Pronto!',     color: '#22c55e', pulse: false },
        delivering: { label: 'Saiu p/ entrega', color: '#3b82f6', pulse: true },
        delivered:  { label: 'Entregue',    color: '#22c55e', pulse: false },
        cancelled:  { label: 'Cancelado',   color: '#ef4444', pulse: false }
    },

    init() {
        const key = 'active_order_' + (RESTAURANT?.id || 'default');
        try {
            const raw = localStorage.getItem(key);
            if (!raw) return;
            this._data = JSON.parse(raw);

            // Expirar após 4 horas
            const created = new Date(this._data.createdAt);
            if (Date.now() - created.getTime() > 4 * 60 * 60 * 1000) {
                localStorage.removeItem(key);
                return;
            }

            this.show();
            this.poll();
            this._interval = setInterval(() => this.poll(), 15000);
        } catch (e) {
            // silently ignore
        }
    },

    show(status = 'pending') {
        if (this._bar) return;

        const bar = document.createElement('div');
        bar.id = 'order-tracker-bar';
        bar.className = 'order-tracker-bar order-tracker-enter';
        bar.innerHTML = this._buildHTML(status);
        document.body.prepend(bar);
        document.body.classList.add('has-order-tracker');
        this._bar = bar;

        requestAnimationFrame(() => {
            bar.classList.remove('order-tracker-enter');
        });
    },

    _buildHTML(status) {
        const s = this.statusMap[status] || this.statusMap.pending;
        const orderId = this._data?.orderId || '';
        const token = this._data?.token || '';

        return `
            <div class="order-tracker-inner">
                <span class="order-tracker-info">
                    <strong>Pedido #${orderId}</strong>
                    <span class="order-tracker-dot${s.pulse ? ' pulse' : ''}" style="background:${s.color}"></span>
                    <span class="order-tracker-status">${s.label}</span>
                </span>
                <span class="order-tracker-actions">
                    <a href="/pedido/${token}" target="_blank" class="order-tracker-link">Acompanhar →</a>
                    <button onclick="OrderTracker.dismiss(event)" class="order-tracker-dismiss" title="Fechar">&times;</button>
                </span>
            </div>
        `;
    },

    async poll() {
        if (!this._data?.token) return;

        try {
            const res = await fetch('/api/orders.php?action=status&token=' + encodeURIComponent(this._data.token));
            const json = await res.json();

            if (json.success && json.order) {
                const status = json.order.status || 'pending';
                if (this._bar) {
                    this._bar.innerHTML = this._buildHTML(status);
                }

                // Status final: limpar após 30s
                if (status === 'delivered' || status === 'cancelled') {
                    clearInterval(this._interval);
                    setTimeout(() => {
                        this.remove();
                        const key = 'active_order_' + (RESTAURANT?.id || 'default');
                        localStorage.removeItem(key);
                    }, 30000);
                }
            }
        } catch (e) {
            // silently ignore network errors
        }
    },

    dismiss(event) {
        // Shift+click = encerrar completamente
        if (event && event.shiftKey) {
            const key = 'active_order_' + (RESTAURANT?.id || 'default');
            localStorage.removeItem(key);
            clearInterval(this._interval);
        }
        this.remove();
    },

    remove() {
        if (this._bar) {
            this._bar.classList.add('order-tracker-exit');
            setTimeout(() => {
                this._bar?.remove();
                this._bar = null;
                document.body.classList.remove('has-order-tracker');
            }, 300);
        }
    }
};

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => Cart.init());
