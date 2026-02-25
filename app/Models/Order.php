<?php

/**
 * Order Model (single-product purchase records).
 */
class Order extends Model
{
    protected $table = 'orders';

    public function __construct()
    {
        parent::__construct();
    }

    public function generateOrderCode(): string
    {
        return 'Y' . strtoupper(bin2hex(random_bytes(8)));
    }
}
