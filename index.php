<?php
session_start();

$genres = ["Fiction", "Non-Fiction", "Science", "History", "Biography", "Technology"];

if (!isset($_SESSION['books'])) {
    $_SESSION['books'] = [
        ["id" => 1, "title" => "The Great Gatsby", "author" => "F. Scott Fitzgerald", "genre" => "Fiction", "year" => 1925, "pages" => 218],
        ["id" => 2, "title" => "A Brief History of Time", "author" => "Stephen Hawking", "genre" => "Science", "year" => 1988, "pages" => 256],
        ["id" => 3, "title" => "Steve Jobs", "author" => "Walter Isaacson", "genre" => "Biography", "year" => 2011, "pages" => 656]
    ];
}

$books = &$_SESSION['books'];
$errors = [];
$submittedData = [
    "title" => "",
    "author" => "",
    "genre" => "",
    "year" => "",
    "pages" => "",
    "edit_id" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $books = array_filter($books, function($book) use ($delete_id) {
            return $book['id'] !== $delete_id;
        });
        $books = array_values($books);
        $_SESSION['success'] = "Book deleted successfully.";
        header("Location: index.php");
        exit;
    }

    $title = htmlspecialchars(trim($_POST['title'] ?? ''));
    $author = htmlspecialchars(trim($_POST['author'] ?? ''));
    $genre = htmlspecialchars(trim($_POST['genre'] ?? ''));
    $year = htmlspecialchars(trim($_POST['year'] ?? ''));
    $pages = htmlspecialchars(trim($_POST['pages'] ?? ''));
    $edit_id = isset($_POST['edit_id']) && $_POST['edit_id'] !== "" ? (int)$_POST['edit_id'] : null;

    $submittedData = compact('title', 'author', 'genre', 'year', 'pages', 'edit_id');

    if (empty($title) || strlen($title) < 3 || strlen($title) > 120) {
        $errors['title'] = "Title must be between 3 and 120 characters.";
    }
    
    $authorParts = array_filter(explode(' ', trim($author)));
    if (empty($author) || count($authorParts) < 2) {
        $errors['author'] = "Author must contain at least two words.";
    }
    
    if (empty($genre) || !in_array($genre, $genres)) {
        $errors['genre'] = "Please select a valid genre from the list.";
    }
    
    $currentYear = (int)date("Y");
    if (empty($year) || !preg_match('/^\d{4}$/', $year) || $year < 1000 || $year > $currentYear) {
        $errors['year'] = "Year must be a 4-digit number between 1000 and $currentYear.";
    }
    
    if (empty($pages) || !filter_var($pages, FILTER_VALIDATE_INT) || $pages <= 0) {
        $errors['pages'] = "Pages must be a positive integer.";
    }

    if (empty($errors)) {
        if ($edit_id) {
            foreach ($books as &$book) {
                if ($book['id'] === $edit_id) {
                    $book['title'] = $title;
                    $book['author'] = $author;
                    $book['genre'] = $genre;
                    $book['year'] = (int)$year;
                    $book['pages'] = (int)$pages;
                    break;
                }
            }
            $_SESSION["success"] = "Book updated successfully.";
        } else {
            $max_id = 0;
            foreach ($books as $book) {
                if ($book['id'] > $max_id) {
                    $max_id = $book['id'];
                }
            }
            $new_id = $max_id + 1;
            
            $books[] = [
                "id" => $new_id,
                "title" => $title,
                "author" => $author,
                "genre" => $genre,
                "year" => (int)$year,
                "pages" => (int)$pages
            ];
            $_SESSION["success"] = "Book added successfully.";
        }
        
        header("Location: index.php");
        exit;
    }
} elseif (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    foreach ($books as $book) {
        if ($book['id'] === $edit_id) {
            $submittedData = [
                "title" => $book['title'],
                "author" => $book['author'],
                "genre" => $book['genre'],
                "year" => $book['year'],
                "pages" => $book['pages'],
                "edit_id" => $book['id']
            ];
            break;
        }
    }
}

$successMessage = "";
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Book Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">Please fix the validation errors below.</div>
            <?php endif; ?>
            <form action="index.php" method="POST">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($submittedData['edit_id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($submittedData['title']) ?>">
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback"><?= $errors['title'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Author</label>
                    <input type="text" name="author" class="form-control <?= isset($errors['author']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($submittedData['author']) ?>">
                    <?php if (isset($errors['author'])): ?>
                        <div class="invalid-feedback"><?= $errors['author'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Genre</label>
                    <select name="genre" class="form-select <?= isset($errors['genre']) ? 'is-invalid' : '' ?>">
                        <option value="">Select a Genre</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $submittedData['genre'] === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['genre'])): ?>
                        <div class="invalid-feedback"><?= $errors['genre'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control <?= isset($errors['year']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($submittedData['year']) ?>">
                    <?php if (isset($errors['year'])): ?>
                        <div class="invalid-feedback"><?= $errors['year'] ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Pages</label>
                    <input type="number" name="pages" class="form-control <?= isset($errors['pages']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($submittedData['pages']) ?>">
                    <?php if (isset($errors['pages'])): ?>
                        <div class="invalid-feedback"><?= $errors['pages'] ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <?= !empty($submittedData['edit_id']) ? 'Update Book' : 'Add Book' ?>
                </button>
                <?php if (!empty($submittedData['edit_id'])): ?>
                    <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-md-8">
            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Year</th>
                        <th>Pages</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['id']) ?></td>
                            <td><?= htmlspecialchars($book['title']) ?></td>
                            <td><?= htmlspecialchars($book['author']) ?></td>
                            <td><?= htmlspecialchars($book['genre']) ?></td>
                            <td><?= htmlspecialchars((string)$book['year']) ?></td>
                            <td><?= htmlspecialchars($book['pages']) ?></td>
                            <td>
                                <a href="?edit_id=<?= htmlspecialchars($book['id']) ?>" class="btn btn-sm btn-warning mb-1">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $book['id'] ?>">Delete</button>

                                <div class="modal fade" id="deleteModal<?= $book['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete the book "<strong><?= htmlspecialchars($book['title']) ?></strong>"?
                                            </div>
                                            <div class="modal-footer">
                                                <form action="index.php" method="POST">
                                                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($book['id']) ?>">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Delete</button>
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