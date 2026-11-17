<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'categories', 'entity' => 'Category', 'title' => 'Product Categories', 'active' => 'categories',
  'title_field' => 'name', 'slug_from' => 'name', 'search' => ['t.name'], 'order' => 't.sort_order, t.name',
  'fields' => [
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100],
    ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'default' => 0],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => 1],
  ],
  'columns' => [['name' => 'name', 'label' => 'Name'], ['name' => 'sort_order', 'label' => 'Order'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
