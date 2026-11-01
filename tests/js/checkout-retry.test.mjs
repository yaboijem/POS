import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import { test } from 'node:test';

test('uncertain checkout freezes its cart and retries the original payload', async () => {
    const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');
    let factory;
    const window = { toast() {}, confirmAction: ({ onConfirm }) => onConfirm() };
    vm.runInNewContext(source.slice(source.indexOf("    Alpine.data('posTerminal'"), source.indexOf('\n});\n\nwindow.toast')), {
        Alpine: { data: (_name, value) => { factory = value; } }, window,
        crypto: { randomUUID: () => 'retry-key' },
    });
    const terminal = factory({ isAdmin: false, paymentMethods: [{ value: 'cash' }], checkoutUrl: '/checkout' });
    const original = { id: 1, name: 'A', price_cents: 10000, qty: 1, unit: 'piece', whole_quantity: true, is_available: true };
    terminal.cart = [original];
    terminal.openCheckout();
    const sent = [];
    window.apiFetch = async (_url, options) => {
        sent.push(options.body);
        if (sent.length === 1) throw new Error('Response lost after commit');
        return { sale: { reference: 'saved-A' }, receipt_html: '', receipt_url: '/receipt', receipt_pdf_url: '/pdf' };
    };
    terminal.fetchProducts = () => {};
    await terminal.submitCheckout();
    terminal.clearCart();
    terminal.addProduct({ ...original, id: 2, name: 'B' });
    assert.equal(terminal.cart.length, 1);
    assert.equal(terminal.cart[0].id, 1, 'Do not discard an unresolved order');
    terminal.notes = 'Changed after response loss';
    await terminal.submitCheckout();
    assert.equal(sent[1], sent[0], 'Retry the exact original checkout');
    assert.equal(terminal.completedSale.reference, 'saved-A');
});
