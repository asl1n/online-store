<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'products', 'entity' => 'Product', 'title' => 'Products', 'active' => 'products',
  'title_field' => 'name', 'slug_from' => 'name', 'search' => ['t.name', 'b.name', 'c.name'],
  'extra_select' => ', b.name AS brand_name, c.name AS category_name',
  'extra_join' => 'LEFT JOIN brands b ON b.id=t.brand_id LEFT JOIN categories c ON c.id=t.category_id',
  'fields' => [
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 200],
    ['name' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'half' => true, 'options' => fn() => array_column(fetch_all('SELECT id,name FROM brands ORDER BY name'), 'name', 'id')],
    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'half' => true, 'options' => fn() => array_column(fetch_all('SELECT id,name FROM categories ORDER BY name'), 'name', 'id')],
    ['name' => 'price', 'label' => 'Price (Rs.)', 'type' => 'number', 'required' => true, 'half' => true, 'min' => 0, 'help' => 'Numbers only, e.g. 68000'],
    ['name' => 'discount_percent', 'label' => 'Discount (%)', 'type' => 'number', 'half' => true, 'min' => 0, 'maxv' => 99, 'default' => 0, 'help' => 'Optional. 0 = no discount. Shoppers see the old price crossed out.', 'preview' => true],
    ['name' => 'is_featured', 'label' => 'Featured on home', 'type' => 'checkbox', 'half' => true],
    ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
    ['name' => 'short_description', 'label' => 'Short description', 'type' => 'text', 'max' => 300, 'help' => 'Also used as the Google (meta) description. Aim for under 155 characters.'],
    ['name' => 'description', 'label' => 'Full description', 'type' => 'richtext', 'help' => 'Format with headings, lists, tables and images.'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'half' => true, 'default' => 1],
  ],
  'columns' => [['name' => 'image', 'label' => 'Image', 'type' => 'image'], ['name' => 'name', 'label' => 'Name'],
    ['name' => 'brand_name', 'label' => 'Brand'], ['name' => 'category_name', 'label' => 'Category'],
    ['name' => 'price', 'label' => 'Price', 'type' => 'money'], ['name' => 'discount_percent', 'label' => 'Discount', 'type' => 'percent'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
