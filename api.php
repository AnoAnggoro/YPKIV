<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

function api_input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function api_response(bool $success, string $message, array $data = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function api_require_auth(): void
{
    if (!api_current_user()) {
        api_response(false, 'Silakan login terlebih dahulu.', [], 401);
    }
}

function api_require_admin(): void
{
    api_require_auth();
    if ((api_current_user()['role'] ?? '') !== 'admin') {
        api_response(false, 'Akses ditolak. Fitur ini hanya untuk admin.', [], 403);
    }
}

function api_user_payload(array $user): array
{
    return [
        'id_user' => (int) $user['id_user'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
}

function api_total_bobot(PDO $pdo): float
{
    return (float) $pdo->query('SELECT COALESCE(SUM(bobot), 0) AS total_bobot FROM kriteria')->fetchColumn();
}

function api_bobot_valid(PDO $pdo): bool
{
    return abs(api_total_bobot($pdo) - 1.0) < 0.0001;
}

function api_fetch_kriteria(PDO $pdo): array
{
    return $pdo->query('SELECT id_kriteria, kode, nama_kriteria, sifat, bobot FROM kriteria ORDER BY id_kriteria ASC')->fetchAll();
}

function api_fetch_guru(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT g.id_guru, g.nama_guru, n.id_kriteria, n.nilai
        FROM guru g
        LEFT JOIN nilai n ON n.id_guru = g.id_guru
        ORDER BY g.id_guru ASC, n.id_kriteria ASC
    ');

    $grouped = [];
    while ($row = $stmt->fetch()) {
        $idGuru = (int) $row['id_guru'];
        if (!isset($grouped[$idGuru])) {
            $grouped[$idGuru] = [
                'id_guru' => $idGuru,
                'nama_guru' => $row['nama_guru'],
                'nilai' => new stdClass(),
            ];
        }

        if ($row['id_kriteria'] !== null) {
            $grouped[$idGuru]['nilai']->{(string) $row['id_kriteria']} = (float) $row['nilai'];
        }
    }

    return array_values($grouped);
}

function api_calculate_saw(PDO $pdo): array
{
    $kriteria = api_fetch_kriteria($pdo);
    $guru = api_fetch_guru($pdo);

    if (!$kriteria || !$guru) {
        return [
            'kriteria' => $kriteria,
            'hasil' => [],
        ];
    }

    $rawMatrix = [];
    foreach ($guru as $rowGuru) {
        $idGuru = (int) $rowGuru['id_guru'];
        foreach ($kriteria as $rowKriteria) {
            $idKriteria = (string) $rowKriteria['id_kriteria'];
            $rawMatrix[$idGuru][$idKriteria] = (float) ($rowGuru['nilai']->{$idKriteria} ?? 0);
        }
    }

    $normalisedMatrix = [];
    foreach ($kriteria as $rowKriteria) {
        $idKriteria = (string) $rowKriteria['id_kriteria'];
        $columnValues = array_column($rawMatrix, $idKriteria);
        $max = $columnValues ? max($columnValues) : 0;
        $min = $columnValues ? min($columnValues) : 0;

        foreach ($guru as $rowGuru) {
            $idGuru = (int) $rowGuru['id_guru'];
            $nilai = (float) ($rawMatrix[$idGuru][$idKriteria] ?? 0);

            if ($rowKriteria['sifat'] === 'Cost') {
                if ($nilai <= 0.0 && $min <= 0.0) {
                    $normalised = 1.0;
                } elseif ($nilai <= 0.0) {
                    $normalised = 0.0;
                } else {
                    $normalised = $min > 0 ? $min / $nilai : 0.0;
                }
            } else {
                $normalised = $max > 0 ? $nilai / $max : 0.0;
            }

            $normalisedMatrix[$idGuru][$idKriteria] = $normalised;
        }
    }

    $hasil = [];
    foreach ($guru as $rowGuru) {
        $idGuru = (int) $rowGuru['id_guru'];
        $preferensi = 0.0;
        $detail = [];

        foreach ($kriteria as $rowKriteria) {
            $idKriteria = (string) $rowKriteria['id_kriteria'];
            $bobot = (float) $rowKriteria['bobot'];
            $normalised = (float) ($normalisedMatrix[$idGuru][$idKriteria] ?? 0);
            $kontribusi = $bobot * $normalised;
            $preferensi += $kontribusi;

            $detail[] = [
                'id_kriteria' => (int) $rowKriteria['id_kriteria'],
                'kode' => $rowKriteria['kode'],
                'nama_kriteria' => $rowKriteria['nama_kriteria'],
                'sifat' => $rowKriteria['sifat'],
                'bobot' => (float) $rowKriteria['bobot'],
                'nilai_mentah' => (float) ($rawMatrix[$idGuru][$idKriteria] ?? 0),
                'nilai_normalisasi' => $normalised,
                'kontribusi' => $kontribusi,
            ];
        }

        $hasil[] = [
            'id_guru' => $idGuru,
            'nama_guru' => $rowGuru['nama_guru'],
            'preferensi' => $preferensi,
            'detail' => $detail,
        ];
    }

    usort($hasil, static fn (array $a, array $b): int => $b['preferensi'] <=> $a['preferensi']);

    foreach ($hasil as $index => $row) {
        $hasil[$index]['ranking'] = $index + 1;
    }

    return [
        'kriteria' => $kriteria,
        'hasil' => $hasil,
    ];
}

try {
    $action = (string) api_input('action', 'bootstrap');

    if ($action === 'me') {
        $user = api_current_user();
        api_response(true, 'OK', [
            'authenticated' => (bool) $user,
            'user' => $user ? api_user_payload($user) : null,
        ]);
    }

    if ($action === 'login') {
        $username = trim((string) api_input('username', ''));
        $password = (string) api_input('password', '');

        if ($username === '' || $password === '') {
            api_response(false, 'Username dan password wajib diisi.', [], 422);
        }

        $stmt = $pdo->prepare('SELECT id_user, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            api_response(false, 'Username atau password salah.', [], 401);
        }

        session_regenerate_id(true);
        $_SESSION['auth_user'] = api_user_payload($user);

        api_response(true, 'Login berhasil.', ['user' => $_SESSION['auth_user']]);
    }

    if ($action === 'register') {
        $username = trim((string) api_input('username', ''));
        $password = (string) api_input('password', '');
        $confirmPassword = (string) api_input('confirm_password', '');

        if ($username === '' || $password === '' || $confirmPassword === '') {
            api_response(false, 'Username, password, dan konfirmasi password wajib diisi.', [], 422);
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            api_response(false, 'Username harus 3-50 karakter.', [], 422);
        }

        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            api_response(false, 'Username hanya boleh huruf, angka, titik, underscore, dan tanda minus.', [], 422);
        }

        if (strlen($password) < 6) {
            api_response(false, 'Password minimal 6 karakter.', [], 422);
        }

        if ($password !== $confirmPassword) {
            api_response(false, 'Konfirmasi password tidak sama.', [], 422);
        }

        $stmt = $pdo->prepare('SELECT id_user FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            api_response(false, 'Username sudah digunakan.', [], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => $passwordHash,
            ':role' => 'viewer',
        ]);

        $userId = (int) $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id_user, username, role FROM users WHERE id_user = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        api_response(true, 'Akun berhasil dibuat. Silakan login.', [
            'user' => $user ? api_user_payload($user) : null,
        ]);
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        api_response(true, 'Logout berhasil.');
    }

    api_require_auth();

    if ($action === 'bootstrap') {
        $summary = api_calculate_saw($pdo);
        api_response(true, 'Data berhasil dimuat', [
            'user' => api_user_payload(api_current_user()),
            'kriteria' => api_fetch_kriteria($pdo),
            'guru' => api_fetch_guru($pdo),
            'hasil' => $summary['hasil'],
            'total_bobot' => api_total_bobot($pdo),
            'bobot_valid' => api_bobot_valid($pdo),
            'role' => api_current_user()['role'] ?? 'viewer',
        ]);
    }

    if ($action === 'save_kriteria') {
        api_require_admin();
        $id = api_input('id_kriteria');
        $kode = trim((string) api_input('kode', ''));
        $nama = trim((string) api_input('nama_kriteria', ''));
        $sifat = (string) api_input('sifat', 'Benefit');
        $bobot = (float) api_input('bobot', 0);

        if ($kode === '' || $nama === '') {
            api_response(false, 'Kode dan nama kriteria wajib diisi.', [], 422);
        }

        if (!in_array($sifat, ['Benefit', 'Cost'], true)) {
            api_response(false, 'Sifat kriteria tidak valid.', [], 422);
        }

        if ($bobot < 0 || $bobot > 1) {
            api_response(false, 'Bobot harus antara 0 dan 1.', [], 422);
        }

        $stmt = $pdo->prepare('SELECT id_kriteria FROM kriteria WHERE kode = :kode AND id_kriteria <> :id LIMIT 1');
        $stmt->execute([':kode' => $kode, ':id' => (int) $id]);
        if ($stmt->fetch()) {
            api_response(false, 'Kode kriteria sudah digunakan.', [], 409);
        }

        $pdo->beginTransaction();

        if ($id) {
            $stmt = $pdo->prepare('UPDATE kriteria SET kode = :kode, nama_kriteria = :nama, sifat = :sifat, bobot = :bobot WHERE id_kriteria = :id');
            $stmt->execute([
                ':kode' => $kode,
                ':nama' => $nama,
                ':sifat' => $sifat,
                ':bobot' => $bobot,
                ':id' => (int) $id,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO kriteria (kode, nama_kriteria, sifat, bobot) VALUES (:kode, :nama, :sifat, :bobot)');
            $stmt->execute([
                ':kode' => $kode,
                ':nama' => $nama,
                ':sifat' => $sifat,
                ':bobot' => $bobot,
            ]);
        }

        $pdo->commit();
        $total = api_total_bobot($pdo);

        api_response(true, 'Kriteria berhasil disimpan.', [
            'kriteria' => api_fetch_kriteria($pdo),
            'total_bobot' => $total,
            'bobot_valid' => api_bobot_valid($pdo),
        ]);
    }

    if ($action === 'delete_kriteria') {
        api_require_admin();
        $id = (int) api_input('id_kriteria', 0);
        if ($id <= 0) {
            api_response(false, 'ID kriteria tidak valid.', [], 422);
        }

        $stmt = $pdo->prepare('DELETE FROM kriteria WHERE id_kriteria = :id');
        $stmt->execute([':id' => $id]);

        $total = api_total_bobot($pdo);
        api_response(true, 'Kriteria berhasil dihapus.', [
            'kriteria' => api_fetch_kriteria($pdo),
            'total_bobot' => $total,
            'bobot_valid' => api_bobot_valid($pdo),
        ]);
    }

    if ($action === 'save_guru') {
        api_require_admin();
        $id = api_input('id_guru');
        $nama = trim((string) api_input('nama_guru', ''));
        $nilaiInput = $_POST['nilai'] ?? [];
        $kriteria = api_fetch_kriteria($pdo);

        if ($nama === '') {
            api_response(false, 'Nama guru wajib diisi.', [], 422);
        }

        if (!$kriteria) {
            api_response(false, 'Tambahkan kriteria terlebih dahulu sebelum menyimpan data guru.', [], 422);
        }

        foreach ($kriteria as $rowKriteria) {
            $key = (string) $rowKriteria['id_kriteria'];
            if (!array_key_exists($key, $nilaiInput)) {
                api_response(false, 'Semua nilai harus diisi.', [], 422);
            }

            if (!is_numeric($nilaiInput[$key])) {
                api_response(false, 'Nilai guru harus berupa angka.', [], 422);
            }
        }

        $pdo->beginTransaction();

        if ($id) {
            $stmt = $pdo->prepare('UPDATE guru SET nama_guru = :nama WHERE id_guru = :id');
            $stmt->execute([
                ':nama' => $nama,
                ':id' => (int) $id,
            ]);
            $guruId = (int) $id;
        } else {
            $stmt = $pdo->prepare('INSERT INTO guru (nama_guru) VALUES (:nama)');
            $stmt->execute([':nama' => $nama]);
            $guruId = (int) $pdo->lastInsertId();
        }

        $stmtDelete = $pdo->prepare('DELETE FROM nilai WHERE id_guru = :id_guru');
        $stmtDelete->execute([':id_guru' => $guruId]);

        $stmtInsert = $pdo->prepare('INSERT INTO nilai (id_guru, id_kriteria, nilai) VALUES (:id_guru, :id_kriteria, :nilai)');
        foreach ($kriteria as $rowKriteria) {
            $key = (string) $rowKriteria['id_kriteria'];
            $stmtInsert->execute([
                ':id_guru' => $guruId,
                ':id_kriteria' => (int) $rowKriteria['id_kriteria'],
                ':nilai' => (float) $nilaiInput[$key],
            ]);
        }

        $pdo->commit();

        api_response(true, 'Data guru dan nilai berhasil disimpan.', [
            'guru' => api_fetch_guru($pdo),
        ]);
    }

    if ($action === 'delete_guru') {
        api_require_admin();
        $id = (int) api_input('id_guru', 0);
        if ($id <= 0) {
            api_response(false, 'ID guru tidak valid.', [], 422);
        }

        $stmt = $pdo->prepare('DELETE FROM guru WHERE id_guru = :id');
        $stmt->execute([':id' => $id]);

        api_response(true, 'Data guru berhasil dihapus.', [
            'guru' => api_fetch_guru($pdo),
        ]);
    }

    if ($action === 'list_kriteria') {
        api_response(true, 'OK', [
            'kriteria' => api_fetch_kriteria($pdo),
            'total_bobot' => api_total_bobot($pdo),
            'bobot_valid' => api_bobot_valid($pdo),
        ]);
    }

    if ($action === 'list_guru') {
        api_response(true, 'OK', [
            'guru' => api_fetch_guru($pdo),
        ]);
    }

    if ($action === 'saw' || $action === 'hasil' || $action === 'report_data') {
        $summary = api_calculate_saw($pdo);
        api_response(true, 'Perhitungan SAW berhasil.', [
            'kriteria' => $summary['kriteria'],
            'hasil' => $summary['hasil'],
            'total_bobot' => api_total_bobot($pdo),
            'bobot_valid' => api_bobot_valid($pdo),
        ]);
    }

    api_response(false, 'Aksi tidak dikenal.', [], 400);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_response(false, 'Terjadi kesalahan server: ' . $e->getMessage(), [], 500);
}