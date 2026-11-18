<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'categories', 'entity' => 'Category', 'title' => 'Product Categories', 'active' => 'categories',
  'title_field' => 'name', 'delete_note' => 'Products in this category will be left without a category.', 'slug_from' => 'name', 'search' => ['t.name'], 'order' => 't.sort_order, t.name',
  'fields' => [
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 100, 'unique' => true],
    ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'int' => true, 'half' => true, 'default' => 0, 'help' => 'Lower numbers show first.'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'half' => true, 'default' => 1],
  ],
  'columns' => [['name' => 'name', 'label' => 'Name'], ['name' => 'sort_order', 'label' => 'Order'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
