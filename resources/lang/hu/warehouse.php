<?php

return [
    'title' => 'Raktárak',
    'create' => 'Új raktár',
    'edit' => 'Raktár szerkesztése',
    'move_stock' => 'Készlet áthelyezése',
    'form' => [
        'name' => 'Név',
        'description' => 'Leírás',
        'source_region' => 'Forrás régió',
        'destination_region' => 'Cél régió',
        'destination_region_helper' => 'A cél régiónak különböznie kell a forrás régiótól',
        'product' => 'Termék',
        'quantity' => 'Mennyiség',
        'available_stock' => 'Elérhető: :stock',
        'move_stock_button' => 'Áthelyezés',
    ],
    'table' => [
        'name' => 'Név',
        'description' => 'Leírás',
        'updated' => 'Módosítva',
        'created' => 'Létrehozva',
    ],
    'notifications' => [
        'insufficient_stock_title' => 'Nincs elegendő készlet',
        'insufficient_stock_body' => 'Csak :available darab áll rendelkezésre, de :requested darabot szeretnél áthelyezni.',
        'stock_moved_title' => 'Készlet áthelyezve',
        'stock_moved_body' => ':quantity darab :product sikeresen áthelyezve :source régióból :destination régióba.',
    ],
];
