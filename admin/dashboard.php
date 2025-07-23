<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$successMsg = $errorMsg = $roleMsg = '';
$updateMode = false; 
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name     = $_POST['name'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    error_log("Received role: " . $role);

    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $errorMsg = "Username or email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $username, $email, $password, $role);
        if ($stmt->execute()) {
            $successMsg = "User added successfully with role: " . htmlspecialchars($role);
        } else {
            $errorMsg = "Error adding user: " . $stmt->error;
        }
        $stmt->close();
    }
    $check->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id       = $_POST['user_id'];
    $name     = $_POST['name'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $role     = $_POST['role'];
    $password = $_POST['password'];

    $check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $check->bind_param("ssi", $username, $email, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $errorMsg = "Username or email already exists.";
    } else {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $username, $email, $hashed_password, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $username, $email, $role, $id);
        }
        if ($stmt->execute()) {
            $successMsg = "User updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errorMsg = "Error updating user.";
        }
        $stmt->close();
    }
    $check->close();

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $roleName = trim($_POST['role_name']);
    if (!empty($roleName)) {
        $check = $conn->prepare("SELECT id FROM roles WHERE role_name = ?");
        $check->bind_param("s", $roleName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $roleMsg = "<p class='text-red-600 font-medium'>⚠️ Role already exists.</p>";
        } else {
            $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
            $stmt->bind_param("s", $roleName);
            if ($stmt->execute()) {
                $roleMsg = "<p class='text-green-600 font-medium'>✅ Role added successfully!</p>";
            } else {
                $roleMsg = "<p class='text-red-600 font-medium'>❌ Failed to add role. Please try again.</p>";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        $roleMsg = "<p class='text-red-600 font-medium'>⚠️ Please enter a role name.</p>";
    }
}

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $editUser = $result->fetch_assoc();
        $updateMode = true;
    }
    $stmt->close();
}

$roles = [];
$roleResult = $conn->query("SELECT role_name FROM roles ORDER BY role_name ASC");
if ($roleResult && $roleResult->num_rows > 0) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row['role_name'];
    }
}

$userResult = $conn->query("SELECT * FROM users");
$totalUsers = $userResult->num_rows;
$allRolesResult = $conn->query("SELECT * FROM roles ORDER BY role_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>.hidden { display: none; }</style>
</head>
<body class="bg-gray-100 font-sans">
<div class="min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg">
        <div class="p-6 text-center border-b">
            <h1 class="text-2xl font-bold text-blue-600">Admin Panel</h1>
            <p class="text-sm text-gray-500">Welcome, Admin</p>
        </div>
        <nav class="mt-6">
            <button onclick="showSection('dashboard')" class="w-full text-left px-6 py-3 hover:bg-blue-100 text-gray-700 font-medium">Dashboard</button>
            <button onclick="showSection('allUsers')" class="w-full text-left px-6 py-3 hover:bg-blue-100 text-gray-700 font-medium">All Users</button>
            <button onclick="showSection('addUser')" class="w-full text-left px-6 py-3 hover:bg-blue-100 text-gray-700 font-medium">Add User</button>
            <button onclick="showSection('addRole')" class="w-full text-left px-6 py-3 hover:bg-blue-100 text-gray-700 font-medium">Add Role</button>
            <button onclick="showSection('allRoles')" class="w-full text-left px-6 py-3 hover:bg-blue-100 text-gray-700 font-medium">All Roles</button>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-10">
        <!-- Dashboard -->
        <section id="dashboard" class="<?php echo $updateMode ? 'hidden' : ''; ?>">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">Dashboard</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div onclick="showSection('allUsers')" class="cursor-pointer bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
                    <h3 class="text-xl font-bold text-blue-600">All Users</h3>
                    <p class="text-gray-500 mt-2">Manage all registered users.</p>
                </div>
                <div onclick="showSection('addUser')" class="cursor-pointer bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
                    <h3 class="text-xl font-bold text-green-600">Add User</h3>
                    <p class="text-gray-500 mt-2">Create a new user account.</p>
                </div>
                <div onclick="showSection('addRole')" class="cursor-pointer bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
                    <h3 class="text-xl font-bold text-purple-600">Add Role</h3>
                    <p class="text-gray-500 mt-2">Define and assign user roles.</p>
                </div>
                <div onclick="showSection('allRoles')" class="cursor-pointer bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
                    <h3 class="text-xl font-bold text-indigo-600">All Roles</h3>
                    <p class="text-gray-500 mt-2">View existing roles.</p>
                </div>
            </div>
        </section>

        <!-- All Users -->
        <section id="allUsers" class="hidden">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">All Users (<?php echo $totalUsers; ?>)</h2>
            <?php if ($successMsg): ?>
                <p class="mb-4 text-green-600 font-semibold"><?php echo $successMsg; ?></p>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <p class="mb-4 text-red-600 font-semibold"><?php echo $errorMsg; ?></p>
            <?php endif; ?>

            <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="py-3 px-6 text-left">ID</th>
                        <th class="py-3 px-6 text-left">Name</th>
                        <th class="py-3 px-6 text-left">Username</th>
                        <th class="py-3 px-6 text-left">Email</th>
                        <th class="py-3 px-6 text-left">Role</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $userResult->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-6"><?php echo $user['id']; ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="py-3 px-6 space-x-2">
                            <a href="?edit_id=<?php echo $user['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" onclick="return confirm('Delete this user?');" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <!-- Add User -->
        <section id="addUser" class="hidden">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6"><?php echo $updateMode ? 'Update User' : 'Add New User'; ?></h2>
            <?php if ($successMsg): ?>
                <p class="mb-4 text-green-600 font-semibold"><?php echo $successMsg; ?></p>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <p class="mb-4 text-red-600 font-semibold"><?php echo $errorMsg; ?></p>
            <?php endif; ?>

            <form method="POST" class="max-w-lg bg-white p-6 rounded shadow space-y-4">
                <?php if ($updateMode): ?>
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>" />
                <?php endif; ?>

                <div>
                    <label class="block font-medium mb-1" for="name">Full Name</label>
                    <input required type="text" id="name" name="name" class="w-full border border-gray-300 rounded p-2" 
                    value="<?php echo $updateMode ? htmlspecialchars($editUser['name']) : ''; ?>" />
                </div>

                <div>
                    <label class="block font-medium mb-1" for="username">Username</label>
                    <input required type="text" id="username" name="username" class="w-full border border-gray-300 rounded p-2" 
                    value="<?php echo $updateMode ? htmlspecialchars($editUser['username']) : ''; ?>" />
                </div>

                <div>
                    <label class="block font-medium mb-1" for="email">Email</label>
                    <input required type="email" id="email" name="email" class="w-full border border-gray-300 rounded p-2" 
                    value="<?php echo $updateMode ? htmlspecialchars($editUser['email']) : ''; ?>" />
                </div>

                <div>
                    <label class="block font-medium mb-1" for="password"><?php echo $updateMode ? 'New Password (leave blank to keep current)' : 'Password'; ?></label>
                    <input <?php echo $updateMode ? '' : 'required'; ?> type="password" id="password" name="password" class="w-full border border-gray-300 rounded p-2" />
                </div>

                <div>
                    <label class="block font-medium mb-1" for="role">Role</label>
                    <select id="role" name="role" required class="w-full border border-gray-300 rounded p-2">
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $updateMode && $editUser['role'] === $role ? 'selected' : ''; ?>>
                                <?php echo ucfirst($role); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="<?php echo $updateMode ? 'update_user' : 'add_user'; ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    <?php echo $updateMode ? 'Update User' : 'Add User'; ?>
                </button>

                <?php if ($updateMode): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="inline-block ml-4 text-gray-600 hover:underline">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <!-- Add Role -->
        <section id="addRole" class="hidden">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">Add Role</h2>
            <?php echo $roleMsg; ?>
            <form method="POST" class="max-w-sm bg-white p-6 rounded shadow space-y-4">
                <label class="block font-medium mb-1" for="role_name">Role Name</label>
                <input required type="text" id="role_name" name="role_name" class="w-full border border-gray-300 rounded p-2" />
                <button type="submit" name="add_role" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    Add Role
                </button>
            </form>
        </section>

        <!-- All Roles -->
        <section id="allRoles" class="hidden">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">All Roles</h2>
            <ul class="bg-white p-6 rounded shadow max-w-sm">
                <?php if ($allRolesResult->num_rows > 0): ?>
                    <?php while ($r = $allRolesResult->fetch_assoc()): ?>
                        <li class="py-2 border-b last:border-none"><?php echo htmlspecialchars($r['role_name']); ?></li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li>No roles defined yet.</li>
                <?php endif; ?>
            </ul>
        </section>
    </main>
</div>

<script>
    // Function to show only one section
    function showSection(id) {
        ['dashboard', 'allUsers', 'addUser', 'addRole', 'allRoles'].forEach(sectionId => {
            document.getElementById(sectionId).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
    }

    // If update mode, show addUser section
    <?php if ($updateMode): ?>
        showSection('addUser');
    <?php endif; ?>
</script>
</body>
</html>
