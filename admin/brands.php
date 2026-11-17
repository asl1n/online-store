<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'brands', 'entity' => 'Brand', 'title' => 'Brands', 'active' => 'brands',
  'title_field' => 'name', 'slug_from' => 'name', 'search' => ['t.name'], 'order' => 't.sort_order, t.name',
  'fields' => [
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
    ['name' => 'logo', 'label' => 'Logo', 'type' => 'image'],
    ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0, 'help' => 'Lower shows first.'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => 1],
  ],
  'columns' => [['name' => 'logo', 'label' => 'Logo', 'type' => 'image'], ['name' => 'name', 'label' => 'Name'],
                ['name' => 'sort_order', 'label' => 'Order'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
