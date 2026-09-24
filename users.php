<?php
declare(strict_types=1);
require __DIR__ . '/includes/actions.php';
require_role('ADMIN');
$users = query_all('SELECT id,username,role,is_active,created_at,updated_at FROM users ORDER BY is_active DESC, username ASC');
layout_start('Manajemen User', 'users.php');
?>
<section class="page-hero">
    <div>
        <p class="eyebrow">AKSES PENGGUNA</p>
        <h1>Manajemen user</h1>
        <p class="muted">Tambah, edit, aktifkan, atau nonaktifkan akun aplikasi.</p>
    </div>
</section>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">USER BARU</p>
            <h2>Tambah user</h2>
        </div>
    </div>
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_user">
        <label>Username<input name="username" maxlength="80" autocomplete="off" required></label>
        <label>Password<input type="password" name="password" minlength="8" autocomplete="new-password"
                required></label>
        <label>Role<select name="role">
                <option value="KASIR">Kasir</option>
                <option value="ADMIN">Admin</option>
            </select></label>
        <label>Status<select name="is_active">
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select></label>
        <div class="form-actions"><button class="button primary" type="submit">Tambah user</button></div>
    </form>
</section>
<section class="panel">
    <div class="panel-heading">
        <div>
            <p class="eyebrow">DAFTAR AKUN</p>
            <h2><?= count($users) ?> user terdaftar</h2>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $isSelf = (int) $user['id'] === (int) (current_user()['id'] ?? 0); ?>
                    <tr>
                        <td data-label="Username">
                            <strong><?= e($user['username']) ?></strong><?= $isSelf ? ' <span class="badge">Anda</span>' : '' ?>
                        </td>
                        <td data-label="Role"><span
                                class="badge <?= $user['role'] === 'ADMIN' ? 'success' : '' ?>"><?= e($user['role']) ?></span>
                        </td>
                        <td data-label="Status"><span
                                class="badge <?= (int) $user['is_active'] === 1 ? 'success' : 'warning' ?>"><?= (int) $user['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span>
                        </td>
                        <td data-label="Dibuat"><?= e(date('d/m/Y H:i', strtotime((string) $user['created_at']))) ?></td>
                        <td data-label="Aksi">
                            <details class="edit-box">
                                <summary>Edit</summary>
                                <form method="post" class="form-grid compact">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="save_user">
                                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <label>Username<input name="username" maxlength="80" value="<?= e($user['username']) ?>"
                                            autocomplete="off" required></label>
                                    <label>Password baru <small class="muted">Kosongkan jika tidak diubah</small><input
                                            type="password" name="password" minlength="8"
                                            autocomplete="new-password"></label>
                                    <label>Role<select name="role">
                                            <option value="KASIR" <?= $user['role'] === 'KASIR' ? 'selected' : '' ?>>Kasir
                                            </option>
                                            <option value="ADMIN" <?= $user['role'] === 'ADMIN' ? 'selected' : '' ?>>Admin
                                            </option>
                                        </select></label>
                                    <label>Status<select name="is_active" <?= $isSelf ? 'disabled' : '' ?>>
                                            <option value="1" <?= (int) $user['is_active'] === 1 ? 'selected' : '' ?>>Aktif
                                            </option>
                                            <option value="0" <?= (int) $user['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif
                                            </option>
                                        </select></label>
                                    <?php if ($isSelf): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>
                                    <div class="form-actions"><button class="button primary" type="submit">Simpan
                                            perubahan</button></div>
                                </form>
                            </details>
                            <?php if (!$isSelf && (int) $user['is_active'] === 1): ?>
                                <form method="post" class="inline-form user-delete-form" data-confirm="Nonaktifkan user ini?">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_user"><input
                                        type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                    <button class="button danger small" type="submit">Nonaktifkan</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php layout_end(); ?>