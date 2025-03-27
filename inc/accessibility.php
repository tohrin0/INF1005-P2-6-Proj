<?php
/**
 * Accessibility helper functions
 * Provides functions for creating accessible UI components including csrf tokens
 */

/**
 * Generate an accessible form field with label and error handling
 * 
 * @param string $type Input type (text, email, password, etc.)
 * @param string $name Field name
 * @param string $label Label text
 * @param array $attributes Additional attributes
 * @param string $error Error message if any
 * @return string HTML for the form field
 */
function accessibleFormField($type, $name, $label, $attributes = [], $error = '') {
    $required = isset($attributes['required']) && $attributes['required'];
    $id = $attributes['id'] ?? $name;
    $value = $attributes['value'] ?? '';
    $placeholder = $attributes['placeholder'] ?? '';
    $class = $attributes['class'] ?? 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';
    $describedBy = $attributes['aria-describedby'] ?? '';
    $autocomplete = $attributes['autocomplete'] ?? '';
    
    // Convert boolean attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if (in_array($key, ['class', 'value', 'placeholder', 'id', 'aria-describedby', 'autocomplete'])) {
            continue; // These are handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<div class="mb-4">';
    
    // Add label with required indicator if needed
    $html .= '<label for="' . htmlspecialchars($id) . '" class="block text-sm font-medium text-gray-700 mb-1">';
    $html .= htmlspecialchars($label);
    if ($required) {
        $html .= ' <span class="text-red-500" aria-hidden="true">*</span>';
        $html .= '<span class="sr-only">(required)</span>';
    }
    $html .= '</label>';
    
    // Add input field
    if ($type === 'textarea') {
        $html .= '<textarea id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" ';
        $html .= 'class="' . htmlspecialchars($class) . '" ';
        if ($placeholder) {
            $html .= 'placeholder="' . htmlspecialchars($placeholder) . '" ';
        }
        if ($required) {
            $html .= 'aria-required="true" required ';
        }
        if (!empty($error)) {
            $html .= 'aria-invalid="true" ';
        }
        if (!empty($describedBy)) {
            $html .= 'aria-describedby="' . htmlspecialchars($describedBy) . '" ';
        } else if (!empty($error)) {
            $html .= 'aria-describedby="' . htmlspecialchars($name) . '-error" ';
        }
        $html .= $attrString . '>';
        $html .= htmlspecialchars($value);
        $html .= '</textarea>';
    } else {
        $html .= '<div class="relative">';
        
        // If using icons inside inputs
        if ($type === 'email' || $type === 'password' || $type === 'search') {
            $html .= '<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">';
            if ($type === 'email') {
                $html .= '<i class="fas fa-envelope text-gray-400" aria-hidden="true"></i>';
            } else if ($type === 'password') {
                $html .= '<i class="fas fa-lock text-gray-400" aria-hidden="true"></i>';
            } else if ($type === 'search') {
                $html .= '<i class="fas fa-search text-gray-400" aria-hidden="true"></i>';
            }
            $html .= '</div>';
            $class .= ' pl-10'; // Add padding for icon
        }
        
        $html .= '<input type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($id) . '" ';
        $html .= 'name="' . htmlspecialchars($name) . '" ';
        $html .= 'value="' . htmlspecialchars($value) . '" ';
        if (!empty($placeholder)) {
            $html .= 'placeholder="' . htmlspecialchars($placeholder) . '" ';
        }
        if (!empty($autocomplete)) {
            $html .= 'autocomplete="' . htmlspecialchars($autocomplete) . '" ';
        }
        $html .= 'class="' . htmlspecialchars($class) . '" ';
        if ($required) {
            $html .= 'aria-required="true" required ';
        }
        if (!empty($error)) {
            $html .= 'aria-invalid="true" ';
        }
        if (!empty($describedBy)) {
            $html .= 'aria-describedby="' . htmlspecialchars($describedBy) . '" ';
        } else if (!empty($error)) {
            $html .= 'aria-describedby="' . htmlspecialchars($name) . '-error" ';
        }
        $html .= $attrString . '>';
        $html .= '</div>';
    }
    
    // Add error message if present
    if (!empty($error)) {
        $html .= '<p id="' . htmlspecialchars($name) . '-error" class="mt-1 text-sm text-red-600">';
        $html .= htmlspecialchars($error);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate an accessible select dropdown with label and error handling
 * 
 * @param string $name Field name
 * @param string $label Label text
 * @param array $options Options array [value => label]
 * @param array $attributes Additional attributes
 * @param string $error Error message if any
 * @return string HTML for the select field
 */
function accessibleSelectField($name, $label, $options = [], $attributes = [], $error = '') {
    $required = isset($attributes['required']) && $attributes['required'];
    $id = $attributes['id'] ?? $name;
    $selected = $attributes['value'] ?? '';
    $class = $attributes['class'] ?? 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500';
    $describedBy = $attributes['aria-describedby'] ?? '';
    
    // Convert boolean attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if (in_array($key, ['class', 'value', 'id', 'aria-describedby'])) {
            continue; // These are handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<div class="mb-4">';
    
    // Add label with required indicator if needed
    $html .= '<label for="' . htmlspecialchars($id) . '" class="block text-sm font-medium text-gray-700 mb-1">';
    $html .= htmlspecialchars($label);
    if ($required) {
        $html .= ' <span class="text-red-500" aria-hidden="true">*</span>';
        $html .= '<span class="sr-only">(required)</span>';
    }
    $html .= '</label>';
    
    // Add select field
    $html .= '<select id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" ';
    $html .= 'class="' . htmlspecialchars($class) . '" ';
    if ($required) {
        $html .= 'aria-required="true" required ';
    }
    if (!empty($error)) {
        $html .= 'aria-invalid="true" ';
    }
    if (!empty($describedBy)) {
        $html .= 'aria-describedby="' . htmlspecialchars($describedBy) . '" ';
    } else if (!empty($error)) {
        $html .= 'aria-describedby="' . htmlspecialchars($name) . '-error" ';
    }
    $html .= $attrString . '>';
    
    // Add options
    foreach ($options as $value => $label) {
        $html .= '<option value="' . htmlspecialchars($value) . '" ';
        if ((string)$value === (string)$selected) {
            $html .= 'selected';
        }
        $html .= '>' . htmlspecialchars($label) . '</option>';
    }
    
    $html .= '</select>';
    
    // Add error message if present
    if (!empty($error)) {
        $html .= '<p id="' . htmlspecialchars($name) . '-error" class="mt-1 text-sm text-red-600">';
        $html .= htmlspecialchars($error);
        $html .= '</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate an accessible checkbox or radio button with label
 *
 * @param string $type Either 'checkbox' or 'radio'
 * @param string $name Field name
 * @param string $label Label text
 * @param string $value Input value
 * @param array $attributes Additional attributes
 * @return string HTML for the checkbox/radio
 */
function accessibleCheckboxField($type, $name, $label, $value = '1', $attributes = []) {
    $checked = !empty($attributes['checked']);
    $id = $attributes['id'] ?? $name . '_' . preg_replace('/[^a-z0-9]/i', '_', $value);
    $class = $attributes['class'] ?? 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded';
    
    // Radio buttons have a different style for the ring
    if ($type === 'radio') {
        $class = str_replace('rounded', 'rounded-full', $class);
    }
    
    // Convert boolean attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if (in_array($key, ['class', 'id', 'checked'])) {
            continue; // These are handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<div class="flex items-center mb-2">';
    $html .= '<input type="' . $type . '" id="' . htmlspecialchars($id) . '" ';
    $html .= 'name="' . htmlspecialchars($name) . '" ';
    $html .= 'value="' . htmlspecialchars($value) . '" ';
    $html .= 'class="' . htmlspecialchars($class) . '" ';
    if ($checked) {
        $html .= 'checked ';
    }
    $html .= $attrString . '>';
    
    $html .= '<label for="' . htmlspecialchars($id) . '" class="ml-2 block text-sm text-gray-700">';
    $html .= htmlspecialchars($label);
    $html .= '</label>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate an accessible button
 * 
 * @param string $text Button text
 * @param array $attributes Additional attributes
 * @return string HTML for the button
 */
function accessibleButton($text, $attributes = []) {
    $type = $attributes['type'] ?? 'button';
    $class = $attributes['class'] ?? 'px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500';
    $name = $attributes['name'] ?? '';
    $value = $attributes['value'] ?? '';
    $id = $attributes['id'] ?? '';
    $ariaLabel = $attributes['aria-label'] ?? '';
    
    // Convert boolean attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if (in_array($key, ['class', 'type', 'name', 'value', 'id', 'aria-label'])) {
            continue; // These are handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<button type="' . htmlspecialchars($type) . '" ';
    if (!empty($name)) {
        $html .= 'name="' . htmlspecialchars($name) . '" ';
    }
    if (!empty($value)) {
        $html .= 'value="' . htmlspecialchars($value) . '" ';
    }
    if (!empty($id)) {
        $html .= 'id="' . htmlspecialchars($id) . '" ';
    }
    if (!empty($ariaLabel)) {
        $html .= 'aria-label="' . htmlspecialchars($ariaLabel) . '" ';
    }
    $html .= 'class="' . htmlspecialchars($class) . '" ';
    $html .= $attrString . '>';
    $html .= htmlspecialchars($text);
    $html .= '</button>';
    
    return $html;
}

/**
 * Generate an accessible image tag with proper alt text
 * 
 * @param string $src Image source URL
 * @param string $alt Alt text (or empty for decorative images)
 * @param array $attributes Additional attributes
 * @return string HTML for the image
 */
function accessibleImage($src, $alt, $attributes = []) {
    $class = $attributes['class'] ?? '';
    $id = $attributes['id'] ?? '';
    $isDecorative = empty($alt) || $alt === 'decorative';
    
    // Convert boolean attributes and handle other attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if (in_array($key, ['class', 'id'])) {
            continue; // These are handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<img src="' . htmlspecialchars($src) . '" ';
    
    // Handle alt text properly - empty or null for decorative images
    if ($isDecorative) {
        $html .= 'alt="" role="presentation" aria-hidden="true" ';
    } else {
        $html .= 'alt="' . htmlspecialchars($alt) . '" ';
    }
    
    if (!empty($class)) {
        $html .= 'class="' . htmlspecialchars($class) . '" ';
    }
    
    if (!empty($id)) {
        $html .= 'id="' . htmlspecialchars($id) . '" ';
    }
    
    $html .= $attrString . '>';
    
    return $html;
}

/**
 * Generate an accessible SVG icon with proper accessibility attributes
 * 
 * @param string $name Icon name/identifier
 * @param string $title Accessible title for the icon (or null for decorative)
 * @param array $attributes Additional attributes
 * @return string HTML for the SVG icon
 */
function accessibleIcon($name, $title = null, $attributes = []) {
    $class = $attributes['class'] ?? 'h-5 w-5';
    $isDecorative = empty($title);
    $uniqueId = 'icon-' . $name . '-' . uniqid();
    
    // Convert boolean attributes and handle other attributes
    $attrString = '';
    foreach ($attributes as $key => $val) {
        if ($key === 'class') {
            continue; // This is handled separately
        }
        
        if (is_bool($val)) {
            if ($val) {
                $attrString .= " $key";
            }
        } else {
            $attrString .= " $key=\"" . htmlspecialchars($val) . "\"";
        }
    }
    
    $html = '<svg class="' . htmlspecialchars($class) . '" ';
    $html .= 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" ';
    $html .= 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ';
    
    if ($isDecorative) {
        $html .= 'aria-hidden="true" role="presentation" ';
    } else {
        $html .= 'aria-labelledby="' . $uniqueId . '" role="img" ';
    }
    
    $html .= $attrString . '>';
    
    if (!$isDecorative) {
        $html .= '<title id="' . $uniqueId . '">' . htmlspecialchars($title) . '</title>';
    }
    
    // Define the SVG paths based on the icon name
    switch ($name) {
        case 'search':
            $html .= '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>';
            break;
        case 'user':
            $html .= '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>';
            break;
        case 'check':
            $html .= '<polyline points="20 6 9 17 4 12"></polyline>';
            break;
        case 'x':
            $html .= '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>';
            break;
        case 'calendar':
            $html .= '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>';
            break;
        case 'lock':
            $html .= '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>';
            break;
        case 'mail':
            $html .= '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>';
            break;
        case 'info':
            $html .= '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>';
            break;
        case 'warning':
            $html .= '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>';
            break;
        case 'error':
            $html .= '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>';
            break;
        case 'success':
            $html .= '<circle cx="12" cy="12" r="10"></circle><polyline points="16 9 11 15 8 12"></polyline>';
            break;
        case 'chevron-down':
            $html .= '<polyline points="6 9 12 15 18 9"></polyline>';
            break;
        case 'chevron-right':
            $html .= '<polyline points="9 18 15 12 9 6"></polyline>';
            break;
        case 'chevron-left':
            $html .= '<polyline points="15 18 9 12 15 6"></polyline>';
            break;
        case 'home':
            $html .= '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>';
            break;
        case 'plane':
            $html .= '<path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"></path>';
            break;
        // Add more icons as needed
        default:
            // Default icon or placeholder
            $html .= '<circle cx="12" cy="12" r="10"></circle>';
    }
    
    $html .= '</svg>';
    
    return $html;
}

/**
 * Generate an accessible alert/notification
 * 
 * @param string $message Alert message
 * @param string $type Type of alert (success, error, warning, info)
 * @param array $attributes Additional attributes
 * @return string HTML for the alert
 */
function accessibleAlert($message, $type = 'info', $attributes = []) {
    $id = $attributes['id'] ?? 'alert-' . uniqid();
    $dismissible = isset($attributes['dismissible']) && $attributes['dismissible'];
    
    // Determine alert styles based on type
    $alertClasses = 'p-4 mb-4 rounded-md flex items-start';
    $iconName = 'info';
    
    switch ($type) {
        case 'success':
            $alertClasses .= ' bg-green-50 text-green-800 border border-green-200';
            $iconName = 'success';
            break;
        case 'error':
            $alertClasses .= ' bg-red-50 text-red-800 border border-red-200';
            $iconName = 'error';
            break;
        case 'warning':
            $alertClasses .= ' bg-yellow-50 text-yellow-800 border border-yellow-200';
            $iconName = 'warning';
            break;
        case 'info':
        default:
            $alertClasses .= ' bg-blue-50 text-blue-800 border border-blue-200';
            $iconName = 'info';
            break;
    }
    
    $html = '<div id="' . htmlspecialchars($id) . '" class="' . $alertClasses . '" role="alert">';
    
    // Add icon based on alert type
    $html .= '<div class="flex-shrink-0 mr-3">';
    $html .= accessibleIcon($iconName, ucfirst($type) . ' alert', ['class' => 'h-5 w-5']);
    $html .= '</div>';
    
    // Alert content
    $html .= '<div class="flex-1">';
    $html .= '<p>' . $message . '</p>';
    $html .= '</div>';
    
    // Dismissible button
    if ($dismissible) {
        $html .= '<div class="ml-auto pl-3">';
        $html .= '<button type="button" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" ';
        $html .= 'aria-label="Dismiss" onclick="this.parentElement.parentElement.style.display=\'none\';">';
        $html .= accessibleIcon('x', 'Dismiss', ['class' => 'h-5 w-5']);
        $html .= '</button>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Add a CSRF token field to a form
 * 
 * @return string HTML for the CSRF token input
 */
function csrfTokenField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

/**
 * Create accessible pagination controls
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $baseUrl Base URL for pagination links
 * @param array $queryParams Additional query parameters
 * @param array $attributes Additional attributes
 * @return string HTML for the pagination controls
 */
function accessiblePagination($currentPage, $totalPages, $baseUrl, $queryParams = [], $attributes = []) {
    $currentPage = max(1, min($currentPage, $totalPages));
    $ariaLabel = $attributes['aria-label'] ?? 'Pagination';
    $class = $attributes['class'] ?? 'flex justify-center items-center space-x-2 my-6';
    
    $html = '<nav aria-label="' . htmlspecialchars($ariaLabel) . '" class="' . htmlspecialchars($class) . '">';
    $html .= '<ul class="flex space-x-1">';
    
    // Previous button
    $html .= '<li>';
    if ($currentPage > 1) {
        $queryParams['page'] = $currentPage - 1;
        $prevUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<a href="' . htmlspecialchars($prevUrl) . '" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Previous page">';
        $html .= accessibleIcon('chevron-left', 'Previous page', ['class' => 'h-4 w-4']);
        $html .= '</a>';
    } else {
        $html .= '<span class="px-3 py-2 border border-gray-200 rounded-md text-sm font-medium text-gray-400 bg-gray-50 cursor-not-allowed" aria-disabled="true">';
        $html .= accessibleIcon('chevron-left', 'Previous page (disabled)', ['class' => 'h-4 w-4']);
        $html .= '</span>';
    }
    $html .= '</li>';
    
    // Calculate range of pages to show
    $range = 2; // Show 2 pages before and after current page
    $startPage = max(1, $currentPage - $range);
    $endPage = min($totalPages, $currentPage + $range);
    
    // Always show first page
    if ($startPage > 1) {
        $queryParams['page'] = 1;
        $pageUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<li>';
        $html .= '<a href="' . htmlspecialchars($pageUrl) . '" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Page 1">';
        $html .= '1';
        $html .= '</a>';
        $html .= '</li>';
        
        // Add ellipsis if needed
        if ($startPage > 2) {
            $html .= '<li>';
            $html .= '<span class="px-3 py-2 text-sm text-gray-500" aria-hidden="true">...</span>';
            $html .= '</li>';
        }
    }
    
    // Page links
    for ($i = $startPage; $i <= $endPage; $i++) {
        $html .= '<li>';
        if ($i == $currentPage) {
            $html .= '<span aria-current="page" class="px-3 py-2 border border-blue-500 rounded-md text-sm font-medium text-white bg-blue-600">';
            $html .= $i;
            $html .= '</span>';
        } else {
            $queryParams['page'] = $i;
            $pageUrl = $baseUrl . '?' . http_build_query($queryParams);
            $html .= '<a href="' . htmlspecialchars($pageUrl) . '" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Page ' . $i . '">';
            $html .= $i;
            $html .= '</a>';
        }
        $html .= '</li>';
    }
    
    // Always show last page
    if ($endPage < $totalPages) {
        // Add ellipsis if needed
        if ($endPage < $totalPages - 1) {
            $html .= '<li>';
            $html .= '<span class="px-3 py-2 text-sm text-gray-500" aria-hidden="true">...</span>';
            $html .= '</li>';
        }
        
        $queryParams['page'] = $totalPages;
        $pageUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<li>';
        $html .= '<a href="' . htmlspecialchars($pageUrl) . '" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Page ' . $totalPages . '">';
        $html .= $totalPages;
        $html .= '</a>';
        $html .= '</li>';
    }
    
    // Next button
    $html .= '<li>';
    if ($currentPage < $totalPages) {
        $queryParams['page'] = $currentPage + 1;
        $nextUrl = $baseUrl . '?' . http_build_query($queryParams);
        $html .= '<a href="' . htmlspecialchars($nextUrl) . '" class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Next page">';
        $html .= accessibleIcon('chevron-right', 'Next page', ['class' => 'h-4 w-4']);
        $html .= '</a>';
    } else {
        $html .= '<span class="px-3 py-2 border border-gray-200 rounded-md text-sm font-medium text-gray-400 bg-gray-50 cursor-not-allowed" aria-disabled="true">';
        $html .= accessibleIcon('chevron-right', 'Next page (disabled)', ['class' => 'h-4 w-4']);
        $html .= '</span>';
    }
    $html .= '</li>';
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Create an accessible tab interface
 * 
 * @param array $tabs Array of tab data [id => [title => string, content => string]]
 * @param string $activeTab ID of the active tab
 * @param array $attributes Additional attributes
 * @return string HTML for the tab interface
 */
function accessibleTabs($tabs, $activeTab = null, $attributes = []) {
    if (empty($tabs)) {
        return '';
    }
    
    // Set first tab as active if not specified
    if ($activeTab === null) {
        $activeTab = array_key_first($tabs);
    }
    
    $id = $attributes['id'] ?? 'tabs-' . uniqid();
    $class = $attributes['class'] ?? '';
    
    $html = '<div id="' . htmlspecialchars($id) . '" class="' . htmlspecialchars($class) . '">';
    
    // Tab list
    $html .= '<div role="tablist" class="flex border-b border-gray-200 mb-4" aria-label="Tabs">';
    
    foreach ($tabs as $tabId => $tab) {
        $isActive = $tabId === $activeTab;
        $tabClass = 'px-4 py-2 text-sm font-medium -mb-px';
        $tabClass .= $isActive 
            ? ' border-b-2 border-blue-500 text-blue-600' 
            : ' text-gray-500 hover:text-gray-700 hover:border-gray-300';
        
        $html .= '<button type="button" role="tab" ';
        $html .= 'id="tab-' . htmlspecialchars($tabId) . '" ';
        $html .= 'aria-controls="panel-' . htmlspecialchars($tabId) . '" ';
        $html .= 'aria-selected="' . ($isActive ? 'true' : 'false') . '" ';
        $html .= 'class="' . $tabClass . '" ';
        $html .= 'onclick="switchTab(\'' . htmlspecialchars($id) . '\', \'' . htmlspecialchars($tabId) . '\')">';
        $html .= htmlspecialchars($tab['title']);
        $html .= '</button>';
    }
    
    $html .= '</div>';
    
    // Tab panels
    foreach ($tabs as $tabId => $tab) {
        $isActive = $tabId === $activeTab;
        
        $html .= '<div id="panel-' . htmlspecialchars($tabId) . '" ';
        $html .= 'role="tabpanel" ';
        $html .= 'aria-labelledby="tab-' . htmlspecialchars($tabId) . '" ';
        $html .= ($isActive ? '' : 'hidden ');
        $html .= 'tabindex="0" ';
        $html .= 'class="p-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md">';
        $html .= $tab['content'];
        $html .= '</div>';
    }
    
    // Add JavaScript to switch tabs
    $html .= '<script>
        function switchTab(tabsId, tabId) {
            // Hide all tab panels
            const tabContainer = document.getElementById(tabsId);
            const tabs = tabContainer.querySelectorAll("[role=\'tab\']");
            const panels = tabContainer.querySelectorAll("[role=\'tabpanel\']");
            
            // Deactivate all tabs
            tabs.forEach(tab => {
                tab.setAttribute("aria-selected", "false");
                tab.classList.remove("border-blue-500", "text-blue-600");
                tab.classList.add("text-gray-500");
            });
            
            // Hide all panels
            panels.forEach(panel => panel.hidden = true);
            
            // Activate selected tab
            const selectedTab = document.getElementById("tab-" + tabId);
            selectedTab.setAttribute("aria-selected", "true");
            selectedTab.classList.remove("text-gray-500");
            selectedTab.classList.add("border-blue-500", "text-blue-600");
            
            // Show selected panel
            const selectedPanel = document.getElementById("panel-" + tabId);
            selectedPanel.hidden = false;
            
            // Focus on the panel
            selectedPanel.focus();
        }
    </script>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Safely output content with context-aware encoding
 * 
 * @param string $content Content to encode
 * @param string $context Context for encoding (html, attr, js, url, css)
 * @return string Encoded content
 */
function e($content, $context = 'html') {
    if ($content === null) {
        return '';
    }
    
    switch ($context) {
        case 'html':
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        case 'attr':
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            
        case 'js':
            // For JS contexts, encode all non-alphanumeric characters
            return preg_replace_callback('/[^a-zA-Z0-9,\.\-_]/', function($matches) {
                return sprintf('\\x%02X', ord($matches[0]));
            }, $content);
            
        case 'url':
            return urlencode($content);
            
        case 'css':
            return addslashes($content);
            
        default:
            return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }
}
?>