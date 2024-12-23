<?php
session_start();


// Initialize divisions if not set
if (!isset($_SESSION['divisions'])) {
    $_SESSION['divisions'] = [
        "Bar" => [],
        "Waitress" => [],
        "Kitchen" => [],
    ];
}
$divisions = $_SESSION['divisions'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        $division = $_POST['division'];
        $employee_name = trim($_POST['employee_name']);
        if (!empty($employee_name) && isset($divisions[$division])) {
            $divisions[$division][] = $employee_name;
            $_SESSION['divisions'] = $divisions;
            header("Location: index.php"); // Redirect to prevent duplicate submission
            exit;
        }
    }
    if (isset($_POST['delete_employee'])) {
        $division = $_POST['division'];
        $employee_name = $_POST['employee_name'];
        if (isset($divisions[$division])) {
            $key = array_search($employee_name, $divisions[$division]);
            if ($key !== false) {
                unset($divisions[$division][$key]); // Remove the employee
                $_SESSION['divisions'] = $divisions;
                header("Location: index.php"); // Redirect after deletion
                exit;
            }
        }
    }
    if (isset($_POST['generate_schedule'])) {
        header("Location: schedule.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #d9d9d9;
            padding: 20px;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            margin: auto;
        }
        h1 {
            margin-bottom: 20px;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 80%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            font-weight: bold;
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
        button {
            padding: 3px 3px;
            font-size: 13px;
            cursor: pointer;
            round: 5px;
            rounded: 5px;
        }
        .generate-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
        }
        .delete-btn {
            background-color: #dc3545;
            color: #fff;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>JADWAL KETITIK KOPI</h1>
        <h3>Tambah Karyawan</h3>

        <form method="POST">
            <div class="form-group">
                <label for="employee_name">Nama Karyawan:</label>
                <input type="text" id="employee_name" name="employee_name" placeholder="Nama Karyawan" required>
            </div>

            <div class="form-group">
                <label for="division">Divisi:</label>
                <select id="division" name="division" required>
                    <option value="Bar">Bar</option>
                    <option value="Waitress">Waitress</option>
                    <option value="Kitchen">Kitchen</option>
                </select>
            </div>

            <button type="submit" name="add_employee">Tambah Karyawan</button>
        </form>

        <h3>Daftar Karyawan</h3>
        <table>
            <thead>
                <tr>
                    <th>Divisi</th>
                    <th>Nama</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($divisions as $division => $staff): ?>
                    <?php if (!empty($staff)): ?>
                        <?php foreach ($staff as $person): ?>
                            <tr>
                                <td><?= $division ?></td>
                                <td><?= $person ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="division" value="<?= $division ?>">
                                        <input type="hidden" name="employee_name" value="<?= $person ?>">
                                        <button type="submit" name="delete_employee" class="delete-btn">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><?= $division ?></td>
                            <td colspan="2">Belum ada karyawan</td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty(array_merge(...array_values($divisions)))): ?>
            <form method="POST">
                <button type="submit" name="generate_schedule" class="generate-btn">Generate Jadwal</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
