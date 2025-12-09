<?php

return [
    'title' => 'Warehouses',
    'create' => 'New warehouse',
    'edit' => 'Edit warehouse',
    'move_stock' => 'Move Stock',
    'form' => [
        'name' => 'Name',
        'description' => 'Description',
        'source_region' => 'Source Region',
        'destination_region' => 'Destination Region',
        'destination_region_helper' => 'The destination region must be different from the source region',
        'product' => 'Product',
        'quantity' => 'Quantity',
        'available_stock' => 'Available: :stock',
        'move_stock_button' => 'Move Stock',
    ],
    'table' => [
        'name' => 'Name',
        'description' => 'Description',
        'updated' => 'Updated at',
        'created' => 'Created at',
    ],
    'notifications' => [
        'insufficient_stock_title' => 'Insufficient Stock',
        'insufficient_stock_body' => 'Only :available units are available, but you are trying to move :requested units.',
        'stock_moved_title' => 'Stock Moved',
        'stock_moved_body' => ':quantity units of :product successfully moved from :source region to :destination region.',
    ],
];
