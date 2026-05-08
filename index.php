<?php
session_start();

// قائمة التصنيفات المعتمدة
$categoryList = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

if (!isset($_SESSION['library_data'])) {
    $_SESSION['library_data'] = [
        ["id" => 1, "title" => "The Great Gatsby", "author" => "F. Scott Fitzgerald", "genre" => "Fiction", "year" => 1925, "pages" => 218],
        ["id" => 2, "title" => "A Brief History of Time", "author" => "Stephen Hawking", "genre" => "Science", "year" => 1988, "pages" => 256],
        ["id" => 3, "title" => "Steve Jobs", "author" => "Walter Isaacson", "genre" => "Biography", "year" => 2011, "pages" => 656]
    ];
}

$books = &$_SESSION['library_data'];
$validationErrors = [];
$formValues = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "year" => "",
    "pages" => "",
    "edit_id" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // منطق الحذف
    if (isset($_POST['remove_id'])) {
        $remove_id = (int)$_POST['remove_id'];
        $books = array_filter($books, function($b) use ($remove_id) {
            return $b['id'] !== $remove_id;
        });
        $books = array_values($books); // إعادة ترتيب المفاتيح
        header("Location: index.php");
        exit;
    }

    // تعبئة البيانات المستلمة للفحص
    $formValues['title'] = trim($_POST['title'] ?? '');
    $formValues['author'] = trim($_POST['author'] ?? '');
    $formValues['genre'] = $_POST['genre'] ?? '';
    $formValues['year'] = $_POST['year'] ?? '';
    $formValues['pages'] = $_POST['pages'] ?? '';
    $formValues['edit_id'] = $_POST['edit_id'] ?? '';

    // التحقق من صحة البيانات (Validation)
    if (empty($formValues['title']) || strlen($formValues['title']) < 3 || strlen($formValues['title']) > 120) {
        $validationErrors[] = "Book title must be between 3 and 120 characters long.";
    }
    if (empty($formValues['author']) || count(explode(' ', $formValues['author'])) < 2) {
        $validationErrors[] = "Please provide the author's full name (at least two words).";
    }
    if (!in_array($formValues['genre'], $categoryList)) {
        $validationErrors[] = "Invalid genre selected. Please choose from the list.";
    }
    if (!filter_var($formValues['year'], FILTER_VALIDATE_INT) || $formValues['year'] < 1000 || $formValues['year'] > date("Y")) {
        $validationErrors[] = "Please enter a valid 4-digit year (from 1000 to " . date("Y") . ").";
    }
    if (!filter_var($formValues['pages'], FILTER_VALIDATE_INT) || $formValues['pages'] <= 0) {
        $validationErrors[] = "Pages count must be a positive number.";
    }

    // إذا لم يكن هناك أخطاء، نقوم بالحفظ أو التعديل
    if (empty($validationErrors)) {
        if (!empty($formValues['edit_id'])) {
            // منطق التعديل
            foreach ($books as &$b) {
                if ($b['id'] == $formValues['edit_id']) {
                    $b['title'] = $formValues['title'];
                    $b['author'] = $formValues['author'];
                    $b['genre'] = $formValues['genre'];
                    $b['year'] = (int)$formValues['year'];
                    $b['pages'] = (int)$formValues['pages'];
                    break;
                }
            }
        } else {
            // منطق الإضافة (توليد ID جديد عبر الحلقة)
            $nextId = 0;
            foreach ($books as $b) {
                if ($b['id'] > $nextId) $nextId = $b['id'];
            }
            $nextId++;
            
            $books[] = [
                "id" => $nextId,
                "title" => $formValues['title'],
                "author" => $formValues['author'],
                "genre" => $formValues['genre'],
                "year" => (int)$formValues['year'],
                "pages" => (int)$formValues['pages']
            ];
        }
        header("Location: index.php");
        exit;
    }
}

// تجهيز وضع التعديل
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($books as $b) {
        if ($b['id'] === $edit_id) {
            $formValues = $b;
            $formValues['edit_id'] = $edit_id;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Book Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-5">📚 My Personal Library Management</h2>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card p-4">
                <h4><?= !empty($formValues['edit_id']) ? 'Modify Book' : 'Add New Book' ?></h4>
                
                <?php if (!empty($validationErrors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($validationErrors as $err): ?>
                                <li><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($formValues['edit_id']) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Book Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($formValues['title']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Author Full Name</label>
                        <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($formValues['author']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Genre</label>
                        <select name="genre" class="form-control">
                            <option value="">Select Genre</option>
                            <?php foreach ($categoryList as $g): ?>
                                <option value="<?= $g ?>" <?= $formValues['genre'] == $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Publishing Year</label>
                        <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($formValues['year']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Number of Pages</label>
                        <input type="number" name="pages" class="form-control" value="<?= htmlspecialchars($formValues['pages']) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <?= !empty($formValues['edit_id']) ? 'Update Details' : 'Save Book' ?>
                    </button>
                    <?php if (!empty($formValues['edit_id'])): ?>
                        <a href="index.php" class="btn btn-link w-100 mt-2">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <table class="table table-striped table-hover bg-white rounded shadow-sm">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Year</th>
                        <th>Pages</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><?= htmlspecialchars($item['author']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($item['genre']) ?></span></td>
                            <td><?= $item['year'] ?></td>
                            <td><?= $item['pages'] ?></td>
                            <td>
                                <a href="index.php?edit=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delModal<?= $item['id'] ?>">Delete</button>

                                <div class="modal fade" id="delModal<?= $item['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Removal</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to remove "<strong><?= htmlspecialchars($item['title']) ?></strong>"?
                                            </div>
                                            <div class="modal-footer">
                                                <form action="index.php" method="POST">
                                                    <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>