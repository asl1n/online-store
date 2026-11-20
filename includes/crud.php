<?php
require_once __DIR__ . '/admin_layout.php';
require_once __DIR__ . '/contact.php'; // clean_text()

function crud_switch_labels(array $f): array {
    $map = ['is_active' => ['Active', 'Inactive'], 'is_published' => ['Published', 'Draft'], 'is_featured' => ['Featured', 'Not featured']];
    return [$f['on'] ?? ($map[$f['name']][0] ?? 'Yes'), $f['off'] ?? ($map[$f['name']][1] ?? 'No')];
}
function crud_is_half(array $f): bool {
    if (isset($f['half'])) return (bool)$f['half'];
    if (!empty($f['full']) || in_array($f['type'], ['textarea', 'image', 'richtext', 'gallery'], true)) return false;
    return ($f['max'] ?? 100) < 150;
}
function crud_options(array $f): array { $o = $f['options'] ?? []; return is_callable($o) ? $o() : $o; }

/** Extra images attached to a row (e.g. product_images for a product). */
function gallery_rows(array $f, int $id): array {
    return $id ? fetch_all('SELECT id, image FROM `' . $f['table'] . '` WHERE `' . $f['fk'] . '`=? ORDER BY sort_order, id', [$id]) : [];
}
/** After the parent row is saved: delete ticked images, store newly uploaded ones. */
function gallery_sync(array $c, int $id): void {
    foreach ($c['fields'] as $f) {
        if ($f['type'] !== 'gallery') continue;
        $n = $f['name']; $drop = array_map('intval', (array)($_POST[$n . '_remove'] ?? []));
        foreach (gallery_rows($f, $id) as $g) if (in_array((int)$g['id'], $drop, true)) { delete_upload($g['image']); db_delete($f['table'], (int)$g['id']); }
        $next = (int)fetch_val('SELECT COALESCE(MAX(sort_order),0) FROM `' . $f['table'] . '` WHERE `' . $f['fk'] . '`=?', [$id]) + 1;
        foreach (uploaded_files($n) as $file) {
            try { db_insert($f['table'], [$f['fk'] => $id, 'image' => save_uploaded_image($file, 'gallery'), 'sort_order' => $next++]); } catch (Throwable $ex) { /* already validated */ }
        }
    }
}

function crud_values(array $c, ?array $row): array {
    $v = [];
    foreach ($c['fields'] as $f) $v[$f['name']] = $row[$f['name']] ?? ($f['default'] ?? ($f['type'] === 'checkbox' ? 0 : ''));
    return $v;
}

/** Validate + collect POST. Returns [data, errors]. */
function crud_collect(array $c, ?array $existing, int $id): array {
    $data = []; $err = []; $table = $c['table'];
    foreach ($c['fields'] as $f) {
        $n = $f['name']; $label = $f['label']; $req = !empty($f['required']);
        switch ($f['type']) {
            case 'checkbox': $data[$n] = isset($_POST[$n]) ? 1 : 0; break;
            case 'image':
                try {
                    $p = upload_image($n, $table);
                    if ($p) $data[$n] = $p;
                    elseif (!empty($_POST['remove_' . $n]) && !$req) $data[$n] = null;
                    elseif ($req && empty($existing[$n])) $err[$n] = "$label is required. Please choose an image.";
                } catch (Throwable $ex) { $err[$n] = $ex->getMessage(); }
                break;
            case 'gallery':
                $new = uploaded_files($n); if (!$new) break;
                $drop = array_map('intval', (array)($_POST[$n . '_remove'] ?? []));
                $keep = count(array_filter(gallery_rows($f, $id), fn($g) => !in_array((int)$g['id'], $drop, true)));
                $max = $f['max'] ?? 10;
                if ($keep + count($new) > $max) { $err[$n] = "A product can have at most $max gallery images (you have $keep and tried to add " . count($new) . ')'; break; }
                try { foreach ($new as $file) check_uploaded_image($file); } catch (Throwable $ex) { $err[$n] = $ex->getMessage(); }
                break;
            case 'number':
                $s = trim((string)($_POST[$n] ?? ''));
                if ($s === '') { if ($req) $err[$n] = "$label is required."; else $data[$n] = $f['default'] ?? 0; break; }
                if (!empty($f['int'])) { if (!preg_match('/^\d+$/', $s)) { $err[$n] = "$label must be a whole number (0 or more)."; break; } }
                elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $s)) { $err[$n] = "$label must be a valid amount (numbers only, up to 2 decimals)."; break; }
                if ($s > ($f['maxv'] ?? 999999999)) { $err[$n] = "$label is too large."; break; }
                if ($s < ($f['min'] ?? 0)) { $err[$n] = "$label must be at least " . ($f['min'] ?? 0) . '.'; break; }
                $data[$n] = $s; break;
            case 'select':
                $s = (string)($_POST[$n] ?? ''); $opts = crud_options($f);
                if ($s === '') { if ($req) $err[$n] = "Please choose a $label."; else $data[$n] = null; }
                elseif (!array_key_exists($s, $opts)) $err[$n] = "That $label is not valid. Please choose from the list.";
                else $data[$n] = $s;
                break;
            case 'richtext':
                $raw = (string)($_POST[$n] ?? '');
                if (strlen($raw) > 400000) { $err[$n] = "$label is too large."; break; }
                if ($raw !== '' && !looks_like_html($raw)) $raw = plain_to_html($raw);      // editor failed to load -> plain text
                $clean = html_clean($raw);
                $plain = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $hasImg = stripos($clean, '<img') !== false;
                if ($plain === '' && !$hasImg) { if ($req) $err[$n] = "$label is required."; else $data[$n] = null; break; }
                if (!empty($f['min_len']) && mb_strlen($plain) < $f['min_len'] && !$hasImg) { $err[$n] = "$label must be at least {$f['min_len']} characters."; break; }
                $data[$n] = $clean; break;
            default: // text, textarea
                $s = clean_text((string)($_POST[$n] ?? ''), $f['type'] === 'textarea');
                $max = $f['max'] ?? ($f['type'] === 'textarea' ? 20000 : 255);
                if ($req && $s === '') $err[$n] = "$label is required.";
                elseif (mb_strlen($s) > $max) $err[$n] = "$label is too long (max $max characters, you typed " . mb_strlen($s) . ').';
                elseif (!empty($f['min_len']) && $s !== '' && mb_strlen($s) < $f['min_len']) $err[$n] = "$label must be at least {$f['min_len']} characters.";
                elseif ($s !== '' && !empty($f['pattern']) && !preg_match($f['pattern'], $s)) $err[$n] = $f['pattern_msg'] ?? "$label is not in a valid format.";
                elseif ($s !== '' && !empty($f['unique']) && fetch_val("SELECT COUNT(*) FROM `$table` WHERE LOWER(`$n`)=LOWER(?) AND id<>?", [$s, $id]))
                    $err[$n] = "A {$c['entity']} named “{$s}” already exists. Please use a different $label.";
                else $data[$n] = $s === '' ? null : $s;
        }
    }
    return [$data, $err];
}

function crud_run(array $c): void {
    require_admin();
    $table = $c['table']; $act = $_GET['action'] ?? 'list'; $id = (int)($_GET['id'] ?? 0);
    $self = strtok($_SERVER['REQUEST_URI'], '?');
    $errors = []; $posted = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (($_POST['_do'] ?? '') === 'toggle') { // quick on/off switch from the list
            $fname = (string)($_POST['field'] ?? ''); $ret = preg_replace('/[^A-Za-z0-9=&%_.+\-]/', '', (string)($_POST['ret'] ?? ''));
            $fdef = null; foreach ($c['fields'] as $f) if ($f['name'] === $fname && $f['type'] === 'checkbox') $fdef = $f;
            $row = $fdef ? fetch_one("SELECT * FROM `$table` WHERE id=?", [(int)($_POST['id'] ?? 0)]) : null;
            if ($row) {
                $nv = $row[$fname] ? 0 : 1; $tt = (string)($row[$c['title_field']] ?? '');
                db_update($table, (int)$row['id'], [$fname => $nv] + audit_update());
                log_activity('update', $c['entity'], (int)$row['id'], $tt . ': ' . $fdef['label'] . ' set to ' . ($nv ? 'on' : 'off'));
                flash('ok', '“' . $tt . '”: ' . $fdef['label'] . ' turned ' . ($nv ? 'on' : 'off') . '.');
            }
            go($self . ($ret !== '' ? '?' . $ret : ''));
        }
        if (($_POST['_do'] ?? '') === 'delete') {
            $row = fetch_one("SELECT * FROM `$table` WHERE id=?", [(int)($_POST['id'] ?? 0)]);
            if ($row) {
                foreach ($c['fields'] as $f) {
                    if ($f['type'] === 'image') delete_upload($row[$f['name']]);
                    if ($f['type'] === 'gallery') foreach (gallery_rows($f, (int)$row['id']) as $g) delete_upload($g['image']);
                }
                db_delete($table, (int)$row['id']);
                log_activity('delete', $c['entity'], (int)$row['id'], (string)($row[$c['title_field']] ?? ''));
                flash('ok', $c['entity'] . ' deleted.');
            } else flash('err', 'That ' . strtolower($c['entity']) . ' no longer exists.');
            go($self);
        }
        $existing = $id ? fetch_one("SELECT * FROM `$table` WHERE id=?", [$id]) : null;
        if ($id && !$existing) { flash('err', 'That ' . strtolower($c['entity']) . ' no longer exists.'); go($self); }
        [$data, $errors] = crud_collect($c, $existing, $id);
        if (!$errors) {
            try {
                if (!empty($c['slug_from'])) $data['slug'] = unique_slug($table, (string)$data[$c['slug_from']], $id);
                if (!empty($c['before_save'])) $data = $c['before_save']($data, $existing);
                $title = (string)($data[$c['title_field']] ?? '');
                if ($existing) {
                    db_update($table, $id, $data + audit_update());
                    foreach ($c['fields'] as $f) {
                        $n = $f['name'];
                        if ($f['type'] === 'image' && array_key_exists($n, $data) && $existing[$n] && $data[$n] !== $existing[$n]) delete_upload($existing[$n]);
                    }
                    gallery_sync($c, $id);
                    log_activity('update', $c['entity'], $id, $title); flash('ok', $c['entity'] . ' updated successfully.');
                } else {
                    $nid = db_insert($table, $data + audit_create());
                    gallery_sync($c, $nid);
                    log_activity('create', $c['entity'], $nid, $title); flash('ok', $c['entity'] . ' created successfully.');
                }
                go($self);
            } catch (PDOException $ex) {
                $errors['_form'] = APP_DEBUG ? 'Database error: ' . $ex->getMessage() : 'Sorry, something went wrong while saving. Please try again.';
            }
        }
        $posted = $_POST; $act = $id ? 'edit' : 'new';
    }

    $addBtn = ui_create_btn($self . '?action=new', $c['entity']);

    // ---------- FORM ----------
    if ($act === 'new' || $act === 'edit') {
        $row = $act === 'edit' ? fetch_one("SELECT * FROM `$table` WHERE id=?", [$id]) : null;
        if ($act === 'edit' && !$row) { flash('err', 'That ' . strtolower($c['entity']) . ' was not found.'); go($self); }
        $vals = $posted ? array_merge(crud_values($c, $row), $posted) : crud_values($c, $row);
        if ($posted) foreach ($c['fields'] as $f) if ($f['type'] === 'checkbox') $vals[$f['name']] = isset($posted[$f['name']]) ? 1 : 0;
        foreach ($c['fields'] as $f) if ($f['type'] === 'richtext') $GLOBALS['need_editor'] = true;
        admin_header(($act === 'edit' ? 'Edit ' : 'Add ') . $c['entity'], $c['active'], '<a class="btn ghost" href="' . e($self) . '">' . ui_icon_back() . 'Back to list</a>');
        echo ui_errors_summary($errors); ?>
        <form method="post" enctype="multipart/form-data" class="card formcard"><?= csrf_field() ?><div class="form-grid">
        <?php foreach ($c['fields'] as $f):
            $n = $f['name']; $o = ['error' => $errors[$n] ?? null, 'help' => $f['help'] ?? null, 'half' => crud_is_half($f), 'req' => !empty($f['required']), 'for' => $n];
            $req = !empty($f['required']) ? ['required' => true] : [];
            switch ($f['type']) {
                case 'richtext':
                    $rv = (string)$vals[$n]; if ($rv !== '' && !looks_like_html($rv)) $rv = plain_to_html($rv);
                    $ctl = ui_textarea($n, $rv, ['rows' => 12, 'data-editor' => true, 'data-upload' => BASE_URL . '/admin/editor-upload', 'data-csrf' => csrf_token()]); break;
                case 'textarea': $ctl = ui_textarea($n, $vals[$n], ['rows' => $f['rows'] ?? 5, 'maxlength' => $f['max'] ?? 20000] + $req); break;
                case 'select':
                    $ctl = '<select id="' . e($n) . '" name="' . e($n) . '"><option value="">— Select —</option>';
                    foreach (crud_options($f) as $k => $lab) $ctl .= '<option value="' . e($k) . '"' . ((string)$vals[$n] === (string)$k ? ' selected' : '') . '>' . e($lab) . '</option>';
                    $ctl .= '</select>'; break;
                case 'checkbox': [$on, $off] = crud_switch_labels($f);
                    $ctl = '<label class="switch"><input type="checkbox" id="' . e($n) . '" name="' . e($n) . '"' . ($vals[$n] ? ' checked' : '') . '><span class="track"></span><span class="txt" data-on="' . e($on) . '" data-off="' . e($off) . '"></span></label>'; break;
                case 'image':
                    $ctl = (!empty($row[$n]) ? '<img class="thumb" src="' . e(upload_url($row[$n])) . '" alt="">' : '')
                         . '<input type="file" id="' . e($n) . '" name="' . e($n) . '" accept="image/jpeg,image/png,image/webp,image/gif">'
                         . (!empty($row[$n]) && empty($f['required']) ? '<label class="chk"><input type="checkbox" name="remove_' . e($n) . '"> Remove current image</label>' : '');
                    break;
                case 'gallery':
                    $ctl = '';
                    $grows = gallery_rows($f, (int)$id);
                    if ($grows) {
                        $ctl .= '<div class="gallery-admin">';
                        foreach ($grows as $g) $ctl .= '<label class="gitem"><img src="' . e(upload_url($g['image'])) . '" alt=""><span class="grm"><input type="checkbox" name="' . e($n) . '_remove[]" value="' . (int)$g['id'] . '"> Remove</span></label>';
                        $ctl .= '</div>';
                    }
                    $ctl .= '<input type="file" id="' . e($n) . '" name="' . e($n) . '[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-gallery-input><div class="gallery-admin previews" data-gallery-previews></div>';
                    break;
                case 'number': $ctl = ui_input($n, $vals[$n], ['type' => 'number', 'step' => !empty($f['int']) ? '1' : '0.01', 'min' => $f['min'] ?? 0, 'max' => $f['maxv'] ?? false, 'inputmode' => 'decimal'] + $req); break;
                default: $ctl = ui_input($n, $vals[$n], ['type' => 'text', 'maxlength' => $f['max'] ?? 255] + $req);
            }
            if (!empty($f['preview'])) $ctl .= '<div class="pricepreview" data-price-preview aria-live="polite"></div>';
            echo ui_field($f['label'], $ctl, $o);
        endforeach; ?></div>
        <div class="form-actions"><button class="btn"><?= $act === 'edit' ? 'Save changes' : 'Create ' . e(strtolower($c['entity'])) ?></button><a class="btn ghost" href="<?= e($self) ?>">Cancel</a></div></form>
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
    $rows = fetch_all('SELECT t.*, ca.name AS created_name, ua.name AS updated_name ' . ($c['extra_select'] ?? '') . " $from $where ORDER BY " . ($c['order'] ?? 't.id DESC')
        . ' LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per), $params);
    admin_header($c['title'], $c['active'], $addBtn, !empty($c['search']) ? ui_search($c['title'], $s, $self) : ''); ?>
    <div class="card"><div class="tablewrap"><table class="table"><thead><tr>
      <?php foreach ($c['columns'] as $col): ?><th><?= e($col['label']) ?></th><?php endforeach; ?><th>Added / edited</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <?php foreach ($c['columns'] as $col): $v = $r[$col['name']] ?? ''; $t = $col['type'] ?? ''; ?><td>
        <?php if ($t === 'image'): ?><?= $v ? '<img class="thumb sm" src="' . e(upload_url($v)) . '" alt="">' : '<span class="count">—</span>' ?>
        <?php elseif ($t === 'bool'): [$on, $off] = crud_switch_labels(['name' => $col['name']]); ?><form method="post" class="inline-toggle"><?= csrf_field() ?><input type="hidden" name="_do" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="field" value="<?= e($col['name']) ?>"><input type="hidden" name="ret" value="<?= e((string)($_SERVER['QUERY_STRING'] ?? '')) ?>"><button class="switch-btn <?= $v ? 'on' : '' ?>" title="<?= e($v ? $on : $off) ?> – click to change" aria-label="<?= e($v ? $on : $off) ?>"><span class="knob"></span></button><span class="swtxt <?= $v ? 'on' : '' ?>"><?= e($v ? $on : $off) ?></span></form>
        <?php elseif ($t === 'money'): ?>Rs. <?= e(number_format((float)$v)) ?>
        <?php elseif ($t === 'percent'): ?><?= (float)$v > 0 ? '<span class="pill off">' . e(rtrim(rtrim(number_format((float)$v, 2), '0'), '.')) . '% off</span>' : '<span class="count">—</span>' ?>
        <?php else: ?><?= $v === '' || $v === null ? '<span class="count">—</span>' : e($v) ?><?php endif; ?></td><?php endforeach; ?>
      <td class="audit"><small>Added by <b><?= e($r['created_name'] ?? 'unknown') ?></b> · <?= e(substr((string)$r['created_at'], 0, 10)) ?></small>
        <small>Edited by <b><?= e($r['updated_name'] ?? 'unknown') ?></b> · <?= e(substr((string)$r['updated_at'], 0, 10)) ?></small></td>
      <td class="act"><a class="btn sm ghost" href="<?= e($self) ?>?action=edit&amp;id=<?= (int)$r['id'] ?>">Edit</a>
        <form method="post" data-confirm-title="Delete <?= e(strtolower($c['entity'])) ?>?" data-confirm="Delete “<?= e((string)($r[$c['title_field']] ?? '')) ?>”? <?= e($c['delete_note'] ?? 'This cannot be undone.') ?>"><?= csrf_field() ?><input type="hidden" name="_do" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm danger-ghost">Delete</button></form></td>
    </tr><?php endforeach; if (!$rows): ?><tr><td colspan="<?= count($c['columns']) + 2 ?>" class="empty"><?= $s !== '' ? 'No results for your search.' : 'Nothing here yet. Click “Create” to add the first one.' ?></td></tr><?php endif; ?>
    </tbody></table></div><?= ui_tablefoot($page, $per, $total, $pages, ['item', 'items'], $s) ?></div>
    <?php admin_footer();
}
