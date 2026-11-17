<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'products', 'entity' => 'Product', 'title' => 'Products', 'active' => 'products',
  'title_field' => 'name', 'slug_from' => 'name', 'search' => ['t.name', 'b.name', 'c.name'],
  'extra_select' => ', b.name AS brand_name, c.name AS category_name',
  'extra_join' => 'LEFT JOIN brands b ON b.id=t.brand_id LEFT JOIN categories c ON c.id=t.category_id',
  'fields' => [
    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 200],
    ['name' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'options' => fn() => array_column(fetch_all('SELECT id,name FROM brands ORDER BY name'), 'name', 'id')],
    ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => fn() => array_column(fetch_all('SELECT id,name FROM categories ORDER BY name'), 'name', 'id')],
    ['name' => 'price', 'label' => 'Price (Rs.)', 'type' => 'number', 'required' => true],
    ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
    ['name' => 'short_description', 'label' => 'Short description', 'type' => 'text', 'max' => 300],
    ['name' => 'description', 'label' => 'Full description', 'type' => 'textarea', 'rows' => 8],
    ['name' => 'is_featured', 'label' => 'Featured on home', 'type' => 'checkbox'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => 1],
  ],
  'columns' => [['name' => 'image', 'label' => 'Image', 'type' => 'image'], ['name' => 'name', 'label' => 'Name'],
    ['name' => 'brand_name', 'label' => 'Brand'], ['name' => 'category_name', 'label' => 'Category'],
    ['name' => 'price', 'label' => 'Price', 'type' => 'money'], ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
