<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'banners', 'entity' => 'Banner', 'title' => 'Banners', 'active' => 'banners',
  'title_field' => 'title', 'search' => ['t.title', 't.page'], 'order' => 't.page, t.sort_order, t.id',
  'fields' => [
    ['name' => 'page', 'label' => 'Show on page', 'type' => 'select', 'required' => true, 'half' => true,
    ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'int' => true, 'half' => true, 'default' => 0],
      'options' => ['home' => 'Home', 'shop' => 'Shop', 'blog' => 'Blog', 'contact' => 'Contact']],
    ['name' => 'image', 'label' => 'Banner image', 'type' => 'image', 'required' => true],
    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'max' => 200],
    ['name' => 'subtitle', 'label' => 'Subtitle text', 'type' => 'text', 'max' => 300],
    ['name' => 'button_text', 'label' => 'Button text', 'type' => 'text', 'max' => 60, 'half' => true],
    ['name' => 'button_link', 'label' => 'Button link', 'type' => 'text', 'max' => 255, 'half' => true, 'pattern' => '~^(/|https?://)~i', 'pattern_msg' => 'Link must start with / or http(s)://', 'help' => 'e.g. /shop.php or https://example.com'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'half' => true, 'default' => 1],
  ],
  'columns' => [['name' => 'image', 'label' => 'Image', 'type' => 'image'], ['name' => 'page', 'label' => 'Page'],
    ['name' => 'title', 'label' => 'Title'], ['name' => 'button_text', 'label' => 'Button'],
    ['name' => 'is_active', 'label' => 'Active', 'type' => 'bool']],
]);
