<?php
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/contact.php'; // clean_text()

function crud_values(array $c, ?array $row): array {
    $v = [];
    foreach ($c['fields'] as $f) {
        $n = $f['name'];
        $v[$n] = $row[$n] ?? ($f['default'] ?? ($f['type'] === 'checkbox' ? 0 : ''));
    }
    return $v;
}

function crud_options(array $f): array {
    $o = $f['options'] ?? [];
    return is_callable($o) ? $o() : $o;
}

/** Validate + collect POST into $data. Returns [data, errors]. */
function crud_collect(array $c, ?array $existing): array {
    $data = []; $err = [];
    foreach ($c['fields'] as $f) {
        $n = $f['name']; $label = $f['label']; $req = !empty($f['required']);
        switch ($f['type']) {
            case 'checkbox': $data[$n] = isset($_POST[$n]) ? 1 : 0; break;
            case 'image':
                try {
                    $p = upload_image($n, $c['table']);
                    if ($p) $data[$n] = $p;
                    elseif ($req && empty($existing[$n])) $err[$n] = "$label is required.";
                } catch (Throwable $ex) { $err[$n] = $ex->getMessage(); }
                break;
            case 'number':
                $s = trim((string)($_POST[$n] ?? ''));
                if ($s === '' && !$req) { $data[$n] = $f['default'] ?? 0; break; }
                if (!is_numeric($s) || $s < 0 || $s > 999999999) $err[$n] = "$label must be a valid number.";
                else $data[$n] = $s;
                break;
            case 'select':
                $s = (string)($_POST[$n] ?? ''); $opts = crud_options($f);
                if ($s === '') { if ($req) $err[$n] = "$label is required."; else $data[$n] = null; }
                elseif (!array_key_exists($s, $opts)) $err[$n] = "Invalid $label.";
                else $data[$n] = $s;
                break;
            default: // text, textarea
                $s = clean_text((string)($_POST[$n] ?? ''), $f['type'] === 'textarea');
                $max = $f['max'] ?? ($f['type'] === 'textarea' ? 20000 : 255);
                if ($req && $s === '') $err[$n] = "$label is required.";
                elseif (mb_strlen($s) > $max) $err[$n] = "$label is too long (max $max).";
                elseif ($s !== '' && !empty($f['pattern']) && !preg_match($f['pattern'], $s)) $err[$n] = $f['pattern_msg'] ?? "Invalid $label.";
                else $data[$n] = $s === '' ? null : $s;
        }
    }
    return [$data, $err];
}

function crud_run(array $c): void {
    $a = require_admin();
    $table = $c['table']; $act = $_GET['action'] ?? 'list'; $id = (int)($_GET['id'] ?? 0);
    $self = strtok($_SERVER['REQUEST_URI'], '?');
    $errors = []; $posted = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (($_POST['_do'] ?? '') === 'delete') {
            $row = fetch_one("SELECT * FROM `$table` WHERE id=?", [(int)$_POST['id']]);
            if ($row) {
                foreach ($c['fields'] as $f) if ($f['type'] === 'image') delete_upload($row[$f['name']]);
                db_delete($table, (int)$row['id']);
                log_activity('delete', $c['entity'], (int)$row['id'], $row[$c['title_field']] ?? '');
                flash('ok', $c['entity'] . ' deleted.');
            }
            go($self);
        }
        // save
        $existing = $id ? fetch_one("SELECT * FROM `$table` WHERE id=?", [$id]) : null;
        if ($id && !$existing) go($self);
        [$data, $errors] = crud_collect($c, $existing);
        if (!$errors) {
            if (!empty($c['slug_from'])) $data['slug'] = unique_slug($table, (string)$data[$c['slug_from']], $id);
            if (!empty($c['before_save'])) $data = $c['before_save']($data, $existing);
            if ($existing) {
                db_update($table, $id, $data + audit_update());
                foreach ($c['fields'] as $f) // replaced image -> remove old file
                    if ($f['type'] === 'image' && !empty($data[$f['name']]) && $existing[$f['name']]) delete_upload($existing[$f['name']]);
                log_activity('update', $c['entity'], $id, (string)($data[$c['title_field']] ?? ''));
                flash('ok', $c['entity'] . ' updated.');
            } else {
                $nid = db_insert($table, $data + audit_create());
                log_activity('create', $c['entity'], $nid, (string)($data[$c['title_field']] ?? ''));
                flash('ok', $c['entity'] . ' created.');
            }
            go($self);
        }
        $posted = $_POST; $act = $id ? 'edit' : 'new';
    }

    admin_header($c['title'], $c['active']);

    // ---------- FORM ----------
    if ($act === 'new' || $act === 'edit') {
        $row = $act === 'edit' ? fetch_one("SELECT * FROM `$table` WHERE id=?", [$id]) : null;
        if ($act === 'edit' && !$row) { echo '<p>Not found.</p>'; admin_footer(); return; }
        $vals = $posted ? array_merge(crud_values($c, $row), $posted) : crud_values($c, $row);
        ?>
        <form method="post" enctype="multipart/form-data" class="card form"><?= csrf_field() ?>
        <?php foreach ($c['fields'] as $f): $n = $f['name']; ?>
          <div class="row"><label for="<?= e($n) ?>"><?= e($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?></label>
          <?php if ($f['type'] === 'textarea'): ?>
            <textarea id="<?= e($n) ?>" name="<?= e($n) ?>" rows="<?= (int)($f['rows'] ?? 5) ?>"><?= e($vals[$n]) ?></textarea>
          <?php elseif ($f['type'] === 'select'): ?>
            <select id="<?= e($n) ?>" name="<?= e($n) ?>"><option value="">— none —</option>
            <?php foreach (crud_options($f) as $k => $lab): ?><option value="<?= e($k) ?>" <?= (string)$vals[$n] === (string)$k ? 'selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?></select>
          <?php elseif ($f['type'] === 'checkbox'): ?>
            <label class="chk"><input type="checkbox" id="<?= e($n) ?>" name="<?= e($n) ?>" <?= $vals[$n] ? 'checked' : '' ?>> Yes</label>
          <?php elseif ($f['type'] === 'image'): ?>
            <?php if (!empty($row[$n])): ?><img class="thumb" src="<?= e(upload_url($row[$n])) ?>" alt=""><?php endif; ?>
            <input type="file" id="<?= e($n) ?>" name="<?= e($n) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
          <?php else: ?>
            <input id="<?= e($n) ?>" name="<?= e($n) ?>" type="<?= $f['type'] === 'number' ? 'number' : 'text' ?>" <?= $f['type'] === 'number' ? 'step="0.01" min="0"' : '' ?> value="<?= e($vals[$n]) ?>">
          <?php endif; ?>
          <?php if (!empty($f['help'])): ?><small><?= e($f['help']) ?></small><?php endif; ?>
          <?php if (isset($errors[$n])): ?><div class="err"><?= e($errors[$n]) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
        <button class="btn">Save</button> <a class="btn ghost" href="<?= e($self) ?>">Cancel</a></form>
        <?php admin_footer(); return;
    }

    // ---------- LIST ----------
    $per = 20; $page = max(1, (int)($_GET['page'] ?? 1)); $s = trim((string)($_GET['s'] ?? ''));
    $from = "FROM `$table` t LEFT JOIN admins ca ON ca.id=t.created_by LEFT JOIN admins ua ON ua.id=t.updated_by " . ($c['extra_join'] ?? '');
    $where = ''; $params = [];
    if ($s !== '' && !empty($c['search'])) {
        $where = 'WHERE ' . implode(' OR ', array_map(fn($col) => "$col LIKE ?", $c['search']));
        $params = array_fill(0, count($c['search']), '%' . $s . '%');
    }
    $total = (int) fetch_val("SELECT COUNT(*) $from $where", $params);
    $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
    $order = $c['order'] ?? 't.id DESC'; // from code only
    $rows = fetch_all("SELECT t.*, ca.name AS created_name, ua.name AS updated_name " . ($c['extra_select'] ?? '') .
        " $from $where ORDER BY $order LIMIT " . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per), $params);
    ?>
    <div class="bar"><a class="btn" href="<?= e($self) ?>?action=new">+ Add <?= e($c['entity']) ?></a>
      <?php if (!empty($c['search'])): ?><form method="get"><input name="s" placeholder="Search…" value="<?= e($s) ?>"><button class="btn ghost">Search</button></form><?php endif; ?></div>
    <div class="card"><table><thead><tr>
      <?php foreach ($c['columns'] as $col): ?><th><?= e($col['label']) ?></th><?php endforeach; ?><th>Added by</th><th>Last edit</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <?php foreach ($c['columns'] as $col): $v = $r[$col['name']] ?? ''; ?><td>
        <?php if (($col['type'] ?? '') === 'image'): ?><?= $v ? '<img class="thumb sm" src="' . e(upload_url($v)) . '" alt="">' : '' ?>
        <?php elseif (($col['type'] ?? '') === 'bool'): ?><span class="pill <?= $v ? 'on' : '' ?>"><?= $v ? 'Yes' : 'No' ?></span>
        <?php elseif (($col['type'] ?? '') === 'money'): ?>Rs. <?= e(number_format((float)$v)) ?>
        <?php else: ?><?= e($v) ?><?php endif; ?></td><?php endforeach; ?>
      <td><?= e($r['created_name'] ?? '—') ?><br><small><?= e(substr((string)$r['created_at'], 0, 16)) ?></small></td>
      <td><?= e($r['updated_name'] ?? '—') ?><br><small><?= e(substr((string)$r['updated_at'], 0, 16)) ?></small></td>
      <td class="act"><a href="<?= e($self) ?>?action=edit&id=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post" data-confirm="Delete this <?= e($c['entity']) ?>?"><?= csrf_field() ?><input type="hidden" name="_do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="link danger">Delete</button></form></td>
    </tr><?php endforeach; if (!$rows): ?><tr><td colspan="<?= count($c['columns']) + 3 ?>">Nothing here yet.</td></tr><?php endif; ?>
    </tbody></table></div><?= pager($page, $pages) ?>
    <?php admin_footer();
}
