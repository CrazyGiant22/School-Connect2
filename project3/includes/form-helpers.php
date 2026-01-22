<?php
/**
 * Modern Form and Table Helpers for Bootstrap 5
 * Provides reusable template functions for consistent modern design
 */

/**
 * Display alert message
 */
function showAlert($message, $type = 'success') {
    $icon_map = [
        'success' => 'bi-check-circle',
        'danger' => 'bi-exclamation-circle',
        'warning' => 'bi-exclamation-triangle',
        'info' => 'bi-info-circle'
    ];
    $icon = $icon_map[$type] ?? 'bi-info-circle';
    echo <<<HTML
    <div class="alert-modern $type" role="alert">
        <i class="bi $icon"></i> $message
    </div>
    HTML;
}

/**
 * Start modern form
 */
function startModernForm($action, $method = 'POST', $class = '') {
    echo <<<HTML
    <div class="card-modern">
        <form action="$action" method="$method" class="$class">
            <div class="card-modern-body">
    HTML;
}

/**
 * End modern form
 */
function endModernForm($button_text = 'Submit', $button_type = 'primary') {
    echo <<<HTML
                <div class="form-group" style="margin-top: 2rem; margin-bottom: 0;">
                    <button type="submit" class="btn-modern $button_type">
                        <i class="bi bi-check"></i> $button_text
                    </button>
                    <a href="javascript:history.back()" class="btn-modern secondary" style="background: #6c757d;">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
    HTML;
}

/**
 * Form input field
 */
function formInput($name, $label, $type = 'text', $value = '', $required = false, $placeholder = '') {
    $req = $required ? 'required' : '';
    $placeholder = $placeholder ?: "Enter $label";
    echo <<<HTML
    <div class="form-group">
        <label for="$name" class="form-label">$label</label>
        <input type="$type" class="form-control" id="$name" name="$name" value="$value" placeholder="$placeholder" $req>
    </div>
    HTML;
}

/**
 * Form select field
 */
function formSelect($name, $label, $options, $selected = '', $required = false) {
    $req = $required ? 'required' : '';
    echo <<<HTML
    <div class="form-group">
        <label for="$name" class="form-label">$label</label>
        <select class="form-select" id="$name" name="$name" $req>
            <option value="">Select $label</option>
    HTML;
    
    foreach ($options as $value => $text) {
        $sel = $value == $selected ? 'selected' : '';
        echo "<option value=\"$value\" $sel>$text</option>";
    }
    
    echo <<<HTML
        </select>
    </div>
    HTML;
}

/**
 * Form textarea field
 */
function formTextarea($name, $label, $value = '', $required = false, $rows = 4) {
    $req = $required ? 'required' : '';
    echo <<<HTML
    <div class="form-group">
        <label for="$name" class="form-label">$label</label>
        <textarea class="form-control" id="$name" name="$name" rows="$rows" $req>$value</textarea>
    </div>
    HTML;
}

/**
 * Start modern table
 */
function startModernTable($title = '') {
    echo <<<HTML
    <div class="card-modern">
        <div class="card-modern-header">
            <h5>$title</h5>
        </div>
        <div class="table-responsive-modern">
            <table class="table">
                <thead>
    HTML;
}

/**
 * End modern table
 */
function endModernTable() {
    echo <<<HTML
                </thead>
                <tbody>
                </table>
        </div>
    </div>
    HTML;
}

/**
 * Action buttons for tables
 */
function tableActionButtons($id, $edit_url_pattern = '', $delete_url_pattern = '') {
    $edit_url   = $edit_url_pattern   ? str_replace('{id}', $id, $edit_url_pattern)   : '';
    $delete_url = $delete_url_pattern ? str_replace('{id}', $id, $delete_url_pattern) : '';
    
    echo '<div class="btn-group-modern" style="gap: 0.5rem;">';
    
    // Edit as normal link
    if ($edit_url) {
        echo <<<HTML
        <a href="$edit_url" class="btn-modern primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
        HTML;
    }
    
    // Delete as POST form with CSRF protection
    if ($delete_url) {
        $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        echo '<form method="POST" action="' . $delete_url . '" style="display:inline;" onsubmit="return confirm(\'Are you sure?\');">';
        if (function_exists('csrfTokenField')) {
            echo csrfTokenField();
        }
        echo '<input type="hidden" name="id" value="' . $safeId . '">';
        echo '<button type="submit" class="btn-modern danger" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">';
        echo '<i class="bi bi-trash"></i> Delete';
        echo '</button>';
        echo '</form>';
    }
    
    echo '</div>';
}

/**
 * Status badge
 */
function statusBadge($status, $type = 'primary') {
    echo "<span class=\"badge-modern $type\">$status</span>";
}
?>
