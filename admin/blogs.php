<?php
require __DIR__ . '/../includes/crud.php';
crud_run([
  'table' => 'blogs', 'entity' => 'Blog', 'title' => 'Blog Posts', 'active' => 'blogs',
  'title_field' => 'title', 'slug_from' => 'title', 'search' => ['t.title'],
  'before_save' => function (array $d, ?array $old) {
      if (!empty($d['is_published']) && empty($old['published_at'])) $d['published_at'] = date('Y-m-d H:i:s');
      return $d;
  },
  'fields' => [
    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 200],
    ['name' => 'image', 'label' => 'Cover image', 'type' => 'image'],
    ['name' => 'excerpt', 'label' => 'Short excerpt', 'type' => 'text', 'max' => 300],
    ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'rows' => 14, 'required' => true, 'min_len' => 20, 'help' => 'Plain text. Blank line = new paragraph.'],
    ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'default' => 1],
  ],
  'columns' => [['name' => 'image', 'label' => 'Cover', 'type' => 'image'], ['name' => 'title', 'label' => 'Title'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'bool']],
]);
