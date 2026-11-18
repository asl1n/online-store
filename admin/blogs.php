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
    ['name' => 'excerpt', 'label' => 'Short description', 'type' => 'text', 'max' => 200, 'help' => 'Shown on blog cards and used as the Google (meta) description. Aim for under 155 characters.'],
        ['name' => 'content', 'label' => 'Content', 'type' => 'richtext', 'required' => true, 'min_len' => 20, 'help' => 'Use the toolbar for headings, bold, links, lists, quotes, tables and images (drag an image in or use the image button).'],
    ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'default' => 1],
  ],
  'columns' => [['name' => 'image', 'label' => 'Cover', 'type' => 'image'], ['name' => 'title', 'label' => 'Title'],
                ['name' => 'is_published', 'label' => 'Published', 'type' => 'bool']],
]);
