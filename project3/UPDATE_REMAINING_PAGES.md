# Update Remaining Pages with Modern Sidebar Design

## ✅ Already Updated (100%)
- ✅ admission.php
- ✅ add_student.php  
- ✅ view_student.php

## ⏳ Quick Update Instructions for Remaining Pages

For each remaining page, follow this pattern:

### Step 1: Add Includes at Top (After `session_start()`)

**Find:**
```php
<?php
session_start();
include 'includes/connect.php';
```

**Replace with:**
```php
<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');
```

### Step 2: Replace Auth Check

**Find:**
```php
if (!isset($_SESSION['username'])) {
    header("location:login.php");
}
elseif($_SESSION['usertype'] !== "admin") {
    header("location:login.php");
}
```

**Delete it** (already handled by `requireRole()`)

### Step 3: Add Sidebar Include (Before HTML)

**Find:**
```php
?>

<!DOCTYPE html>
```

**Replace with:**
```php
?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Page Title Here</div>
            <div class="page-subtitle">Page subtitle here</div>

            <!-- Your page content goes here -->

        </main>
    </div>
</div>

<!DOCTYPE html>
```

### Step 4: Replace Old HTML Structure

**Remove:**
- Old `<!DOCTYPE html>`
- Old `<head>` tags
- Old `<body>` tags
- Old CSS in `<style>` tags

**Keep only:** The actual page content (forms, tables, etc.)

### Step 5: Add Modern Footer

**Replace closing tags** with:
```php
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## Pages to Update (With Examples)

### admin_add_lecturer.php

**Page Title:** "Add New Lecturer"

```php
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';
requireRole('admin');

$message = '';
$message_type = '';

if (isset($_POST['add_lecturer'])) {
    $username = mysqli_real_escape_string($data, $_POST['name'] ?? '');
    // ... rest of validation
    if($result) {
        $message = 'Lecturer added successfully!';
        $message_type = 'success';
    }
}
?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Add New Lecturer</div>
            <div class="page-subtitle">Create a new lecturer account</div>
            
            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>
            
            <?php startModernForm('admin_add_lecturer.php', 'POST'); ?>
                <div class="form-row">
                    <?php formInput('name', 'Username', 'text', '', true); ?>
                    <?php formInput('email', 'Email', 'email', '', true); ?>
                </div>
                <div class="form-row">
                    <?php formInput('password', 'Password', 'password', '', true); ?>
                    <?php formInput('phone', 'Phone', 'tel', '', false); ?>
                </div>
                <input type="hidden" name="add_lecturer" value="1">
                <?php endModernForm('Add Lecturer', 'success'); ?>
        </main>
    </div>
</div>
```

### admin_view_lecturer.php

```php
<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Lecturers Management</div>
            <div class="page-subtitle">View and manage all lecturers</div>

            <div class="btn-group-modern" style="margin-bottom: 2rem;">
                <a href="admin_add_lecturer.php" class="btn-modern primary">
                    <i class="bi bi-briefcase"></i> Add New Lecturer
                </a>
            </div>

            <?php startModernTable('All Lecturers'); ?>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php tableActionButtons($row['id'], 'admin_update_lecturer.php?lecturer_id={id}', 'delete.php?id={id}'); ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <?php endModernTable(); ?>
        </main>
    </div>
</div>
```

### add_course.php

```php
<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Add New Course</div>
            <div class="page-subtitle">Create a new course</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php startModernForm('add_course.php', 'POST'); ?>
                <div class="form-row">
                    <?php formInput('code', 'Course Code', 'text', '', true); ?>
                    <?php formInput('name', 'Course Name', 'text', '', true); ?>
                </div>
                <div class="form-group">
                    <?php formTextarea('description', 'Description', '', true, 4); ?>
                </div>
                <input type="hidden" name="submit" value="1">
                <?php endModernForm('Add Course', 'primary'); ?>
        </main>
    </div>
</div>
```

### assign_course.php

```php
<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Assign Courses</div>
            <div class="page-subtitle">Assign courses to lecturers</div>

            <?php startModernForm('assign_course.php', 'POST'); ?>
                <div class="form-row">
                    <?php formSelect('course_id', 'Select Course', $courses, ''); ?>
                    <?php formSelect('lecturer_id', 'Select Lecturer', $lecturers, ''); ?>
                </div>
                <input type="hidden" name="assign" value="1">
                <?php endModernForm('Assign', 'primary'); ?>
        </main>
    </div>
</div>
```

### enroll_student.php

```php
<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Enroll Students</div>
            <div class="page-subtitle">Enroll students in courses</div>

            <?php startModernForm('enroll_student.php', 'POST'); ?>
                <div class="form-row">
                    <?php formSelect('student_id', 'Select Student', $students, ''); ?>
                    <?php formSelect('course_id', 'Select Course', $courses, ''); ?>
                </div>
                <input type="hidden" name="enroll" value="1">
                <?php endModernForm('Enroll', 'primary'); ?>
        </main>
    </div>
</div>
```

---

## Quick Copy-Paste Template

For ANY admin page, use this template:

```php
<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$message = '';
$message_type = '';

// Your PHP logic here
// Process forms, queries, etc.

?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Page Title</div>
            <div class="page-subtitle">Page Description</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <!-- Add your form or table here -->
            <?php startModernForm('page.php', 'POST'); ?>
                <!-- Form fields -->
                <?php endModernForm('Submit', 'primary'); ?>
            
            OR
            
            <?php startModernTable('Title'); ?>
                <tr><th>Col 1</th><th>Col 2</th></tr>
            </thead>
            <tbody>
                <!-- Table rows -->
            </tbody>
            <?php endModernTable(); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## Checklist for Each Page

- [ ] Add includes (connect, session-helpers, form-helpers)
- [ ] Add `requireRole('admin')`
- [ ] Remove old auth checks
- [ ] Add `$message` and `$message_type` variables
- [ ] Replace echo alerts with `showAlert()`
- [ ] Add `<?php include 'includes/admin-sidebar.php'; ?>` before HTML
- [ ] Add page title and subtitle
- [ ] Replace forms with form helpers
- [ ] Replace tables with table helpers
- [ ] Update closing tags
- [ ] Test on desktop and mobile
- [ ] Verify sidebar displays
- [ ] Verify navbar displays
- [ ] Test forms/tables work

---

## Testing Each Page

After updating each page:

1. **Load in browser** - Check sidebar and navbar appear
2. **Test responsive** - Open DevTools (F12), toggle mobile view
3. **Test functionality** - Submit forms, verify database updates
4. **Check styling** - Verify colors, spacing, buttons look right

---

## Helper Functions Reference

### Form Building
```php
formInput($name, $label, $type, $value, $required, $placeholder)
formSelect($name, $label, $options_array, $selected_value)
formTextarea($name, $label, $value, $required, $rows)
startModernForm($action, $method)
endModernForm($button_text, $button_type) // primary, success, danger
```

### Display
```php
showAlert($message, $type) // success, danger, warning, info
statusBadge($text, $type)
startModernTable($title)
tableActionButtons($id, $edit_url, $delete_url)
endModernTable()
```

### Authentication
```php
requireRole('admin') // or 'lecturer', 'student'
```

---

## Speed Tips

1. **Copy one updated file** (e.g., add_student.php) as a reference
2. **Follow the pattern** for other files
3. **Test as you go** - Don't wait until the end
4. **Use Find & Replace** in your editor for repetitive changes

---

**Estimated Time**: 15-20 minutes per page using this guide

Good luck! 🚀
