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
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $errorMsg = "❌ Username or email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $username, $email, $password, $role);
        if ($stmt->execute()) {
            $successMsg = "✅ User added successfully with role: " . htmlspecialchars($role);
        } else {
            $errorMsg = "❌ Error adding user: " . $stmt->error;
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
        $errorMsg = "❌ Username or email already exists.";
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
            $successMsg = "✅ User updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $errorMsg = "❌ Error updating user.";
        }
        $stmt->close();
    }
    $check->close();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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

$userResult     = $conn->query("SELECT * FROM users ORDER BY id DESC");
$totalUsers     = $userResult ? $userResult->num_rows : 0;
$allRolesResult = $conn->query("SELECT * FROM roles ORDER BY role_name ASC");
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #2563eb;
            border-radius: 4px;
        }
        .hidden { display: none; }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

<div class="flex min-h-screen">

    <aside class="w-64 bg-white border-r border-gray-200 shadow-lg flex flex-col">
        <div class="p-6 border-b border-gray-200 text-center">
            <h1 class="text-2xl font-extrabold text-blue-700 tracking-wide">Admin Panel</h1>
            <p class="text-sm text-gray-500 mt-1">Welcome, Admin</p>
        </div>
        <nav class="mt-6 flex flex-col space-y-1">
            <button onclick="showSection('dashboard')" class="text-gray-700 hover:bg-blue-100 px-6 py-3 font-semibold text-left rounded transition">Dashboard</button>
            <button onclick="showSection('allUsers')" class="text-gray-700 hover:bg-blue-100 px-6 py-3 font-semibold text-left rounded transition">All Users</button>
            <button onclick="showSection('addUser')" class="text-gray-700 hover:bg-blue-100 px-6 py-3 font-semibold text-left rounded transition">Add User</button>
            <button onclick="showSection('addRole')" class="text-gray-700 hover:bg-blue-100 px-6 py-3 font-semibold text-left rounded transition">Add Role</button>
            <button onclick="showSection('allRoles')" class="text-gray-700 hover:bg-blue-100 px-6 py-3 font-semibold text-left rounded transition">All Roles</button>
        </nav>
        <div class="mt-auto p-6 border-t border-gray-200 text-center">
            <a href="../logout.php" class="inline-block px-4 py-2 text-sm text-white bg-red-600 rounded hover:bg-red-700 transition">Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-10 max-w-full overflow-x-auto">
        <section id="dashboard" class="<?php echo $updateMode ? 'hidden' : ''; ?>">
            <h2 class="text-4xl font-extrabold mb-8 text-gray-800">Dashboard</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div onclick="showSection('allUsers')" class="cursor-pointer rounded-xl bg-white p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between">
                    <h3 class="text-xl font-bold text-blue-600 mb-2">All Users</h3>
                    <p class="text-gray-500">Manage all registered users</p>
                </div>
                <div onclick="showSection('addUser')" class="cursor-pointer rounded-xl bg-white p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between">
                    <h3 class="text-xl font-bold text-green-600 mb-2">Add User</h3>
                    <p class="text-gray-500">Create a new user account</p>
                </div>
                <div onclick="showSection('addRole')" class="cursor-pointer rounded-xl bg-white p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between">
                    <h3 class="text-xl font-bold text-purple-600 mb-2">Add Role</h3>
                    <p class="text-gray-500">Define and assign user roles</p>
                </div>
                <div onclick="showSection('allRoles')" class="cursor-pointer rounded-xl bg-white p-6 shadow-md hover:shadow-xl transition flex flex-col justify-between">
                    <h3 class="text-xl font-bold text-indigo-600 mb-2">All Roles</h3>
                    <p class="text-gray-500">View existing roles</p>
                </div>
            </div>
        </section>

        <section id="allUsers" class="hidden max-w-7xl mx-auto">
            <h2 class="text-3xl font-extrabold mb-6 text-gray-800">All Users (<?php echo $totalUsers; ?>)</h2>
            <?php if ($successMsg): ?>
                <p class="mb-6 px-4 py-3 rounded bg-green-100 text-green-700 font-semibold"><?php echo $successMsg; ?></p>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <p class="mb-6 px-4 py-3 rounded bg-red-100 text-red-700 font-semibold"><?php echo $errorMsg; ?></p>
            <?php endif; ?>

            <div class="overflow-x-auto rounded-lg shadow-md bg-white">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm font-normal">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-3 uppercase tracking-wide">ID</th>
                            <th class="px-6 py-3 uppercase tracking-wide">Name</th>
                            <th class="px-6 py-3 uppercase tracking-wide">Username</th>
                            <th class="px-6 py-3 uppercase tracking-wide">Email</th>
                            <th class="px-6 py-3 uppercase tracking-wide">Role</th>
                            <th class="px-6 py-3 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($user = $userResult->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 whitespace-nowrap"><?php echo $user['id']; ?></td>
                                <td class="px-6 py-3 whitespace-nowrap"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="px-6 py-3 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-3 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-6 py-3 whitespace-nowrap"><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="px-6 py-3 whitespace-nowrap space-x-3">
                                    <a href="?edit_id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</a>
                                    <a href="?id=<?php echo $user['id']; ?>" onclick="return confirm('Delete this user?');" class="text-red-600 hover:text-red-800 font-semibold">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="addUser" class="hidden max-w-lg mx-auto">
            <h2 class="text-3xl font-extrabold mb-6 text-gray-800"><?php echo $updateMode ? 'Update User' : 'Add New User'; ?></h2>
            <?php if ($successMsg): ?>
                <p class="mb-4 px-4 py-3 rounded bg-green-100 text-green-700 font-semibold"><?php echo $successMsg; ?></p>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <p class="mb-4 px-4 py-3 rounded bg-red-100 text-red-700 font-semibold"><?php echo $errorMsg; ?></p>
            <?php endif; ?>

            <form method="POST" class="bg-white p-8 rounded-lg shadow space-y-6">
                <?php if ($updateMode): ?>
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>" />
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
                    <input type="text" id="name" name="name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?php echo $updateMode ? htmlspecialchars($editUser['name']) : ''; ?>" />
                </div>

                <div>
                    <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?php echo $updateMode ? htmlspecialchars($editUser['username']) : ''; ?>" />
                </div>

                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="<?php echo $updateMode ? htmlspecialchars($editUser['email']) : ''; ?>" />
                </div>

                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-2">
                        <?php echo $updateMode ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                    </label>
                    <input type="password" id="password" name="password" <?php echo $updateMode ? '' : 'required'; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div>
                    <label for="role" class="block text-gray-700 font-semibold mb-2">Role</label>
                    <select id="role" name="role" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled <?php echo !$updateMode ? 'selected' : ''; ?>>-- Select Role --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role; ?>" <?php echo $updateMode && $editUser['role'] === $role ? 'selected' : ''; ?>>
                                <?php echo ucfirst($role); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-center space-x-4">
                    <button type="submit" name="<?php echo $updateMode ? 'update_user' : 'add_user'; ?>"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition font-semibold">
                        <?php echo $updateMode ? 'Update User' : 'Add User'; ?>
                    </button>
                    <?php if ($updateMode): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>"
                           class="text-gray-600 hover:text-gray-900 font-semibold transition">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section id="addRole" class="hidden max-w-sm mx-auto">
            <h2 class="text-3xl font-extrabold mb-6 text-gray-800">Add Role</h2>
            <?php echo $roleMsg; ?>
            <form method="POST" class="bg-white p-8 rounded-lg shadow space-y-6">
                <label for="role_name" class="block text-gray-700 font-semibold mb-2">Role Name</label>
                <input type="text" id="role_name" name="role_name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-600" />
                <button type="submit" name="add_role"
                    class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition font-semibold">
                    Add Role
                </button>
            </form>
        </section>

        <section id="allRoles" class="hidden max-w-sm mx-auto">
            <h2 class="text-3xl font-extrabold mb-6 text-gray-800">All Roles</h2>
            <ul class="bg-white rounded-lg shadow divide-y divide-gray-200">
                <?php if ($allRolesResult->num_rows > 0): ?>
                    <?php while ($r = $allRolesResult->fetch_assoc()): ?>
                        <li class="px-6 py-3 hover:bg-gray-50 transition cursor-default"><?php echo htmlspecialchars($r['role_name']); ?></li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="px-6 py-3 text-gray-500">No roles defined yet.</li>
                <?php endif; ?>
            </ul>
        </section>
    </main>
</div>

<script>
    function showSection(id) {
        ['dashboard', 'allUsers', 'addUser', 'addRole', 'allRoles'].forEach(section => {
            document.getElementById(section).classList.add('hidden');
        });
        document.getElementById(id).classList.remove('hidden');
    }

    <?php if ($updateMode): ?>
        showSection('addUser');
    <?php else: ?>
        showSection('dashboard');
    <?php endif; ?>
</script>

</body>
</html>
