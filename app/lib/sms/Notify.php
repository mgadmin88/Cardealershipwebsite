<?php
declare(strict_types=1);

/** Event notifications (SMS to customer & admins). Silently no-op when the SMS module is off. */
final class Notify
{
    private static function admins(string $event, string $name, string $mobile): void
    {
        foreach (array_filter(array_map('trim', explode(',', (string)setting('sms_admin_mobiles')))) as $m) {
            Sms::send($m, Sms::tpl('admin', ['event' => $event, 'name' => $name, 'mobile' => $mobile]));
        }
    }

    /** notify site admins only */
    public static function adminEvent(string $event, string $name, string $mobile): void
    {
        if (module('sms')) self::admins($event, $name, $mobile);
    }

    public static function request(string $type, string $name, string $mobile): void
    {
        if (!module('sms')) return;
        Sms::send($mobile, Sms::tpl('request', ['name' => $name]));
        self::admins(Catalog::REQUEST_TYPES[$type] ?? $type, $name, $mobile);
    }

    public static function orderCreated(array $o): void
    {
        if (!module('sms')) return;
        Sms::send($o['mobile'], Sms::tpl('order', ['name' => $o['customer_name'], 'code' => $o['code'], 'amount' => num($o['pay_amount'])]));
        self::admins('سفارش جدید ' . $o['code'], $o['customer_name'], $o['mobile']);
    }

    public static function paid(array $o, string $ref): void
    {
        if (!module('sms')) return;
        Sms::send($o['mobile'], Sms::tpl('paid', ['name' => $o['customer_name'], 'code' => $o['code'], 'ref' => $ref, 'amount' => num($o['pay_amount'])]));
        self::admins('پرداخت موفق سفارش ' . $o['code'], $o['customer_name'], $o['mobile']);
    }
}
