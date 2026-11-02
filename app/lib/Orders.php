<?php
declare(strict_types=1);

final class Orders
{
    public static function newCode(): string
    {
        do { $c = (string)random_int(10000000, 99999999); } while (DB::val('SELECT 1 FROM orders WHERE code = ?', [$c]));
        return $c;
    }

    public static function grant(array $order): void
    {
        $_SESSION['orders'][$order['code']] = true;
    }

    /** customers can open an order if it's in their session OR they have the signed token link */
    public static function canView(array $order): bool
    {
        if (!empty($_SESSION['orders'][$order['code']])) return true;
        if (Customer::enabled() && Customer::owns($order)) return true;
        $t = (string)($_GET['t'] ?? '');
        return $t !== '' && hash_equals((string)$order['track_token'], $t);
    }

    public static function link(array $order): string
    {
        return url('order/' . $order['code'], ['t' => $order['track_token']]);
    }

    public static function cartItems(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) return [[], 0];
        $ids = array_map('intval', array_keys($cart));
        $rows = DB::all('SELECT * FROM parts WHERE active = 1 AND id IN ' . DB::in($ids), $ids);
        $items = []; $sum = 0;
        foreach ($rows as $p) {
            $qty = max(1, min((int)$cart[$p['id']], max(1, (int)$p['stock']), 20));
            $price = ($p['sale_price'] && $p['sale_price'] < $p['price']) ? (int)$p['sale_price'] : (int)$p['price'];
            $items[] = ['part' => $p, 'qty' => $qty, 'price' => $price, 'line' => $price * $qty];
            $sum += $price * $qty;
        }
        return [$items, $sum];
    }

    public static function shipping(int $subtotal): int
    {
        $free = (int)setting('free_shipping_over', 0);
        if ($free > 0 && $subtotal >= $free) return 0;
        return (int)setting('shipping_cost', 0);
    }

    /** Called once after a verified successful payment */
    public static function markPaid(array $order, array $tx): void
    {
        DB::tx(function () use ($order, $tx) {
            $paid = (int)$order['paid_amount'] + (int)$tx['amount'];
            DB::update('orders', ['status' => 'paid', 'paid_amount' => $paid, 'updated_at' => now()], 'id = ?', [$order['id']]);
            if ($order['type'] === 'car' && $order['car_id']) {
                DB::update('cars', ['status' => 'reserved', 'updated_at' => now()], 'id = ? AND status = ?', [$order['car_id'], 'available']);
            }
            if ($order['type'] === 'parts') {
                foreach (DB::all('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]) as $it) {
                    DB::run('UPDATE parts SET stock = CASE WHEN stock >= ? THEN stock - ? ELSE 0 END WHERE id = ?', [$it['qty'], $it['qty'], $it['part_id']]);
                }
            }
        });
        Notify::paid($order, (string)$tx['ref_id']);
    }

    public static function statusBadge(string $s): string
    {
        [$l, $c] = Catalog::ORDER_STATUS[$s] ?? [$s, 'info'];
        return '<span class="badge ' . $c . '">' . e($l) . '</span>';
    }
}
