<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    // Hapus semua data sesi
    session_destroy();

    // Redirect ke index.php
    header("Location: index.php");
    exit;
}



// Constants for shift types
define("LIBUR", 0);
define("PAGI", 8);
define("SORE", 16);

// Function to map shift numbers to text
function shift_to_text($shift) {
    if ($shift == PAGI) return "Pagi";
    if ($shift == SORE) return "Sore";
    if ($shift == LIBUR) return "Libur";
    return "-";
}

// Function to generate schedule
function generate_schedule($divisions) {
    $shift_patterns = [
        [SORE, SORE, PAGI, PAGI, PAGI, LIBUR, SORE],
        [PAGI, LIBUR, SORE, SORE, SORE, PAGI, PAGI],
        [PAGI, PAGI, LIBUR, SORE, SORE, SORE, PAGI],
        [SORE, PAGI, PAGI, PAGI, LIBUR, SORE, SORE],
    ];

    $schedule = [];

    foreach ($divisions as $division => $staff) {
        foreach ($staff as $index => $person) {
            $pattern = $shift_patterns[$index % count($shift_patterns)];
            $full_schedule = array_merge($pattern, $pattern, $pattern, $pattern, $pattern); // Repeat pattern for 35 days
            $schedule[$person] = $full_schedule;
        }
    }

    return $schedule;
}

// Function to calculate CSP compliance
function calculate_csp_compliance($schedule) {
    $total_constraints = 0;
    $satisfied_constraints = 0;

    foreach ($schedule as $employee => $shifts) {
        $consecutive_work_days = 0;

        for ($i = 0; $i < count($shifts); $i++) {
            $shift = $shifts[$i];
            $total_constraints++;

            // Hard Constraint: Max 6 consecutive workdays
            if ($shift != LIBUR) {
                $consecutive_work_days++;
                if ($consecutive_work_days <= 6) {
                    $satisfied_constraints++;
                }
            } else {
                $consecutive_work_days = 0; // Reset if day off
            }

            // Hard Constraint: Max 5 SORE shifts in a week
            $weekly_shifts = array_slice($shifts, max(0, $i - 6), 7);
            $count_sore = count(array_filter($weekly_shifts, fn($s) => $s == SORE));
            $total_constraints++;
            if ($count_sore <= 5) {
                $satisfied_constraints++;
            }

            // Soft Constraint: No more than 2 consecutive LIBUR days
            if ($shift == LIBUR && isset($shifts[$i - 1]) && $shifts[$i - 1] == LIBUR) {
                $total_constraints++;
                if (($i < 2 || $shifts[$i - 2] != LIBUR)) {
                    $satisfied_constraints++;
                }
            }
        }
    }

    // Calculate compliance percentage
    $compliance_percentage = ($satisfied_constraints / $total_constraints) * 100;
    return $compliance_percentage;
}

// Generate schedule
$divisions = $_SESSION['divisions'] ?? [];
$schedule = generate_schedule($divisions);
$csp_compliance = calculate_csp_compliance($schedule);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kerja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #d9d9d9;
            padding: 20px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: auto;
            max-width: 95%;
            text-align: left;
        }
        .card h1 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        table {
            margin: 0 auto;
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .weekend {
            background-color: #ffcccc;
        }
        .libur {
            background-color: #ffdddd;
        }
        .pagi {
            background-color: rgb(255, 251, 204); /* Kuning */
        }
        .sore {
            background-color: rgb(245, 245, 245); /* Abu-abu */
        }
        .division-header {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .text-center {
        text-align: center;
        margin-top: 20px;
        }

    </style>
</head>
<body>
    <div class="card">
        <h1>Jadwal Kerja</h1>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <?php for ($day = 1; $day <= 31; $day++): ?>
                        <th class="<?= in_array($day, [7, 8, 14, 21, 22, 28, 29]) ? 'weekend' : '' ?>">
                            Day <?= $day ?>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($divisions as $division => $staff):
                    // Header baris divisi
                    echo '<tr class="division-header">';
                    echo '<td colspan="32">' . $division . '</td>';
                    echo '</tr>';
                    
                    // Data karyawan dalam divisi
                    foreach ($staff as $person):
                        echo '<tr>';
                        echo '<td>' . $person . '</td>';
                        for ($i = 0; $i < 31; $i++) {
                            $shift = shift_to_text($schedule[$person][$i]);
                            $class = '';
                            if ($shift == 'Libur') {
                                $class = 'libur';
                            } elseif ($shift == 'Pagi') {
                                $class = 'pagi';
                            } elseif ($shift == 'Sore') {
                                $class = 'sore';
                            }
                            echo '<td class="' . $class . '">' . $shift . '</td>';
                        }
                        echo '</tr>';
                    endforeach;
                endforeach;
                ?>
            </tbody>
        </table>
        <h3 class="text-center">Persentase Kepatuhan CSP: <?= round($csp_compliance, 2) ?>%</h3>
        <form method="POST">
            <button type="submit" name="reset">Buat Ulang</button>
        </form>
    </div>
</body>
</html>
